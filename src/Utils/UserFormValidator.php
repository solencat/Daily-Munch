<?php

namespace App\Utils;

use Lemmon\Validator\Validator;

class UserFormValidator
{
    private $validator;

    public function __construct(){
        $this->validator = Validator::isAssociative([
            "email" => Validator::isString()
                ->pipe("trim")
                ->required("L'email doit être rempli")
                ->email("Format d'email invalide"),
                
            "pass" => Validator::isString()
                ->pipe("trim")
                ->required("Le passe doit être rempli")
                ->minLength(16, "Il faut au minimum 16 caractères"),
            
            "confirm-pass" => Validator::isString()
                ->pipe("trim")
                ->required("Le passe de confirmation doit être rempli")
                ->satisfies(
                    function($value, $input){
                        return isset($input["pass"]) && $value === $input["pass"];
                    },
                    "Les mots de passes ne correspondent pas"
                ),

            "firstname" => Validator::isString()
                ->pipe("trim")
                ->required("Le prénom doit être rempli")
                ->notEmpty("Le prénom ne peut pas être vide"),

            "lastname" => Validator::isString()
                ->pipe("trim")
                ->required("Le nom de famille doit être rempli")
                ->notEmpty("Le nom de famille ne peut pas être vide"),

            "height" => Validator::isInt()
                ->coerce()
                ->required("La taille doit être rempli")
                ->positive("Le taille doit être un nombre positif"),

            "weight" => Validator::isInt()
                ->coerce()
                ->required("Le poids doit être rempli")
                ->positive("Le poids doit être un nombre positif"),
                
        ]);
    }

    public function validate(array $data): array
    {
        [$valid, $validatedData, $errors] = $this->validator->tryValidate($data);

        return [
            'valid' => $valid,
            'data' => $validatedData,
            'errors' => $errors
        ];
    }
}