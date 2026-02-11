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
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['id']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== 'responsable'
        ) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentIds = $_POST['student_id'] ?? [];

            if (!is_array($studentIds)) {
                $studentIds = [$studentIds];
            }

            $studentIds = array_filter(array_map(function ($id) {
                return is_numeric($id) ? (int)$id : 0;
            }, $studentIds));

            $studentIds = array_filter($studentIds, function ($id) {
                return $id > 0;
            });

            $subject = isset($_POST['subject']) && is_string($_POST['subject'])
                ? trim($_POST['subject'])
                : '';
            $message = isset($_POST['message']) && is_string($_POST['message'])
                ? trim($_POST['message'])
                : '';

            if (empty($studentIds) || $subject === '' || $message === '') {
                header('Location: /dashboard?error=missing_fields');
                exit();
            }

            try {
                $userSession = $_SESSION['user'];

                if (
                    !isset($userSession['prenom']) ||
                    !isset($userSession['nom'])
                ) {
                    header('Location: /login');
                    exit();
                }

                $responsableIdRaw = $userSession['id'];
                $responsableId = is_numeric($responsableIdRaw) ? (int)$responsableIdRaw : 0;

                if ($responsableId === 0) {
                    header('Location: /login');
                    exit();
                }

                $responsablePrenom = is_string($userSession['prenom']) ? $userSession['prenom'] : '';
                $responsableNom = is_string($userSession['nom']) ? $userSession['nom'] : '';
                $responsableName = $responsablePrenom . ' ' . $responsableNom;

                $db = Database::getConnection();
                $studentsBySae = [];

                foreach ($studentIds as $studentId) {
                    $stmt = $db->prepare("
                        SELECT DISTINCT s.id as sae_id, s.titre as sae_name
                        FROM sae s
                        INNER JOIN sae_attributions sa ON s.id = sa.sae_id
                        WHERE sa.student_id = ? AND sa.responsable_id = ?
                        LIMIT 1
                    ");

                    if ($stmt === false) {
                        error_log("Failed to prepare statement for student {$studentId}");
                        continue;
                    }

                    $stmt->bind_param("ii", $studentId, $responsableId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result === false) {
                        $stmt->close();
                        continue;
                    }

                    $saeInfo = $result->fetch_assoc();
                    $stmt->close();

                    if ($saeInfo !== null && is_array($saeInfo)) {
                        $saeId = (int)$saeInfo['sae_id'];
                        $saeName = (string)$saeInfo['sae_name'];

                        if (!isset($studentsBySae[$saeId])) {
                            $studentsBySae[$saeId] = [
                                'sae_name' => $saeName,
                                'students' => []
                            ];
                        }

                        $studentsBySae[$saeId]['students'][] = $studentId;
                    } else {
                        if (!isset($studentsBySae[0])) {
                            $studentsBySae[0] = [
                                'sae_name' => null,
                                'students' => []
                            ];
                        }
                        $studentsBySae[0]['students'][] = $studentId;
                    }
                }

                $emailService = new EmailService();
                $successCount = 0;
                $failCount = 0;
                $invalidCount = 0;

                foreach ($studentsBySae as $saeId => $saeData) {
                    $saeName = $saeData['sae_name'];
                    $studentsInSae = $saeData['students'];

                    $personalizedSubject = $saeName !== null
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

                            $studentRole = is_string($student['role']) ? strtolower($student['role']) : '';

                            if ($studentRole !== 'etudiant') {
                                $invalidCount++;
                                $roleStr = is_string($student['role']) ? $student['role'] : 'unknown';
                                error_log("User {$studentId} is not a student: " . $roleStr);
                                continue;
                            }

                            $studentEmail = is_string($student['mail']) ? $student['mail'] : '';
                            $studentPrenom = is_string($student['prenom']) ? $student['prenom'] : '';
                            $studentNom = is_string($student['nom']) ? $student['nom'] : '';
                            $studentName = $studentPrenom . ' ' . $studentNom;

                            if ($studentEmail === '') {
                                $invalidCount++;
                                error_log("Student {$studentId} has no email");
                                continue;
                            }

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
                                    $logMessage = "Message sent to {$studentName} "
                                        . "({$studentEmail}) for SAE: {$saeName}";
                                    error_log($logMessage);
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

                if ($successCount > 0 && $failCount === 0 && $invalidCount === 0) {
                    if ($successCount === 1) {
                        header('Location: /dashboard?success=message_sent');
                    } else {
                        header('Location: /dashboard?success=messages_sent&count=' . $successCount);
                    }
                } elseif ($successCount > 0 && ($failCount > 0 || $invalidCount > 0)) {
                    $totalFailed = $failCount + $invalidCount;
                    $redirectUrl = '/dashboard?warning=partial_success'
                        . '&sent=' . $successCount
                        . '&failed=' . $totalFailed;
                    header('Location: ' . $redirectUrl);
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
