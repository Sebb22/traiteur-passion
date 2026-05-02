<?php
    $order         = isset($order) && is_array($order) ? $order : [];
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

    $formatPrice = static function ($value): string {
    return number_format(((int) $value) / 100, 2, ',', ' ') . ' €';
    };

    $fulfillmentMethod = trim((string) ($order['fulfillment_method'] ?? 'pickup')) === 'delivery' ? 'delivery' : 'pickup';
    $orderItems        = is_array($order['items'] ?? null) ? $order['items'] : [];
    $subtotalCents     = max(0, (int) ($order['subtotal_cents'] ?? $order['total_cents'] ?? 0));
    $discountCents     = max(0, (int) ($order['discount_cents'] ?? 0));
    $orderStatusKey    = (string) ($order['status'] ?? 'new');
        $orderStatusLabel  = (string) ($statusOptions[$orderStatusKey] ?? ucfirst($orderStatusKey));
        $previewSummary    = [
            ['label' => 'Reference', 'value' => '#' . (int) ($order['id'] ?? 0)],
            ['label' => 'Mode', 'value' => $fulfillmentMethod === 'delivery' ? 'Livraison' : 'Retrait'],
            ['label' => 'Date', 'value' => $formatDate($order['pickup_date'] ?? null)],
            ['label' => 'Creneau', 'value' => (string) ($order['pickup_slot'] ?? '-')],
            ['label' => 'Articles', 'value' => (string) ((int) ($order['item_count'] ?? 0))],
            ['label' => 'Total', 'value' => $formatPrice($order['total_cents'] ?? 0)],
            ['label' => 'Statut', 'value' => $orderStatusLabel],
        ];
    switch ($orderStatusKey) {
    case 'confirmed':
        $defaultMailSubject = sprintf('Traiteur Passion - Votre commande #%d est confirmee', (int) ($order['id'] ?? 0));
        break;
    case 'preparing':
        $defaultMailSubject = sprintf('Traiteur Passion - Votre commande #%d est en preparation', (int) ($order['id'] ?? 0));
        break;
    case 'completed':
        $defaultMailSubject = sprintf('Traiteur Passion - Votre commande #%d est finalisee', (int) ($order['id'] ?? 0));
        break;
    case 'cancelled':
        $defaultMailSubject = sprintf('Traiteur Passion - Votre commande #%d a ete annulee', (int) ($order['id'] ?? 0));
        break;
    default:
        $defaultMailSubject = sprintf('Traiteur Passion - Suivi de votre commande #%d', (int) ($order['id'] ?? 0));
        break;
    }
    $deliveryAddress   = trim(implode(', ', array_filter([
    trim((string) ($order['delivery_address'] ?? '')),
    trim((string) (($order['delivery_postal_code'] ?? '') . ' ' . ($order['delivery_city'] ?? ''))),
    ])));
