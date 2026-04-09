<?php
declare (strict_types = 1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public function get(string $path, string $action): void
    {$this->routes['GET'][$this->normalize($path)] = $action;}
    public function post(string $path, string $action): void
    {$this->routes['POST'][$this->normalize($path)] = $action;}
    public function patch(string $path, string $action): void
    {$this->routes['PATCH'][$this->normalize($path)] = $action;}
    public function delete(string $path, string $action): void
    {$this->routes['DELETE'][$this->normalize($path)] = $action;}

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path   = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        // Try exact match first
        $action = $this->routes[$method][$path] ?? null;
        $params = [];

        // If no exact match, try pattern matching for dynamic routes
        if (! $action) {
            foreach ($this->routes[$method] as $route => $routeAction) {
                $pattern = $this->convertRouteToRegex($route);
                if (preg_match($pattern, $path, $matches)) {
                    $action = $routeAction;
                    // Extract parameters (remove full match)
                    array_shift($matches);
                    $params = $matches;
                    break;
                }
            }
        }

        if (! $action) {
            HttpError::notFound();
            return;
        }

        [$controller, $methodName] = explode('@', $action, 2);
        $fqcn                      = "App\\Controllers\\{$controller}";

        if (! class_exists($fqcn)) {
            throw new \RuntimeException("Controller introuvable: $fqcn");
        }

        $instance = new $fqcn();

        if (! method_exists($instance, $methodName)) {
            throw new \RuntimeException("Méthode introuvable: $fqcn::$methodName()");
        }

        // Call method with parameters
        $instance->$methodName(...$params);
    }

    /**
     * Convert route pattern like /admin/contacts/{id} to regex
     */
    private function convertRouteToRegex(string $route): string
    {
        // Escape special regex characters except {}
        $pattern = preg_quote($route, '#');

        // Replace {param} with capture group for one URI segment
        $pattern = preg_replace('/\\\{(\w+)\\\}/', '([^/]+)', $pattern);

        return '#^' . $pattern . '$#';
    }

    private function normalize(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        // enlève le slash final sauf pour "/"
        return $path !== '/' ? rtrim($path, '/') : '/';
    }
}
