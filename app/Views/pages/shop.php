<?php
    $sections  = isset($sections) && is_array($sections) ? $sections : [];
    $loadError = isset($loadError) && is_string($loadError) ? $loadError : null;
    $shopPromo = isset($shopPromo) && is_array($shopPromo) ? $shopPromo : null;

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
            'artisan'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 14h12c0 3.05-2.69 5.5-6 5.5S6 17.05 6 14Z" fill="currentColor" stroke="none" opacity=".14"/><path d="M6 14h12c0 3.05-2.69 5.5-6 5.5S6 17.05 6 14Z"/><path d="M8.2 13.7v-1.1c0-1.82 1.7-3.3 3.8-3.3s3.8 1.48 3.8 3.3v1.1"/><path d="M8.1 8.6c0-1.07.84-1.95 1.88-1.95.67 0 1.16.3 1.52.77.38-.85 1.17-1.42 2.17-1.42 1.38 0 2.5 1.1 2.5 2.46 0 .2-.03.39-.08.57"/><circle cx="17.8" cy="6.4" r="1.15" fill="currentColor" stroke="none" opacity=".92"/></svg>',
            'location'   => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 19.9s4.9-3.63 4.9-8.27a4.9 4.9 0 1 0-9.8 0c0 4.64 4.9 8.27 4.9 8.27Z" fill="currentColor" stroke="none" opacity=".14"/><path d="M12 19.9s4.9-3.63 4.9-8.27a4.9 4.9 0 1 0-9.8 0c0 4.64 4.9 8.27 4.9 8.27Z"/><circle cx="12" cy="11.55" r="1.7" fill="currentColor" stroke="none" opacity=".92"/><path d="M15.9 16.4c1.7.22 2.9.8 2.9 1.48 0 .88-3.04 1.6-6.8 1.6s-6.8-.72-6.8-1.6c0-.67 1.1-1.24 2.69-1.46"/><path d="M18.1 8.7h2.2"/><path d="M19.2 7.6v2.2"/></svg>',
            'payment'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4.4" y="7.1" width="15.2" height="9.8" rx="2.7" fill="currentColor" stroke="none" opacity=".14"/><rect x="4.4" y="7.1" width="15.2" height="9.8" rx="2.7"/><path d="M4.4 10.45h15.2"/><path d="M8.1 13.8h2.9"/><circle cx="18.15" cy="7.35" r="2.45" fill="currentColor" stroke="none" opacity=".92"/><path d="m17.15 6.35 2 2" stroke="#120f0b"/><path d="m19.15 6.35-2 2" stroke="#120f0b"/></svg>',
            'categories' => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4.6" y="4.6" width="6.2" height="6.2" rx="1.6" fill="currentColor" stroke="none" opacity=".16"/><rect x="13.2" y="4.6" width="6.2" height="6.2" rx="1.6" fill="currentColor" stroke="none" opacity=".16"/><rect x="4.6" y="13.2" width="6.2" height="6.2" rx="1.6" fill="currentColor" stroke="none" opacity=".16"/><rect x="13.2" y="13.2" width="6.2" height="6.2" rx="1.6" fill="currentColor" stroke="none" opacity=".84"/><rect x="4.6" y="4.6" width="6.2" height="6.2" rx="1.6"/><rect x="13.2" y="4.6" width="6.2" height="6.2" rx="1.6"/><rect x="4.6" y="13.2" width="6.2" height="6.2" rx="1.6"/><rect x="13.2" y="13.2" width="6.2" height="6.2" rx="1.6"/></svg>',
            'selection'  => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4.5" y="5.5" width="11.5" height="13" rx="2.4" fill="currentColor" stroke="none" opacity=".12"/><rect x="4.5" y="5.5" width="11.5" height="13" rx="2.4"/><path d="m7.2 9.4 1 1 1.8-1.9"/><path d="M11.4 9.9h2.2"/><path d="m7.2 13.3 1 1 1.8-1.9"/><path d="M11.4 13.8h2.2"/><circle cx="17.9" cy="15.8" r="3.1" fill="currentColor" stroke="none" opacity=".9"/><path d="M17.9 14.2v3.2" stroke="#120f0b"/><path d="M16.3 15.8h3.2" stroke="#120f0b"/></svg>',
            'confirm'    => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4.2 18.2 7v4.85c0 4.05-2.53 7.46-6.2 8.95-3.67-1.49-6.2-4.9-6.2-8.95V7L12 4.2Z"/><path d="m9.35 11.95 1.75 1.75 3.55-3.75"/><circle cx="17.9" cy="8.1" r="1" fill="currentColor" stroke="none" opacity=".9"/></svg>',
            'alert'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4.5 20 19H4l8-14.5Z"/><path d="M12 9v4.5"/><circle cx="12" cy="16.5" r=".75"/></svg>',
            'store'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 10.5 6.4 6h11.2l1.4 4.5"/><path d="M6 10.5V18h12v-7.5"/><path d="M4.75 10.5h14.5"/><path d="M9.5 18v-4h5v4"/></svg>',
            'dish'       => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 12a4.5 4.5 0 0 1 9 0"/><path d="M4.5 13h15"/><path d="M7 16.5h10"/><path d="M10 19h4"/></svg>',
            'bag'        => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.1 9V8.1a3.9 3.9 0 0 1 7.8 0V9"/><path d="M6 9h12l-1 10H7L6 9Z" fill="currentColor" stroke="none" opacity=".14"/><path d="M6 9h12l-1 10H7L6 9Z"/><path d="M9.5 12.9h5"/><circle cx="18.1" cy="7.4" r="2.5" fill="currentColor" stroke="none" opacity=".92"/><path d="M18.1 6.15v2.5" stroke="#120f0b"/><path d="M16.85 7.4h2.5" stroke="#120f0b"/></svg>',
            'box'        => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4.5 19 8l-7 3.5L5 8l7-3.5Z"/><path d="M5 8v8l7 3.5 7-3.5V8"/><path d="M12 11.5V19.5"/></svg>',
            'clock'      => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="7.5"/><path d="M12 8.5v4.2l2.8 1.8"/></svg>',
            'layers'     => '<svg class="shopIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 5.5 7 3.5-7 3.5L5 9l7-3.5Z"/><path d="m7 12 5 2.5 5-2.5"/><path d="m7 15 5 2.5 5-2.5"/></svg>',
        ];
    }

    return $icons[$name] ?? $icons['categories'];
    };

    $visibleProductsCount = 0;
    foreach ($sections as $section) {
    $visibleProductsCount += count(is_array($section['items'] ?? null) ? $section['items'] : []);
    }

    $firstAvailable = date('Y-m-d');