?>
<div class="adminSplit adminSplit--contact-detail">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Commande #<?php echo (int) ($order['id'] ?? 0); ?></h1>
            <p class="adminMediaTitle__sub">Boutique • detail du panier & suivi</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle"><?php echo $e($order['customer_name'] ?? 'Commande boutique'); ?></h2>
                    <p class="adminSubtitle">Lecture complète du panier, du mode de retrait ou de livraison, puis suivi opérationnel depuis une fiche dédiée.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                        <a href="/admin/boutique#orders" class="adminBtn adminBtn--primary">Retour aux commandes</a>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--modules">
                        <a href="/admin/contacts#orders" class="adminBtn">Demandes & commandes</a>
                        <a href="/admin" class="adminBtn">Dashboard</a>
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
                        <div class="adminCard__title">Repères de commande</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Référence</span>
                            <span class="adminInfoItem__value">#<?php echo (int) ($order['id'] ?? 0); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Mode</span>
                            <span class="adminInfoItem__value"><?php echo $fulfillmentMethod === 'delivery' ? 'Livraison demandée' : 'Retrait demandé'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Date souhaitée</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($order['pickup_date'] ?? null); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Créneau</span>
                            <span class="adminInfoItem__value"><?php echo $e($order['pickup_slot'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Articles</span>
                            <span class="adminInfoItem__value"><?php echo (int) ($order['item_count'] ?? 0); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Total</span>
                            <span class="adminInfoItem__value"><?php echo $formatPrice($order['total_cents'] ?? 0); ?></span>
                        </div>
                        <?php if ($discountCents > 0): ?>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Code promo</span>
                            <span class="adminInfoItem__value"><?php echo $e($order['promo_code'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Sous-total</span>
                            <span class="adminInfoItem__value"><?php echo $formatPrice($subtotalCents); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Remise</span>
                            <span class="adminInfoItem__value">- <?php echo $formatPrice($discountCents); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Informations client</div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Nom</span>
                            <span class="adminInfoItem__value"><?php echo $e($order['customer_name'] ?? '-'); ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Email</span>
                            <span class="adminInfoItem__value"><a href="mailto:<?php echo $e($order['customer_email'] ?? ''); ?>" class="adminLink"><?php echo $e($order['customer_email'] ?? '-'); ?></a></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Telephone</span>
                            <span class="adminInfoItem__value">
                                <?php if (! empty($order['customer_phone'])): ?>
                                <a href="tel:<?php echo $e($order['customer_phone']); ?>" class="adminLink"><?php echo $e($order['customer_phone']); ?></a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Créée le</span>
                            <span class="adminInfoItem__value"><?php echo $formatDate($order['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title"><?php echo $fulfillmentMethod === 'delivery' ? 'Livraison' : 'Retrait'; ?></div>
                    </div>

                    <div class="adminInfoGrid">
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Mode demandé</span>
                            <span class="adminInfoItem__value"><?php echo $fulfillmentMethod === 'delivery' ? 'Livraison' : 'Retrait'; ?></span>
                        </div>
                        <div class="adminInfoItem">
                            <span class="adminInfoItem__label">Adresse</span>
                            <span class="adminInfoItem__value"><?php echo $e($fulfillmentMethod === 'delivery' ? ($deliveryAddress !== '' ? $deliveryAddress : '-') : 'Retrait sur créneau'); ?></span>
                        </div>
                    </div>

                    <div class="adminMessageSection">
                        <span class="adminInfoItem__label">Message client</span>
                        <div class="adminMessageBox"><?php echo nl2br($e($order['message'] ?? '')); ?></div>
                    </div>
                </section>

                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Détail du panier</div>
                        <div class="adminCard__meta">
                            <span class="adminHint"><?php echo count($orderItems); ?> ligne(s)</span>
                        </div>
                    </div>

                    <?php if ($orderItems === []): ?>
                    <div class="adminEmptyState">Aucune ligne panier enregistrée sur cette commande.</div>
                    <?php else: ?>
                    <div class="adminTableWrap">
                        <table class="adminTable">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Section</th>
                                    <th>Quantité</th>
                                    <th>PU</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><strong><?php echo $e($item['item_name_snapshot'] ?? '-'); ?></strong></td>
                                    <td><?php echo $e($item['section_name_snapshot'] ?? '-'); ?></td>
                                    <td><?php echo (int) ($item['quantity'] ?? 0); ?></td>
                                    <td><?php echo $formatPrice($item['unit_price_cents'] ?? 0); ?></td>
                                    <td><?php echo $formatPrice($item['line_total_cents'] ?? 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="adminDashboardStack">
                <section class="adminCard adminCard--padded">
                    <div class="adminCard__head">
                        <div class="adminCard__title">Statut</div>
                    </div>

                    <div class="adminStatusPanel">
                        <span class="adminBadge adminBadge--<?php echo $e($order['status'] ?? 'new'); ?>">
                            <?php echo $e($statusOptions[$order['status'] ?? ''] ?? ucfirst((string) ($order['status'] ?? 'new'))); ?>
                        </span>

                        <form action="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>/status" method="post"
                            class="adminInlineForm adminInlineForm--stack">
                            <input type="hidden" name="redirect" value="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>">
                            <label class="adminField">
                                <span class="adminField__label">Mettre a jour</span>
                                <select name="status" class="adminSelect">
                                    <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                                    <option value="<?php echo $e($statusKey); ?>"
                                        <?php echo($order['status'] ?? '') === $statusKey ? 'selected' : ''; ?>>
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
                                    placeholder="Confirmation, precision de retrait, ajustement logistique..." data-mail-message></textarea>
                            </label>
                            <div class="adminMailPreview" data-mail-preview-root
                                data-mail-kind="order"
                                data-reference="<?php echo (int) ($order['id'] ?? 0); ?>"
                                data-client-name="<?php echo $e($order['customer_name'] ?? 'Client'); ?>"
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
                                            <div class="adminMailPreview__blockLabel">Recap de la commande</div>
                                            <div class="adminMailPreview__summaryGrid">
                                                <?php foreach ($previewSummary as $summaryItem): ?>
                                                <div class="adminMailPreview__summaryItem">
                                                    <span class="adminMailPreview__summaryLabel"><?php echo $e($summaryItem['label']); ?></span>
                                                    <span class="adminMailPreview__summaryValue"><?php echo $e($summaryItem['value']); ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if ($orderItems !== []): ?>
                                        <div class="adminMailPreview__block">
                                            <div class="adminMailPreview__blockLabel">Produits retenus</div>
                                            <div class="adminMailPreview__items">
                                                <?php foreach ($orderItems as $item): ?>
                                                <article class="adminMailPreview__itemCard">
                                                    <?php if (! empty($item['image_path'])): ?>
                                                    <img class="adminMailPreview__itemThumb" src="<?php echo $e($item['image_path']); ?>"
                                                        alt="<?php echo $e($item['image_alt'] ?? ($item['item_name_snapshot'] ?? 'Produit')); ?>" loading="lazy">
                                                    <?php endif; ?>
                                                    <div class="adminMailPreview__itemBody">
                                                        <p class="adminMailPreview__itemCategory"><?php echo $e($item['section_name_snapshot'] ?? '-'); ?></p>
                                                        <p class="adminMailPreview__itemTitle"><?php echo $e($item['item_name_snapshot'] ?? '-'); ?></p>
                                                        <?php if (! empty($item['item_description'])): ?>
                                                        <p class="adminMailPreview__itemDetail"><?php echo $e($item['item_description']); ?></p>
                                                        <?php endif; ?>
                                                        <p class="adminMailPreview__itemMeta">
                                                            Prix <?php echo $e($formatPrice($item['unit_price_cents'] ?? 0)); ?>
                                                            <span>•</span>
                                                            Qte <?php echo (int) ($item['quantity'] ?? 0); ?>
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
                        <a href="mailto:<?php echo $e($order['customer_email'] ?? ''); ?>" class="adminBtn">Repondre par email</a>
                        <?php if (! empty($order['customer_phone'])): ?>
                        <a href="tel:<?php echo $e($order['customer_phone']); ?>" class="adminBtn">Appeler</a>
                        <?php endif; ?>
                        <button type="button" class="adminBtn" onclick="window.print()">Imprimer</button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>