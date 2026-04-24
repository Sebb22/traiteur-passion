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
                            de production, garantir la fraîcheur et ne proposer que des créations prêtes à être
                            retirées dans de bonnes conditions.</p>
                        <p class="shopIntro__copy">Ici, pas de catalogue figé ni d’assemblage standardisé: uniquement
                            du fait maison, produit en petites séries, avec une sélection volontairement courte pour
                            préserver le goût, la régularité et la qualité d’exécution. Une livraison peut aussi être
                            proposée ensuite par l’équipe dans un rayon de 20 km dès 15 € de commande.</p>
                    </div>
                    <div class="shopIntro__story" aria-label="Esprit de la boutique">
                        <article class="shopIntroCard">
                            <span class="shopIntroCard__kicker">Sélection</span>
                            <strong class="shopIntroCard__title">Une boutique courte mais vraiment suivie</strong>
                            <p class="shopIntroCard__copy">Chaque semaine, la carte se concentre sur peu de références pour garder de la fraîcheur, du goût et une exécution régulière.</p>
                        </article>
                        <article class="shopIntroCard">
                            <span class="shopIntroCard__kicker">Commande</span>
                            <strong class="shopIntroCard__title">Vous composez, nous revérifions</strong>
                            <p class="shopIntroCard__copy">Le panier donne une lecture claire de la commande, puis le stock et les créneaux sont revus avant validation finale.</p>
                        </article>
                        <article class="shopIntroCard">
                            <span class="shopIntroCard__kicker">Service</span>
                            <strong class="shopIntroCard__title">Retrait simple, livraison selon votre zone</strong>
                            <p class="shopIntroCard__copy">La boutique est pensée pour aller vite sans devenir impersonnelle, avec un retrait cadré et une livraison locale quand elle est possible.</p>
                        </article>
                    </div>
                    <div class="shopIntro__highlight">
                        <strong class="shopIntro__highlightTitle">Carte courte, rotation hebdo, fait maison
                            uniquement</strong>
                        <p class="shopIntro__highlightCopy">Chaque commande fait l’objet d’une vérification attentive
                            afin de garantir des produits disponibles, sélectionnés avec exigence pour leur fraîcheur.
                            La livraison est également proposée autour de Compiègne, dans un rayon de 20 km, dès 15 € de
                            commande, selon votre localisation.</p>
                    </div>
                    <div class="shopIntro__meta">
                        <span class="shopPill">Renouvelée chaque semaine</span>
                        <span class="shopPill">100% fait maison</span>
                        <span class="shopPill">Retrait sur créneau</span>
                        <span class="shopPill">Livraison 20 km dès 15 €</span>
                        <span class="shopPill">Stock limité</span>
                    </div>
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
                    <p>Ajoutez d'abord des sections et des produits depuis l'administration pour ouvrir la commande en
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
                            <article class="shopCatalogSection" id="section-<?php echo (int) ($section['id'] ?? 0); ?>">
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
                                        $allowed       = $stockQuantity;
                                        $isSoldOut     = ! empty($item['is_sold_out']) || $stockQuantity <= 0;
                                        $isLowStock    = ! empty($item['is_low_stock']);
                                        $statusLabel   = $isSoldOut
                                            ? 'Rupture'
                                            : ($isLowStock ? 'Plus que ' . $stockQuantity . ' disponible(s)' : $stockQuantity . ' disponible(s)');
                                    ?>
                                    <article class="shopItemCard<?php echo $isSoldOut ? ' is-sold-out' : ''; ?>"
                                        data-shop-item data-item-id="<?php echo $itemId; ?>"
                                        data-item-name="<?php echo $e($item['name'] ?? ''); ?>"
                                        data-item-price="<?php echo $e($formatPrice($item)); ?>"
                                        data-item-price-cents="<?php echo (int) ($item['price_cents'] ?? 0); ?>"
                                        data-item-stock="<?php echo $stockQuantity; ?>">
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
                                                    <span class="shopItemCard__eyebrow">Sélection prête à retirer</span>
                                                    <h4 class="shopItemCard__title">
                                                        <?php echo $e($item['name'] ?? ''); ?></h4>
                                                </div>
                                                <span
                                                    class="shopItemCard__price"><?php echo $e($formatPrice($item)); ?></span>
                                            </div>

                                            <?php if (($item['description'] ?? '') !== ''): ?>
                                            <p class="shopItemCard__desc"><?php echo $e($item['description'] ?? ''); ?>
                                            </p>
                                            <?php endif; ?>

                                            <div class="shopItemCard__meta">
                                                <span
                                                    class="shopStockBadge<?php echo $isLowStock ? ' is-low' : ''; ?><?php echo $isSoldOut ? ' is-sold-out' : ''; ?>"
                                                    data-shop-stock
                                                    data-item-id="<?php echo $itemId; ?>"><?php echo $e($statusLabel); ?></span>
                                            </div>

                                            <div class="shopItemCard__footer">
                                                <div class="shopItemCard__actions">
                                                    <button type="button" class="btn btn--ghost shopItemCard__add"
                                                        data-shop-add data-item-id="<?php echo $itemId; ?>"
                                                        <?php echo $isSoldOut ? 'disabled' : ''; ?>>Ajouter au
                                                        panier</button>

                                                    <div class="shopQtyControls" data-shop-controls
                                                        data-item-id="<?php echo $itemId; ?>" hidden>
                                                        <button type="button" class="shopQtyControls__btn"
                                                            data-shop-decrease data-item-id="<?php echo $itemId; ?>"
                                                            aria-label="Retirer une unité">−</button>
                                                        <label class="shopQty">
                                                            <span class="shopQty__label">Quantité</span>
                                                            <input class="shopQty__input" type="number"
                                                                name="shop_quantity[<?php echo $itemId; ?>]" min="0"
                                                                max="<?php echo $allowed; ?>" value="0"
                                                                <?php echo $isSoldOut ? 'disabled' : ''; ?>
                                                                data-shop-qty data-item-id="<?php echo $itemId; ?>">
                                                        </label>
                                                        <button type="button" class="shopQtyControls__btn"
                                                            data-shop-increase data-item-id="<?php echo $itemId; ?>"
                                                            aria-label="Ajouter une unité">+</button>
                                                        <button type="button" class="shopQtyControls__remove"
                                                            data-shop-remove
                                                            data-item-id="<?php echo $itemId; ?>">Retirer</button>
                                                    </div>
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
                                        <p class="shopSectionHead__hint">Nous enregistrons simplement la demande et le
                                            stock correspondant. La confirmation se fait ensuite directement avec vous.
                                        </p>
                                    </div>
                                </div>

                                <div class="shopCheckoutIntro">
                                    <span class="shopCheckoutIntro__eyebrow">Après validation</span>
                                    <ul class="shopCheckoutIntro__list">
                                        <li>Le stock choisi est réservé à l’enregistrement.</li>
                                        <li>Nous revenons vers vous pour confirmer le retrait ou proposer la livraison
                                            si elle est possible.</li>
                                        <li>La livraison peut être proposée dans un rayon de 20 km dès 15 € de commande.
                                        </li>
                                        <li>Aucun paiement n’est demandé à cette étape.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="shopCheckoutLayout">
                                <div class="shopCheckoutPanel">
                                    <div class="shopCheckoutPanel__head">
                                        <h4>Vos coordonnées</h4>
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
                                        <h4>Retrait ou livraison</h4>
                                        <p>Dites-nous quand vous souhaitez récupérer la commande. Si votre adresse le
                                            permet, nous pouvons aussi vous proposer une livraison dans un rayon de 20
                                            km dès 15 €.</p>
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