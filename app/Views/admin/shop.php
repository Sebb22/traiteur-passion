<?php
    $sections      = is_array($sections ?? null) ? $sections : [];
    $stats         = is_array($stats ?? null) ? $stats : [];
    $lowStockItems = is_array($lowStockItems ?? null) ? $lowStockItems : [];
    $imageRuntime  = is_array($imageRuntime ?? null) ? $imageRuntime : [];
    $flash         = is_array($flash ?? null) ? $flash : null;
    $loadError     = is_string($loadError ?? null) ? $loadError : null;

    $activeSections    = 0;
    $inactiveSections  = 0;
    $activeItems       = 0;
    $inactiveItems     = 0;
    $itemsWithoutImage = 0;

    foreach ($sections as $section) {
    if (! empty($section['is_active'])) {
        $activeSections++;
    } else {
        $inactiveSections++;
    }

    foreach (($section['items'] ?? []) as $item) {
        if (! empty($item['is_active'])) {
            $activeItems++;
        } else {
            $inactiveItems++;
        }

        if (trim((string) ($item['image_path'] ?? '')) === '') {
            $itemsWithoutImage++;
        }
    }
    }

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatPrice = static function ($cents): string {
    return number_format(((int) $cents) / 100, 2, ',', ' ') . ' EUR';
    };

    $formatPriceInput = static function ($cents): string {
    return number_format(((int) $cents) / 100, 2, ',', '');
    };

    $formatItemPrice = static function (array $item) use ($formatPrice): string {
    $priceLabel = trim((string) ($item['price_label'] ?? ''));
    return $priceLabel !== '' ? $priceLabel : $formatPrice($item['price_cents'] ?? 0);
    };

    $normalizeStockUnit = static function ($value): string {
    return trim((string) $value) === 'g' ? 'g' : 'unit';
    };

    $formatStockQuantity = static function ($quantity, $unit) use ($normalizeStockUnit): string {
    $amount    = max(0, (int) ($quantity ?? 0));
    $stockUnit = $normalizeStockUnit($unit);
    if ($stockUnit === 'g') {
        if ($amount >= 1000) {
            $kilograms = number_format($amount / 1000, 2, ',', ' ');
            $kilograms = rtrim(rtrim($kilograms, '0'), ',');
            return $kilograms . ' kg';
        }

        return $amount . ' g';
    }

    return $amount . ' unité(s)';
    };

    $resolveOptionQuantityInputValue = static function (array $option, $unit) use ($normalizeStockUnit): int {
    $storedQuantity = max(1, (int) ($option['quantity'] ?? 1));
    if ($normalizeStockUnit($unit) === 'g' || $storedQuantity > 1) {
        return $storedQuantity;
    }

    $label = trim((string) ($option['label'] ?? ''));
    if ($label !== '' && preg_match('/(?:lot\s*de|x)\s*(\d+)/iu', $label, $matches)) {
        return max(1, (int) ($matches[1] ?? 1));
    }

    return $storedQuantity;
    };

    $resolveOptionStockInputValue = static function (array $option): string {
    if (! array_key_exists('stock_quantity', $option) || $option['stock_quantity'] === null || trim((string) $option['stock_quantity']) === '') {
        return '';
    }

    return (string) max(0, (int) $option['stock_quantity']);
    };
?>

