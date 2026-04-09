<?php
    $eyebrow         = $eyebrow ?? 'Page introuvable';
    $headline        = $headline ?? 'Cette page s\'est évaporée.';
    $message         = $message ?? 'L\'adresse demandée n\'existe pas, plus, ou n\'est pas accessible depuis ce lien. On vous renvoie vers quelque chose d\'utile.';
    $primaryAction   = $primaryAction ?? ['href' => '/', 'label' => 'Retour à l\'accueil'];
    $secondaryAction = $secondaryAction ?? ['href' => '/menu', 'label' => 'Voir le menu'];
    $hints           = $hints ?? [
    'Vérifiez l\'orthographe de l\'URL si vous l\'avez saisie à la main.',
    'Repartez du menu ou de l\'accueil pour retrouver la bonne page.',
    'Contactez-nous si vous cherchiez une prestation précise.',
    ];
?>
<main class="errorPage" aria-labelledby="error-page-title">
    <section class="errorHero errorHero--404">
        <div class="errorHero__halo errorHero__halo--one" aria-hidden="true"></div>
        <div class="errorHero__halo errorHero__halo--two" aria-hidden="true"></div>

        <div class="errorHero__inner">
            <div class="errorHero__content">
                <p class="errorHero__eyebrow">Erreur 404 · <?php echo htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 id="error-page-title" class="errorHero__title"><?php echo htmlspecialchars($headline, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="errorHero__message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="errorHero__actions">
                    <a class="btn btn--primary" href="<?php echo htmlspecialchars((string) $primaryAction['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) $primaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a class="btn btn--ghost" href="<?php echo htmlspecialchars((string) $secondaryAction['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) $secondaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            </div>

            <aside class="errorHero__panel" aria-label="Pistes de navigation">
                <span class="errorHero__code">404</span>
                <p class="errorHero__panelTitle">Repartir rapidement</p>
                <ul class="errorHero__list">
                    <?php foreach ($hints as $hint): ?>
                        <li><?php echo htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </section>
</main>