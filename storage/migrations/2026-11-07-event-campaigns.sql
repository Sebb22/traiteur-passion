CREATE TABLE IF NOT EXISTS event_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    internal_name VARCHAR(180) NOT NULL,
    visible_title VARCHAR(180) NOT NULL,
    short_description TEXT NOT NULL,
    image_path VARCHAR(500) NULL,
    image_mobile_path VARCHAR(500) NULL,
    image_alt VARCHAR(255) NULL,
    primary_button_label VARCHAR(120) NOT NULL,
    destination_url VARCHAR(1000) NULL,
    destination_target ENUM('_self', '_blank') NOT NULL DEFAULT '_blank',
    secondary_button_label VARCHAR(120) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    manual_enabled TINYINT(1) NOT NULL DEFAULT 0,
    display_frequency VARCHAR(32) NOT NULL DEFAULT 'session',
    display_interval_days SMALLINT UNSIGNED NULL,
    display_scope VARCHAR(32) NOT NULL DEFAULT 'homepage',
    appearance_delay_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    close_with_button TINYINT(1) NOT NULL DEFAULT 1,
    close_on_overlay TINYINT(1) NOT NULL DEFAULT 1,
    theme_key VARCHAR(64) NOT NULL DEFAULT 'classic',
    animation_key VARCHAR(64) NOT NULL DEFAULT 'fade-slide',
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_campaigns_schedule (manual_enabled, starts_at, ends_at),
    INDEX idx_event_campaigns_deleted_at (deleted_at),
    INDEX idx_event_campaigns_scope (display_scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_campaigns (
    internal_name,
    visible_title,
    short_description,
    primary_button_label,
    secondary_button_label,
    display_frequency,
    display_scope,
    manual_enabled,
    theme_key,
    animation_key
)
SELECT
    'Noël 2026 — Carte des fêtes',
    'Les fêtes se savourent ensemble.',
    'Découvrez notre carte spécialement imaginée pour les fêtes de fin d’année. Des créations gourmandes pour partager des moments d’exception.',
    'Découvrir la carte des fêtes',
    'Continuer ma visite',
    'session',
    'homepage',
    0,
    'classic',
    'fade-slide'
WHERE NOT EXISTS (
    SELECT 1 FROM event_campaigns WHERE internal_name = 'Noël 2026 — Carte des fêtes' AND deleted_at IS NULL
);
