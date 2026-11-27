<?php

namespace App\Repository;

use App\Database\Mysql;
use App\Entity\Meal;
use App\Entity\MealItem;

/**
 * Enregistrement et lecture des repas (tables `log_meal` et `log_meal_product`).
 */
class MealRepository
{
    private \PDO $connexion;

    public function __construct()
    {
        $this->connexion = Mysql::connectBdd();
    }

    /**
     * Enregistre un repas et ses produits dans une seule transaction :
     * un repas à moitié écrit n'a aucun sens.
     *
     * @param array<int, array{code: ?string, name: string, quantity_g: float, kcal_100g: float}> $items
     */
    public function create(int $userId, string $date, string $name, array $items): int
    {
        $this->connexion->beginTransaction();

        try {
            $statement = $this->connexion->prepare(
                "INSERT INTO log_meal(date_meal, name, id_user_identification) VALUES (?, ?, ?)"
            );
            $statement->execute([$date, $name, $userId]);

            $mealId = (int) $this->connexion->lastInsertId();

            $insert = $this->connexion->prepare(
                "INSERT INTO log_meal_product(id_log_meal, product_code, product_name, quantity_g, kcal_100g)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($items as $item) {
                $insert->execute([
                    $mealId,
                    $item["code"],
                    $item["name"],
                    $item["quantity_g"],
                    $item["kcal_100g"],
                ]);
            }

            $this->connexion->commit();

            return $mealId;
        } catch (\Throwable $exception) {
            $this->connexion->rollBack();
            throw $exception;
        }
    }

    /**
     * Tous les repas d'un utilisateur pour une date donnée.
     *
     * @return Meal[]
     */
    public function findByUserAndDate(int $userId, string $date): array
    {
        $sql = "SELECT m.id_log_meal, m.date_meal, m.name AS meal_name,
                       p.product_code, p.product_name, p.quantity_g, p.kcal_100g, p.kcal
                FROM log_meal AS m
                LEFT JOIN log_meal_product AS p ON p.id_log_meal = m.id_log_meal
                WHERE m.id_user_identification = ? AND m.date_meal = ?
                ORDER BY m.id_log_meal, p.id_log_meal_product";

        $statement = $this->connexion->prepare($sql);
        $statement->execute([$userId, $date]);

        /** @var array<int, Meal> $repas */
        $repas = [];

        foreach ($statement->fetchAll() as $ligne) {
            $mealId = (int) $ligne["id_log_meal"];

            if (!isset($repas[$mealId])) {
                $repas[$mealId] = new Meal($mealId, (string) $ligne["date_meal"], (string) $ligne["meal_name"]);
            }

            if ($ligne["product_name"] !== null) {
                $repas[$mealId]->addItem(MealItem::hydrate($ligne));
            }
        }

        return array_values($repas);
    }

    /**
     * Supprime un repas, à condition qu'il appartienne à l'utilisateur.
     */
    public function delete(int $mealId, int $userId): bool
    {
        $statement = $this->connexion->prepare(
            "DELETE FROM log_meal WHERE id_log_meal = ? AND id_user_identification = ?"
        );
        $statement->execute([$mealId, $userId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Calories et nombre de repas par jour, sur une plage de dates.
     *
     * Les jours sans repas sont absents du résultat : c'est au service de
     * compléter la série pour que le graphique reste régulier.
     *
     * @return array<string, array{kcal: float, repas: int}> clé = AAAA-MM-JJ
     */
    public function dailyTotals(int $userId, string $from, string $to): array
    {
        $sql = "SELECT m.date_meal AS jour,
                       COUNT(DISTINCT m.id_log_meal) AS repas,
                       COALESCE(SUM(p.kcal), 0) AS kcal
                FROM log_meal AS m
                LEFT JOIN log_meal_product AS p ON p.id_log_meal = m.id_log_meal
                WHERE m.id_user_identification = ? AND m.date_meal BETWEEN ? AND ?
                GROUP BY m.date_meal
                ORDER BY m.date_meal";

        $statement = $this->connexion->prepare($sql);
        $statement->execute([$userId, $from, $to]);

        $totaux = [];

        foreach ($statement->fetchAll() as $ligne) {
            $totaux[(string) $ligne["jour"]] = [
                "kcal" => (float) $ligne["kcal"],
                "repas" => (int) $ligne["repas"],
            ];
        }

        return $totaux;
    }
}
