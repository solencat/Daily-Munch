# Les nouvelles fonctionnalités, et comment les reconstruire

Ce document a deux buts :

1. expliquer **comment découper** un besoin flou en étapes qu'on peut vérifier
   une par une ;
2. donner, pour chaque fonctionnalité de cette branche, **la version la plus
   simple qui marche** — celle que le stagiaire doit écrire lui-même — puis ce
   que la version livrée ajoute par-dessus.

---

## 1. Le principe : déstructurer un gros problème

« Ajouter un suivi des calories » n'est pas une tâche. C'est une phrase qui
contient au moins huit tâches, et tant qu'on ne les a pas séparées, on ne peut
rien finir : chaque ligne écrite dépend d'une décision non prise ailleurs.

Méthode utilisée ici, en trois passes :

**Passe 1 — écrire le besoin en une phrase, avec un verbe et un objet.**
> « Je veux savoir combien de calories j'ai mangées aujourd'hui. »

**Passe 2 — le découper en questions indépendantes.**
> Où sont les aliments ? Comment j'en trouve un ? Quelle quantité ? Où je
> garde le repas ? Qui additionne ? Comment je relis ma journée ? Comment je
> relis une semaine ?

Chaque question devient un sous-problème. Une bonne découpe a trois propriétés :

- **chaque morceau se vérifie seul** : je peux tester « la recherche » sans avoir
  écrit « le graphique » ;
- **chaque morceau a une seule responsabilité** : `ProductRepository` ne calcule
  pas de calories, `MealService` ne dessine pas de barre ;
- **l'ordre est celui des dépendances** : on ne peut pas enregistrer un repas
  avant de savoir identifier un produit.

**Passe 3 — découper encore, jusqu'à ce que chaque morceau tienne en une
session de travail et se teste en une commande.**

Exemple concret, celui de cette branche :

```
« suivre mes calories »
├── 1. un catalogue d'aliments avec leurs calories
│      ├── 1a. table product(code, name, kcal_100g)
│      └── 1b. la remplir (20 lignes à la main → puis import OFF)
├── 2. trouver un aliment            → une requête SQL + un formulaire GET
├── 3. choisir la quantité           → un champ number, défaut 100 g
├── 4. enregistrer un repas          → 2 tables + un POST + redirection
├── 5. calculer les calories         → une multiplication, à UN seul endroit
├── 6. relire une journée            → 1 SELECT + 1 boucle dans le template
└── 7. relire une période
       ├── 7a. agrégat SQL GROUP BY date
       ├── 7b. tableau HTML (toujours là, même sans JS)
       └── 7c. graphique (facultatif)
```

Le point 7c est **facultatif** et c'est volontaire : la version minimale du suivi
est un tableau. Le graphique est une amélioration, pas une condition.

Ce découpage n'est pas décoratif : il explique aussi l'ordre du code. `product`
existe avant `log_meal_product`, qui existe avant le graphique ; et le calcul des
calories est fait par la base (colonne générée) pour qu'il ne puisse pas exister
deux versions divergentes de la même formule.

### Le test de la bonne découpe

Une étape est bien découpée si on peut écrire son critère de réussite sans
regarder les autres. Par exemple :

| Étape | Critère de réussite (vérifiable) |
|---|---|
| 1a | `DESCRIBE product;` montre `kcal_100g` |
| 1b | `SELECT COUNT(*) FROM product;` > 0 |
| 2 | `GET /api/products?q=yaourt` renvoie du JSON avec des noms |
| 3 | le champ quantité est à 100 par défaut |
| 4 | après le POST, `SELECT * FROM log_meal` montre une ligne |
| 5 | `kcal` de la ligne = `quantity_g * kcal_100g / 100` |
| 6 | la page affiche le repas avec son total |
| 7a | `SELECT date_meal, SUM(kcal) … GROUP BY date_meal` renvoie une ligne par jour |
| 7b | le tableau HTML contient les mêmes chiffres (vérifiable sans JS) |

---

## 2. Fonctionnalité 1 — Composer un repas (`/repas`)

**Besoin.** « Je cherche un produit, je mets une quantité, j'enregistre mon
repas. »

### Version minimale (à écrire par le stagiaire)

Objectif : **une page, un formulaire, pas de JavaScript, pas d'Open Food Facts.**

```sql
CREATE TABLE produit (
    code VARCHAR(20) PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    kcal_100g DECIMAL(6,2) NOT NULL
);
INSERT INTO produit VALUES ('0001','Pain complet',254), ('0002','Yaourt nature',62);
```

```php
// liste des produits, filtrée par un champ GET
$q = trim($_GET['q'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM produit WHERE nom LIKE ? ORDER BY nom LIMIT 20");
$stmt->execute(['%' . $q . '%']);
$produits = $stmt->fetchAll();
```

```html
<form method="GET">
  <label for="q">Rechercher</label>
  <input id="q" name="q" value="<?= htmlspecialchars($q) ?>">
  <button>Chercher</button>
</form>
<!-- chaque résultat est un lien qui renvoie le code -->
```

