<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $sectionEditorial = [
    'cocktails-aperitifs' => [
        'eyebrow' => 'A partager et a celebrer',
        'lead'    => 'Des formats souples pour un aperitif qui reste genereux, fluide et facile a ajuster selon le nombre d\'invites.',
        'note'    => 'Bouches salees, equilibres de pieces et options vegetales: nous affinons ensuite le rythme de service avec vous.',
        'cta'     => 'Preparer mon devis aperitif',
    ],
    'plateaux-repas'      => [
        'eyebrow' => 'Pratique sans compromis',
        'lead'    => 'Une solution claire pour vos reunions, formations et journees d\'equipe, avec des plateaux prets a servir et simples a coordonner.',
        'note'    => 'Vous choisissez la composition, nous vous aidons a cadrer les quantites, les regimes specifiques et la logistique.',
        'cta'     => 'Composer mon devis plateaux',
    ],
    'aperitif-animation'  => [
        'eyebrow' => 'Le moment signature',
        'lead'    => 'Des ateliers et animations qui donnent du relief a la reception, avec une lecture immediate des volumes et des envies.',
        'note'    => 'Ideal pour installer une ambiance, ouvrir un evenement ou rythmer un cocktail avec une proposition plus scenarisee.',
        'cta'     => 'Imaginer mon devis animation',
    ],
    'buffets'             => [
        'eyebrow' => 'Convivial et evolutif',
        'lead'    => 'Des buffets penses pour laisser circuler vos invites, varier les plaisirs et tenir une belle ligne de service du debut a la fin.',
        'note'    => 'Nous pouvons calibrer la proposition selon le moment de la journee, le profil des convives et le niveau de service attendu.',
        'cta'     => 'Preparer mon devis buffet',
    ],
    'boissons'            => [
        'eyebrow' => 'Les bons equilibres',
        'lead'    => 'Softs, vins, bulles ou options chaudes: la carte boissons vient completer votre selection sans surcharger l\'organisation.',
        'note'    => 'Ajoutez simplement ce qu\'il faut pour accompagner le menu choisi, nous vous aidons ensuite a ajuster les volumes.',
        'cta'     => 'Ajouter les boissons a mon devis',
    ],
    ];

    $menuItemCardPartialFilePath = dirname(__DIR__) . '/partials/menu-item-card.php';
    $menuSectionExtraPartialPath = dirname(__DIR__) . '/partials/menu-sections';
?>

<main class="siteMain siteContainer">
    <section class="menuSplit" data-wheel-redirect data-wheel-target=".menuPanel--menu">

        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la carte">
            <div class="menuHero">
                <img class="menuHero__img" src="/uploads/pages/menu/images/menu3Illu.webp" alt="" aria-hidden="true">
                <div>
                    <h1 class="menuHero__title">Menu</h1>
                </div>
            </div>
        </div>

        <!-- RIGHT : panel carte -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--menu">

                <!-- Tabs de navigation générés depuis les sections BDD -->
                <nav class="menuTabs" aria-label="Catégories" data-menu-tabs>
                    <?php foreach ($sections as $index => $section): ?>
                    <a href="#<?php echo $e($section['slug']); ?>"
                        class="menuTabs__tab <?php echo $index === 0 ? 'is-active' : ''; ?>"
                        <?php echo $index === 0 ? 'aria-current="location"' : ''; ?>><?php echo $e($section['name']); ?></a>
                    <?php endforeach; ?>
                </nav>

                <button type="button" class="menuTabsShortcut" data-menu-tabs-shortcut>
                    Catégories
                </button>

                <!-- Sections de la carte — boucle sur les sections BDD -->
                <?php foreach ($sections as $section): ?>
                <?php $editorial = $sectionEditorial[$section['slug']] ?? null; ?>
                <section class="menuSection" id="<?php echo $e($section['slug']); ?>">

                    <header class="menuSectionTitle">
                        <span class="menuSectionTitle__line" aria-hidden="true"></span>
                        <?php if ($editorial !== null): ?>
                        <span class="menuSectionTitle__small"><?php echo $e($editorial['eyebrow']); ?></span>
                        <?php endif; ?>
                        <h2 class="menuSectionTitle__text"><?php echo $e($section['name']); ?></h2>
                        <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    </header>

                    <?php if ($editorial !== null): ?>
                    <p class="menuSection__lead"><?php echo $e($editorial['lead']); ?></p>
                    <?php endif; ?>

                    <?php if (($editorial['note'] ?? '') !== '' || $section['description'] !== ''): ?>
                    <p class="menuSection__note"><?php echo $e($editorial['note'] ?? $section['description']); ?></p>
                    <?php endif; ?>

                    <!-- Items de la section via le partial carte -->
                    <div class="menuList">
                        <?php if (empty($section['items'])): ?>
                        <p class="menuSection__note">Cette section sera bientôt disponible.</p>
                        <?php else: ?>
                        <?php foreach ($section['items'] as $menuItem): ?>
                        <?php require $menuItemCardPartialFilePath; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php
                        // Chargement du partial spécifique à la section si présent
                        // (widgets de commande, notes, CTAs propres à chaque section)
                        $sectionExtraPartialPath = $menuSectionExtraPartialPath . '/' . $section['slug'] . '.php';
                        if (is_file($sectionExtraPartialPath)) {
                            require $sectionExtraPartialPath;
                        }
                    ?>

                    <?php if (! empty($section['items'])): ?>
                    <div class="menuSection__ctaRow">
                        <a class="menuSection__cta"
                            href="/devis?category=<?php echo $e($section['slug']); ?>#quoteForm">
                            <?php echo $e($editorial['cta'] ?? ('Composer ma commande de ' . $section['name'])); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                </section>
                <?php endforeach; ?>

                <footer class="menuFooter">
                    <p>© <?php echo date('Y'); ?> Traiteur Passion</p>
                </footer>

            </div>
        </div>

    </section>
</main>