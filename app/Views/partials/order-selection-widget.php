<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */
    /** @var string $selectedSectionSlug */
    /** @var bool $limitToSelectedSection */
    /** @var string $accordionTitle */
    /** @var string $accordionSummary */
    /** @var string $accordionContext */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $buildPrefillParam = static function (string $itemSlug, string $optionKey = '__item__'): string {
    $suffix = $optionKey === '__item__' ? 'item' : $optionKey;
    $value  = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $itemSlug . '-' . $suffix));

    return 'menu_' . trim($value, '-');
    };

    $selectedSectionSlug    = isset($selectedSectionSlug) ? trim((string) $selectedSectionSlug) : '';
    $limitToSelectedSection = (bool) ($limitToSelectedSection ?? false);
    $accordionTitle         = trim((string) ($accordionTitle ?? 'Sélectionner des items du menu (optionnel)'));
    $accordionSummary       = trim((string) ($accordionSummary ?? 'Choisir parmi nos menus'));
    $accordionContext       = trim((string) ($accordionContext ?? 'Cette sélection sert à préparer votre devis. Notre équipe vous recontacte systématiquement pour confirmer le format, la livraison et les derniers détails.'));

    $renderCategoryIcon = static function (string $iconKey): string {
    $iconMap = [
        'spark'   => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 9.7 6.3 14.5 8l-4.8 1.7L8 14.5 6.3 9.7 1.5 8l4.8-1.7Z" fill="currentColor"/></svg>',
        'tray'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4.5h12v7H2z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M4.5 2.8h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M5 8h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'sunrise' => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 9a4 4 0 1 1 8 0" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 11.5h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M8 2.2v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'leaf'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M13.6 2.6c-4.1.2-7 1.7-8.6 4.4-1.2 2-.9 4.3.8 6 .2.2.6.2.8 0l6.7-6.7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.8 13.4c-1.3-.3-2.4-1-3.2-2.1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'basket'  => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 6h10l-1 6.5H4z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M5.2 6 8 2.8 10.8 6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'cuts'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 4.2a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4Zm0 4.2a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4Z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="m4.4 5.6 8.1 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="m4.4 10.4 8.1-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'flame'   => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8.2 2.2c.5 2-.7 2.8-.7 4.1 0 1 .7 1.7 1.7 1.7 1.1 0 1.9-.8 2-2 .9 1 1.4 2.2 1.4 3.5 0 2.3-1.8 4-4.4 4S3.8 11.8 3.8 9.5c0-2 1-3.7 2.8-5.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'default' => '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="3" fill="currentColor"/></svg>',
    ];

    return $iconMap[$iconKey] ?? $iconMap['default'];
    };

    $prefillParamMap = [
    'plateau-classique' => [
        'viande'     => 'cqv',
        'poisson'    => 'cqp',
        'vegetarien' => 'cqveg',
    ],
    'plateau-gourmand'  => [
        'viande'     => 'gqv',
        'poisson'    => 'gqp',
        'vegetarien' => 'gqveg',
    ],
    'pieces-cocktail'   => [
        'cocktail-5' => 'aqc5',
        'cocktail-7' => 'aqc7',
        'cocktail-9' => 'aqc9',
    ],
    'format-dinatoire'  => [
        'dinatoire-12' => 'aqd12',
        'dinatoire-15' => 'aqd15',
    ],
    'decoupe-jambon'    => [
        '__item__' => 'aqj',
    ],
    'decoupe-saumon'    => [
        '__item__' => 'aqs',
    ],
    'animation-plancha' => [
        '__item__' => 'aqp',
    ],
    ];

    $categoryVisualMap = [
    'aperitif-animation'           => ['icon' => 'spark', 'tone' => 'amber'],
    'plateaux-repas'               => ['icon' => 'tray', 'tone' => 'sand'],
    'brunch'                       => ['icon' => 'sunrise', 'tone' => 'gold'],
    'buffet-froid'                 => ['icon' => 'leaf', 'tone' => 'sage'],
    'paniers'                      => ['icon' => 'basket', 'tone' => 'terracotta'],
    'a-la-carte'                   => ['icon' => 'cuts', 'tone' => 'copper'],
    'plat-unique-animation-poelon' => ['icon' => 'flame', 'tone' => 'ember'],
    ];

    $visibleSections = [];
    foreach ($sections as $section) {
    $sectionSlug = (string) ($section['slug'] ?? '');
    if ($limitToSelectedSection && $selectedSectionSlug !== '' && $sectionSlug !== $selectedSectionSlug) {
        continue;
    }

    $visibleSections[] = $section;
    }

    if (! $limitToSelectedSection && $selectedSectionSlug !== '') {
    usort(
        $visibleSections,
        static function (array $left, array $right) use ($selectedSectionSlug): int {
            $leftSelected  = ((string) ($left['slug'] ?? '')) === $selectedSectionSlug;
            $rightSelected = ((string) ($right['slug'] ?? '')) === $selectedSectionSlug;

            if ($leftSelected === $rightSelected) {
                return 0;
            }

            return $leftSelected ? -1 : 1;
        }
    );
    }
