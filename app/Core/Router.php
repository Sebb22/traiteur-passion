<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    public function get(string $path, string $action): void { $this->routes['GET'][$this->normalize($path)] = $action; }
    public function post(string $path, string $action): void { $this->routes['POST'][$this->normalize($path)] = $action; }
    public function patch(string $path, string $action): void { $this->routes['PATCH'][$this->normalize($path)] = $action; }
    public function delete(string $path, string $action): void { $this->routes['DELETE'][$this->normalize($path)] = $action; }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        $action = $this->routes[$method][$path] ?? null;

        if (!$action) {
            http_response_code(404);
            View::render('errors/404', ['title' => '404 — Introuvable']);
            return;
        }

        [$controller, $methodName] = explode('@', $action, 2);
        $fqcn = "App\\Controllers\\{$controller}";

        if (!class_exists($fqcn)) {
            throw new \RuntimeException("Controller introuvable: $fqcn");
        }

        $instance = new $fqcn();

        if (!method_exists($instance, $methodName)) {
            throw new \RuntimeException("Méthode introuvable: $fqcn::$methodName()");
        }

        $instance->$methodName();
    }

    private function normalize(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        // enlève le slash final sauf pour "/"
        return $path !== '/' ? rtrim($path, '/') : '/';
    }
}