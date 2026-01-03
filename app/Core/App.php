<?php
declare(strict_types=1);

namespace App\Core;

final class App
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        try {
            $this->router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log((string) $e);
            echo "<pre>Erreur serveur:\n" .
                 htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
                 "\n</pre>";
        }
    }
}
