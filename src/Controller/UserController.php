<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Service\UserService;

class UserController extends AbstractController
{
    //Attribut
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function register(): mixed
    {
        //redirection vers accueil si connecté
        if (isset($_SESSION["user"])) {
            header('Location:/');
        }
        //Tester si le formulaire est soumis
        if (isset($_POST["submit"])) {
            $data["msg"] = $this->userService->insertUser();
        }
        //Retourne la vue 
        return $this->render('register', 'Inscription', $data ?? []);
    }

    public function login():mixed 
    {
        //redirection vers accueil si connecté
        if (isset($_SESSION["user"])) {
            header('Location:/');
        }

        //Test si le formulaire est soumis
        if (isset($_POST["submit"])) {
            //Traitement de la connexion
            $data = $this->userService->connectUser();
        }
        //Retourne la vue
        return $this->render('login', 'Connexion', $data ?? []);
    }

    public function logout(): void 
    {
        $this->userService->disconnect();
    }
}
