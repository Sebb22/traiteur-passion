<?php
$eyebrow = $eyebrow ?? 'Accès refusé';
$headline = $headline ?? 'Cette porte reste fermée.';
$message = $message ?? 'Vous avez bien trouvé la bonne zone, mais votre session ou vos droits actuels ne permettent pas d\'y entrer.';
$primaryAction = $primaryAction ?? ['href' => '/admin/login', 'label' => 'Connexion admin'];
$secondaryAction = $secondaryAction ?? ['href' => '/', 'label' => 'Retour à l\'accueil'];
$hints = $hints ?? [
    'Vérifiez que vous utilisez le bon compte administrateur.',
    'Reconnectez-vous si votre session a expiré.',
    'Revenez à l\'accueil si vous cherchiez une page publique.',
];
?>
<main class="errorPage" aria-labelledby="error-page-title">
    <section class="errorHero errorHero--403">
        <div class="errorHero__halo errorHero__halo--one" aria-hidden="true"></div>
        <div class="errorHero__halo errorHero__halo--two" aria-hidden="true"></div>

        <div class="errorHero__inner">
            <div class="errorHero__content">
                <p class="errorHero__eyebrow">Erreur 403 · <?php echo htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
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
                <span class="errorHero__code">403</span>
                <p class="errorHero__panelTitle">Avant de réessayer</p>
                <ul class="errorHero__list">
                    <?php foreach ($hints as $hint): ?>
                        <li><?php echo htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </section>
</main>