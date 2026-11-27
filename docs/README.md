# Branche `exemple` — mode d'emploi

Cette branche part de `development` (commit `da371c0`) et ajoute deux choses :

1. **Le suivi alimentaire complet** : une page pour composer un repas à partir
   d'Open Food Facts, et une page de suivi des calories par jour.
2. **Des documents** pour que le stage soit reproductible : ce que j'ai corrigé,
   pourquoi, et comment reconstruire les fonctionnalités soi-même.

Rien n'est fait « pour être repris tel quel ». Le but est que le stagiaire
réécrive les mêmes fonctionnalités avec son propre code, en comprenant le
découpage. Les documents sont écrits dans cet ordre.

## Lecture

| Fichier | Contenu |
|---|---|
| `docs/README.md` | ce fichier : contexte, lancement, ordre de lecture |
| `docs/01-BUGS-CORRIGES.md` | chaque problème réglé : symptôme, cause, correction, vérification |
| `docs/02-NOUVELLES-FEATURES.md` | les fonctionnalités, découpées, avec leur version la plus simple et le plan de travail |
| `README-SUIVI-REPAS.md` | référence technique : tables, import Open Food Facts, routes, fichiers |

Ordre conseillé : ce fichier → `01-BUGS-CORRIGES.md` → `02-NOUVELLES-FEATURES.md`
→ le code, dans l'ordre des commits.

## Lancer le projet

Prérequis utilisés ici : Apache + PHP 8.4 + MySQL 8.4 (Laragon), PHP 8.4 dans
`C:\tools\php`.

```bash
composer install                     # dépendances PHP
mysql -u root dailymunch < bdd.sql   # schéma + rôles + 3 nouvelles tables
python scripts/import-off.py --source products.csv.gz --limit 5000 \
    | mysql -u root dailymunch       # catalogue de test (5 000 produits)
```

`.env` à la racine (non versionné) :

```
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=dailymunch
DATABASE_USERNAME=root
DATABASE_PASSWORD=
```

Points d'entrée : `/` (accueil), `/connection`, `/repas`, `/suivi`,
`/api/products?q=...`, `/bmi`.

## Ce que contient la branche

```
public/index.php                     routeur (4 routes ajoutées)
public/css/{repas,suivi}.css         styles des deux pages
public/js/repas.js                   recherche + panier du repas
public/js/suivi.js                   graphique D3
public/js/vendor/d3.min.js           D3 v7.9.2 (copie locale, pas de CDN)
public/images/logout.svg             icône manquante dans l'en-tête
src/Controller/{Meal,Suivi}Controller.php
src/Service/MealService.php
src/Repository/{Product,Meal}Repository.php
src/Entity/{Meal,MealItem}.php
templates/template_{repas,suivi-calories}.php
scripts/import-off.py                import Open Food Facts
bdd.sql                              3 tables ajoutées + 2 corrections
docs/                                ces documents
```

## Règle de travail pour le stagiaire

Recopier ne sert à rien : le code de cette branche sera là au prochain rendez-vous
de toute façon. Ce qui est évalué, c'est de savoir **découper** et **vérifier**.
Chaque étape de `02-NOUVELLES-FEATURES.md` a un critère de réussite vérifiable
en base ou dans le navigateur. Une étape n'est finie que quand ce critère est
atteint, pas quand le code « a l'air bon ».
