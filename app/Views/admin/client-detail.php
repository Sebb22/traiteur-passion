<?php
    $client        = isset($client) && is_array($client) ? $client : [];
    $contacts      = isset($contacts) && is_array($contacts) ? $contacts : [];
    $orders        = isset($orders) && is_array($orders) ? $orders : [];
    $timeline      = isset($timeline) && is_array($timeline) ? $timeline : [];
    $clientView    = isset($clientView) && is_array($clientView) ? $clientView : ['active' => 'all', 'show_contacts' => true, 'show_orders' => true, 'is_recent' => false];
    $orderStatuses = isset($orderStatuses) && is_array($orderStatuses) ? $orderStatuses : [];
    $flash         = isset($flash) && is_array($flash) ? $flash : null;
    $latestContact = isset($client['latest_contact']) && is_array($client['latest_contact']) ? $client['latest_contact'] : null;
    $latestOrder   = isset($client['latest_order']) && is_array($client['latest_order']) ? $client['latest_order'] : null;
    $actions       = isset($client['actions']) && is_array($client['actions']) ? $client['actions'] : [];
    $activeView    = trim((string) ($clientView['active'] ?? 'all'));
    $showContacts  = ! empty($clientView['show_contacts']);
    $showOrders    = ! empty($clientView['show_orders']);
    $isRecentView  = ! empty($clientView['is_recent']);
    $viewQueryBase = array_filter([
    'email' => trim((string) ($client['email'] ?? '')),
    'phone' => trim((string) ($client['phone'] ?? '')),
    ], static fn(string $value): bool => $value !== '');

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatDate = static function ($value, string $format = 'd/m/Y H:i'): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }

    $timestamp = strtotime($raw);

    return $timestamp !== false ? date($format, $timestamp) : '-';
    };

    $formatPrice = static function ($value): string {
    return number_format(max(0, (int) $value) / 100, 2, ',', ' ') . ' €';
    };
