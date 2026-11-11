<?php
    $contacts               = isset($contacts) && is_array($contacts) ? $contacts : [];
    $stats                  = isset($stats) && is_array($stats) ? $stats : [];
    $filters                = isset($filters) && is_array($filters) ? $filters : ['status' => '', 'deadline' => '', 'q' => ''];
    $statusOptions          = isset($statusOptions) && is_array($statusOptions) ? $statusOptions : [];
    $orderStatusOptions     = isset($orderStatusOptions) && is_array($orderStatusOptions) ? $orderStatusOptions : [];
    $paymentStatusOptions   = isset($paymentStatusOptions) && is_array($paymentStatusOptions) ? $paymentStatusOptions : [];
    $csrfToken              = is_string($csrfToken ?? null) ? $csrfToken : '';
    $paymentStatusFilter    = is_string($paymentStatusFilter ?? null) ? $paymentStatusFilter : '';
    $orderStats             = isset($orderStats) && is_array($orderStats) ? $orderStats : [];
    $recentOrders           = isset($recentOrders) && is_array($recentOrders) ? $recentOrders : [];
    $orderLoadError         = isset($orderLoadError) && is_string($orderLoadError) ? $orderLoadError : null;
    $filteredCount          = (int) ($filteredCount ?? count($contacts));
    $ordersCount            = (int) ($ordersCount ?? count($recentOrders));
    $displayedContactsCount = count($contacts);
    $displayedOrdersCount   = count($recentOrders);
    $pagination             = isset($pagination) && is_array($pagination) ? $pagination : [];
    $contactsPage           = max(1, (int) ($pagination['contacts_page'] ?? 1));
    $contactsPageCount      = max(1, (int) ($pagination['contacts_page_count'] ?? 1));
    $ordersPage             = max(1, (int) ($pagination['orders_page'] ?? 1));
    $ordersPageCount        = max(1, (int) ($pagination['orders_page_count'] ?? 1));
    $activeView             = trim((string) ($view ?? 'contacts')) === 'orders' ? 'orders' : 'contacts';
    $isOrdersView           = $activeView === 'orders';
    $flash                  = isset($flash) && is_array($flash) ? $flash : null;

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

    $activeStatus    = trim((string) ($filters['status'] ?? ''));
    $activeDeadline  = trim((string) ($filters['deadline'] ?? ''));
    $searchQuery     = trim((string) ($filters['q'] ?? ''));
    $deadlineOptions = [
    ''            => 'Toutes les échéances',
    'overdue'     => 'Échéances dépassées',
    'today'       => 'Aujourd’hui',
    'next_7_days' => 'Dans les 7 prochains jours',
    ];
    $redirectFilters = [
    'status'         => $activeStatus,
    'deadline'       => $activeDeadline,
    'q'              => $searchQuery,
    'contacts_page'  => $contactsPage > 1 ? $contactsPage : '',
    'orders_page'    => $ordersPage > 1 ? $ordersPage : '',
    'payment_status' => $paymentStatusFilter,
    'view'           => $activeView,
    ];
    $redirectFilters = array_filter($redirectFilters, static function ($value): bool {
    return $value !== '';
    });
    $contactRedirect = '/admin/contacts' . ($redirectFilters !== [] ? '?' . http_build_query($redirectFilters) : '');

    $getPageUrl = static function (string $pageKey, int $page) use ($activeStatus, $activeDeadline, $searchQuery, $contactsPage, $ordersPage, $activeView, $paymentStatusFilter): string {
    $query = [
        'status'         => $activeStatus,
        'deadline'       => $activeDeadline,
        'q'              => $searchQuery,
        'contacts_page'  => $contactsPage > 1 ? $contactsPage : '',
        'orders_page'    => $ordersPage > 1 ? $ordersPage : '',
        'view'           => $activeView,
        'payment_status' => $paymentStatusFilter,
    ];
    $query[$pageKey] = max(1, $page);
    $query           = array_filter($query, static function ($value): bool {
        return $value !== '';
    });

    return '/admin/contacts?' . http_build_query($query);
    };

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
?>
<div class="adminSplit adminSplit--contacts">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Demandes</h1>
            <p class="adminMediaTitle__sub">Contacts • devis • commandes</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <?php require dirname(__DIR__) . '/partials/admin-navigation.php'; ?>
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle"><?php echo $isOrdersView ? 'Commandes boutique' : 'Demandes et devis'; ?></h2>
                    <p class="adminSubtitle"><?php echo $isOrdersView ? 'Suivez les retraits, livraisons et préparations à venir.' : 'Traitez les nouveaux besoins, devis et relances clients.'; ?></p>
                </div>
                <div class="adminPanelHead__actions">
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                        <a href="/admin/contacts?view=<?php echo $e($activeView); ?>&amp;deadline=overdue" class="adminBtn adminBtn--primary">Échéances dépassées</a>
                        <?php if (! $isOrdersView): ?>
                        <a href="/admin/contacts/export" class="adminBtn">Exporter CSV</a>
                        <?php endif; ?>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--utility">
                        <a href="/" class="adminBtn">Retour au site</a>
                        <form action="/admin/logout" method="post">
                            <button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>">
            <?php echo $e($flash['message'] ?? 'Modification enregistrée.'); ?>
        </div>
        <?php endif; ?>

        <section class="adminStats adminStats--panel" aria-label="Statistiques">
            <?php if (! $isOrdersView): ?>
            <div class="statCard">
                <div class="statCard__label">Total</div>
                <div class="statCard__value"><?php echo (int) ($stats['total'] ?? 0); ?></div>
            </div>

            <div class="statCard">
                <div class="statCard__label">Nouvelles</div>
                <div class="statCard__value"><?php echo (int) ($stats['new_count'] ?? 0); ?></div>
            </div>

            <div class="statCard">
                <div class="statCard__label">Devis envoyés</div>
                <div class="statCard__value"><?php echo (int) ($stats['quoted_count'] ?? 0); ?></div>
            </div>

            <div class="statCard">
                <div class="statCard__label">Événements à venir</div>
                <div class="statCard__value"><?php echo (int) ($stats['upcoming_events'] ?? 0); ?></div>
            </div>
            <?php else: ?>
            <div class="statCard">
                <div class="statCard__label">À traiter</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['priority_count'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Nouvelles</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['new_count'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">À préparer</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['preparing_count'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Prêtes</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['ready_count'] ?? 0); ?></div>
            </div>
            <?php endif; ?>
        </section>

        <section class="adminCatalogUtilityGrid" aria-label="Outils demandes, devis et commandes">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Filtres de suivi</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo $isOrdersView ? $displayedOrdersCount . ' commande(s) affichée(s) sur ' . $ordersCount : $displayedContactsCount . ' demande(s) affichée(s) sur ' . $filteredCount; ?></span>
                    </div>
                </div>

                <form action="/admin/contacts" method="get" class="adminCatalogToolbar">
                    <input type="hidden" name="view" value="<?php echo $e($activeView); ?>">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" name="q" value="<?php echo $e($searchQuery); ?>"
                            placeholder="<?php echo $isOrdersView ? 'Référence, nom, email...' : 'Nom, email, téléphone, lieu...'; ?>">
                    </label>

                    <?php if (! $isOrdersView): ?>
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Statut</span>
                        <select class="adminSelect" name="status">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                            <option value="<?php echo $e($statusKey); ?>"
                                <?php echo $activeStatus === (string) $statusKey ? 'selected' : ''; ?>>
                                <?php echo $e($statusLabel); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php endif; ?>

                    <?php if ($isOrdersView): ?>
                        <label class="adminField adminField--filter">
                            <span class="adminField__label">Statut commande</span>
                            <select class="adminSelect" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($orderStatusOptions as $statusKey => $statusLabel): ?>
                                <option value="<?php echo $e($statusKey); ?>" <?php echo $activeStatus === (string) $statusKey ? 'selected' : ''; ?>><?php echo $e($statusLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Règlement</span>
                        <select class="adminSelect" name="payment_status">
                            <option value="">Tous les règlements</option>
                            <?php foreach ($paymentStatusOptions as $paymentKey => $paymentLabel): ?>
                            <option value="<?php echo $e($paymentKey); ?>" <?php echo $paymentStatusFilter === (string) $paymentKey ? 'selected' : ''; ?>><?php echo $e($paymentLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php endif; ?>

                    <label class="adminField adminField--filter">
                        <span class="adminField__label"><?php echo $isOrdersView ? 'Échéance de retrait / livraison' : 'Échéance de l’événement'; ?></span>
                        <select class="adminSelect" name="deadline">
                            <?php foreach ($deadlineOptions as $deadlineKey => $deadlineLabel): ?>
                            <option value="<?php echo $e($deadlineKey); ?>"
                                <?php echo $activeDeadline === (string) $deadlineKey ? 'selected' : ''; ?>>
                                <?php echo $e($deadlineLabel); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="adminInlineActions adminInlineActions--filters">
                        <button type="submit" class="adminBtn adminBtn--primary">Filtrer</button>
                        <a href="/admin/contacts?view=<?php echo $e($activeView); ?>" class="adminBtn">Réinitialiser</a>
                    </div>
                </form>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard adminContactsPipeline">
                <div class="adminCard__head">
                    <div class="adminCard__title"><?php echo $isOrdersView ? 'Préparation des commandes' : 'Suivi des demandes'; ?></div>
                    <div class="adminCard__meta">
                        <span class="adminHint">État des dossiers ouverts</span>
                    </div>
                </div>

                <div class="adminDashboardStatus adminDashboardStatus--compact">
                    <?php if (! $isOrdersView): ?>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">En cours</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['in_progress_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Devis envoyés</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['quoted_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Terminées</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['completed_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Annulées</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['cancelled_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Commandes nouvelles</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['new_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Commandes prêtes</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['ready_count'] ?? 0); ?></div>
                    </div>
                    <?php else: ?>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Nouvelles</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['new_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">À préparer</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['preparing_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Prêtes</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['ready_count'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">À échéance</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['priority_count'] ?? 0); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <?php if ($activeView === 'orders'): ?>
        <section class="adminCard adminCard--table" id="orders">
            <div class="adminCard__head">
                <div class="adminCard__title">Commandes boutique</div>
                <div class="adminCard__meta">
                    <span class="adminHint"><?php echo $displayedOrdersCount; ?> commande(s) affichée(s) sur <?php echo $ordersCount; ?> correspondante(s)</span>
                </div>
            </div>

            <?php if ($orderLoadError !== null): ?>
            <div class="adminEmptyState"><?php echo $e($orderLoadError); ?></div>
            <?php elseif ($recentOrders === []): ?>
            <div class="adminEmptyState">
                Aucune commande ne correspond aux critères actuels.
                <a href="/admin/boutique/orders" class="adminLink">Réinitialiser les filtres</a>
            </div>
            <?php else: ?>
            <div class="adminTableWrap">
                <table class="adminTable">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Retrait</th>
                            <th>Volume</th>
                            <th>Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <?php
                            $orderDiscountCents = max(0, (int) ($order['discount_cents'] ?? 0));
                            $orderSubtotalCents = max(0, (int) ($order['subtotal_cents'] ?? $order['total_cents'] ?? 0));
                            $orderPromoCode     = trim((string) ($order['promo_code'] ?? ''));
                            $orderReference     = trim((string) ($order['order_reference'] ?? ''));
                            if ($orderReference === '') {
                                $orderReference = '#' . (int) ($order['id'] ?? 0);
                            }
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>?redirect=<?php echo rawurlencode($contactRedirect); ?>" class="adminLink">
                                        <?php echo $e($orderReference); ?> · <?php echo $e($order['customer_name'] ?? ''); ?>
                                    </a>
                                </strong><br>
                                <a href="mailto:<?php echo $e($order['customer_email'] ?? ''); ?>" class="adminLink">
                                    <?php echo $e($order['customer_email'] ?? '-'); ?>
                                </a><br>
                                <span class="adminHint">Créée le <?php echo $formatDate($order['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
                            </td>
                            <td>
                                <strong><?php echo $e(($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait'); ?></strong><br>
                                <?php echo $formatDate($order['pickup_date'] ?? null); ?><br>
                                <span class="adminHint"><?php echo $e($order['pickup_slot'] ?? '-'); ?></span>
                                <?php if (($order['fulfillment_method'] ?? 'pickup') === 'delivery'): ?><br>
                                <span class="adminHint"><?php echo $e(trim((string) (($order['delivery_postal_code'] ?? '') . ' ' . ($order['delivery_city'] ?? '')))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int) ($order['item_count'] ?? 0); ?> article(s)</td>
                            <td>
                                <?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?>
                                <?php if ($orderDiscountCents > 0): ?><br>
                                <span class="adminPromoState">
                                    Promo <?php echo $e($orderPromoCode !== '' ? $orderPromoCode : 'appliquee'); ?> · -<?php echo $e($formatPrice($orderDiscountCents)); ?>
                                </span><br>
                                <span class="adminHint">Sous-total <?php echo $e($formatPrice($orderSubtotalCents)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>/status" method="post">
                                    <input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo $e($contactRedirect); ?>">
                                    <select class="adminSelect" name="status">
                                        <?php foreach ($orderStatusOptions as $statusKey => $statusLabel): ?>
                                        <option value="<?php echo $e($statusKey); ?>"
                                            <?php echo (string) ($order['status'] ?? '') === (string) $statusKey ? 'selected' : ''; ?>>
                                            <?php echo $e($statusLabel); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="adminSelect" name="payment_status">
                                        <?php foreach ($paymentStatusOptions as $paymentKey => $paymentLabel): ?>
                                        <option value="<?php echo $e($paymentKey); ?>" <?php echo (string) ($order['payment_status'] ?? 'unpaid') === (string) $paymentKey ? 'selected' : ''; ?>><?php echo $e($paymentLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="adminInlineActions adminInlineActions--orderQuick">
                                        <button type="submit" class="adminBtn adminBtn--sm">Enregistrer</button>
                                        <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>?redirect=<?php echo rawurlencode($contactRedirect); ?>" class="adminBtn">Ouvrir</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($ordersPageCount > 1): ?>
            <nav class="adminPagination" aria-label="Pages des commandes">
                <span class="adminPagination__label">Page <?php echo $ordersPage; ?> sur <?php echo $ordersPageCount; ?></span>
                <div class="adminPagination__actions">
                    <?php if ($ordersPage > 1): ?>
                    <a href="<?php echo $e($getPageUrl('orders_page', $ordersPage - 1)); ?>#orders" class="adminBtn adminBtn--sm">Précédente</a>
                    <?php endif; ?>
                    <?php if ($ordersPage < $ordersPageCount): ?>
                    <a href="<?php echo $e($getPageUrl('orders_page', $ordersPage + 1)); ?>#orders" class="adminBtn adminBtn--sm">Suivante</a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>
        </section>

        <?php endif; ?>

        <?php if ($activeView === 'contacts'): ?>
        <section class="adminCard adminCard--table">
            <div class="adminCard__head">
                <div class="adminCard__title">Liste des demandes et devis</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Ouvrez un dossier pour lire le détail complet ou personnaliser visuellement le mail client avant envoi.</span>
                </div>
            </div>

            <?php if ($contacts !== []): ?>
            <form action="/admin/contacts/status" method="post" class="adminBulkActions" id="bulk-contact-status-form" data-bulk-contact-status>
                <input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>">
                <input type="hidden" name="redirect" value="<?php echo $e($contactRedirect); ?>">
                <label class="adminSelectionControl">
                    <input type="checkbox" class="adminCheckbox" data-select-all-contacts>
                    <span>Tout sélectionner sur cette page</span>
                </label>
                <span class="adminHint" data-contact-selection-count>0 dossier sélectionné</span>
                <label class="adminField adminField--filter">
                    <span class="adminField__label">Statut pour les dossiers sélectionnés</span>
                    <select class="adminSelect" name="status" required>
                        <option value="">Choisir un statut</option>
                        <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                        <option value="<?php echo $e($statusKey); ?>"><?php echo $e($statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="adminBtn adminBtn--primary">Mettre à jour la sélection</button>
                <span class="adminHint">Aucun message client n’est envoyé avec cette action.</span>
            </form>
            <?php endif; ?>

            <div class="adminRequestList" role="list" aria-label="Liste des demandes">
                <?php if (empty($contacts)): ?>
                <div class="adminEmptyState">
                    Aucune demande ou devis ne correspond aux critères actuels.
                    <a href="/admin/contacts" class="adminLink">Réinitialiser les filtres</a>
                </div>
                <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                <?php
                    $contactId       = (int) ($contact['id'] ?? 0);
                    $statusKey       = (string) ($contact['status'] ?? 'new');
                    $customerAddress = trim(implode(', ', array_filter([
                        trim((string) ($contact['customer_address'] ?? '')),
                        trim((string) (($contact['customer_postal_code'] ?? '') . ' ' . ($contact['customer_city'] ?? ''))),
                    ])));
                    $contactDeadline = $getDeadlineMeta($contact['date'] ?? null);
                ?>
                <article class="adminRequestCard" role="listitem">
                    <div class="adminRequestCard__head">
                        <div class="adminRequestCard__titleWrap">
                            <div class="adminRequestCard__eyebrow">Dossier #<?php echo $contactId; ?></div>
                            <a href="/admin/contacts/<?php echo $contactId; ?>?redirect=<?php echo rawurlencode($contactRedirect); ?>" class="adminRequestCard__title adminLink">
                                <?php echo $e($contact['name'] ?? 'Demande'); ?>
                            </a>
                        </div>
                        <span class="adminBadge adminBadge--<?php echo $e($statusKey); ?>">
                            <?php echo $e($statusOptions[$statusKey] ?? ucfirst($statusKey)); ?>
                        </span>
                        <?php if ($contactDeadline !== null): ?>
                        <span class="adminDeadlineBadge adminDeadlineBadge--<?php echo $e($contactDeadline['state']); ?>">
                            <?php echo $e($contactDeadline['label']); ?>
                        </span>
                        <?php endif; ?>
                        <label class="adminSelectionControl">
                            <input type="checkbox" class="adminCheckbox" name="contact_ids[]" value="<?php echo $contactId; ?>"
                                form="bulk-contact-status-form">
                            <span>Sélectionner</span>
                        </label>
                    </div>

                    <div class="adminRequestCard__grid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Email</span>
                            <span class="adminInfoItem__value">
                                <a href="mailto:<?php echo $e($contact['email'] ?? ''); ?>" class="adminLink">
                                    <?php echo $e($contact['email'] ?? '-'); ?>
                                </a>
                            </span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Téléphone</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['phone'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date événement</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($contact['date'] ?? null); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Type</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['type'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Adresse client</span>
                            <span class="adminInfoItem__value"><?php echo $e($customerAddress !== '' ? $customerAddress : '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Personnes</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['people'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Créée le</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($contact['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>

                    <div class="adminRequestCard__message">
                        <span class="adminInfoItem__label">Message</span>
                        <p class="adminRequestCard__messageText"><?php echo $e($contact['message'] ?? ''); ?></p>
                    </div>

                    <div class="adminRequestCard__actions">
                        <form action="/admin/contacts/<?php echo $contactId; ?>/status"
                            method="post" class="adminInlineForm adminInlineForm--stack adminInlineForm--full">
                            <input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>">
                            <input type="hidden" name="redirect"
                                value="<?php echo $e($contactRedirect); ?>">
                            <div class="adminInlineForm adminInlineForm--requestStatus">
                                <select name="status" class="adminSelect adminSelect--compact">
                                    <?php foreach ($statusOptions as $statusKeyOption => $statusLabel): ?>
                                    <option value="<?php echo $e($statusKeyOption); ?>"
                                        <?php echo $statusKey === $statusKeyOption ? 'selected' : ''; ?>>
                                        <?php echo $e($statusLabel); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="adminBtn adminBtn--sm">Mettre à jour</button>
                                <details class="adminInlineNotification">
                                    <summary>Notifier le client</summary>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Envoyer un mail</span>
                                        <input type="checkbox" class="adminCheckbox" name="notify_client" value="1">
                                    </label>
                                    <input type="text" class="adminInput" name="client_message"
                                        placeholder="Message optionnel au client">
                                </details>
                            </div>
                        </form>

                        <a href="/admin/contacts/<?php echo $contactId; ?>?redirect=<?php echo rawurlencode($contactRedirect); ?>" class="adminBtn">Ouvrir le dossier</a>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($contactsPageCount > 1): ?>
            <nav class="adminPagination" aria-label="Pages des demandes et devis">
                <span class="adminPagination__label">Page <?php echo $contactsPage; ?> sur <?php echo $contactsPageCount; ?></span>
                <div class="adminPagination__actions">
                    <?php if ($contactsPage > 1): ?>
                    <a href="<?php echo $e($getPageUrl('contacts_page', $contactsPage - 1)); ?>" class="adminBtn adminBtn--sm">Précédente</a>
                    <?php endif; ?>
                    <?php if ($contactsPage < $contactsPageCount): ?>
                    <a href="<?php echo $e($getPageUrl('contacts_page', $contactsPage + 1)); ?>" class="adminBtn adminBtn--sm">Suivante</a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>
</div>