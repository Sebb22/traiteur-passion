<?php
    $contactStats      = is_array($contactStats ?? null) ? $contactStats : [];
    $blogStats         = is_array($blogStats ?? null) ? $blogStats : [];
    $catalogStats      = is_array($catalogStats ?? null) ? $catalogStats : [];
    $orderStats        = is_array($orderStats ?? null) ? $orderStats : [];
    $orderStatusLabels = is_array($orderStatusLabels ?? null) ? $orderStatusLabels : [];
    $shopStats         = is_array($shopStats ?? null) ? $shopStats : [];
    $recentContacts    = is_array($recentContacts ?? null) ? $recentContacts : [];
    $recentOrders      = is_array($recentOrders ?? null) ? $recentOrders : [];
    $typeBreakdown     = is_array($typeBreakdown ?? null) ? $typeBreakdown : [];
    $shopLoadError     = is_string($shopLoadError ?? null) ? $shopLoadError : null;
    $shopPromo         = is_array($shopPromo ?? null) ? $shopPromo : [];
    $flash             = is_array($flash ?? null) ? $flash : null;

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatDate = static function ($value, string $format = 'd/m/Y'): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }

    $timestamp = strtotime($raw);

    return $timestamp !== false ? date($format, $timestamp) : '-';
    };

    $maxTypeCount = 0;
    foreach ($typeBreakdown as $typeRow) {
    $maxTypeCount = max($maxTypeCount, (int) ($typeRow['total'] ?? 0));
    }

    $formatPrice = static function ($value): string {
    return number_format(max(0, (int) $value) / 100, 2, ',', ' ') . ' €';
    };

    $urgentTotal = (int) ($contactStats['new_count'] ?? 0) + (int) ($orderStats['new_count'] ?? 0);

    $kpiCards = [
    [
        'label' => 'Nouvelles demandes',
        'value' => (int) ($contactStats['new_count'] ?? 0),
        'href'  => '/admin/contacts?status=new',
    ],
    [
        'label' => 'Demandes en cours',
        'value' => (int) ($contactStats['in_progress_count'] ?? 0),
        'href'  => '/admin/contacts?status=in_progress',
    ],
    [
        'label' => 'Commandes nouvelles',
        'value' => (int) ($orderStats['new_count'] ?? 0),
        'href'  => '/admin/contacts#orders',
    ],
    [
        'label' => 'Evenements a venir',
        'value' => (int) ($contactStats['upcoming_events'] ?? 0),
        'href'  => '/admin/contacts',
    ],
    ];

    $moduleCards = [
    [
        'eyebrow' => 'Demandes',
        'title'   => 'Suivi commercial',
        'meta'    => 'Demandes, devis et commandes sur un seul ecran.',
        'href'    => '/admin/contacts',
    ],
    [
        'eyebrow' => 'Carte',
        'title'   => 'Catalogue evenementiel',
        'meta'    => 'Sections, items et options de la carte.',
        'href'    => '/admin/catalog',
    ],
    [
        'eyebrow' => 'Boutique',
        'title'   => 'Produits et stock',
        'meta'    => 'Suivi du catalogue e-commerce et des commandes.',
        'href'    => '/admin/boutique',
    ],
    [
        'eyebrow' => 'Blog',
        'title'   => 'Contenus editoriaux',
        'meta'    => 'Publication et corrections des articles.',
        'href'    => '/admin/blog',
    ],
    ];

    $businessCards = [
    [
        'label' => 'Catalogue visible',
        'value' => (int) ($catalogStats['items'] ?? 0),
        'meta'  => (int) ($catalogStats['sections'] ?? 0) . ' section(s)',
    ],
    [
        'label' => 'Boutique active',
        'value' => (int) ($shopStats['active_items'] ?? 0),
        'meta'  => (int) ($shopStats['low_stock_items'] ?? 0) . ' stock(s) bas',
    ],
    [
        'label' => 'Articles publies',
        'value' => (int) ($blogStats['published'] ?? 0),
        'meta'  => (int) ($blogStats['drafts'] ?? 0) . ' brouillon(s)',
    ],
    [
        'label' => 'Dossiers qualifies',
        'value' => (int) ($contactStats['quoted_count'] ?? 0),
        'meta'  => (int) ($contactStats['completed_count'] ?? 0) . ' finalise(s)',
    ],
    ];

    $promoStatusLabels = [
    'inactive'  => 'Inactive',
    'scheduled' => 'Programmée',
    'active'    => 'Active',
    'expired'   => 'Terminée',
    ];

    $promoStatus = (string) ($shopPromo['status'] ?? 'inactive');
