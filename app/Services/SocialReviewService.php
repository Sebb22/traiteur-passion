<?php
declare (strict_types = 1);

namespace App\Services;

final class SocialReviewService
{
    private array $config;
    private string $cachePath;
    private string $logPath;
    private int $cacheTtl;
    private int $requestTimeout;

    public function __construct(?array $config = null, ?string $projectRoot = null)
    {
        $root                 = $projectRoot ?? dirname(__DIR__, 2);
        $this->config         = $config ?? require $root . '/config/social_reviews.php';
        $this->cachePath      = $root . '/storage/cache/about-reviews.json';
        $this->logPath        = $root . '/storage/logs/social-reviews.log';
        $this->cacheTtl       = (int) ($this->config['cache_ttl'] ?? 21600);
        $this->requestTimeout = (int) ($this->config['request_timeout'] ?? 6);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAboutReviews(): array
    {
        $fallback = $this->fallbackCards();

        if (($this->config['enabled'] ?? true) !== true) {
            return $this->mergeSources($fallback['google'], $fallback['facebook']);
        }

        $cached = $this->readCache();
        if ($cached !== null) {
            return $cached;
        }

        $googleCards   = $fallback['google'];
        $facebookCards = $fallback['facebook'];

        try {
            $googleCards = $this->fetchGoogleReviews($fallback['google']);
        } catch (\Throwable $e) {
            $this->logError('google', $e);
        }

        try {
            $facebookCards = $this->fetchFacebookComments($fallback['facebook']);
        } catch (\Throwable $e) {
            $this->logError('facebook', $e);
        }

        $normalized = $this->mergeSources($googleCards, $facebookCards);
        $this->writeCache($normalized);

        return $normalized;
    }

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function fallbackCards(): array
    {
        $fallback = (array) ($this->config['fallback'] ?? []);

        return [
            'google'   => [$this->normalizeCard((array) ($fallback['google'] ?? []), 'google')],
            'facebook' => [$this->normalizeCard((array) ($fallback['facebook'] ?? []), 'facebook')],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $fallback
     * @return array<int,array<string,mixed>>
     */
    private function fetchGoogleReviews(array $fallback): array
    {
        $provider = (array) ($this->config['google'] ?? []);
        $apiKey   = trim((string) ($provider['api_key'] ?? ''));
        $placeId  = trim((string) ($provider['place_id'] ?? ''));
        $limit    = max(1, (int) ($provider['reviews_limit'] ?? 3));

        if ($apiKey === '' || $placeId === '') {
            return $fallback;
        }

        $url = sprintf(
            'https://places.googleapis.com/v1/places/%s?languageCode=fr',
            rawurlencode($placeId)
        );

        $payload = $this->httpGetJson($url, [
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: displayName,rating,reviews.rating,reviews.text,reviews.originalText,reviews.authorAttribution.displayName,reviews.authorAttribution.uri,reviews.publishTime,reviews.relativePublishTimeDescription',
            'Accept: application/json',
        ]);

        $reviews = isset($payload['reviews']) && is_array($payload['reviews']) ? $payload['reviews'] : [];
        $cards   = [];

        foreach ($reviews as $review) {
            if (! is_array($review)) {
                continue;
            }

            $text = $this->googleReviewText($review);
            if ($text === '') {
                continue;
            }

            $rating  = (int) round((float) ($review['rating'] ?? ($payload['rating'] ?? 5)));
            $cards[] = $this->normalizeCard([
                'provider'   => 'google',
                'aria_label' => 'Avis Google',
                'source'     => $this->sourceLabel('Google', $review['authorAttribution']['displayName'] ?? null),
                'badge'      => $this->starBadge($rating),
                'text'       => $this->excerpt($text, 220),
                'link'       => trim((string) ($review['authorAttribution']['uri'] ?? ($provider['reviews_link'] ?? ($fallback[0]['link'] ?? '')))),
                'cta'        => 'Lire l’avis →',
            ], 'google');

            if (count($cards) >= $limit) {
                break;
            }
        }

        return $cards === [] ? $fallback : $cards;
    }

    /**
     * @param array<int,array<string,mixed>> $fallback
     * @return array<int,array<string,mixed>>
     */
    private function fetchFacebookComments(array $fallback): array
    {
        $provider    = (array) ($this->config['facebook'] ?? []);
        $pageId      = trim((string) ($provider['page_id'] ?? ''));
        $accessToken = trim((string) ($provider['access_token'] ?? ''));
        $pageUrl     = trim((string) ($provider['page_url'] ?? ($fallback[0]['link'] ?? '')));
        $version     = trim((string) ($provider['graph_version'] ?? 'v22.0'));
        $limit       = max(1, (int) ($provider['comments_limit'] ?? 5));

        if ($pageId === '' || $accessToken === '') {
            return $fallback;
        }

        $url = sprintf('https://graph.facebook.com/%s/%s/posts?%s', $version, rawurlencode($pageId), http_build_query([
            'fields'       => sprintf(
                'message,permalink_url,created_time,comments.limit(%d){message,created_time,permalink_url,from{name}}',
                $limit
            ),
            'limit'        => (int) ($provider['posts_limit'] ?? 5),
            'access_token' => $accessToken,
        ]));

        $payload  = $this->httpGetJson($url, ['Accept: application/json']);
        $posts    = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];
        $comments = [];

        foreach ($posts as $post) {
            if (! is_array($post)) {
                continue;
            }

            $postComments = $post['comments']['data'] ?? null;
            if (! is_array($postComments)) {
                continue;
            }

            foreach ($postComments as $comment) {
                if (! is_array($comment)) {
                    continue;
                }

                $message = trim((string) ($comment['message'] ?? ''));
                if ($message === '') {
                    continue;
                }

                $comments[] = [
                    'message'      => $message,
                    'created_time' => (string) ($comment['created_time'] ?? ''),
                    'author'       => (string) ($comment['from']['name'] ?? ''),
                    'link'         => (string) ($comment['permalink_url'] ?? ($post['permalink_url'] ?? $pageUrl)),
                ];
            }
        }

        if ($comments === []) {
            return $fallback;
        }

        usort($comments, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_time'] ?? '')) <=> strtotime((string) ($left['created_time'] ?? ''));
        });

        $cards = [];
        foreach (array_slice($comments, 0, $limit) as $comment) {
            $cards[] = $this->normalizeCard([
                'provider'   => 'facebook',
                'aria_label' => 'Commentaire Facebook',
                'source'     => $this->sourceLabel('Facebook', $comment['author'] ?? null),
                'badge'      => '★★★★★',
                'text'       => $this->excerpt((string) ($comment['message'] ?? ''), 220),
                'link'       => trim((string) ($comment['link'] ?? $pageUrl)),
                'cta'        => 'Lire le commentaire →',
            ], 'facebook');
        }

        return $cards === [] ? $fallback : $cards;
    }

    /**
     * @param array<int,array<string,mixed>> $googleCards
     * @param array<int,array<string,mixed>> $facebookCards
     * @return array<int,array<string,mixed>>
     */
    private function mergeSources(array $googleCards, array $facebookCards): array
    {
        $displayLimit = max(2, (int) ($this->config['display_limit'] ?? 6));
        $merged       = [];
        $index        = 0;

        while (count($merged) < $displayLimit) {
            $added = false;

            if (isset($googleCards[$index])) {
                $merged[] = $googleCards[$index];
                $added    = true;
            }

            if (count($merged) >= $displayLimit) {
                break;
            }

            if (isset($facebookCards[$index])) {
                $merged[] = $facebookCards[$index];
                $added    = true;
            }

            if (! $added) {
                break;
            }

            $index++;
        }

        return array_slice($merged, 0, $displayLimit);
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    private function readCache(): ?array
    {
        if (! is_file($this->cachePath)) {
            return null;
        }

        $raw = @file_get_contents($this->cachePath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return null;
        }

        $fetchedAt = (int) ($payload['fetched_at'] ?? 0);
        if ($fetchedAt < (time() - $this->cacheTtl)) {
            return null;
        }

        $cards = isset($payload['cards']) && is_array($payload['cards']) ? $payload['cards'] : null;
        if ($cards === null) {
            return null;
        }

        $normalized = [];
        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }

            $provider     = (string) ($card['provider'] ?? '');
            $normalized[] = $this->normalizeCard($card, $provider === '' ? 'google' : $provider);
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $cards
     */
    private function writeCache(array $cards): void
    {
        $directory = dirname($this->cachePath);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return;
        }

        $payload = json_encode([
            'fetched_at' => time(),
            'cards'      => $cards,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($payload === false) {
            return;
        }

        @file_put_contents($this->cachePath, $payload, LOCK_EX);
    }

    /**
     * @param list<string> $headers
     * @return array<string,mixed>
     */
    private function httpGetJson(string $url, array $headers): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new \RuntimeException('Impossible d’initialiser cURL.');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $this->requestTimeout,
                CURLOPT_HTTPHEADER     => $headers,
            ]);

            $response = curl_exec($ch);
            $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \RuntimeException('Erreur HTTP: ' . $error);
            }

