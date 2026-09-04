<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $route, array $handler): void
    {
        $this->routes['GET'][$route] = $handler;
    }

    public function post(string $route, array $handler): void
    {
        $this->routes['POST'][$route] = $handler;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $route = trim((string)($_GET['route'] ?? ''), '/');
        $handler = $this->routes[$method][$route] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '<h1>404 - Page not found</h1>';
            return;
        }

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->$action();
    }
}
