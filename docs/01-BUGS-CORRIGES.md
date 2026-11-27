# Ce qui a été corrigé, et pourquoi

Trois familles de problèmes. Les deux premières étaient déjà dans `development`
avant que j'ajoute quoi que ce soit : elles empêchaient le projet de tourner sur
une autre machine. La troisième famille, ce sont des défauts que j'ai introduits
pendant l'implémentation et corrigés ensuite — ils sont documentés aussi, parce
que ce sont exactement les pièges dans lesquels on tombe en écrivant ces
fonctionnalités.

Chaque fiche suit le même plan : symptôme observé → cause → correction →
comment vérifier.

---

## A. Bugs présents dans `development`

### A1. Impossible de créer un compte : la confirmation du mot de passe échoue toujours

**Symptôme.** `POST /register` avec deux mots de passe identiques renvoie
toujours « Les mots de passes ne correspondent pas ». Aucune ligne en base,
aucune erreur serveur.

**Cause.** `src/Utils/UserFormValidator.php` déclarait la règle `satisfies` avec
deux paramètres :

```php
->satisfies(function ($value, $input) {
    return isset($input["pass"]) && $value === $input["pass"];
})
```

Lemmon n'appelle pas les règles comme ça. Dans
`vendor/lemmon/validator/src/Lemmon/Validator/FieldValidator.php`, la règle est
invoquée avec **trois** arguments : la valeur, la **clé du champ**, puis le
tableau complet.

```php
return (bool) $rule($value, $key, $input);   // ligne 432
```

Donc `$input` valait la chaîne `"confirm-pass"`, pas le tableau. `isset()` sur un
offset de chaîne renvoie `false`, la comparaison échouait toujours, et le message
d'erreur était juste assez plausible pour qu'on cherche ailleurs.

**Correction.** `function ($value, $key, $input)` (ligne 29).

**Vérification.** `POST /register` avec `pass` = `confirm-pass` ≥ 16 caractères
renvoie « Le compte … a bien été enregistré ! » et la ligne apparaît dans
`user_identification`.

**Ce que ça apprend.** Quand une bibliothèque documente mal ses signatures, la
source est l'autorité : on ouvre le fichier qui appelle le callback. Une règle
qui échoue « pour toutes les valeurs » est presque toujours une erreur de
signature, pas une erreur de logique.

---

### A2. La session n'a pas d'identifiant : `getId()` renvoie `null`

**Symptôme.** La connexion affiche « Connecté », mais `$_SESSION["user"]["id"]`
vaut `null`. Toute donnée rattachée à l'utilisateur devient impossible à écrire.

**Cause.** `src/Entity/User.php`, `hydrate()` lisait `$user["id_user"]`, une clé
qui n'existe pas : la requête de `UserRepository::findByEmail()` renvoie
`id_user_identification`. `setId(null)` était donc appliqué sans erreur.

**Correction.** Lecture de `id_user_identification` (ligne 148), avec la même
occasion pour le rôle : `id_role_access` sert de repli (ligne 150).

**Vérification.**

```sql
SELECT id_user_identification FROM user_identification WHERE email = '…';
```

doit être égal à ce que la page affiche après connexion (par exemple dans
`$_SESSION` via un `var_dump` temporaire). Test fonctionnel : enregistrer un
repas fonctionne (il faut un `id_user_identification` valide pour la clé
étrangère).

**Ce que ça apprend.** `?? null` masque les fautes de frappe. Un hydrateur qui
lit des clés non vérifiées est le pire endroit pour un `??` : la faute devient
silencieuse. Un `array_key_exists` explicite, ou un test qui vérifie l'id après
connexion, l'aurait attrapée.

---

### A3. `bdd.sql` ne peut pas recréer la base : `size` au lieu de `height`

**Symptôme.** Sur une base créée neuve à partir du fichier livré, l'inscription
échoue : `SQLSTATE[42S22] Unknown column 'height' in 'field list'`.

