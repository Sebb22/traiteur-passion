<?php
    $contactStats   = is_array($contactStats ?? null) ? $contactStats : [];
    $catalogStats   = is_array($catalogStats ?? null) ? $catalogStats : [];
    $recentContacts = is_array($recentContacts ?? null) ? $recentContacts : [];
    $typeBreakdown  = is_array($typeBreakdown ?? null) ? $typeBreakdown : [];

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
?>
<div class="adminSplit adminSplit--dashboard">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Dashboard</h1>
            <p class="adminMediaTitle__sub">Admin • pilotage & priorites</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Vue d'ensemble</h2>
                    <p class="adminSubtitle">Synthese des demandes entrantes et de la carte pour prioriser l'exploitation.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin/contacts" class="adminBtn adminBtn--primary">Voir les demandes</a>
                    <a href="/admin/catalog" class="adminBtn">Editer la carte</a>
                    <a href="/menu" class="adminBtn">Voir le menu</a>
                    <a href="/" class="adminBtn">Retour au site</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Deconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="adminStats adminStats--panel adminStats--dashboard" aria-label="Indicateurs admin">
            <div class="statCard">
                <div class="statCard__label">Demandes totales</div>
                <div class="statCard__value"><?php echo (int) ($contactStats['total'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Nouvelles</div>
                <div class="statCard__value"><?php echo (int) ($contactStats['new_count'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">A traiter</div>
                <div class="statCard__value"><?php echo (int) ($contactStats['in_progress_count'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Avec menu</div>
                <div class="statCard__value"><?php echo (int) ($contactStats['with_menu_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Sections menu</div>
                <div class="statCard__value"><?php echo (int) ($catalogStats['sections'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Items menu</div>
                <div class="statCard__value"><?php echo (int) ($catalogStats['items'] ?? 0); ?></div>
            </div>
        </section>

        <div class="adminDashboardGrid">
            <div class="adminDashboardStack">
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
                            <div class="adminStatusPill__label">Evenements a venir</div>
                            <div class="adminStatusPill__value"><?php echo (int) ($contactStats['upcoming_events'] ?? 0); ?></div>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--table">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Dernieres demandes</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Les 8 plus recentes</span>
                        </div>
                    </div>

                    <?php if ($recentContacts === []): ?>
                    <div class="adminEmptyState">Aucune demande enregistre pour le moment.</div>
                    <?php else: ?>
                    <div class="adminMiniList">
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
                                <?php echo (int) ($contact['menu_items_count'] ?? 0); ?> selection(s) menu<br>
                                Creee le <?php echo $formatDate($contact['created_at'] ?? null, 'd/m/Y H:i'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Acces rapides</div>
                        <div class="adminCard__meta">
                            <span class="adminHint">Actions frequentes</span>
                        </div>
                    </div>

                    <div class="adminDashboardQuickGrid">
                        <a href="/admin/contacts?status=new" class="adminQuickLinkCard adminLink">
                            <div class="adminQuickLinkCard__eyebrow">Suivi</div>
                            <div class="adminQuickLinkCard__title">Nouvelles demandes</div>
                            <div class="adminQuickLinkCard__meta">Traiter rapidement les entrees non lues ou non qualifiees.</div>
                        </a>

                        <a href="/admin/catalog" class="adminQuickLinkCard adminLink">
                            <div class="adminQuickLinkCard__eyebrow">Carte</div>
                            <div class="adminQuickLinkCard__title">Editer la carte</div>
                            <div class="adminQuickLinkCard__meta">Sections, items, options et visuels depuis le back-office.</div>
                        </a>
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