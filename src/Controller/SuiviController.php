<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Service\MealService;

/**
 * Suivi des calories consommées, calculées à partir des repas enregistrés.
 *
 * La page présente une série journalière (7, 14, 30 ou 90 jours), un
 * graphique en barres côté navigateur et le même contenu sous forme de
 * tableau, pour rester lisible sans graphique.
 */
class SuiviController extends AbstractController
{
    private const PERIODES = [7, 14, 30, 90];

    private MealService $mealService;

    public function __construct()
    {
        $this->mealService = new MealService();
    }

    public function handle(): mixed
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            header("Location:/connection");
            exit;
        }

        $periode = (int) ($_GET["periode"] ?? 7);

        if (!in_array($periode, self::PERIODES, true)) {
            $periode = 7;
        }

        $serie = $this->mealService->calorieSeries($userId, $periode);

        return $this->render("suivi-calories", "Suivi des calories", [
            "periode" => $periode,
            "periodes" => self::PERIODES,
            "serie" => $serie,
            "resume" => MealService::resume($serie),
        ]);
    }
}
