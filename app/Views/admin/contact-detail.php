<?php
    $contact       = isset($contact) && is_array($contact) ? $contact : [];
    $statusOptions = isset($statusOptions) && is_array($statusOptions) ? $statusOptions : [];
    $flash         = isset($flash) && is_array($flash) ? $flash : null;

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
    $mailRequestKind    = $menuSelectionCount > 0 ? 'quote' : 'contact';
    $contactStatusKey   = (string) ($contact['status'] ?? 'new');
    $contactStatusLabel = (string) ($statusOptions[$contactStatusKey] ?? ucfirst($contactStatusKey));
        $previewSummary     = [
            ['label' => 'Reference', 'value' => '#' . (int) ($contact['id'] ?? 0)],
            ['label' => 'Type', 'value' => (string) ($contact['type'] ?? '-')],
            ['label' => 'Date', 'value' => $formatDate($contact['date'] ?? null)],
            ['label' => 'Personnes', 'value' => (string) ($contact['people'] ?? '-')],
            ['label' => 'Lieu', 'value' => (string) ($contact['location'] ?? '-')],
            ['label' => 'Statut', 'value' => $contactStatusLabel],
        ];
    if ($mailRequestKind === 'quote') {
    switch ($contactStatusKey) {
        case 'in_progress':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre devis #%d est en cours d\'etude', (int) ($contact['id'] ?? 0));
            break;
        case 'quoted':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre devis #%d est pret', (int) ($contact['id'] ?? 0));
            break;
        case 'completed':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre devis #%d est finalise', (int) ($contact['id'] ?? 0));
            break;
        case 'cancelled':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre devis #%d a ete cloture', (int) ($contact['id'] ?? 0));
            break;
        default:
            $defaultMailSubject = sprintf('Traiteur Passion - Suivi de votre devis #%d', (int) ($contact['id'] ?? 0));
            break;
    }
    } else {
    switch ($contactStatusKey) {
        case 'in_progress':
            $defaultMailSubject = sprintf('Traiteur Passion - Nous etudions votre demande #%d', (int) ($contact['id'] ?? 0));
            break;
        case 'quoted':
            $defaultMailSubject = sprintf('Traiteur Passion - Une proposition est prete pour votre demande #%d', (int) ($contact['id'] ?? 0));
            break;
        case 'completed':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre demande #%d est finalisee', (int) ($contact['id'] ?? 0));
            break;
        case 'cancelled':
            $defaultMailSubject = sprintf('Traiteur Passion - Votre demande #%d a ete cloturee', (int) ($contact['id'] ?? 0));
            break;
        default:
            $defaultMailSubject = sprintf('Traiteur Passion - Suivi de votre demande #%d', (int) ($contact['id'] ?? 0));
            break;
    }
    }
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
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                        <a href="/admin/contacts" class="adminBtn adminBtn--primary">Retour aux demandes</a>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--modules">
                        <a href="/admin" class="adminBtn">Dashboard</a>
                        <a href="/admin/catalog" class="adminBtn">Carte</a>
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
                            <label class="adminField adminField--checkbox">
                                <span class="adminField__label">Prevenir le client par email</span>
                                <input type="checkbox" class="adminCheckbox" name="notify_client" value="1">
                            </label>
                            <label class="adminField adminField--full">
                                <span class="adminField__label">Objet du mail</span>
                                <div class="adminMailPreview__subjectRow">
                                    <input type="text" name="client_subject" class="adminInput"
                                        value="<?php echo $e($defaultMailSubject); ?>" data-mail-subject>
                                    <button type="button" class="adminBtn adminBtn--sm" data-mail-subject-reset>Objet auto</button>
                                </div>
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Message complementaire pour le client</span>
                                <textarea name="client_message" class="adminTextarea" rows="4"
                                    placeholder="Precisions utiles, prochaine etape, point a confirmer..." data-mail-message></textarea>
                            </label>
                            <div class="adminMailPreview" data-mail-preview-root
                                data-mail-kind="<?php echo $e($mailRequestKind); ?>"
                                data-reference="<?php echo (int) ($contact['id'] ?? 0); ?>"
                                data-client-name="<?php echo $e($contact['name'] ?? 'Client'); ?>"
                                data-status-labels="<?php echo $e((string) json_encode($statusOptions, JSON_UNESCAPED_UNICODE)); ?>">
                                <div class="adminMailPreview__eyebrow">Apercu du mail client</div>
                                <div class="adminMailPreview__shell">
                                    <div class="adminMailPreview__hero">
                                        <span class="adminMailPreview__badge" data-mail-preview-badge></span>
                                        <p class="adminMailPreview__label">Suivi client</p>
                                        <h3 class="adminMailPreview__title" data-mail-preview-title></h3>
                                        <p class="adminMailPreview__summary" data-mail-preview-summary></p>
                                    </div>
                                    <div class="adminMailPreview__body">
                                        <p class="adminMailPreview__greeting" data-mail-preview-greeting></p>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Objet</div>
                                            <p class="adminMailPreview__blockText" data-mail-preview-subject></p>
                                        </div>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Introduction</div>
                                            <p class="adminMailPreview__blockText" data-mail-preview-intro></p>
                                        </div>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Point de suivi</div>
                                            <p class="adminMailPreview__blockText" data-mail-preview-message></p>
                                        </div>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Prochaine etape</div>
                                            <p class="adminMailPreview__blockText" data-mail-preview-next-step></p>
                                        </div>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Recap de la demande</div>
                                            <div class="adminMailPreview__summaryGrid">
                                                <?php foreach ($previewSummary as $summaryItem): ?>
                                                <div class="adminMailPreview__summaryItem">
                                                    <span class="adminMailPreview__summaryLabel"><?php echo $e($summaryItem['label']); ?></span>
                                                    <span class="adminMailPreview__summaryValue"><?php echo $e($summaryItem['value']); ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if (! empty($contact['menu_items'])): ?>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Produits retenus</div>
                                            <div class="adminMailPreview__items">
                                                <?php foreach ($contact['menu_items'] as $item): ?>
                                                <article class="adminMailPreview__itemCard">
                                                    <?php if (! empty($item['image_path'])): ?>
                                                    <img class="adminMailPreview__itemThumb" src="<?php echo $e($item['image_path']); ?>"
                                                        alt="<?php echo $e($item['image_alt'] ?? ($item['menu_item_name'] ?? 'Produit')); ?>" loading="lazy">
                                                    <?php endif; ?>
                                                    <div class="adminMailPreview__itemBody">
                                                        <p class="adminMailPreview__itemCategory"><?php echo $e($item['menu_item_category'] ?? '-'); ?></p>
                                                        <p class="adminMailPreview__itemTitle"><?php echo $e($item['menu_item_name'] ?? '-'); ?></p>
                                                        <?php if (! empty($item['item_description'])): ?>
                                                        <p class="adminMailPreview__itemDetail"><?php echo $e($item['item_description']); ?></p>
                                                        <?php endif; ?>
                                                        <p class="adminMailPreview__itemMeta">
                                                            Prix <?php echo $e($item['menu_item_price'] ?? 'Sur devis'); ?>
                                                            <span>•</span>
                                                            Qte <?php echo (int) ($item['quantity'] ?? 1); ?>
                                                        </p>
                                                    </div>
                                                </article>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <p class="adminMailPreview__closing" data-mail-preview-closing></p>
                                    </div>
                                </div>
                                <p class="adminHint">Le mail part avec cet objet et ce contenu si la case d'envoi est cochée.</p>
                            </div>
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
