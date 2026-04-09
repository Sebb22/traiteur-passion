<?php
declare (strict_types = 1);

namespace App\Core;

final class Vite
{
    private const ENTRY              = 'resources/js/main.js';
    private const DEFAULT_DEV_SERVER = 'http://localhost:5173';

    public static function styles(): string
    {
        if (self::shouldUseDevServer()) {
            // En dev, Vite injecte le CSS via JS (HMR)
            return '';
        }

        $manifest = self::manifest();
        $tags     = [];

        if (! empty($manifest[self::ENTRY]['css'])) {
            foreach ($manifest[self::ENTRY]['css'] as $css) {
                $tags[] = '<link rel="stylesheet" href="/build/' . $css . '">';
            }
        }

        return implode(PHP_EOL, $tags);
    }

    public static function scripts(): string
    {
        if (self::shouldUseDevServer()) {
            $devServer = self::devServerUrl();

            return '<script type="module" src="' . $devServer . '/@vite/client"></script>' . PHP_EOL .
            '<script type="module" src="' . $devServer . '/' . self::ENTRY . '"></script>';
        }

        $manifest = self::manifest();
        $file     = $manifest[self::ENTRY]['file'] ?? null;

        if (! $file) {
            throw new \RuntimeException('Fichier JS introuvable dans le manifest Vite.');
        }

        return '<script type="module" src="/build/' . $file . '"></script>';
    }

    private static function manifest(): array
    {
        $path = dirname(__DIR__, 2) . '/public/build/.vite/manifest.json';

        if (! file_exists($path)) {
            throw new \RuntimeException("Vite manifest introuvable: $path");
        }

        return json_decode((string) file_get_contents($path), true);
    }

    private static function shouldUseDevServer(): bool
    {
        return self::appEnv() === 'dev' && self::isDevServerReachable(self::devServerUrl());
    }

    private static function appEnv(): string
    {
        $env = Config::get('APP_ENV', getenv('APP_ENV') ?: 'prod');
        return strtolower(trim((string) $env));
    }

    private static function devServerUrl(): string
    {
        $value = trim((string) (Config::get('VITE_DEV_SERVER_URL', getenv('VITE_DEV_SERVER_URL') ?: self::DEFAULT_DEV_SERVER)));
        return rtrim($value !== '' ? $value : self::DEFAULT_DEV_SERVER, '/');
    }

    private static function isDevServerReachable(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $host   = (string) ($parts['host'] ?? '');
        $port   = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if ($host === '' || $port <= 0) {
            return false;
        }

        $transport  = $scheme === 'https' ? 'ssl://' : '';
        $connection = @fsockopen($transport . $host, $port, $errorCode, $errorMessage, 0.2);
        if (! is_resource($connection)) {
            return false;
        }

        fclose($connection);
        return true;
    }
}