            if ($status >= 400) {
                throw new \RuntimeException('Réponse HTTP ' . $status);
            }

            return $this->decodeJson((string) $response);
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $this->requestTimeout,
                'ignore_errors' => true,
                'header'        => implode("\r\n", $headers),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Erreur HTTP via file_get_contents.');
        }

        $status = 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $matches) === 1) {
            $status = (int) $matches[1];
        }

        if ($status >= 400) {
            throw new \RuntimeException('Réponse HTTP ' . $status);
        }

        return $this->decodeJson($response);
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJson(string $json): array
    {
        $payload = json_decode($json, true);
        if (! is_array($payload)) {
            throw new \RuntimeException('Réponse JSON invalide.');
        }

        if (isset($payload['error']) && is_array($payload['error'])) {
            $message = (string) ($payload['error']['message'] ?? 'Erreur API inconnue.');
            throw new \RuntimeException($message);
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $review
     */
    private function googleReviewText(array $review): string
    {
        $candidates = [
            $review['text']['text'] ?? null,
            $review['originalText']['text'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $card
     * @return array<string,mixed>
     */
    private function normalizeCard(array $card, string $provider): array
    {
        $source = trim((string) ($card['source'] ?? ''));
        $text   = trim((string) ($card['text'] ?? ''));
        $link   = trim((string) ($card['link'] ?? ''));
        $cta    = trim((string) ($card['cta'] ?? 'Lire plus →'));

        return [
            'provider'   => $provider,
            'aria_label' => trim((string) ($card['aria_label'] ?? ('Avis ' . ucfirst($provider)))),
            'source'     => $source === '' ? ucfirst($provider) : $source,
            'badge'      => trim((string) ($card['badge'] ?? '★★★★★')),
            'text'       => $text,
            'link'       => $link,
            'cta'        => $cta,
        ];
    }

    private function excerpt(string $text, int $maxLength): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));
        $normalized = is_string($normalized) ? $normalized : trim($text);

        if (mb_strlen($normalized) <= $maxLength) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, $maxLength - 1)) . '…';
    }

    private function starBadge(int $rating): string
    {
        $rating = max(1, min(5, $rating));
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    private function sourceLabel(string $provider, $author): string
    {
        $author = trim((string) $author);
        if ($author === '') {
            return $provider;
        }

        return $provider . ' • ' . $author;
    }

    private function logError(string $provider, \Throwable $error): void
    {
        $directory = dirname($this->logPath);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s\n",
            date('c'),
            $provider,
            $error->getMessage()
        );

        @file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
