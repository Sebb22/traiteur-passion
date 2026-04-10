<?php
    $posts = is_array($posts ?? null) ? $posts : [];
    $stats = is_array($stats ?? null) ? $stats : ['total' => 0, 'published' => 0, 'drafts' => 0, 'videos' => 0];
    $flash = is_array($flash ?? null) ? $flash : null;

    $e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $dateTimeLabel = static function ($value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }

    $timestamp = strtotime($raw);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
    };

    $renderSectionFields = static function (array $post = []) use ($e): void {
    $sections = is_array($post['sections'] ?? null) ? $post['sections'] : [];
    $slots    = max(4, count($sections) + 1);

    for ($index = 0; $index < $slots; $index++):
        $section = is_array($sections[$index] ?? null) ? $sections[$index] : [];
    ?>
<section class="adminEditorBlock adminEditorBlock--nested">
    <div class="adminCatalogSubsection">Section <?php echo $index + 1; ?></div>
    <label class="adminField">
        <span class="adminField__label">Titre de section</span>
        <input class="adminInput" type="text" name="section_titles[]"
            value="<?php echo $e($section['title'] ?? ''); ?>">
    </label>
    <label class="adminField">
        <span class="adminField__label">Texte</span>
        <textarea class="adminTextarea" name="section_texts[]" rows="4"><?php echo $e($section['text'] ?? ''); ?></textarea>
    </label>
	</section>
<?php
    endfor;
    };
