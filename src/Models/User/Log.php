<?php

namespace Models\User;

use Models\Database;

/**
 * Class Log
 *
 * Handles the creation of audit logs for security and tracking purposes.
 * It automatically captures system information (IP address, User Agent, OS)
 * to enrich the log data.
 *
 * @package Models\User
 */
class Log
{
    /**
     * Creates a new audit log entry in the database.
     *
     * This method automatically detects the user's IP address and system information
     * (Browser/OS) before inserting the record.
     *
     * @param int|null $userId    The ID of the user performing the action (NULL if anonymous/system).
     * @param string   $action    The type of action performed (e.g., 'LOGIN', 'DELETE_TASK').
     * @param string   $table     The database table affected by the action (e.g., 'users', 'sae').
     * @param int      $elementId The ID of the specific element being modified.
     * @param string   $details   A human-readable description of the event.
     *
     * @return void
     */
    public function create(?int $userId, string $action, string $table, int $elementId, string $details): void
    {
        try {
            $db = Database::getConnection();

            // 1. Retrieve the client's real IP address
            $ip = $this->getIpAddress();

            // 2. Parse the User Agent to get readable System Info (OS - Browser)
            $systemInfo = $this->getSystemInfo();

            // 3. Prepare the SQL Statement
            $stmt = $db->prepare("
                INSERT INTO logs (user_id, action, table_concernee, element_id, details, ip_address, system_info) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt) {
                // Bind parameters: userId(i), action(s), table(s), elementId(i), details(s), ip(s), systemInfo(s)
                $stmt->bind_param('ississs', $userId, $action, $table, $elementId, $details, $ip, $systemInfo);

                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }

    /**
     * Retrieves the client's IP address.
     *
     * Handles cases where the user is behind a proxy or load balancer.
     *
     * @return string The detected IP address or '0.0.0.0' if undetectable.
     */
    private function getIpAddress(): string
    {
        // Check HTTP_CLIENT_IP
        $clientIp = $_SERVER['HTTP_CLIENT_IP'] ?? null;
        if (is_string($clientIp) && !empty($clientIp)) {
            return $clientIp;
        }

        // Check HTTP_X_FORWARDED_FOR (Proxy)
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded) && !empty($forwarded)) {
            $ips = explode(',', $forwarded);
            return trim($ips[0]);
        }

        // Check REMOTE_ADDR
        $remote = $_SERVER['REMOTE_ADDR'] ?? null;
        if (is_string($remote)) {
            return $remote;
        }

        return '0.0.0.0';
    }

    /**
     * Parses the HTTP User Agent to return a readable string representing the OS and Browser.
     *
     * Examples: "iPhone (iOS) - Safari", "PC (Windows) - Chrome".
     *
     * @return string The formatted system information.
     */
    private function getSystemInfo(): string
    {
        $uaRaw = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ua = is_string($uaRaw) ? $uaRaw : 'Unknown';

        $os = "Unknown OS";
        $device = "Desktop/Unknown";

        // --- Operating System & Device Detection ---
        if (preg_match('/iphone/i', $ua)) {
            $device = "iPhone";
            $os = "iOS";
        } elseif (preg_match('/android/i', $ua)) {
            $device = "Smartphone";
            $os = "Android";
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $device = "Mac";
            $os = "macOS";
        } elseif (preg_match('/windows|win32/i', $ua)) {
            $device = "PC";
            $os = "Windows";
        } elseif (preg_match('/linux/i', $ua)) {
            $device = "PC";
            $os = "Linux";
        }

        // --- Browser Detection ---
        $browser = "Unknown Browser";

        if (preg_match('/MSIE/i', $ua) && !preg_match('/Opera/i', $ua)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('Firefox|FxiOS/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome|CriOS/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/Edg/i', $ua)) {
            $browser = 'Edge';
        }

        $result = "$device ($os) - $browser";
        return substr($result, 0, 255);
    }
}
