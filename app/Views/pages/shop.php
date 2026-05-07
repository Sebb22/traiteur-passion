<?php
    $pickupSubmissionService = new \App\Services\ShopOrderSubmissionService();
    $sections                = isset($sections) && is_array($sections) ? $sections : [];
    $loadError               = isset($loadError) && is_string($loadError) ? $loadError : null;
    $shopPromo               = isset($shopPromo) && is_array($shopPromo) ? $shopPromo : null;

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatPrice = static function (array $item): string {
    $label = trim((string) ($item['price_label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    $cents = (int) ($item['price_cents'] ?? 0);
    return number_format($cents / 100, 2, ',', ' ') . ' €';
    };

    $normalizeStockUnit = static function ($value): string {
    return trim((string) $value) === 'g' ? 'g' : 'unit';
    };

    $formatStockQuantity = static function ($quantity, $unit) use ($normalizeStockUnit): string {
    $amount    = max(0, (int) ($quantity ?? 0));
    $stockUnit = $normalizeStockUnit($unit);
    if ($stockUnit === 'g') {
        if ($amount >= 1000) {
            $kilograms = number_format($amount / 1000, 2, ',', ' ');
            $kilograms = rtrim(rtrim($kilograms, '0'), ',');
            return $kilograms . ' kg';
        }

        return $amount . ' g';
    }

    return $amount . ' unité(s)';
    };

    $pluralize = static function (int $count, string $singular, string $plural): string {
    return $count > 1 ? $plural : $singular;
    };

    $resolveOptionUnits = static function (array $option, string $stockUnit = 'unit') use ($normalizeStockUnit): int {
    $quantity = max(1, (int) ($option['quantity'] ?? 1));
    if ($quantity > 1) {
        return $quantity;
    }

    $label = trim((string) ($option['label'] ?? ''));
    if ($label === '') {
        return 1;
    }

    if ($normalizeStockUnit($stockUnit) === 'g') {
        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*kg\b/i', $label, $matches) === 1) {
            return max(1, (int) round(((float) str_replace(',', '.', (string) ($matches[1] ?? '0'))) * 1000));
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*g\b/i', $label, $matches) === 1) {
            return max(1, (int) round((float) str_replace(',', '.', (string) ($matches[1] ?? '0'))));
        }
    }

    if (preg_match('/\b(?:lot|x)\s*(?:de\s*)?(\d+)\b/i', $label, $matches) === 1) {
        return max(1, (int) ($matches[1] ?? 1));
    }

    return 1;
    };

    $resolveSectionAnchor = static function (array $section): string {
    $slug = trim((string) ($section['slug'] ?? ''));
    if ($slug !== '') {
        return $slug;
    }

    return 'section-' . (int) ($section['id'] ?? 0);
    };

    $shopIcon = static function (string $name): string {
    static $icons = null;

    if ($icons === null) {
        $icons = [
            'artisan'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 12h8v6.5H8z" fill="currentColor" opacity=".12"/><path d="M8 12h8v6.5H8z"/><path d="M8 12c-1.2 0-2.15-.96-2.15-2.15 0-1.1.82-2 1.88-2.13.27-1.55 1.61-2.72 3.24-2.72 1.04 0 1.99.48 2.62 1.26A2.86 2.86 0 0 1 18 8.95 3.03 3.03 0 0 1 16 12"/><path d="M10.2 15.2h3.6"/></svg>',
            'location'   => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s6-5.2 6-10a6 6 0 0 0-12 0c0 4.8 6 10 6 10z" fill="currentColor" opacity=".12"/><path d="M12 21s6-5.2 6-10a6 6 0 0 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>',
            'payment'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="6.5" width="16" height="11" rx="2.5" fill="currentColor" opacity=".12"/><rect x="4" y="6.5" width="16" height="11" rx="2.5"/><path d="M4 10h16"/><path d="M7 14.5h4"/><path d="M15 14.5h2"/></svg>',
            'categories' => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 7h14"/><path d="M5 12h14"/><path d="M5 17h14"/></svg>',
            'selection'  => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 8.2h11l-1.2 6.6H9.4z" fill="currentColor" opacity=".12"/><path d="M4.5 5.5H6l2.1 9.3h9.7L19.4 8.2H8"/><path d="M9.1 17.2h8.2"/><circle cx="10" cy="19" r="1.15"/><circle cx="17.2" cy="19" r="1.15"/></svg>',
            'confirm'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="currentColor" opacity=".12"/><circle cx="12" cy="12" r="8"/><path d="m8.5 12.2 2.2 2.2 4.9-5"/></svg>',
            'alert'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4 21 20H3z" fill="currentColor" opacity=".12"/><path d="M12 4 21 20H3z"/><path d="M12 9v5"/><circle cx="12" cy="17" r=".7"/></svg>',
            'store'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.4 8.8h13.2l-1.1-3.6H6.5z" fill="currentColor" opacity=".12"/><path d="M5.4 8.8h13.2l-1.1-3.6H6.5z"/><path d="M6.2 8.8v10h11.6v-10"/><path d="M10 18.8v-4.9h4v4.9"/><path d="M5.4 8.8c0 1.18.96 2.14 2.14 2.14 1.18 0 2.14-.96 2.14-2.14 0 1.18.96 2.14 2.14 2.14 1.18 0 2.14-.96 2.14-2.14 0 1.18.96 2.14 2.14 2.14 1.18 0 2.14-.96 2.14-2.14"/></svg>',
            'dish'       => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 18h12"/><path d="M8 15a4 4 0 0 1 8 0"/><path d="M5 15h14"/><path d="M12 8v-2"/><circle cx="12" cy="6" r="1"/></svg>',
            'bag'        => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 9h10l-1 11H8z" fill="currentColor" opacity=".12"/><path d="M7 9h10l-1 11H8z"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/></svg>',
            'box'        => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4.5 20 8.5v7L12 20l-8-4.5v-7z" fill="currentColor" opacity=".12"/><path d="M12 4.5 20 8.5v7L12 20l-8-4.5v-7z"/><path d="M4 8.5 12 13l8-4.5"/><path d="M12 13v7"/></svg>',
            'clock'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="currentColor" opacity=".12"/><circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 1.8"/></svg>',
            'layers'     => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 5 8 4-8 4-8-4z" fill="currentColor" opacity=".12"/><path d="m12 5 8 4-8 4-8-4z"/><path d="m6 13 6 3 6-3"/><path d="m6 16 6 3 6-3"/></svg>',
        ];
    }

    return $icons[$name] ?? $icons['categories'];
    };

    $visibleProductsCount = 0;
    foreach ($sections as $section) {
    $visibleProductsCount += count(is_array($section['items'] ?? null) ? $section['items'] : []);
    }

    $today                = date('Y-m-d');
    $firstAvailable       = $pickupSubmissionService->firstAvailablePickupDate();
    $pickupHoursSummary   = 'Retrait boutique: mardi au vendredi 8:30 - 19:00, samedi 8:30 - 15:30. Retrait possible à partir de 2h après validation. Fermé dimanche et lundi.';
    $pickupAddressLine    = '631 rue de Compiègne';
    $pickupPostalCode     = '60162';
    $pickupCity           = 'Vignemont';
    $pickupAddressDisplay = $pickupAddressLine . ', ' . $pickupPostalCode . ' ' . $pickupCity;
    $pickupMapQuery       = rawurlencode($pickupAddressDisplay . ', France');
    $pickupMapsUrl        = 'https://www.google.com/maps/search/?api=1&query=' . $pickupMapQuery;
?>

<main class="siteMain siteContainer">
    <section class="menuSplit shopSplit" data-wheel-redirect data-wheel-target=".shopPanel"
        data-wheel-breakpoint="(min-width: 1280px) and (max-width: 1640px)">
        <div class="menuSplit__left" aria-label="Visuel de la boutique en ligne">
            <div class="menuHero shopHero">
                <img class="menuHero__img" src="/uploads/pages/shop/images/shopIllu-1200.webp" alt=""
                    aria-hidden="true">
                <div class="shopHero__content">
                    <h1 class="menuHero__title">Commande en ligne</h1>
                </div>
            </div>
        </div>

        <div class="menuSplit__right">
            <div class="menuPanel shopPanel">
                <header class="shopIntro">
                    <span class="shopIntro__eyebrow">Compiègne • retrait organisé • livraison locale</span>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="shopIntro__title">Une carte renouvelée chaque semaine, produits frais et locaux</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <div class="shopIntro__body">
                        <p class="shopIntro__copy">La carte boutique change chaque semaine pour suivre le rythme réel
                            de production et garantir la fraîcheur. Vous composez ici la demande, puis nous confirmons
                            avec vous le retrait ou la livraison possible selon votre zone.</p>
                    </div>
                    <div class="shopIntroPills" aria-label="Repères boutique">
                        <span class="shopIntroPill shopIntroPill--gold">
                            <span class="shopIntroPill__icon" aria-hidden="true">
                                <?php echo $shopIcon('artisan'); ?>
                            </span>
                            Fait maison
                        </span>
                        <span class="shopIntroPill shopIntroPill--sage">
                            <span class="shopIntroPill__icon" aria-hidden="true">
                                <?php echo $shopIcon('location'); ?>
                            </span>
                            Retrait ou livraison
                        </span>
                        <span class="shopIntroPill shopIntroPill--coral">
                            <span class="shopIntroPill__icon" aria-hidden="true">
                                <?php echo $shopIcon('payment'); ?>
                            </span>
                            Sans paiement en ligne
                        </span>
                    </div>
                    <div class="shopIntroSteps" aria-label="Parcours de commande boutique">
                        <article class="shopIntroStep shopIntroStep--gold">
                            <div class="shopIntroStep__marker">
                                <span class="shopIntroStep__index">01</span>
                                <span class="shopIntroStep__icon" aria-hidden="true">
                                    <?php echo $shopIcon('categories'); ?>
                                </span>
                            </div>
                            <div>
                                <strong class="shopIntroStep__title">Choisissez une catégorie</strong>
                                <p class="shopIntroStep__copy">Repérez vite ce qui est disponible cette semaine.</p>
                            </div>
                        </article>
                        <article class="shopIntroStep shopIntroStep--sage">
                            <div class="shopIntroStep__marker">
                                <span class="shopIntroStep__index">02</span>
                                <span class="shopIntroStep__icon" aria-hidden="true">
                                    <?php echo $shopIcon('selection'); ?>
                                </span>
                            </div>
                            <div>
                                <strong class="shopIntroStep__title">Ajoutez les bons formats</strong>
                                <p class="shopIntroStep__copy">À l’unité ou en lot selon le stock affiché.</p>
                            </div>
                        </article>
                        <article class="shopIntroStep shopIntroStep--coral">
                            <div class="shopIntroStep__marker">
                                <span class="shopIntroStep__index">03</span>
                                <span class="shopIntroStep__icon" aria-hidden="true">
                                    <?php echo $shopIcon('confirm'); ?>
                                </span>
                            </div>
                            <div>
                                <strong class="shopIntroStep__title">Confirmez sans payer</strong>
                                <p class="shopIntroStep__copy">Nous validons ensuite avec vous le retrait ou la
                                    livraison.</p>
                            </div>
                        </article>
                    </div>
                    <p class="shopIntro__note">
                        <span class="shopIntro__noteIcon" aria-hidden="true">
                            <?php echo $shopIcon('location'); ?>
                        </span>
                        <span>Carte courte, fait maison, stock revérifié à l’enregistrement. Retrait sur créneau ou
                            livraison locale selon votre adresse.</span>
                    </p>
                </header>

                <?php if ($loadError !== null): ?>
                <section class="shopNotice shopNotice--warning" aria-live="polite">
                    <span class="shopNotice__icon" aria-hidden="true">
                        <?php echo $shopIcon('alert'); ?>
                    </span>
                    <div>
                        <strong>Boutique temporairement indisponible.</strong>
                        <p><?php echo $e($loadError); ?></p>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($sections === [] && $loadError === null): ?>
                <section class="shopNotice" aria-live="polite">
                    <span class="shopNotice__icon" aria-hidden="true">
                        <?php echo $shopIcon('store'); ?>
                    </span>
                    <div>
                        <strong>La boutique n'est pas encore configurée.</strong>
                        <p>Ajoutez d'abord des catégories et des produits depuis l'administration pour ouvrir la
                            commande en
                            ligne.</p>
                    </div>
                </section>
                <?php endif; ?>

                <form class="shopOrderForm" id="shopOrderForm" data-shop-form action="/boutique-en-ligne" method="post"
                    data-stock-endpoint="/api/boutique/stock" data-submit-endpoint="/boutique-en-ligne"
                    data-promo-active="<?php echo $shopPromo !== null ? '1' : '0'; ?>"
                    data-promo-code="<?php echo $e($shopPromo['promo_code'] ?? ''); ?>"
                    data-promo-percent="<?php echo (int) ($shopPromo['discount_percent'] ?? 0); ?>"
                    data-promo-title="<?php echo $e($shopPromo['title'] ?? ''); ?>"
                    data-promo-ends-at="<?php echo $e($shopPromo['countdown_iso'] ?? ''); ?>">
                    <?php if ($sections !== []): ?>
                    <div class="shopCatalogNav">
                        <div class="shopCatalogNav__head">
                            <div class="shopCatalogNav__copy">
                                <span class="shopCatalogNav__eyebrow">Parcourir les catégories</span>
                                <p class="shopCatalogNav__title">Repérez d'abord votre catégorie, puis composez le
                                    panier sans perdre le fil de la carte.</p>
                            </div>
                            <div class="shopCatalogNav__stats" aria-label="Repères de navigation boutique">
                                <span class="shopCatalogNav__stat shopCatalogNav__stat--gold">
                                    <span class="shopCatalogNav__statIcon" aria-hidden="true">
                                        <?php echo $shopIcon('categories'); ?>
                                    </span>
                                    <?php echo count($sections); ?>
                                    catégorie<?php echo count($sections) > 1 ? 's' : ''; ?>
                                </span>
                                <span class="shopCatalogNav__stat shopCatalogNav__stat--sage">
                                    <span class="shopCatalogNav__statIcon" aria-hidden="true">
                                        <?php echo $shopIcon('dish'); ?>
                                    </span>
                                    <?php echo $visibleProductsCount; ?>
                                    produit<?php echo $visibleProductsCount > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                        </div>

                        <nav class="menuTabs shopTabs" aria-label="Catégories boutique" data-menu-tabs>
                            <?php foreach ($sections as $index => $section): ?>
                            <?php $sectionAnchor = $resolveSectionAnchor($section); ?>
                            <a href="#<?php echo $e($sectionAnchor); ?>"
                                class="menuTabs__tab <?php echo $index === 0 ? 'is-active' : ''; ?>"
                                <?php echo $index === 0 ? 'aria-current="location"' : ''; ?>><?php echo $e($section['name'] ?? 'Section'); ?></a>
                            <?php endforeach; ?>
                        </nav>

                        <button type="button" class="menuTabsShortcut" data-menu-tabs-shortcut>
                            Catégories
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="shopOrderTop">
                        <div class="shopSummaryDock" data-shop-summary-dock>
                            <button type="button" class="shopSummaryTab" data-shop-summary-toggle aria-expanded="false"
                                aria-controls="shopSummaryPanel" hidden>
                                <span class="shopSummaryTab__icon" aria-hidden="true">
                                    <?php echo $shopIcon('selection'); ?>
                                </span>
                                <span class="shopSummaryTab__main">
                                    <span class="shopSummaryTab__label">Panier</span>
                                    <strong class="shopSummaryTab__count" data-shop-summary-tab-count>0</strong>
                                    <span class="shopSummaryTab__items" data-shop-summary-count-mobile>0 article</span>
                                </span>

                                <span class="shopSummaryTab__aside">
                                    <span class="shopSummaryTab__total" data-shop-summary-tab-total>0,00 €</span>
                                    <span class="shopSummaryTab__cta">Ouvrir le panier</span>
                                </span>
                            </button>

                            <div class="shopSummaryOverlay" data-shop-summary-overlay hidden></div>

                            <aside class="shopSummary" id="shopSummaryPanel" data-shop-summary hidden>
                                <div class="shopSummary__handle" aria-hidden="true"></div>
                                <div class="shopSummary__hero">
                                    <div>
                                        <span class="shopStep">Panier</span>
                                        <h3 class="shopSectionHead__title">Votre panier</h3>
                                        <p class="shopSectionHead__hint">Le stock est revérifié au moment de la
                                            validation finale.</p>
                                    </div>
                                    <div class="shopSummary__heroAside">
                                        <div class="shopSummary__state" data-shop-summary-state>Panier vide</div>
                                        <button type="button" class="shopSummary__close" data-shop-summary-close
                                            aria-label="Fermer le panier">Fermer</button>
                                    </div>
                                </div>

                                <div class="shopSummary__metrics" aria-label="Résumé du panier">
                                    <div class="shopSummaryMetric">
                                        <span class="shopSummaryMetric__label">Quantités</span>
                                        <strong class="shopSummaryMetric__value" data-shop-summary-count>0
                                            article</strong>
                                    </div>
                                    <div class="shopSummaryMetric">
                                        <span class="shopSummaryMetric__label">Références</span>
                                        <strong class="shopSummaryMetric__value" data-shop-summary-items>0
                                            produit</strong>
                                    </div>
                                    <div class="shopSummaryMetric shopSummaryMetric--accent">
                                        <span class="shopSummaryMetric__label">Commande</span>
                                        <strong class="shopSummaryMetric__value">Sans paiement</strong>
                                    </div>
                                </div>

                                <div class="shopSummary__lines" data-shop-summary-lines>
                                    <p class="shopSummary__empty">Ajoutez des quantités pour préparer votre commande.
                                    </p>
                                </div>

                                <div class="shopSummary__totals">
                                    <div class="shopSummary__totalsMain">
                                        <span>Total estimatif</span>
                                        <small data-shop-summary-subtotal hidden></small>
                                        <small class="shopSummary__discount" data-shop-summary-discount hidden></small>
                                    </div>
                                    <strong data-shop-summary-total>0,00 €</strong>
                                </div>

                                <?php if ($shopPromo !== null): ?>
                                <div class="shopSummary__promoBox" data-shop-promo-box>
                                    <div class="shopSummary__promoHead">
                                        <div>
                                            <span class="shopStep">Promo</span>
                                            <strong><?php echo $e($shopPromo['title'] ?? 'Offre boutique'); ?></strong>
                                        </div>
                                        <span class="shopSummary__promoCountdown"
                                            data-countdown-target="<?php echo $e($shopPromo['countdown_iso'] ?? ''); ?>">Fin
                                            dans --</span>
                                    </div>
                                    <p class="shopSummary__promoCopy"><?php echo $e($shopPromo['banner_text'] ?? ''); ?>
                                    </p>
                                    <div class="shopPromoCodeRow">
                                        <label class="shopField shopField--full">
                                            <span class="shopField__label">Code promo</span>
                                            <input class="shopInput" type="text" name="promo_code" data-shop-promo-input
                                                placeholder="<?php echo $e($shopPromo['promo_code'] ?? ''); ?>">
                                        </label>
                                        <button type="button" class="btn btn--ghost shopPromoCodeRow__apply"
                                            data-shop-promo-apply>Appliquer</button>
                                    </div>
                                    <p class="shopSummary__promoHint">Code actif :
                                        <strong><?php echo $e($shopPromo['promo_code'] ?? ''); ?></strong> pour
                                        -<?php echo (int) ($shopPromo['discount_percent'] ?? 0); ?>% sur les articles
                                        boutique.
                                    </p>
                                    <p class="shopSummary__promoState" data-shop-promo-state></p>
                                </div>
                                <?php endif; ?>

                                <div class="shopSummary__cta">
                                    <button type="button" class="btn btn--primary shopSummary__submit"
                                        data-shop-go-checkout disabled>
                                        Continuer vers les informations
                                    </button>
                                    <p class="shopSummary__note">Ajoutez vos produits, vérifiez le total, puis finalisez
                                        la demande en dessous.</p>
                                </div>
                            </aside>
                        </div>

                        <section class="shopCatalog" aria-label="Produits boutique">
                            <?php foreach ($sections as $section): ?>
                            <?php $sectionAnchor = $resolveSectionAnchor($section); ?>
                            <article class="shopCatalogSection" id="<?php echo $e($sectionAnchor); ?>">
                                <header class="shopSectionHead shopSectionHead--catalog">
                                    <div>
                                        <span class="shopStep">Choisir</span>
                                        <h3 class="shopSectionHead__title">
                                            <?php echo $e($section['name'] ?? 'Section'); ?></h3>
                                        <?php if (($section['description'] ?? '') !== ''): ?>
                                        <p class="shopSectionHead__hint">
                                            <?php echo $e($section['description'] ?? ''); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </header>

                                <div class="shopGrid">
                                    <?php foreach (($section['items'] ?? []) as $item): ?>
                                    <?php
                                        $itemId        = (int) ($item['id'] ?? 0);
                                        $stockQuantity = max(0, (int) ($item['stock_quantity'] ?? 0));
                                        $stockUnit     = $normalizeStockUnit($item['stock_unit'] ?? 'unit');
                                        $options       = array_values(array_filter(
                                            is_array($item['options'] ?? null) ? $item['options'] : [],
                                            static fn(array $option): bool => ! empty($option['is_active']),
                                        ));
                                        $purchaseLines = [];
                                        if ($options !== []) {
                                            foreach ($options as $option) {
                                                $optionId    = (int) ($option['id'] ?? 0);
                                                $optionUnits = $resolveOptionUnits($option, $stockUnit);
                                                $optionStock = array_key_exists('stock_quantity', $option) && $option['stock_quantity'] !== null
                                                    ? max(0, (int) $option['stock_quantity'])
                                                    : null;
                                                $selectionSingular = $stockUnit === 'g'
                                                    ? 'format'
                                                    : ($optionUnits > 1 ? 'lot' : 'unité');
                                                $selectionPlural = $stockUnit === 'g'
                                                    ? 'formats'
                                                    : ($optionUnits > 1 ? 'lots' : 'unités');
                                                $conversionHint = $stockUnit === 'g'
                                                    ? '1 format = ' . $formatStockQuantity($optionUnits, 'g')
                                                    : ($optionUnits > 1
                                                        ? '1 lot = ' . $formatStockQuantity($optionUnits, 'unit')
                                                        : 'Vente à l’unité');
                                                $availableSelections = (int) floor($stockQuantity / max(1, $optionUnits));
                                                if ($optionStock !== null) {
                                                    $availableSelections = min($availableSelections, $optionStock);
                                                }
                                                $purchaseLines[] = [
                                                    'line_key'              => 'item-' . $itemId . '-option-' . $optionId,
                                                    'option_id'             => $optionId,
                                                    'option_label'          => trim((string) ($option['label'] ?? '')),
                                                    'option_stock_quantity' => $optionStock,
                                                    'option_units'          => $optionUnits,
                                                    'price_cents'           => (int) ($option['price_cents'] ?? 0),
                                                    'price_display'         => $formatPrice($option),
                                                    'allowed'               => $availableSelections,
                                                    'hint'                  => 'Jusqu’à ' . $availableSelections . ' ' . $pluralize($availableSelections, $selectionSingular, $selectionPlural),
                                                    'meta_hint'             => $conversionHint,
                                                    'quantity_label'        => $stockUnit === 'g'
                                                        ? 'Nombre de formats'
                                                        : ($optionUnits > 1 ? 'Nombre de lots' : 'Nombre d’unités'),
                                                    'button_label'          => 'Ajouter 1 ' . $selectionSingular,
                                                    'cart_label_singular'   => $selectionSingular,
                                                    'cart_label_plural'     => $selectionPlural,
                                                ];
                                            }
                                        } else {
                                            $purchaseLines[] = [
                                                'line_key'            => 'item-' . $itemId . '-default',
                                                'option_id'           => 0,
                                                'option_label'        => '',
                                                'option_units'        => 1,
                                                'price_cents'         => (int) ($item['price_cents'] ?? 0),
                                                'price_display'       => $formatPrice($item),
                                                'allowed'             => $stockQuantity,
                                                'hint'                => 'Jusqu’à ' . $stockQuantity . ' ' . $pluralize($stockQuantity, 'unité', 'unités'),
                                                'meta_hint'           => 'Ajout direct au panier',
                                                'quantity_label'      => 'Nombre d’unités',
                                                'button_label'        => 'Ajouter au panier',
                                                'cart_label_singular' => 'unité',
                                                'cart_label_plural'   => 'unités',
                                            ];
                                        }
                                        $cardPriceDisplay = $purchaseLines[0]['price_display'] ?? $formatPrice($item);
                                        if (count($purchaseLines) > 1) {
                                            $cardPriceDisplay = 'Dès ' . $cardPriceDisplay;
                                        }
                                        $primaryPurchaseLine = $purchaseLines[0] ?? null;
                                        $hasAvailableLine    = false;
                                        foreach ($purchaseLines as $purchaseLine) {
                                            if ((int) ($purchaseLine['allowed'] ?? 0) > 0) {
                                                $hasAvailableLine = true;
                                                break;
                                            }
                                        }
                                        $isSoldOut   = ! empty($item['is_sold_out']) || $stockQuantity <= 0 || ! $hasAvailableLine;
                                        $isLowStock  = ! empty($item['is_low_stock']);
                                        $statusLabel = $isSoldOut
                                            ? 'Rupture'
                                            : ($isLowStock ? 'Plus que ' . $formatStockQuantity($stockQuantity, $stockUnit) : $formatStockQuantity($stockQuantity, $stockUnit));
                                    ?>
                                    <article class="shopItemCard<?php echo $isSoldOut ? ' is-sold-out' : ''; ?>"
                                        data-item-id="<?php echo $itemId; ?>" data-shop-item-card>
                                        <div class="shopItemCard__media">
                                            <?php if (($item['image_path'] ?? '') !== ''): ?>
                                            <img src="<?php echo $e($item['image_path'] ?? ''); ?>"
                                                alt="<?php echo $e($item['image_alt'] ?? $item['name'] ?? ''); ?>"
                                                loading="lazy">
                                            <?php else: ?>
                                            <div class="shopItemCard__placeholder">
                                                <?php echo mb_strtoupper(mb_substr((string) ($item['name'] ?? '?'), 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="shopItemCard__body">
                                            <div class="shopItemCard__top">
                                                <h4 class="shopItemCard__title"><?php echo $e($item['name'] ?? ''); ?></h4>
                                                <span
                                                    class="shopItemCard__price"><?php echo $e($cardPriceDisplay); ?></span>
                                            </div>

                                            <div class="shopItemCard__footer">
                                                <span
                                                    class="shopStockBadge<?php echo $isLowStock ? ' is-low' : ''; ?><?php echo $isSoldOut ? ' is-sold-out' : ''; ?>"
                                                    data-shop-stock data-item-id="<?php echo $itemId; ?>">
                                                    <span><?php echo $e($statusLabel); ?></span>
                                                </span>

                                                <?php if ($options !== []): ?>
                                                <button type="button" class="btn btn--ghost shopItemCard__add"
                                                    data-shop-options-toggle data-item-id="<?php echo $itemId; ?>"
                                                    data-closed-label="Choisir un format"
                                                    data-open-label="Masquer les formats"
                                                    data-filled-label="Modifier le format"
                                                    aria-expanded="false"
                                                    aria-controls="shop-options-<?php echo $itemId; ?>"
                                                    <?php echo $isSoldOut ? 'disabled' : ''; ?>>Choisir un format</button>
                                                <?php elseif ($primaryPurchaseLine !== null): ?>
                                                <?php
                                                    $lineKey               = (string) ($primaryPurchaseLine['line_key'] ?? '');
                                                    $lineOptionId          = (int) ($primaryPurchaseLine['option_id'] ?? 0);
                                                    $lineLabel             = trim((string) ($primaryPurchaseLine['option_label'] ?? ''));
                                                    $lineUnits             = max(1, (int) ($primaryPurchaseLine['option_units'] ?? 1));
                                                    $linePrice             = (string) ($primaryPurchaseLine['price_display'] ?? '');
                                                    $linePriceCents        = (int) ($primaryPurchaseLine['price_cents'] ?? 0);
                                                    $lineAllowed           = max(0, (int) ($primaryPurchaseLine['allowed'] ?? 0));
                                                    $lineSoldOut           = $stockQuantity <= 0 || $lineAllowed <= 0;
                                                    $lineQuantityLabel     = trim((string) ($primaryPurchaseLine['quantity_label'] ?? 'Quantité'));
                                                    $lineButtonLabel       = trim((string) ($primaryPurchaseLine['button_label'] ?? 'Ajouter'));
                                                    $lineCartLabelSingular = trim((string) ($primaryPurchaseLine['cart_label_singular'] ?? 'article'));
                                                    $lineCartLabelPlural   = trim((string) ($primaryPurchaseLine['cart_label_plural'] ?? 'articles'));
                                                ?>
                                                <div class="shopItemCard__inlineOrder<?php echo $lineSoldOut ? ' is-sold-out' : ''; ?>"
                                                    data-shop-order-line data-line-key="<?php echo $e($lineKey); ?>"
                                                    data-item-id="<?php echo $itemId; ?>"
                                                    data-item-name="<?php echo $e($item['name'] ?? ''); ?>"
                                                    data-item-stock="<?php echo $stockQuantity; ?>"
                                                    data-item-stock-unit="<?php echo $e($stockUnit); ?>"
                                                    data-item-low-stock-threshold="<?php echo max(0, (int) ($item['low_stock_threshold'] ?? 0)); ?>"
                                                    data-item-price="<?php echo $e($linePrice); ?>"
                                                    data-item-price-cents="<?php echo $linePriceCents; ?>"
                                                    data-option-id="<?php echo $lineOptionId; ?>"
                                                    data-option-label="<?php echo $e($lineLabel); ?>"
                                                    data-option-stock="<?php echo array_key_exists('option_stock_quantity', $primaryPurchaseLine) && $primaryPurchaseLine['option_stock_quantity'] !== null ? max(0, (int) $primaryPurchaseLine['option_stock_quantity']) : ''; ?>"
                                                    data-option-units="<?php echo $lineUnits; ?>"
                                                    data-cart-label-singular="<?php echo $e($lineCartLabelSingular); ?>"
                                                    data-cart-label-plural="<?php echo $e($lineCartLabelPlural); ?>">
                                                    <input type="hidden" name="shop_item[<?php echo $e($lineKey); ?>]"
                                                        value="<?php echo $itemId; ?>">
                                                    <input type="hidden"
                                                        name="shop_option[<?php echo $e($lineKey); ?>]"
                                                        value="<?php echo $lineOptionId > 0 ? $lineOptionId : ''; ?>">
                                                    <input type="hidden"
                                                        name="shop_option_label[<?php echo $e($lineKey); ?>]"
                                                        value="<?php echo $e($lineLabel); ?>">
                                                    <input type="hidden"
                                                        name="shop_option_units[<?php echo $e($lineKey); ?>]"
                                                        value="<?php echo $lineUnits; ?>">

                                                    <div class="shopItemCard__actions">
                                                        <button type="button"
                                                            class="btn btn--ghost shopItemCard__add" data-shop-add
                                                            data-line-key="<?php echo $e($lineKey); ?>"
                                                            <?php echo $lineSoldOut ? 'disabled' : ''; ?>><?php echo $e($lineButtonLabel); ?></button>

                                                        <div class="shopQtyControls" data-shop-controls
                                                            data-line-key="<?php echo $e($lineKey); ?>" hidden>
                                                            <button type="button" class="shopQtyControls__btn"
                                                                data-shop-decrease
                                                                data-line-key="<?php echo $e($lineKey); ?>"
                                                                aria-label="Retirer une unité de ce produit">−</button>
                                                            <label class="shopQty shopQty--compact">
                                                                <span
                                                                    class="shopQty__label sr-only"><?php echo $e($lineQuantityLabel); ?></span>
                                                                <input class="shopQty__input" type="number"
                                                                    name="shop_quantity[<?php echo $e($lineKey); ?>]"
                                                                    min="0" max="<?php echo $lineAllowed; ?>"
                                                                    value="0"
                                                                    <?php echo $lineSoldOut ? 'disabled' : ''; ?>
                                                                    data-shop-qty
                                                                    data-line-key="<?php echo $e($lineKey); ?>">
                                                            </label>
                                                            <button type="button" class="shopQtyControls__btn"
                                                                data-shop-increase
                                                                data-line-key="<?php echo $e($lineKey); ?>"
                                                                aria-label="Ajouter une unité à ce produit">+</button>
                                                            <button type="button" class="shopQtyControls__remove"
                                                                data-shop-remove
                                                                data-line-key="<?php echo $e($lineKey); ?>">Retirer</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($options !== []): ?>
                                            <div class="shopItemCard__drawer" id="shop-options-<?php echo $itemId; ?>"
                                                data-shop-options-drawer hidden>
                                                <div class="shopItemCard__purchase">
                                                    <div class="shopPurchaseOptions">
                                                        <p class="shopPurchaseOptions__intro">
                                                            <span>Choisissez un format puis ajustez la quantité.</span>
                                                        </p>
                                                    <?php foreach ($purchaseLines as $purchaseLine): ?>
                                                    <?php
                                                        $lineKey               = (string) ($purchaseLine['line_key'] ?? '');
                                                        $lineOptionId          = (int) ($purchaseLine['option_id'] ?? 0);
                                                        $lineLabel             = trim((string) ($purchaseLine['option_label'] ?? ''));
                                                        $lineUnits             = max(1, (int) ($purchaseLine['option_units'] ?? 1));
                                                        $linePrice             = (string) ($purchaseLine['price_display'] ?? '');
                                                        $linePriceCents        = (int) ($purchaseLine['price_cents'] ?? 0);
                                                        $lineAllowed           = max(0, (int) ($purchaseLine['allowed'] ?? 0));
                                                        $lineHint              = trim((string) ($purchaseLine['hint'] ?? ''));
                                                        $lineMetaHint          = trim((string) ($purchaseLine['meta_hint'] ?? ''));
                                                        $lineSoldOut           = $stockQuantity <= 0 || $lineAllowed <= 0;
                                                        $lineTitle             = $lineLabel !== '' ? $lineLabel : 'Format standard';
                                                        $lineQuantityLabel     = trim((string) ($purchaseLine['quantity_label'] ?? 'Quantité'));
                                                        $lineButtonLabel       = trim((string) ($purchaseLine['button_label'] ?? 'Ajouter'));
                                                        $lineCartLabelSingular = trim((string) ($purchaseLine['cart_label_singular'] ?? 'article'));
                                                        $lineCartLabelPlural   = trim((string) ($purchaseLine['cart_label_plural'] ?? 'articles'));
                                                    ?>
                                                    <div class="shopPurchaseOption<?php echo $lineSoldOut ? ' is-sold-out' : ''; ?>"
                                                        data-shop-order-line data-line-key="<?php echo $e($lineKey); ?>"
                                                        data-item-id="<?php echo $itemId; ?>"
                                                        data-item-name="<?php echo $e($item['name'] ?? ''); ?>"
                                                        data-item-stock="<?php echo $stockQuantity; ?>"
                                                        data-item-stock-unit="<?php echo $e($stockUnit); ?>"
                                                        data-item-low-stock-threshold="<?php echo max(0, (int) ($item['low_stock_threshold'] ?? 0)); ?>"
                                                        data-item-price="<?php echo $e($linePrice); ?>"
                                                        data-item-price-cents="<?php echo $linePriceCents; ?>"
                                                        data-option-id="<?php echo $lineOptionId; ?>"
                                                        data-option-label="<?php echo $e($lineLabel); ?>"
                                                        data-option-stock="<?php echo array_key_exists('option_stock_quantity', $purchaseLine) && $purchaseLine['option_stock_quantity'] !== null ? max(0, (int) $purchaseLine['option_stock_quantity']) : ''; ?>"
                                                        data-option-units="<?php echo $lineUnits; ?>"
                                                        data-cart-label-singular="<?php echo $e($lineCartLabelSingular); ?>"
                                                        data-cart-label-plural="<?php echo $e($lineCartLabelPlural); ?>">
                                                        <div class="shopPurchaseOption__head">
                                                            <div class="shopPurchaseOption__copy">
                                                                <strong
                                                                    class="shopPurchaseOption__title"><?php echo $e($lineTitle); ?></strong>
                                                                <p class="shopPurchaseOption__hint"
                                                                    data-default-text="<?php echo $e($lineHint); ?>">
                                                                    <span><?php echo $e($lineHint); ?></span>
                                                                </p>
                                                                <p class="shopPurchaseOption__meta">
                                                                    <span><?php echo $e($lineMetaHint); ?></span>
                                                                </p>
                                                            </div>
                                                            <span
                                                                class="shopPurchaseOption__price"><?php echo $e($linePrice); ?></span>
                                                        </div>

                                                        <input type="hidden"
                                                            name="shop_item[<?php echo $e($lineKey); ?>]"
                                                            value="<?php echo $itemId; ?>">
                                                        <input type="hidden"
                                                            name="shop_option[<?php echo $e($lineKey); ?>]"
                                                            value="<?php echo $lineOptionId > 0 ? $lineOptionId : ''; ?>">
                                                        <input type="hidden"
                                                            name="shop_option_label[<?php echo $e($lineKey); ?>]"
                                                            value="<?php echo $e($lineLabel); ?>">
                                                        <input type="hidden"
                                                            name="shop_option_units[<?php echo $e($lineKey); ?>]"
                                                            value="<?php echo $lineUnits; ?>">

                                                        <div class="shopItemCard__actions">
                                                            <button type="button"
                                                                class="btn btn--ghost shopItemCard__add" data-shop-add
                                                                data-line-key="<?php echo $e($lineKey); ?>"
                                                                <?php echo $lineSoldOut ? 'disabled' : ''; ?>><?php echo $e($lineButtonLabel); ?></button>

                                                            <div class="shopQtyControls" data-shop-controls
                                                                data-line-key="<?php echo $e($lineKey); ?>" hidden>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-decrease
                                                                    data-line-key="<?php echo $e($lineKey); ?>"
                                                                    aria-label="Retirer une unité de cette sélection">−</button>
                                                                <label class="shopQty">
                                                                    <span
                                                                        class="shopQty__label"><?php echo $e($lineQuantityLabel); ?></span>
                                                                    <input class="shopQty__input" type="number"
                                                                        name="shop_quantity[<?php echo $e($lineKey); ?>]"
                                                                        min="0" max="<?php echo $lineAllowed; ?>"
                                                                        value="0"
                                                                        <?php echo $lineSoldOut ? 'disabled' : ''; ?>
                                                                        data-shop-qty
                                                                        data-line-key="<?php echo $e($lineKey); ?>">
                                                                </label>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-increase
                                                                    data-line-key="<?php echo $e($lineKey); ?>"
                                                                    aria-label="Ajouter une unité à cette sélection">+</button>
                                                                <button type="button" class="shopQtyControls__remove"
                                                                    data-shop-remove
                                                                    data-line-key="<?php echo $e($lineKey); ?>">Retirer</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </section>
                    </div>

                    <div class="shopOrderBottom" id="shop-checkout">
                        <div class="shopFeedback" data-shop-feedback hidden></div>

                        <section class="shopCustomerCard" data-shop-checkout>
                            <div class="shopCheckoutHeader">
                                <div class="shopSectionHead">
                                    <div>
                                        <span class="shopStep">Finaliser</span>
                                        <h3 class="shopSectionHead__title">Finaliser sans paiement</h3>
                                        <p class="shopSectionHead__hint">Deux étapes suffisent pour envoyer la demande.
                                            Nous confirmons ensuite avec vous le retrait ou la livraison.
                                        </p>
                                    </div>
                                </div>

                                <div class="shopCheckoutGuide" aria-label="Repères avant envoi">
                                    <div class="shopCheckoutGuide__item">
                                        <strong>Stock réservé</strong>
                                        <span>Les quantités choisies sont bloquées à l’enregistrement.</span>
                                    </div>
                                    <div class="shopCheckoutGuide__item">
                                        <strong>Créneau confirmé</strong>
                                        <span>Retrait validé avec vous, ou livraison si elle est possible.</span>
                                    </div>
                                    <div class="shopCheckoutGuide__item">
                                        <strong>Sans paiement</strong>
                                        <span>Cette étape sert uniquement à envoyer la demande.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="shopCheckoutLayout">
                                <div class="shopCheckoutPanel">
                                    <div class="shopCheckoutPanel__head">
                                        <h4>1. Vos coordonnées</h4>
                                        <p>Le minimum utile pour vous recontacter rapidement.</p>
                                    </div>

                                    <div class="shopFieldGrid">
                                        <label class="shopField">
                                            <span class="shopField__label">Nom complet</span>
                                            <input class="shopInput" type="text" name="name" required>
                                        </label>
                                        <label class="shopField">
                                            <span class="shopField__label">Email</span>
                                            <input class="shopInput" type="email" name="email" required>
                                        </label>
                                        <label class="shopField shopField--full">
                                            <span class="shopField__label">Téléphone</span>
                                            <input class="shopInput" type="tel" name="phone">
                                        </label>
                                    </div>
                                </div>

                                <div class="shopCheckoutPanel">
                                    <div class="shopCheckoutPanel__head">
                                        <h4>2. Retrait ou livraison</h4>
                                        <p>Choisissez un créneau souhaité. Le retrait est possible à partir de 2h
                                            après validation du panier. La livraison reste proposée selon votre
                                            adresse.</p>
                                    </div>

                                    <div class="shopFieldGrid">
                                        <div class="shopField shopField--full">
                                            <span class="shopField__label">Mode souhaité</span>
                                            <div class="shopFulfillmentChoices">
                                                <label class="shopFulfillmentChoice">
                                                    <input type="radio" name="fulfillment_method" value="pickup"
                                                        data-shop-fulfillment required>
                                                    <span class="shopFulfillmentChoice__body">
                                                        <strong>Retrait</strong>
                                                        <small>Retrait possible dès 2h</small>
                                                    </span>
                                                </label>
                                                <label class="shopFulfillmentChoice">
                                                    <input type="radio" name="fulfillment_method" value="delivery"
                                                        data-shop-fulfillment required>
                                                    <span class="shopFulfillmentChoice__body">
                                                        <strong>Livraison</strong>
                                                        <small>Dans un rayon de 20 km dès 15 €</small>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <article class="shopPickupInfo shopField--full" data-shop-pickup-location data-shop-pickup-notice hidden>
                                            <span class="shopPickupInfo__eyebrow">Retrait boutique</span>
                                            <div class="shopPickupInfo__grid">
                                                <div class="shopPickupInfo__section shopPickupInfo__section--place">
                                                    <strong class="shopPickupInfo__label">Lieu de retrait</strong>
                                                    <p class="shopPickupInfo__address"><?php echo $e($pickupAddressLine); ?><br><?php echo $e($pickupPostalCode . ' ' . $pickupCity); ?></p>
                                                    <p class="shopPickupInfo__text">Le retrait s’effectue à cette adresse, sur créneau proposé à partir de 2h après validation de la commande.</p>
                                                    <a class="btn btn--ghost shopPickupInfo__action" href="<?php echo $e($pickupMapsUrl); ?>" target="_blank" rel="noopener noreferrer">
                                                        Voir l’itinéraire
                                                    </a>
                                                </div>
                                                <div class="shopPickupInfo__section shopPickupInfo__section--hours" aria-label="Horaires de retrait boutique">
                                                    <strong class="shopPickupInfo__label">Horaires</strong>
                                                    <div class="shopPickupInfo__rows">
                                                        <div class="shopPickupInfo__row">
                                                            <span class="shopPickupInfo__day">Mardi au vendredi</span>
                                                            <span class="shopPickupInfo__time">8:30 - 19:00</span>
                                                        </div>
                                                        <div class="shopPickupInfo__row">
                                                            <span class="shopPickupInfo__day">Samedi</span>
                                                            <span class="shopPickupInfo__time">8:30 - 15:30</span>
                                                        </div>
                                                        <div class="shopPickupInfo__row shopPickupInfo__row--closed">
                                                            <span class="shopPickupInfo__day">Dimanche et lundi</span>
                                                            <span class="shopPickupInfo__time">Fermé</span>
                                                        </div>
                                                    </div>
                                                    <p class="shopPickupInfo__text shopPickupInfo__text--muted">Le jour même, seuls les créneaux respectant un délai minimum de 2h sont proposés. Samedi susceptible d’ajustement selon les prestations en cours.</p>
                                                </div>
                                            </div>
                                        </article>
                                        <label class="shopField shopField--appointment" data-shop-appointment-field hidden>
                                            <span class="shopField__label">Date souhaitée</span>
                                            <input class="shopInput shopInput--date" type="date" name="pickup_date"
                                                min="<?php echo $e($today); ?>" required data-shop-pickup-date
                                                data-shop-default-min="<?php echo $e($today); ?>"
                                                data-shop-pickup-min="<?php echo $e($firstAvailable); ?>">
                                        </label>
                                        <label class="shopField shopField--appointment" data-shop-appointment-field hidden>
                                            <span class="shopField__label">Créneau souhaité</span>
                                            <input class="shopInput shopInput--slot" type="text" name="pickup_slot"
                                                placeholder="Choisissez d’abord une date" list="shopPickupSlots"
                                                data-shop-pickup-slot autocomplete="off">
                                            <datalist id="shopPickupSlots" data-shop-pickup-slot-list></datalist>
                                            <small class="shopField__hint" data-shop-pickup-slot-hint>
                                                Créneaux de retrait disponibles du mardi au vendredi de 8:30 à 19:00 et le samedi de 8:30 à 15:30, avec un délai minimum de 2h après validation.
                                            </small>
                                        </label>
                                        <div class="shopDeliveryFields shopFieldGrid shopField--full"
                                            data-shop-delivery-panel hidden>
                                            <label class="shopField shopField--full">
                                                <span class="shopField__label">Adresse de livraison</span>
                                                <input class="shopInput" type="text" name="delivery_address"
                                                    data-shop-delivery-field
                                                    placeholder="Numéro, rue, complément d’adresse">
                                            </label>
                                            <label class="shopField">
                                                <span class="shopField__label">Code postal</span>
                                                <input class="shopInput" type="text" name="delivery_postal_code"
                                                    data-shop-delivery-field inputmode="numeric" pattern="[0-9]{5}"
                                                    maxlength="5" placeholder="60200">
                                            </label>
                                            <label class="shopField">
                                                <span class="shopField__label">Ville</span>
                                                <input class="shopInput" type="text" name="delivery_city"
                                                    data-shop-delivery-field placeholder="Compiègne">
                                            </label>
                                            <p class="shopDeliveryFields__note">La livraison reste validée par l’équipe
                                                selon l’adresse, la zone desservie et un minimum de 15 € de commande.
                                            </p>
                                        </div>
                                        <label class="shopField shopField--full">
                                            <span class="shopField__label">Message</span>
                                            <textarea class="shopTextarea" name="message" rows="4"
                                                placeholder="Précisions utiles, allergies, timing, accès..."></textarea>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="shopSummary__actions">
                                <button type="submit" class="btn btn--primary shopSummary__submit" data-shop-submit
                                    disabled>
                                    Envoyer ma demande
                                </button>
                                <p class="shopSummary__note">En cas de variation de stock entre deux clients, les
                                    quantités disponibles sont réappliquées automatiquement avant enregistrement.</p>
                            </div>
                        </section>
                    </div>
                </form>

                <?php require dirname(__DIR__) . '/partials/menu-footer.php'; ?>
            </div>
        </div>
    </section>
</main>