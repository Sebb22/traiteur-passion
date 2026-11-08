<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

final class SeasonalAmbience
{
    public const ID = 1;

    public function getForAdmin(): array
    {
        $statement = Database::getInstance()->prepare(
            'SELECT * FROM seasonal_ambiences WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => self::ID]);
        $row = $statement->fetch();

        return is_array($row) ? $row : $this->defaults();
    }

    public function getPublic(): ?array
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');
        $statement = Database::getInstance()->prepare(
            'SELECT * FROM seasonal_ambiences
             WHERE id = :id
               AND enabled = 1
               AND (starts_at IS NULL OR starts_at <= :starts_at)
               AND (ends_at IS NULL OR ends_at >= :ends_at)
             LIMIT 1'
        );
        $statement->execute(['id' => self::ID, 'starts_at' => $now, 'ends_at' => $now]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function save(array $data): void
    {
        $statement = Database::getInstance()->prepare(
            'INSERT INTO seasonal_ambiences (
                id, enabled, starts_at, ends_at, snowflake_count, color,
                opacity_min, opacity_max, speed_min, speed_max,
                wind_min, wind_max, mobile_enabled
            ) VALUES (
                :id, :enabled, :starts_at, :ends_at, :snowflake_count, :color,
                :opacity_min, :opacity_max, :speed_min, :speed_max,
                :wind_min, :wind_max, :mobile_enabled
            ) ON DUPLICATE KEY UPDATE
                enabled = VALUES(enabled),
                starts_at = VALUES(starts_at),
                ends_at = VALUES(ends_at),
                snowflake_count = VALUES(snowflake_count),
                color = VALUES(color),
                opacity_min = VALUES(opacity_min),
                opacity_max = VALUES(opacity_max),
                speed_min = VALUES(speed_min),
                speed_max = VALUES(speed_max),
                wind_min = VALUES(wind_min),
                wind_max = VALUES(wind_max),
                mobile_enabled = VALUES(mobile_enabled)'
        );
        $statement->execute([
            'id'              => self::ID,
            'enabled'         => ! empty($data['enabled']) ? 1 : 0,
            'starts_at'       => $data['starts_at'] !== '' ? $data['starts_at'] : null,
            'ends_at'         => $data['ends_at'] !== '' ? $data['ends_at'] : null,
            'snowflake_count' => (int) $data['snowflake_count'],
            'color'           => (string) $data['color'],
            'opacity_min'     => (float) $data['opacity_min'],
            'opacity_max'     => (float) $data['opacity_max'],
            'speed_min'       => (float) $data['speed_min'],
            'speed_max'       => (float) $data['speed_max'],
            'wind_min'        => (float) $data['wind_min'],
            'wind_max'        => (float) $data['wind_max'],
            'mobile_enabled'  => ! empty($data['mobile_enabled']) ? 1 : 0,
        ]);
    }

    public function defaults(): array
    {
        return [
            'id'              => self::ID,
            'enabled'         => 0,
            'starts_at'       => null,
            'ends_at'         => null,
            'snowflake_count' => 35,
            'color'           => '#ffffff',
            'opacity_min'     => '0.25',
            'opacity_max'     => '0.75',
            'speed_min'       => '0.35',
            'speed_max'       => '1.10',
            'wind_min'        => '-0.20',
            'wind_max'        => '0.60',
            'mobile_enabled'  => 1,
        ];
    }
}