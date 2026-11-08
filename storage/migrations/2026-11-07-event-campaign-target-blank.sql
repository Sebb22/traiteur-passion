ALTER TABLE event_campaigns
    ALTER COLUMN destination_target SET DEFAULT '_blank';

UPDATE event_campaigns
SET destination_target = '_blank'
WHERE internal_name = 'Noël 2026 — Carte des fêtes'
  AND deleted_at IS NULL
  AND (destination_url IS NULL OR TRIM(destination_url) = '');
