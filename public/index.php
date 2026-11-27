<?php
include '../vendor/autoload.php';

session_start();

// Récupération de l'URL
$url = parse_url($_SERVER["REQUEST_URI"]);

// Extraction du path
$path = isset($url["path"]) ? $url["path"] : "/";

// Importation des variables d'environnement 
$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

// Importation des Controller
use App\Controller\HomeController;
use App\Controller\MealController;
use App\Controller\SuiviController;
use App\Controller\UserController;
use App\Controller\ToolController;


// Instanciation des controllers
$homeController = new HomeController();
$userController = new UserController();
$toolController = new ToolController();


// Routage depuis le path
switch ($path) {
    case '/':
        $homeController->home();
        break;
    case '/connection':
        $userController->connection();
        break;
    case '/register':
        $userController->register();
        break;
    case '/login':
        $userController->login();
        break;
    case '/logout':
        $userController->logout();
        break;
    case '/bmi':
        $toolController->bmi();
        break;
    case '/repas':
        // Instancié ici : la page d'accueil n'a pas besoin d'une connexion MySQL.
        (new MealController())->handle();
        break;
    case '/repas/supprimer':
        (new MealController())->destroy();
        break;
    case '/api/products':
        (new MealController())->search();
        break;
    case '/suivi':
        (new SuiviController())->handle();
        break;
    default:
        echo "Erreur 404";
        break;
}
