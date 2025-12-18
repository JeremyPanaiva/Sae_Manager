<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Views\User\ProfileView;
use Shared\Exceptions\DataBaseException;

class ProfileController implements ControllerInterface {
    public const PATH = '/user/profile';
    public const PATH_DELETE = '/user/profile/delete';

    public static function support(string $uri, string $method): bool {
        return ($uri === self:: PATH && in_array($method, ['GET','POST']))
            || ($uri === self:: PATH_DELETE && $method === 'POST');
    }

    public function control(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']['id'])) {
            header("Location: /login");
            exit;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


        if ($path === self::PATH_DELETE && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleDelete();
            return;
        }

        $userId = $_SESSION['user']['id'];
        $errors = [];
        $success = '';

        $userModel = new User();
        $userData = [];


        try {
            $userModel::checkDatabaseConnection();
            $userData = $userModel:: getById($userId) ?? [];
        } catch (DataBaseException $e) {
            $errors[] = $e;
        } catch (\Throwable $e) {
            $errors[] = $e;
        }

        // Traitement POST pour update du profil
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            $nom = trim($_POST['nom'] ??  '');
            $prenom = trim($_POST['prenom'] ??  '');
            $mail = trim($_POST['mail'] ?? '');

            if (!$nom || ! $prenom || !$mail) {
                $errors[] = new \Exception("Tous les champs sont obligatoires.");
            }

            if (empty($errors)) {
                try {
                    $conn = \Models\Database::getConnection();

                    // Vérifie si l'email existe pour un autre utilisateur
                    $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ?  AND id != ?");
                    $stmt->bind_param("si", $mail, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $errors[] = new \Exception("Cet email est déjà utilisé.");
                    }

                    // Update du profil
                    if (empty($errors)) {
                        $stmt = $conn->prepare("UPDATE users SET nom=?, prenom=?, mail=? WHERE id=?");
                        $stmt->bind_param("sssi", $nom, $prenom, $mail, $userId);
                        $stmt->execute();
                        $stmt->close();

                        $_SESSION['user']['nom'] = $nom;
                        $_SESSION['user']['prenom'] = $prenom;
                        $_SESSION['user']['mail'] = $mail;

                        $success = "Profil mis à jour avec succès. ";
                        $userData = $userModel::getById($userId) ?? [];
                    }

                } catch (DataBaseException $e) {
                    $errors[] = $e;
                } catch (\Throwable $e) {
                    $errors[] = $e;
                }
            }
        }

        // Affiche la vue
        $view = new ProfileView($userData, $errors, $success);
        echo $view->render();
    }

    /**
     * ✅ Gérer la suppression définitive du compte
     */
    private function handleDelete(): void
    {
        $userId = $_SESSION['user']['id'];

        try {
            User::checkDatabaseConnection();
            User:: deleteAccount($userId);

            // Détruit la session
            session_destroy();

            // Redirige vers la page d'accueil avec un message
            header("Location: /?deleted=1");
            exit;

        } catch (DataBaseException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            header("Location: /user/profile");
            exit;
        } catch (\Throwable $e) {
            $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression. ";
            header("Location: /user/profile");
            exit;
        }
    }
}