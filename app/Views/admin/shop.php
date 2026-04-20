<?php
    $sections      = is_array($sections ?? null) ? $sections : [];
    $stats         = is_array($stats ?? null) ? $stats : [];
    $orderStats    = is_array($orderStats ?? null) ? $orderStats : [];
    $recentOrders  = is_array($recentOrders ?? null) ? $recentOrders : [];
    $lowStockItems = is_array($lowStockItems ?? null) ? $lowStockItems : [];
    $statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
    $imageRuntime  = is_array($imageRuntime ?? null) ? $imageRuntime : [];
    $flash         = is_array($flash ?? null) ? $flash : null;
    $loadError     = is_string($loadError ?? null) ? $loadError : null;

    $activeSections   = 0;
    $inactiveSections = 0;
    $activeItems      = 0;
    $inactiveItems    = 0;

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
    }
    }

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $formatPrice = static function ($cents): string {
    return number_format(((int) $cents) / 100, 2, ',', ' ') . ' EUR';
    };

    $formatDateTime = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    };

    $formatItemPrice = static function (array $item) use ($formatPrice): string {
    $priceLabel = trim((string) ($item['price_label'] ?? ''));
    return $priceLabel !== '' ? $priceLabel : $formatPrice($item['price_cents'] ?? 0);
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
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Editer la boutique</h2>
                    <p class="adminSubtitle">Le flux boutique reste distinct de la carte : ici vous gerez les sections,
                        les produits, le stock reel et le suivi des commandes.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/admin/catalog" class="adminBtn">Carte evenementielle</a>
                    <a href="/boutique-en-ligne" class="adminBtn adminBtn--primary">Voir la boutique</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Deconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>">
            <?php echo $e($flash['message'] ?? 'Modification enregistree.'); ?>
        </div>
        <?php endif; ?>

        <?php if ($loadError !== null): ?>
        <div class="adminFlash adminFlash--error">
            <?php echo $e($loadError); ?>
        </div>
        <?php endif; ?>

        <section class="adminStats adminStats--panel" aria-label="Statistiques boutique">
            <div class="statCard">
                <div class="statCard__label">Sections totales</div>
                <div class="statCard__value"><?php echo (int) ($stats['total_sections'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Sections visibles</div>
                <div class="statCard__value"><?php echo $activeSections; ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Produits visibles</div>
                <div class="statCard__value"><?php echo $activeItems; ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Stocks bas</div>
                <div class="statCard__value"><?php echo (int) ($stats['low_stock_items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Nouvelles commandes</div>
                <div class="statCard__value"><?php echo (int) ($orderStats['new_count'] ?? 0); ?></div>
            </div>
        </section>

        <?php if ($loadError === null): ?>
        <section class="adminCatalogUtilityGrid" aria-label="Outils boutique">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Retrouver rapidement un produit</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Cherchez une section ou un produit sans quitter l'ecran</span>
                    </div>
                </div>

                <div class="adminCatalogToolbar">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" placeholder="Ex: brunch, tartelette, formule..."
                            data-catalog-search>
                    </label>

                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Acceder a une section</span>
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
                        <button type="button" class="adminBtn" data-catalog-expand-all>Tout ouvrir</button>
                        <button type="button" class="adminBtn" data-catalog-collapse-all>Tout fermer</button>
                    </div>
                </div>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Repere stock & suivi</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">La boutique se pilote par disponibilite immediate et non par options de
                            devis</span>
                    </div>
                </div>

                <div class="adminDashboardStatus adminDashboardStatus--compact">
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Sections masquees</div>
                        <div class="adminStatusPill__value"><?php echo $inactiveSections; ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Produits masques</div>
                        <div class="adminStatusPill__value"><?php echo $inactiveItems; ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Ruptures</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['sold_out_items'] ?? 0); ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Commandes confirmees</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($orderStats['confirmed_count'] ?? 0); ?>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="adminCard adminCard--padded adminCatalogGuideCard">
            <div class="adminCard__head">
                <div class="adminCard__title">Mode d'emploi</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Trois reflexes simples pour garder une boutique nette et exploitable</span>
                </div>
            </div>

            <div class="adminCatalogGuideGrid">
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">1. Structurer</div>
                    <div class="adminQuickLinkCard__title">Section puis produit</div>
                    <div class="adminQuickLinkCard__meta">Classez les references par famille avant d'ajouter les details
                        de vente.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">2. Stock</div>
                    <div class="adminQuickLinkCard__title">Verifier la quantite reelle</div>
                    <div class="adminQuickLinkCard__meta">Le stock et la quantite maximale par commande doivent rester
                        coherents pour eviter la survente.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">3. Publication</div>
                    <div class="adminQuickLinkCard__title">Masquer sans supprimer</div>
                    <div class="adminQuickLinkCard__meta">Desactivez un produit pour le retirer de la vente tout en
                        conservant sa fiche.</div>
                </div>
            </div>
        </section>

        <section class="adminCard adminCatalogCreateSection">
            <div class="adminCard__head">
                <div class="adminCard__title">Ajouter une section</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Le slug est auto-genere si vous le laissez vide.</span>
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
                            <span class="adminField__label">Slug (optionnel)</span>
                            <input class="adminInput" type="text" name="slug" placeholder="auto-genere si vide">
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
                        <button type="submit" class="adminBtn adminBtn--primary">Creer la section</button>
                    </div>
                </form>
            </div>
        </section>

        <form action="/admin/boutique/sections/reorder" method="post" id="catalogSectionOrderForm"
            class="adminReorderForm">
            <input type="hidden" name="section_ids" id="catalogSectionOrderInput" value="">
        </form>

        <div class="adminCatalogList" data-section-sortable>
            <?php foreach ($sections as $section): ?>
            <?php
                $sectionName      = (string) ($section['name'] ?? 'Section');
                $sectionSlug      = (string) ($section['slug'] ?? '');
                $sectionSearch    = strtolower(trim($sectionName . ' ' . $sectionSlug . ' ' . (string) ($section['description'] ?? '')));
                $sectionState     = ! empty($section['is_active']) ? 'Visible' : 'Masquee';
                $sectionItemCount = count($section['items'] ?? []);
                $sectionStock     = 0;
                foreach (($section['items'] ?? []) as $sectionItemForStock) {
                    $sectionStock += max(0, (int) ($sectionItemForStock['stock_quantity'] ?? 0));
                }
            ?>
            <details class="adminCard adminCatalogSection" id="section-<?php echo (int) ($section['id'] ?? 0); ?>"
                data-section-id="<?php echo (int) ($section['id'] ?? 0); ?>" draggable="true" data-catalog-section
                data-catalog-search-text="<?php echo $e($sectionSearch); ?>">
                <summary class="adminCard__head adminCatalogSection__summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($sectionName); ?></div>
                        <div class="adminCatalogMeta">
                            <span>Slug : <?php echo $e($sectionSlug); ?></span>
                            <span><?php echo $sectionItemCount; ?> produit(s)</span>
                            <span><?php echo $sectionStock; ?> unite(s) en stock</span>
                            <span><?php echo $sectionState; ?></span>
                        </div>
                    </div>
                    <div class="adminCatalogSection__headActions" aria-hidden="true">
                        <span class="adminCatalogSection__chevron">▾</span>
                        <span class="adminDragHandle">↕</span>
                    </div>
                </summary>

                <div class="adminCatalogBody">
                    <div class="adminCatalogSectionGrid">
                        <details class="adminEditorBlock adminEditorBlock--collapsible">
                            <summary class="adminEditorBlock__summary">
                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                    <div class="adminEditorBlock__title">Reglages de la section</div>
                                    <p class="adminEditorBlock__text">Nom, ordre d'affichage et visibilite de la famille
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
                                    <label class="adminField">
                                        <span class="adminField__label">Slug</span>
                                        <input class="adminInput adminInput--readonly" type="text"
                                            value="<?php echo $e($section['slug'] ?? ''); ?>" readonly>
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Ordre</span>
                                        <input class="adminInput" type="number" name="sort_order"
                                            value="<?php echo (int) ($section['sort_order'] ?? 0); ?>">
                                    </label>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Visible</span>
                                        <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                            <?php echo ! empty($section['is_active']) ? 'checked' : ''; ?>>
                                    </label>
                                </div>

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
                                    <p class="adminEditorBlock__text">Creez une nouvelle reference avec son prix, son
                                        stock et sa limite par commande.</p>
                                </div>
                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                            </summary>

                            <form
                                action="/admin/boutique/sections/<?php echo (int) ($section['id'] ?? 0); ?>/items/create"
                                method="post" enctype="multipart/form-data" class="adminForm adminForm--create">
                                <div class="adminCatalogEditorGrid">
                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Identite produit</div>
                                        <div class="adminFieldGrid">
                                            <label class="adminField">
                                                <span class="adminField__label">Nom</span>
                                                <input class="adminInput" type="text" name="name" required>
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Slug (optionnel)</span>
                                                <input class="adminInput" type="text" name="slug"
                                                    placeholder="auto-genere si vide">
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Libelle prix</span>
                                                <input class="adminInput" type="text" name="price_label"
                                                    placeholder="Ex: 12 EUR piece">
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Prix cents</span>
                                                <input class="adminInput" type="number" min="0" name="price_cents"
                                                    value="0" required>
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Ordre</span>
                                                <input class="adminInput" type="number" name="sort_order" value="0">
                                            </label>
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Visible</span>
                                                <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                                    checked>
                                            </label>
                                        </div>
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
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Max / commande</span>
                                                <input class="adminInput" type="number" min="1"
                                                    name="max_order_quantity" value="5">
                                            </label>
                                        </div>
                                        <span class="adminHint">Le panier public se limite automatiquement au minimum
                                            entre le stock reel et cette quantite maximale.</span>
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Visuel</div>
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
                                                formats utiles a la boutique.</span>
                                        </label>
                                        <div class="adminFieldGrid adminFieldGrid--two">
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Supprimer le fond automatiquement</span>
                                                <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1"
                                                    checked>
                                            </label>
                                        </div>
                                        <input type="hidden" name="background_fuzz" value="6">
                                        <span class="adminHint">Vous pouvez generer un apercu avant enregistrement,
                                            comme sur la carte evenementielle.</span>
                                        <div class="adminInlineActions adminInlineActions--image">
                                            <button type="submit" class="adminBtn adminBtn--primary"
                                                data-image-save-button disabled>Creer et enregistrer l'image</button>
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
                        <div class="adminCatalogOptions__title">Produits de la section</div>
                        <span class="adminHint">Ouvrez un produit pour modifier ses informations commerciales et son
                            stock.</span>
                    </div>

                    <div class="adminCatalogItems" data-item-sortable
                        data-section-id="<?php echo (int) ($section['id'] ?? 0); ?>">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                        <?php
                            $itemSearch = strtolower(trim(
                                (string) ($item['name'] ?? '') . ' ' .
                                (string) ($item['slug'] ?? '') . ' ' .
                                (string) ($item['short_description'] ?? '') . ' ' .
                                (string) ($item['price_label'] ?? '')
                            ));
                            $itemVisibilityLabel = ! empty($item['is_active']) ? 'Visible' : 'Masque';
                            $itemDescription     = trim((string) ($item['short_description'] ?? ''));
                            $itemStock           = max(0, (int) ($item['stock_quantity'] ?? 0));
                            $itemMaxOrder        = max(1, (int) ($item['max_order_quantity'] ?? 1));
                        ?>
                        <details class="adminCatalogItem" id="item-<?php echo (int) ($item['id'] ?? 0); ?>"
                            data-item-id="<?php echo (int) ($item['id'] ?? 0); ?>" draggable="true" data-catalog-item
                            data-catalog-search-text="<?php echo $e($itemSearch); ?>">
                            <summary class="adminCatalogItem__summary">
                                <div class="adminCatalogItem__summaryMain">
                                    <strong><?php echo $e($item['name'] ?? 'Produit'); ?></strong>
                                    <div class="adminCatalogMeta adminCatalogMeta--inline">
                                        <span><?php echo $e($item['slug'] ?? ''); ?></span>
                                        <span><?php echo $e($formatItemPrice($item)); ?></span>
                                        <span>Stock : <?php echo $itemStock; ?></span>
                                        <span>Max / cmd : <?php echo $itemMaxOrder; ?></span>
                                        <span><?php echo $itemVisibilityLabel; ?></span>
                                    </div>
                                    <?php if ($itemDescription !== ''): ?>
                                    <p class="adminCatalogItem__summaryText"><?php echo $e($itemDescription); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="adminCatalogItem__drag" aria-hidden="true">↕</span>
                            </summary>

                            <div class="adminCatalogItem__body">
                                <form action="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>"
                                    method="post" enctype="multipart/form-data" class="adminForm">
                                    <div class="adminCatalogEditorGrid">
                                        <section class="adminEditorBlock adminEditorBlock--nested">
                                            <div class="adminCatalogSubsection">Informations produit</div>
                                            <div class="adminFieldGrid">
                                                <label class="adminField">
                                                    <span class="adminField__label">Nom</span>
                                                    <input class="adminInput" type="text" name="name"
                                                        value="<?php echo $e($item['name'] ?? ''); ?>" required>
                                                </label>
                                                <label class="adminField">
                                                    <span class="adminField__label">Slug</span>
                                                    <input class="adminInput adminInput--readonly" type="text"
                                                        value="<?php echo $e($item['slug'] ?? ''); ?>" readonly>
                                                </label>
                                                <label class="adminField">
                                                    <span class="adminField__label">Libelle prix</span>
                                                    <input class="adminInput" type="text" name="price_label"
                                                        value="<?php echo $e($item['price_label'] ?? ''); ?>">
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Prix cents</span>
                                                    <input class="adminInput" type="number" min="0" name="price_cents"
                                                        value="<?php echo (int) ($item['price_cents'] ?? 0); ?>"
                                                        required>
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Ordre</span>
                                                    <input class="adminInput" type="number" name="sort_order"
                                                        value="<?php echo (int) ($item['sort_order'] ?? 0); ?>">
                                                </label>
                                                <label class="adminField adminField--checkbox">
                                                    <span class="adminField__label">Visible</span>
                                                    <input class="adminCheckbox" type="checkbox" name="is_active"
                                                        value="1"
                                                        <?php echo ! empty($item['is_active']) ? 'checked' : ''; ?>>
                                                </label>
                                            </div>
                                        </section>

                                        <section class="adminEditorBlock adminEditorBlock--nested">
                                            <div class="adminCatalogSubsection">Stock & limites</div>
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
                                                        value="<?php echo (int) ($item['low_stock_threshold'] ?? 0); ?>">
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Max / commande</span>
                                                    <input class="adminInput" type="number" min="1"
                                                        name="max_order_quantity" value="<?php echo $itemMaxOrder; ?>">
                                                </label>
                                            </div>
                                            <div class="adminCatalogMeta adminCatalogMeta--inline">
                                                <span>Prix public : <?php echo $e($formatItemPrice($item)); ?></span>
                                                <span><?php echo $itemStock <= 0 ? 'Rupture' : 'Disponible'; ?></span>
                                            </div>
                                        </section>

                                        <section class="adminEditorBlock adminEditorBlock--nested">
                                            <div class="adminCatalogSubsection">Visuel</div>
                                            <div class="adminFieldGrid adminFieldGrid--two">
                                                <label class="adminField">
                                                    <span class="adminField__label">Image</span>
                                                    <input class="adminInput" type="text" name="image_path"
                                                        value="<?php echo $e($item['image_path'] ?? ''); ?>">
                                                </label>
                                                <label class="adminField">
                                                    <span class="adminField__label">Alt image</span>
                                                    <input class="adminInput" type="text" name="image_alt"
                                                        value="<?php echo $e($item['image_alt'] ?? ''); ?>">
                                                </label>
                                            </div>
                                            <label class="adminField">
                                                <span class="adminField__label">Upload image (PNG/JPG/WEBP)</span>
                                                <input class="adminInput" type="file" name="image_file"
                                                    accept="image/png,image/jpeg,image/webp">
                                                <span class="adminHint">Si un fichier est envoye, il remplace l'image
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
                                                    <span class="adminField__label">Tolerance fond (%)</span>
                                                    <input class="adminInput" type="number" name="background_fuzz"
                                                        value="6" min="0" max="40">
                                                </label>
                                            </div>
                                            <span class="adminHint">L'aperçu permet de verifier le rendu avant
                                                d'enregistrer definitivement l'image.</span>
                                            <div class="adminInlineActions adminInlineActions--image">
                                                <button type="submit" class="adminBtn adminBtn--primary"
                                                    data-image-save-button disabled>Enregistrer l'image</button>
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
                                        </section>

                                        <section
                                            class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                            <div class="adminCatalogSubsection">Texte public</div>
                                            <label class="adminField">
                                                <span class="adminField__label">Description</span>
                                                <textarea class="adminTextarea" name="short_description"
                                                    rows="4"><?php echo $e($item['short_description'] ?? ''); ?></textarea>
                                            </label>
                                        </section>
                                    </div>

                                    <input type="hidden" name="section_id"
                                        value="<?php echo (int) ($section['id'] ?? 0); ?>">

                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn adminBtn--primary">Enregistrer le
                                            produit</button>
                                        <button type="submit" class="adminBtn adminBtn--danger"
                                            formaction="/admin/boutique/items/<?php echo (int) ($item['id'] ?? 0); ?>/delete"
                                            formmethod="post"
                                            onclick="return confirm('Supprimer ce produit boutique ?');">Supprimer le
                                            produit</button>
                                    </div>
                                </form>
                            </div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <section class="adminDashboardGrid" id="orders">
            <article class="adminCard adminCard--table">
                <div class="adminCard__head">
                    <div class="adminCard__title">Commandes recentes</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo (int) ($orderStats['total'] ?? 0); ?> commande(s)</span>
                    </div>
                </div>
                <?php if ($recentOrders === []): ?>
                <div class="adminEmptyState">Aucune commande boutique pour le moment.</div>
                <?php else: ?>
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
                                    <strong>
                                        <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminLink">
                                            #<?php echo (int) ($order['id'] ?? 0); ?> · <?php echo $e($order['customer_name'] ?? ''); ?>
                                        </a>
                                    </strong><br>
                                    <?php echo $e($order['customer_email'] ?? ''); ?><br>
                                    <span
                                        class="adminHint"><?php echo $e($formatDateTime($order['created_at'] ?? '')); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $e(($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait'); ?></strong><br>
                                    <?php echo $e($order['pickup_date'] ?? ''); ?><br>
                                    <span class="adminHint"><?php echo $e($order['pickup_slot'] ?? ''); ?></span>
                                    <?php if (($order['fulfillment_method'] ?? 'pickup') === 'delivery'): ?><br>
                                    <span class="adminHint"><?php echo $e(trim((string) (($order['delivery_postal_code'] ?? '') . ' ' . ($order['delivery_city'] ?? '')))); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int) ($order['item_count'] ?? 0); ?> article(s)</td>
                                <td><?php echo $e($formatPrice($order['total_cents'] ?? 0)); ?></td>
                                <td>
                                    <form
                                        action="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>/status"
                                        method="post">
                                        <select class="adminSelect" name="status">
                                            <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                                            <option value="<?php echo $e($statusKey); ?>"
                                                <?php echo (string) ($order['status'] ?? '') === (string) $statusKey ? 'selected' : ''; ?>>
                                                <?php echo $e($statusLabel); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="adminInlineActions">
                                            <button type="submit" class="adminBtn">Enregistrer</button>
                                            <a href="/admin/boutique/orders/<?php echo (int) ($order['id'] ?? 0); ?>" class="adminBtn">Détail</a>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </article>

            <article class="adminCard adminCard--table">
                <div class="adminCard__head">
                    <div class="adminCard__title">Stocks bas</div>
                    <div class="adminCard__meta">
                        <span class="adminHint"><?php echo (int) ($stats['low_stock_items'] ?? 0); ?> produit(s) a
                            surveiller</span>
                    </div>
                </div>
                <?php if ($lowStockItems === []): ?>
                <div class="adminEmptyState">Aucun stock bas detecte actuellement.</div>
                <?php else: ?>
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
                                <td>
                                    <a href="#item-<?php echo (int) ($item['id'] ?? 0); ?>" class="adminLink">
                                        <?php echo $e($item['name'] ?? ''); ?>
                                    </a>
                                </td>
                                <td><?php echo $e($item['section_name'] ?? ''); ?></td>
                                <td><?php echo (int) ($item['stock_quantity'] ?? 0); ?></td>
                                <td><?php echo (int) ($item['low_stock_threshold'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </article>
        </section>
        <?php endif; ?>
    </main>
</div>