?>
<div class="adminSplit adminSplit--contact-detail">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1"><?php echo $e($client['name'] ?? 'Client'); ?></h1>
            <p class="adminMediaTitle__sub">Client • vue agrégée contacts & commandes</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Fiche client agrégée</h2>
                    <p class="adminSubtitle">Regroupement des demandes, devis et commandes boutique à partir de l’identité client connue.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                        <a href="/admin" class="adminBtn adminBtn--primary">Retour au dashboard</a>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--modules">
                        <a href="/admin/contacts" class="adminBtn">Demandes & commandes</a>
                        <a href="/admin/boutique" class="adminBtn">Boutique</a>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--utility">
                        <form action="/admin/logout" method="post">
                            <button type="submit" class="adminBtn adminBtn--danger">Deconnexion</button>
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

        <div class="adminDetailGrid">
            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Identité</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Nom</span>
                            <span class="adminInfoItem__value"><?php echo $e($client['name'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Email</span>
                            <span class="adminInfoItem__value"><?php echo $e($client['email'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Telephone</span>
                            <span class="adminInfoItem__value"><?php echo $e($client['phone'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Lieu</span>
                            <span class="adminInfoItem__value"><?php echo $e($client['location'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Derniere activite</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($client['last_activity'] ?? null); ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Historique commercial</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Demandes / devis</span>
                            <span class="adminInfoItem__value"><?php echo (int) ($client['contacts_count'] ?? 0); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Commandes boutique</span>
                            <span class="adminInfoItem__value"><?php echo (int) ($client['orders_count'] ?? 0); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">CA commandes</span>
                            <span class="adminInfoItem__value"><?php echo $formatPrice($client['orders_total'] ?? 0); ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Actions rapides</div>
                    </div>

                    <div class="adminDetailActionsBar">
                        <?php if (! empty($actions['mailto'])): ?>
                        <a href="<?php echo $e($actions['mailto']); ?>" class="adminBtn">Envoyer un email</a>
                        <?php endif; ?>
                        <?php if (! empty($actions['tel'])): ?>
                        <a href="<?php echo $e($actions['tel']); ?>" class="adminBtn">Appeler</a>
                        <?php endif; ?>
                        <?php if (! empty($actions['latest_contact'])): ?>
                        <a href="<?php echo $e($actions['latest_contact']); ?>" class="adminBtn">Derniere demande</a>
                        <?php endif; ?>
                        <?php if (! empty($actions['latest_order'])): ?>
                        <a href="<?php echo $e($actions['latest_order']); ?>" class="adminBtn">Derniere commande</a>
                        <?php endif; ?>
                        <?php if (! empty($actions['dashboard_search'])): ?>
                        <a href="<?php echo $e($actions['dashboard_search']); ?>" class="adminBtn">Recherche dashboard</a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Résumé immédiat</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Dernier dossier</span>
                            <span class="adminInfoItem__value"><?php echo $latestContact !== null ? $e(($latestContact['type'] ?? '') !== '' ? $latestContact['type'] : 'Demande') : '-'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Statut dossier</span>
                            <span class="adminInfoItem__value"><?php echo $latestContact !== null ? $e(ucfirst((string) ($latestContact['status'] ?? 'new'))) : '-'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Derniere commande</span>
                            <span class="adminInfoItem__value"><?php echo $latestOrder !== null ? $e(($latestOrder['reference'] ?? '') !== '' ? $latestOrder['reference'] : ('#' . (int) ($latestOrder['id'] ?? 0))) : '-'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Statut commande</span>
                            <span class="adminInfoItem__value"><?php echo $latestOrder !== null ? $e($orderStatuses[(string) ($latestOrder['status'] ?? 'new')] ?? (string) ($latestOrder['status'] ?? 'new')) : '-'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date dossier</span>
                            <span class="adminInfoItem__value"><?php echo $latestContact !== null ? $formatDate($latestContact['created_at'] ?? null) : '-'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date commande</span>
                            <span class="adminInfoItem__value"><?php echo $latestOrder !== null ? $formatDate($latestOrder['created_at'] ?? null) : '-'; ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Timeline unifiée</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo count($timeline); ?> événement(s) affiché(s)<?php echo $isRecentView ? ' sur la fenêtre récente' : ' du plus récent au plus ancien'; ?></span>
                        </div>
                    </div>

                    <div class="adminInlineActions adminInlineActions--filters" style="padding:0 24px 18px;">
                        <a href="/admin/clients?<?php echo $e(http_build_query($viewQueryBase + ['view' => 'all'])); ?>" class="adminBtn <?php echo $activeView === 'all' ? 'adminBtn--primary' : ''; ?>">Tout</a>
                        <a href="/admin/clients?<?php echo $e(http_build_query($viewQueryBase + ['view' => 'contacts'])); ?>" class="adminBtn <?php echo $activeView === 'contacts' ? 'adminBtn--primary' : ''; ?>">Demandes</a>
                        <a href="/admin/clients?<?php echo $e(http_build_query($viewQueryBase + ['view' => 'orders'])); ?>" class="adminBtn <?php echo $activeView === 'orders' ? 'adminBtn--primary' : ''; ?>">Commandes</a>
                        <a href="/admin/clients?<?php echo $e(http_build_query($viewQueryBase + ['view' => 'recent'])); ?>" class="adminBtn <?php echo $activeView === 'recent' ? 'adminBtn--primary' : ''; ?>">Récents</a>
                    </div>

                    <?php if ($timeline === []): ?>
                    <div class="adminEmptyState">Aucun événement consolidé pour cette identité.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($timeline as $event): ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="<?php echo $e($event['link'] ?? '/admin'); ?>" class="adminLink"><?php echo $e($event['title'] ?? 'Événement'); ?></a>
                                    <span class="adminBadge adminBadge--quoted"><?php echo $e($event['badge'] ?? 'Suivi'); ?></span>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($event['status_label'] ?? '-'); ?>
                                    <?php if (! empty($event['meta'])): ?>
                                    • <?php echo $e($event['meta']); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (! empty($event['summary'])): ?>
                                <div class="adminRequestCard__message">
                                    <p class="adminRequestCard__messageText"><?php echo $e($event['summary']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="adminMiniList__aside">
                                <?php echo $formatDate($event['sort_at'] ?? null); ?>
                                <div class="adminMiniList__actions">
                                    <a href="<?php echo $e($event['link'] ?? '/admin'); ?>" class="adminBtn adminBtn--sm"><?php echo $e($event['link_label'] ?? 'Ouvrir'); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if ($showContacts): ?>
                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Détail demandes et devis liés</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo count($contacts); ?> dossier(s)</span>
                        </div>
                    </div>

                    <?php if ($contacts === []): ?>
                    <div class="adminEmptyState">Aucune demande liée à cette identité.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>" class="adminLink"><?php echo $e($contact['name'] ?? 'Demande'); ?></a>
                                    <span class="adminBadge adminBadge--<?php echo $e($contact['status'] ?? 'new'); ?>"><?php echo $e(ucfirst((string) ($contact['status'] ?? 'new'))); ?></span>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e($contact['type'] ?? 'Type non renseigne'); ?> • <?php echo $formatDate($contact['date'] ?? null, 'd/m/Y'); ?> • <?php echo $e($contact['location'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="adminMiniList__aside">
                                <?php echo (int) ($contact['menu_items_count'] ?? 0); ?> selection(s) carte<br>
                                Creee le <?php echo $formatDate($contact['created_at'] ?? null); ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>" class="adminBtn adminBtn--sm">Ouvrir</a>
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
                <?php if ($showOrders): ?>
                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Détail commandes boutique liées</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo count($orders); ?> commande(s)</span>
                        </div>
                    </div>

                    <?php if ($orders === []): ?>
                    <div class="adminEmptyState">Aucune commande boutique liée à cette identité.</div>
                    <?php else: ?>
                    <div class="adminMiniList adminMiniList--actions">
                        <?php foreach ($orders as $order): ?>
                        <?php
                            $orderReference = trim((string) ($order['order_reference'] ?? ''));
                            if ($orderReference === '') {
                                $orderReference = '#' . (int) ($order['id'] ?? 0);
                            }
                        ?>
                        <div class="adminMiniList__item">
                            <div>
                                <div class="adminMiniList__title">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminLink"><?php echo $e($orderReference); ?></a>
                                    <span class="adminBadge adminBadge--<?php echo $e($order['status'] ?? 'new'); ?>"><?php echo $e($orderStatuses[(string) ($order['status'] ?? 'new')] ?? (string) ($order['status'] ?? 'new')); ?></span>
                                </div>
                                <div class="adminMiniList__meta">
                                    <?php echo $e(($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait'); ?> • <?php echo $formatDate($order['pickup_date'] ?? null, 'd/m/Y'); ?> • <?php echo $e($order['pickup_slot'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="adminMiniList__aside">
                                <?php echo (int) ($order['item_count'] ?? 0); ?> article(s)<br>
                                Total <?php echo $formatPrice($order['total_cents'] ?? 0); ?>
                                <div class="adminMiniList__actions">
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminBtn adminBtn--sm">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>