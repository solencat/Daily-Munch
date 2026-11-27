<?php

namespace App\Service;

use App\Entity\Meal;
use App\Repository\MealRepository;
use App\Repository\ProductRepository;

/**
 * Validation et enregistrement d'un repas.
 *
 * Le client envoie uniquement des codes produit et des quantités : les
 * calories sont toujours recalculées ici à partir du catalogue, jamais
 * reprises du navigateur.
 */
class MealService
{
    public const NOMS_SUGGERES = ["Petit-déjeuner", "Déjeuner", "Dîner", "Collation"];

    private const MAX_PRODUITS = 50;
    private const QUANTITE_MIN = 0.1;
    private const QUANTITE_MAX = 5000.0;

    private MealRepository $mealRepository;
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->mealRepository = new MealRepository();
        $this->productRepository = new ProductRepository();
    }

    /**
     * Valide puis enregistre un repas soumis par le formulaire.
     *
     * @param array<string, mixed> $post
     * @return array{ok: bool, errors: string[], date: string, mealId: ?int}
     */
    public function save(array $post, int $userId): array
    {
        $errors = [];
        $date = $this->validateDate($post["date_meal"] ?? null, $errors);
        $nom = $this->validateName($post["meal_name"] ?? null, $errors);
        $items = $this->validateItems($post["items"] ?? null, $errors);

        if ($errors !== []) {
            return ["ok" => false, "errors" => $errors, "date" => $date ?? date("Y-m-d"), "mealId" => null];
        }

        try {
            $mealId = $this->mealRepository->create($userId, $date, $nom, $items);
        } catch (\Throwable $exception) {
            // Le détail part dans le journal du serveur, pas dans la page :
            // un message technique n'aide pas l'utilisateur.
            error_log("MealService::save - " . $exception->getMessage());
            return [
                "ok" => false,
                "errors" => ["L'enregistrement a échoué, réessayez dans un instant."],
                "date" => $date,
                "mealId" => null,
            ];
        }

        return ["ok" => true, "errors" => [], "date" => $date, "mealId" => $mealId];
    }

    /**
     * @return Meal[]
     */
    public function dayMeals(int $userId, string $date): array
    {
        return $this->mealRepository->findByUserAndDate($userId, $date);
    }

    public static function totalKcal(array $meals): float
    {
        $total = 0.0;

        foreach ($meals as $meal) {
            $total += $meal->getTotalKcal();
        }

        return round($total, 2);
    }

    public function delete(int $mealId, int $userId): bool
    {
        return $this->mealRepository->delete($mealId, $userId);
    }

    /**
     * Série journalière de calories pour la page de suivi.
     *
     * Les jours sans repas sont renvoyés à zéro : le graphique garde ainsi
     * une échelle régulière, et le tableau montre les jours non renseignés.
     *
     * @return array<int, array{date: string, etiquette: string, etiquette_longue: string, kcal: float, repas: int, aujourdhui: bool}>
     */
    public function calorieSeries(int $userId, int $jours): array
    {
        $jours = max(1, min($jours, 365));

        $fin = new \DateTimeImmutable("today");
        $debut = $fin->modify("-" . ($jours - 1) . " days");

        $totaux = $this->mealRepository->dailyTotals(
            $userId,
            $debut->format("Y-m-d"),
            $fin->format("Y-m-d")
        );

        $joursCourts = ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."];
        $mois = ["", "janvier", "février", "mars", "avril", "mai", "juin", "juillet",
            "août", "septembre", "octobre", "novembre", "décembre"];

        $serie = [];
        $aujourdhui = $fin->format("Y-m-d");

        for ($jour = $debut; $jour <= $fin; $jour = $jour->modify("+1 day")) {
            $cle = $jour->format("Y-m-d");
            $totauxJour = $totaux[$cle] ?? ["kcal" => 0.0, "repas" => 0];

            $serie[] = [
                "date" => $cle,
                "etiquette" => $jour->format("d/m"),
                "etiquette_longue" => $joursCourts[(int) $jour->format("w")] . " "
                    . (int) $jour->format("j") . " " . $mois[(int) $jour->format("n")],
                "kcal" => round($totauxJour["kcal"], 2),
                "repas" => $totauxJour["repas"],
                "aujourdhui" => $cle === $aujourdhui,
            ];
        }

        return $serie;
    }

    /**
     * Chiffres de synthèse affichés au-dessus du graphique.
     *
     * @param array<int, array<string, mixed>> $serie
     * @return array{aujourdhui: float, total: float, jours_suivis: int, jours: int, moyenne_jour_suivi: float, moyenne_periode: float, maximum: float, maximum_date: ?string}
     */
    public static function resume(array $serie): array
    {
        $total = 0.0;
        $joursSuivis = 0;
        $maximum = 0.0;
        $maximumDate = null;
        $aujourdhui = 0.0;

        foreach ($serie as $ligne) {
            $kcal = (float) $ligne["kcal"];
            $total += $kcal;

            if ($kcal > 0) {
                $joursSuivis++;
            }

            if ($kcal > $maximum) {
                $maximum = $kcal;
                $maximumDate = (string) $ligne["etiquette_longue"];
            }

            if (!empty($ligne["aujourdhui"])) {
                $aujourdhui = $kcal;
            }
        }

        return [
            "aujourdhui" => round($aujourdhui, 2),
            "total" => round($total, 2),
            "jours_suivis" => $joursSuivis,
            "jours" => count($serie),
            "moyenne_jour_suivi" => $joursSuivis > 0 ? round($total / $joursSuivis, 2) : 0.0,
            "moyenne_periode" => count($serie) > 0 ? round($total / count($serie), 2) : 0.0,
            "maximum" => round($maximum, 2),
            "maximum_date" => $maximumDate,
        ];
    }

    /**
     * Recherche de produits pour la barre de recherche.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(string $query, int $limit = 20): array
    {
        return $this->productRepository->search($query, $limit);
    }

    private function validateDate(mixed $value, array &$errors): string
    {
        $date = is_string($value) ? trim($value) : "";
        // "!Y-m-d" : sans le "!", createFromFormat complète avec l'heure
        // courante, et la date du jour devient "dans le futur".
        $parsed = \DateTimeImmutable::createFromFormat("!Y-m-d", $date);

        if ($parsed === false || $parsed->format("Y-m-d") !== $date) {
            $errors[] = "La date du repas est invalide.";
            return date("Y-m-d");
        }

        $aujourdhui = new \DateTimeImmutable("today");

        if ($parsed > $aujourdhui) {
            $errors[] = "La date du repas ne peut pas être dans le futur.";
        }

        return $date;
    }

    private function validateName(mixed $value, array &$errors): string
    {
        $nom = is_string($value) ? trim($value) : "";

        if ($nom === "") {
            $errors[] = "Indiquez le nom du repas (par exemple : Déjeuner).";
        } elseif (mb_strlen($nom) > 50) {
            $errors[] = "Le nom du repas est trop long (50 caractères maximum).";
        }

        return $nom;
    }

    /**
     * @param array<int, array{code: ?string, name: string, quantity_g: float, kcal_100g: float}>|null $items
     */
    private function validateItems(mixed $rawItems, array &$errors): array
    {
        if (!is_string($rawItems) || trim($rawItems) === "") {
            $errors[] = "Ajoutez au moins un produit au repas.";
            return [];
        }

        $decoded = json_decode($rawItems, true);

        if (!is_array($decoded) || $decoded === []) {
            $errors[] = "Ajoutez au moins un produit au repas.";
            return [];
        }

        if (count($decoded) > self::MAX_PRODUITS) {
            $errors[] = "Un repas ne peut pas contenir plus de " . self::MAX_PRODUITS . " produits.";
            return [];
        }

        $items = [];

        foreach ($decoded as $ligne) {
            if (!is_array($ligne) || !isset($ligne["code"])) {
                $errors[] = "Une des lignes du repas est illisible.";
                continue;
            }

            $code = trim((string) $ligne["code"]);
            $quantite = isset($ligne["quantity_g"]) ? (float) $ligne["quantity_g"] : 0.0;

            if ($code === "") {
                $errors[] = "Une des lignes du repas n'a pas de produit.";
                continue;
            }

            if ($quantite < self::QUANTITE_MIN || $quantite > self::QUANTITE_MAX) {
                $errors[] = "La quantité doit être comprise entre 0,1 g et 5 000 g.";
                continue;
            }

            $produit = $this->productRepository->findByCode($code);

            if ($produit === null || $produit["kcal_100g"] === null) {
                $errors[] = "Le produit « " . $code . " » n'existe pas dans le catalogue local.";
                continue;
            }

            $items[] = [
                "code" => $code,
                "name" => (string) $produit["name"],
                "quantity_g" => round($quantite, 2),
                "kcal_100g" => (float) $produit["kcal_100g"],
            ];
        }

        if ($items === [] && $errors === []) {
            $errors[] = "Ajoutez au moins un produit au repas.";
        }

        return $items;
    }
}
