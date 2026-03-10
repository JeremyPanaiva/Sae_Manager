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
use Shared\PasswordValidator;
use Views\User\RegisterView;

/**
 * Class RegisterPost
 *
 * User registration submission controller.
 * Handles POST requests from the registration form.
 * Password complexity validation is delegated to PasswordValidator.
 * Creates the user account, sends a verification email on success, and logs failures.
 *
 * @package Controllers\User
 */
class RegisterPost implements ControllerInterface
{
    /**
     * Main controller method for registration.
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

        $lastName  = $_POST['nom']    ?? '';
        $firstName = $_POST['prenom'] ?? '';
        $email     = $_POST['mail']   ?? '';
        $mdp       = $_POST['mdp']    ?? '';
        $role      = $_POST['role']   ?? 'etudiant';

        $userModel = new User();
        $logger    = new Log();
        $validationExceptions = [];

        // 1. Delegate password validation to PasswordValidator
        $passwordErrors = PasswordValidator::validate($mdp);
        if (!empty($passwordErrors)) {
            $logger->create(
                null,
                'ECHEC_INSCRIPTION',
                'users',
                0,
                "Mot de passe non conforme pour : $email"
            );
            $validationExceptions = array_merge($validationExceptions, $passwordErrors);
        }

        // 2. Email format validation
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
            // 3. Email unicity check
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

            // 4. Create account and send verification email
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
