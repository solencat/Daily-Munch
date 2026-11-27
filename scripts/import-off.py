#!/usr/bin/env python3
"""Import local d'Open Food Facts vers la table `product` de Daily Munch.

Le fichier source est l'export TSV officiel, compressé :

    https://static.openfoodfacts.org/data/en.openfoodfacts.org.products.csv.gz

Le script lit le fichier en flux (jamais chargé en mémoire), garde les
produits exploitables pour un suivi calorique, et écrit des INSERT sur la
sortie standard. On le branche donc sur le client mysql :

    python scripts/import-off.py --source /chemin/products.csv.gz \
        | mysql -u root dailymunch

Options utiles :
    --countries france,belgium    pays retenus (défaut : france)
    --limit 5000                  s'arrêter après N produits (test rapide)
    --batch 2000                  lignes par INSERT

Filtres appliqués (un produit est gardé si) :
  * `product_name` non vide ;
  * des calories pour 100 g sont déductibles (`energy-kcal_100g`, sinon
    `energy_100g` en kJ divisé par 4,184) ;
  * le produit est vendu dans un des pays demandés (`countries_tags`).

Les valeurs nutritionnelles restent celles pour 100 g : le calcul de la
portion se fait à l'enregistrement du repas, côté PHP.
"""

from __future__ import annotations

import argparse
import csv
import gzip
import re
import sys
import unicodedata

# Colonnes de l'export OFF réellement utilisées.
COL = {
    "code": "code",
    "name": "product_name",
    "brands": "brands",
    "quantity": "quantity",
    "serving_size": "serving_size",
    "serving_quantity": "serving_quantity",
    "kcal": "energy-kcal_100g",
    "energy_kj": "energy_100g",
    "protein": "proteins_100g",
    "carbs": "carbohydrates_100g",
    "fat": "fat_100g",
    "sugars": "sugars_100g",
    "salt": "salt_100g",
    "fiber": "fiber_100g",
    "image": "image_small_url",
    "image_full": "image_url",
    "nutriscore": "nutriscore_grade",
    "categories": "categories",
    "countries": "countries_tags",
}

COLUMNS = (
    "code, name, name_normalized, brands, quantity_label, serving_size_label, "
    "serving_quantity_g, kcal_100g, protein_100g, carbs_100g, fat_100g, "
    "sugars_100g, salt_100g, fiber_100g, image_url, nutriscore_grade, categories"
)

ACCENTS = str.maketrans(
    {
        "ø": "o",
        "æ": "ae",
        "œ": "oe",
        "ß": "ss",
        "ł": "l",
        "đ": "d",
    }
)
NUM_RE = re.compile(r"(\d+(?:[.,]\d+)?)\s*(kg|g|ml|cl|l|oz)?", re.I)


