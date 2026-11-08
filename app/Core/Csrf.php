<?php
declare (strict_types = 1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = 'admin_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || ! is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($expected)
        && $expected !== ''
        && is_string($token)
        && $token !== ''
        && hash_equals($expected, $token);
    }

    public static function requireValid(): void
    {
        if (! self::validate(isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : null)) {
            http_response_code(419);
            exit('Jeton de sécurité invalide. Rechargez la page puis réessayez.');
        }
    }
}
