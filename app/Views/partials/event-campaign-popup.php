<?php
    $campaign = is_array($eventCampaign ?? null) ? $eventCampaign : [];
    $escape   = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    $target    = ($campaign['destination_target'] ?? '_blank') === '_blank' ? '_blank' : '_self';
    $rel       = $target === '_blank' ? 'noopener noreferrer' : '';
    $frequency = (string) ($campaign['display_frequency'] ?? 'session');
    $theme     = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($campaign['theme_key'] ?? 'classic'))) ?: 'classic';
?>
<div class="eventCampaign eventCampaign--<?php echo $escape($theme); ?>" data-event-campaign data-campaign-id="<?php echo (int) ($campaign['id'] ?? 0); ?>"
    data-preview="<?php echo ! empty($preview) ? '1' : '0'; ?>" data-frequency="<?php echo $escape($frequency); ?>"
    data-interval-days="<?php echo (int) ($campaign['display_interval_days'] ?? 0); ?>"
    data-delay="<?php echo (int) ($campaign['appearance_delay_seconds'] ?? 3); ?>"
    data-close-overlay="<?php echo ! empty($campaign['close_on_overlay']) ? '1' : '0'; ?>"
    hidden>
    <div class="eventCampaign__overlay" data-event-campaign-overlay></div>
    <section class="eventCampaign__dialog" role="dialog" aria-modal="true"
        aria-labelledby="event-campaign-title" aria-describedby="event-campaign-description" tabindex="-1">
        <?php if (! isset($campaign['close_with_button']) || ! empty($campaign['close_with_button']) || ! empty($preview)): ?>
        <button class="eventCampaign__close" type="button" data-event-campaign-close aria-label="Fermer la fenêtre promotionnelle">
            <span aria-hidden="true">×</span>
        </button>
        <?php endif; ?>
        <div class="eventCampaign__visual">
            <?php if (! empty($campaign['image_path'])): ?>
            <picture>
                <?php if (! empty($campaign['image_mobile_path'])): ?>
                <source media="(max-width: 700px)" srcset="<?php echo $escape($campaign['image_mobile_path']); ?>">
                <?php endif; ?>
                <img src="<?php echo $escape($campaign['image_path']); ?>"
                    alt="<?php echo $escape($campaign['image_alt'] ?? $campaign['visible_title'] ?? ''); ?>"
                    width="1600" height="1200" loading="lazy">
            </picture>
            <?php endif; ?>
        </div>
        <div class="eventCampaign__content">
            <p class="eventCampaign__eyebrow">Traiteur Passion</p>
            <?php if ($theme === 'christmas'): ?>
            <div class="eventCampaign__ornament" aria-hidden="true"><span>✦</span><i></i><span>✦</span></div>
            <div class="eventCampaign__sprigs" aria-hidden="true"><span>✧</span><span>✦</span><span>✧</span></div>
            <?php endif; ?>
            <h2 id="event-campaign-title"><?php echo $escape($campaign['visible_title'] ?? ''); ?></h2>
            <div class="eventCampaign__rule" aria-hidden="true"></div>
            <p id="event-campaign-description"><?php echo nl2br($escape($campaign['short_description'] ?? '')); ?></p>
            <div class="eventCampaign__actions">
                <?php if (! empty($campaign['destination_url'])): ?>
                <a class="eventCampaign__primary" href="<?php echo $escape($campaign['destination_url']); ?>"
                    target="<?php echo $escape($target); ?>"<?php echo $rel !== '' ? ' rel="' . $rel . '"' : ''; ?>>
                    <span><?php echo $escape($campaign['primary_button_label'] ?? 'Découvrir'); ?></span><span class="eventCampaign__arrow" aria-hidden="true">→</span>
                </a>
                <?php endif; ?>
                <?php if (! empty($campaign['secondary_button_label'])): ?>
                <button class="eventCampaign__secondary" type="button" data-event-campaign-close>
                    <?php echo $escape($campaign['secondary_button_label']); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