?>
<div class="adminSplit adminSplit--dashboard">
    <main class="adminSplit__panel">
        <header class="adminPanelHead adminPanelHead--dashboard">
            <div class="adminOverviewHero">
                <div class="adminOverviewHero__content">
                    <div class="adminOverviewHero__eyebrow">Poste de pilotage</div>
                    <h1 class="adminTitle">Vue prioritaire du jour</h1>
                    <p class="adminSubtitle">Reperez d'abord les nouvelles demandes et commandes, puis ouvrez les modules de gestion seulement quand le dossier demande plus de detail.</p>
                </div>

                <div class="adminOverviewHero__summary">
                    <div class="adminOverviewHero__summaryLabel">Urgences du jour</div>
                    <div class="adminOverviewHero__summaryValue"><?php echo $urgentTotal; ?></div>
                    <div class="adminOverviewHero__summaryMeta">
                        <?php echo (int) ($contactStats['new_count'] ?? 0); ?> demande(s) a qualifier • <?php echo (int) ($orderStats['new_count'] ?? 0); ?> commande(s) a confirmer
                    </div>
                </div>
            </div>

            <div class="adminPanelHead__actions adminPanelHead__actions--dashboard">
                <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                    <a href="/admin/contacts?status=new" class="adminBtn adminBtn--primary">Ouvrir les nouvelles demandes</a>
                    <a href="/admin/contacts#orders" class="adminBtn">Ouvrir les commandes</a>
                </div>
                <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--modules">
                    <a href="/admin/blog" class="adminBtn">Blog</a>
                    <a href="/admin/catalog" class="adminBtn">Carte</a>
                    <a href="/admin/boutique" class="adminBtn">Boutique</a>
                </div>
                <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--utility">
                    <a href="/" class="adminBtn">Retour au site</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Deconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="adminStats adminStats--panel adminStats--dashboard" aria-label="Priorites du jour">
            <?php foreach ($kpiCards as $card): ?>
            <a href="<?php echo $e($card['href']); ?>" class="statCard statCard--link adminLink">
                <div class="statCard__label"><?php echo $e($card['label']); ?></div>
                <div class="statCard__value"><?php echo (int) $card['value']; ?></div>
            </a>
            <?php endforeach; ?>
        </section>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>">
            <?php echo $e($flash['message'] ?? 'Modification enregistrée.'); ?>
        </div>
        <?php endif; ?>

        <?php if ($shopLoadError !== null): ?>
        <div class="adminFlash adminFlash--error"><?php echo $e($shopLoadError); ?></div>
        <?php endif; ?>

        <div class="adminDashboardGrid adminDashboardGrid--triage">
            <div class="adminDashboardStack">
                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Demandes recentes a qualifier</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Les dernieres demandes qui demandent une lecture rapide</span>
                        </div>
                    </div>

                    <?php if ($recentContacts === []): ?>
                    <div class="adminEmptyState">Aucune demande enregistre pour le moment.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($recentContacts as $contact): ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>" class="adminLink">
                                        <?php echo $e($contact['name'] ?? 'Demande'); ?>
                                    </a>
                                    <span class="adminBadge adminBadge--<?php echo $e($contact['status'] ?? 'new'); ?>">
                                        <?php echo $e(ucfirst((string) ($contact['status'] ?? 'new'))); ?>
                                    </span>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($contact['email'] ?? ''); ?>
                                    • <?php echo $e($contact['type'] ?? 'Type non renseigne'); ?>
                                    • <?php echo $formatDate($contact['date'] ?? null); ?>
                                </div>
                            </div>

                            <div class="adminMiniList__aside">
                                <?php echo (int) ($contact['menu_items_count'] ?? 0); ?> selection(s) carte<br>
                                Creee le <?php echo $formatDate($contact['created_at'] ?? null, 'd/m/Y H:i'); ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>" class="adminBtn adminBtn--sm">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <section class="adminCard adminCard--table" id="orders">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Commandes boutique recentes</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo (int) ($orderStats['total'] ?? 0); ?> commande(s) dans le flux boutique</span>
                        </div>
                    </div>

                    <?php if ($shopLoadError !== null): ?>
                    <div class="adminEmptyState"><?php echo $e($shopLoadError); ?></div>
                    <?php elseif ($recentOrders === []): ?>
                    <div class="adminEmptyState">Aucune commande boutique pour le moment.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($recentOrders as $order): ?>
                        <?php
                            $orderDiscountCents = max(0, (int) ($order['discount_cents'] ?? 0));
                            $orderSubtotalCents = max(0, (int) ($order['subtotal_cents'] ?? $order['total_cents'] ?? 0));
                            $orderPromoCode     = trim((string) ($order['promo_code'] ?? ''));
                        ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminLink">
                                        #<?php echo (int) ($order['id'] ?? 0); ?> · <?php echo $e($order['customer_name'] ?? 'Commande'); ?>
                                    </a>
                                    <span class="adminBadge adminBadge--<?php echo $e($order['status'] ?? 'new'); ?>">
                                        <?php echo $e($orderStatusLabels[(string) ($order['status'] ?? 'new')] ?? (string) ($order['status'] ?? 'new')); ?>
                                    </span>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($order['customer_email'] ?? '-'); ?>
                                    • <?php echo $e(($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait'); ?>
                                    • <?php echo $formatDate($order['pickup_date'] ?? null); ?>
                                </div>
                            </div>

                            <div class="adminMiniList__aside">
                                <?php echo (int) ($order['item_count'] ?? 0); ?> article(s)<br>
                                Total <?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?>
                                <?php if ($orderDiscountCents > 0): ?><br>
                                <span class="adminPromoState">
                                    Promo <?php echo $e($orderPromoCode !== '' ? $orderPromoCode : 'appliquee'); ?> · -<?php echo $e($formatPrice($orderDiscountCents)); ?>
                                </span><br>
                                <span class="adminHint">Sous-total <?php echo $e($formatPrice($orderSubtotalCents)); ?></span>
                                <?php endif; ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminBtn adminBtn--sm">Voir commande</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head" id="shop-promo">
                        <div class="adminCard__title">Promotion boutique</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Bannière sticky globale, minuteur et code promo panier.</span>
                        </div>
                    </div>

                    <div class="adminDashboardPromoStatus">
                        <span class="adminBadge adminBadge--<?php echo $e($promoStatus === 'active' ? 'completed' : ($promoStatus === 'scheduled' ? 'quoted' : 'new')); ?>">
                            <?php echo $e($promoStatusLabels[$promoStatus] ?? 'Inactive'); ?>
                        </span>
                        <div class="adminDashboardPromoMeta">
                            <strong><?php echo $e($shopPromo['title'] ?? 'Offre de lancement'); ?></strong>
                            <span>Code <?php echo $e($shopPromo['promo_code'] ?? '-'); ?> • -<?php echo (int) ($shopPromo['discount_percent'] ?? 0); ?>%</span>
                            <span>Du <?php echo $formatDate($shopPromo['starts_at'] ?? null, 'd/m/Y H:i'); ?> au <?php echo $formatDate($shopPromo['ends_at'] ?? null, 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>

                    <form action="/admin/dashboard/shop-promo" method="post" class="adminForm adminInlineForm--stack">
                        <div class="adminFieldGrid">
                            <label class="adminField adminField--checkbox">
                                <span class="adminField__label">Activer l'offre</span>
                                <input class="adminCheckbox" type="checkbox" name="is_enabled" value="1"
                                    <?php echo ! empty($shopPromo['is_enabled']) ? 'checked' : ''; ?>>
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Titre</span>
                                <input class="adminInput" type="text" name="title"
                                    value="<?php echo $e($shopPromo['title'] ?? 'Offre de lancement'); ?>">
                            </label>
                            <label class="adminField adminField--sm">
                                <span class="adminField__label">Remise (%)</span>
                                <input class="adminInput" type="number" name="discount_percent" min="1" max="90"
                                    value="<?php echo (int) ($shopPromo['discount_percent'] ?? 10); ?>">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Code promo</span>
                                <input class="adminInput" type="text" name="promo_code"
                                    value="<?php echo $e($shopPromo['promo_code'] ?? ''); ?>" placeholder="LANCEMENT10">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Début</span>
                                <input class="adminInput" type="datetime-local" name="starts_at"
                                    value="<?php echo $e($shopPromo['starts_at_input'] ?? ''); ?>">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Fin</span>
                                <input class="adminInput" type="datetime-local" name="ends_at"
                                    value="<?php echo $e($shopPromo['ends_at_input'] ?? ''); ?>">
                            </label>
                            <label class="adminField adminField--full">
                                <span class="adminField__label">Texte bannière</span>
                                <input class="adminInput" type="text" name="banner_text"
                                    value="<?php echo $e($shopPromo['banner_text'] ?? ''); ?>"
                                    placeholder="-10% sur les articles de la boutique pendant l'offre de lancement.">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Libellé bouton</span>
                                <input class="adminInput" type="text" name="cta_label"
                                    value="<?php echo $e($shopPromo['cta_label'] ?? 'Voir la boutique'); ?>">
                            </label>
                        </div>

                        <div class="adminInlineActions">
                            <button type="submit" class="adminBtn adminBtn--primary">Enregistrer la promo boutique</button>
                        </div>
                    </form>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Etat du business</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Indicateurs compacts pour verifier le contenu, le catalogue et le stock</span>
                        </div>
                    </div>

                    <div class="adminDashboardQuickGrid">
                        <?php foreach ($businessCards as $card): ?>
                        <div class="adminQuickLinkCard adminQuickLinkCard--metric">
                            <div class="adminQuickLinkCard__eyebrow"><?php echo $e($card['label']); ?></div>
                            <div class="adminQuickLinkCard__title"><?php echo (int) $card['value']; ?></div>
                            <div class="adminQuickLinkCard__meta"><?php echo $e($card['meta']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Pipeline des demandes</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Repartition par statut</span>
                        </div>
                    </div>

                    <div class="adminDashboardStatus">
                        <div class="adminStatusPill">
                            <div class="adminStatusPill__label">En cours</div>
                            <div class="adminStatusPill__value"><?php echo (int) ($contactStats['in_progress_count'] ?? 0); ?></div>
                        </div>
                        <div class="adminStatusPill">
                            <div class="adminStatusPill__label">Devis envoyes</div>
                            <div class="adminStatusPill__value"><?php echo (int) ($contactStats['quoted_count'] ?? 0); ?></div>
                        </div>
                        <div class="adminStatusPill">
                            <div class="adminStatusPill__label">Finalisees</div>
                            <div class="adminStatusPill__value"><?php echo (int) ($contactStats['completed_count'] ?? 0); ?></div>
                        </div>
                        <div class="adminStatusPill">
                            <div class="adminStatusPill__label">Avec selection</div>
                            <div class="adminStatusPill__value"><?php echo (int) ($contactStats['with_menu_items'] ?? 0); ?></div>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Modules</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Basculer vers l'espace qui permet d'agir en profondeur</span>
                        </div>
                    </div>

                    <div class="adminDashboardQuickGrid adminDashboardQuickGrid--modules">
                        <?php foreach ($moduleCards as $card): ?>
                        <a href="<?php echo $e($card['href']); ?>" class="adminQuickLinkCard adminLink">
                            <div class="adminQuickLinkCard__eyebrow"><?php echo $e($card['eyebrow']); ?></div>
                            <div class="adminQuickLinkCard__title"><?php echo $e($card['title']); ?></div>
                            <div class="adminQuickLinkCard__meta"><?php echo $e($card['meta']); ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Types d'evenements</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Top des demandes</span>
                        </div>
                    </div>

                    <?php if ($typeBreakdown === []): ?>
                    <div class="adminEmptyState">Aucune repartition disponible pour l'instant.</div>
                    <?php else: ?>
                    <div class="adminTypeList">
                        <?php foreach ($typeBreakdown as $typeRow): ?>
                        <?php $width = $maxTypeCount > 0 ? ((int) ($typeRow['total'] ?? 0) / $maxTypeCount) * 100 : 0; ?>
                        <div class="adminTypeRow">
                            <div class="adminTypeRow__label"><?php echo $e($typeRow['label'] ?? 'Non renseigne'); ?></div>
                            <div class="adminTypeRow__bar">
                                <div class="adminTypeRow__fill" style="width: <?php echo number_format($width, 2, '.', ''); ?>%;"></div>
                            </div>
                            <div class="adminTypeRow__value"><?php echo (int) ($typeRow['total'] ?? 0); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
</div>