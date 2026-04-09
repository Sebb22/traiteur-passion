<?php
declare (strict_types = 1);

namespace App\Core;

final class HttpError
{
    public static function notFound(array $data = []): void
    {
        self::render(404, 'errors/404', array_merge([
            'title' => '404 — Introuvable',
        ], $data));
    }

    public static function forbidden(array $data = []): void
    {
        self::render(403, 'errors/403', array_merge([
            'title' => '403 — Accès refusé',
        ], $data));
    }

    private static function render(int $statusCode, string $view, array $data): void
    {
        http_response_code($statusCode);

        View::render($view, array_merge([
            'bodyClass'                    => 'page--error',
            'metaDescription'              => 'Page d\'erreur Traiteur Passion.',
            'metaRobots'                   => 'noindex, nofollow',
            'disableStructuredBreadcrumbs' => true,
        ], $data));
    }
}