**Cause.** `bdd.sql` déclarait `size INT`, alors que tout le code PHP écrit et
lit `height` (`User::hydrate`, `UserRepository::create`, `UserFormValidator`).
La base de dev locale avait été modifiée à la main, donc le bug n'était visible
que sur une installation neuve.

**Correction.** `bdd.sql` ligne 18 : `height INT NOT NULL`.

**Vérification.** Créer un schéma jetable, importer le fichier, lancer
l'`INSERT` du dépôt :

```sql
CREATE DATABASE dm_check;
-- puis, avec le nom de base réécrit dans bdd.sql :
INSERT INTO user_identification(firstname,lastname,height,weight,email,pass,id_role_access)
VALUES ('a','b',180,80,'a@b.c','x',2);
```

Doit passer. Avant correction : `ERROR 1054`.

**Ce que ça apprend.** Un `ALTER TABLE` fait à la main sur la base de dev crée
une dette invisible. La règle : tout changement de schéma passe par le fichier
`.sql`, jamais par un client SQL seul. Test : « est-ce que je peux recréer ma
base depuis le dépôt, ce soir, sans réfléchir ? »

---

### A4. `role_access` vide : l'inscription viole la clé étrangère

**Symptôme.** Toujours sur une base neuve, même avec `height` corrigé :
`ERROR 1452 Cannot add or update a child row: a foreign key constraint fails`.

**Cause.** Le code PHP écrit `id_role_access = 2` en dur, mais `bdd.sql` créait
la table `role_access` vide. Aucun rôle 2 n'existait.

**Correction.** Deux lignes d'amorçage, idempotentes (ligne 12) :

```sql
INSERT IGNORE INTO role_access(id_role_access, name) VALUES (1, 'admin'), (2, 'user');
```

**Vérification.** `SELECT * FROM role_access;` renvoie 1 = admin, 2 = user dès
l'import du fichier.

**Ce que ça apprend.** Une clé étrangère sans données de référence est une bombe
à retardement : elle ne casse pas à la création des tables, mais au premier
`INSERT` venu d'ailleurs.

---

### A5. Aucune session ne survit à une requête (environnement)

**Symptôme.** « Connecté » s'affiche, puis la requête suivante renvoie vers
`/connection`. Aucune erreur. Les fichiers de session créés faisaient 0 octet.

**Cause.** Dans `php.ini`, `session.save_path` n'était pas défini. PHP prend
alors `sys_get_temp_dir()`, qui vaut `C:\WINDOWS` pour le processus CGI lancé par
Apache — un dossier non inscriptible. `session_start()` échoue, sans que l'échec
remonte dans la page.

Sonde utilisée pour le prouver (temporaire, supprimée depuis) :

```
session_id  : "" (vide)
save_path   : C:\WINDOWS
writable    : non
```

**Correction.** `C:\tools\php\php.ini` :

```ini
session.save_path = "C:/laragon/tmp"
```

**Vérification.** Recharger une page qui écrit en session deux fois de suite avec
le même cookie : le `session_id` reste le même. Ici, après correction :
`5d9821e4…` sur les deux requêtes.

**Ce que ça apprend.** « Ça marche » ne veut rien dire si on ne vérifie que la
première requête. Un échec de session est silencieux par conception : il faut
lire `session_id()` et `session_save_path()`, pas seulement le message affiché.

---

### A6. Le jour courant est refusé : « la date du repas ne peut pas être dans le futur »

**Deux causes, corrigées ensemble.**

**a) Fuseau horaire.** `date.timezone` n'était pas défini : PHP tournait en UTC
alors que la machine est en UTC+1. Entre 00 h et 01 h, « aujourd'hui » côté PHP
est encore hier.

```ini
date.timezone = Europe/Paris
```

**b) `createFromFormat` complète l'heure courante.** Avec le format `"Y-m-d"`,
PHP garde l'heure du moment pour les champs absents : la date « 27/11 » devenait
« 27/11 à 14 h 02 », donc supérieure à `new DateTimeImmutable("today")`
(27/11 à 00 h 00) — la date du jour était rejetée comme future.

```php
$parsed = \DateTimeImmutable::createFromFormat("!Y-m-d", $date); // "!" = minuit
```

