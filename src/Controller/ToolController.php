<?php

namespace App\Controller;

use App\Controller\AbstractController;

class ToolController extends AbstractController
{
    //Méthode qui affiche la page d'accueil
    public function tools(): mixed
    {
        return $this->render('outils', 'Outils');
    }

    //Méthode qui affiche la page d'accueil
    public function bmi(): mixed
    {
        return $this->render('imc', "Calculer l'Indicateur de Masse Corporelle");
    }
}