Puis le formulaire d'ajout : `code` en champ caché, `quantity_g` avec
`value="100"`, un bouton. Le POST insère dans `repas(date_repas, nom, id_user)`
et `repas_produit(id_repas, nom_produit, quantite_g, kcal_100g)`.

**Ce qu'il faut savoir faire à ce stade** : préparer une requête, échapper
l'affichage, rediriger après un POST (pattern POST → Redirect → GET), refuser un
utilisateur non connecté.

### Ce que la branche `exemple` ajoute, dans cet ordre

| Ajout | Pourquoi | Où |
|---|---|---|
| Table `product` alimentée par Open Food Facts | on ne saisit pas 900 000 aliments à la main | `scripts/import-off.py` |
| Recherche `FULLTEXT` + repli `LIKE` | `LIKE '%x%'` sur 896 000 lignes ne passe pas l'échelle ; mesuré à ~140 ms en FULLTEXT | `ProductRepository::search()` |
| Recherche en AJAX (debounce 250 ms, `AbortController`) | ne pas recharger la page à chaque frappe | `public/js/repas.js` |
| Portion par défaut issue d'Open Food Facts | `serving_quantity` existe pour 15 % des produits ; évite de retaper 30 g | `search()` + `repas.js` |
| Panier côté navigateur + total en direct | composer 4 produits sans 4 allers-retours serveur | `repas.js` |
| Recalcul serveur des calories | le navigateur n'est pas une source de vérité | `MealService::validateItems()` |
| Colonne générée `kcal` | la formule n'existe qu'à un endroit | `bdd.sql:86` |
| Liste de la journée + suppression | relire et corriger ce qu'on vient de saisir | `MealController::index()`, `destroy()` |
| Accessibilité (labels, `aria-live`, cibles 44 px) | exigence du projet | templates + CSS |

### Pièges, tous rencontrés pour de vrai

- `$_POST["items"]` reçu en JSON **doit être revalidé** : codes inconnus, quantité
  hors bornes, tableau vide. Un client peut envoyer n'importe quoi.
- Les calories ne se lisent **jamais** dans la requête : on relit `kcal_100g` en
  base, sinon on peut gonfler ou vider un repas depuis la console.
- `min="0.1"` + `step="1"` rend `100` invalide côté HTML5 (voir B1).
- Un nom de produit peut contenir des guillemets : `htmlspecialchars` à
  l'affichage, requêtes préparées en base. Jamais l'inverse.

### Critères de réussite

1. `POST /repas` avec un produit connu crée **une** ligne `log_meal` et **n**
   lignes `log_meal_product`.
2. Le `kcal` stocké vérifie `quantity_g * kcal_100g / 100`.
3. Poster un code inconnu ne crée rien et affiche une erreur compréhensible.
4. Poster `quantity_g = 99999` est refusé.
5. La page fonctionne sans JavaScript pour l'affichage (liste de la journée).

---

## 3. Fonctionnalité 2 — Suivi des calories (`/suivi`)

**Besoin.** « Je veux voir mes calories jour par jour sur une période. »

### Version minimale

Une seule requête, un tableau, aucun graphique :

```sql
SELECT m.date_meal AS jour,
       COUNT(DISTINCT m.id_log_meal) AS repas,
       COALESCE(SUM(p.kcal), 0) AS kcal
FROM log_meal m
LEFT JOIN log_meal_product p ON p.id_log_meal = m.id_log_meal
WHERE m.id_user_identification = ? AND m.date_meal BETWEEN ? AND ?
GROUP BY m.date_meal
ORDER BY m.date_meal;
```

Trois points à comprendre dans cette requête :

- `LEFT JOIN` : un repas sans produit doit quand même apparaître (sinon le total
  du jour est faux) ;
- `COUNT(DISTINCT m.id_log_meal)` : sans `DISTINCT`, un repas de 3 produits
  compterait pour 3 repas ;
- `COALESCE(SUM(...), 0)` : `SUM` renvoie `NULL` sur un repas vide, pas `0`.

Ensuite, en PHP : compléter les jours manquants à zéro, et boucler dans un
tableau HTML. **C'est déjà une fonctionnalité complète.**

### Le graphique, étape par étape

Ne pas commencer par D3. Trois paliers, chacun utilisable :

1. **Tableau** (obligatoire) : c'est la version accessible, et elle sert de
   référence pour vérifier les deux suivantes.
2. **Barres en CSS** : mêmes chiffres, `div` avec
   `style="height: <?= $pct ?>%"`. Pas de dépendance, pas de JS. Pour une
   semaine c'est très suffisant.
3. **D3 / SVG** : utile au-delà de ~15 jours, pour les axes, l'échelle
   automatique et les infobulles.

Ce que la branche `exemple` fait au niveau 3 : échelle calculée sur le maximum,
ligne de moyenne, barre du jour en jaune, `<title>` par barre, et **le tableau
reste affiché sous le graphique** — le SVG est `aria-hidden` et le conteneur porte
un résumé en `aria-label`.

