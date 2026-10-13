<?php
declare(strict_types=1);

namespace App\Services;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['authenticated'])
            && !empty($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn()
            && ($_SESSION['role'] ?? '') === 'Administrator';
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
    }

    public static function user(): array
    {
        return [
            'id'           => (int) ($_SESSION['user_id'] ?? 0),
            'username'     => (string) ($_SESSION['username'] ?? ''),
            'role'         => (string) ($_SESSION['role'] ?? ''),
            'full_name'    => (string) ($_SESSION['full_name'] ?? ''),
            'jail_unit_id' => isset($_SESSION['jail_unit_id']) && $_SESSION['jail_unit_id'] !== null
                                ? (int) $_SESSION['jail_unit_id']
                                : null,
        ];
    }

    /**
     * Returns the current user's assigned jail unit ID, or null if unassigned.
     * Used to auto-scope records for staff/officers.
     */
    public static function jailUnitId(): ?int
    {
        $id = $_SESSION['jail_unit_id'] ?? null;
        return ($id !== null && (int) $id > 0) ? (int) $id : null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['authenticated'] = true;
        $_SESSION['user_id']       = (int) $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['full_name']     = $user['full_name'] ?? '';
        $_SESSION['jail_unit_id']  = isset($user['jail_unit_id']) && $user['jail_unit_id'] !== null
                                        ? (int) $user['jail_unit_id']
                                        : null;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }

        session_destroy();
    }
}