Le `!` remet tous les champs non spécifiés à zéro. Corrigé dans
`src/Service/MealService.php:191` et `src/Controller/MealController.php`.

**Vérification.**

```php
var_dump(\DateTimeImmutable::createFromFormat("Y-m-d", "2026-11-27") > new \DateTimeImmutable("today")); // true : bug
var_dump(\DateTimeImmutable::createFromFormat("!Y-m-d", "2026-11-27") > new \DateTimeImmutable("today")); // false : ok
```

Puis : enregistrer un repas daté d'aujourd'hui doit passer.

**Ce que ça apprend.** Une comparaison de dates sans heure est un piège permanent.
Format `!Y-m-d` pour une date pure, et toujours se demander « à quelle heure la
journée commence pour cet utilisateur ».

---

### A7. Déconnexion automatique au bout de ~24 minutes

**Symptôme.** Session perdue sans action, retour à la page de connexion.

**Cause.** `session.gc_maxlifetime = 1440` (24 min). Avec le gestionnaire de
sessions par fichiers et l'écriture différée, une session peut ne pas être
réécrite à chaque requête : son horodatage vieillit, le ramasse-miettes la
supprime.

**Correction.** `session.gc_maxlifetime = 86400` (24 h), pour un poste de dev.

**Vérification.** Se connecter, attendre, recharger — ou lire
`ini_get("session.gc_maxlifetime")` dans une page PHP.

**Ce que ça apprend.** La durée de vie d'une session est une décision produit, pas
un défaut de PHP. Sur un vrai site, on l'aligne sur la politique de sécurité, et
on préfère un cookie qui se renouvelle.

---

### A8. Icône de déconnexion absente

**Symptôme.** `GET /images/logout.png` → 404, et le texte alternatif « logout
icon » s'affiche à la place de l'icône dans l'en-tête.

**Cause.** `templates/components/component_header.php` pointait vers un fichier
jamais livré (`public/images/` ne contient que `account_circle.png`, `login.png`,
`Logo.png`).

**Correction.** `public/images/logout.svg` (icône inline, `stroke` blanc pour le
fond marine) et `alt=""` — le libellé « DECONNEXION » est juste à côté, donc
l'image ne doit pas être annoncée deux fois.

**Vérification.** `curl -o /dev/null -w '%{http_code}' /images/logout.svg` → 200,
et plus de texte alternatif visible.

**Ce que ça apprend.** Un `alt` sur une image décorative double l'information au
lecteur d'écran ; et un `alt` visible signale un fichier manquant.

---

### A9. Les liens de navigation pointent vers des pages inexistantes

**Symptôme.** REPAS et SUIVI renvoient un corps « Erreur 404 » **avec un code HTTP
200**, et restent sur place : le routeur `echo` l'erreur sans changer le statut.

**Cause.** Les liens venaient des maquettes (`./repas.html`, `./suivi.html`),
alors que l'application route sur des chemins PHP (`/repas`, `/suivi`).

**Correction.** `component_header.php` et `component_footer.php` pointent vers
`/repas` et `/suivi`, avec `class='tab-active'` quand on est sur la page.

**Vérification.** Cliquer REPAS puis SUIVI : l'URL change et l'onglet courant est
surligné.

**Ce que ça apprend.** Un 404 qui répond 200 casse le référencement, les caches et
les tests. `http_response_code(404)` avant d'afficher l'erreur serait la
correction complète — elle reste à faire, et c'est un exercice facile.

---

### A10. `public/js/vendor/d3.min.js` ignoré par git

**Symptôme.** Sur un clone neuf, le graphique du suivi ne s'affiche pas.

