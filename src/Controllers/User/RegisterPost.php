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

class RegisterPost implements ControllerInterface
{
    public function control()
    {
        if (!isset($_POST['ok']))
            return;

        $lastName = $_POST['nom'] ?? '';
        $firstName = $_POST['prenom'] ?? '';
        $email = $_POST['mail'] ?? '';
        $mdp = $_POST['mdp'] ?? '';
        $role = $_POST['role'] ?? 'etudiant';

        $User = new User();
        $validationExceptions = [];

        // Vérifie la longueur du mot de passe
        if (strlen($mdp) < 8 || strlen($mdp) > 20) {
            $validationExceptions[] = new ValidationException(
                "Le mot de passe doit contenir entre 8 et 20 caractères."
            );
        }

        // Vérifie le format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException(
                "L'adresse email \"$email\" n'est pas valide."
            );
        }

        try {
            // Vérifie si l'email existe déjà dans la base
            try {
                $User->emailExists($email);
            } catch (DataBaseException $dbEx) {
                throw new ArrayException([$dbEx]);
            } catch (EmailAlreadyExistsException $e) {
                $validationExceptions[] = new ValidationException(
                    "L'adresse email \"$email\" est déjà utilisée."
                );
            }

            // Si des erreurs existent, on lance une exception
            if (!empty($validationExceptions)) {
                throw new ArrayException($validationExceptions);
            }

            // Inscription
            $verificationToken = bin2hex(random_bytes(32));
            $User->register($firstName, $lastName, $email, $mdp, $role, $verificationToken);

            // Envoi de l'email de vérification
            $emailService = new EmailService();
            $emailService->sendAccountVerificationEmail($email, $verificationToken);

            // Redirection vers login
            header("Location: /user/login?success=registered");
            exit();

        } catch (ArrayException $exceptions) {
            $view = new \Views\User\RegisterView($exceptions->getExceptions());
            echo $view->render();
        }
    }

    public static function support(string $path, string $method): bool
    {
        return $path === "/user/register" && $method === "POST";
    }
}
