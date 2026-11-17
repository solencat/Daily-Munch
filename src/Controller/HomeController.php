<?php

namespace App\Controller;

use App\Controller\AbstractController;

class HomeController extends AbstractController
{
    //Méthode qui affiche la page d'accueil
    public function home(): mixed
    {
        return $this->render('accueil', 'Daily Munch - Food Logging');
    }
}