### Ce qu'il faut savoir faire

- dériver une échelle : `y = hauteur * (valeur / maximum)` ;
- choisir un plafond lisible : arrondir au cent supérieur
  (`Math.ceil(max * 1.15 / 100) * 100`) évite des barres collées au bord ;
- gérer le cas vide : période sans repas → message, pas un graphique cassé ;
- ne pas déformer : `viewBox` et ratio doivent rester cohérents (voir B4).

### Critères de réussite

1. La page sans repas affiche « Aucun repas enregistré sur cette période » et des
   totaux à 0 — pas d'erreur.
2. Les chiffres du tableau sont **identiques** à un `SELECT … GROUP BY` lancé à
   la main. (C'est le test le plus important : le tableau est la référence.)
3. Un jour sans repas apparaît à 0 kcal, pas absent.
4. `GET /suivi?periode=30` change la fenêtre, et un `periode` inconnu retombe sur
   la valeur par défaut (liste blanche, jamais de paramètre libre dans le SQL).
5. À 390 px de large, aucun débordement horizontal (`scrollWidth === innerWidth`).

---

## 4. Fonctionnalité 3 — Le catalogue Open Food Facts

**Besoin.** « Ne pas saisir les aliments à la main. »

### Version minimale

20 lignes `INSERT` dans `bdd.sql`. Sérieusement : c'est la bonne première étape,
elle permet d'écrire tout le reste sans dépendre d'un fichier de 1,5 Go.

### Version livrée

`scripts/import-off.py` lit l'export TSV d'Open Food Facts en flux (jamais
chargé en mémoire), garde les produits vendus en France avec un nom et des
calories, et écrit des `INSERT` sur la sortie standard, branchés sur `mysql` :

```bash
python scripts/import-off.py --source products.csv.gz | mysql -u root dailymunch
```

Résultat mesuré : 4 929 745 lignes lues, **895 880 produits** en base, 430 Mo,
15 % avec une portion renseignée, 95 % avec une image.

Points de vigilance : un flux gz se lit avec `gzip.open(...)` + `csv.reader`, mais
le séparateur est une **tabulation** ; les colonnes de l'export changent avec le
temps, donc on les cherche par nom, jamais par position ; les codes-barres ne sont
pas uniques dans la source (16 doublons ici).

---

## 5. Plan de travail pour le stagiaire

À faire dans cet ordre. Chaque étape a son critère ; une étape non vérifiée n'est
pas une étape terminée. Le code de la branche `exemple` sert de référence en cas
de blocage, pas de point de départ.

| # | Étape | Livrable | Critère de réussite |
|---|---|---|---|
| 1 | Schéma minimal | `produit`, `repas`, `repas_produit` dans ton `bdd.sql` | base recréée à neuf depuis le fichier, sans erreur |
| 2 | Catalogue de test | 20 lignes insérées | `SELECT COUNT(*) FROM produit` = 20 |
| 3 | Page liste + recherche en GET | `/repas` sans JS | taper « yaourt » filtre la liste |
| 4 | Enregistrement d'un repas | POST + redirection | une ligne en base, quantité 100 par défaut |
| 5 | Calcul des calories | en SQL ou en PHP, à un seul endroit | `kcal` = `quantite * kcal_100g / 100` |
| 6 | Journée | liste + total du jour | total = somme des lignes affichées |
| 7 | Suivi par période | `/suivi` avec tableau | chiffres identiques à un `GROUP BY` manuel |
| 8 | Implémentation à toi : import OFF | script d'import | ≥ 10 000 produits, recherche < 300 ms |
| 9 | Recherche en AJAX + panier | JS | composer 3 produits sans recharger la page |
| 10 | Graphique | CSS **ou** SVG | tableau et graphique affichent les mêmes chiffres |
| 11 | Accessibilité | labels, focus, contraste | navigation au clavier complète, ratios ≥ 4.5:1 |

Estimation : étapes 1 à 7 = le cœur (2 à 3 jours), 8 à 11 = les améliorations.

### Règles

- **Une étape = un commit** avec un message qui dit *pourquoi*, pas seulement
  *quoi*.
- **Aucun `var_dump` laissé**, aucun `dd()`, aucun mot de passe en dur.
- Toute requête SQL avec une variable passe par une requête préparée.
- Toute valeur affichée passe par `htmlspecialchars`.
- Le stagiaire décrit son découpage **avant** d'écrire la première ligne d'une
  étape. Si le découpage tient en une phrase vérifiable, l'étape est prête.

### Comment on vérifiera

Pas de « ça marche chez moi ». On vérifiera en base (`SELECT`), en HTTP
(`curl -i`, codes de statut, JSON) et dans le navigateur (parcours complet,
puis largeur 390 px). Les critères du tableau ci-dessus sont les mêmes que ceux
utilisés pour valider cette branche `exemple`.
