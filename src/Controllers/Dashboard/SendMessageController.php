<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\User\EmailService;
use Models\User\User;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\DataBaseException;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * Send Message Controller
 *
 * Handles sending messages from responsables to students via email.
 * Supports sending to multiple students at once, grouped by SAE.
 * Role verification is delegated to RoleGuard.
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

    /**
     * Main controller method.
     *
     * @return void
     */
    public function control(): void
    {
        // Verify user is authenticated as a supervisor
        RoleGuard::requireRole('responsable');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Requête invalide (CSRF).');
        }

        $studentIdsRaw = $_POST['student_id'] ?? [];
        if (!is_array($studentIdsRaw)) {
            $studentIdsRaw = [$studentIdsRaw];
        }

        $studentIds = array_filter(
            array_map(fn($id) => is_numeric($id) ? (int) $id : 0, $studentIdsRaw),
            fn($id) => $id > 0
        );

        $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim($_POST['subject']) : '';
        $message = isset($_POST['message']) && is_string($_POST['message']) ? trim($_POST['message']) : '';

        if (empty($studentIds) || $subject === '' || $message === '') {
            header('Location: /dashboard?error=missing_fields');
            exit();
        }

        try {
            $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];

            $responsableIdRaw = $user['id'] ?? 0;
            $responsableId    = is_numeric($responsableIdRaw) ? (int) $responsableIdRaw : 0;

            if ($responsableId === 0) {
                header('Location: /login');
                exit();
            }

            $responsablePrenom = is_string($user['prenom'] ?? null) ? (string) $user['prenom'] : '';
            $responsableNom    = is_string($user['nom']    ?? null) ? (string) $user['nom']    : '';
            $responsableName   = $responsablePrenom . ' ' . $responsableNom;

            // Group students by SAE
            $studentsBySae = [];
            foreach ($studentIds as $studentId) {
                $saeInfo = SaeAttribution::getSaeForStudentAndResponsable($studentId, $responsableId);

                if (
                    $saeInfo !== null &&
                    isset($saeInfo['sae_id'], $saeInfo['sae_name'])
                ) {
                    $saeId   = is_numeric($saeInfo['sae_id'])  ? (int) $saeInfo['sae_id']  : 0;
                    $saeName = is_string($saeInfo['sae_name']) ? $saeInfo['sae_name']       : '';

                    if ($saeId > 0 && $saeName !== '') {
                        if (!isset($studentsBySae[$saeId])) {
                            $studentsBySae[$saeId] = ['sae_name' => $saeName, 'students' => []];
                        }
                        $studentsBySae[$saeId]['students'][] = $studentId;
                        continue;
                    }
                }

                // No SAE found — group under key 0
                if (!isset($studentsBySae[0])) {
                    $studentsBySae[0] = ['sae_name' => null, 'students' => []];
                }
                $studentsBySae[0]['students'][] = $studentId;
            }

            $emailService = new EmailService();
            $successCount = 0;
            $failCount    = 0;
            $invalidCount = 0;

            foreach ($studentsBySae as $saeId => $saeData) {
                $saeName             = is_string($saeData['sae_name'] ?? null) ? (string)
                $saeData['sae_name'] : null;
                $studentsInSae       = $saeData['students'];
                $personalizedSubject = $saeName !== null ? "[SAE: {$saeName}] {$subject}" : $subject;

                foreach ($studentsInSae as $studentId) {
                    try {
                        $student = User::getById($studentId);

                        if (!$student) {
                            $invalidCount++;
                            error_log("Student with ID {$studentId} not found");
                            continue;
                        }

                        $studentRole = is_string($student['role'] ?? null) ? strtolower($student['role']) : '';
                        if ($studentRole !== 'etudiant') {
                            $invalidCount++;
                            error_log("User {$studentId} is not a student: " . (is_string(
                                $student['role'] ?? null
                            ) ? $student['role'] : 'unknown'));
                            continue;
                        }

                        $studentEmail  = is_string($student['mail']   ?? null) ? $student['mail']   : '';
                        $studentPrenom = is_string($student['prenom'] ?? null) ? $student['prenom'] : '';
                        $studentNom    = is_string($student['nom']    ?? null) ? $student['nom']    : '';
                        $studentName   = $studentPrenom . ' ' . $studentNom;

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
                        $invalidCount++;
                        error_log("Database error getting student {$studentId}: " . $e->getMessage());
                    } catch (\Exception $e) {
                        $invalidCount++;
                        error_log("Error getting student {$studentId}: " . $e->getMessage());
                    }
                }
            }

            // Redirect based on results
            if ($successCount > 0 && $failCount === 0 && $invalidCount === 0) {
                $param = $successCount === 1 ? 'message_sent' : 'messages_sent&count=' . $successCount;
                header('Location: /dashboard?success=' . $param);
            } elseif ($successCount > 0 && ($failCount > 0 || $invalidCount > 0)) {
                $totalFailed = $failCount + $invalidCount;
                header('Location: /dashboard?warning=partial_success&sent=' .
                    $successCount . '&failed=' . $totalFailed);
            } else {
                $errorParam = $invalidCount > 0 ? 'invalid_student' : 'mail_failed';
                header('Location: /dashboard?error=' . $errorParam);
            }
        } catch (DataBaseException $e) {
            error_log('Database error in SendMessageController: ' . $e->getMessage());
            header('Location: /dashboard?error=database');
        } catch (\Throwable $e) {
            error_log('Error in SendMessageController: ' . $e->getMessage());
            header('Location: /dashboard?error=unknown');
        }

        exit();
    }
}
