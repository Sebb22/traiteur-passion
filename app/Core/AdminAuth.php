<?php
declare (strict_types = 1);

namespace App\Core;

final class AdminAuth
{
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['admin_authenticated'])
            && $_SESSION['admin_authenticated'] === true;
    }

    public static function attemptLogin(string $username, string $password): bool
    {
        $expectedUser  = (string) Config::get('ADMIN_USER', '');
        $passwordHash  = (string) Config::get('ADMIN_PASSWORD_HASH', '');
        $plainPassword = (string) Config::get('ADMIN_PASSWORD', '');

        if ($expectedUser === '' || ($passwordHash === '' && $plainPassword === '')) {
            return false;
        }

        $userOk = hash_equals($expectedUser, $username);

        $passwordOk = false;
        if ($passwordHash !== '') {
            $passwordOk = password_verify($password, $passwordHash);
        } elseif ($plainPassword !== '') {
            $passwordOk = hash_equals($plainPassword, $password);
        }

        if (! $userOk || ! $passwordOk) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_user']          = $username;
        $_SESSION['admin_login_at']      = time();

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];

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

        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (self::isAuthenticated()) {
            return;
        }

        $uri    = $_SERVER['REQUEST_URI'] ?? '/admin/contacts';
        $target = '/admin/login?redirect=' . urlencode($uri);
        header('Location: ' . $target);
        exit;
    }
}
