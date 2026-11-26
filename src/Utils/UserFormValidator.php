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
                ->required("L'email doit être remplis")
                ->email("Format d'email invalide"),
                
            "pass" => Validator::isString()
                ->pipe("trim")
                ->required("Le pass doit être remplis")
                ->minLength(16, "Il faut au minimum 16 caractères"),
            
            "confirm-pass" => Validator::isString()
                ->pipe("trim")
                ->required("Le pass doit être remplis")
                ->satisfies(
                    function($value, $input){
                        return isset($input["pass"]) && $value === $input["pass"];
                    },
                    "Les mots de pass ne correspondent pas"
                ),

            "firstname" => Validator::isString()
                ->pipe("trim")
                ->required("Le prénom doit être remplis")
                ->notEmpty("Le pass ne peut pas être vide"),

            "lastname" => Validator::isString()
                ->pipe("trim")
                ->required("Le nom de famille doit être remplis")
                ->notEmpty("Le pass ne peut pas être vide"),

            "height" => Validator::isInt()
                ->pipe("trim")
                ->coerce()
                ->required("La taille doit être remplis")
                ->positive("Le nombre doit être positif"),

            "weight" => Validator::isInt()
                ->pipe("trim")
                ->coerce()
                ->required("La taille doit être remplis")
                ->positive("Le nombre doit être positif"),
                
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