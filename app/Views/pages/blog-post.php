<?php
    /** @var array<string, mixed> $post */
?>

<main class="siteMain siteContainer">
    <section class="blogSplit blogSplit--article">
        <?php $hasVideo = ! empty($post['video_url']); ?>
        <aside class="blogSplit__media blogSplit__media--article">
            <img class="blogSplit__mediaImg"
                src="/uploads/pages/blog/images/blogIllu.png"
                alt=""
                loading="eager" />
            <div class="blogSplit__mediaOverlay"></div>

            <div class="blogSplit__titles blogSplit__titles--article">
                <p class="blogArticleContent__breadcrumb blogArticleContent__breadcrumb--overlay">
                    <a href="/blog">Blog</a>
                    <span aria-hidden="true">/</span>
                    <span><?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                </p>

                <div class="blogCard__meta blogCard__meta--overlay">
                    <?php foreach (($post['categories'] ?? []) as $category): ?>
                    <span class="blogCard__category"><?php echo htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>

                <h1 class="blogSplit__title">Blog</h1>
                <p class="blogSplit__subtitle">Recettes, inspirations et coulisses Traiteur Passion</p>
            </div>
        </aside>

        <div class="blogSplit__panel blogSplit__panel--article">
            <header class="blogPanelHead blogPanelHead--article">
                <div class="blogPanelHead__left">
                    <p class="blogArticleEyebrow">Lecture Traiteur Passion</p>
                    <h2 class="blogPanelHead__title blogPanelHead__title--article">Article</h2>
                    <p class="blogPanelHead__hint blogPanelHead__hint--article">
                        <time datetime="<?php echo htmlspecialchars((string) $post['date_iso'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $post['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </time>
                        • Par <?php echo htmlspecialchars((string) $post['author'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div class="blogPanelHead__actions">
                    <a href="/blog" class="btn btn--ghost">Retour au blog</a>
                    <a href="/contact" class="btn btn--primary">Demander un devis</a>
                </div>
            </header>

            <article class="blogArticleContent blogArticleContent--split">
                <header class="blogArticleContent__header blogArticleContent__header--split">
                    <p class="blogArticleContent__breadcrumb">
                        <a href="/blog">Blog</a>
                        <span aria-hidden="true">/</span>
                        <span><?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>

                    <h1 class="blogArticleContent__title"><?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

                    <p class="blogArticleContent__meta">
                        <time datetime="<?php echo htmlspecialchars((string) $post['date_iso'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $post['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </time>
                        • Par <?php echo htmlspecialchars((string) $post['author'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <div class="blogCard__meta blogCard__meta--article">
                        <?php foreach (($post['categories'] ?? []) as $category): ?>
                        <span class="blogCard__category"><?php echo htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </header>

                <div class="blogArticleContent__media blogArticleContent__media--split">
                    <?php if ($hasVideo): ?>
                    <video controls preload="metadata" poster="<?php echo htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8'); ?>">
                        <source src="<?php echo htmlspecialchars((string) $post['video_url'], ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la lecture vidéo HTML5.
                    </video>
                    <?php else: ?>
                    <img src="<?php echo htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        loading="eager">
                    <?php endif; ?>
                </div>

                <section class="blogArticleLead">
                    <p class="blogArticleLead__text"><?php echo htmlspecialchars((string) $post['intro'], ENT_QUOTES, 'UTF-8'); ?></p>
                </section>

                <div class="blogArticleContent__body blogArticleContent__body--split">
                    <?php foreach (($post['sections'] ?? []) as $index => $section): ?>
                        <section class="blogArticleSection">
                            <div class="blogArticleSection__kicker">Chapitre <?php echo $index + 1; ?></div>
                            <h2><?php echo htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars((string) ($section['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </section>
                    <?php endforeach; ?>
                </div>

                <section class="blogArticleCta">
                    <div>
                        <p class="blogArticleEyebrow">Projet en preparation</p>
                        <h2 class="blogArticleCta__title">Vous souhaitez une prestation dans cet esprit ?</h2>
                        <p class="blogArticleCta__text">Nous adaptons chaque proposition au format, au nombre de convives et au niveau de service attendu.</p>
                    </div>

                    <footer class="blogArticleContent__footer blogArticleContent__footer--cta">
                        <a href="/blog" class="btn btn--ghost">Retour au blog</a>
                        <a href="/contact" class="btn btn--primary">Demander un devis</a>
                    </footer>
                </section>
            </article>
        </div>
    </section>
</main>
