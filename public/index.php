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
use App\Controller\UserController;


// Instanciation des controllers
$homeController = new HomeController();
$userController = new UserController();


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
    default:
        echo "Erreur 404";
        break;
}
