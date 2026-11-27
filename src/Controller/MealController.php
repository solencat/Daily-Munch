<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Service\MealService;

/**
 * Suivi des repas : saisie d'un repas (recherche de produits Open Food Facts
 * + quantités) et lecture des repas déjà enregistrés pour une journée.
 */
class MealController extends AbstractController
{
    private MealService $mealService;

    public function __construct()
    {
        $this->mealService = new MealService();
    }

    /**
     * Point d'entrée de la route /repas : affiche la journée (GET) ou
     * enregistre un repas (POST).
     */
    public function handle(): mixed
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            header("Location:/connection");
            exit;
        }

        if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
            return $this->store($userId);
        }

        return $this->index($userId, $_SESSION["user"] ?? []);
    }

    /**
     * Recherche de produits pour la barre de recherche (réponse JSON).
     */
    public function search(): mixed
    {
        $query = trim((string) ($_GET["q"] ?? ""));

        if (mb_strlen($query) < 2) {
            return $this->jsonResponse(["query" => $query, "count" => 0, "products" => []]);
        }

        $produits = $this->mealService->searchProducts($query, 20);

        $payload = array_map(
            static fn(array $produit): array => [
                "code" => (string) $produit["code"],
                "name" => (string) $produit["name"],
                "brands" => $produit["brands"] !== null ? (string) $produit["brands"] : null,
                "quantity" => $produit["quantity_label"] !== null ? (string) $produit["quantity_label"] : null,
                "serving_size" => $produit["serving_size_label"] !== null ? (string) $produit["serving_size_label"] : null,
                "serving_g" => $produit["serving_quantity_g"] !== null ? (float) $produit["serving_quantity_g"] : null,
                "kcal_100g" => $produit["kcal_100g"] !== null ? (float) $produit["kcal_100g"] : null,
                "image" => $produit["image_url"] !== null ? (string) $produit["image_url"] : null,
                "nutriscore" => $produit["nutriscore_grade"] !== null ? (string) $produit["nutriscore_grade"] : null,
            ],
            $produits
        );

        return $this->jsonResponse([
            "query" => $query,
            "count" => count($payload),
            "products" => $payload,
        ]);
    }

    /**
     * Suppression d'un repas (POST, retour sur la journée).
     */
    public function destroy(): mixed
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            header("Location:/connection");
            exit;
        }

        $mealId = (int) ($_POST["id_log_meal"] ?? 0);
        $date = $this->resolveDate($_POST["date"] ?? null);

        $supprime = $mealId > 0 && $this->mealService->delete($mealId, $userId);

        $_SESSION["repas_flash"] = $supprime
            ? ["type" => "success", "messages" => ["Repas supprimé."]]
            : ["type" => "error", "messages" => ["Ce repas est introuvable."]];

        header("Location:/repas?date=" . urlencode($date));
        exit;
    }

    private function index(int $userId, array $sessionUser): mixed
    {
        $date = $this->resolveDate($_GET["date"] ?? null);
        $meals = $this->mealService->dayMeals($userId, $date);

        $flash = $_SESSION["repas_flash"] ?? null;
        unset($_SESSION["repas_flash"]);

        return $this->render("repas", "Mes repas", [
            "date" => $date,
            "date_label" => $this->formatDate($date),
            "meals" => $meals,
            "totalKcal" => MealService::totalKcal($meals),
            "suggestions" => MealService::NOMS_SUGGERES,
            "flash" => $flash,
            "today" => date("Y-m-d"),
        ]);
    }

    private function store(int $userId): mixed
    {
        $result = $this->mealService->save($_POST, $userId);

        $_SESSION["repas_flash"] = $result["ok"]
            ? ["type" => "success", "messages" => ["Le repas a été enregistré."]]
            : ["type" => "error", "messages" => $result["errors"]];

        header("Location:/repas?date=" . urlencode($result["date"]));
        exit;
    }

    private function resolveDate(mixed $value): string
    {
        $date = is_string($value) ? trim($value) : "";
        $parsed = \DateTimeImmutable::createFromFormat("!Y-m-d", $date);

        if ($parsed === false || $parsed->format("Y-m-d") !== $date) {
            return date("Y-m-d");
        }

        return $date;
    }

    private function formatDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat("Y-m-d", $date);
        $jours = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
        $mois = ["", "janvier", "février", "mars", "avril", "mai", "juin", "juillet",
            "août", "septembre", "octobre", "novembre", "décembre"];

        if ($parsed === false) {
            return $date;
        }

        return $jours[(int) $parsed->format("w")]
            . " " . (int) $parsed->format("j")
            . " " . $mois[(int) $parsed->format("n")];
    }
}
