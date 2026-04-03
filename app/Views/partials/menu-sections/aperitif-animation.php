<?php
    /**
 * Extra partial — section "Apéritif & animation"
 *
 * Reçu via le scope du foreach dans menu.php :
 * @var array    $section  Section courante avec ses items et options imbriquées
 * @var callable $e        Fonction d'échappement HTML
 */

    $renderCategoryIcon = static function (string $iconKey): string {
    $iconMap = [
        'spark' => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 9.7 6.3 14.5 8l-4.8 1.7L8 14.5 6.3 9.7 1.5 8l4.8-1.7Z" fill="currentColor"/></svg>',
    ];

    return $iconMap[$iconKey] ?? '';
    };

    // ── Config du widget de commande apéritif ──────────────────────────────────

    // Items à afficher en lignes avec sous-options (quantité par option)
    $aperitifOptionsOrderMap = [
    'pieces-cocktail'  => [
        'row_name' => 'Pièces cocktail',
        'params'   => [
            'cocktail-5' => 'aqc5',
            'cocktail-7' => 'aqc7',
            'cocktail-9' => 'aqc9',
        ],
    ],
    'format-dinatoire' => [
        'row_name' => 'Format dînatoire',
        'params'   => [
            'dinatoire-12' => 'aqd12',
            'dinatoire-15' => 'aqd15',
        ],
    ],
    ];

    // Items affichés en ligne directe (prix issu de price_from_label, un seul sélecteur)
    $aperitifDirectOrderMap = [
    'decoupe-jambon'    => 'aqj',
    'decoupe-saumon'    => 'aqs',
    'animation-plancha' => 'aqp',
    ];

    // Index des items de la section par slug pour accès O(1)
    $itemsBySlug = [];
    foreach ($section['items'] as $item) {
    $itemsBySlug[$item['slug']] = $item;
    }
?>

<p class="menuSection__note">
    Cocktail, pièces apéritives et animations minute : une base rapide pour poser votre intention avant affinage avec notre équipe.
</p>

