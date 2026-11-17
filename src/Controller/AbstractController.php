<?php

namespace App\Controller;

abstract class AbstractController
{
    /**
     * Méthode pour rendre une vue avec un template
     * @param string $template Le nom du template à inclure
     * @param string|null $title Le titre de la page
     * @param array $data Les données à passer au template
     * @return mixed
     */
    protected function render(string $template, ?string $title, array $data = []): mixed
    {
       return include __DIR__ . "/../../templates/template_" . $template . ".php";
    }

    /**
     * Méthode pour envoyer une réponse JSON
     * @param array $data Les données à encoder en JSON
     * @param int $statusCode Le code de statut HTTP
     * @return void
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Méthode pour vérifier si un formulaire a été soumis
     * @param array $post Les données POST
     * @return bool Vrai si le formulaire a été soumis, faux sinon
     */
    protected function isFormSubmitted(array $post, ?string $button = "submit"): bool
    {
        return isset($post[$button]);
    }

    /**
     * Méthode pour générer un Token CSRF
     * @return string $token CSRF Token
     */
    protected function getCsrfToken(): string
    {
        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        return $_SESSION["csrf_token"];
    }

    /**
     * Méthode pour tester la validé du token CSRF
     * @return bool $token true si valide false invalide 
     */
    protected function isCsrfTokenValid(array $post): bool
    {
        $token = $post["csrf_token"] ?? "";
        $sessionToken = $_SESSION["csrf_token"] ?? "";

        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }


}
