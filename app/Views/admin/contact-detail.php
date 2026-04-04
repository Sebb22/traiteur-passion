<?php
    $contact       = is_array($contact ?? null) ? $contact : [];
    $statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
    $flash         = is_array($flash ?? null) ? $flash : null;

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

    $menuSelectionCount = count($contact['menu_items'] ?? []);
?>
<div class="adminSplit adminSplit--contact-detail">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Demande #<?php echo (int) ($contact['id'] ?? 0); ?></h1>
            <p class="adminMediaTitle__sub">Contact • detail & suivi</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle"><?php echo $e($contact['name'] ?? 'Demande'); ?></h2>
                    <p class="adminSubtitle">Lecture du besoin, qualification commerciale et suivi du devis depuis une fiche plus directe.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin/contacts" class="adminBtn">Retour aux demandes</a>
                    <a href="/admin/catalog" class="adminBtn">Editer la carte</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Deconnexion</button>
                    </form>
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
                        <div class="adminCard__title">Repères du dossier</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Type</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['type'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date evenement</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($contact['date'] ?? null); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Nombre de personnes</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['people'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Produits sélectionnés</span>
                            <span class="adminInfoItem__value"><?php echo $menuSelectionCount; ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Informations client</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Nom</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['name'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Email</span>
                            <span class="adminInfoItem__value">
                                <a href="mailto:<?php echo $e($contact['email'] ?? ''); ?>" class="adminLink">
                                    <?php echo $e($contact['email'] ?? '-'); ?>
                                </a>
                            </span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Telephone</span>
                            <span class="adminInfoItem__value">
                                <?php if (! empty($contact['phone'])): ?>
                                <a href="tel:<?php echo $e($contact['phone']); ?>" class="adminLink">
                                    <?php echo $e($contact['phone']); ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Lieu</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['location'] ?? '-'); ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Details de l'evenement</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date evenement</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($contact['date'] ?? null); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Type</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['type'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Nombre de personnes</span>
                            <span class="adminInfoItem__value"><?php echo $e($contact['people'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Creee le</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($contact['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>

                    <div class="adminMessageSection">
                        <span class="adminInfoItem__label">Message</span>
                        <div class="adminMessageBox">
                            <?php echo nl2br($e($contact['message'] ?? '')); ?>
                        </div>
                    </div>
                </section>

                <details class="adminCard adminCard--padded adminCard--collapsible">
                    <summary class="adminCard__head adminCard__head--summary">
                        <div class="adminCard__title">Sélection de la carte</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo $menuSelectionCount; ?> ligne(s)</span>
                            <span class="adminCard__chevron" aria-hidden="true">▾</span>
                        </div>
                    </summary>

                    <?php if (! empty($contact['menu_items'])): ?>
                    <div class="adminMenuItems">
                        <?php foreach ($contact['menu_items'] as $item): ?>
                        <div class="adminMenuItem">
                            <span class="adminMenuItem__category"><?php echo $e($item['menu_item_category'] ?? '-'); ?></span>
                            <span class="adminMenuItem__name">
                                <?php echo $e($item['menu_item_name'] ?? '-'); ?>
                                <?php if ((int) ($item['quantity'] ?? 1) > 1): ?>
                                <span class="adminMenuItem__quantity">x <?php echo (int) ($item['quantity'] ?? 1); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="adminMenuItem__price"><?php echo $e($item['menu_item_price'] ?? '-'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="adminEmptyState">Aucun produit de la carte n'a été sélectionné.</div>
                    <?php endif; ?>
                </details>
            </div>

            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Statut</div>
                    </div>

                    <div class="adminStatusPanel">
                        <span class="adminBadge adminBadge--<?php echo $e($contact['status'] ?? 'new'); ?>">
                            <?php echo $e($statusOptions[$contact['status'] ?? ''] ?? ucfirst((string) ($contact['status'] ?? 'new'))); ?>
                        </span>

                        <form action="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>/status" method="post"
                            class="adminInlineForm adminInlineForm--stack">
                            <input type="hidden" name="redirect"
                                value="/admin/contacts/<?php echo (int) ($contact['id'] ?? 0); ?>">
                            <label class="adminField">
                                <span class="adminField__label">Mettre a jour</span>
                                <select name="status" class="adminSelect">
                                    <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                                    <option value="<?php echo $e($statusKey); ?>"
                                        <?php echo($contact['status'] ?? '') === $statusKey ? 'selected' : ''; ?>>
                                        <?php echo $e($statusLabel); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit" class="adminBtn adminBtn--primary">Enregistrer le statut</button>
                        </form>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Actions rapides</div>
                    </div>

                    <div class="adminDetailActionsBar">
                        <a href="mailto:<?php echo $e($contact['email'] ?? ''); ?>" class="adminBtn">Repondre par email</a>
                        <?php if (! empty($contact['phone'])): ?>
                        <a href="tel:<?php echo $e($contact['phone']); ?>" class="adminBtn">Appeler</a>
                        <?php endif; ?>
                        <button type="button" class="adminBtn" onclick="window.print()">Imprimer</button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