def normalize(text: str) -> str:
    """Minuscules, sans accents, ponctuation remplacée par des espaces."""
    text = unicodedata.normalize("NFKD", text)
    text = "".join(c for c in text if not unicodedata.combining(c))
    text = text.lower().translate(ACCENTS)
    text = re.sub(r"[^a-z0-9 ]+", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def to_float(value: str) -> float | None:
    if not value:
        return None
    value = value.strip().replace(",", ".")
    try:
        number = float(value)
    except ValueError:
        return None
    if number != number or number in (float("inf"), float("-inf")):
        return None
    return number


def serving_grams(serving_quantity: str, serving_size: str) -> float | None:
    """Poids d'une portion normale, en grammes, si l'info existe."""
    quantity = to_float(serving_quantity)
    if quantity and 0 < quantity <= 5000:
        return round(quantity, 2)

    match = NUM_RE.search(serving_size or "")
    if not match:
        return None

    amount = to_float(match.group(1))
    unit = (match.group(2) or "").lower()
    if not amount:
        return None
    if unit == "kg":
        amount *= 1000
    elif unit == "cl":
        amount *= 10
    elif unit == "l":
        amount *= 1000
    elif unit == "oz":
        amount *= 28.35
    # g et ml sont traités comme équivalents (densité 1), approximation assumée.
    if not (0 < amount <= 5000):
        return None
    return round(amount, 2)


def kcal_100g(kcal: str, energy_kj: str) -> float | None:
    value = to_float(kcal)
    if value and 0 < value <= 900:
        return round(value, 2)
    kj = to_float(energy_kj)
    if kj and 0 < kj <= 3800:
        return round(kj / 4.184, 2)
    return None


def sql_str(value: str | None) -> str:
    if value is None:
        return "NULL"
    value = value.replace("\\", "\\\\").replace("'", "\\'")
    value = value.replace("\n", "\\n").replace("\r", "\\r").replace("\0", "")
    return "'" + value + "'"


def sql_num(value: float | None) -> str:
    return "NULL" if value is None else f"{value:.2f}"


def clip(value: str, size: int) -> str | None:
    value = (value or "").strip()
    if not value:
        return None
    return value[:size]


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", required=True, help="products.csv.gz d'Open Food Facts")
    parser.add_argument("--countries", default="france", help="pays retenus, séparés par des virgules")
    parser.add_argument("--batch", type=int, default=2000, help="lignes par INSERT")
    parser.add_argument("--limit", type=int, default=0, help="nombre maximum de produits importés")
    args = parser.parse_args()

    countries = ["en:" + c.strip().lower().replace(" ", "-") for c in args.countries.split(",")]

    out = sys.stdout
    reader_stream = gzip.open(args.source, "rt", encoding="utf-8", errors="replace", newline="")
    reader = csv.reader(reader_stream, delimiter="\t")
    header = next(reader)
    index = {}
    for key, column in COL.items():
        if column not in header:
            print(f"colonne absente de l'export : {column}", file=sys.stderr)
            return 2
        index[key] = header.index(column)

    def cell(row: list[str], key: str) -> str:
        position = index[key]
        return row[position] if position < len(row) else ""

    seen = 0
    kept = 0
    batch: list[str] = []
    written = False
    checked = 0

    for row in reader:
        seen += 1
        if seen % 200000 == 0:
            print(f"  lues {seen:,} / gardées {kept:,}", file=sys.stderr)

        name = clip(cell(row, "name"), 255)
        if not name:
            continue
        code = clip(cell(row, "code"), 24)
        if not code:
            continue

        countries_value = cell(row, "countries").lower()
        if not any(country in countries_value for country in countries):
            continue

        kcal = kcal_100g(cell(row, "kcal"), cell(row, "energy_kj"))
        if kcal is None:
            continue

        if not written:
            out.write(
                "-- Import Open Food Facts : produit = ligne de products.csv.gz\n"
                "SET NAMES utf8mb4;\n"
                "SET autocommit=0;\n"
            )
            written = True

        image = clip(cell(row, "image"), 255) or clip(cell(row, "image_full"), 255)
        values = ", ".join(
            [
                sql_str(code),
                sql_str(name),
                sql_str(normalize(name)[:255]),
                sql_str(clip(cell(row, "brands"), 255)),
                sql_str(clip(cell(row, "quantity"), 100)),
                sql_str(clip(cell(row, "serving_size"), 100)),
                sql_num(serving_grams(cell(row, "serving_quantity"), cell(row, "serving_size"))),
                sql_num(kcal),
                sql_num(to_float(cell(row, "protein"))),
                sql_num(to_float(cell(row, "carbs"))),
                sql_num(to_float(cell(row, "fat"))),
                sql_num(to_float(cell(row, "sugars"))),
                sql_num(to_float(cell(row, "salt"))),
                sql_num(to_float(cell(row, "fiber"))),
                sql_str(image),
                sql_str((clip(cell(row, "nutriscore"), 1) or "").lower() or None),
                sql_str(clip(cell(row, "categories"), 255)),
            ]
        )
        batch.append(f"({values})")
        kept += 1

        if len(batch) >= args.batch:
            out.write(f"INSERT IGNORE INTO product ({COLUMNS}) VALUES\n")
            out.write(",\n".join(batch))
            out.write(";\n")
            batch.clear()
            checked += args.batch

        if args.limit and kept >= args.limit:
            break

    if batch:
        out.write(f"INSERT IGNORE INTO product ({COLUMNS}) VALUES\n")
        out.write(",\n".join(batch))
        out.write(";\n")

    out.write("COMMIT;\n")
    print(
        f"terminé : {seen:,} lignes lues, {kept:,} produits écrits "
        f"({len(countries)} pays demandés)",
        file=sys.stderr,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
