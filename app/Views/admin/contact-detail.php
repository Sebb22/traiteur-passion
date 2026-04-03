<div class="adminPage">
    <div class="adminContainer adminContainer--narrow">
        <div class="adminDetailHeader">
            <h1>📄 Demande #<?php echo $contact['id'] ?></h1>
            <div class="adminDetailActions">
                <a href="/admin/contacts" class="adminBtn">← Retour</a>
                <form action="/admin/logout" method="post">
                    <button type="submit" class="adminBtn">🚪 Déconnexion</button>
                </form>
            </div>
        </div>

        <!-- Client Information -->
        <div class="adminCard">
            <h2 class="adminCard__title">👤 Informations client</h2>
            <div class="adminInfoGrid">
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Nom</span>
                    <span class="adminInfoItem__value"><?php echo htmlspecialchars($contact['name']) ?></span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Email</span>
                    <span class="adminInfoItem__value">
                        <a href="mailto:<?php echo htmlspecialchars($contact['email']) ?>" class="adminLink">
                            <?php echo htmlspecialchars($contact['email']) ?>
                        </a>
                    </span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Téléphone</span>
                    <span class="adminInfoItem__value">
                        <?php if ($contact['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($contact['phone']) ?>" class="adminLink">
                                <?php echo htmlspecialchars($contact['phone']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Event Information -->
        <div class="adminCard">
            <h2 class="adminCard__title">🎉 Détails de l'événement</h2>
            <div class="adminInfoGrid">
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Date</span>
                    <span class="adminInfoItem__value">
                        <?php echo $contact['date'] ? date('d/m/Y', strtotime($contact['date'])) : '-' ?>
                    </span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Lieu</span>
                    <span class="adminInfoItem__value"><?php echo htmlspecialchars($contact['location'] ?? '-') ?></span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Type</span>
                    <span class="adminInfoItem__value"><?php echo htmlspecialchars($contact['type'] ?? '-') ?></span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Nombre de personnes</span>
                    <span class="adminInfoItem__value"><?php echo $contact['people'] ?? '-' ?></span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Statut</span>
                    <span class="adminInfoItem__value">
                        <span class="adminBadge adminBadge--<?php echo $contact['status'] ?>">
                            <?php echo ucfirst($contact['status']) ?>
                        </span>
                    </span>
                </div>
                <div class="adminInfoItem">
                    <span class="adminInfoItem__label">Date de création</span>
                    <span class="adminInfoItem__value">
                        <?php echo date('d/m/Y à H:i', strtotime($contact['created_at'])) ?>
                    </span>
                </div>
            </div>

            <div class="adminMessageSection">
                <span class="adminInfoItem__label">Message</span>
                <div class="adminMessageBox">
                    <?php echo nl2br(htmlspecialchars($contact['message'])) ?>
                </div>
            </div>
        </div>

        <!-- Selected Menu Items -->
        <div class="adminCard">
            <h2 class="adminCard__title">🍽️ Items du menu sélectionnés</h2>

            <?php if (! empty($contact['menu_items'])): ?>
                <div class="adminMenuItems">
                    <?php foreach ($contact['menu_items'] as $item): ?>
                        <div class="adminMenuItem">
                            <span class="adminMenuItem__category">
                                <?php echo htmlspecialchars($item['menu_item_category']) ?>
                            </span>
                            <span class="adminMenuItem__name">
                                <?php echo htmlspecialchars($item['menu_item_name']) ?>
                                <?php if ($item['quantity'] > 1): ?>
                                    <span class="adminMenuItem__quantity"> × <?php echo $item['quantity'] ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="adminMenuItem__price">
                                <?php echo htmlspecialchars($item['menu_item_price']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="adminEmptyState">
                    Aucun item du menu n'a été sélectionné
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="adminCard">
            <h2 class="adminCard__title">⚙️ Actions</h2>
            <div class="adminDetailActionsBar">
                <a href="mailto:<?php echo htmlspecialchars($contact['email']) ?>" class="adminBtn">
                    📧 Répondre par email
                </a>
                <?php if ($contact['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($contact['phone']) ?>" class="adminBtn">
                        📞 Appeler
                    </a>
                <?php endif; ?>
                <button class="adminBtn" onclick="window.print()">🖨️ Imprimer</button>
            </div>
        </div>
    </div>
</div>
