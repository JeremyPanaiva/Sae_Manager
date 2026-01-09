<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Views\User\ProfileView;
use Shared\Exceptions\DataBaseException;

/**
 * User profile controller
 *
 * Handles displaying and updating user profile information, as well as account deletion.
 * Allows users to modify their name, first name, and email address.  When the email is
 * changed, a verification email is sent and the user must re-verify their account.
 *
 * @package Controllers\User
 */
class ProfileController implements ControllerInterface
{
    /**
     * Profile page route path
     *
     * @var string
     */
    public const PATH = '/user/profile';

    /**
     * Account deletion route path
     *
     * @var string
     */
    public const PATH_DELETE = '/user/profile/delete';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $uri The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches profile routes with appropriate methods
     */
    public static function support(string $uri, string $method): bool
    {
        return ($uri === self::PATH && in_array($method, ['GET', 'POST']))
            || ($uri === self::PATH_DELETE && $method === 'POST');
    }

    /**
     * Main controller method
     *
     * Handles both GET (display profile) and POST (update profile) requests.
     * When email is changed, sends verification email and logs user out.
     * Routes account deletion requests to handleDelete().
     *
     * @return void
     */
    public function control(): void
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify user is authenticated
        if (!isset($_SESSION['user']['id'])) {
            header("Location: /login");
            exit;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Route to account deletion handler
        if ($path === self:: PATH_DELETE && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleDelete();
            return;
        }

        $userId = $_SESSION['user']['id'];
        $errors = [];
        $success = '';

        $userModel = new User();
        $userData = [];

        // Retrieve user data from database
        try {
            \Models\Database::checkConnection();
            $userData = $userModel:: getById($userId) ?? [];
        } catch (DataBaseException $e) {
            $errors[] = $e;
        } catch (\Throwable $e) {
            $errors[] = $e;
        }

        // Handle profile update (POST request)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            // Extract and sanitize form data
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $mail = trim($_POST['mail'] ?? '');

            // Validate required fields
            if (!$nom || !$prenom || !$mail) {
                $errors[] = new \Exception("Tous les champs sont obligatoires.");
            }

            if (empty($errors)) {
                try {
                    $conn = \Models\Database::getConnection();

                    // Check if email is already used by another user
                    $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ?  AND id != ?");
                    $stmt->bind_param("si", $mail, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $errors[] = new \Exception("Cet email est déjà utilisé.");
                    }

                    // Update profile
                    if (empty($errors)) {
                        $currentEmail = $_SESSION['user']['mail'];

                        // Handle email change
                        if ($mail !== $currentEmail) {
                            // Generate new verification token
                            $verificationToken = bin2hex(random_bytes(32));

                            // Update email and reset verification status
                            $userModel->updateEmail($userId, $mail, $verificationToken);

                            // Update other fields (name, first name)
                            $stmt = $conn->prepare("UPDATE users SET nom=?, prenom=? WHERE id=?");
                            $stmt->bind_param("ssi", $nom, $prenom, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Send verification email to new address
                            $emailService = new \Models\User\EmailService();
                            $emailService->sendAccountVerificationEmail($mail, $verificationToken);

                            // Log user out to force re-verification
                            session_destroy();

                            header("Location: /user/login? success=email_changed");
                            exit;
                        } else {
                            // Standard update without email change
                            $stmt = $conn->prepare("UPDATE users SET nom=?, prenom=? WHERE id=?");
                            $stmt->bind_param("ssi", $nom, $prenom, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Update session data
                            $_SESSION['user']['nom'] = $nom;
                            $_SESSION['user']['prenom'] = $prenom;

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
     * Handles permanent account deletion
     *
     * Deletes the user account and all associated data from the database,
     * destroys the session, and redirects to the home page.
     *
     * @return void
     */
    private function handleDelete(): void
    {
        $userId = $_SESSION['user']['id'];

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
            $_SESSION['error_message'] = "Erreur :  " . $e->getMessage();
            header("Location: /user/profile");
            exit;
        } catch (\Throwable $e) {
            // Generic error handling
            $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression. ";
            header("Location: /user/profile");
            exit;
        }
    }
}
