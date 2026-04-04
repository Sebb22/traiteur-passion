<?php
    $sections = is_array($sections ?? null) ? $sections : [];
    $stats    = is_array($stats ?? null) ? $stats : ['sections' => 0, 'items' => 0, 'options' => 0];
    $flash    = is_array($flash ?? null) ? $flash : null;

    $activeSections   = 0;
    $inactiveSections = 0;
    $activeItems      = 0;
    $inactiveItems    = 0;
    $activeOptions    = 0;
    $inactiveOptions  = 0;

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

        foreach (($item['options'] ?? []) as $option) {
            if (empty($option['is_active'])) {
                $inactiveOptions++;
            } else {
                $activeOptions++;
            }
        }
    }
    }

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $deriveImageVariants = static function (string $imagePath): array {
    $desktop = trim($imagePath);
    $mobile  = $desktop;
    $source  = '';

    if ($desktop !== '' && substr($desktop, -10) === '-1200.webp') {
        $base   = substr($desktop, 0, -10);
        $mobile = $base . '-600.webp';
        $source = str_replace('/uploads/pages/menu/', '/uploads/pages/menu/sources/', $base) . '.png';
    }

    return [
        'desktop' => $desktop,
        'mobile'  => $mobile,
        'source'  => $source,
    ];
    };
