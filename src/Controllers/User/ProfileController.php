<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\Log;
use Models\User\EmailService;
use Models\Database;
use Views\User\ProfileView;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

/**
 * Class ProfileController
 *
 * User profile controller.
 * Handles displaying and updating user profile information, as well as account deletion.
 * Allows users to modify their name, first name, and email address. When the email is
 * changed, a verification email is sent and the user must re-verify their account.
 * Logs security events such as failed account deletion attempts.
 *
 * @package Controllers\User
 */
class ProfileController implements ControllerInterface
{
    /**
     * Profile page route path.
     * @var string
     */
    public const PATH = '/user/profile';

    /**
     * Account deletion route path.
     * @var string
     */
    public const PATH_DELETE = '/user/profile/delete';

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $uri    The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path matches profile routes with appropriate methods.
     */
    public static function support(string $uri, string $method): bool
    {
        return ($uri === self::PATH && in_array($method, ['GET', 'POST']))
            || ($uri === self::PATH_DELETE && $method === 'POST');
    }

    /**
     * Main controller method.
     *
     * Handles both GET (display profile) and POST (update profile) requests.
     * When email is changed, sends verification email and logs user out.
     * Routes account deletion requests to handleDelete().
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify user is authenticated
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header("Location: /user/login");
            exit;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = is_string($requestUri) ? parse_url($requestUri, PHP_URL_PATH) : '';

        // Route to account deletion handler
        if ($path === self::PATH_DELETE && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleDelete();
            return;
        }

        $userIdRaw = $_SESSION['user']['id'];
        $userId = is_numeric($userIdRaw) ? (int) $userIdRaw : 0;
        $errors = [];
        $success = '';

        if (isset($_GET['success']) && $_GET['success'] === 'password_updated') {
            $success = "Mot de passe modifié avec succès.";
        }

        $userModel = new User();
        $logger = new Log();
        $userData = [];

        // Retrieve user data from database
        try {
            Database::checkConnection();
            $userData = $userModel::getById($userId) ?? [];
        } catch (DataBaseException $e) {
            $errors[] = $e;
        } catch (\Throwable $e) {
            $errors[] = $e;
        }

        // Handle profile update (POST request)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            // Extract and sanitize form data
            $nomRaw = $_POST['nom'] ?? '';
            $nom = is_string($nomRaw) ? trim($nomRaw) : '';

            $prenomRaw = $_POST['prenom'] ?? '';
            $prenom = is_string($prenomRaw) ? trim($prenomRaw) : '';

            $mailRaw = $_POST['mail'] ?? '';
            $mail = is_string($mailRaw) ? trim($mailRaw) : '';

            // Validate required fields
            if (!$nom || !$prenom || !$mail) {
                $errors[] = new \Exception("Tous les champs sont obligatoires.");
            }

            if (empty($errors)) {
                try {
                    $conn = Database::getConnection();

                    // Check if email is already used by another user
                    $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ? AND id != ?");
                    if ($stmt === false) {
                        throw new \Exception("Erreur de préparation de la requête");
                    }
                    $stmt->bind_param("si", $mail, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result === false) {
                        throw new \Exception("Erreur lors de l'exécution de la requête");
                    }

                    if ($result->num_rows > 0) {
                        // Audit: Log attempt to use an already taken email
                        $logger->create(
                            $userId,
                            'ECHEC_PROFIL_MODIF',
                            'users',
                            $userId,
                            "Tentative d'utilisation d'un email déjà pris : $mail"
                        );
                        $errors[] = new \Exception("Cet email est déjà utilisé.");
                    }

                    // Proceed with profile update if no errors
                    if (empty($errors)) {
                        $currentEmailRaw = $_SESSION['user']['mail'] ?? '';
                        $currentEmail = is_string($currentEmailRaw) ? $currentEmailRaw : '';

                        // Handle email change
                        if ($mail !== $currentEmail) {
                            // Generate new verification token
                            $verificationToken = bin2hex(random_bytes(32));

                            // Update email and reset verification status
                            $userModel->updateEmail($userId, $mail, $verificationToken);

                            // Update other fields (name, first name)
                            $stmt = $conn->prepare("UPDATE users SET nom=?, prenom=? WHERE id=?");
                            if ($stmt === false) {
                                throw new \Exception("Erreur de préparation de la requête");
                            }
                            $stmt->bind_param("ssi", $nom, $prenom, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Send verification email to new address
                            $emailService = new EmailService();
                            $emailService->sendAccountVerificationEmail($mail, $verificationToken);

                            // Log user out to force re-verification
                            session_destroy();

                            header("Location: /user/login?success=email_changed");
                            exit;
                        } else {
                            // Standard update without email change
                            $stmt = $conn->prepare("UPDATE users SET nom=?, prenom=? WHERE id=?");
                            if ($stmt === false) {
                                throw new \Exception("Erreur de préparation de la requête");
                            }
                            $stmt->bind_param("ssi", $nom, $prenom, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Update session data
                            if (isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'])) {
                                $_SESSION['user']['nom'] = $nom;
                                $_SESSION['user']['prenom'] = $prenom;
                            }

                            $success = "Profil mis à jour avec succès.";
                            $userData = $userModel::getById($userId) ?? [];
                        }
                    }
                } catch (DataBaseException $e) {
                    $errors[] = $e;
                } catch (\Throwable $e) {
                    $errors[] = $e;
                }
            }
        }

        // Render profile view
        $view = new ProfileView($userData, $errors, $success);
        echo $view->render();
    }

    /**
     * Handles permanent account deletion.
     *
     * Deletes the user account and all associated data from the database,
     * destroys the session, and redirects to the home page. Verifies the user's
     * password before deletion and logs any failed attempts.
     *
     * @return void
     */
    private function handleDelete(): void
    {
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header("Location: /user/login");
            exit;
        }

        $userIdRaw = $_SESSION['user']['id'];
        $userId = is_numeric($userIdRaw) ? (int) $userIdRaw : 0;

        $passwordRaw = $_POST['delete_password'] ?? '';
        $password = is_string($passwordRaw) ? $passwordRaw : '';

        $userModel = new User();
        $logger = new Log();

        $sessionMail = $_SESSION['user']['mail'] ?? '';
        $email = is_string($sessionMail) ? $sessionMail : '';

        $userData = $userModel->findByEmail($email);
        $hash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';

        // Validate password for account deletion
        if (!$userData || !password_verify($password, $hash)) {
            // Audit: Log failed deletion attempt due to incorrect password
            $logger->create(
                $userId,
                'ECHEC_SUPPRESSION_COMPTE',
                'users',
                $userId,
                "Tentative de suppression de compte échouée : Mot de passe incorrect"
            );

            $_SESSION['error_message'] = "Mot de passe incorrect.";
            $_SESSION['delete_error'] = true;
            header("Location: /user/profile");
            exit;
        }

        try {
            // Check database connection
            User::checkDatabaseConnection();

            // Delete user account and associated data
            User::deleteAccount($userId);

            // Destroy session
            session_destroy();

            // Redirect to home page with deletion confirmation
            header("Location: /?deleted=1");
            exit;
        } catch (DataBaseException $e) {
            // Database error
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            header("Location: /user/profile");
            exit;
        } catch (\Throwable $e) {
            // Generic error handling
            $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression.";
            header("Location: /user/profile");
            exit;
        }
    }
}
