<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $base = dirname(__DIR__) . '/Views';
        $viewFile = $base . '/' . $view . '.php';
        $layoutFile = $base . '/layouts/main.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vue introuvable: $viewFile");
        }

        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout introuvable: $layoutFile");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }
}