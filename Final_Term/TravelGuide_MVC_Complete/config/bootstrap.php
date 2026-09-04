<?php
require_once __DIR__ . '/app.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\Core\\' => APP_ROOT . '/app/Core/',
        'App\\Controllers\\' => APP_ROOT . '/app/Controllers/',
        'App\\Models\\' => APP_ROOT . '/app/Models/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }
});

require_once APP_ROOT . '/app/helpers.php';
