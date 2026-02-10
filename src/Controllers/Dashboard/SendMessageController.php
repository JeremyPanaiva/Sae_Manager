<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\User\EmailService;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * Send Message Controller
 *
 * Handles sending messages from responsables to students via email.
 * Supports sending to multiple students at once, grouped by SAE.
 *
 * @package Controllers\Dashboard
 */
class SendMessageController implements ControllerInterface
{
    public const PATH = '/dashboard/send-message';

    public function getPath(): string
    {
        return self::PATH;
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public static function support(string $chemin, string $method): bool
    {
        return $method === 'POST' && $chemin === self::PATH;
    }

    public function control(): void
    {
        // Verify user is logged in and is a responsable
        if (
            !isset($_SESSION['user']['id']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== 'responsable'
        ) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get form data
            $studentIds = $_POST['student_id'] ?? [];

            // Si c'est une string, convertir en tableau
            if (!is_array($studentIds)) {
                $studentIds = [$studentIds];
            }

            // Filtrer et convertir en entiers
            $studentIds = array_filter(array_map(function($id) {
                return is_numeric($id) ? (int)$id : 0;
            }, $studentIds));

            // Retirer les 0
            $studentIds = array_filter($studentIds, function($id) {
                return $id > 0;
            });

            $subject = isset($_POST['subject']) && is_string($_POST['subject'])
                ? trim($_POST['subject'])
                : '';
            $message = isset($_POST['message']) && is_string($_POST['message'])
                ? trim($_POST['message'])
                : '';

            // Validate required fields
            if (empty($studentIds) || $subject === '' || $message === '') {
                header('Location: /dashboard?error=missing_fields');
                exit();
            }

            try {
                // Get responsable information from session
                $responsableId = (int)$_SESSION['user']['id'];
                $responsableName = $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'];

                // Group students by SAE
                $db = Database::getConnection();
                $studentsBySae = [];

                foreach ($studentIds as $studentId) {
                    // Récupérer la SAE de cet étudiant (pour ce responsable)
                    $stmt = $db->prepare("
                        SELECT DISTINCT s.id as sae_id, s.titre as sae_name
                        FROM sae s
                        INNER JOIN sae_attributions sa ON s.id = sa.sae_id
                        WHERE sa.student_id = ? AND sa.responsable_id = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param("ii", $studentId, $responsableId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $saeInfo = $result->fetch_assoc();
                    $stmt->close();

                    if ($saeInfo) {
                        $saeId = (int)$saeInfo['sae_id'];
                        $saeName = $saeInfo['sae_name'];

                        if (!isset($studentsBySae[$saeId])) {
                            $studentsBySae[$saeId] = [
                                'sae_name' => $saeName,
                                'students' => []
                            ];
                        }

                        $studentsBySae[$saeId]['students'][] = $studentId;
                    } else {
                        // Si aucune SAE trouvée, mettre dans un groupe "general"
                        if (!isset($studentsBySae[0])) {
                            $studentsBySae[0] = [
                                'sae_name' => null,
                                'students' => []
                            ];
                        }
                        $studentsBySae[0]['students'][] = $studentId;
                    }
                }

                // Send emails grouped by SAE
                $emailService = new EmailService();
                $successCount = 0;
                $failCount = 0;
                $invalidCount = 0;

                foreach ($studentsBySae as $saeId => $saeData) {
                    $saeName = $saeData['sae_name'];
                    $studentsInSae = $saeData['students'];

                    // Personnaliser le sujet avec le nom de la SAE
                    $personalizedSubject = $saeName
                        ? "[SAE: {$saeName}] {$subject}"
                        : $subject;

                    foreach ($studentsInSae as $studentId) {
                        try {
                            $student = User::getById($studentId);

                            if (!$student) {
                                $invalidCount++;
                                error_log("Student with ID {$studentId} not found");
                                continue;
                            }

                            if (strtolower($student['role']) !== 'etudiant') {
                                $invalidCount++;
                                error_log("User {$studentId} is not a student: " . $student['role']);
                                continue;
                            }

                            $studentEmail = $student['mail'];
                            $studentName = $student['prenom'] . ' ' . $student['nom'];

                            // Send email with personalized subject
                            try {
                                $success = $emailService->sendMessageToStudent(
                                    $studentEmail,
                                    $studentName,
                                    $personalizedSubject,
                                    $message,
                                    $responsableName
                                );

                                if ($success) {
                                    $successCount++;
                                    error_log("Message sent to {$studentName} ({$studentEmail}) for SAE: {$saeName}");
                                } else {
                                    $failCount++;
                                    error_log("Failed to send message to {$studentName} ({$studentEmail})");
                                }
                            } catch (\Exception $e) {
                                $failCount++;
                                error_log("Exception sending message to {$studentName}: " . $e->getMessage());
                            }
                        } catch (DataBaseException $e) {
                            error_log("Database error getting student {$studentId}: " . $e->getMessage());
                            $invalidCount++;
                            continue;
                        } catch (\Exception $e) {
                            error_log("Error getting student {$studentId}: " . $e->getMessage());
                            $invalidCount++;
                            continue;
                        }
                    }
                }

                // Redirect with appropriate message
                if ($successCount > 0 && $failCount === 0 && $invalidCount === 0) {
                    if ($successCount === 1) {
                        header('Location: /dashboard?success=message_sent');
                    } else {
                        header('Location: /dashboard?success=messages_sent&count=' . $successCount);
                    }
                } elseif ($successCount > 0 && ($failCount > 0 || $invalidCount > 0)) {
                    $totalFailed = $failCount + $invalidCount;
                    header('Location: /dashboard?warning=partial_success&sent=' . $successCount . '&failed=' . $totalFailed);
                } else {
                    if ($invalidCount > 0) {
                        header('Location: /dashboard?error=invalid_student');
                    } else {
                        header('Location: /dashboard?error=mail_failed');
                    }
                }
            } catch (DataBaseException $e) {
                error_log('Database error in SendMessageController: ' . $e->getMessage());
                header('Location: /dashboard?error=database');
                exit();
            } catch (\Throwable $e) {
                error_log('Error in SendMessageController: ' . $e->getMessage());
                header('Location: /dashboard?error=unknown');
                exit();
            }
        } else {
            header('Location: /dashboard');
        }
        exit();
    }
}