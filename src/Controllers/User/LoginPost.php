<?php
namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\EmailNotFoundException;
use Shared\Exceptions\InvalidPasswordException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

class LoginPost implements ControllerInterface
{
    function control()
    {
        if (!isset($_POST['ok']))
            return;

        $email = $_POST['uname'] ?? '';
        $mdp = $_POST['psw'] ?? '';

        $User = new User();
        $validationExceptions = [];

        //  Vérifie email vide ou invalide
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException("Email invalide.");
        }

        //  Vérifie mot de passe vide
        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
        }

        try {
            //  Si des erreurs de validation locales
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            //  Vérifie la BDD en priorité
            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                throw new ArrayException([$dbEx]);
            }

            //  Email non trouvé
            if (!$userData) {
                throw new ArrayException([new EmailNotFoundException($email)]);
            }

            //  Vérifie compte activé
            if (isset($userData['is_verified']) && (int) $userData['is_verified'] === 0) {
                throw new ArrayException([new ValidationException("Votre compte n'est pas vérifié. Veuillez cliquer sur le lien reçu par email.")]);
            }

            //  Vérifie mot de passe
            if (!password_verify($mdp, $userData['mdp'])) {
                throw new ArrayException([new InvalidPasswordException()]);
            }

            //  Connexion réussie
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $role = isset($userData['role']) ? strtolower(trim($userData['role'])) : 'etudiant';

            $_SESSION['user'] = [
                'id' => $userData['id'],
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'role' => $role
            ];




            header("Location: /");
            exit();

        } catch (ArrayException $exceptions) {
            // Affiche les erreurs sur la vue
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
            return;
        }
    }

    static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}
