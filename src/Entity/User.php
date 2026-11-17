<?php

namespace App\Entity;


class User
{
    //Attributs
    private ?int $id;
    private string $firstname;
    private string $lastname;
    private string $email;
    private string $password;
    private \DateTimeImmutable $createdAt;
    private string $role;

    //Constructeur
    public function __construct(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
    ) {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = new \DateTimeImmutable();
    }

    //Getters et Setters
    //Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }


    public function setFirstname(string  $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }


    public function setLastname(string  $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }


    public function setEmail(string  $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }


    public function setPassword(string  $password): void
    {
        $this->password = $password;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string  $role): void
    {
        $this->role = $role;
    }

    /**
     * Méthode statique qui transforme un tableau associatif en objet User
     * @param array $user tableau associatif (BDD requête)
     * @return User $newUser objet User;
     */
    public static function hydrate(array $user): User
    {
        //Création d'un objet User
        $newUser = new User(
            $user["firstname"],
            $user["lastname"],
            $user["email"],
            $user["password"]
        );
        
        //Set des attributs
        $newUser->setId($user["id_user"] ?? null);
        $newUser->setCreatedAt(new \DateTimeImmutable($user["created_at"] ?? "") ?? null);
        $newUser->setRole($user["grant"] ?? "ROLE_USER");
        
        //Retourne l'objet User
        return $newUser;
    }
}
