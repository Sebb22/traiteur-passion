<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (AdminAuth::isAuthenticated()) {
            header('Location: /admin/contacts');
            exit;
        }

        View::render('admin/login', [
            'title'    => 'Connexion admin',
            'error'    => null,
            'redirect' => $this->safeRedirect($_GET['redirect'] ?? '/admin/contacts'),
        ]);
    }

    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $redirect = $this->safeRedirect($_POST['redirect'] ?? '/admin/contacts');

        if (AdminAuth::attemptLogin($username, $password)) {
            header('Location: ' . $redirect);
            exit;
        }

        sleep(1);

        View::render('admin/login', [
            'title'    => 'Connexion admin',
            'error'    => 'Identifiants invalides.',
            'redirect' => $redirect,
        ]);
    }

    public function logout(): void
    {
        AdminAuth::logout();
        header('Location: /admin/login');
        exit;
    }

    private function safeRedirect(string $redirect): string
    {
        if ($redirect === '' || strpos($redirect, '/admin') !== 0) {
            return '/admin/contacts';
        }

        return $redirect;
    }
}
