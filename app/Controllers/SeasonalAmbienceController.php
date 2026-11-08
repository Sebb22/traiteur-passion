<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\SeasonalAmbience;
use DateTimeImmutable;
use DateTimeZone;

final class SeasonalAmbienceController
{
    public function index(): void
    {
        AdminAuth::requireAuth();
        $model = new SeasonalAmbience();
        $error = null;

        try {
            $ambience = $model->getForAdmin();
        } catch (\Throwable $exception) {
            error_log('Seasonal ambience load error: ' . $exception->getMessage());
            $ambience = $model->defaults();
            $error = 'La table des ambiances saisonnières n’est pas encore disponible. Appliquez la migration SQL.';
        }

        View::render('admin/seasonal-ambience', [
            'title'    => 'Administration — Ambiances saisonnières',
            'ambience' => $ambience,
            'csrfToken' => Csrf::token(),
            'flash'    => $this->pullFlash(),
            'loadError' => $error,
        ]);
    }

    public function update(): void
    {
        AdminAuth::requireAuth();
        Csrf::requireValid();

        try {
            $data = $this->validatedPayload($_POST);
            (new SeasonalAmbience())->save($data);
            $this->pushFlash('success', 'Ambiance saisonnière enregistrée.');
        } catch (\Throwable $exception) {
            error_log('Seasonal ambience save error: ' . $exception->getMessage());
            $this->pushFlash('error', $exception->getMessage());
        }

        header('Location: /admin/ambiances-saisonnieres');
        exit;
    }

    private function validatedPayload(array $input): array
    {
        $startsAt = $this->normalizeDate($input['starts_at'] ?? '');
        $endsAt   = $this->normalizeDate($input['ends_at'] ?? '');
        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            throw new \RuntimeException('La date de fin doit être postérieure à la date de début.');
        }

        $color = trim((string) ($input['color'] ?? '#ffffff'));
        if (! preg_match('/^#[0-9a-f]{6}$/i', $color)) {
            throw new \RuntimeException('La couleur doit être un code hexadécimal valide.');
        }

        $data = [
            'enabled'         => ! empty($input['enabled']) ? 1 : 0,
            'starts_at'       => $startsAt !== null ? $startsAt->format('Y-m-d H:i:s') : '',
            'ends_at'         => $endsAt !== null ? $endsAt->format('Y-m-d H:i:s') : '',
            'snowflake_count' => $this->boundedInt($input['snowflake_count'] ?? 35, 5, 120),
            'color'           => strtolower($color),
            'opacity_min'     => $this->boundedFloat($input['opacity_min'] ?? 0.25, 0.05, 1),
            'opacity_max'     => $this->boundedFloat($input['opacity_max'] ?? 0.75, 0.05, 1),
            'speed_min'       => $this->boundedFloat($input['speed_min'] ?? 0.35, 0.05, 4),
            'speed_max'       => $this->boundedFloat($input['speed_max'] ?? 1.10, 0.05, 4),
            'wind_min'        => $this->boundedFloat($input['wind_min'] ?? -0.20, -4, 4),
            'wind_max'        => $this->boundedFloat($input['wind_max'] ?? 0.60, -4, 4),
            'mobile_enabled'  => ! empty($input['mobile_enabled']) ? 1 : 0,
        ];

        foreach (['opacity', 'speed', 'wind'] as $range) {
            if ($data[$range . '_min'] > $data[$range . '_max']) {
                throw new \RuntimeException('Les valeurs minimales doivent être inférieures ou égales aux valeurs maximales.');
            }
        }

        return $data;
    }

    private function normalizeDate($value): ?DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date   = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value, new DateTimeZone('Europe/Paris'));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \RuntimeException('Format de date invalide.');
        }

        return $date;
    }

    private function boundedInt($value, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            throw new \RuntimeException('Une valeur numérique est attendue.');
        }
        return min($max, max($min, (int) $value));
    }

    private function boundedFloat($value, float $min, float $max): float
    {
        if (! is_numeric($value)) {
            throw new \RuntimeException('Une valeur numérique est attendue.');
        }
        return min($max, max($min, (float) $value));
    }

    private function pushFlash(string $type, string $message): void
    {
        $_SESSION['admin_ambience_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_ambience_flash'] ?? null;
        unset($_SESSION['admin_ambience_flash']);
        return is_array($flash) ? $flash : null;
    }
}