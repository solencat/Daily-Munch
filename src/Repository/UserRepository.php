<?php

namespace App\Repository;

use App\Entity\User;
use App\Database\Mysql;

class UserRepository
{
    //Attribut
    private \PDO $connexion;

    public function __construct()
    {
        $this->connexion = Mysql::connectBdd();
    }

    /**
     * Méthode pour ajouter un user en BDD
     * @param User $user Objet User à ajouter en BDD
     * @return User $user Objet User
     */
    public function create(User $user): User
    {
        try {
            // Requête
            $sql = "INSERT INTO user_identification(firstname, lastname, height, weight, email, pass, id_role_access) VALUE(?,?,?,?,?,?,?)";

            // Préparation de la requête
            $stmt = $this->connexion->prepare($sql);

            // Bind des valeurs à la requête
            $stmt->bindValue(1, $user->getFirstname(), \PDO::PARAM_STR);
            $stmt->bindValue(2, $user->getLastname(), \PDO::PARAM_STR);
            $stmt->bindValue(3, $user->getHeight(), \PDO::PARAM_INT);
            $stmt->bindValue(4, $user->getWeight(), \PDO::PARAM_INT);
            $stmt->bindValue(5, $user->getEmail(), \PDO::PARAM_STR);
            $stmt->bindValue(6, $user->getPassword(), \PDO::PARAM_STR);
            $stmt->bindValue(7, $user->getRole(), \PDO::PARAM_STR);

            // Exécution de la requete
            $stmt->execute();

            // Récupération de l'id
            $id = $this->connexion->lastInsertId();

            // Set de l'id
            $user->setId($id);

        } catch(\PDOException $e) {
            echo "Erreur " . $e->getMessage();
        }
        return $user;
    }

    /**
     * Méthode qui récupére un User depuis la BDD avec son email
     * @param string $email email à rechercher
     * @return ?User $user Retourne l'objet User si existe sinon null
     */
    public function findByEmail(string $email): ?User
    {
        try {
            // Requête
            $sql = "SELECT u.id_user_identification, u.firstname, u.lastname, u.height, u.weight, u.email, u.pass, 
            u.id_role_access FROM user_identification As u 
            WHERE u.email = ?";
            
            // Préparation de la requête
            $stmt = $this->connexion->prepare($sql);

            // Bind des valeurs à la requête
            $stmt->bindValue(1, $email, \PDO::PARAM_STR);

            // Exécution de la requete
            $stmt->execute();

            // Récupération des informations dans la base de données sous le format tableau associatif
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if($data) {
                $user = User::hydrate($data);
            }
        } catch(\PDOException $e) {
            echo "Erreur " . $e->getMessage();
        }
        return $user ?? null;
    }
}
