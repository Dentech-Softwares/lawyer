<?php
/**
 * Helper Functions
 * Shared utilities for the Justice Law Firm application
 */

if (!function_exists('get_user_initials')) {
    /**
     * Generate avatar initials from a full name
     * @param string $name Full name (e.g., "Dennis Njenga")
     * @param int $maxLength Maximum number of characters (default 2)
     * @return string Uppercase initials (e.g., "DN")
     */
    function get_user_initials(string $name, int $maxLength = 2): string
    {
        $name = trim($name);
        if (empty($name)) {
            return '??';
        }
        
        $parts = preg_split('/\s+/', $name);
        $initials = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                if (mb_strlen($initials) >= $maxLength) {
                    break;
                }
            }
        }
        
        return $initials ?: '??';
    }
}

if (!function_exists('get_current_user_name')) {
    /**
     * Get the current logged-in user's display name
     * @return string
     */
    function get_current_user_name(): string
    {
        if (isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username'])) {
            return $_SESSION['admin_username'];
        }
        if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
            return $_SESSION['user_name'];
        }
        if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
            return $_SESSION['full_name'];
        }
        return 'User';
    }
}

if (!function_exists('get_current_user_initials')) {
    /**
     * Get the current logged-in user's avatar initials
     * @return string
     */
    function get_current_user_initials(): string
    {
        return get_user_initials(get_current_user_name());
    }
}

if (!function_exists('get_current_user_role')) {
    /**
     * Get the current logged-in user's role label
     * @return string
     */
    function get_current_user_role(): string
    {
        if (isset($_SESSION['admin_id'])) {
            return 'Administrator';
        }
        if (isset($_SESSION['user_id'])) {
            return 'Client Account';
        }
        return 'Guest';
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if current user is admin
     * @return bool
     */
    function is_admin(): bool
    {
        return isset($_SESSION['admin_id']);
    }
}

if (!function_exists('is_client')) {
    /**
     * Check if current user is client
     * @return bool
     */
    function is_client(): bool
    {
        return isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
    }
}

if (!function_exists('can_manage_case_notes')) {
    /**
     * Case notes are maintained by the firm only.
     * Clients get a strictly read-only view of the notes on their own cases.
     *
     * Supports an explicit $_SESSION['role'] when one is set, and falls back
     * to the admin session flag used by the portal.
     *
     * @return bool
     */
    function can_manage_case_notes(): bool
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] !== '') {
            return $_SESSION['role'] === 'admin';
        }

        return isset($_SESSION['admin_id']);
    }
}

if (!function_exists('formatKenyanPhone')) {
    function formatKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) {
            return $phone;
        }
        if (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        if (substr($phone, 0, 3) === '254') {
            return $phone;
        }
        if (substr($phone, 0, 4) === '+254') {
            return substr($phone, 1);
        }
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') {
            return '254' . $phone;
        }
        return $phone;
    }
}

if (!function_exists('formatKenyanPhone')) {
    function formatKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return $phone;
        if (substr($phone, 0, 1) === '0') return '254' . substr($phone, 1);
        if (substr($phone, 0, 3) === '254') return $phone;
        if (substr($phone, 0, 4) === '+254') return substr($phone, 1);
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') return '254' . $phone;
        return $phone;
    }
}

if (!function_exists('formatKenyanPhone')) {
    function formatKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return $phone;
        if (substr($phone, 0, 1) === '0') return '254' . substr($phone, 1);
        if (substr($phone, 0, 3) === '254') return $phone;
        if (substr($phone, 0, 4) === '+254') return substr($phone, 1);
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') return '254' . $phone;
        return $phone;
    }
}

if (!function_exists('formatKenyanPhone')) {
    function formatKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return $phone;
        if (substr($phone, 0, 1) === '0') return '254' . substr($phone, 1);
        if (substr($phone, 0, 3) === '254') return $phone;
        if (substr($phone, 0, 4) === '+254') return substr($phone, 1);
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') return '254' . $phone;
        return $phone;
    }
}

