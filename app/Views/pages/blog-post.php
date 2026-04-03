<?php
    /** @var array<string, mixed> $post */
?>

<main class="siteMain siteContainer">
    <section class="blogArticle blogArticle--single">
        <div class="blogArticle__container">
            <article class="blogArticleContent">
                <header class="blogArticleContent__header">
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
                </header>

                <div class="blogArticleContent__video">
                    <video controls preload="metadata" poster="<?php echo htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8'); ?>">
                        <source src="<?php echo htmlspecialchars((string) $post['video_url'], ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la lecture vidéo HTML5.
                    </video>
                </div>

                <div class="blogArticleContent__body">
                    <p><?php echo htmlspecialchars((string) $post['intro'], ENT_QUOTES, 'UTF-8'); ?></p>

                    <?php foreach (($post['sections'] ?? []) as $section): ?>
                        <h2><?php echo htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars((string) ($section['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>

                <footer class="blogArticleContent__footer">
                    <a href="/blog" class="btn btn--ghost">Retour au blog</a>
                    <a href="/contact" class="btn btn--primary">Demander un devis</a>
                </footer>
            </article>
        </div>
    </section>
</main>
