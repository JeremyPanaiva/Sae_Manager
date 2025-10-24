<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\DataBaseException;

class UpdateSaeDateController implements ControllerInterface
{
    public const PATH = '/sae/update_date';

    public function control()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        try {
            $responsableId = $_SESSION['user']['id'];
            $saeAttributionId = intval($_POST['sae_attribution_id'] ?? 0);
            $newDate = $_POST['date_rendu'] ?? '';

            if ($saeAttributionId <= 0 || !$newDate) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
                header('Location: /dashboard');
                exit();
            }

            // Récupérer le SAE ID correspondant à cette attribution
            $db = \Models\Database::getConnection();
            $stmt = $db->prepare("SELECT sae_id FROM sae_attributions WHERE id = ? AND responsable_id = ?");
            $stmt->bind_param("ii", $saeAttributionId, $responsableId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $_SESSION['error_message'] = "SAE invalide ou non attribuée.";
                header('Location: /dashboard');
                exit();
            }

            $saeId = $row['sae_id'];

            // Mettre à jour la date pour tous les étudiants de cette SAE
            SaeAttribution::updateDateRendu($saeId, $responsableId, $newDate);

            $_SESSION['success_message'] = "Date de rendu mise à jour avec succès.";
            header('Location: /dashboard');
            exit();

        } catch (DataBaseException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /dashboard');
            exit();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Une erreur est survenue. Veuillez réessayer.";
            header('Location: /dashboard');
            exit();
        }
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
