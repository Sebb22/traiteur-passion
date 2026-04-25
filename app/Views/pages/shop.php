<?php
    $sections  = is_array($sections ?? null) ? $sections : [];
    $loadError = is_string($loadError ?? null) ? $loadError : null;
    $shopPromo = is_array($shopPromo ?? null) ? $shopPromo : null;

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

    $resolveOptionUnits = static function (array $option): int {
    $quantity = max(1, (int) ($option['quantity'] ?? 1));
    if ($quantity > 1) {
        return $quantity;
    }

    $label = trim((string) ($option['label'] ?? ''));
    if ($label !== '' && preg_match('/\b(?:lot|x)\s*(?:de\s*)?(\d+)\b/i', $label, $matches) === 1) {
        return max(1, (int) ($matches[1] ?? 1));
    }

    return $quantity;
    };

    $resolveSectionAnchor = static function (array $section): string {
    $slug = trim((string) ($section['slug'] ?? ''));
    if ($slug !== '') {
        return $slug;
    }

    return 'section-' . (int) ($section['id'] ?? 0);
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
                    <div class="shopIntroSteps" aria-label="Parcours de commande boutique">
                        <article class="shopIntroStep">
                            <span class="shopIntroStep__index">01</span>
                            <div>
                                <strong class="shopIntroStep__title">Choisissez une catégorie</strong>
                                <p class="shopIntroStep__copy">Repérez vite ce qui est disponible cette semaine.</p>
                            </div>
                        </article>
                        <article class="shopIntroStep">
                            <span class="shopIntroStep__index">02</span>
                            <div>
                                <strong class="shopIntroStep__title">Ajoutez les bons formats</strong>
                                <p class="shopIntroStep__copy">À l’unité ou en lot selon le stock affiché.</p>
                            </div>
                        </article>
                        <article class="shopIntroStep">
                            <span class="shopIntroStep__index">03</span>
                            <div>
                                <strong class="shopIntroStep__title">Confirmez sans payer</strong>
                                <p class="shopIntroStep__copy">Nous validons ensuite avec vous le retrait ou la livraison.</p>
                            </div>
                        </article>
                    </div>
                    <p class="shopIntro__note">Carte courte, fait maison, stock revérifié à l’enregistrement. Retrait sur créneau ou livraison locale selon votre adresse.</p>
                </header>

                <?php if ($loadError !== null): ?>
                <section class="shopNotice shopNotice--warning" aria-live="polite">
                    <strong>Boutique temporairement indisponible.</strong>
                    <p><?php echo $e($loadError); ?></p>
                </section>
                <?php endif; ?>

                <?php if ($sections === [] && $loadError === null): ?>
                <section class="shopNotice" aria-live="polite">
                    <strong>La boutique n'est pas encore configurée.</strong>
                    <p>Ajoutez d'abord des catégories et des produits depuis l'administration pour ouvrir la commande en
                        ligne.</p>
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
                                <span class="shopCatalogNav__stat"><?php echo count($sections); ?> catégorie<?php echo count($sections) > 1 ? 's' : ''; ?></span>
                                <span class="shopCatalogNav__stat"><?php echo $visibleProductsCount; ?> produit<?php echo $visibleProductsCount > 1 ? 's' : ''; ?></span>
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
                                    <svg viewBox="0 0 24 24" focusable="false">
                                        <path
                                            d="M7 6.5h13l-1.4 6.73a2 2 0 0 1-1.96 1.6H10.1a2 2 0 0 1-1.95-1.56L6.2 4.5H3.5"
                                            fill="none" stroke="currentColor" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="1.7" />
                                        <circle cx="10.5" cy="18.5" r="1.35" fill="currentColor" />
                                        <circle cx="17.25" cy="18.5" r="1.35" fill="currentColor" />
                                    </svg>
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
                                        $options       = array_values(array_filter(
                                            is_array($item['options'] ?? null) ? $item['options'] : [],
                                            static fn(array $option): bool => ! empty($option['is_active']),
                                        ));
                                        $purchaseLines = [];
                                        if ($options !== []) {
                                            foreach ($options as $option) {
                                                $optionId        = (int) ($option['id'] ?? 0);
                                                $optionUnits     = $resolveOptionUnits($option);
                                                $purchaseLines[] = [
                                                    'line_key'      => 'item-' . $itemId . '-option-' . $optionId,
                                                    'option_id'     => $optionId,
                                                    'option_label'  => trim((string) ($option['label'] ?? '')),
                                                    'option_units'  => $optionUnits,
                                                    'price_cents'   => (int) ($option['price_cents'] ?? 0),
                                                    'price_display' => $formatPrice($option),
                                                    'allowed'       => (int) floor($stockQuantity / max(1, $optionUnits)),
                                                    'hint'          => $optionUnits > 1
                                                        ? $optionUnits . ' unité(s) par lot'
                                                        : 'Ajout à l’unité',
                                                ];
                                            }
                                        } else {
                                            $purchaseLines[] = [
                                                'line_key'      => 'item-' . $itemId . '-default',
                                                'option_id'     => 0,
                                                'option_label'  => '',
                                                'option_units'  => 1,
                                                'price_cents'   => (int) ($item['price_cents'] ?? 0),
                                                'price_display' => $formatPrice($item),
                                                'allowed'       => $stockQuantity,
                                                'hint'          => 'Ajout direct au panier',
                                            ];
                                        }
                                        $cardPriceDisplay  = $purchaseLines[0]['price_display'] ?? $formatPrice($item);
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
                                            : ($isLowStock ? 'Plus que ' . $stockQuantity . ' unité(s)' : $stockQuantity . ' unité(s)');
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
                                                        data-item-id="<?php echo $itemId; ?>"><?php echo $e($statusLabel); ?></span>
                                                    <p class="shopItemCard__purchaseHint"><?php echo $options !== [] ? 'Chaque format ci-dessous peut être ajouté séparément. Vous pouvez cumuler plusieurs lots pour un même produit.' : 'Ajout direct au panier.'; ?></p>
                                                </div>

                                                <div class="shopPurchaseOptions">
                                                    <?php if ($options !== []): ?>
                                                    <p class="shopPurchaseOptions__intro">Formats disponibles : ajoutez une quantité sur chaque ligne voulue.</p>
                                                    <?php endif; ?>
                                                    <?php foreach ($purchaseLines as $purchaseLine): ?>
                                                    <?php
                                                        $lineKey        = (string) ($purchaseLine['line_key'] ?? '');
                                                        $lineOptionId   = (int) ($purchaseLine['option_id'] ?? 0);
                                                        $lineLabel      = trim((string) ($purchaseLine['option_label'] ?? ''));
                                                        $lineUnits      = max(1, (int) ($purchaseLine['option_units'] ?? 1));
                                                        $linePrice      = (string) ($purchaseLine['price_display'] ?? '');
                                                        $linePriceCents = (int) ($purchaseLine['price_cents'] ?? 0);
                                                        $lineAllowed    = max(0, (int) ($purchaseLine['allowed'] ?? 0));
                                                        $lineHint       = trim((string) ($purchaseLine['hint'] ?? ''));
                                                        $lineSoldOut    = $stockQuantity <= 0 || $lineAllowed <= 0;
                                                        $lineTitle      = $lineLabel !== '' ? $lineLabel : 'Format standard';
                                                    ?>
                                                    <div class="shopPurchaseOption<?php echo $lineSoldOut ? ' is-sold-out' : ''; ?>"
                                                        data-shop-order-line
                                                        data-line-key="<?php echo $e($lineKey); ?>"
                                                        data-item-id="<?php echo $itemId; ?>"
                                                        data-item-name="<?php echo $e($item['name'] ?? ''); ?>"
                                                        data-item-stock="<?php echo $stockQuantity; ?>"
                                                        data-item-price="<?php echo $e($linePrice); ?>"
                                                        data-item-price-cents="<?php echo $linePriceCents; ?>"
                                                        data-option-id="<?php echo $lineOptionId; ?>"
                                                        data-option-label="<?php echo $e($lineLabel); ?>"
                                                        data-option-units="<?php echo $lineUnits; ?>">
                                                        <div class="shopPurchaseOption__head">
                                                            <div class="shopPurchaseOption__copy">
                                                                <strong class="shopPurchaseOption__title"><?php echo $e($lineTitle); ?></strong>
                                                                <p class="shopPurchaseOption__hint" data-default-text="<?php echo $e($lineHint); ?>"><?php echo $e($lineHint); ?></p>
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
                                                                <?php echo $lineSoldOut ? 'disabled' : ''; ?>>Ajouter</button>

                                                            <div class="shopQtyControls" data-shop-controls
                                                                data-line-key="<?php echo $e($lineKey); ?>" hidden>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-decrease data-line-key="<?php echo $e($lineKey); ?>"
                                                                    aria-label="Retirer une unité">−</button>
                                                                <label class="shopQty">
                                                                    <span class="shopQty__label">Quantité</span>
                                                                    <input class="shopQty__input" type="number"
                                                                        name="shop_quantity[<?php echo $e($lineKey); ?>]" min="0"
                                                                        max="<?php echo $lineAllowed; ?>" value="0"
                                                                        <?php echo $lineSoldOut ? 'disabled' : ''; ?>
                                                                        data-shop-qty data-line-key="<?php echo $e($lineKey); ?>">
                                                                </label>
                                                                <button type="button" class="shopQtyControls__btn"
                                                                    data-shop-increase data-line-key="<?php echo $e($lineKey); ?>"
                                                                    aria-label="Ajouter une unité">+</button>
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

                <footer class="menuFooter">
                    <p>© <?php echo date('Y'); ?> Traiteur Passion</p>
                </footer>
            </div>
        </div>
    </section>
</main>