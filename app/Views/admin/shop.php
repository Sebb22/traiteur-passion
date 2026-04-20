<?php
    $sections      = is_array($sections ?? null) ? $sections : [];
    $stats         = is_array($stats ?? null) ? $stats : [];
    $orderStats    = is_array($orderStats ?? null) ? $orderStats : [];
    $recentOrders  = is_array($recentOrders ?? null) ? $recentOrders : [];
    $lowStockItems = is_array($lowStockItems ?? null) ? $lowStockItems : [];
    $statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
    $flash         = is_array($flash ?? null) ? $flash : null;
    $loadError     = is_string($loadError ?? null) ? $loadError : null;

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatPrice = static function ($cents): string {
    return number_format(((int) $cents) / 100, 2, ',', ' ') . ' €';
    };

    $formatDateTime = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '—';
    };
?>

<div class="adminSplit adminSplit--catalog">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">La boutique</h1>
            <p class="adminMediaTitle__sub">Boutique en ligne • catalogue & stock</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Gérer la boutique en ligne</h2>
                    <p class="adminSubtitle">Ajoutez des sections, alimentez vos stocks et suivez les commandes sans quitter l'administration.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/admin/catalog" class="adminBtn">Carte évènementielle</a>
                    <a href="/boutique-en-ligne" class="adminBtn adminBtn--primary">Voir la boutique</a>
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

        <?php if ($loadError !== null): ?>
        <div class="adminFlash adminFlash--error">
            <?php echo $e($loadError); ?>
        </div>
        <?php endif; ?>

        <section class="adminStats adminStats--panel" aria-label="Statistiques boutique">
            <div class="statCard">
                <div class="statCard__label">Sections</div>
                <div class="statCard__value"><?php echo (int) ($stats['total_sections'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Produits actifs</div>
                <div class="statCard__value"><?php echo (int) ($stats['active_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Ruptures</div>
                <div class="statCard__value"><?php echo (int) ($stats['sold_out_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Nouvelles commandes</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['new_count'] ?? 0); ?></div>
            </div>
        </section>

        <section class="adminCard adminCard--padded adminCatalogGuideCard">
            <div class="adminCard__head">
                <div class="adminCard__title">Créer une section boutique</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Vous pouvez ensuite ajouter des produits et fixer leur stock maximum.</span>
                </div>
            </div>
            <div class="adminCatalogBody">
                <form action="/admin/boutique/sections/create" method="post" class="adminForm adminForm--create">
                    <div class="adminFieldGrid">
                        <label class="adminField">
                            <span class="adminField__label">Nom</span>
                            <input class="adminInput" type="text" name="name" required>
                        </label>
                        <label class="adminField">
                            <span class="adminField__label">Slug</span>
                            <input class="adminInput" type="text" name="slug" placeholder="auto-généré si vide">
                        </label>
                        <label class="adminField adminField--sm">
                            <span class="adminField__label">Ordre</span>
                            <input class="adminInput" type="number" name="sort_order" value="0">
                        </label>
                        <label class="adminField adminField--checkbox">
                            <span class="adminField__label">Visible</span>
                            <input class="adminCheckbox" type="checkbox" name="is_active" value="1" checked>
                        </label>
                    </div>

                    <label class="adminField">
                        <span class="adminField__label">Description</span>
                        <textarea class="adminTextarea" name="description" rows="3"></textarea>
                    </label>

                    <div class="adminInlineActions">
                        <button type="submit" class="adminBtn adminBtn--primary">Créer la section</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="adminCatalogList">
            <?php foreach ($sections as $section): ?>
            <details class="adminCard adminCard--collapsible" id="section-<?php echo (int) ($section['id'] ?? 0); ?>"
                data-catalog-section open>
                <summary class="adminCard__head adminCard__head--summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($section['name'] ?? 'Section'); ?></div>
                        <div class="adminHint"><?php echo $e($section['description'] ?? ''); ?></div>
                    </div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo ! empty($section['is_active']) ? 'Visible' : 'Masquée'; ?></span>
                        <span class="adminHint">• <?php echo (int) ($section['count_items'] ?? 0); ?> produit(s)</span>
                    </div>
                </summary>

                <div class="adminCatalogBody">
                    <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>" method="post" class="adminForm">
                        <div class="adminFieldGrid">
                            <label class="adminField">
                                <span class="adminField__label">Nom</span>
                                <input class="adminInput" type="text" name="name" value="<?php echo $e($section['name'] ?? ''); ?>" required>
                            </label>
                            <label class="adminField adminField--sm">
                                <span class="adminField__label">Ordre</span>
                                <input class="adminInput" type="number" name="sort_order" value="<?php echo (int) ($section['sort_order'] ?? 0); ?>">
                            </label>
                            <label class="adminField adminField--checkbox">
                                <span class="adminField__label">Visible</span>
                                <input class="adminCheckbox" type="checkbox" name="is_active" value="1" <?php echo ! empty($section['is_active']) ? 'checked' : ''; ?>>
                            </label>
                        </div>
                        <label class="adminField">
                            <span class="adminField__label">Description</span>
                            <textarea class="adminTextarea" name="description" rows="2"><?php echo $e($section['description'] ?? ''); ?></textarea>
                        </label>
                        <div class="adminInlineActions">
                            <button type="submit" class="adminBtn adminBtn--primary">Mettre à jour la section</button>
                        </div>
                    </form>

                    <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/delete" method="post" class="adminInlineActions">
                        <button type="submit" class="adminBtn adminBtn--danger">Supprimer la section</button>
                    </form>

                    <section class="adminCard adminCard--padded adminCatalogCreateSection">
                        <div class="adminCard__head">
                            <div class="adminCard__title">Ajouter un produit</div>
                        </div>
                        <div class="adminCatalogBody">
                            <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/create" method="post" class="adminForm adminForm--create">
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Nom</span>
                                        <input class="adminInput" type="text" name="name" required>
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Slug</span>
                                        <input class="adminInput" type="text" name="slug" placeholder="auto-généré si vide">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Prix (centimes)</span>
                                        <input class="adminInput" type="number" min="0" name="price_cents" value="0" required>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Stock</span>
                                        <input class="adminInput" type="number" min="0" name="stock_quantity" value="0" required>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Seuil bas</span>
                                        <input class="adminInput" type="number" min="0" name="low_stock_threshold" value="5">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Qté max / commande</span>
                                        <input class="adminInput" type="number" min="1" name="max_order_quantity" value="5">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Ordre</span>
                                        <input class="adminInput" type="number" name="sort_order" value="0">
                                    </label>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Visible</span>
                                        <input class="adminCheckbox" type="checkbox" name="is_active" value="1" checked>
                                    </label>
                                </div>
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Libellé prix</span>
                                        <input class="adminInput" type="text" name="price_label" placeholder="Ex: 12 € pièce">
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Image</span>
                                        <input class="adminInput" type="text" name="image_path" placeholder="/uploads/pages/...">
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Alt image</span>
                                        <input class="adminInput" type="text" name="image_alt">
                                    </label>
                                </div>
                                <label class="adminField">
                                    <span class="adminField__label">Description courte</span>
                                    <textarea class="adminTextarea" name="short_description" rows="3"></textarea>
                                </label>
                                <div class="adminInlineActions">
                                    <button type="submit" class="adminBtn adminBtn--primary">Créer le produit</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <?php foreach (($section['items'] ?? []) as $item): ?>
                    <article class="adminCard adminCard--padded" id="item-<?php echo (int) ($item['id'] ?? 0); ?>" data-catalog-item>
                        <div class="adminCard__head">
                            <div class="adminCard__title"><?php echo $e($item['name'] ?? 'Produit'); ?></div>
                            <div class="adminCard__meta">
                                <span class="adminHint">Stock <?php echo (int) ($item['stock_quantity'] ?? 0); ?></span>
                                <span class="adminHint">• <?php echo ! empty($item['is_active']) ? 'Visible' : 'Masqué'; ?></span>
                            </div>
                        </div>
                        <div class="adminCatalogBody">
                            <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>" method="post" class="adminForm">
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Nom</span>
                                        <input class="adminInput" type="text" name="name" value="<?php echo $e($item['name'] ?? ''); ?>" required>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Prix (centimes)</span>
                                        <input class="adminInput" type="number" min="0" name="price_cents" value="<?php echo (int) ($item['price_cents'] ?? 0); ?>" required>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Stock</span>
                                        <input class="adminInput" type="number" min="0" name="stock_quantity" value="<?php echo (int) ($item['stock_quantity'] ?? 0); ?>" required>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Seuil bas</span>
                                        <input class="adminInput" type="number" min="0" name="low_stock_threshold" value="<?php echo (int) ($item['low_stock_threshold'] ?? 0); ?>">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Qté max / commande</span>
                                        <input class="adminInput" type="number" min="1" name="max_order_quantity" value="<?php echo (int) ($item['max_order_quantity'] ?? 1); ?>">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Ordre</span>
                                        <input class="adminInput" type="number" name="sort_order" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>">
                                    </label>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Visible</span>
                                        <input class="adminCheckbox" type="checkbox" name="is_active" value="1" <?php echo ! empty($item['is_active']) ? 'checked' : ''; ?>>
                                    </label>
                                </div>
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Libellé prix</span>
                                        <input class="adminInput" type="text" name="price_label" value="<?php echo $e($item['price_label'] ?? ''); ?>">
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Image</span>
                                        <input class="adminInput" type="text" name="image_path" value="<?php echo $e($item['image_path'] ?? ''); ?>">
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Alt image</span>
                                        <input class="adminInput" type="text" name="image_alt" value="<?php echo $e($item['image_alt'] ?? ''); ?>">
                                    </label>
                                </div>
                                <label class="adminField">
                                    <span class="adminField__label">Description courte</span>
                                    <textarea class="adminTextarea" name="short_description" rows="3"><?php echo $e($item['short_description'] ?? ''); ?></textarea>
                                </label>
                                <div class="adminInlineActions">
                                    <button type="submit" class="adminBtn adminBtn--primary">Mettre à jour le produit</button>
                                    <span class="adminHint">Prix actuel: <?php echo $e(trim((string) ($item['price_label'] ?? '')) !== '' ? (string) $item['price_label'] : $formatPrice($item['price_cents'] ?? 0)); ?></span>
                                </div>
                            </form>
                            <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/delete" method="post" class="adminInlineActions">
                                <input type="hidden" name="section_id" value="<?php echo (int) ($section['id'] ?? 0); ?>">
                                <button type="submit" class="adminBtn adminBtn--danger">Supprimer le produit</button>
                            </form>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <section class="adminDashboardGrid" id="orders">
            <article class="adminCard adminCard--table">
                <div class="adminCard__head">
                    <div class="adminCard__title">Commandes récentes</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo (int) ($orderStats['total'] ?? 0); ?> commande(s)</span>
                    </div>
                </div>
                <div class="adminTableWrap">
                    <table class="adminTable">
                        <thead>
                            <tr>
                                <th>Client</th>
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
                                    <strong><?php echo $e($order['customer_name'] ?? ''); ?></strong><br>
                                    <?php echo $e($order['customer_email'] ?? ''); ?><br>
                                    <span class="adminHint"><?php echo $e($formatDateTime($order['created_at'] ?? '')); ?></span>
                                </td>
                                <td>
                                    <?php echo $e($order['pickup_date'] ?? ''); ?><br>
                                    <span class="adminHint"><?php echo $e($order['pickup_slot'] ?? ''); ?></span>
                                </td>
                                <td><?php echo (int) ($order['item_count'] ?? 0); ?> article(s)</td>
                                <td><?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?></td>
                                <td>
                                    <form action="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>/status" method="post">
                                        <select class="adminSelect" name="status">
                                            <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                                            <option value="<?php echo $e($statusKey); ?>" <?php echo (string) ($order['status'] ?? '') === (string) $statusKey ? 'selected' : ''; ?>>
                                                <?php echo $e($statusLabel); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="adminInlineActions">
                                            <button type="submit" class="adminBtn">Enregistrer</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="adminCard adminCard--table">
                <div class="adminCard__head">
                    <div class="adminCard__title">Stocks bas</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo (int) ($stats['low_stock_items'] ?? 0); ?> produit(s) à surveiller</span>
                    </div>
                </div>
                <div class="adminTableWrap">
                    <table class="adminTable">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Section</th>
                                <th>Stock</th>
                                <th>Seuil</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td><?php echo $e($item['name'] ?? ''); ?></td>
                                <td><?php echo $e($item['section_name'] ?? ''); ?></td>
                                <td><?php echo (int) ($item['stock_quantity'] ?? 0); ?></td>
                                <td><?php echo (int) ($item['low_stock_threshold'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>
</div>