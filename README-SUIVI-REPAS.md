# Suivi des repas — implementation

Journal alimentaire de Daily Munch : rechercher un produit, choisir une
quantité, puis enregistrer un repas daté. Les calories sont calculées à
partir des valeurs Open Food Facts (pour 100 g) importées en local.

Page : `/repas` (lien `REPAS` dans l'en-tête et le pied de page).

## Ce qui a été ajouté

| Fichier | Rôle |
|---|---|
| `src/Controller/MealController.php` | routes `/repas`, `/repas/supprimer`, `/api/products` |
| `src/Service/MealService.php` | validation, calcul des calories, recherche |
| `src/Repository/ProductRepository.php` | recherche dans le catalogue OFF (FULLTEXT) |
| `src/Repository/MealRepository.php` | écriture/lecture des repas (transaction) |
| `src/Entity/Meal.php`, `src/Entity/MealItem.php` | objets manipulés par les vues |
| `templates/template_repas.php` | formulaire + repas de la journée |
| `public/js/repas.js` | recherche, sélection, total en direct |
| `public/css/repas.css` | styles (contraste élevé, cibles ≥ 44 px) |
| `scripts/import-off.py` | import de la base Open Food Facts |
| `bdd.sql` | tables `product`, `log_meal`, `log_meal_product` |

## Base de données

Trois tables (voir la fin de `bdd.sql`) :

- `product` : extrait du catalogue Open Food Facts, valeurs pour 100 g ;
- `log_meal` : un repas daté (`date_meal`, `name`) pour un utilisateur ;
- `log_meal_product` : les produits du repas. `kcal_100g` est recopié à
  l'ajout et `kcal` est une colonne générée (`quantity_g * kcal_100g / 100`),
  donc un repas passé ne change pas quand Open Food Facts met à jour une fiche.

## Importer Open Food Facts

L'export complet fait 1,5 Go compressé (~5 millions de lignes). Le script le
lit en flux, garde les produits vendus en France avec un nom et des calories,
et écrit des `INSERT` sur la sortie standard :

```bash
curl -L -o products.csv.gz \
  https://static.openfoodfacts.org/data/en.openfoodfacts.org.products.csv.gz

python scripts/import-off.py --source products.csv.gz | mysql -u root dailymunch
```

Options : `--countries france,belgium` (défaut `france`), `--batch 2000`,
`--limit 5000` pour un essai rapide.

Résultat sur cette machine : 4 930 000 lignes lues, 895 880 produits importés,
table `product` d'environ 430 Mo. L'import dure quelques minutes.

## Recherche

`ProductRepository::search()` interroge l'index FULLTEXT (`name_normalized`,
mode booléen avec préfixe : `pate* tartiner*`). En dessous de 3 caractères —
la longueur minimale d'un token InnoDB — la recherche passe par
`name_normalized LIKE 'x%'`. Mesure sur 896 000 lignes : ~140 ms.

La normalisation PHP (`ProductRepository::normalize`) doit rester alignée avec
celle du script d'import, sinon la recherche ne trouve plus les produits
accentués.

## Comportement de l'interface

- Recherche à partir de 2 caractères, 250 ms après la dernière frappe (un
  `AbortController` annule la requête précédente).
- Quantité pré-remplie avec la portion normale indiquée par Open Food Facts
  (`serving_quantity`) quand elle existe, sinon 100 g.
- « Ajouter » ajoute la ligne ; ajouter deux fois le même produit cumule les
  quantités. La quantité reste modifiable dans la liste.
- Total du repas recalculé à chaque changement, annoncé via `aria-live`.
- « Enregistrer le repas » envoie uniquement les codes produit et les
  quantités : **les calories sont recalculées côté serveur** à partir du
  catalogue, jamais reprises du navigateur.
- La journée en cours est listée sous le formulaire, avec le total du jour, la
  navigation jour précédent/suivant et la suppression d'un repas.

## Page de suivi (calories consommées)

Route `/suivi`, alimentée uniquement par les repas : chaque jour de la période
est la somme des `log_meal_product.kcal` de la journée.

- Périodes : 7, 14, 30 ou 90 jours (`/suivi?periode=30`).
- Quatre chiffres : aujourd'hui, moyenne par jour renseigné, jours renseignés,
  jour le plus élevé.
- Graphique en barres D3 (copie locale `public/js/vendor/d3.min.js`, v7.9.2,
  aucun CDN en production) : une barre par jour, valeur en kcal au-dessus,
  barre jaune pour aujourd'hui, ligne pointillée pour la moyenne.
- Le même contenu est toujours présent en tableau (`Jour / Repas / Calories`),
  avec la ligne du jour mise en évidence et le total de la période : le
  graphique est `aria-hidden`, le conteneur porte un résumé en `aria-label`,
  donc rien ne dépend de la lecture du SVG.
- `ResizeObserver` : le graphique se redessine au changement de largeur.

## Comptes et données de test

- `repas.test@example.com` / `motdepassecorrect16`, avec trois repas déjà
  enregistrés le 27/11/2026.

## Correctifs faits au passage (hors périmètre initial)

- `src/Utils/UserFormValidator.php` : la règle `satisfies` était appelée avec
  `($value, $input)` alors que Lemmon passe `($value, $key, $input)` ; le
  deuxième paramètre est la clé du champ, donc la comparaison des mots de
  passe échouait toujours et **l'inscription ne pouvait pas réussir**.
- `src/Entity/User.php` : `hydrate()` lisait `id_user` (inexistant) au lieu de
  `id_user_identification`, d'où un `getId()` à `null` et une session sans
  identifiant — bloquant pour toute donnée rattachée à l'utilisateur.
- `bdd.sql` : colonne `size` → `height` (le code utilise `height` partout) et
  `INSERT IGNORE` des rôles 1 = admin, 2 = user (sans eux, l'inscription viole
  la clé étrangère sur une base créée à neuf).
- `public/images/logout.svg` : l'en-tête pointait vers `logout.png`, absent.
- `php.ini` (`C:\tools\php`) : `session.save_path` non défini → PHP utilisait
  `C:\WINDOWS`, non inscriptible, donc **aucune session ne fonctionnait**
  (connexion annoncée mais perdue à la requête suivante). Réglé sur
  `C:/laragon/tmp`, et `date.timezone` fixé à `Europe/Paris` (le serveur était
  en UTC, ce qui décalait la « journée » du journal).

## Limites connues

- Pas de gestion d'admin sur le catalogue : pour rafraîchir Open Food Facts, il
  faut relancer l'import (les produits existants sont ignorés, pas mis à jour).
- Le catalogue est filtré sur la France : un produit vendu uniquement ailleurs
  ne sortira pas de la recherche.
- Pas de pagination sur les résultats (20 produits maximum par recherche).
- `log_weight` (poids) et les templates `poids`, `tdee`, `calendrier` restent
  non branchés.
