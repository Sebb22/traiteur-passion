<?php
    $sections = is_array($sections ?? null) ? $sections : [];
    $stats    = is_array($stats ?? null) ? $stats : ['sections' => 0, 'items' => 0, 'options' => 0];
    $flash    = is_array($flash ?? null) ? $flash : null;

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
            <h1 class="adminMediaTitle__h1">Catalogue</h1>
            <p class="adminMediaTitle__sub">Menu • édition & pilotage</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Catalogue du menu</h2>
                    <p class="adminSubtitle">MVP back-office : édition des sections, items et options déjà présents en
                        base.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/admin/contacts" class="adminBtn adminBtn--primary">Voir les demandes</a>
                    <a href="/menu" class="adminBtn">Voir le menu</a>
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

        <section class="adminStats adminStats--panel" aria-label="Statistiques catalogue">
            <div class="statCard">
                <div class="statCard__label">Sections</div>
                <div class="statCard__value"><?php echo (int) ($stats['sections'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Items</div>
                <div class="statCard__value"><?php echo (int) ($stats['items'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Options</div>
                <div class="statCard__value"><?php echo (int) ($stats['options'] ?? 0); ?></div>
            </div>
        </section>

        <section class="adminCard adminCatalogCreateSection">
            <div class="adminCard__head">
                <div class="adminCard__title">Créer une section</div>
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
            <?php foreach ($sections as $sectionIndex => $section): ?>
            <details class="adminCard adminCatalogSection" id="section-<?php echo (int) $section['id']; ?>"
                data-section-id="<?php echo (int) $section['id']; ?>" draggable="true"
                <?php echo $sectionIndex === 0 ? 'open' : ''; ?>>
                <summary class="adminCard__head adminCatalogSection__summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($section['name'] ?? 'Section'); ?></div>
                        <div class="adminCatalogMeta">
                            <span>Slug : <?php echo $e($section['slug'] ?? ''); ?></span>
                            <span><?php echo count($section['items'] ?? []); ?> item(s)</span>
                            <span><?php echo (int) ($section['count_options'] ?? 0); ?> option(s)</span>
                        </div>
                    </div>
                    <div class="adminCatalogSection__headActions" aria-hidden="true">
                        <span class="adminCatalogSection__chevron">▾</span>
                        <span class="adminDragHandle">↕</span>
                    </div>
                </summary>

                <div class="adminCatalogBody">
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

                    <form action="/admin/catalog/sections/<?php echo (int) $section['id']; ?>/items/create"
                        method="post" enctype="multipart/form-data" class="adminForm adminForm--create">
                        <div class="adminCatalogOptions__title">Créer un nouvel item</div>
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
                            <span class="adminHint">Le fichier est converti automatiquement en PNG source + WEBP 600 et
                                1200 (qualité 82).</span>
                        </label>
                        <div class="adminFieldGrid adminFieldGrid--two">
                            <label class="adminField adminField--checkbox">
                                <span class="adminField__label">Détourage fond auto</span>
                                <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1" checked>
                            </label>
                        </div>
                        <input type="hidden" name="background_fuzz" value="6">
                        <span class="adminHint">Aperçu rapide en basse résolution avec u2netp. L'enregistrement final utilisera le modèle normal u2net.</span>
                        <label class="adminField">
                            <span class="adminField__label">Description</span>
                            <textarea class="adminTextarea" name="short_description" rows="3"></textarea>
                        </label>
                        <div class="adminInlineActions">
                            <button type="submit" class="adminBtn">Créer l’item</button>
                        </div>
                    </form>

                    <form action="/admin/catalog/sections/<?php echo (int) $section['id']; ?>/items/reorder"
                        method="post" class="adminReorderForm"
                        id="catalogItemOrderForm-<?php echo (int) $section['id']; ?>">
                        <input type="hidden" name="item_ids"
                            id="catalogItemOrderInput-<?php echo (int) $section['id']; ?>" value="">
                    </form>

                    <div class="adminCatalogItems" data-item-sortable
                        data-section-id="<?php echo (int) $section['id']; ?>">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                        <details class="adminCatalogItem" id="item-<?php echo (int) $item['id']; ?>"
                            data-item-id="<?php echo (int) $item['id']; ?>" draggable="true">
                            <summary class="adminCatalogItem__summary">
                                <strong><?php echo $e($item['name'] ?? 'Item'); ?></strong>
                                <span><?php echo $e($item['slug'] ?? ''); ?></span>
                                <span><?php echo count($item['options'] ?? []); ?> option(s)</span>
                                <span class="adminCatalogItem__drag" aria-hidden="true">↕</span>
                            </summary>

                            <div class="adminCatalogItem__body">
                                <form action="/admin/catalog/items/<?php echo (int) $item['id']; ?>" method="post"
                                    enctype="multipart/form-data" class="adminForm">
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
                                        <span class="adminHint">Si un fichier est envoyé, il remplace le chemin image
                                            par des variantes WEBP (600/1200) et conserve une source PNG.</span>
                                    </label>
                                    <div class="adminFieldGrid adminFieldGrid--two">
                                        <label class="adminField adminField--checkbox">
                                            <span class="adminField__label">Détourage fond auto</span>
                                            <input class="adminCheckbox" type="checkbox" name="remove_bg" value="1"
                                                checked>
                                        </label>
                                        <label class="adminField adminField--sm">
                                            <span class="adminField__label">Tolérance fond (%)</span>
                                            <input class="adminInput" type="number" name="background_fuzz" value="6"
                                                min="0" max="40">
                                        </label>
                                    </div>
                                    <span class="adminHint">Aperçu: cliquez le bouton pour générer un aperçu rapide en basse résolution (u2netp). À l'enregistrement, le modèle normal u2net sera appliqué.</span>

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
                                            <div><strong>Desktop :</strong> <?php echo $e($imageVariants['desktop']); ?>
                                            </div>
                                            <div><strong>Mobile :</strong> <?php echo $e($imageVariants['mobile']); ?>
                                            </div>
                                            <?php if ($imageVariants['source'] !== ''): ?>
                                            <div><strong>Source PNG :</strong>
                                                <?php echo $e($imageVariants['source']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <label class="adminField">
                                        <span class="adminField__label">Description</span>
                                        <textarea class="adminTextarea" name="short_description"
                                            rows="4"><?php echo $e($item['short_description'] ?? ''); ?></textarea>
                                    </label>

                                    <input type="hidden" name="section_id" value="<?php echo (int) $section['id']; ?>">

                                    <div class="adminInlineActions">
                                        <button type="submit" class="adminBtn adminBtn--primary">Enregistrer
                                            l’item</button>
                                        <button type="submit" class="adminBtn adminBtn--danger"
                                            formaction="/admin/catalog/items/<?php echo (int) $item['id']; ?>/delete"
                                            formmethod="post"
                                            onclick="return confirm('Supprimer cet item et ses options ?');">Supprimer
                                            l’item</button>
                                    </div>
                                </form>

                                <form action="/admin/catalog/items/<?php echo (int) $item['id']; ?>/options/create"
                                    method="post" class="adminForm adminForm--create">
                                    <div class="adminCatalogOptions__title">Créer une option</div>
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
                                        <button type="submit" class="adminBtn">Créer l’option</button>
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