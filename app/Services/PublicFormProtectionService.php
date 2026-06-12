<?php
declare (strict_types = 1);

namespace App\Services;

final class PublicFormProtectionService
{
    private const SESSION_KEY             = 'public_form_protection';
    private const TOKEN_FIELD             = '__form_token';
    private const ISSUED_AT_FIELD         = '__form_issued_at';
    private const MIN_SUBMIT_DELAY        = 3;
    private const TOKEN_TTL               = 7200;
    private const RATE_LIMIT_WINDOW       = 900;
    private const RATE_LIMIT_MAX_ATTEMPTS = 4;

    /**
     * @return array{token_field:string,token:string,issued_field:string,issued_at:int,honeypot_field:string,minimum_delay:int}
     */
    public function issue(string $formKey): array
    {
        $formState = $this->sessionFormState($formKey);
        $now       = time();

        $token         = (string) ($formState['token'] ?? '');
        $issuedAt      = (int) ($formState['issued_at'] ?? 0);
        $honeypotField = (string) ($formState['honeypot_field'] ?? '');

        if ($token === '' || $issuedAt <= 0 || ($now - $issuedAt) > self::TOKEN_TTL || $honeypotField === '') {
            $token         = bin2hex(random_bytes(16));
            $issuedAt      = $now;
            $honeypotField = '__website_' . substr(hash('sha256', $formKey . $token), 0, 10);

            $_SESSION[self::SESSION_KEY]['forms'][$formKey] = [
                'token'          => $token,
                'issued_at'      => $issuedAt,
                'honeypot_field' => $honeypotField,
            ];
        }

        return [
            'token_field'    => self::TOKEN_FIELD,
            'token'          => $token,
            'issued_field'   => self::ISSUED_AT_FIELD,
            'issued_at'      => $issuedAt,
            'honeypot_field' => $honeypotField,
            'minimum_delay'  => self::MIN_SUBMIT_DELAY,
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $server
     * @return array{success:bool,status:int,error?:string}
     */
    public function verify(string $formKey, array $post, array $server): array
    {
        $ipAddress   = $this->clientIp($server);
        $userAgent   = trim((string) ($server['HTTP_USER_AGENT'] ?? ''));
        $rateLimited = $this->recordAttempt($formKey, $ipAddress);

        if ($rateLimited) {
            $this->logBlockedAttempt($formKey, 'rate_limit', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 429,
                'error'   => 'Trop de tentatives ont été détectées. Patientez quelques minutes puis rechargez la page.',
            ];
        }

        $formState     = $this->sessionFormState($formKey);
        $expectedToken = (string) ($formState['token'] ?? '');
        $issuedAt      = (int) ($formState['issued_at'] ?? 0);
        $honeypotField = (string) ($formState['honeypot_field'] ?? '');

        if ($expectedToken === '' || $issuedAt <= 0 || $honeypotField === '') {
            $this->logBlockedAttempt($formKey, 'missing_session_state', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 422,
                'error'   => 'La vérification du formulaire a expiré. Rechargez la page puis recommencez.',
            ];
        }

        if (trim((string) ($post[$honeypotField] ?? '')) !== '') {
            $this->logBlockedAttempt($formKey, 'honeypot_filled', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 422,
                'error'   => 'La vérification du formulaire a échoué. Rechargez la page puis recommencez.',
            ];
        }

        $submittedToken    = trim((string) ($post[self::TOKEN_FIELD] ?? ''));
        $submittedIssuedAt = (int) ($post[self::ISSUED_AT_FIELD] ?? 0);
        $now               = time();

        if ($submittedToken === '' || ! hash_equals($expectedToken, $submittedToken) || $submittedIssuedAt !== $issuedAt) {
            $this->logBlockedAttempt($formKey, 'invalid_token', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 422,
                'error'   => 'La vérification du formulaire a expiré. Rechargez la page puis recommencez.',
            ];
        }

        if (($now - $issuedAt) < self::MIN_SUBMIT_DELAY) {
            $this->logBlockedAttempt($formKey, 'submitted_too_fast', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 422,
                'error'   => 'Le formulaire a été envoyé trop rapidement. Patientez quelques secondes puis recommencez.',
            ];
        }

        if (($now - $issuedAt) > self::TOKEN_TTL) {
            $this->logBlockedAttempt($formKey, 'expired_token', $ipAddress, $userAgent);

            return [
                'success' => false,
                'status'  => 422,
                'error'   => 'La vérification du formulaire a expiré. Rechargez la page puis recommencez.',
            ];
        }

        return [
            'success' => true,
            'status'  => 200,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function sessionFormState(string $formKey): array
    {
        if (! isset($_SESSION[self::SESSION_KEY]['forms'][$formKey]) || ! is_array($_SESSION[self::SESSION_KEY]['forms'][$formKey])) {
            return [];
        }

        return $_SESSION[self::SESSION_KEY]['forms'][$formKey];
    }

    private function clientIp(array $server): string
    {
        $candidates = [
            (string) ($server['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($server['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $candidate));
            foreach ($parts as $part) {
                if ($part !== '') {
                    return $part;
                }
            }
        }

        return 'unknown';
    }

    private function recordAttempt(string $formKey, string $ipAddress): bool
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $cacheFile = $cacheDir . '/public-form-rate-limit.json';
        $handle    = @fopen($cacheFile, 'c+');
        if ($handle === false) {
            return false;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return false;
            }

            $contents = stream_get_contents($handle);
            $payload  = $contents !== false && trim($contents) !== '' ? json_decode($contents, true) : [];
            if (! is_array($payload)) {
                $payload = [];
            }

            $now       = time();
            $threshold = $now - self::RATE_LIMIT_WINDOW;
            $bucketKey = hash('sha256', $formKey . '|' . $ipAddress);
            $existing  = $payload[$bucketKey] ?? [];
            $attempts  = [];

            if (is_array($existing)) {
                foreach ($existing as $attemptAt) {
                    $attemptAt = (int) $attemptAt;
                    if ($attemptAt >= $threshold) {
                        $attempts[] = $attemptAt;
                    }
                }
            }

            $attempts[]          = $now;
            $payload[$bucketKey] = $attempts;
            $payload             = $this->pruneRateLimitPayload($payload, $threshold);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($payload, JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);

            return count($attempts) > self::RATE_LIMIT_MAX_ATTEMPTS;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,list<int>>
     */
    private function pruneRateLimitPayload(array $payload, int $threshold): array
    {
        $pruned = [];

        foreach ($payload as $bucketKey => $attempts) {
            if (! is_array($attempts)) {
                continue;
            }

            $recentAttempts = [];
            foreach ($attempts as $attemptAt) {
                $attemptAt = (int) $attemptAt;
                if ($attemptAt >= $threshold) {
                    $recentAttempts[] = $attemptAt;
                }
            }

            if ($recentAttempts !== []) {
                $pruned[(string) $bucketKey] = $recentAttempts;
            }
        }

        return $pruned;
    }

    private function logBlockedAttempt(string $formKey, string $reason, string $ipAddress, string $userAgent): void
    {
        error_log(sprintf(
            'Public form blocked: form=%s reason=%s ip=%s ua=%s',
            $formKey,
            $reason,
            $ipAddress,
            $userAgent !== '' ? $userAgent : 'n/a'
        ));
    }
}
