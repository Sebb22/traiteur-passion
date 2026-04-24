<?php
declare (strict_types = 1);

namespace App\Services;

final class ShopPromoService
{
    private const STORAGE_FILE = __DIR__ . '/../../storage/cache/shop-promo.json';

    /**
     * @return array<string,mixed>
     */
    public function getAdminPromo(): array
    {
        $promo = $this->readPromo();

        return $promo + [
            'status'              => $this->resolveStatus($promo),
            'starts_at_input'     => $this->formatDateTimeLocal($promo['starts_at'] ?? null),
            'ends_at_input'       => $this->formatDateTimeLocal($promo['ends_at'] ?? null),
            'is_currently_active' => $this->isCurrentlyActive($promo),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getPublicPromo(): ?array
    {
        $promo = $this->readPromo();
        if (! $this->isCurrentlyActive($promo)) {
            return null;
        }

        return [
            'title'            => (string) ($promo['title'] ?? 'Offre de lancement'),
            'banner_text'      => (string) ($promo['banner_text'] ?? ''),
            'cta_label'        => (string) ($promo['cta_label'] ?? 'Voir la boutique'),
            'promo_code'       => (string) ($promo['promo_code'] ?? ''),
            'discount_percent' => (int) ($promo['discount_percent'] ?? 0),
            'starts_at'        => (string) ($promo['starts_at'] ?? ''),
            'ends_at'          => (string) ($promo['ends_at'] ?? ''),
            'countdown_iso'    => $this->toIso8601($promo['ends_at'] ?? null),
            'shop_url'         => '/boutique-en-ligne',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveFromInput(array $input): array
    {
        $enabled         = isset($input['is_enabled']);
        $title           = trim((string) ($input['title'] ?? ''));
        $bannerText      = trim((string) ($input['banner_text'] ?? ''));
        $ctaLabel        = trim((string) ($input['cta_label'] ?? ''));
        $promoCode       = $this->normalizePromoCode($input['promo_code'] ?? '');
        $discountPercent = (int) ($input['discount_percent'] ?? 0);
        $startsAt        = $this->normalizeDateTimeInput($input['starts_at'] ?? null);
        $endsAt          = $this->normalizeDateTimeInput($input['ends_at'] ?? null);

        if ($title === '') {
            $title = 'Offre de lancement';
        }

        if ($bannerText === '') {
            $bannerText = 'Profitez de notre offre boutique pendant une durée limitée.';
        }

        if ($ctaLabel === '') {
            $ctaLabel = 'Voir la boutique';
        }

        if ($enabled && $promoCode === '') {
            throw new \InvalidArgumentException('Le code promo est requis pour activer l’offre boutique.');
        }

        if ($enabled && ($discountPercent < 1 || $discountPercent > 90)) {
            throw new \InvalidArgumentException('La remise boutique doit être comprise entre 1% et 90%.');
        }

        if ($enabled && $endsAt === null) {
            throw new \InvalidArgumentException('La date de fin est requise pour activer le minuteur.');
        }

        if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) >= strtotime($endsAt)) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        $payload = [
            'is_enabled'       => $enabled,
            'title'            => $title,
            'banner_text'      => $bannerText,
            'cta_label'        => $ctaLabel,
            'promo_code'       => $promoCode,
            'discount_percent' => $discountPercent,
            'starts_at'        => $startsAt,
            'ends_at'          => $endsAt,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        $directory = dirname(self::STORAGE_FILE);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Impossible de préparer le stockage de la promo boutique.');
        }

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded) || file_put_contents(self::STORAGE_FILE, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d’enregistrer la promo boutique.');
        }

        return $this->getAdminPromo();
    }

    /**
     * @return array{valid:bool,discount_cents:int,total_cents:int,subtotal_cents:int,promo_code:?string,promo_label:?string,discount_percent:int,error:?string}
     */
    public function evaluateCheckout(int $subtotalCents, ?string $submittedCode): array
    {
        $normalizedCode = $this->normalizePromoCode($submittedCode ?? '');
        $promo          = $this->getPublicPromo();

        if ($promo === null) {
            if ($normalizedCode !== '') {
                return [
                    'valid'            => false,
                    'discount_cents'   => 0,
                    'total_cents'      => max(0, $subtotalCents),
                    'subtotal_cents'   => max(0, $subtotalCents),
                    'promo_code'       => null,
                    'promo_label'      => null,
                    'discount_percent' => 0,
                    'error'            => 'Ce code promo n’est plus disponible.',
                ];
            }

            return [
                'valid'            => true,
                'discount_cents'   => 0,
                'total_cents'      => max(0, $subtotalCents),
                'subtotal_cents'   => max(0, $subtotalCents),
                'promo_code'       => null,
                'promo_label'      => null,
                'discount_percent' => 0,
                'error'            => null,
            ];
        }

        if ($normalizedCode === '') {
            return [
                'valid'            => true,
                'discount_cents'   => 0,
                'total_cents'      => max(0, $subtotalCents),
                'subtotal_cents'   => max(0, $subtotalCents),
                'promo_code'       => null,
                'promo_label'      => null,
                'discount_percent' => (int) ($promo['discount_percent'] ?? 0),
                'error'            => null,
            ];
        }

        if ($normalizedCode !== (string) ($promo['promo_code'] ?? '')) {
            return [
                'valid'            => false,
                'discount_cents'   => 0,
                'total_cents'      => max(0, $subtotalCents),
                'subtotal_cents'   => max(0, $subtotalCents),
                'promo_code'       => null,
                'promo_label'      => null,
                'discount_percent' => (int) ($promo['discount_percent'] ?? 0),
                'error'            => 'Le code promo saisi est invalide.',
            ];
        }

        $discountPercent = max(0, min(90, (int) ($promo['discount_percent'] ?? 0)));
        $discountCents   = (int) floor(max(0, $subtotalCents) * ($discountPercent / 100));
        $totalCents      = max(0, $subtotalCents - $discountCents);

        return [
            'valid'            => true,
            'discount_cents'   => $discountCents,
            'total_cents'      => $totalCents,
            'subtotal_cents'   => max(0, $subtotalCents),
            'promo_code'       => (string) ($promo['promo_code'] ?? ''),
            'promo_label'      => (string) ($promo['title'] ?? 'Offre boutique'),
            'discount_percent' => $discountPercent,
            'error'            => null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function readPromo(): array
    {
        $defaults = [
            'is_enabled'       => false,
            'title'            => 'Offre de lancement',
            'banner_text'      => 'Profitez de notre offre boutique pendant une durée limitée.',
            'cta_label'        => 'Voir la boutique',
            'promo_code'       => '',
            'discount_percent' => 10,
            'starts_at'        => null,
            'ends_at'          => null,
            'updated_at'       => null,
        ];

        if (! is_file(self::STORAGE_FILE)) {
            return $defaults;
        }

        $raw = file_get_contents(self::STORAGE_FILE);
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return [
            'is_enabled'       => ! empty($decoded['is_enabled']),
            'title'            => trim((string) ($decoded['title'] ?? $defaults['title'])),
            'banner_text'      => trim((string) ($decoded['banner_text'] ?? $defaults['banner_text'])),
            'cta_label'        => trim((string) ($decoded['cta_label'] ?? $defaults['cta_label'])),
            'promo_code'       => $this->normalizePromoCode($decoded['promo_code'] ?? ''),
            'discount_percent' => max(0, min(90, (int) ($decoded['discount_percent'] ?? $defaults['discount_percent']))),
            'starts_at'        => $this->normalizeStoredDateTime($decoded['starts_at'] ?? null),
            'ends_at'          => $this->normalizeStoredDateTime($decoded['ends_at'] ?? null),
            'updated_at'       => $this->normalizeStoredDateTime($decoded['updated_at'] ?? null),
        ];
    }

    /**
     * @param array<string,mixed> $promo
     */
    private function isCurrentlyActive(array $promo): bool
    {
        if (empty($promo['is_enabled'])) {
            return false;
        }

        $nowTs = time();
        $start = $promo['starts_at'] ?? null;
        $end   = $promo['ends_at'] ?? null;

        if (is_string($start) && $start !== '' && $nowTs < (int) strtotime($start)) {
            return false;
        }

        if (! is_string($end) || $end === '') {
            return false;
        }

        return $nowTs < (int) strtotime($end);
    }

    /**
     * @param array<string,mixed> $promo
     */
    private function resolveStatus(array $promo): string
    {
        if (empty($promo['is_enabled'])) {
            return 'inactive';
        }

        $nowTs = time();
        $start = $promo['starts_at'] ?? null;
        $end   = $promo['ends_at'] ?? null;

        if (is_string($start) && $start !== '' && $nowTs < (int) strtotime($start)) {
            return 'scheduled';
        }

        if (is_string($end) && $end !== '' && $nowTs >= (int) strtotime($end)) {
            return 'expired';
        }

        return 'active';
    }

    private function normalizePromoCode($value): string
    {
        $normalized = strtoupper(trim((string) ($value ?? '')));
        $normalized = preg_replace('/[^A-Z0-9_-]+/', '', $normalized) ?? '';

        return substr($normalized, 0, 40);
    }

    private function normalizeDateTimeInput($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            $dateTime = \DateTimeImmutable::createFromFormat($format, $value);
            if ($dateTime instanceof \DateTimeImmutable) {
                return $dateTime->format('Y-m-d H:i:s');
            }
        }

        throw new \InvalidArgumentException('Le format des dates promo est invalide.');
    }

    private function normalizeStoredDateTime($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function formatDateTimeLocal($value): string
    {
        $value = $this->normalizeStoredDateTime($value);
        if ($value === null) {
            return '';
        }

        return date('Y-m-d\TH:i', (int) strtotime($value));
    }

    private function toIso8601($value): string
    {
        $value = $this->normalizeStoredDateTime($value);
        if ($value === null) {
            return '';
        }

        return date(DATE_ATOM, (int) strtotime($value));
    }
}
