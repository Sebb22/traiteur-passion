<?php
    $contactStats        = is_array($contactStats ?? null) ? $contactStats : [];
    $blogStats           = is_array($blogStats ?? null) ? $blogStats : [];
    $catalogStats        = is_array($catalogStats ?? null) ? $catalogStats : [];
    $orderStats          = is_array($orderStats ?? null) ? $orderStats : [];
    $orderStatusLabels   = is_array($orderStatusLabels ?? null) ? $orderStatusLabels : [];
    $shopStats           = is_array($shopStats ?? null) ? $shopStats : [];
    $recentContacts      = is_array($recentContacts ?? null) ? $recentContacts : [];
    $contactResultsCount = (int) ($contactResultsCount ?? count($recentContacts));
    $clientResults       = is_array($clientResults ?? null) ? $clientResults : [];
    $clientResultsCount  = (int) ($clientResultsCount ?? count($clientResults));
    $recentOrders        = is_array($recentOrders ?? null) ? $recentOrders : [];
    $orderResultsCount   = (int) ($orderResultsCount ?? count($recentOrders));
    $typeBreakdown       = is_array($typeBreakdown ?? null) ? $typeBreakdown : [];
    $shopLoadError       = is_string($shopLoadError ?? null) ? $shopLoadError : null;
    $shopPromo           = is_array($shopPromo ?? null) ? $shopPromo : [];
    $dashboardSearch     = is_array($dashboardSearch ?? null) ? $dashboardSearch : ['query' => '', 'active' => false, 'total_results' => 0];
    $flash               = is_array($flash ?? null) ? $flash : null;

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

    $getDeadlineMeta = static function ($value): ?array {
    $timestamp = strtotime(trim((string) $value));
    if ($timestamp === false) {
        return null;
    }

    $daysUntil = (int) floor(($timestamp - strtotime('today')) / 86400);
    if ($daysUntil < 0) {
        $daysOverdue = abs($daysUntil);
        return ['label' => 'Retard de ' . $daysOverdue . ' jour' . ($daysOverdue > 1 ? 's' : ''), 'state' => 'overdue'];
    }
    if ($daysUntil === 0) {
        return ['label' => 'Aujourd’hui', 'state' => 'today'];
    }
    if ($daysUntil === 1) {
        return ['label' => 'Demain', 'state' => 'soon'];
    }
    if ($daysUntil <= 7) {
        return ['label' => 'Dans ' . $daysUntil . ' jours', 'state' => 'soon'];
    }

    return null;
    };

    $actionTotal         = (int) ($contactStats['priority_count'] ?? 0) + (int) ($orderStats['priority_count'] ?? 0);
    $newTodayTotal       = (int) ($contactStats['new_today_count'] ?? 0) + (int) ($orderStats['new_today_count'] ?? 0);
    $searchQuery         = trim((string) ($dashboardSearch['query'] ?? ''));
    $isSearchActive      = ! empty($dashboardSearch['active']);
    $searchScope         = trim((string) ($dashboardSearch['scope'] ?? 'all'));
    $showContactsResults = ! empty($dashboardSearch['show_contacts']);
    $showOrderResults    = ! empty($dashboardSearch['show_orders']);
    $showClientResults   = ! empty($dashboardSearch['show_clients']);

    $kpiCards = [
    [
        'label' => 'Demandes à qualifier',
        'value' => (int) ($contactStats['new_count'] ?? 0),
        'href'  => '/admin/contacts?status=new',
    ],
    [
        'label' => 'Commandes à confirmer',
        'value' => (int) ($orderStats['new_count'] ?? 0),
        'href'  => '/admin/contacts#orders',
    ],
    [
        'label' => 'Événements sous 7 jours',
        'value' => (int) ($contactStats['events_next_7_days'] ?? 0),
        'href'  => '/admin/contacts?deadline=next_7_days',
    ],
    [
        'label' => 'Retraits / livraisons sous 7 jours',
        'value' => (int) ($orderStats['fulfillments_next_7_days'] ?? 0),
        'href'  => '/admin/contacts?deadline=next_7_days#orders',
    ],
    ];

    $businessCards = [
    [
        'label' => 'Catalogue visible',
        'value' => (int) ($catalogStats['items'] ?? 0),
        'meta'  => (int) ($catalogStats['sections'] ?? 0) . ' section(s)',
        'href'  => '/admin/catalog',
    ],
    [
        'label' => 'Stocks faibles',
        'value' => (int) ($shopStats['low_stock_items'] ?? 0),
        'meta'  => (int) ($shopStats['active_items'] ?? 0) . ' produit(s) actifs',
        'href'  => '/admin/boutique#low-stock',
    ],
    [
        'label' => 'Articles publies',
        'value' => (int) ($blogStats['published'] ?? 0),
        'meta'  => (int) ($blogStats['drafts'] ?? 0) . ' brouillon(s)',
        'href'  => '/admin/blog',
    ],
    [
        'label' => 'Dossiers qualifies',
        'value' => (int) ($contactStats['quoted_count'] ?? 0),
        'meta'  => (int) ($contactStats['completed_count'] ?? 0) . ' finalise(s)',
        'href'  => '/admin/contacts?status=quoted',
    ],
    ];

    $pipelineCards = [
    [
        'label' => 'En cours',
        'value' => (int) ($contactStats['in_progress_count'] ?? 0),
        'href'  => '/admin/contacts?status=in_progress',
    ],
    [
        'label' => 'Devis envoyés',
        'value' => (int) ($contactStats['quoted_count'] ?? 0),
        'href'  => '/admin/contacts?status=quoted',
    ],
    [
        'label' => 'Finalisées',
        'value' => (int) ($contactStats['completed_count'] ?? 0),
        'href'  => '/admin/contacts?status=completed',
    ],
    [
        'label' => 'Annulées',
        'value' => (int) ($contactStats['cancelled_count'] ?? 0),
        'href'  => '/admin/contacts?status=cancelled',
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
        <?php require dirname(__DIR__) . '/partials/admin-navigation.php'; ?>
        <header class="adminPanelHead adminPanelHead--dashboard">
            <div class="adminOverviewHero">
                <div class="adminOverviewHero__content">
                    <div class="adminOverviewHero__eyebrow">Tableau de bord</div>
                    <h1 class="adminTitle">À traiter</h1>
                </div>

                <div class="adminOverviewHero__summary">
                    <div class="adminOverviewHero__summaryLabel">Dossiers prioritaires</div>
                    <div class="adminOverviewHero__summaryValue"><?php echo $actionTotal; ?></div>
                    <div class="adminOverviewHero__summaryMeta">
                        <?php echo $newTodayTotal; ?> nouvelle(s) entrée(s) aujourd’hui •
                        <?php echo (int) ($contactStats['overdue_events'] ?? 0) + (int) ($orderStats['overdue_fulfillments'] ?? 0); ?> échéance(s) dépassée(s)
                    </div>
                </div>
            </div>

            <div class="adminPanelHead__actions adminPanelHead__actions--dashboard">
                <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                    <a href="/admin/contacts?status=new" class="adminBtn adminBtn--primary">Traiter les demandes</a>
                    <a href="/admin/contacts#orders" class="adminBtn">Suivre les commandes</a>
                    <a href="#admin-search" class="adminBtn">Rechercher un dossier</a>
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

        <section class="adminCard adminCard--padded" id="admin-search">
            <div class="adminCard__head">
                <div class="adminCard__title">Recherche admin</div>
                <div class="adminCard__meta">
                    <span
                        class="adminHint"><?php echo $isSearchActive ? (int) ($dashboardSearch['total_results'] ?? 0) . ' résultat(s) — aperçu limité à 8 par type' : 'Retrouver rapidement une demande, un client ou une commande'; ?></span>
                </div>
            </div>

            <form action="/admin" method="get" class="adminCatalogToolbar">
                <label class="adminField adminField--filter">
                    <span class="adminField__label">Recherche globale</span>
                    <input class="adminInput" type="search" name="q" value="<?php echo $e($searchQuery); ?>"
                        placeholder="Nom, email, telephone, lieu, message, ref commande...">
                </label>

                <label class="adminField adminField--filter">
                    <span class="adminField__label">Perimetre</span>
                    <select class="adminSelect" name="scope">
                        <option value="all" <?php echo $searchScope === 'all' ? 'selected' : ''; ?>>Tout</option>
                        <option value="contacts" <?php echo $searchScope === 'contacts' ? 'selected' : ''; ?>>Demandes /
                            devis</option>
                        <option value="orders" <?php echo $searchScope === 'orders' ? 'selected' : ''; ?>>Commandes
                        </option>
                        <option value="clients" <?php echo $searchScope === 'clients' ? 'selected' : ''; ?>>Clients
                        </option>
                    </select>
                </label>

                <div class="adminInlineActions adminInlineActions--filters">
                    <button type="submit" class="adminBtn adminBtn--primary">Rechercher</button>
                    <?php if ($isSearchActive): ?>
                    <a href="/admin" class="adminBtn">Effacer</a>
                    <a href="/admin/contacts?<?php echo $e(http_build_query(['q' => $searchQuery])); ?>"
                        class="adminBtn">Voir les demandes</a>
                    <a href="/admin/contacts?<?php echo $e(http_build_query(['q' => $searchQuery])); ?>#orders"
                        class="adminBtn">Voir les commandes</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <div class="adminDashboardGrid adminDashboardGrid--triage">
            <div class="adminDashboardStack">
                <?php if ($showClientResults): ?>
                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Clients correspondants</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo $clientResultsCount; ?> resultat(s) cotes clients
                                consolides</span>
                        </div>
                    </div>

                    <?php if ($clientResults === []): ?>
                    <div class="adminEmptyState">Aucun client consolide pour cette recherche.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($clientResults as $client): ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="<?php echo $e($client['client_link'] ?? '/admin'); ?>"
                                        class="adminLink"><?php echo $e($client['name'] ?? 'Client'); ?></a>
                                    <?php if (! empty($client['order_reference'])): ?>
                                    <span
                                        class="adminBadge adminBadge--quoted"><?php echo $e($client['order_reference']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e(($client['email'] ?? '') !== '' ? $client['email'] : 'Email non renseigne'); ?>
                                    •
                                    <?php echo $e(($client['phone'] ?? '') !== '' ? $client['phone'] : 'Telephone non renseigne'); ?>
                                    •
                                    <?php echo $e(($client['location'] ?? '') !== '' ? $client['location'] : 'Lieu non renseigne'); ?>
                                </div>
                            </div>

                            <div class="adminMiniList__aside">
                                <?php echo (int) ($client['contacts_count'] ?? 0); ?> demande(s) •
                                <?php echo (int) ($client['orders_count'] ?? 0); ?> commande(s)<br>
                                Derniere activite
                                <?php echo $formatDate($client['last_activity'] ?? null, 'd/m/Y H:i'); ?>
                                <div class="adminMiniList__actions">
                                    <?php if (! empty($client['client_link'])): ?>
                                    <a href="<?php echo $e($client['client_link']); ?>"
                                        class="adminBtn adminBtn--sm">Voir fiche client</a>
                                    <?php endif; ?>
                                    <?php if (! empty($client['contact_link'])): ?>
                                    <a href="<?php echo $e($client['contact_link']); ?>"
                                        class="adminBtn adminBtn--sm">Voir demande</a>
                                    <?php endif; ?>
                                    <?php if (! empty($client['order_link'])): ?>
                                    <a href="<?php echo $e($client['order_link']); ?>"
                                        class="adminBtn adminBtn--sm">Voir commande</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($showContactsResults): ?>
                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">
                            <?php echo $isSearchActive ? 'Demandes et devis correspondants' : 'Demandes à traiter'; ?>
                        </div>
                        <div class="adminCard__meta">
                            <span
                                class="adminHint"><?php echo $isSearchActive ? $contactResultsCount . ' résultat(s) côté demandes / devis — aperçu limité à 8' : 'Dossiers ouverts classés par échéance, puis par date de réception'; ?></span>
                        </div>
                    </div>

                    <?php if ($recentContacts === []): ?>
                    <div class="adminEmptyState">
                        <?php echo $isSearchActive ? 'Aucun resultat cote demandes / devis pour cette recherche.' : 'Aucune demande enregistre pour le moment.'; ?>
                    </div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($recentContacts as $contact): ?>
                        <?php $contactDeadline = $getDeadlineMeta($contact['date'] ?? null); ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>"
                                        class="adminLink">
                                        <?php echo $e($contact['name'] ?? 'Demande'); ?>
                                    </a>
                                    <span class="adminBadge adminBadge--<?php echo $e($contact['status'] ?? 'new'); ?>">
                                        <?php echo $e(ucfirst((string) ($contact['status'] ?? 'new'))); ?>
                                    </span>
                                    <?php if ($contactDeadline !== null): ?>
                                    <span class="adminDeadlineBadge adminDeadlineBadge--<?php echo $e($contactDeadline['state']); ?>">
                                        <?php echo $e($contactDeadline['label']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($contact['email'] ?? ''); ?>
                                    • <?php echo $e($contact['type'] ?? 'Type non renseigne'); ?>
                                    • Événement le <?php echo $formatDate($contact['date'] ?? null); ?>
                                </div>
                            </div>

                            <div class="adminMiniList__aside">
                                <?php echo (int) ($contact['menu_items_count'] ?? 0); ?> selection(s) carte<br>
                                Creee le <?php echo $formatDate($contact['created_at'] ?? null, 'd/m/Y H:i'); ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>"
                                        class="adminBtn adminBtn--sm">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($showOrderResults): ?>
                <section class="adminCard adminCard--table" id="orders">
                    <div class="adminCard__head">
                        <div class="adminCard__title">
                            <?php echo $isSearchActive ? 'Commandes boutique correspondantes' : 'Commandes à traiter'; ?>
                        </div>
                        <div class="adminCard__meta">
                            <span
                                class="adminHint"><?php echo $isSearchActive ? $orderResultsCount . ' résultat(s) côté commandes — aperçu limité à 8' : 'Commandes ouvertes classées par échéance de retrait ou livraison'; ?></span>
                        </div>
                    </div>

                    <?php if ($shopLoadError !== null): ?>
                    <div class="adminEmptyState"><?php echo $e($shopLoadError); ?></div>
                    <?php elseif ($recentOrders === []): ?>
                    <div class="adminEmptyState">
                        <?php echo $isSearchActive ? 'Aucun resultat cote commandes boutique pour cette recherche.' : 'Aucune commande boutique pour le moment.'; ?>
                    </div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($recentOrders as $order): ?>
                        <?php
                            $orderDiscountCents = max(0, (int) ($order['discount_cents'] ?? 0));
                            $orderSubtotalCents = max(0, (int) ($order['subtotal_cents'] ?? $order['total_cents'] ?? 0));
                            $orderPromoCode     = trim((string) ($order['promo_code'] ?? ''));
                            $orderReference     = trim((string) ($order['order_reference'] ?? ''));
                            $orderDeadline      = $getDeadlineMeta($order['pickup_date'] ?? null);
                            if ($orderReference === '') {
                                $orderReference = '#' . (int) ($order['id'] ?? 0);
                            }
                        ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>"
                                        class="adminLink">
                                        <?php echo $e($orderReference); ?> ·
                                        <?php echo $e($order['customer_name'] ?? 'Commande'); ?>
                                    </a>
                                    <span class="adminBadge adminBadge--<?php echo $e($order['status'] ?? 'new'); ?>">
                                        <?php echo $e($orderStatusLabels[(string) ($order['status'] ?? 'new')] ?? (string) ($order['status'] ?? 'new')); ?>
                                    </span>
                                    <?php if ($orderDeadline !== null): ?>
                                    <span class="adminDeadlineBadge adminDeadlineBadge--<?php echo $e($orderDeadline['state']); ?>">
                                        <?php echo $e($orderDeadline['label']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($order['customer_email'] ?? '-'); ?>
                                    •
                                    <?php echo $e(($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait'); ?>
                                    • prévu le <?php echo $formatDate($order['pickup_date'] ?? null); ?>
                                </div>
                            </div>

                            <div class="adminMiniList__aside">
                                <?php echo (int) ($order['item_count'] ?? 0); ?> article(s)<br>
                                Total <?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?>
                                <?php if ($orderDiscountCents > 0): ?><br>
                                <span class="adminPromoState">
                                    Promo <?php echo $e($orderPromoCode !== '' ? $orderPromoCode : 'appliquee'); ?> ·
                                    -<?php echo $e($formatPrice($orderDiscountCents)); ?>
                                </span><br>
                                <span class="adminHint">Sous-total
                                    <?php echo $e($formatPrice($orderSubtotalCents)); ?></span>
                                <?php endif; ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>"
                                        class="adminBtn adminBtn--sm">Voir commande</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>

            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head" id="shop-promo">
                        <div class="adminCard__title">Promotion</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Boutique</span>
                        </div>
                    </div>

                    <div class="adminDashboardPromoStatus">
                        <span
                            class="adminBadge adminBadge--<?php echo $e($promoStatus === 'active' ? 'completed' : ($promoStatus === 'scheduled' ? 'quoted' : 'new')); ?>">
                            <?php echo $e($promoStatusLabels[$promoStatus] ?? 'Inactive'); ?>
                        </span>
                        <div class="adminDashboardPromoMeta">
                            <strong><?php echo $e($shopPromo['title'] ?? 'Offre de lancement'); ?></strong>
                            <span>Code <?php echo $e($shopPromo['promo_code'] ?? '-'); ?> •
                                -<?php echo (int) ($shopPromo['discount_percent'] ?? 0); ?>%</span>
                            <span>Du <?php echo $formatDate($shopPromo['starts_at'] ?? null, 'd/m/Y H:i'); ?> au
                                <?php echo $formatDate($shopPromo['ends_at'] ?? null, 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>

                    <details class="adminDisclosure" id="shop-promo">
                        <summary class="adminDisclosure__summary">Modifier la promotion</summary>
                        <form action="/admin/dashboard/shop-promo" method="post" class="adminForm adminInlineForm--stack adminDisclosure__content">
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
                            <button type="submit" class="adminBtn adminBtn--primary">Enregistrer la promo
                                boutique</button>
                        </div>
                        </form>
                    </details>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Activité</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Catalogue, stock, blog et dossiers</span>
                        </div>
                    </div>

                    <div class="adminDashboardQuickGrid">
                        <?php foreach ($businessCards as $card): ?>
                        <a href="<?php echo $e($card['href']); ?>" class="adminQuickLinkCard adminQuickLinkCard--metric adminLink">
                            <div class="adminQuickLinkCard__eyebrow"><?php echo $e($card['label']); ?></div>
                            <div class="adminQuickLinkCard__title"><?php echo (int) $card['value']; ?></div>
                            <div class="adminQuickLinkCard__meta"><?php echo $e($card['meta']); ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Demandes</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Par statut</span>
                        </div>
                    </div>

                    <div class="adminDashboardStatus">
                        <?php foreach ($pipelineCards as $card): ?>
                        <a href="<?php echo $e($card['href']); ?>" class="adminStatusPill adminStatusPill--link">
                            <div class="adminStatusPill__label"><?php echo $e($card['label']); ?></div>
                            <div class="adminStatusPill__value"><?php echo (int) $card['value']; ?></div>
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
                            <div class="adminTypeRow__label"><?php echo $e($typeRow['label'] ?? 'Non renseigne'); ?>
                            </div>
                            <div class="adminTypeRow__bar">
                                <div class="adminTypeRow__fill"
                                    style="width: <?php echo number_format($width, 2, '.', ''); ?>%;"></div>
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