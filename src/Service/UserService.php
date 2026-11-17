<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Tools;

class UserService
{
    //Attribut
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function insertUser(): string
    {
        //Tester si les champs obligatoire sont vides
        if (
            empty($_POST["firstname"]) ||
            empty($_POST["lastname"]) ||
            empty($_POST["email"]) ||
            empty($_POST["password"]) ||
            empty($_POST["confirm-password"]) ||
            empty($_POST["size"]) ||
            empty($_POST["weight"])
        ) {
            return "Veuillez remplir tous les champs du formulaire";
        }

        //Nettoyer les données
        Tools::sanitizeArray($_POST);

        //Tests Si email est non valide
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            return "L'email : " . $_POST["email"] . " est invalide";
        }

        //Tests si les 2 mot de sont différents
        if ($_POST["password"] != $_POST["confirm-password"]) {
            return "Les mots de passe ne sont pas identiques";
        }

        //Test si le mot de passe est plus petit que 8 caractères
        if (strlen($_POST["password"]) < 8) {
            return "Le mot de passe est trop court il faut 8 caractères au minimum";
        }

        //récupérer le compte
        $user = $this->userRepository->findByEmail($_POST["email"]);

        //Tester si le compte existe
        if ($user) {
            return "Le compte existe déja";
        }

        //Hasher le mot de passe
        $hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

        //hydrater l'objet User
        $newUser = User::hydrate($_POST);

        //Setter le mot de passe hashé
        $newUser->setPassword($hash);
              
        //Ajouter en BDD le compte
        $this->userRepository->create($newUser);

        return "Le compte " . $_POST["email"] . " a bien été enregistré!";
    }

    public function connectUser(): array
    {
        $errors = [];
        Tools::sanitizeArray($_POST);
        
        //Récupération du compte
        $user = $this->userRepository->findByEmail($_POST["email"]->email);

        //Test si le compte existe
        if (!$user) {
            $errors["errors"] = "Les informations de connexion ne sont pas valides";
            return $errors;
        }

        //Test si le mot de passe est valide
        if (!password_verify($_POST["password"]->password, $user->getPassword())) {
            $errors["errors"] = "Les informations de connexion ne sont pas valides";
            return $errors;
        }

        //Renouvellement de l'id de session
        session_regenerate_id(true);

        //Création de la super globale de session
        $_SESSION["user"] = [
            "id" => $user->getId(),
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "email" => $user->getEmail(),
            "grant" => $user->getGrant() ?? ""
        ];

        $errors["success"] = "Connecté";
        header("Refresh:2; url=/");
        return $errors;
    }

    /**
     * Méthode pour se déconnecter
     */
    public function disconnect(): void
    {
        //Supprimer la session
        session_destroy();
        //supprime le cookie dans le navigateur
        unset($_COOKIE["PHPSESSID"]);
        //Redirection vers home
        header('Location:/');
    }

    
}