?>
<div class="adminSplit adminSplit--catalog">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Le blog</h1>
            <p class="adminMediaTitle__sub">Admin • contenus & publication</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Gerer le blog</h2>
                    <p class="adminSubtitle">Creer, corriger, publier ou retirer des articles sans toucher au code.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin" class="adminBtn">Dashboard</a>
                    <a href="/admin/contacts" class="adminBtn">Demandes</a>
                    <a href="/admin/catalog" class="adminBtn">Carte</a>
                    <a href="/blog" class="adminBtn adminBtn--primary">Voir le blog</a>
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

        <section class="adminStats adminStats--panel" aria-label="Statistiques blog">
            <div class="statCard">
                <div class="statCard__label">Articles</div>
                <div class="statCard__value"><?php echo (int) ($stats['total'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Publies</div>
                <div class="statCard__value"><?php echo (int) ($stats['published'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Brouillons</div>
                <div class="statCard__value"><?php echo (int) ($stats['drafts'] ?? 0); ?></div>
            </div>
            <div class="statCard">
                <div class="statCard__label">Avec video</div>
                <div class="statCard__value"><?php echo (int) ($stats['videos'] ?? 0); ?></div>
            </div>
        </section>

        <section class="adminCatalogUtilityGrid" aria-label="Repères blog">
            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Retrouver rapidement un article</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Filtrez par titre, slug, categorie ou statut sans recharger la page</span>
                    </div>
                </div>

                <div class="adminCatalogToolbar">
                    <label class="adminField adminField--filter">
                        <span class="adminField__label">Recherche</span>
                        <input class="adminInput" type="search" placeholder="Ex: brunch, entreprise, brouillon..."
                            data-blog-search>
                    </label>

                    <div class="adminInlineActions adminInlineActions--filters">
                        <button type="button" class="adminBtn" data-blog-expand-all>Tout ouvrir</button>
                        <button type="button" class="adminBtn" data-blog-collapse-all>Tout fermer</button>
                    </div>
                </div>
            </article>

            <article class="adminCard adminCard--padded adminCatalogUtilityCard">
                <div class="adminCard__head">
                    <div class="adminCard__title">Ligne editoriale</div>
                    <div class="adminCard__meta">
                        <span class="adminHint">Un article utile, concret et oriente prestation convertit mieux qu'une simple actualite.</span>
                    </div>
                </div>

                <div class="adminCatalogGuideGrid">
                    <div class="adminQuickLinkCard">
                        <div class="adminQuickLinkCard__eyebrow">1. Angle</div>
                        <div class="adminQuickLinkCard__title">Un sujet clair</div>
                        <div class="adminQuickLinkCard__meta">Mariage, entreprise, brunch, animation culinaire: un article par intention.</div>
                    </div>
                    <div class="adminQuickLinkCard">
                        <div class="adminQuickLinkCard__eyebrow">2. Lecture</div>
                        <div class="adminQuickLinkCard__title">Intro puis 3 blocs</div>
                        <div class="adminQuickLinkCard__meta">La structure courte actuelle du site fonctionne bien pour une lecture rapide.</div>
                    </div>
                    <div class="adminQuickLinkCard">
                        <div class="adminQuickLinkCard__eyebrow">3. Publication</div>
                        <div class="adminQuickLinkCard__title">Masquer sans perdre</div>
                        <div class="adminQuickLinkCard__meta">Decochez “Publie” pour retirer un article du site sans le supprimer.</div>
                    </div>
                </div>
            </article>
        </section>

        <section class="adminCard adminCatalogCreateSection">
            <div class="adminCard__head">
                <div class="adminCard__title">Ajouter un article</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Le slug est genere automatiquement si vous le laissez vide.</span>
                </div>
            </div>

            <div class="adminCatalogBody">
                <form action="/admin/blog/create" method="post" enctype="multipart/form-data" class="adminForm adminForm--create">
                    <div class="adminCatalogSectionGrid">
                        <section class="adminEditorBlock adminEditorBlock--nested">
                            <div class="adminCatalogSubsection">Identite</div>
                            <div class="adminFieldGrid">
                                <label class="adminField">
                                    <span class="adminField__label">Titre</span>
                                    <input class="adminInput" type="text" name="title" required>
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">Slug</span>
                                    <input class="adminInput" type="text" name="slug" placeholder="auto-genere si vide">
                                </label>
                                <label class="adminField adminField--sm">
                                    <span class="adminField__label">Date</span>
                                    <input class="adminInput" type="date" name="date_iso" value="<?php echo date('Y-m-d'); ?>">
                                </label>
                                <label class="adminField adminField--checkbox">
                                    <span class="adminField__label">Publie</span>
                                    <input class="adminCheckbox" type="checkbox" name="is_published" value="1" checked>
                                </label>
                            </div>
                            <div class="adminFieldGrid adminFieldGrid--two">
                                <label class="adminField">
                                    <span class="adminField__label">Auteur</span>
                                    <input class="adminInput" type="text" name="author" value="Traiteur Passion">
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">Categories</span>
                                    <input class="adminInput" type="text" name="categories_text"
                                        placeholder="Ex: Mariage, Brunch, Organisation">
                                </label>
                            </div>
                        </section>

                        <section class="adminEditorBlock adminEditorBlock--nested">
                            <div class="adminCatalogSubsection">Media</div>
                            <label class="adminField">
                                <span class="adminField__label">Image de couverture</span>
                                <input class="adminInput" type="text" name="cover_image"
                                    placeholder="/uploads/pages/blog/images/blogIllu.jpg">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Upload image</span>
                                <input class="adminInput" type="file" name="cover_image_file"
                                    accept="image/png,image/jpeg,image/webp">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">URL video (optionnel)</span>
                                <input class="adminInput" type="text" name="video_url"
                                    placeholder="/uploads/pages/blog/videos/paella.mp4">
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Upload video</span>
                                <input class="adminInput" type="file" name="video_file"
                                    accept="video/mp4,video/webm,video/quicktime">
                            </label>
                            <span class="adminHint">L'image suffit. La video reste optionnelle et s'affiche automatiquement si renseignee.</span>
                        </section>

                        <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                            <div class="adminCatalogSubsection">Resume</div>
                            <label class="adminField">
                                <span class="adminField__label">Extrait</span>
                                <textarea class="adminTextarea" name="excerpt" rows="3"></textarea>
                            </label>
                            <label class="adminField">
                                <span class="adminField__label">Introduction</span>
                                <textarea class="adminTextarea" name="intro" rows="4"></textarea>
                            </label>
                        </section>

                        <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                            <div class="adminCatalogSubsection">Corps de l'article</div>
                            <div class="adminCatalogEditorGrid">
                                <?php $renderSectionFields(); ?>
                            </div>
                        </section>
                    </div>

                    <div class="adminInlineActions">
                        <button type="submit" class="adminBtn adminBtn--primary">Creer l'article</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="adminCatalogList">
            <?php if ($posts === []): ?>
            <section class="adminCard">
                <div class="adminEmptyState">Aucun article disponible pour le moment.</div>
            </section>
            <?php endif; ?>

            <?php foreach ($posts as $post): ?>
            <?php $statusClass = ! empty($post['is_published']) ? 'completed' : 'cancelled'; ?>
            <?php
                $searchText = strtolower(trim(implode(' ', array_filter([
                    (string) ($post['title'] ?? ''),
                    (string) ($post['slug'] ?? ''),
                    (string) ($post['author'] ?? ''),
                    (string) ($post['categories_csv'] ?? ''),
                    ! empty($post['is_published']) ? 'publie' : 'brouillon',
                    trim((string) ($post['video_url'] ?? '')) !== '' ? 'video' : 'image',
                ]))));
            ?>
            <details class="adminCard adminCatalogSection" id="post-<?php echo $e($post['slug'] ?? ''); ?>"
                data-blog-article data-blog-search-text="<?php echo $e($searchText); ?>">
                <summary class="adminCard__head adminCatalogSection__summary">
                    <div>
                        <div class="adminCard__title"><?php echo $e($post['title'] ?? 'Article'); ?></div>
                        <div class="adminCatalogMeta">
                            <span>Slug : <?php echo $e($post['slug'] ?? ''); ?></span>
                            <span><?php echo $e($post['date_label'] ?? ''); ?></span>
                            <span><?php echo $e($post['categories_csv'] ?? 'Sans categorie'); ?></span>
                            <span><?php echo trim((string) ($post['video_url'] ?? '')) !== '' ? 'Video' : 'Image'; ?></span>
                            <span>
                                <span class="adminBadge adminBadge--<?php echo $statusClass; ?>">
                                    <?php echo ! empty($post['is_published']) ? 'Publie' : 'Brouillon'; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="adminCatalogSection__headActions" aria-hidden="true">
                        <span class="adminCatalogSection__chevron">▾</span>
                    </div>
                </summary>

                <div class="adminCatalogBody">
                    <div class="adminCard adminCard--padded">
                        <div class="adminCard__head">
                            <div class="adminCard__title">Derniere mise a jour</div>
                            <div class="adminCard__meta">
                                <span class="adminHint">Modifie le <?php echo $dateTimeLabel($post['updated_at'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>

                    <form action="/admin/blog/<?php echo rawurlencode((string) ($post['slug'] ?? '')); ?>" method="post" enctype="multipart/form-data"
                        class="adminForm adminForm--section">
                        <div class="adminCatalogSectionGrid">
                            <section class="adminEditorBlock adminEditorBlock--nested">
                                <div class="adminCatalogSubsection">Identite</div>
                                <div class="adminFieldGrid">
                                    <label class="adminField">
                                        <span class="adminField__label">Titre</span>
                                        <input class="adminInput" type="text" name="title"
                                            value="<?php echo $e($post['title'] ?? ''); ?>" required>
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Slug</span>
                                        <input class="adminInput" type="text" name="slug"
                                            value="<?php echo $e($post['slug'] ?? ''); ?>">
                                    </label>
                                    <label class="adminField adminField--sm">
                                        <span class="adminField__label">Date</span>
                                        <input class="adminInput" type="date" name="date_iso"
                                            value="<?php echo $e($post['date_iso'] ?? ''); ?>">
                                    </label>
                                    <label class="adminField adminField--checkbox">
                                        <span class="adminField__label">Publie</span>
                                        <input class="adminCheckbox" type="checkbox" name="is_published" value="1"
                                            <?php echo ! empty($post['is_published']) ? 'checked' : ''; ?>>
                                    </label>
                                </div>
                                <div class="adminFieldGrid adminFieldGrid--two">
                                    <label class="adminField">
                                        <span class="adminField__label">Auteur</span>
                                        <input class="adminInput" type="text" name="author"
                                            value="<?php echo $e($post['author'] ?? ''); ?>">
                                    </label>
                                    <label class="adminField">
                                        <span class="adminField__label">Categories</span>
                                        <input class="adminInput" type="text" name="categories_text"
                                            value="<?php echo $e($post['categories_csv'] ?? ''); ?>">
                                    </label>
                                </div>
                            </section>

                            <section class="adminEditorBlock adminEditorBlock--nested">
                                <div class="adminCatalogSubsection">Media</div>
                                <label class="adminField">
                                    <span class="adminField__label">Image de couverture</span>
                                    <input class="adminInput" type="text" name="cover_image"
                                        value="<?php echo $e($post['cover_image'] ?? ''); ?>">
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">Remplacer par un upload image</span>
                                    <input class="adminInput" type="file" name="cover_image_file"
                                        accept="image/png,image/jpeg,image/webp">
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">URL video (optionnel)</span>
                                    <input class="adminInput" type="text" name="video_url"
                                        value="<?php echo $e($post['video_url'] ?? ''); ?>">
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">Remplacer par un upload video</span>
                                    <input class="adminInput" type="file" name="video_file"
                                        accept="video/mp4,video/webm,video/quicktime">
                                </label>
                            </section>

                            <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                <div class="adminCatalogSubsection">Resume</div>
                                <label class="adminField">
                                    <span class="adminField__label">Extrait</span>
                                    <textarea class="adminTextarea" name="excerpt" rows="3"><?php echo $e($post['excerpt'] ?? ''); ?></textarea>
                                </label>
                                <label class="adminField">
                                    <span class="adminField__label">Introduction</span>
                                    <textarea class="adminTextarea" name="intro" rows="4"><?php echo $e($post['intro'] ?? ''); ?></textarea>
                                </label>
                            </section>

                            <section class="adminEditorBlock adminEditorBlock--nested adminEditorBlock--full">
                                <div class="adminCatalogSubsection">Corps de l'article</div>
                                <div class="adminCatalogEditorGrid">
                                    <?php $renderSectionFields($post); ?>
                                </div>
                            </section>
                        </div>

                        <div class="adminInlineActions">
                            <button type="submit" class="adminBtn adminBtn--primary">Enregistrer l'article</button>
                            <a href="/blog/<?php echo rawurlencode((string) ($post['slug'] ?? '')); ?>" class="adminBtn">Voir</a>
                            <button type="submit" class="adminBtn adminBtn--danger"
                                formaction="/admin/blog/<?php echo rawurlencode((string) ($post['slug'] ?? '')); ?>/delete"
                                formmethod="post"
                                onclick="return confirm('Supprimer cet article ?');">Supprimer</button>
                        </div>
                    </form>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </main>
</div>