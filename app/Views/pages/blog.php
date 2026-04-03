<?php
    /** @var array<int, array<string, mixed>> $posts */
    $posts = $posts ?? [];
?>

<main class="siteMain siteContainer">
    <section class="blogSplit">
        <aside class="blogSplit__media" aria-hidden="true">
            <img class="blogSplit__mediaImg" src="/uploads/pages/blog/images/blogIllu.png" alt="" loading="lazy" />
            <div class="blogSplit__mediaOverlay"></div>

            <div class="blogSplit__titles">
                <h1 class="blogSplit__title">Blog</h1>
                <p class="blogSplit__subtitle">Recettes, inspirations et coulisses Traiteur Passion</p>
            </div>
        </aside>

        <div class="blogSplit__panel">
            <header class="blogPanelHead">
                <div class="blogPanelHead__left">
                    <h2 class="blogPanelHead__title">Derniers articles</h2>
                    <p class="blogPanelHead__hint">Une sélection d'idées gourmandes au style Qitchen.</p>
                </div>

                <div class="blogPanelHead__actions">
                    <a href="/menu" class="btn btn--ghost">Voir la carte</a>
                    <a href="/contact" class="btn btn--primary">Demander un devis</a>
                </div>
            </header>

            <section class="blogGrid blogGrid--panel">
                <?php foreach ($posts as $post): ?>
                <article class="blogCard blogCard--row">
                    <div class="blogCard__media">
                        <video class="blogCard__video" controls preload="metadata"
                            poster="<?php echo htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8'); ?>">
                            <source
                                src="<?php echo htmlspecialchars((string) $post['video_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                type="video/mp4">
                            Votre navigateur ne supporte pas la lecture vidéo HTML5.
                        </video>
                        <span class="blogCard__badge">Vidéo</span>
                    </div>

                    <div class="blogCard__content">
                        <h3 class="blogCard__title">
                            <?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <time class="blogCard__date"
                            datetime="<?php echo htmlspecialchars((string) $post['date_iso'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $post['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </time>

                        <p class="blogCard__excerpt">
                            <?php echo htmlspecialchars((string) $post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>

                        <div class="blogCard__meta">
                            <?php foreach (($post['categories'] ?? []) as $category): ?>
                            <span
                                class="blogCard__category"><?php echo htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <a href="/blog/<?php echo rawurlencode((string) $post['slug']); ?>"
                            class="btn btn--primary btn--small">Lire l'article</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
        </div>
    </section>
</main>