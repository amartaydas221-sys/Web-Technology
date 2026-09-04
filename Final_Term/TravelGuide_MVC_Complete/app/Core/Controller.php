<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $file = APP_ROOT . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/app/Views/layouts/header.php';
        require $file;
        require APP_ROOT . '/app/Views/layouts/footer.php';
        clear_old();
    }

    protected function validateRequired(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[] = $label . ' is required.';
            }
        }
        return $errors;
    }
}
