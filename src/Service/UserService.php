<?php

namespace App\Service;

require_once 'vendor/autoload.php';

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Tools;
use App\Utils\UserFormValidator;

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
            empty($_POST["email"]) ||
            empty($_POST["pass"]) ||
            empty($_POST["confirm-pass"]) ||            
            empty($_POST["firstname"]) ||
            empty($_POST["lastname"]) ||
            empty($_POST["height"]) ||
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
        
        // Validation
        $userValidator = new UserFormValidator();
        $result = $userValidator->validate($_POST);
        if(!$result["valid"]){
            return $result["errors"];
        }

        //récupérer le compte
        $user = $this->userRepository->findByEmail($_POST["email"]);

        //Tester si le compte existe
        if ($user) {
            return "Le compte existe déja";
        }

        //Hasher le mot de passe
        $hash = password_hash($_POST["pass"], PASSWORD_DEFAULT);

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
        
        $data = [];
        Tools::sanitizeArray($_POST);
        // dd($_POST["email"]);
        //Récupération du compte
        $user = $this->userRepository->findByEmail($_POST["email"]);
        
        //Test si le compte existe
        if (!$user) {
            $data["msg"] = "Les informations de connexion ne sont pas valides";
            return $data;
        }

        //Test si le mot de passe est valide
        if (!password_verify($_POST["pass"], $user->getPassword())) {
            $data["msg"] = "Les informations de connexion ne sont pas valides";
            return $data;
        }

        //Renouvellement de l'id de session
        session_regenerate_id(true);

        //Création de la super globale de session
        $_SESSION["user"] = [
            "id" => $user->getId(),
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "email" => $user->getEmail(),
            "grant" => $user->getRole() ?? ""
        ];

        $data["msg"] = "Connecté";
        header("Refresh:2; url=/");
        return $data;
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
        echo "Déconnexion en cours (2s)";
        header('Refresh:2; url=/');
    }

    
}
