<?php
    /**
 * Extra partial — section "Plateaux repas"
 *
 * Reçu via le scope du foreach dans menu.php :
 * @var array    $section  Section courante avec ses items et options imbriquées
 * @var callable $e        Fonction d'échappement HTML
 */

    $renderCategoryIcon = static function (string $iconKey): string {
    $iconMap = [
        'tray' => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4.5h12v7H2z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M4.5 2.8h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M5 8h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
    ];

    return $iconMap[$iconKey] ?? '';
    };

    $buildPrefillParam = static function (string $itemSlug, string $optionKey = '__item__'): string {
    $suffix = $optionKey === '__item__' ? 'item' : $optionKey;
    $value  = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $itemSlug . '-' . $suffix));

    return 'menu_' . trim($value, '-');
    };

    // ── Config du widget de commande plateaux ──────────────────────────────────

    // Mappe chaque slug d'item à son label de ligne et ses option_key → data-param JS
    $plateauxOrderMap = [
    'plateau-classique' => [
        'row_name' => 'Plateau Classique',
        'params'   => [
            'viande'     => 'cqv',
            'poisson'    => 'cqp',
            'vegetarien' => 'cqveg',
        ],
    ],
    'plateau-gourmand'  => [
        'row_name' => 'Plateau Gourmand',
        'params'   => [
            'viande'     => 'gqv',
            'poisson'    => 'gqp',
            'vegetarien' => 'gqveg',
        ],
    ],
    ];

    // Index des items de la section par slug pour accès O(1)
    $itemsBySlug = [];
    foreach ($section['items'] as $item) {
    $itemsBySlug[$item['slug']] = $item;
    }
?>

<p class="menuSection__note">
    Plateaux repas sur devis : un format net, personnalisable et pensé pour les déjeuners d'équipe, réunions et journées de production.
</p>

