<?php
/**
 * Single active session enforcement (admin + client)
 * Justice Law Firm
 *
 * Only one device may be signed in to an account at a time. A successful login
 * stores the fresh PHP session id on the account row (current_session_id).
 * Every protected request compares the running session with the stored one:
 * when they no longer match the account was opened somewhere else, so the
 * local session is dropped and the visitor is asked to sign in again.
 */

/**
 * Drop the current session (and its cookie) and send the visitor to $target.
 *
 * @param string $target Relative URL to redirect to
 */
if (!function_exists('destroy_session_and_redirect')) {
    function destroy_session_and_redirect(string $target): void
    {
        // Clear every session variable
        $_SESSION = [];

        // Remove the session cookie so the browser stops sending the old id
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session on the server
        session_destroy();

        header('Location: ' . $target);
        exit;
    }
}

/**
 * Register the session that is allowed to use this account and stamp the
 * login time. Called right after a successful password verification.
 *
 * @param string $table Account table ("users" or "admin_users")
 * @param int    $id    Account id
 */
if (!function_exists('register_account_session')) {
    function register_account_session(string $table, int $id): void
    {
        execute(
            "UPDATE {$table} SET current_session_id = ?, last_login = NOW() WHERE id = ?",
            [session_id(), $id]
        );
    }
}

/**
 * Release the account session lock (used by the logout handler).
 *
 * @param string $table Account table ("users" or "admin_users")
 * @param int    $id    Account id
 */
if (!function_exists('release_account_session')) {
    function release_account_session(string $table, int $id): void
    {
        execute("UPDATE {$table} SET current_session_id = NULL WHERE id = ?", [$id]);
    }
}

/**
 * Validate the running session against the session id stored on the account.
 * Redirects to login.php when the account was signed in from another device.
 */
if (!function_exists('enforce_single_session')) {
    function enforce_single_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAdmin = !empty($_SESSION['admin_id']);
        $isClient = empty($_SESSION['admin_id']) && !empty($_SESSION['user_id']);

        // Anonymous visitors have nothing to protect
        if (!$isAdmin && !$isClient) {
            return;
        }

        $table = $isAdmin ? 'admin_users' : 'users';
        $id = (int)($isAdmin ? $_SESSION['admin_id'] : $_SESSION['user_id']);

        $account = getRow("SELECT current_session_id FROM {$table} WHERE id = ?", [$id]);

        if (!$account) {
            // The account no longer exists - drop the stale session
            destroy_session_and_redirect('login.php');
            return;
        }

        $storedSessionId = $account['current_session_id'] ?? '';

        if ($storedSessionId === null || $storedSessionId === '') {
            // No session registered yet (first request after this feature was
            // enabled, or after a clean logout) - this session claims it
            execute("UPDATE {$table} SET current_session_id = ? WHERE id = ?", [session_id(), $id]);
            return;
        }

        if (!hash_equals((string)$storedSessionId, (string)session_id())) {
            // The account was opened on another device in the meantime
            destroy_session_and_redirect('login.php?error=concurrent_login');
        }
    }
}
