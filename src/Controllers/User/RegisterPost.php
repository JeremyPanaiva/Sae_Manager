<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\EmailAlreadyExistsException;
use Shared\Exceptions\DataBaseException;
use Views\User\RegisterView;

/**
 * User registration submission controller
 *
 * Handles POST requests from the registration form.  Validates user input including
 * password complexity requirements, checks for existing email addresses, creates the
 * user account, and sends a verification email.
 *
 * @package Controllers\User
 */
class RegisterPost implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Validates registration form data, creates a new user account with a verification
     * token, sends a verification email, and redirects to login on success.
     * Displays validation errors on failure.
     *
     * Password requirements:
     * - Between 8 and 20 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     *
     * Email requirements:
     * - Valid email format
     * - Not already registered in the system
     *
     * @return void
     */
    public function control()
    {
        // Check if form was submitted
        if (!isset($_POST['ok']))
            return;

        // Extract form data
        $lastName = $_POST['nom'] ?? '';
        $firstName = $_POST['prenom'] ?? '';
        $email = $_POST['mail'] ?? '';
        $mdp = $_POST['mdp'] ?? '';
        $role = $_POST['role'] ??  'etudiant';

        $User = new User();
        $validationExceptions = [];

        // Validate password length
        if (strlen($mdp) < 8 || strlen($mdp) > 20) {
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir entre 8 et 20 caractères."
            );
        }

        // Validate password contains uppercase letter
        if (!preg_match('/[A-Z]/', $mdp)) {
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre majuscule."
            );
        }

        // Validate password contains lowercase letter
        if (!preg_match('/[a-z]/', $mdp)) {
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre minuscule."
            );
        }

        // Validate password contains digit
        if (!preg_match('/[0-9]/', $mdp)) {
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir au moins un chiffre."
            );
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException(
                "L'adresse email \"$email\" n'est pas valide."
            );
        }

        try {
            // Check if email already exists in database
            try {
                $User->emailExists($email);
            } catch (DataBaseException $dbEx) {
                // Wrap database exception in ArrayException
                throw new ArrayException([$dbEx]);
            } catch (EmailAlreadyExistsException $e) {
                // Email is already registered
                $validationExceptions[] = new ValidationException(
                    "L'adresse email \"$email\" est déjà utilisée."
                );
            }

            // If validation errors exist, throw exception to display them
            if (!empty($validationExceptions)) {
                throw new ArrayException($validationExceptions);
            }

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));

            // Register user with verification token
            $User->register($firstName, $lastName, $email, $mdp, $role, $verificationToken);

            // Send account verification email
            $emailService = new EmailService();
            $emailService->sendAccountVerificationEmail($email, $verificationToken);

            // Redirect to login with success message
            header("Location: /user/login?success=registered");
            exit();

        } catch (ArrayException $exceptions) {
            // Display registration form with validation errors
            $view = new \Views\User\RegisterView($exceptions->getExceptions());
            echo $view->render();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/register' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === "/user/register" && $method === "POST";
    }
}