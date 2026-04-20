<?php
declare (strict_types = 1);

namespace App\Core;

final class Url
{
    public static function resolveBaseUrl(?string $configuredUrl = null): ?string
    {
        $normalizedConfigured = self::normalizeUrl($configuredUrl);
        $requestOrigin        = self::requestOrigin();

        if ($normalizedConfigured !== null) {
            if (! self::isLocalUrl($normalizedConfigured)) {
                return $normalizedConfigured;
            }

            if ($requestOrigin === null || self::isLocalUrl($requestOrigin)) {
                return $normalizedConfigured;
            }

            return $requestOrigin;
        }

        return $requestOrigin;
    }

    public static function requestOrigin(): ?string
    {
        $host = self::requestHost();
        if ($host === null) {
            return null;
        }

        return self::requestScheme() . '://' . $host;
    }

    private static function requestScheme(): string
    {
        $forwardedProto = trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '') {
            $parts = explode(',', $forwardedProto);
            $proto = strtolower(trim((string) ($parts[0] ?? '')));
            if ($proto === 'https' || $proto === 'http') {
                return $proto;
            }
        }

        $isHttps = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

        return $isHttps ? 'https' : 'http';
    }

    private static function requestHost(): ?string
    {
        $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        if ($forwardedHost !== '') {
            $parts = explode(',', $forwardedHost);
            $host  = self::sanitizeHost((string) ($parts[0] ?? ''));
            if ($host !== null) {
                return $host;
            }
        }

        $httpHost = self::sanitizeHost((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($httpHost !== null) {
            return $httpHost;
        }

        return self::sanitizeHost((string) ($_SERVER['SERVER_NAME'] ?? ''));
    }

    private static function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) ($url ?? ''));
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host   = strtolower((string) $parts['host']);
        $port   = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path   = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $scheme . '://' . $host . $port . $path;
    }

    private static function sanitizeHost(string $host): ?string
    {
        $host = trim($host);
        if ($host === '') {
            return null;
        }

        $host = preg_replace('/[^A-Za-z0-9\.\-:\[\]]/', '', $host);
        $host = is_string($host) ? trim($host) : '';

        return $host === '' ? null : strtolower($host);
    }

    private static function isLocalUrl(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        return str_ends_with($host, '.local') || str_ends_with($host, '.test');
    }
}