<div class="adminSplit adminSplit--catalog"
    data-rembg-preview-model="<?php echo $e((string) ($imageRuntime['preview_model'] ?? 'u2netp')); ?>"
    data-rembg-final-model="<?php echo $e((string) ($imageRuntime['final_model'] ?? 'u2net')); ?>"
    data-rembg-preview-reusable="<?php echo ! empty($imageRuntime['preview_reusable']) ? '1' : '0'; ?>"
    data-image-preview-endpoint="/admin/boutique/image-preview">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">La boutique</h1>
            <p class="adminMediaTitle__sub">Boutique en ligne • catalogue & stock</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <?php require dirname(__DIR__) . '/partials/admin-navigation.php'; ?>
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Boutique</h2>
                    <p class="adminSubtitle">Produits, disponibilité et visuels.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--primary">
                        <a href="/boutique-en-ligne" class="adminBtn adminBtn--primary">Voir la boutique</a>
                        <button type="button" class="adminBtn" data-shop-product-dialog-open>Nouveau produit</button>
                        <a href="#new-section" class="adminBtn">Nouvelle section</a>
                    </div>
                    <div class="adminPanelHead__actionsGroup adminPanelHead__actionsGroup--utility">
                        <form action="/admin/logout" method="post">
                            <button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <dialog class="adminCreateDialog" data-shop-product-dialog aria-labelledby="new-product-dialog-title">
            <form method="dialog" class="adminCreateDialog__form">
                <div>
                    <h3 id="new-product-dialog-title">Nouveau produit</h3>
                    <p>Choisissez la section dans laquelle créer ce produit.</p>
                </div>
                <label class="adminField">
                    <span class="adminField__label">Section</span>
                    <select class="adminSelect" data-shop-product-section required>
                        <option value="">Choisir une section</option>
                        <?php foreach ($sections as $section): ?>
                        <option value="<?php echo (int) ($section['id'] ?? 0); ?>"><?php echo $e($section['name'] ?? 'Section'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="adminInlineActions">
                    <button type="button" class="adminBtn adminBtn--primary" data-shop-product-dialog-confirm>Continuer</button>
                    <button type="submit" class="adminBtn">Annuler</button>
                </div>
            </form>
        </dialog>

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
                <div class="statCard__label">Produits visibles</div>
                <div class="statCard__value"><?php echo $activeItems; ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Ruptures</div>
                <div class="statCard__value"><?php echo (int) ($stats['sold_out_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Stocks bas</div>
                <div class="statCard__value"><?php echo (int) ($stats['low_stock_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Sans image</div>
                <div class="statCard__value"><?php echo $itemsWithoutImage; ?></div>
            </div>
        </section>

        <?php if ($loadError === null): ?>
        <?php if ($lowStockItems !== []): ?>
        <section class="adminShopAlerts" aria-label="Produits à traiter">
            <div class="adminShopAlerts__head">
                <strong>À traiter</strong>
                <span><?php echo count($lowStockItems); ?> produit(s) à surveiller</span>
            </div>
            <div class="adminShopAlerts__list">
                <?php foreach ($lowStockItems as $item): ?>
                <?php $alertUnit = $normalizeStockUnit($item['stock_unit'] ?? 'unit'); ?>
                <a class="adminShopAlert" href="#item-<?php echo (int) ($item['id'] ?? 0); ?>">
                    <span><?php echo $e($item['name'] ?? 'Produit'); ?></span>
                    <span><?php echo $e($formatStockQuantity($item['stock_quantity'] ?? 0, $alertUnit)); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <section class="adminCatalogUtilityGrid" aria-label="Outils boutique">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Recherche catalogue</div>
                </div>

                <div class="adminCatalogToolbar">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" placeholder="Ex: brunch, tartelette, formule..."
                            data-catalog-search>
                    </label>

                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Accéder à une section</span>
                        <select class="adminSelect" data-catalog-jump>
                            <option value="">Choisir une section</option>
                            <?php foreach ($sections as $section): ?>
                            <option value="section-<?php echo (int) ($section['id'] ?? 0); ?>">
                                <?php echo $e($section['name'] ?? 'Section'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="adminInlineActions adminInlineActions--filters">
                        <button type="button" class="adminBtn" data-catalog-view-toggle>Vue images</button>
                        <button type="button" class="adminBtn" data-catalog-collapse-all>Fermer les sections</button>
                    </div>
                </div>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Afficher</div>
                </div>

                <div class="adminCatalogFilterGroup" role="group" aria-label="Filtres catalogue">
                    <button type="button" class="adminFilterButton is-active" data-catalog-filter="all">Tous</button>
                    <button type="button" class="adminFilterButton" data-catalog-filter="low-stock">Stock bas</button>
                    <button type="button" class="adminFilterButton" data-catalog-filter="sold-out">Rupture</button>
                    <button type="button" class="adminFilterButton" data-catalog-filter="hidden">Masqués</button>
                    <button type="button" class="adminFilterButton" data-catalog-filter="no-image">Sans image</button>
                </div>
                <p class="adminCatalogResults" data-catalog-results aria-live="polite"></p>
            </article>
        </section>

        <div class="adminCatalogNoResults" data-catalog-no-results hidden>
            Aucun produit ne correspond à ces critères.
            <button type="button" class="adminLink" data-catalog-reset>Réinitialiser</button>
        </div>

        <details class="adminCard adminCatalogCreateSection" id="new-section">
            <summary class="adminCard__head"><span class="adminCard__title">Nouvelle section</span></summary>
            <div class="adminCatalogBody">
                <form action="/admin/boutique/sections/create" method="post" class="adminForm adminForm--create">
                    <div class="adminFieldGrid">
                        <label class="adminField">
                            <span class="adminField__label">Nom</span>
                            <input class="adminInput" type="text" name="name" required>
                        </label>
                        <label class="adminField adminField--checkbox">
                            <span class="adminField__label">Visible</span>
                            <input class="adminCheckbox" type="checkbox" name="is_active" value="1" checked>
                        </label>
                    </div>
                    <span class="adminHint">Le slug et l'ordre d'affichage sont gérés automatiquement.</span>

                    <label class="adminField">
                        <span class="adminField__label">Description</span>
                        <textarea class="adminTextarea" name="description" rows="3"></textarea>
                    </label>

                    <div class="adminInlineActions">
                        <button type="submit" class="adminBtn adminBtn--primary">Créer la section</button>
                    </div>
                </form>
            </div>
        </details>

        <form action="/admin/boutique/sections/reorder" method="post" id="catalogSectionOrderForm"
            class="adminReorderForm">
            <input type="hidden" name="section_ids" id="catalogSectionOrderInput" value="">
        </form>

        <div class="adminCatalogItemsHead adminCatalogItemsHead--reorder">
            <div>
                <div class="adminCatalogOptions__title">Organisation des sections</div>
                <span class="adminHint">Activez le mode tri, glissez avec la poignée puis enregistrez l'ordre.</span>
            </div>
            <div class="adminInlineActions adminInlineActions--reorder">
                <button type="button" class="adminBtn" data-reorder-toggle="sections">Réordonner</button>
                <button type="button" class="adminBtn adminBtn--primary" data-reorder-save="sections" hidden
                    disabled>Enregistrer l'ordre</button>
                <button type="button" class="adminBtn" data-reorder-cancel="sections" hidden>Annuler</button>
            </div>
        </div>

        <div class="adminCatalogList" data-section-sortable data-reorder-scope="sections">
            <?php foreach ($sections as $section): ?>
            <?php
                $sectionName         = (string) ($section['name'] ?? 'Section');
                $sectionSlug         = (string) ($section['slug'] ?? '');
                $sectionSearch       = strtolower(trim($sectionName . ' ' . $sectionSlug . ' ' . (string) ($section['description'] ?? '')));
                $sectionState        = ! empty($section['is_active']) ? 'Visible' : 'Masquee';
                $sectionItemCount    = count($section['items'] ?? []);
                $sectionStock        = 0;
                $sectionVisibleItems = 0;
                $sectionAlertItems   = 0;
                foreach (($section['items'] ?? []) as $sectionItemForStock) {
                    $sectionStock += max(0, (int) ($sectionItemForStock['stock_quantity'] ?? 0));
                    if (! empty($sectionItemForStock['is_active'])) {
                        $sectionVisibleItems++;
                    }
                    $sectionItemStock     = max(0, (int) ($sectionItemForStock['stock_quantity'] ?? 0));
                    $sectionItemThreshold = max(0, (int) ($sectionItemForStock['low_stock_threshold'] ?? 0));
                    if ($sectionItemStock <= $sectionItemThreshold) {
                        $sectionAlertItems++;
                    }
                }
            ?>
            <details class="adminCard adminCatalogSection" id="section-<?php echo (int) ($section['id'] ?? 0); ?>"
                data-section-id="<?php echo (int) ($section['id'] ?? 0); ?>" draggable="false" data-catalog-section
                data-catalog-search-text="<?php echo $e($sectionSearch); ?>">
                <summary class="adminCard__head adminCatalogSection__summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($sectionName); ?></div>
                        <div class="adminCatalogMeta">
                            <span>Slug : <?php echo $e($sectionSlug); ?></span>
                            <span><?php echo $sectionVisibleItems; ?>/<?php echo $sectionItemCount; ?> visible(s)</span>
                            <?php if ($sectionAlertItems > 0): ?>
                            <span class="adminSectionAlert"><?php echo $sectionAlertItems; ?> alerte(s) stock</span>
                            <?php endif; ?>
                            <span><?php echo $sectionState; ?></span>
                        </div>
                    </div>
                    <div class="adminCatalogSection__headActions" aria-hidden="true">
                        <span class="adminCatalogSection__chevron">▾</span>
                        <span class="adminDragHandle" data-drag-handle>↕</span>
                    </div>
                </summary>

                <div class="adminCatalogBody">
                    <div class="adminCatalogSectionGrid">
                        <details class="adminEditorBlock adminEditorBlock--collapsible" data-new-shop-item>
                            <summary class="adminEditorBlock__summary">
                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                    <div class="adminEditorBlock__title">Réglages de la section</div>
                                    <p class="adminEditorBlock__text">Nom, ordre d'affichage et visibilité de la famille
                                        de produits.</p>
                                </div>
                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                            </summary>

                            <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>"
                                method="post" class="adminForm adminForm--section">
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Nom</span>
                                        <input class="adminInput" type="text" name="name"
                                            value="<?php echo $e($section['name'] ?? ''); ?>" required>
                                    </label>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Visible</span>
                                        <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                            <?php echo ! empty($section['is_active']) ? 'checked' : ''; ?>>
                                    </label>
                                </div>
                                <span class="adminHint">Le slug reste stable et l'ordre se gère depuis le tri de la
                                    liste.</span>

                                <label class="adminField">
                                    <span class="adminField__label">Description</span>
                                    <textarea class="adminTextarea" name="description"
                                        rows="3"><?php echo $e($section['description'] ?? ''); ?></textarea>
                                </label>

                                <div class="adminInlineActions">
                                    <button type="submit" class="adminBtn adminBtn--primary">Enregistrer la
                                        section</button>
                                    <button type="submit" class="adminBtn adminBtn--danger"
                                        formaction="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/delete"
                                        formmethod="post"
                                        onclick="return confirm('Supprimer cette section et tous ses produits ?');">Supprimer
                                        la section</button>
                                </div>
                            </form>
                        </details>

                        <details class="adminEditorBlock adminEditorBlock--collapsible">
                            <summary class="adminEditorBlock__summary">
                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                    <div class="adminEditorBlock__title">Ajouter un produit</div>
                                    <p class="adminEditorBlock__text">Créez une nouvelle référence avec son prix, son
                                        stock et sa limite par commande.</p>
                                </div>
                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                            </summary>

                            <form
                                action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/create"
                                method="post" enctype="multipart/form-data" class="adminForm adminForm--create">
                                <div class="adminCatalogEditorGrid">
                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Identité du produit</div>
                                        <div class="adminFieldGrid">
                                            <label class="adminField">
                                                <span class="adminField__label">Nom</span>
                                                <input class="adminInput" type="text" name="name" required>
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Libellé prix</span>
                                                <input class="adminInput" type="text" name="price_label"
                                                    placeholder="Ex: 12 EUR piece">
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Prix</span>
                                                <input class="adminInput" type="text" name="price_euros"
                                                    inputmode="decimal" placeholder="Ex: 12,50" required>
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Unité de stock</span>
                                                <select class="adminSelect" name="stock_unit">
                                                    <option value="unit" selected>Unités</option>
                                                    <option value="g">Grammes (1000 = 1 kg)</option>
                                                </select>
                                            </label>
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Visible</span>
                                                <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                                    checked>
                                            </label>
                                        </div>
                                        <span class="adminHint">Le prix est saisi en euros, puis le slug et l'ordre
                                            d'affichage sont gérés automatiquement.</span>
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Stock & vente</div>
                                        <div class="adminFieldGrid">
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Stock</span>
                                                <input class="adminInput" type="number" min="0" name="stock_quantity"
                                                    value="0" required>
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Seuil bas</span>
                                                <input class="adminInput" type="number" min="0"
                                                    name="low_stock_threshold" value="5">
                                            </label>
                                        </div>
                                        <span class="adminHint">Le stock saisi ici correspond à l'unité de stock du produit. Pour une vente au poids, utilisez les grammes : 1000 = 1 kg.</span>
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Image du produit</div>
                                        <div class="adminFieldGrid adminFieldGrid--two">
                                            <label class="adminField">
                                                <span class="adminField__label">Image</span>
                                                <input class="adminInput" type="text" name="image_path"
                                                    placeholder="/uploads/pages/...">
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Alt image</span>
                                                <input class="adminInput" type="text" name="image_alt">
                                            </label>
                                        </div>
                                        <label class="adminField">
                                            <span class="adminField__label">Upload image (PNG/JPG/WEBP)</span>
                                            <input class="adminInput" type="file" name="image_file"
                                                accept="image/png,image/jpeg,image/webp">
                                            <span class="adminHint">Le fichier est converti automatiquement dans les
                                                formats utiles à la boutique.</span>
                                        </label>
                                        <div class="adminFieldGrid adminFieldGrid--two">
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Supprimer le fond automatiquement</span>
                                                <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1"
                                                    checked>
                                            </label>
                                        </div>
                                        <input type="hidden" name="background_fuzz" value="6">
                                        <span class="adminHint">Vous pouvez générer un aperçu avant enregistrement,
                                            comme sur la carte événementielle.</span>
                                        <div class="adminInlineActions adminInlineActions--image">
                                            <button type="submit" class="adminBtn adminBtn--primary"
                                                data-image-save-button disabled>Créer et enregistrer l'image</button>
                                        </div>
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                        <div class="adminCatalogSubsection">Texte public</div>
                                        <label class="adminField">
                                            <span class="adminField__label">Description</span>
                                            <textarea class="adminTextarea" name="short_description"
                                                rows="3"></textarea>
                                        </label>
                                    </section>
                                </div>

                                <div class="adminInlineActions">
                                    <button type="submit" class="adminBtn adminBtn--primary">Ajouter le produit</button>
                                </div>
                            </form>
                        </details>
                    </div>

                    <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/reorder"
                        method="post" class="adminReorderForm"
                        id="catalogItemOrderForm-<?php echo (int) ($section['id'] ?? 0); ?>">
                        <input type="hidden" name="item_ids"
                            id="catalogItemOrderInput-<?php echo (int) ($section['id'] ?? 0); ?>" value="">
                    </form>

                    <div class="adminCatalogItemsHead">
                        <div>
                            <div class="adminCatalogOptions__title">Produits de la section</div>
                            <span class="adminHint">Ouvrez un produit pour modifier ses informations, son image et son
                                stock.</span>
                        </div>
                        <div class="adminInlineActions adminInlineActions--reorder">
                            <details class="adminItemMenu">
                                <summary class="adminBtn">Section</summary>
                                <div class="adminItemMenu__content">
                                    <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/visibility" method="post">
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" class="adminItemMenu__action">Rendre tous visibles</button>
                                    </form>
                                    <form action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/visibility" method="post">
                                        <input type="hidden" name="is_active" value="0">
                                        <button type="submit" class="adminItemMenu__action" onclick="return confirm('Masquer tous les produits de cette section ?');">Masquer tous les produits</button>
                                    </form>
                                </div>
                            </details>
                            <button type="button" class="adminBtn"
                                data-reorder-toggle="items-<?php echo (int) ($section['id'] ?? 0); ?>">Réordonner</button>
                            <button type="button" class="adminBtn adminBtn--primary"
                                data-reorder-save="items-<?php echo (int) ($section['id'] ?? 0); ?>" hidden
                                disabled>Enregistrer l'ordre</button>
                            <button type="button" class="adminBtn"
                                data-reorder-cancel="items-<?php echo (int) ($section['id'] ?? 0); ?>"
                                hidden>Annuler</button>
                        </div>
                    </div>

                    <div class="adminCatalogItems" data-item-sortable
                        data-section-id="<?php echo (int) ($section['id'] ?? 0); ?>"
                        data-reorder-scope="items-<?php echo (int) ($section['id'] ?? 0); ?>"
                        data-reorder-method="buttons">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                        <?php
                            $itemSearch = strtolower(trim(
                                (string) ($item['name'] ?? '') . ' ' .
                                (string) ($item['slug'] ?? '') . ' ' .
                                (string) ($item['short_description'] ?? '') . ' ' .
                                (string) ($item['price_label'] ?? '')
                            ));
                            $itemVisibilityLabel   = ! empty($item['is_active']) ? 'Visible' : 'Masque';
                            $itemDescription       = trim((string) ($item['short_description'] ?? ''));
                            $itemStock             = max(0, (int) ($item['stock_quantity'] ?? 0));
                            $itemStockUnit         = $normalizeStockUnit($item['stock_unit'] ?? 'unit');
                            $itemStockDisplay      = $formatStockQuantity($itemStock, $itemStockUnit);
                            $itemLowStockThreshold = max(0, (int) ($item['low_stock_threshold'] ?? 0));
                            $itemThresholdDisplay  = $formatStockQuantity($itemLowStockThreshold, $itemStockUnit);
                            $itemOptionsCount      = count($item['options'] ?? []);
                            $itemStockState        = $itemStock <= 0
                                ? 'Rupture'
                                : ($itemLowStockThreshold > 0 && $itemStock <= $itemLowStockThreshold ? 'Stock bas' : 'Disponible');
                            $itemImagePath   = trim((string) ($item['image_path'] ?? ''));
                            $itemFilterState = $itemStock <= 0 ? 'sold-out' : ($itemLowStockThreshold > 0 && $itemStock <= $itemLowStockThreshold ? 'low-stock' : 'available');
                            $itemFormId      = 'shop-item-form-' . (int) ($item['id'] ?? 0);
                        ?>
                        <details class="adminCatalogItem" id="item-<?php echo (int) ($item['id'] ?? 0); ?>"
                            data-item-id="<?php echo (int) ($item['id'] ?? 0); ?>" draggable="false" data-catalog-item
                            data-catalog-search-text="<?php echo $e($itemSearch); ?>"
                            data-catalog-stock-state="<?php echo $e($itemFilterState); ?>"
                            data-catalog-visibility="<?php echo ! empty($item['is_active']) ? 'visible' : 'hidden'; ?>"
                            data-catalog-image-state="<?php echo $itemImagePath === '' ? 'missing' : 'present'; ?>">
                            <summary class="adminCatalogItem__summary">
                                <div class="adminCatalogItem__thumb" aria-hidden="true">
                                    <?php if ($itemImagePath !== ''): ?>
                                    <img src="<?php echo $e($itemImagePath); ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                    <span>Sans image</span>
                                    <?php endif; ?>
                                </div>
                                <div class="adminCatalogItem__summaryMain">
                                    <strong><?php echo $e($item['name'] ?? 'Produit'); ?></strong>
                                    <div class="adminCatalogMeta adminCatalogMeta--inline">
                                        <span><?php echo $e($formatItemPrice($item)); ?></span>
                                        <span>Stock : <?php echo $e($itemStockDisplay); ?></span>
                                        <span class="adminStockBadge adminStockBadge--<?php echo $e($itemFilterState); ?>"><?php echo $itemStockState; ?></span>
                                        <span><?php echo $itemOptionsCount; ?> option(s)</span>
                                        <span><?php echo $itemVisibilityLabel; ?></span>
                                        <?php if ($itemImagePath === ''): ?>
                                        <span class="adminImageMissingBadge">Sans image</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($itemDescription !== ''): ?>
                                    <p class="adminCatalogItem__summaryText"><?php echo $e($itemDescription); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="adminCatalogItem__reorderTools">
                                    <button type="button" class="adminOrderStep" data-reorder-move="up"
                                        aria-label="Monter ce produit">↑</button>
                                    <button type="button" class="adminOrderStep" data-reorder-move="down"
                                        aria-label="Descendre ce produit">↓</button>
                                </div>
                            </summary>

                            <div class="adminCatalogItem__body">
                                <div class="adminCatalogItemsHead adminCatalogItemsHead--item">
                                    <div>
                                        <div class="adminCatalogOptions__title">Édition du produit</div>
                                        <div class="adminCatalogMeta adminCatalogMeta--inline">
                                            <span>Slug : <?php echo $e($item['slug'] ?? ''); ?></span>
                                            <span>Seuil bas : <?php echo $e($itemThresholdDisplay); ?></span>
                                            <span>Section : <?php echo $e($section['name'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                    <div class="adminItemActions">
                                        <button type="submit" class="adminBtn adminBtn--primary" form="<?php echo $e($itemFormId); ?>">Enregistrer</button>
                                        <details class="adminItemMenu">
                                            <summary class="adminBtn" aria-label="Actions du produit">Actions</summary>
                                            <div class="adminItemMenu__content">
                                                <button type="button" class="adminItemMenu__action" data-open-product-panel="stock">Modifier le stock</button>
                                                <button type="button" class="adminItemMenu__action" data-open-product-panel="image">Modifier l'image</button>
                                                <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/visibility" method="post">
                                                    <input type="hidden" name="is_active" value="<?php echo ! empty($item['is_active']) ? '0' : '1'; ?>">
                                                    <button type="submit" class="adminItemMenu__action">
                                                        <?php echo ! empty($item['is_active']) ? 'Masquer le produit' : 'Rendre visible'; ?>
                                                    </button>
                                                </form>
                                                <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/duplicate" method="post">
                                                    <button type="submit" class="adminItemMenu__action">Dupliquer</button>
                                                </form>
                                                <button type="submit" class="adminItemMenu__action adminItemMenu__action--danger" form="<?php echo $e($itemFormId); ?>"
                                                    formaction="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/delete"
                                                    formmethod="post"
                                                    onclick="return confirm('Supprimer ce produit et ses options ?');">Supprimer</button>
                                            </div>
                                        </details>
                                    </div>
                                </div>

                                <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>"
                                    method="post" enctype="multipart/form-data" class="adminForm" id="<?php echo $e($itemFormId); ?>">
                                    <div class="adminCatalogEditorGrid adminCatalogEditorGrid--stacked">
                                        <details
                                            class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--collapsible"
                                            open>
                                            <summary class="adminEditorBlock__summary">
                                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                                    <div class="adminEditorBlock__title">Informations du produit</div>
                                                    <p class="adminEditorBlock__text">Nom, prix public et visibilité de
                                                        la fiche.</p>
                                                </div>
                                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                                            </summary>

                                            <div class="adminFieldGrid">
                                                <label class="adminField">
                                                    <span class="adminField__label">Nom</span>
                                                    <input class="adminInput" type="text" name="name"
                                                        value="<?php echo $e($item['name'] ?? ''); ?>" required>
                                                </label>
                                                <label class="adminField">
                                                    <span class="adminField__label">Libellé prix</span>
                                                    <input class="adminInput" type="text" name="price_label"
                                                        value="<?php echo $e($item['price_label'] ?? ''); ?>">
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Prix</span>
                                                    <input class="adminInput" type="text" name="price_euros"
                                                        inputmode="decimal"
                                                        value="<?php echo $e($formatPriceInput($item['price_cents'] ?? 0)); ?>"
                                                        required>
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Unité de stock</span>
                                                    <select class="adminSelect" name="stock_unit">
                                                        <option value="unit" <?php echo $itemStockUnit === 'unit' ? 'selected' : ''; ?>>Unités</option>
                                                        <option value="g" <?php echo $itemStockUnit === 'g' ? 'selected' : ''; ?>>Grammes (1000 = 1 kg)</option>
                                                    </select>
                                                </label>
                                                <label class="adminField adminField--checkbox">
                                                    <span class="adminField__label">Visible</span>
                                                    <input class="adminCheckbox" type="checkbox" name="is_active"
                                                        value="1"
                                                        <?php echo ! empty($item['is_active']) ? 'checked' : ''; ?>>
                                                </label>
                                            </div>
                                            <span class="adminHint">Le slug reste stable et l'ordre se gère depuis le
                                                tri de la section.</span>
                                        </details>

                                        <details
                                            class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--collapsible"
                                            data-product-panel="stock">
                                            <summary class="adminEditorBlock__summary">
                                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                                    <div class="adminEditorBlock__title">Stock & limites</div>
                                                    <p class="adminEditorBlock__text">Quantité réelle disponible et
                                                        seuil d'alerte.</p>
                                                </div>
                                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                                            </summary>

                                            <div class="adminFieldGrid">
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Stock</span>
                                                    <input class="adminInput" type="number" min="0"
                                                        name="stock_quantity" value="<?php echo $itemStock; ?>"
                                                        required>
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Seuil bas</span>
                                                    <input class="adminInput" type="number" min="0"
                                                        name="low_stock_threshold"
                                                        value="<?php echo $itemLowStockThreshold; ?>">
                                                </label>
                                            </div>
                                            <div class="adminCatalogMeta adminCatalogMeta--inline">
                                                <span>Etat : <?php echo $itemStockState; ?></span>
                                                <span>Prix public : <?php echo $e($formatItemPrice($item)); ?></span>
                                            </div>
                                            <span class="adminHint">Le prix saisi ici est converti automatiquement en cents au moment de l'enregistrement. En mode poids, stock et seuil bas sont saisis en grammes.</span>
                                        </details>

                                        <details
                                            class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--collapsible"
                                            data-product-panel="image">
                                            <summary class="adminEditorBlock__summary">
                                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                                    <div class="adminEditorBlock__title">Image du produit</div>
                                                    <p class="adminEditorBlock__text">Image, alt et traitement de fond
                                                        du produit.</p>
                                                </div>
                                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                                            </summary>

                                            <div class="adminFieldGrid adminFieldGrid--two">
                                                <label class="adminField">
                                                    <span class="adminField__label">Image</span>
                                                    <input class="adminInput" type="text" name="image_path"
                                                        value="<?php echo $e($item['image_path'] ?? ''); ?>">
                                                </label>
                                                <label class="adminField">
                                                    <span class="adminField__label">Texte alternatif</span>
                                                    <input class="adminInput" type="text" name="image_alt"
                                                        value="<?php echo $e($item['image_alt'] ?? ''); ?>">
                                                </label>
                                            </div>
                                            <label class="adminField">
                                                <span class="adminField__label">Upload image (PNG/JPG/WEBP)</span>
                                                <input class="adminInput" type="file" name="image_file"
                                                    accept="image/png,image/jpeg,image/webp">
                                                <span class="adminHint">Si un fichier est envoyé, il remplace l'image
                                                    actuelle du produit.</span>
                                            </label>
                                            <div class="adminFieldGrid adminFieldGrid--two">
                                                <label class="adminField adminField--checkbox">
                                                    <span class="adminField__label">Supprimer le fond
                                                        automatiquement</span>
                                                    <input class="adminCheckbox" type="checkbox" name="remove_bg"
                                                        value="1" checked>
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Tolérance fond (%)</span>
                                                    <input class="adminInput" type="number" name="background_fuzz"
                                                        value="6" min="0" max="40">
                                                </label>
                                            </div>
                                            <span class="adminHint">L'aperçu permet de vérifier le rendu avant
                                                d'enregistrer définitivement l'image.</span>

                                            <div class="adminInlineActions adminInlineActions--image">
                                                <button type="submit" class="adminBtn adminBtn--primary"
                                                    data-image-save-button disabled>
                                                    Enregistrer l'image
                                                </button>
                                            </div>

                                            <?php if (($item['image_path'] ?? '') !== ''): ?>
                                            <div class="adminImagePreview">
                                                <div class="adminImagePreview__thumbWrap">
                                                    <img class="adminImagePreview__thumb"
                                                        src="<?php echo $e($item['image_path'] ?? ''); ?>"
                                                        alt="<?php echo $e($item['image_alt'] ?? ($item['name'] ?? 'Image produit')); ?>"
                                                        loading="lazy">
                                                </div>
                                                <div class="adminImagePreview__meta">
                                                    <div><strong>Image actuelle :</strong>
                                                        <?php echo $e($item['image_path'] ?? ''); ?></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </details>

                                        <details
                                            class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--collapsible adminEditorBlock--full">
                                            <summary class="adminEditorBlock__summary">
                                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                                    <div class="adminEditorBlock__title">Texte public</div>
                                                    <p class="adminEditorBlock__text">Description courte affichée sur la
                                                        boutique.</p>
                                                </div>
                                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                                            </summary>

                                            <label class="adminField adminField--full">
                                                <span class="adminField__label">Description</span>
                                                <textarea class="adminTextarea" name="short_description"
                                                    rows="4"><?php echo $e($item['short_description'] ?? ''); ?></textarea>
                                            </label>
                                        </details>
                                    </div>

                                    <input type="hidden" name="section_id"
                                        value="<?php echo (int) ($section['id'] ?? 0); ?>">

                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn adminBtn--primary">Enregistrer les modifications</button>
                                    </div>
                                </form>

                                <?php $options                   = $item['options'] ?? []; ?>
                                <?php $optionQuantityLabel       = $itemStockUnit === 'g' ? 'Quantité vendue (g)' : 'Taille du lot'; ?>
                                <?php $optionQuantityPlaceholder = $itemStockUnit === 'g' ? 'Ex: 100' : 'Ex: 4'; ?>
                                <?php $optionCreateDefault       = $itemStockUnit === 'g' ? '100' : ''; ?>
                                <?php $optionLabelPlaceholder    = $itemStockUnit === 'g' ? 'Libellé, ex: Barquette 100 g' : 'Libellé, ex: Unité, Lot de 4, Lot de 6'; ?>
                                <details
                                    class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--collapsible adminEditorBlock--full adminCatalogOptions"
                                    <?php echo ! empty($options) ? 'open' : ''; ?>>
                                    <summary class="adminEditorBlock__summary">
                                        <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                            <div class="adminEditorBlock__title">Options d'achat</div>
                                            <p class="adminEditorBlock__text">Unité, lots et variantes de vente pour ce
                                                produit.</p>
                                        </div>
                                        <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                                    </summary>

                                    <div class="adminCatalogOptions__body">
                                        <?php if (! empty($options)): ?>
                                        <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/options/reorder"
                                            method="post" class="adminReorderForm"
                                            id="catalogOptionOrderForm-<?php echo (int) ($item['id'] ?? 0); ?>">
                                            <input type="hidden" name="option_ids"
                                                id="catalogOptionOrderInput-<?php echo (int) ($item['id'] ?? 0); ?>" value="">
                                        </form>

                                        <div class="adminCatalogItemsHead adminCatalogItemsHead--item">
                                            <div>
                                                <div class="adminCatalogOptions__title">Ordre des options</div>
                                                <span class="adminHint">Réorganisez les formats de vente affichés pour ce produit.</span>
                                            </div>
                                            <div class="adminInlineActions adminInlineActions--reorder">
                                                <button type="button" class="adminBtn"
                                                    data-reorder-toggle="options-<?php echo (int) ($item['id'] ?? 0); ?>">Réordonner</button>
                                                <button type="button" class="adminBtn adminBtn--primary"
                                                    data-reorder-save="options-<?php echo (int) ($item['id'] ?? 0); ?>" hidden
                                                    disabled>Enregistrer l'ordre</button>
                                                <button type="button" class="adminBtn"
                                                    data-reorder-cancel="options-<?php echo (int) ($item['id'] ?? 0); ?>"
                                                    hidden>Annuler</button>
                                            </div>
                                        </div>

                                        <div class="adminTableWrap">
                                            <?php foreach ($options as $option): ?>
                                            <form id="shop-option-form-<?php echo (int) $option['id']; ?>"
                                                action="/admin/boutique/options/<?php echo (int) $option['id']; ?>"
                                                method="post" class="adminOptionTableForm"></form>
                                            <?php endforeach; ?>
                                            <table class="adminTable adminTable--options">
                                                <thead>
                                                    <tr>
                                                        <th>Libellé</th>
                                                        <th><?php echo $e($optionQuantityLabel); ?></th>
                                                        <th>Stock option</th>
                                                        <th>Prix</th>
                                                        <th>Visible</th>
                                                        <th>Ordre</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody data-option-sortable
                                                    data-item-id="<?php echo (int) ($item['id'] ?? 0); ?>"
                                                    data-reorder-scope="options-<?php echo (int) ($item['id'] ?? 0); ?>"
                                                    data-reorder-method="buttons">
                                                    <?php foreach ($options as $option): ?>
                                                    <?php $optionFormId        = 'shop-option-form-' . (int) $option['id']; ?>
                                                    <?php $optionQuantityValue = $resolveOptionQuantityInputValue($option, $itemStockUnit); ?>
                                                    <?php $optionStockValue    = $resolveOptionStockInputValue($option); ?>
                                                    <tr id="option-<?php echo (int) $option['id']; ?>"
                                                        data-option-id="<?php echo (int) $option['id']; ?>">
                                                        <td data-label="Libellé">
                                                            <input class="adminInput" type="text" name="label"
                                                                value="<?php echo $e($option['label'] ?? ''); ?>"
                                                                form="<?php echo $e($optionFormId); ?>" required>
                                                        </td>
                                                        <td data-label="<?php echo $e($optionQuantityLabel); ?>">
                                                            <input class="adminInput adminInput--sm" type="number"
                                                                name="quantity" min="1"
                                                                value="<?php echo $optionQuantityValue; ?>"
                                                                placeholder="<?php echo $e($optionQuantityPlaceholder); ?>"
                                                                form="<?php echo $e($optionFormId); ?>" required>
                                                        </td>
                                                        <td data-label="Stock option">
                                                            <input class="adminInput adminInput--sm" type="number"
                                                                name="stock_quantity" min="0"
                                                                value="<?php echo $e($optionStockValue); ?>"
                                                                placeholder="Ex: 1"
                                                                form="<?php echo $e($optionFormId); ?>">
                                                        </td>
                                                        <td data-label="Prix">
                                                            <input class="adminInput" type="text" name="price_euros"
                                                                inputmode="decimal"
                                                                value="<?php echo isset($option['price_cents']) ? number_format(((int) $option['price_cents']) / 100, 2, ',', '') : ''; ?>"
                                                                form="<?php echo $e($optionFormId); ?>">
                                                        </td>
                                                        <td data-label="Visible">
                                                            <label class="adminOptionVisibility">
                                                                <input class="adminCheckbox" type="checkbox"
                                                                    name="is_active" value="1"
                                                                    form="<?php echo $e($optionFormId); ?>"
                                                                    <?php echo ! empty($option['is_active']) ? 'checked' : ''; ?>>
                                                                <span>Visible</span>
                                                            </label>
                                                        </td>
                                                        <td data-label="Ordre">
                                                            <div class="adminCatalogItem__reorderTools">
                                                                <button type="button" class="adminOrderStep" data-reorder-move="up"
                                                                    aria-label="Monter cette option">↑</button>
                                                                <button type="button" class="adminOrderStep" data-reorder-move="down"
                                                                    aria-label="Descendre cette option">↓</button>
                                                            </div>
                                                        </td>
                                                        <td data-label="Actions">
                                                            <div class="adminOptionActions">
                                                                <button type="submit" class="adminBtn adminBtn--sm"
                                                                    form="<?php echo $e($optionFormId); ?>">
                                                                    Enregistrer
                                                                </button>

                                                                <button type="submit"
                                                                    class="adminBtn adminBtn--danger adminBtn--sm"
                                                                    form="<?php echo $e($optionFormId); ?>"
                                                                    formaction="/admin/boutique/options/<?php echo (int) $option['id']; ?>/delete"
                                                                    formmethod="post"
                                                                    onclick="return confirm('Supprimer cette option ?');">
                                                                    Supprimer
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <div class="adminHint">Aucune option de lot n'est définie pour ce produit.</div>
                                        <?php endif; ?>

                                        <form
                                            action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/options/create"
                                            method="post" class="adminForm adminForm--inline">
                                            <input type="text" name="label" placeholder="<?php echo $e($optionLabelPlaceholder); ?>" required
                                                class="adminInput adminInput--sm">

                                            <input type="number" name="quantity" min="1"
                                                value="<?php echo $e($optionCreateDefault); ?>"
                                                placeholder="<?php echo $e($optionQuantityPlaceholder); ?>" required
                                                class="adminInput adminInput--sm">

                                            <input type="number" name="stock_quantity" min="0"
                                                placeholder="Stock option, ex: 1"
                                                class="adminInput adminInput--sm">

                                            <input type="text" name="price_euros" inputmode="decimal"
                                                placeholder="Prix, ex: 12,50" class="adminInput adminInput--sm">

                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Visible</span>
                                                <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                                    checked>
                                            </label>

                                            <button type="submit" class="adminBtn adminBtn--primary adminBtn--sm">
                                                Ajouter une option
                                            </button>
                                        </form>

                                        <span class="adminHint"><?php echo $itemStockUnit === 'g'
                                                                    ? 'Pour une vente au poids, saisissez la quantité vendue en grammes : 100 pour 100 g, 250 pour 250 g, 1000 pour 1 kg. Le libellé sert surtout à l’affichage public.'
                                                                    : 'Pour une vente à l’unité, indiquez la taille du lot : 1 pour « Unité », 4 pour « Lot de 4 », 6 pour « Lot de 6 ». Si le libellé contient déjà « Lot de 8 », gardez aussi 8 ici pour que le stock reste lisible en admin.'; ?></span>
                                        <span class="adminHint">Le champ "Stock option" limite une option précise. Laissez vide pour n'utiliser que le stock global du produit. Mettez 1 pour une pièce unique, 2 ou plus pour un stock limité sur cette option.</span>
                                    </div>
                                </details>
                            </div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
        <div class="adminStickySave" data-sticky-save hidden>
            <span data-sticky-save-label>Vous avez des modifications non enregistrées.</span>
            <button type="button" class="adminBtn" data-sticky-save-discard>Annuler les modifications</button>
            <button type="button" class="adminBtn adminBtn--primary" data-sticky-save-submit>Enregistrer les modifications</button>
        </div>
    </main>
</div>