?>

<div class="field field--full menuSelection">
    <span class="field__label"><?php echo $e($accordionTitle); ?></span>

    <?php if ($visibleSections === []): ?>
    <p class="menuSelection__empty">La carte est en cours de mise à jour.</p>
    <?php else: ?>
    <details class="menuSelection__accordion" data-order-selection
        data-selected-category="<?php echo $e($selectedSectionSlug); ?>"
        <?php echo $selectedSectionSlug !== '' ? 'open' : ''; ?>>
        <summary class="menuSelection__summary"><?php echo $e($accordionSummary); ?></summary>
        <p class="menuSelection__context"><?php echo $e($accordionContext); ?></p>

        <div class="menuSelection__content">
            <?php foreach ($visibleSections as $section): ?>
            <?php
                $sectionSlug       = (string) ($section['slug'] ?? '');
                $sectionName       = (string) ($section['name'] ?? '');
                $isSelectedSection = $selectedSectionSlug !== '' && $sectionSlug === $selectedSectionSlug;
                $categoryVisual    = $categoryVisualMap[$sectionSlug] ?? ['icon' => '•', 'tone' => 'default'];
            ?>
            <div class="menuCategory<?php echo $isSelectedSection ? ' menuCategory--selected' : ''; ?>"
                data-menu-category data-category-name="<?php echo $e($sectionName); ?>"
                data-category-slug="<?php echo $e($sectionSlug); ?>"
                data-category-icon="<?php echo $e($categoryVisual['icon']); ?>"
                data-category-tone="<?php echo $e($categoryVisual['tone']); ?>">
                <h4 class="menuCategory__title">
                    <?php echo $e($sectionName); ?>
                    <?php if ($sectionSlug === 'plateaux-repas'): ?>
                    <span class="menuCategory__notice">— livraison sous 72h</span>
                    <?php endif; ?>
                </h4>

                <?php if (trim((string) ($section['description'] ?? '')) !== ''): ?>
                <p class="menuCategory__description"><?php echo $e($section['description']); ?></p>
                <?php endif; ?>

                <?php foreach (($section['items'] ?? []) as $menuItem): ?>
                <?php
                    $itemSlug      = (string) ($menuItem['slug'] ?? '');
                    $itemName      = (string) ($menuItem['name'] ?? '');
                    $itemPrice     = trim((string) ($menuItem['price_from_label'] ?? ''));
                    $itemImagePath = trim((string) ($menuItem['image_path'] ?? ''));
                    $itemThumbPath = $itemImagePath !== ''
                        ? str_replace('-1200.webp', '-600.webp', $itemImagePath)
                        : '';
                    $itemVisualLabel = function_exists('mb_substr')
                        ? mb_strtoupper(mb_substr($itemName, 0, 1, 'UTF-8'), 'UTF-8')
                        : strtoupper(substr($itemName, 0, 1));
                    $itemOptions         = is_array($menuItem['options'] ?? null) ? $menuItem['options'] : [];
                    $itemPrefillMap      = $prefillParamMap[$itemSlug] ?? [];
                    $hasOptionQuantities = $itemOptions !== [];
                    $requiresLeadTime    = $sectionSlug === 'plateaux-repas';
                    $prefillParam        = (string) ($itemPrefillMap['__item__'] ?? $buildPrefillParam($itemSlug, '__item__'));
                ?>

                <div class="menuCheckboxQty">
                    <label class="menuCheckbox">
                        <input type="checkbox" data-order-item-toggle
                            data-category-name="<?php echo $e($sectionName); ?>"
                            data-category-slug="<?php echo $e($sectionSlug); ?>"
                            data-category-icon="<?php echo $e($categoryVisual['icon']); ?>"
                            data-category-tone="<?php echo $e($categoryVisual['tone']); ?>"
                            data-item-name="<?php echo $e($itemName); ?>" data-item-slug="<?php echo $e($itemSlug); ?>"
                            data-item-price="<?php echo $e($itemPrice); ?>"
                            data-item-image="<?php echo $e($itemThumbPath); ?>"
                            data-item-visual="<?php echo $e($itemVisualLabel); ?>"
                            data-requires-lead-time="<?php echo $requiresLeadTime ? '1' : '0'; ?>"
                            data-has-options="<?php echo $hasOptionQuantities ? '1' : '0'; ?>"
                            data-has-direct-quantity="1">
                        <span class="menuCheckbox__contentWrap">
                            <span
                                class="menuCheckbox__visual menuCheckbox__visual--<?php echo $e($categoryVisual['tone']); ?>">
                                <?php if ($itemThumbPath !== ''): ?>
                                <img src="<?php echo $e($itemThumbPath); ?>" alt="" loading="lazy" decoding="async">
                                <?php else: ?>
                                <span class="menuCheckbox__visualFallback"><?php echo $e($itemVisualLabel); ?></span>
                                <?php endif; ?>
                                <span
                                    class="menuCheckbox__visualBadge"><?php echo $renderCategoryIcon((string) $categoryVisual['icon']); ?></span>
                            </span>
                            <span class="menuCheckbox__content">
                                <span class="menuCheckbox__name"><?php echo $e($itemName); ?></span>
                                <?php if ($itemPrice !== ''): ?>
                                <span class="menuCheckbox__price"><?php echo $e($itemPrice); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </label>

                    <div class="menuVariantGrid" data-order-options-for="<?php echo $e($itemSlug); ?>"
                        style="display:none">
                        <?php if ($hasOptionQuantities): ?>
                        <?php foreach ($itemOptions as $option): ?>
                        <?php
                            $optionKey          = (string) ($option['option_key'] ?? '');
                            $optionLabel        = trim((string) ($option['label'] ?? ''));
                            $optionPrice        = trim((string) ($option['price_label'] ?? ''));
                            $optionPrefillParam = (string) ($itemPrefillMap[$optionKey] ?? $buildPrefillParam($itemSlug, $optionKey));
                        ?>
                        <div class="menuVariantQty" data-option-key="<?php echo $e($optionKey); ?>"
                            data-option-label="<?php echo $e($optionLabel); ?>"
                            data-option-price="<?php echo $e($optionPrice); ?>"
                            data-prefill-param="<?php echo $e($optionPrefillParam); ?>">
                            <span class="menuVariantQty__label">
                                <?php echo $e($optionLabel); ?>
                                <?php if ($optionPrice !== ''): ?>
                                — <?php echo $e($optionPrice); ?>
                                <?php endif; ?>
                            </span>
                            <div class="menuVariantQty__control">
                                <button type="button" class="menuQty__btn" data-action="minus"
                                    aria-label="Diminuer la quantité <?php echo $e($itemName . ' ' . $optionLabel); ?>">−</button>
                                <input type="number" class="menuQty__input" data-order-option-qty min="0" max="99"
                                    value="0" aria-label="Quantité <?php echo $e($itemName . ' ' . $optionLabel); ?>">
                                <button type="button" class="menuQty__btn" data-action="plus"
                                    aria-label="Augmenter la quantité <?php echo $e($itemName . ' ' . $optionLabel); ?>">+</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="menuVariantQty" data-simple-item="1"
                            data-option-label="<?php echo $e($itemName); ?>"
                            data-option-price="<?php echo $e($itemPrice); ?>"
                            data-prefill-param="<?php echo $e($prefillParam); ?>">
                            <span class="menuVariantQty__label">
                                <?php echo $e($itemName); ?>
                                <?php if ($itemPrice !== ''): ?>
                                — <?php echo $e($itemPrice); ?>
                                <?php endif; ?>
                            </span>
                            <div class="menuVariantQty__control">
                                <button type="button" class="menuQty__btn" data-action="minus"
                                    aria-label="Diminuer la quantité <?php echo $e($itemName); ?>">−</button>
                                <input type="number" class="menuQty__input" data-order-option-qty min="0" max="99"
                                    value="0" aria-label="Quantité <?php echo $e($itemName); ?>">
                                <button type="button" class="menuQty__btn" data-action="plus"
                                    aria-label="Augmenter la quantité <?php echo $e($itemName); ?>">+</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($requiresLeadTime): ?>
                <p class="menuCategory__delivery72h" data-delivery-warning role="alert" aria-live="polite"
                    style="display:none"></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>
</div>