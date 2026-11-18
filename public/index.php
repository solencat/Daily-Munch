<?php
include '../vendor/autoload.php';

session_start();

//récupération de l'URL (composante)
$url = parse_url($_SERVER["REQUEST_URI"]);

//extraction du path
$path = isset($url["path"]) ? $url["path"] : "/";

//importer les variables d'environnement 
$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

//importer
use App\Controller\HomeController;
use App\Controller\UserController;


//instancier les controllers
$homeController = new HomeController();
$userController = new UserController();


//test des valeurs du path
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
