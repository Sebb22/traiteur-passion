<?php
    $contacts           = is_array($contacts ?? null) ? $contacts : [];
    $stats              = is_array($stats ?? null) ? $stats : [];
    $filters            = is_array($filters ?? null) ? $filters : ['status' => '', 'q' => ''];
    $statusOptions      = is_array($statusOptions ?? null) ? $statusOptions : [];
    $orderStatusOptions = is_array($orderStatusOptions ?? null) ? $orderStatusOptions : [];
    $orderStats         = is_array($orderStats ?? null) ? $orderStats : [];
    $recentOrders       = is_array($recentOrders ?? null) ? $recentOrders : [];
    $orderLoadError     = is_string($orderLoadError ?? null) ? $orderLoadError : null;
    $filteredCount      = (int) ($filteredCount ?? count($contacts));
    $flash              = is_array($flash ?? null) ? $flash : null;

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
    $searchQuery     = trim((string) ($filters['q'] ?? ''));
    $contactRedirect = '/admin/contacts' . (($activeStatus !== '' || $searchQuery !== '') ? '?' . http_build_query(['status' => $activeStatus, 'q' => $searchQuery]) : '');

    $formatPrice = static function ($value): string {
    return number_format(max(0, (int) $value) / 100, 2, ',', ' ') . ' €';
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
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Demandes, devis et commandes</h2>
                    <p class="adminSubtitle">Retrouvez les nouveaux besoins, les devis et les commandes boutique depuis un seul écran de suivi.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/admin/boutique#orders" class="adminBtn">Commandes boutique</a>
                    <a href="/admin/catalog" class="adminBtn adminBtn--primary">Editer la carte</a>
                    <a href="/admin/contacts/export" class="adminBtn">Exporter CSV</a>
                    <a href="/" class="adminBtn">Retour au site</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>">
            <?php echo $e($flash['message'] ?? 'Modification enregistrée.'); ?>
        </div>
        <?php endif; ?>

        <section class="adminStats adminStats--panel" aria-label="Statistiques">
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

            <div class="statCard">
                <div class="statCard__label">Commandes boutique</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['total'] ?? 0); ?></div>
            </div>
        </section>

        <section class="adminCatalogUtilityGrid" aria-label="Outils demandes, devis et commandes">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Retrouver rapidement une demande</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo $filteredCount; ?> resultat(s) affiché(s)</span>
                    </div>
                </div>

                <form action="/admin/contacts" method="get" class="adminCatalogToolbar">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" name="q" value="<?php echo $e($searchQuery); ?>"
                            placeholder="Nom, email, telephone, lieu, message...">
                    </label>

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

                    <div class="adminInlineActions adminInlineActions--filters">
                        <button type="submit" class="adminBtn adminBtn--primary">Filtrer</button>
                        <a href="/admin/contacts" class="adminBtn">Réinitialiser</a>
                    </div>
                </form>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Repères de suivi</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Les contacts et les commandes boutique restent suivables ici, puis opérationnels dans l’espace boutique.</span>
                    </div>
                </div>

                <div class="adminDashboardStatus adminDashboardStatus--compact">
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
                </div>
            </article>
        </section>

        <section class="adminCard adminCard--padded adminCatalogGuideCard">
            <div class="adminCard__head">
                <div class="adminCard__title">Mode d'emploi</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Trois réflexes simples pour garder le suivi commercial propre et rapide</span>
                </div>
            </div>

            <div class="adminCatalogGuideGrid">
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">1. Trier</div>
                    <div class="adminQuickLinkCard__title">Nouveau puis en cours</div>
                    <div class="adminQuickLinkCard__meta">Commencez par les demandes récentes, puis basculez-les en suivi dès qu'un échange a commencé.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">2. Deviser</div>
                    <div class="adminQuickLinkCard__title">Qualifier avant d'envoyer</div>
                    <div class="adminQuickLinkCard__meta">Vérifiez date, lieu, volume et sélection de carte avant de passer le dossier en devis envoyé.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">3. Clôturer</div>
                    <div class="adminQuickLinkCard__title">Conserver l'historique</div>
                    <div class="adminQuickLinkCard__meta">Terminez ou annulez le dossier pour garder un historique clair sans perdre les informations client.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">4. Préparer</div>
                    <div class="adminQuickLinkCard__title">Traiter la commande boutique</div>
                    <div class="adminQuickLinkCard__meta">Passez par le statut de commande dès qu’un panier validé entre, puis confirmez ou préparez le retrait.</div>
                </div>
            </div>
        </section>

        <section class="adminCard adminCard--table" id="orders">
            <div class="adminCard__head">
                <div class="adminCard__title">Commandes boutique récentes</div>
                <div class="adminCard__meta">
                    <span class="adminHint"><?php echo (int) ($orderStats['total'] ?? 0); ?> commande(s) enregistrée(s)</span>
                </div>
            </div>

            <?php if ($orderLoadError !== null): ?>
            <div class="adminEmptyState"><?php echo $e($orderLoadError); ?></div>
            <?php elseif ($recentOrders === []): ?>
            <div class="adminEmptyState">Aucune commande boutique pour le moment</div>
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
                        <tr>
                            <td>
                                <strong>
                                    <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminLink">
                                        #<?php echo (int) ($order['id'] ?? 0); ?> · <?php echo $e($order['customer_name'] ?? ''); ?>
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
                            <td><?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?></td>
                            <td>
                                <form action="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>/status" method="post">
                                    <input type="hidden" name="redirect" value="<?php echo $e($contactRedirect . '#orders'); ?>">
                                    <select class="adminSelect" name="status">
                                        <?php foreach ($orderStatusOptions as $statusKey => $statusLabel): ?>
                                        <option value="<?php echo $e($statusKey); ?>"
                                            <?php echo (string) ($order['status'] ?? '') === (string) $statusKey ? 'selected' : ''; ?>>
                                            <?php echo $e($statusLabel); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn adminBtn--sm">Mettre à jour</button>
                                        <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminBtn">Détail</a>
                                        <a href="/admin/boutique#orders" class="adminBtn">Ouvrir la boutique</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <section class="adminCard adminCard--table">
            <div class="adminCard__head">
                <div class="adminCard__title">Liste des demandes et devis</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Ouvrez un dossier pour lire le détail complet ou mettez à jour le statut directement ici.</span>
                </div>
            </div>

            <div class="adminRequestList" role="list" aria-label="Liste des demandes">
                <?php if (empty($contacts)): ?>
                <div class="adminEmptyState">Aucune demande pour le moment</div>
                <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                <?php
                    $contactId = (int) ($contact['id'] ?? 0);
                    $statusKey = (string) ($contact['status'] ?? 'new');
                ?>
                <article class="adminRequestCard" role="listitem">
                    <div class="adminRequestCard__head">
                        <div class="adminRequestCard__titleWrap">
                            <div class="adminRequestCard__eyebrow">Dossier #<?php echo $contactId; ?></div>
                            <a href="/admin/contacts/<?php echo $contactId; ?>" class="adminRequestCard__title adminLink">
                                <?php echo $e($contact['name'] ?? 'Demande'); ?>
                            </a>
                        </div>
                        <span class="adminBadge adminBadge--<?php echo $e($statusKey); ?>">
                            <?php echo $e($statusOptions[$statusKey] ?? ucfirst($statusKey)); ?>
                        </span>
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
                            </div>
                        </form>

                        <a href="/admin/contacts/<?php echo $contactId; ?>" class="adminBtn">Ouvrir le dossier</a>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>