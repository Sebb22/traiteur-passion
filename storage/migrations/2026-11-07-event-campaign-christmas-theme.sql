UPDATE event_campaigns
SET theme_key = 'christmas',
    animation_key = 'fade-slide'
WHERE internal_name = 'Noël 2026 — Carte des fêtes'
  AND deleted_at IS NULL;