<details class="plateauOrder" id="plateauOrder" data-plateau-order data-quote-category="plateaux-repas">
    <summary class="plateauOrder__summary">
        <span class="plateauOrder__summaryMain">
            <span class="plateauOrder__badge plateauOrder__badge--sand"><?php echo $renderCategoryIcon('tray'); ?></span>
            <span class="plateauOrder__summaryText">
                <span class="plateauOrder__title">Plateaux repas</span>
                <span class="plateauOrder__subtitle">Composer une première sélection en quelques clics</span>
            </span>
        </span>
        <span class="plateauOrder__chevron" aria-hidden="true">▾</span>
    </summary>

    <div class="plateauOrder__content">
        <p class="plateauOrder__intro">
            Commencez par les formats les plus demandés. Nous reprenons ensuite avec vous les quantités, les régimes spécifiques et la logistique de livraison.
        </p>

        <?php foreach ($plateauxOrderMap as $itemSlug => $rowConfig): ?>
            <?php if (! isset($itemsBySlug[$itemSlug])) {
                    continue;
                }
            ?>
            <?php
                $item          = $itemsBySlug[$itemSlug];
                $itemImagePath = trim((string) ($item['image_path'] ?? ''));
                $itemThumbPath = $itemImagePath !== '' ? str_replace('-1200.webp', '-600.webp', $itemImagePath) : '';
                // Index des options de cet item par option_key pour accès O(1)
                $optionsByKey = [];
                foreach ($item['options'] as $option) {
                    $optionsByKey[$option['option_key']] = $option;
                }
                $renderedOptionKeys = [];
            ?>
            <div class="plateauOrder__row" data-plateau-row="<?php echo $e($itemSlug); ?>">
                <div class="plateauOrder__rowHead">
                    <span class="plateauOrder__rowIdentity">
                        <span class="plateauOrder__thumb plateauOrder__thumb--sand">
                            <?php if ($itemThumbPath !== ''): ?>
                            <img src="<?php echo $e($itemThumbPath); ?>" alt="" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <span class="plateauOrder__thumbBadge"><?php echo $renderCategoryIcon('tray'); ?></span>
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
                        <?php
                            $option = $optionsByKey[$optionKey];
                            $renderedOptionKeys[] = $optionKey;
                        ?>

                        <div class="plateauOrder__optQty" data-variant="<?php echo $e($optionKey); ?>" data-param="<?php echo $e($param); ?>">
                            <span class="plateauOrder__optLabel">
                                <?php echo $e($option['label']); ?>
                                <?php if ($option['price_label'] !== ''): ?>
                                    <em><?php echo $e($option['price_label']); ?></em>
                                <?php endif; ?>
                            </span>
                            <div class="plateauOrder__qtyWrap" aria-label="Quantité <?php echo $e($rowConfig['row_name'] . ' ' . $option['label']); ?>">
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="minus" disabled aria-label="Diminuer">−</button>
                                <output class="plateauOrder__qtyVal" aria-live="polite">0</output>
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="plus" aria-label="Augmenter">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($item['options'] as $option): ?>
                        <?php
                            $optionKey = (string) ($option['option_key'] ?? '');
                            if ($optionKey === '' || in_array($optionKey, $renderedOptionKeys, true)) {
                                continue;
                            }
                            $fallbackParam = $buildPrefillParam($itemSlug, $optionKey);
                        ?>
                        <div class="plateauOrder__optQty" data-variant="<?php echo $e($optionKey); ?>" data-param="<?php echo $e($fallbackParam); ?>">
                            <span class="plateauOrder__optLabel">
                                <?php echo $e((string) ($option['label'] ?? '')); ?>
                                <?php if (trim((string) ($option['price_label'] ?? '')) !== ''): ?>
                                    <em><?php echo $e((string) $option['price_label']); ?></em>
                                <?php endif; ?>
                            </span>
                            <div class="plateauOrder__qtyWrap" aria-label="Quantité <?php echo $e($rowConfig['row_name'] . ' ' . ((string) ($option['label'] ?? 'Option'))); ?>">
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="minus" disabled aria-label="Diminuer">−</button>
                                <output class="plateauOrder__qtyVal" aria-live="polite">0</output>
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="plus" aria-label="Augmenter">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php foreach ($section['items'] as $item): ?>
            <?php
                $itemSlug = (string) ($item['slug'] ?? '');
                if ($itemSlug === '' || isset($plateauxOrderMap[$itemSlug])) {
                    continue;
                }
                $itemImagePath = trim((string) ($item['image_path'] ?? ''));
                $itemThumbPath = $itemImagePath !== '' ? str_replace('-1200.webp', '-600.webp', $itemImagePath) : '';
            ?>
            <div class="plateauOrder__row" data-plateau-row="<?php echo $e($itemSlug); ?>">
                <div class="plateauOrder__rowHead">
                    <span class="plateauOrder__rowIdentity">
                        <span class="plateauOrder__thumb plateauOrder__thumb--sand">
                            <?php if ($itemThumbPath !== ''): ?>
                            <img src="<?php echo $e($itemThumbPath); ?>" alt="" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <span class="plateauOrder__thumbBadge"><?php echo $renderCategoryIcon('tray'); ?></span>
                        </span>
                        <span class="plateauOrder__nameBlock">
                            <span class="plateauOrder__name"><?php echo $e((string) ($item['name'] ?? 'Plateau')); ?></span>
                            <?php if (trim((string) ($item['price_from_label'] ?? '')) !== ''): ?>
                            <span class="plateauOrder__meta"><?php echo $e((string) $item['price_from_label']); ?></span>
                            <?php endif; ?>
                        </span>
                    </span>
                </div>

                <div class="plateauOrder__opts plateauOrder__opts--stack">
                    <?php foreach (($item['options'] ?? []) as $option): ?>
                        <?php
                            $optionKey = (string) ($option['option_key'] ?? '');
                            if ($optionKey === '') {
                                continue;
                            }
                            $fallbackParam = $buildPrefillParam($itemSlug, $optionKey);
                        ?>
                        <div class="plateauOrder__optQty" data-variant="<?php echo $e($optionKey); ?>" data-param="<?php echo $e($fallbackParam); ?>">
                            <span class="plateauOrder__optLabel">
                                <?php echo $e((string) ($option['label'] ?? '')); ?>
                                <?php if (trim((string) ($option['price_label'] ?? '')) !== ''): ?>
                                    <em><?php echo $e((string) $option['price_label']); ?></em>
                                <?php endif; ?>
                            </span>
                            <div class="plateauOrder__qtyWrap" aria-label="Quantité <?php echo $e((string) ($item['name'] ?? 'Plateau') . ' ' . ((string) ($option['label'] ?? 'Option'))); ?>">
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="minus" disabled aria-label="Diminuer">−</button>
                                <output class="plateauOrder__qtyVal" aria-live="polite">0</output>
                                <button type="button" class="plateauOrder__qtyBtn" data-qty-action="plus" aria-label="Augmenter">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="plateauOrder__footer">
            <p class="plateauOrder__hint" aria-live="assertive"></p>
            <div class="plateauOrder__actions">
                <button type="button" class="plateauOrder__submit" data-plateau-submit>Préparer mon devis plateaux</button>
                <a class="plateauOrder__devis" href="/devis?category=plateaux-repas#quoteForm">Ouvrir le devis complet</a>
            </div>
        </div>

    </div>
</details>

<div class="menuPlateauMobileCta" aria-label="Commander un plateau repas">
    <button type="button" class="menuPlateauMobileCta__btn" data-plateau-cta>Composer ma commande →</button>
</div>
