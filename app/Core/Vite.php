<?php
declare(strict_types=1);

namespace App\Core;

final class Vite
{
    private const ENTRY = 'resources/js/main.js';

    public static function styles(): string
    {
        if (getenv('APP_ENV') === 'dev') {
            // En dev, Vite injecte le CSS via JS (HMR)
            return '';
        }

        $manifest = self::manifest();
        $tags = [];

        if (!empty($manifest[self::ENTRY]['css'])) {
            foreach ($manifest[self::ENTRY]['css'] as $css) {
                $tags[] = '<link rel="stylesheet" href="/build/' . $css . '">';
            }
        }

        return implode(PHP_EOL, $tags);
    }

    public static function scripts(): string
    {
        if (getenv('APP_ENV') === 'dev') {
            $devServer = 'http://localhost:5173';

            return
                '<script type="module" src="' . $devServer . '/@vite/client"></script>' . PHP_EOL .
                '<script type="module" src="' . $devServer . '/' . self::ENTRY . '"></script>';
        }

        $manifest = self::manifest();
        $file = $manifest[self::ENTRY]['file'] ?? null;

        if (!$file) {
            throw new \RuntimeException('Fichier JS introuvable dans le manifest Vite.');
        }

        return '<script type="module" src="/build/' . $file . '"></script>';
    }

    private static function manifest(): array
    {
        $path = dirname(__DIR__, 2) . '/public/build/manifest.json';

        if (!file_exists($path)) {
            throw new \RuntimeException("Vite manifest introuvable: $path");
        }

        return json_decode((string) file_get_contents($path), true);
    }
}