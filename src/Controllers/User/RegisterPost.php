<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\EmailService;
use Models\User\Log;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\EmailAlreadyExistsException;
use Shared\Exceptions\DataBaseException;
use Shared\CsrfGuard;
use Views\User\RegisterView;

/**
 * Class RegisterPost
 *
 * User registration submission controller.
 * Handles POST requests from the registration form. Validates user input including
 * password complexity requirements and checks for existing email addresses.
 * Creates the user account, sends a verification email on success, and logs
 * any validation or security failures for audit purposes.
 *
 * @package Controllers\User
 */
class RegisterPost implements ControllerInterface
{
    /**
     * Main controller method for registration.
     *
     * Validates registration form data, logs potential security issues (like
     * weak passwords or duplicate emails), creates a new user account with a
     * verification token, sends a verification email, and redirects to login.
     * Displays validation errors on failure.
     *
     * Password requirements:
     * - Between 12 and 30 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     * - At least one special character or punctuation
     *
     * @return void
     */
    public function control(): void
    {
        if (!isset($_POST['ok'])) {
            return;
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Invalid request (CSRF).');
        }

        $lastName = $_POST['nom'] ?? '';
        $firstName = $_POST['prenom'] ?? '';
        $email = $_POST['mail'] ?? '';
        $mdp = $_POST['mdp'] ?? '';
        $role = $_POST['role'] ?? 'etudiant';

        $userModel = new User();
        $logger = new Log();
        $validationExceptions = [];

        if (strlen($mdp) < 12 || strlen($mdp) > 30) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme (longueur) pour : $email"
            );
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir entre 12 et 30 caractères."
            );
        }

        if (!preg_match('/[A-Z]/', $mdp)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme (majuscule manquante) pour : $email"
            );
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre majuscule."
            );
        }

        if (!preg_match('/[a-z]/', $mdp)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme (minuscule manquante) pour : $email"
            );
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre minuscule."
            );
        }

        if (!preg_match('/[0-9]/', $mdp)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme (chiffre manquant) pour : $email"
            );
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins un chiffre."
            );
        }

        if (!preg_match('/[!@#$%^&*()_+€£µ§?\\/\\[\\]|{}]/', $mdp)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme (caractère spécial manquant) pour : $email"
            );
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins un des caractères spéciaux suivants : "
                . "! @ # $ % ^ & * ( ) _ + € £ µ § ? / \\ | { } [ ]"
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Format d'email invalide renseigné : $email"
            );
            $validationExceptions[] = new ValidationException(
                "L'adresse email \"$email\" n'est pas valide."
            );
        }

        try {
            try {
                $userModel->emailExists($email);
            } catch (DataBaseException $dbEx) {
                throw new ArrayException([new ValidationException($dbEx->getMessage())]);
            } catch (EmailAlreadyExistsException $e) {
                $logger->create(
                    null,
                    'ECHEC_INSCRIPTION',
                    'users',
                    0,
                    "Tentative d'inscription avec un email déjà utilisé : $email"
                );
                $validationExceptions[] = new ValidationException(
                    "L'adresse email \"$email\" est déjà utilisée."
                );
            }

            if (!empty($validationExceptions)) {
                throw new ArrayException($validationExceptions);
            }

            $verificationToken = bin2hex(random_bytes(32));

            $userModel->register($firstName, $lastName, $email, $mdp, $role, $verificationToken);

            $emailService = new EmailService();
            $emailService->sendAccountVerificationEmail($email, $verificationToken);

            header("Location: /user/login?success=registered");
            exit();
        } catch (ArrayException $exceptions) {
            $view = new RegisterView($exceptions->getExceptions());
            echo $view->render();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $chemin The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/user/register' and method is POST.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/register" && $method === "POST";
    }
}