?>

<main class="siteMain siteContainer">
    <section class="menuSplit shopSplit" data-wheel-redirect data-wheel-target=".menuSplit__right">
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
                                <p class="shopIntroStep__copy">Nous validons ensuite avec vous le retrait ou la livraison.</p>
                            </div>
                        </article>
                    </div>
                    <p class="shopIntro__note">
                        <span class="shopIntro__noteIcon" aria-hidden="true">
                            <?php echo $shopIcon('location'); ?>
                        </span>
                        <span>Carte courte, fait maison, stock revérifié à l’enregistrement. Retrait sur créneau ou livraison locale selon votre adresse.</span>
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
                    <p>Ajoutez d'abord des catégories et des produits depuis l'administration pour ouvrir la commande en
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
                                <p class="shopCatalogNav__title">Repérez d'abord votre catégorie, puis composez le panier sans perdre le fil de la carte.</p>
                            </div>
                            <div class="shopCatalogNav__stats" aria-label="Repères de navigation boutique">
                                <span class="shopCatalogNav__stat shopCatalogNav__stat--gold">
                                    <span class="shopCatalogNav__statIcon" aria-hidden="true">
                                        <?php echo $shopIcon('categories'); ?>
                                    </span>
                                    <?php echo count($sections); ?> catégorie<?php echo count($sections) > 1 ? 's' : ''; ?>
                                </span>
                                <span class="shopCatalogNav__stat shopCatalogNav__stat--sage">
                                    <span class="shopCatalogNav__statIcon" aria-hidden="true">
                                        <?php echo $shopIcon('dish'); ?>
                                    </span>
                                    <?php echo $visibleProductsCount; ?> produit<?php echo $visibleProductsCount > 1 ? 's' : ''; ?>
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
                                    <?php echo $shopIcon('bag'); ?>
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
                                        <span class="shopSummary__promoCountdown" data-countdown-target="<?php echo $e($shopPromo['countdown_iso'] ?? ''); ?>">Fin dans --</span>
                                    </div>
                                    <p class="shopSummary__promoCopy"><?php echo $e($shopPromo['banner_text'] ?? ''); ?></p>
                                    <div class="shopPromoCodeRow">
                                        <label class="shopField shopField--full">
                                            <span class="shopField__label">Code promo</span>
                                            <input class="shopInput" type="text" name="promo_code" data-shop-promo-input placeholder="<?php echo $e($shopPromo['promo_code'] ?? ''); ?>">
                                        </label>
                                        <button type="button" class="btn btn--ghost shopPromoCodeRow__apply" data-shop-promo-apply>Appliquer</button>
                                    </div>
                                    <p class="shopSummary__promoHint">Code actif : <strong><?php echo $e($shopPromo['promo_code'] ?? ''); ?></strong> pour -<?php echo (int) ($shopPromo['discount_percent'] ?? 0); ?>% sur les articles boutique.</p>
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
                                                $optionId          = (int) ($option['id'] ?? 0);
                                                $optionUnits       = $resolveOptionUnits($option, $stockUnit);
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
                                                $purchaseLines[]     = [
                                                    'line_key'            => 'item-' . $itemId . '-option-' . $optionId,
                                                    'option_id'           => $optionId,
                                                    'option_label'        => trim((string) ($option['label'] ?? '')),
                                                    'option_units'        => $optionUnits,
                                                    'price_cents'         => (int) ($option['price_cents'] ?? 0),
                                                    'price_display'       => $formatPrice($option),
                                                    'allowed'             => $availableSelections,
                                                    'hint'                => 'Jusqu’à ' . $availableSelections . ' ' . $pluralize($availableSelections, $selectionSingular, $selectionPlural),
                                                    'meta_hint'           => $conversionHint,
                                                    'quantity_label'      => $stockUnit === 'g'
                                                        ? 'Nombre de formats'
                                                        : ($optionUnits > 1 ? 'Nombre de lots' : 'Nombre d’unités'),
                                                    'button_label'        => 'Ajouter 1 ' . $selectionSingular,
                                                    'cart_label_singular' => $selectionSingular,
                                                    'cart_label_plural'   => $selectionPlural,
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
                                                'button_label'        => 'Ajouter 1 unité',
                                                'cart_label_singular' => 'unité',
                                                'cart_label_plural'   => 'unités',
                                            ];
                                        }
                                        $cardPriceDisplay = $purchaseLines[0]['price_display'] ?? $formatPrice($item);
                                        if (count($purchaseLines) > 1) {
                                            $cardPriceDisplay = 'Dès ' . $cardPriceDisplay;
                                        }
                                        $hasAvailableLine = false;
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
                                        data-item-id="<?php echo $itemId; ?>">
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
                                                <div>
                                                    <h4 class="shopItemCard__title">
                                                        <?php echo $e($item['name'] ?? ''); ?></h4>
                                                </div>
                                                <span class="shopItemCard__price"><?php echo $e($cardPriceDisplay); ?></span>
                                            </div>

                                            <div class="shopItemCard__content">
                                                <?php if (($item['description'] ?? '') !== ''): ?>
                                                <p class="shopItemCard__desc"><?php echo $e($item['description'] ?? ''); ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="shopItemCard__purchase">
                                                <div class="shopItemCard__purchaseHead">
                                                    <span
                                                        class="shopStockBadge<?php echo $isLowStock ? ' is-low' : ''; ?><?php echo $isSoldOut ? ' is-sold-out' : ''; ?>"
                                                        data-shop-stock
                                                        data-item-id="<?php echo $itemId; ?>">
                                                        <?php echo $shopIcon($isSoldOut ? 'alert' : ($isLowStock ? 'clock' : 'box')); ?>
                                                        <span><?php echo $e($statusLabel); ?></span>
                                                    </span>
                                                    <p class="shopItemCard__purchaseHint">
                                                        <?php echo $shopIcon($options !== [] ? 'layers' : 'bag'); ?>
                                                        <span><?php echo $options !== [] ? 'Chaque ligne correspond à une sélection distincte. Vous pouvez combiner plusieurs formats pour un même produit.' : 'Ajout direct au panier.'; ?></span>
                                                    </p>
                                                </div>

                                                <div class="shopPurchaseOptions">
                                                    <?php if ($options !== []): ?>
                                                    <p class="shopPurchaseOptions__intro">
                                                        <?php echo $shopIcon('selection'); ?>
                                                        <span>Formats disponibles : choisissez le nombre voulu sur chaque ligne.</span>
                                                    </p>
                                                    <?php endif; ?>
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
                                                        data-shop-order-line
                                                        data-line-key="<?php echo $e($lineKey); ?>"
                                                        data-item-id="<?php echo $itemId; ?>"
                                                        data-item-name="<?php echo $e($item['name'] ?? ''); ?>"
                                                        data-item-stock="<?php echo $stockQuantity; ?>"
                                                        data-item-stock-unit="<?php echo $e($stockUnit); ?>"
                                                        data-item-low-stock-threshold="<?php echo max(0, (int) ($item['low_stock_threshold'] ?? 0)); ?>"
                                                        data-item-price="<?php echo $e($linePrice); ?>"
                                                        data-item-price-cents="<?php echo $linePriceCents; ?>"
                                                        data-option-id="<?php echo $lineOptionId; ?>"
                                                        data-option-label="<?php echo $e($lineLabel); ?>"
                                                        data-option-units="<?php echo $lineUnits; ?>"
                                                        data-cart-label-singular="<?php echo $e($lineCartLabelSingular); ?>"
                                                        data-cart-label-plural="<?php echo $e($lineCartLabelPlural); ?>">
                                                        <div class="shopPurchaseOption__head">
                                                            <div class="shopPurchaseOption__copy">
                                                                <strong class="shopPurchaseOption__title"><?php echo $e($lineTitle); ?></strong>
                                                                <p class="shopPurchaseOption__hint" data-default-text="<?php echo $e($lineHint); ?>">
                                                                    <span><?php echo $e($lineHint); ?></span>
                                                                </p>
                                                                <p class="shopPurchaseOption__meta">
                                                                    <span><?php echo $e($lineMetaHint); ?></span>
                                                                </p>
                                                            </div>
                                                            <span class="shopPurchaseOption__price"><?php echo $e($linePrice); ?></span>
                                                        </div>

                                                        <input type="hidden" name="shop_item[<?php echo $e($lineKey); ?>]" value="<?php echo $itemId; ?>">
                                                        <input type="hidden" name="shop_option[<?php echo $e($lineKey); ?>]" value="<?php echo $lineOptionId > 0 ? $lineOptionId : ''; ?>">
                                                        <input type="hidden" name="shop_option_label[<?php echo $e($lineKey); ?>]" value="<?php echo $e($lineLabel); ?>">
                                                        <input type="hidden" name="shop_option_units[<?php echo $e($lineKey); ?>]" value="<?php echo $lineUnits; ?>">

                                                        <div class="shopItemCard__actions">
                                                            <button type="button" class="btn btn--ghost shopItemCard__add"
                                                                data-shop-add data-line-key="<?php echo $e($lineKey); ?>"
                                                                <?php echo $lineSoldOut ? 'disabled' : ''; ?>><?php echo $e($lineButtonLabel); ?></button>

                                                            <div class="shopQtyControls" data-shop-controls
                                                                data-line-key="<?php echo $e($lineKey); ?>" hidden>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-decrease data-line-key="<?php echo $e($lineKey); ?>"
                                                                    aria-label="Retirer une unité de cette sélection">−</button>
                                                                <label class="shopQty">
                                                                    <span class="shopQty__label"><?php echo $e($lineQuantityLabel); ?></span>
                                                                    <input class="shopQty__input" type="number"
                                                                        name="shop_quantity[<?php echo $e($lineKey); ?>]" min="0"
                                                                        max="<?php echo $lineAllowed; ?>" value="0"
                                                                        <?php echo $lineSoldOut ? 'disabled' : ''; ?>
                                                                        data-shop-qty data-line-key="<?php echo $e($lineKey); ?>">
                                                                </label>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-increase data-line-key="<?php echo $e($lineKey); ?>"
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
                                        <p class="shopSectionHead__hint">Deux étapes suffisent pour envoyer la demande. Nous confirmons ensuite avec vous le retrait ou la livraison.
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
                                        <p>Choisissez un créneau souhaité. La livraison reste proposée selon votre adresse.</p>
                                    </div>

                                    <div class="shopFieldGrid">
                                        <div class="shopField shopField--full">
                                            <span class="shopField__label">Mode souhaité</span>
                                            <div class="shopFulfillmentChoices">
                                                <label class="shopFulfillmentChoice">
                                                    <input type="radio" name="fulfillment_method" value="pickup"
                                                        data-shop-fulfillment checked>
                                                    <span class="shopFulfillmentChoice__body">
                                                        <strong>Retrait</strong>
                                                        <small>Créneau confirmé avec vous</small>
                                                    </span>
                                                </label>
                                                <label class="shopFulfillmentChoice">
                                                    <input type="radio" name="fulfillment_method" value="delivery"
                                                        data-shop-fulfillment>
                                                    <span class="shopFulfillmentChoice__body">
                                                        <strong>Livraison</strong>
                                                        <small>Dans un rayon de 20 km dès 15 €</small>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <label class="shopField">
                                            <span class="shopField__label">Date souhaitée</span>
                                            <input class="shopInput" type="date" name="pickup_date"
                                                min="<?php echo $e($firstAvailable); ?>" required>
                                        </label>
                                        <label class="shopField">
                                            <span class="shopField__label">Créneau souhaité</span>
                                            <input class="shopInput" type="text" name="pickup_slot"
                                                placeholder="Ex: samedi 11h00 - 12h00">
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