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
            $sql = "INSERT INTO `user`(firstname, lastname, email, `password`, img_profil, `grant`) VALUE(?,?,?,?,?,?)";
            $stmt = $this->connexion->prepare($sql);
            $stmt->bindValue(1, $user->getFirstname(), \PDO::PARAM_STR);
            $stmt->bindValue(2, $user->getLastname(), \PDO::PARAM_STR);
            $stmt->bindValue(3, $user->getEmail(), \PDO::PARAM_STR);
            $stmt->bindValue(4, $user->getPassword(), \PDO::PARAM_STR);
            $stmt->bindValue(5, $user->getImgProfil(), \PDO::PARAM_STR);
            $stmt->bindValue(6, $user->getGrant(), \PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->connexion->lastInsertId();
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
            $sql = "SELECT u.id_user, u.firstname, u.lastname, u.email, u.`password`, 
            u.created_at, u.updated_at, u.img_profil, u.grant FROM `user` As u 
            WHERE u.email = ?";
            $stmt = $this->connexion->prepare($sql);
            $stmt->bindValue(1, $email, \PDO::PARAM_STR);
            $stmt->execute();
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