?>
<div class="adminSplit adminSplit--catalog">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">La carte</h1>
            <p class="adminMediaTitle__sub">Menu • edition & image</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Editer la carte</h2>
                    <p class="adminSubtitle">Organisez vos rubriques, mettez à jour vos produits et gérez leurs images depuis une interface plus directe.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/menu" class="adminBtn adminBtn--primary">Voir la carte</a>
                    <a href="/admin/contacts" class="adminBtn">Demandes</a>
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

        <section class="adminStats adminStats--panel" aria-label="Statistiques catalogue">
            <div class="statCard">
                <div class="statCard__label">Sections totales</div>
                <div class="statCard__value"><?php echo (int) ($stats['sections'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Sections visibles</div>
                <div class="statCard__value"><?php echo $activeSections; ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Items visibles</div>
                <div class="statCard__value"><?php echo $activeItems; ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Options visibles</div>
                <div class="statCard__value"><?php echo $activeOptions; ?></div>
            </div>
        </section>

        <section class="adminCatalogUtilityGrid" aria-label="Outils carte">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Retrouver rapidement une rubrique</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Cherchez une section ou un produit sans recharger la page</span>
                    </div>
                </div>

                <div class="adminCatalogToolbar">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" placeholder="Ex: cocktail, saumon, brunch..."
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
                        <button type="button" class="adminBtn" data-catalog-expand-all>Tout ouvrir</button>
                        <button type="button" class="adminBtn" data-catalog-collapse-all>Tout fermer</button>
                    </div>
                </div>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Repères d'édition</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Ce qui est masqué n'est pas supprimé, seulement retiré de l'affichage public</span>
                    </div>
                </div>

                <div class="adminDashboardStatus adminDashboardStatus--compact">
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Sections masquées</div>
                        <div class="adminStatusPill__value"><?php echo $inactiveSections; ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Items masques</div>
                        <div class="adminStatusPill__value"><?php echo $inactiveItems; ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Options masquees</div>
                        <div class="adminStatusPill__value"><?php echo $inactiveOptions; ?></div>
                    </div>
                    <div class="adminStatusPill">
                        <div class="adminStatusPill__label">Options totales</div>
                        <div class="adminStatusPill__value"><?php echo (int) ($stats['options'] ?? 0); ?></div>
                    </div>
                </div>
            </article>
        </section>

        <section class="adminCard adminCard--padded adminCatalogGuideCard">
            <div class="adminCard__head">
                <div class="adminCard__title">Mode d'emploi</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Trois réflexes simples pour garder la carte claire et à jour</span>
                </div>
            </div>

            <div class="adminCatalogGuideGrid">
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">1. Structurer</div>
                    <div class="adminQuickLinkCard__title">Section puis produit</div>
                    <div class="adminQuickLinkCard__meta">Créez d'abord une section, puis ajoutez vos produits et leurs options à l'intérieur.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">2. Image</div>
                    <div class="adminQuickLinkCard__title">Prévisualiser puis enregistrer</div>
                    <div class="adminQuickLinkCard__meta">Ajoutez un fichier, lancez l'aperçu si besoin, puis utilisez le bouton d'enregistrement de l'image.</div>
                </div>
                <div class="adminQuickLinkCard">
                    <div class="adminQuickLinkCard__eyebrow">3. Publication</div>
                    <div class="adminQuickLinkCard__title">Masquer sans supprimer</div>
                    <div class="adminQuickLinkCard__meta">Décochez “Visible” pour retirer un contenu du site tout en le conservant pour plus tard.</div>
                </div>
            </div>
        </section>

        <section class="adminCard adminCatalogCreateSection">
            <div class="adminCard__head">
                <div class="adminCard__title">Ajouter une section</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Le slug est auto-généré si tu le laisses vide.</span>
                </div>
            </div>
            <div class="adminCatalogBody">
                <form action="/admin/catalog/sections/create" method="post" class="adminForm adminForm--create">
                    <div class="adminFieldGrid">
                        <label class="adminField">
                            <span class="adminField__label">Nom</span>
                            <input class="adminInput" type="text" name="name" required>
                        </label>
                        <label class="adminField">
                            <span class="adminField__label">Slug (optionnel)</span>
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

        <form action="/admin/catalog/sections/reorder" method="post" id="catalogSectionOrderForm"
            class="adminReorderForm">
            <input type="hidden" name="section_ids" id="catalogSectionOrderInput" value="">
        </form>

        <div class="adminCatalogList" data-section-sortable>
            <?php foreach ($sections as $section): ?>
            <?php
                $sectionName   = (string) ($section['name'] ?? 'Section');
                $sectionSlug   = (string) ($section['slug'] ?? '');
                $sectionSearch = strtolower(trim($sectionName . ' ' . $sectionSlug . ' ' . (string) ($section['description'] ?? '')));
                $sectionState  = ! empty($section['is_active']) ? 'Visible' : 'Masquee';
            ?>
            <details class="adminCard adminCatalogSection" id="section-<?php echo (int) $section['id']; ?>"
                data-section-id="<?php echo (int) $section['id']; ?>" draggable="true"
                data-catalog-section data-catalog-search-text="<?php echo $e($sectionSearch); ?>">
                <summary class="adminCard__head adminCatalogSection__summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($sectionName); ?></div>
                        <div class="adminCatalogMeta">
                            <span>Slug : <?php echo $e($sectionSlug); ?></span>
                            <span><?php echo count($section['items'] ?? []); ?> produit(s)</span>
                            <span><?php echo (int) ($section['count_options'] ?? 0); ?> option(s)</span>
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
                                    <div class="adminEditorBlock__title">Réglages de la section</div>
                                    <p class="adminEditorBlock__text">Nom, ordre d'affichage et visibilité de la rubrique.</p>
                                </div>
                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                            </summary>

                            <form action="/admin/catalog/sections/<?php echo (int) $section['id']; ?>" method="post"
                                class="adminForm adminForm--section">
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
                                    <button type="submit" class="adminBtn adminBtn--primary">Enregistrer la section</button>
                                    <button type="submit" class="adminBtn adminBtn--danger"
                                        formaction="/admin/catalog/sections/<?php echo (int) $section['id']; ?>/delete"
                                        formmethod="post"
                                        onclick="return confirm('Supprimer cette section et tout son contenu ?');">Supprimer la
                                        section</button>
                                </div>
                            </form>
                        </details>

                        <details class="adminEditorBlock adminEditorBlock--collapsible">
                            <summary class="adminEditorBlock__summary">
                                <div class="adminEditorBlock__head adminEditorBlock__head--summary">
                                    <div class="adminEditorBlock__title">Ajouter un produit</div>
                                    <p class="adminEditorBlock__text">Créez rapidement un nouveau produit dans cette rubrique.</p>
                                </div>
                                <span class="adminEditorBlock__chevron" aria-hidden="true">▾</span>
                            </summary>

                            <form action="/admin/catalog/sections/<?php echo (int) $section['id']; ?>/items/create"
                                method="post" enctype="multipart/form-data" class="adminForm adminForm--create">
                                <div class="adminCatalogEditorGrid">
                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Identité produit</div>
                                        <div class="adminFieldGrid">
                                            <label class="adminField">
                                                <span class="adminField__label">Nom</span>
                                                <input class="adminInput" type="text" name="name" required>
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Slug (optionnel)</span>
                                                <input class="adminInput" type="text" name="slug" placeholder="auto-généré si vide">
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Prix affiché</span>
                                                <input class="adminInput" type="text" name="price_from_label"
                                                    placeholder="Ex: 16,50€/kg">
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
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested">
                                        <div class="adminCatalogSubsection">Image du produit</div>
                                        <div class="adminFieldGrid adminFieldGrid--two">
                                            <label class="adminField">
                                                <span class="adminField__label">Image</span>
                                                <input class="adminInput" type="text" name="image_path"
                                                    placeholder="/uploads/pages/menu/...">
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
                                            <span class="adminHint">Le fichier est converti automatiquement dans les formats utiles au site.</span>
                                        </label>
                                        <div class="adminFieldGrid adminFieldGrid--two">
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Supprimer le fond automatiquement</span>
                                                <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1" checked>
                                            </label>
                                        </div>
                                        <input type="hidden" name="background_fuzz" value="6">
                                        <span class="adminHint">Vous pouvez vérifier l'image avant enregistrement, puis enregistrer uniquement le visuel si nécessaire.</span>
                                        <div class="adminInlineActions adminInlineActions--image">
                                            <button type="submit" class="adminBtn adminBtn--primary" data-image-save-button disabled>Créer et enregistrer l'image</button>
                                        </div>
                                    </section>

                                    <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                        <div class="adminCatalogSubsection">Texte public</div>
                                        <label class="adminField">
                                            <span class="adminField__label">Description</span>
                                            <textarea class="adminTextarea" name="short_description" rows="3"></textarea>
                                        </label>
                                    </section>
                                </div>

                                <div class="adminInlineActions">
                                    <button type="submit" class="adminBtn">Ajouter le produit</button>
                                </div>
                            </form>
                        </details>
                    </div>

                    <form action="/admin/catalog/sections/<?php echo (int) $section['id']; ?>/items/reorder"
                        method="post" class="adminReorderForm"
                        id="catalogItemOrderForm-<?php echo (int) $section['id']; ?>">
                        <input type="hidden" name="item_ids"
                            id="catalogItemOrderInput-<?php echo (int) $section['id']; ?>" value="">
                    </form>

                    <div class="adminCatalogItemsHead">
                        <div class="adminCatalogOptions__title">Produits de la section</div>
                        <span class="adminHint">Ouvrez un produit pour modifier ses informations, son image et ses options.</span>
                    </div>
                    <div class="adminCatalogItems" data-item-sortable
                        data-section-id="<?php echo (int) $section['id']; ?>">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                        <?php
                            $itemSearch = strtolower(trim(
                                (string) ($item['name'] ?? '') . ' ' .
                                (string) ($item['slug'] ?? '') . ' ' .
                                (string) ($item['short_description'] ?? '') . ' ' .
                                (string) ($item['price_from_label'] ?? '')
                            ));
                        ?>
                        <?php
                            $itemOptionCount        = count($item['options'] ?? []);
                            $itemVisibilityLabel    = ! empty($item['is_active']) ? 'Visible' : 'Masqué';
                            $itemPriceLabel         = trim((string) ($item['price_from_label'] ?? ''));
                            $itemDescriptionPreview = trim((string) ($item['short_description'] ?? ''));
                        ?>
                        <details class="adminCatalogItem" id="item-<?php echo (int) $item['id']; ?>"
                            data-item-id="<?php echo (int) $item['id']; ?>" draggable="true"
                            data-catalog-item data-catalog-search-text="<?php echo $e($itemSearch); ?>">
                            <summary class="adminCatalogItem__summary">
                                <div class="adminCatalogItem__summaryMain">
                                    <strong><?php echo $e($item['name'] ?? 'Produit'); ?></strong>
                                    <div class="adminCatalogMeta adminCatalogMeta--inline">
                                        <span><?php echo $e($item['slug'] ?? ''); ?></span>
                                        <?php if ($itemPriceLabel !== ''): ?>
                                        <span><?php echo $e($itemPriceLabel); ?></span>
                                        <?php endif; ?>
                                        <span><?php echo $itemOptionCount; ?> option(s)</span>
                                        <span><?php echo $itemVisibilityLabel; ?></span>
                                    </div>
                                    <?php if ($itemDescriptionPreview !== ''): ?>
                                    <p class="adminCatalogItem__summaryText"><?php echo $e($itemDescriptionPreview); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="adminCatalogItem__drag" aria-hidden="true">↕</span>
                            </summary>

                            <div class="adminCatalogItem__body">
                                <form action="/admin/catalog/items/<?php echo (int) $item['id']; ?>" method="post"
                                    enctype="multipart/form-data" class="adminForm">
                                    <div class="adminCatalogEditorGrid">
                                        <section class="adminEditorBlock adminEditorBlock--nested">
                                            <div class="adminCatalogSubsection">Informations du produit</div>
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
                                                    <span class="adminField__label">Prix affiché</span>
                                                    <input class="adminInput" type="text" name="price_from_label"
                                                        value="<?php echo $e($item['price_from_label'] ?? ''); ?>">
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Ordre</span>
                                                    <input class="adminInput" type="number" name="sort_order"
                                                        value="<?php echo (int) ($item['sort_order'] ?? 0); ?>">
                                                </label>
                                                <label class="adminField adminField--checkbox">
                                                    <span class="adminField__label">Visible</span>
                                                    <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                                        <?php echo ! empty($item['is_active']) ? 'checked' : ''; ?>>
                                                </label>
                                            </div>
                                        </section>

                                        <section class="adminEditorBlock adminEditorBlock--nested">
                                            <div class="adminCatalogSubsection">Image du produit</div>
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
                                                <span class="adminHint">Si un fichier est envoyé, il remplace l'image actuelle sur le site.</span>
                                            </label>
                                            <div class="adminFieldGrid adminFieldGrid--two">
                                                <label class="adminField adminField--checkbox">
                                                    <span class="adminField__label">Supprimer le fond automatiquement</span>
                                                    <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1"
                                                        checked>
                                                </label>
                                                <label class="adminField adminField--sm">
                                                    <span class="adminField__label">Tolérance fond (%)</span>
                                                    <input class="adminInput" type="number" name="background_fuzz" value="6"
                                                        min="0" max="40">
                                                </label>
                                            </div>
                                            <span class="adminHint">L'aperçu permet de vérifier le rendu avant d'enregistrer définitivement l'image.</span>
                                            <div class="adminInlineActions adminInlineActions--image">
                                                <button type="submit" class="adminBtn adminBtn--primary" data-image-save-button disabled>Enregistrer l'image</button>
                                            </div>

                                            <?php $imageVariants = $deriveImageVariants((string) ($item['image_path'] ?? '')); ?>
                                            <?php if ($imageVariants['desktop'] !== ''): ?>
                                            <div class="adminImagePreview">
                                                <div class="adminImagePreview__thumbWrap">
                                                    <img class="adminImagePreview__thumb"
                                                        src="<?php echo $e($imageVariants['desktop']); ?>"
                                                        alt="<?php echo $e($item['image_alt'] ?? ($item['name'] ?? 'Image item')); ?>"
                                                        loading="lazy">
                                                </div>
                                                <div class="adminImagePreview__meta">
                                                    <div><strong>Image principale :</strong> <?php echo $e($imageVariants['desktop']); ?>
                                                    </div>
                                                    <div><strong>Version mobile :</strong> <?php echo $e($imageVariants['mobile']); ?>
                                                    </div>
                                                    <?php if ($imageVariants['source'] !== ''): ?>
                                                    <div><strong>Fichier source :</strong>
                                                        <?php echo $e($imageVariants['source']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </section>

                                        <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                            <div class="adminCatalogSubsection">Texte public</div>
                                            <label class="adminField">
                                                <span class="adminField__label">Description</span>
                                                <textarea class="adminTextarea" name="short_description"
                                                    rows="4"><?php echo $e($item['short_description'] ?? ''); ?></textarea>
                                            </label>
                                        </section>
                                    </div>

                                    <input type="hidden" name="section_id" value="<?php echo (int) $section['id']; ?>">

                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn adminBtn--primary">Enregistrer
                                            le produit</button>
                                        <button type="submit" class="adminBtn adminBtn--danger"
                                            formaction="/admin/catalog/items/<?php echo (int) $item['id']; ?>/delete"
                                            formmethod="post"
                                            onclick="return confirm('Supprimer ce produit et ses options ?');">Supprimer
                                            le produit</button>
                                    </div>
                                </form>

                                <form action="/admin/catalog/items/<?php echo (int) $item['id']; ?>/options/create"
                                    method="post" class="adminForm adminForm--create">
                                    <div class="adminCatalogOptions__title">Ajouter une option</div>
                                    <div class="adminFieldGrid">
                                        <label class="adminField">
                                            <span class="adminField__label">Libellé</span>
                                            <input class="adminInput" type="text" name="label" required>
                                        </label>
                                        <label class="adminField">
                                            <span class="adminField__label">Clé (optionnel)</span>
                                            <input class="adminInput" type="text" name="option_key"
                                                placeholder="auto-générée si vide">
                                        </label>
                                        <label class="adminField">
                                            <span class="adminField__label">Prix affiché</span>
                                            <input class="adminInput" type="text" name="price_label">
                                        </label>
                                        <label class="adminField adminField--sm">
                                            <span class="adminField__label">Prix cents</span>
                                            <input class="adminInput" type="number" name="price_cents">
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
                                        <label class="adminField adminField--checkbox">
                                            <span class="adminField__label">Sur devis</span>
                                            <input class="adminCheckbox" type="checkbox" name="is_quote_only" value="1">
                                        </label>
                                    </div>
                                    <label class="adminField">
                                        <span class="adminField__label">Description</span>
                                        <input class="adminInput" type="text" name="description">
                                    </label>
                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn">Ajouter l'option</button>
                                    </div>
                                </form>

                                <?php if (! empty($item['options'])): ?>
                                <div class="adminCatalogOptions">
                                    <div class="adminCatalogOptions__title">Options</div>
                                    <?php foreach ($item['options'] as $option): ?>
                                    <form action="/admin/catalog/options/<?php echo (int) $option['id']; ?>"
                                        method="post" class="adminForm adminForm--option"
                                        id="option-<?php echo (int) $option['id']; ?>">
                                        <div class="adminFieldGrid">
                                            <label class="adminField">
                                                <span class="adminField__label">Libellé</span>
                                                <input class="adminInput" type="text" name="label"
                                                    value="<?php echo $e($option['label'] ?? ''); ?>" required>
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Clé</span>
                                                <input class="adminInput adminInput--readonly" type="text"
                                                    value="<?php echo $e($option['option_key'] ?? ''); ?>" readonly>
                                            </label>
                                            <label class="adminField">
                                                <span class="adminField__label">Prix affiché</span>
                                                <input class="adminInput" type="text" name="price_label"
                                                    value="<?php echo $e($option['price_label'] ?? ''); ?>">
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Prix cents</span>
                                                <input class="adminInput" type="number" name="price_cents"
                                                    value="<?php echo $e($option['price_cents'] ?? ''); ?>">
                                            </label>
                                            <label class="adminField adminField--sm">
                                                <span class="adminField__label">Ordre</span>
                                                <input class="adminInput" type="number" name="sort_order"
                                                    value="<?php echo (int) ($option['sort_order'] ?? 0); ?>">
                                            </label>
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Visible</span>
                                                <input class="adminCheckbox" type="checkbox" name="is_active" value="1"
                                                    <?php echo ! empty($option['is_active']) ? 'checked' : ''; ?>>
                                            </label>
                                            <label class="adminField adminField--checkbox">
                                                <span class="adminField__label">Sur devis</span>
                                                <input class="adminCheckbox" type="checkbox" name="is_quote_only"
                                                    value="1"
                                                    <?php echo ! empty($option['is_quote_only']) ? 'checked' : ''; ?>>
                                            </label>
                                        </div>

                                        <label class="adminField">
                                            <span class="adminField__label">Description</span>
                                            <input class="adminInput" type="text" name="description"
                                                value="<?php echo $e($option['description'] ?? ''); ?>">
                                        </label>

                                        <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">

                                        <div class="adminInlineActions">
                                            <button type="submit" class="adminBtn">Enregistrer l’option</button>
                                            <button type="submit" class="adminBtn adminBtn--danger"
                                                formaction="/admin/catalog/options/<?php echo (int) $option['id']; ?>/delete"
                                                formmethod="post"
                                                onclick="return confirm('Supprimer cette option ?');">Supprimer</button>
                                        </div>
                                    </form>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </main>
</div>