<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

final class EventCampaign
{
    public const FREQUENCIES = ['session', 'days', 'visit'];
    public const SCOPES      = ['homepage', 'all'];

    public function getAllForAdmin(): array
    {
        $statement = Database::getInstance()->query(
            'SELECT * FROM event_campaigns
             WHERE deleted_at IS NULL
             ORDER BY created_at DESC, id DESC'
        );

        $campaigns = $statement->fetchAll();
        foreach ($campaigns as &$campaign) {
            $campaign['computed_status'] = $this->getStatus($campaign);
        }

        return $campaigns;
    }

    public function findForAdmin(int $id): ?array
    {
        $statement = Database::getInstance()->prepare(
            'SELECT * FROM event_campaigns WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $campaign = $statement->fetch();

        return is_array($campaign) ? $campaign : null;
    }

    public function create(array $data): int
    {
        $statement = Database::getInstance()->prepare(
            'INSERT INTO event_campaigns (
                internal_name, visible_title, short_description, image_path, image_mobile_path,
                image_alt, primary_button_label, destination_url, destination_target,
                secondary_button_label, starts_at, ends_at, manual_enabled, display_frequency,
                display_interval_days, display_scope, appearance_delay_seconds, close_with_button,
                close_on_overlay, theme_key, animation_key
            ) VALUES (
                :internal_name, :visible_title, :short_description, :image_path, :image_mobile_path,
                :image_alt, :primary_button_label, :destination_url, :destination_target,
                :secondary_button_label, :starts_at, :ends_at, :manual_enabled, :display_frequency,
                :display_interval_days, :display_scope, :appearance_delay_seconds, :close_with_button,
                :close_on_overlay, :theme_key, :animation_key
            )'
        );
        $statement->execute($this->bindings($data));

        return (int) Database::getInstance()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings       = $this->bindings($data);
        $bindings['id'] = $id;
        $statement      = Database::getInstance()->prepare(
            'UPDATE event_campaigns SET
                internal_name = :internal_name,
                visible_title = :visible_title,
                short_description = :short_description,
                image_path = :image_path,
                image_mobile_path = :image_mobile_path,
                image_alt = :image_alt,
                primary_button_label = :primary_button_label,
                destination_url = :destination_url,
                destination_target = :destination_target,
                secondary_button_label = :secondary_button_label,
                starts_at = :starts_at,
                ends_at = :ends_at,
                manual_enabled = :manual_enabled,
                display_frequency = :display_frequency,
                display_interval_days = :display_interval_days,
                display_scope = :display_scope,
                appearance_delay_seconds = :appearance_delay_seconds,
                close_with_button = :close_with_button,
                close_on_overlay = :close_on_overlay,
                theme_key = :theme_key,
                animation_key = :animation_key
             WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->execute($bindings);
    }

    public function toggle(int $id, bool $enabled): void
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($enabled) {
                $disableOthers = $pdo->prepare(
                    'UPDATE event_campaigns SET manual_enabled = 0
                     WHERE id <> :id AND deleted_at IS NULL'
                );
                $disableOthers->execute(['id' => $id]);
            }

            $statement = $pdo->prepare(
                'UPDATE event_campaigns SET manual_enabled = :enabled
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $statement->execute(['enabled' => $enabled ? 1 : 0, 'id' => $id]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function softDelete(int $id): void
    {
        $statement = Database::getInstance()->prepare(
            'UPDATE event_campaigns SET deleted_at = :deleted_at, manual_enabled = 0
             WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->execute([
            'deleted_at' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'),
            'id'         => $id,
        ]);
    }

    public function getPublicCampaign(string $path): ?array
    {
        $now            = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');
        $scopeCondition = $path === '/' ? "display_scope IN ('homepage', 'all')" : "display_scope = 'all'";
        $statement      = Database::getInstance()->prepare(
            "SELECT * FROM event_campaigns
             WHERE deleted_at IS NULL
               AND manual_enabled = 1
                               AND (starts_at IS NULL OR starts_at <= :starts_now)
                               AND (ends_at IS NULL OR ends_at >= :ends_now)
               AND {$scopeCondition}
             ORDER BY starts_at DESC, id DESC
             LIMIT 1"
        );
        $statement->execute(['starts_now' => $now, 'ends_now' => $now]);
        $campaign = $statement->fetch();

        return is_array($campaign) ? $campaign : null;
    }

    public function getStatus(array $campaign): string
    {
        $timezone = new DateTimeZone('Europe/Paris');
        $now      = new DateTimeImmutable('now', $timezone);
        $startsAt = $this->dateValue($campaign['starts_at'] ?? null, $timezone);
        $endsAt   = $this->dateValue($campaign['ends_at'] ?? null, $timezone);

        if ($endsAt !== null && $endsAt < $now) {
            return 'ended';
        }
        if ((int) ($campaign['manual_enabled'] ?? 0) !== 1) {
            return 'draft';
        }
        if ($startsAt !== null && $startsAt > $now) {
            return 'scheduled';
        }

        return 'active';
    }

    private function bindings(array $data): array
    {
        return [
            'internal_name'            => (string) ($data['internal_name'] ?? ''),
            'visible_title'            => (string) ($data['visible_title'] ?? ''),
            'short_description'        => (string) ($data['short_description'] ?? ''),
            'image_path'               => $data['image_path'] !== '' ? $data['image_path'] : null,
            'image_mobile_path'        => $data['image_mobile_path'] !== '' ? $data['image_mobile_path'] : null,
            'image_alt'                => $data['image_alt'] !== '' ? $data['image_alt'] : null,
            'primary_button_label'     => (string) ($data['primary_button_label'] ?? ''),
            'destination_url'          => $data['destination_url'] !== '' ? $data['destination_url'] : null,
            'destination_target'       => (string) ($data['destination_target'] ?? '_blank'),
            'secondary_button_label'   => $data['secondary_button_label'] !== '' ? $data['secondary_button_label'] : null,
            'starts_at'                => $data['starts_at'] !== '' ? $data['starts_at'] : null,
            'ends_at'                  => $data['ends_at'] !== '' ? $data['ends_at'] : null,
            'manual_enabled'           => ! empty($data['manual_enabled']) ? 1 : 0,
            'display_frequency'        => (string) ($data['display_frequency'] ?? 'session'),
            'display_interval_days'    => $data['display_interval_days'] !== '' ? (int) $data['display_interval_days'] : null,
            'display_scope'            => (string) ($data['display_scope'] ?? 'homepage'),
            'appearance_delay_seconds' => (int) ($data['appearance_delay_seconds'] ?? 3),
            'close_with_button'        => ! empty($data['close_with_button']) ? 1 : 0,
            'close_on_overlay'         => ! empty($data['close_on_overlay']) ? 1 : 0,
            'theme_key'                => (string) ($data['theme_key'] ?? 'classic'),
            'animation_key'            => (string) ($data['animation_key'] ?? 'fade-slide'),
        ];
    }

    private function dateValue($value, DateTimeZone $timezone): ?DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