**Cause.** `.gitignore` contenait `vendor/` sans ancre. Ce motif ignore **tout**
dossier nommé `vendor`, y compris `public/js/vendor/` où j'ai copié D3 en local
(pas de CDN : l'application doit fonctionner hors ligne).

**Correction.** `/vendor/` (seulement la racine, là où Composer installe).

**Vérification.** `git check-ignore -v public/js/vendor/d3.min.js` ne renvoie
rien, et le fichier apparaît dans `git status`.

**Ce que ça apprend.** Un `.gitignore` trop large ne se voit qu'au moment où
quelqu'un d'autre clone le dépôt. La bonne question : « ce motif peut-il
correspondre à un dossier que je veux versionner ? »

---

## B. Défauts introduits pendant l'implémentation, puis corrigés

Ces cinq-là sont les plus utiles à lire : ce sont des erreurs normales quand on
construit ces pages, et elles se détectent toutes en **mesurant**, pas en
regardant.

### B1. Le formulaire ne s'envoyait pas du tout (validation HTML5)

**Symptôme.** Cliquer « Enregistrer le repas » ne déclenchait aucune requête : le
journal Apache ne montrait aucun `POST /repas`.

**Cause.** Les champs de quantité étaient créés avec `min="0.1"` et `step="1"`.
Avec un `min` à 0,1, les valeurs autorisées sont 0,1 / 1,1 / 2,1 … donc **100 est
invalide**. Le navigateur bloquait l'envoi en silence, avec un message
« Les deux valeurs valides les plus proches sont 99,1 et 100,1 ».

**Correction.** `step="any"` (`public/js/repas.js`), le serveur bornant déjà à
0,1–5 000 g.

**Vérification.** `document.getElementById('form-repas').checkValidity()` doit
être `true` avant l'envoi, et le journal doit montrer le `POST`.

**Ce que ça apprend.** « Le bouton ne fait rien » → regarder le journal réseau
avant de lire le code PHP. Et `min` + `step` ne sont pas indépendants.

---

### B2. Les cartes débordaient de 26 px sur mobile

**Symptôme.** À 390 px de large, `.carte` mesurait **410 px** : le contenu était
rogné à droite, sans barre de défilement horizontale (donc invisible).

**Cause.** Aucun `box-sizing: border-box`. Une carte en `width: 100%` avec du
`padding` fait `100% + padding`.

**Correction.** `box-sizing: border-box` limité à la page concernée
(`.repas-page *`, `.suivi-page *`), pour ne pas toucher au reste du site.

**Vérification.** Mesurer `document.querySelector('.carte').getBoundingClientRect().width`
≤ largeur de la fenêtre, et `document.documentElement.scrollWidth === window.innerWidth`.
Après correction : 373 px pour 390, `clipped: 0`.

**Ce que ça apprend.** Un débordement horizontal ne se voit pas toujours à l'œil
(le navigateur le masque) : on le mesure. `scrollWidth` vs `innerWidth` est deux
lignes de JavaScript qui remplacent une relecture.

---

### B3. Le bouton principal échouait au contraste AA

**Symptôme.** Texte blanc sur `#2a73f0` : **4,37:1**, sous le minimum WCAG AA de
4,5:1 pour du texte normal.

**Correction.** `#1b5ccd` (6,07:1) et `#1150b5` au survol (7,4:1).

**Vérification.** Calcul du ratio sur les couleurs calculées, pas sur la palette :

```
#1b5ccd sur blanc = 6.07:1
#1150b5 sur blanc = 7.40:1
```

**Ce que ça apprend.** Le contraste se calcule sur la couleur **rendue**
(`getComputedStyle`), pas sur la variable CSS ; et 4,37 est juste assez bon pour
tromper l'œil.

---

### B4. Le graphique s'étirait verticalement

**Symptôme.** À 1440 px, le SVG mesurait **941 × 1142** alors que son `viewBox`
était `941 × 400` : barres étirées 2,8× verticalement.

**Cause.** `.graphique svg { width: 100%; height: auto }` : quand un
redimensionnement est manqué, `height: auto` recalcule la hauteur d'après
l'**ancien** `viewBox`. Le dessin n'est plus à l'échelle.

**Correction.** Garder la taille dessinée, et seulement `max-width: 100%` pour
réduire proprement : le pire cas devient « un peu trop petit », jamais « déformé ».

**Vérification.** `rect.width / rect.height` doit égaler
`viewBox.width / viewBox.height`. Mesuré à 320/390/768/1024/1440 : vrai partout
après correction.

**Ce que ça apprend.** `width: 100%` sur un SVG sans `preserveAspectRatio`
explicite suppose que le viewBox est à jour. Si le dessin est piloté par JS, le
CSS ne doit pas décider de la taille.

---

### B5. Le graphique restait à son ancienne taille dans un onglet en arrière-plan

**Symptôme.** Redimensionnement sans redessin.

**Cause.** Le redessin était appelé dans `requestAnimationFrame`, qui ne se
déclenche pas (ou très tard) quand la page n'est pas rendue — onglet inactif,
fenêtre masquée. Le graphique gardait sa taille précédente.

**Correction.** Redessin synchrone dans le gestionnaire (gardé par une comparaison
de largeur, donc pas de boucle). `ResizeObserver` et `window.resize` partagent le
même garde-fou.

**Vérification.** Changer la largeur puis lire l'attribut `width` du SVG : il doit
suivre le conteneur (266 → 280, 336 → 336, 660 → 660, 941 → 941).

**Ce que ça apprend.** Le throttling des onglets est réel : tout code de rendu
différé doit avoir un chemin synchrone de secours, ou être testé onglet visible.

---

### B6. Deux façons de retrouver l'utilisateur courant

**Symptôme.** `MealController::destroy()` lisait `$_SESSION["user"]["id"]`
directement, alors que `handle()` avait une résolution plus complète (repli par
email pour les anciennes sessions). Selon la page, on avait un identifiant ou
`null`.

**Correction.** Une seule méthode protégée `currentUserId()` dans
`AbstractController`, utilisée par les deux contrôleurs.

**Ce que ça apprend.** Une règle (ici : « comment obtenir l'id de la session »)
doit exister à un seul endroit. La duplication ne se voit pas dans un fichier,
elle se voit quand les deux copies divergent.

---

### B7. L'import comptait 16 produits de trop

**Symptôme.** Le script annonçait « 895 896 produits écrits », la table en
contenait 895 880.

**Cause.** `INSERT IGNORE` ignore les codes-barres présents deux fois dans
l'export Open Food Facts ; le script comptait les lignes émises, pas les lignes
insérées.

**Correction.** La différence est désormais documentée (ce n'est pas une erreur de
comptage, c'est 16 doublons de la source). Compter `SELECT COUNT(*)` après import
reste la seule vérité.

**Ce que ça apprend.** Un compteur applicatif qui n'est pas le compteur de la base
n'est pas une preuve. Pour vérifier « combien j'ai inséré », on interroge la base.

---

## Récapitulatif

| # | Problème | Type | Fichier |
|---|---|---|---|
| A1 | Confirmation du mot de passe toujours fausse | bug | `src/Utils/UserFormValidator.php` |
| A2 | `getId()` renvoie `null` | bug | `src/Entity/User.php` |
| A3 | `size` au lieu de `height` | bug | `bdd.sql` |
| A4 | `role_access` vide | bug | `bdd.sql` |
| A5 | Sessions non persistées | environnement | `php.ini` |
| A6 | Date du jour refusée | bug | `MealService`, `php.ini` |
| A7 | Déconnexion après 24 min | environnement | `php.ini` |
| A8 | Icône manquante | bug | `component_header.php` |
| A9 | Liens `.html` morts | bug | `component_*.php` |
| A10 | `vendor/` trop large | configuration | `.gitignore` |
| B1 | Formulaire bloqué par HTML5 | défaut d'implémentation | `public/js/repas.js` |
| B2 | Cartes trop larges sur mobile | défaut d'implémentation | `public/css/repas.css` |
| B3 | Contraste 4,37:1 | défaut d'implémentation | `public/css/repas.css` |
| B4 | Graphique étiré | défaut d'implémentation | `public/css/suivi.css` |
| B5 | Redessin jamais exécuté | défaut d'implémentation | `public/js/suivi.js` |
| B6 | Identifiant utilisateur dupliqué | défaut d'implémentation | `src/Controller/*` |
| B7 | Compteur d'import faux | défaut d'implémentation | `scripts/import-off.py` |