if (!function_exists('formatKenyanPhone')) {
    function formatKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return $phone;
        if (substr($phone, 0, 1) === '0') return '254' . substr($phone, 1);
        if (substr($phone, 0, 3) === '254') return $phone;
        if (substr($phone, 0, 4) === '+254') return substr($phone, 1);
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') return '254' . $phone;
        return $phone;
    }
}

if (!function_exists('sendSMS')) {
    function sendSMS(string $phone, string $message): array
    {
        global $conn;
        $apiToken = '364|RZhtRx1OcagcMR9tU1GLgWzX4aXXeKMSaVDsVzbf1d94cf36';
        $phone = formatKenyanPhone($phone);
        $logId = null;
        
        if (isset($conn) && $conn) {
            $stmt = mysqli_prepare($conn, "INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, 'pending')");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $phone, $message);
                mysqli_stmt_execute($stmt);
                $logId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }
        
        $apiEndpoint = 'https://www.opensms.co.ke/api/http/sms/send';
        $success = false;
        $lastResponse = '';
        $lastCode = 0;
        $errDetail = '';
        
        $payload = [
            'recipient' => $phone,
            'message' => $message,
            'api_token' => $apiToken,
            'sender_id' => 'OPENSMS',
            'sender' => 'OPENSMS',
            'from' => 'OPENSMS',
            'originator' => 'OPENSMS',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $lastResponse = ($curlError ? "cURL:$curlError|" : '') . $response;
        $lastCode = $httpCode;
        $json = json_decode($response, true);
        if ($json) {
            if (isset($json['status']) && in_array($json['status'], ['success', 'sent', true, 1], true)) {
                $success = true;
            }
            if (isset($json['message'])) $errDetail = $json['message'];
            elseif (isset($json['error'])) $errDetail = $json['error'];
        }
        
        if ($logId && isset($conn) && $conn) {
            $status = $success ? 'sent' : 'failed';
            $logText = 'TO:' . $phone . ' [CODE:' . $lastCode . '] [ERR:' . $errDetail . '] [RESP:' . substr($lastResponse, 0, 2000) . ']';
            $stmt = mysqli_prepare($conn, "UPDATE sms_logs SET status = ?, message = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssi', $status, $logText, $logId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        return ['success' => $success, 'response' => $lastResponse, 'log_id' => $logId, 'http_code' => $lastCode, 'phone_formatted' => $phone, 'error_detail' => $errDetail];
    }
}




if (!function_exists('sendSMS')) {
    /**
     * Send SMS via OpenSMS API
     * @param string $phone Recipient phone number
     * @param string $message SMS message content
     * @return array ['success' => bool, 'response' => string, 'log_id' => int|null]
     */
    function sendSMS(string $phone, string $message): array
    {
        global $conn;
        
        // OpenSMS API configuration
        $apiEndpoint = 'https://www.opensms.co.ke/api/http/';
        $apiToken = '364|RZhtRx1OcagcMR9tU1GLgWzX4aXXeKMSaVDsVzbf1d94cf36';
        
        // Log the attempt first
        $logId = null;
        if (isset($conn) && $conn) {
            $stmt = mysqli_prepare($conn, "INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, 'pending')");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $phone, $message);
                mysqli_stmt_execute($stmt);
                $logId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Prepare payload
        $payload = [
            'phone' => $phone,
            'message' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $result = [
            'success' => false,
            'response' => '',
            'log_id' => $logId,
            'http_code' => $httpCode
        ];
        
        if ($curlError) {
            $result['response'] = 'cURL Error: ' . $curlError;
            if ($logId && isset($conn) && $conn) {
                $stmt = mysqli_prepare($conn, "UPDATE sms_logs SET status = 'failed' WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $logId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
            return $result;
        }
        
        $result['response'] = $response;
        
        // Check if request was successful (typically 200)
        if ($httpCode >= 200 && $httpCode < 300) {
            $result['success'] = true;
            if ($logId && isset($conn) && $conn) {
                $stmt = mysqli_prepare($conn, "UPDATE sms_logs SET status = 'sent' WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $logId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        } else {
            if ($logId && isset($conn) && $conn) {
                $stmt = mysqli_prepare($conn, "UPDATE sms_logs SET status = 'failed' WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $logId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
        
        return $result;
    }
}