<details class="plateauOrder" data-aperitif-order data-quote-category="aperitif-animation">
    <summary class="plateauOrder__summary">
        <span class="plateauOrder__summaryMain">
            <span class="plateauOrder__badge plateauOrder__badge--amber"><?php echo $renderCategoryIcon('spark'); ?></span>
            <span class="plateauOrder__summaryText">
                <span class="plateauOrder__title">Apéritif & animation</span>
                <span class="plateauOrder__subtitle">Esquisser un cocktail qui vous ressemble</span>
            </span>
        </span>
        <span class="plateauOrder__chevron" aria-hidden="true">▾</span>
    </summary>

    <div class="plateauOrder__content">
        <p class="plateauOrder__intro">
            Sélectionnez quelques formats et animations, puis nous vous aidons à calibrer le rythme de service, les pièces par personne et la présence en animation.
        </p>

        <?php foreach ($aperitifOptionsOrderMap as $itemSlug => $rowConfig): ?>
            <?php if (! isset($itemsBySlug[$itemSlug])) {
                    continue;
                }
            ?>
            <?php
                $item = $itemsBySlug[$itemSlug];
                $itemImagePath = trim((string) ($item['image_path'] ?? ''));
                $itemThumbPath = $itemImagePath !== '' ? str_replace('-1200.webp', '-600.webp', $itemImagePath) : '';
                // Index des options de cet item par option_key pour accès O(1)
                $optionsByKey = [];
                foreach ($item['options'] as $option) {
                    $optionsByKey[$option['option_key']] = $option;
                }
            ?>
            <div class="plateauOrder__row">
                <div class="plateauOrder__rowHead">
                    <span class="plateauOrder__rowIdentity">
                        <span class="plateauOrder__thumb plateauOrder__thumb--amber">
                            <?php if ($itemThumbPath !== ''): ?>
                            <img src="<?php echo $e($itemThumbPath); ?>" alt="" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <span class="plateauOrder__thumbBadge"><?php echo $renderCategoryIcon('spark'); ?></span>
                        </span>
                        <span class="plateauOrder__nameBlock">
                            <span class="plateauOrder__name"><?php echo $e($rowConfig['row_name']); ?></span>
                            <?php if (trim((string) ($item['price_from_label'] ?? '')) !== ''): ?>
                            <span class="plateauOrder__meta"><?php echo $e((string) $item['price_from_label']); ?></span>
                            <?php endif; ?>
                        </span>
                    </span>
                </div>

                <div class="plateauOrder__opts plateauOrder__opts--stack">
                    <?php foreach ($rowConfig['params'] as $optionKey => $param): ?>
                        <?php if (! isset($optionsByKey[$optionKey])) {
                                continue;
                            }
                        ?>
                        <?php $option = $optionsByKey[$optionKey]; ?>

                        <div class="plateauOrder__optQty" data-param="<?php echo $e($param); ?>">
                            <span class="plateauOrder__optLabel">
                                <?php echo $e($option['label']); ?>
                                <?php if ($option['price_label'] !== ''): ?>
                                    <em><?php echo $e($option['price_label']); ?> / pers.</em>
                                <?php endif; ?>
                            </span>
                            <div class="plateauOrder__qtyWrap" aria-label="Quantité <?php echo $e($rowConfig['row_name'] . ' ' . $option['label']); ?>">
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="minus" disabled aria-label="Diminuer">−</button>
                                <output class="plateauOrder__qtyVal" aria-live="polite">0</output>
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="plus" aria-label="Augmenter">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Ligne "Animations" : items directs sans sous-options -->
        <div class="plateauOrder__row">
            <div class="plateauOrder__rowHead">
                <span class="plateauOrder__rowIdentity">
                    <span class="plateauOrder__thumb plateauOrder__thumb--amber plateauOrder__thumb--iconOnly">
                        <span class="plateauOrder__thumbBadge plateauOrder__thumbBadge--static"><?php echo $renderCategoryIcon('spark'); ?></span>
                    </span>
                    <span class="plateauOrder__nameBlock">
                        <span class="plateauOrder__name">Animations</span>
                        <span class="plateauOrder__meta">Découpes, plancha et présence minute</span>
                    </span>
                </span>
            </div>

            <div class="plateauOrder__opts plateauOrder__opts--stack">
                <?php foreach ($aperitifDirectOrderMap as $itemSlug => $param): ?>
                    <?php if (! isset($itemsBySlug[$itemSlug])) {
                            continue;
                        }
                    ?>
                    <?php $animationItem = $itemsBySlug[$itemSlug]; ?>

                    <div class="plateauOrder__optQty" data-param="<?php echo $e($param); ?>">
                        <span class="plateauOrder__optLabel">
                            <?php echo $e($animationItem['name']); ?>
                            <?php if ($animationItem['price_from_label'] !== ''): ?>
                                <em><?php echo $e($animationItem['price_from_label']); ?></em>
                            <?php endif; ?>
                        </span>
                        <div class="plateauOrder__qtyWrap" aria-label="Quantité <?php echo $e($animationItem['name']); ?>">
                            <button type="button" class="plateauOrder__qtyBtn" data-qty-action="minus" disabled aria-label="Diminuer">−</button>
                            <output class="plateauOrder__qtyVal" aria-live="polite">0</output>
                            <button type="button" class="plateauOrder__qtyBtn" data-qty-action="plus" aria-label="Augmenter">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="plateauOrder__footer">
            <p class="plateauOrder__hint" aria-live="assertive"></p>
            <div class="plateauOrder__actions">
                <button type="button" class="plateauOrder__submit" data-plateau-submit>Préparer mon devis cocktail</button>
                <a class="plateauOrder__devis" href="/devis?category=aperitif-animation#quoteForm">Ouvrir le devis complet</a>
            </div>
        </div>

    </div>
</details>
