<?php
    $sections  = is_array($sections ?? null) ? $sections : [];
    $loadError = is_string($loadError ?? null) ? $loadError : null;

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
                <img class="menuHero__img" src="/uploads/pages/menu/images/menu3Illu.webp" alt="" aria-hidden="true">
                <div class="shopHero__content">
                    <span class="shopHero__eyebrow">Boutique en ligne</span>
                    <h1 class="menuHero__title">Commande en ligne</h1>
                    <p class="shopHero__lead">Créations du moment, quantités limitées et retrait organisé simplement.
                        Les disponibilités se mettent à jour en direct.</p>
                </div>
            </div>
        </div>

        <div class="menuSplit__right">
            <div class="menuPanel shopPanel">
                <header class="shopIntro">
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="shopIntro__title">Commander avec un stock réel</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="shopIntro__copy">Chaque quantité envoyée est vérifiée au moment de la commande pour
                        garantir la fraîcheur et éviter toute survente.</p>
                    <div class="shopIntro__meta">
                        <span class="shopPill">Retrait sur créneau</span>
                        <span class="shopPill">Stock limité</span>
                        <span class="shopPill">Validation immédiate</span>
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
                    data-stock-endpoint="/api/boutique/stock" data-submit-endpoint="/boutique-en-ligne">
                    <div class="shopOrderTop">
                        <div class="shopSummaryDock" data-shop-summary-dock>
                            <button type="button" class="shopSummaryTab" data-shop-summary-toggle aria-expanded="false"
                                aria-controls="shopSummaryPanel" hidden>
                                <span class="shopSummaryTab__main">
                                    <span class="shopSummaryTab__label">Panier</span>
                                    <strong class="shopSummaryTab__count" data-shop-summary-tab-count>0</strong>
                                    <span class="shopSummaryTab__items" data-shop-summary-count-mobile>0 article</span>
                                </span>

                                <span class="shopSummaryTab__aside">
                                    <span class="shopSummaryTab__total" data-shop-summary-tab-total>0,00 €</span>
                                    <span class="shopSummaryTab__cta">Voir le panier</span>
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
                                    <span>Total estimatif</span>
                                    <strong data-shop-summary-total>0,00 €</strong>
                                </div>

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
                                        $maxOrder      = max(1, (int) ($item['max_order_quantity'] ?? 1));
                                        $allowed       = min($stockQuantity, $maxOrder);
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
                                        data-item-stock="<?php echo $stockQuantity; ?>"
                                        data-item-max-order="<?php echo $maxOrder; ?>">
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
                                                <span class="shopItemCard__limit">Jusqu'à <?php echo $allowed; ?> par
                                                    commande</span>
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
                                        <li>Nous revenons vers vous pour confirmer le retrait.</li>
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
                                        <h4>Retrait souhaité</h4>
                                        <p>Dites-nous quand vous souhaitez récupérer la commande.</p>
                                    </div>

                                    <div class="shopFieldGrid">
                                        <label class="shopField">
                                            <span class="shopField__label">Date de retrait</span>
                                            <input class="shopInput" type="date" name="pickup_date"
                                                min="<?php echo $e($firstAvailable); ?>" required>
                                        </label>
                                        <label class="shopField">
                                            <span class="shopField__label">Créneau de retrait</span>
                                            <input class="shopInput" type="text" name="pickup_slot"
                                                placeholder="Ex: samedi 11h00 - 12h00">
                                        </label>
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