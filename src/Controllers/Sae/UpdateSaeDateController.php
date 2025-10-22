<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;

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

        $responsableId = $_SESSION['user']['id'];
        $saeAttributionId = intval($_POST['sae_attribution_id'] ?? 0);
        $newDate = $_POST['date_rendu'] ?? '';

        if ($saeAttributionId <= 0 || !$newDate) {
            header('Location: /dashboard?error=missing_fields');
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
            header('Location: /dashboard?error=invalid_sae');
            exit();
        }

        $saeId = $row['sae_id'];

        // Mettre à jour la date pour tous les étudiants de cette SAE
        \Models\Sae\SaeAttribution::updateDateRendu($saeId, $responsableId, $newDate);

        header('Location: /dashboard?success=date_updated');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
