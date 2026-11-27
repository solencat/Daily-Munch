<?php

namespace App\Repository;

use App\Database\Mysql;

/**
 * Accès au catalogue local Open Food Facts (table `product`).
 *
 * La table contient plusieurs centaines de milliers de lignes : la recherche
 * passe par l'index FULLTEXT en mode booléen (préfixe), et retombe sur un
 * LIKE pour les recherches trop courtes, qu'InnoDB ne sait pas indexer
 * (taille minimale d'un token : 3 caractères).
 */
class ProductRepository
{
    private const COLONNES = "code, name, brands, quantity_label, serving_size_label,
            serving_quantity_g, kcal_100g, protein_100g, carbs_100g, fat_100g,
            sugars_100g, salt_100g, image_url, nutriscore_grade";

    private const LIMITE_MAX = 50;

    private \PDO $connexion;

    public function __construct()
    {
        $this->connexion = Mysql::connectBdd();
    }

    /**
     * Recherche des produits dont le nom contient les mots demandés.
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 20): array
    {
        $normalized = self::normalize($query);

        if ($normalized === "") {
            return [];
        }

        $limit = max(1, min($limit, self::LIMITE_MAX));

        if (mb_strlen($normalized) < 3) {
            $sql = "SELECT " . self::COLONNES . "
                    FROM product
                    WHERE name_normalized LIKE ?
                    ORDER BY CHAR_LENGTH(name), name
                    LIMIT " . $limit;

            $statement = $this->connexion->prepare($sql);
            $statement->execute([$normalized . "%"]);

            return $statement->fetchAll();
        }

        $expression = self::toBooleanExpression($normalized);

        $sql = "SELECT " . self::COLONNES . ",
                       MATCH(name_normalized) AGAINST (? IN BOOLEAN MODE) AS score
                FROM product
                WHERE MATCH(name_normalized) AGAINST (? IN BOOLEAN MODE)
                ORDER BY score DESC, CHAR_LENGTH(name), name
                LIMIT " . $limit;

        $statement = $this->connexion->prepare($sql);
        $statement->bindValue(1, $expression, \PDO::PARAM_STR);
        $statement->bindValue(2, $expression, \PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCode(string $code): ?array
    {
        $statement = $this->connexion->prepare(
            "SELECT " . self::COLONNES . " FROM product WHERE code = ?"
        );
        $statement->execute([$code]);

        $produit = $statement->fetch();

        return $produit === false ? null : $produit;
    }

    public function count(): int
    {
        return (int) $this->connexion->query("SELECT COUNT(*) FROM product")->fetchColumn();
    }

    /**
     * Minuscules, sans accents, ponctuation remplacée par des espaces.
     * Doit produire la même forme que le script d'import (name_normalized).
     */
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), "UTF-8");

        $value = strtr($value, [
            "à" => "a", "â" => "a", "ä" => "a", "á" => "a", "ã" => "a", "å" => "a",
            "ç" => "c",
            "è" => "e", "é" => "e", "ê" => "e", "ë" => "e",
            "ì" => "i", "í" => "i", "î" => "i", "ï" => "i",
            "ñ" => "n",
            "ò" => "o", "ó" => "o", "ô" => "o", "ö" => "o", "õ" => "o", "ø" => "o",
            "ù" => "u", "ú" => "u", "û" => "u", "ü" => "u",
            "ý" => "y", "ÿ" => "y",
            "æ" => "ae", "œ" => "oe", "ß" => "ss",
        ]);

        $value = preg_replace("/[^a-z0-9]+/", " ", $value) ?? "";
        $value = preg_replace("/\s+/", " ", $value) ?? "";

        return trim($value);
    }

    /**
     * Transforme "pate tartiner" en "pate* tartiner*" : chaque mot doit
     * commencer par le texte saisi, dans n'importe quel ordre de pertinence.
     */
    private static function toBooleanExpression(string $normalized): string
    {
        $mots = explode(" ", $normalized);
        $mots = array_slice($mots, 0, 8);

        $mots = array_map(
            static fn(string $mot): string => preg_replace("/[+\-<>()~*\"@]/", "", $mot) . "*",
            $mots
        );

        return implode(" ", $mots);
    }
}
