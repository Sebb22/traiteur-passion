<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $sectionEditorial = [
    'cocktails-aperitifs' => [
        'eyebrow'    => 'A partager et a celebrer',
        'lead'       => 'Des formats souples pour un aperitif qui reste genereux, fluide et facile a ajuster selon le nombre d\'invites.',
        'note'       => 'Bouches salees, equilibres de pieces et options vegetales: nous affinons ensuite le rythme de service avec vous.',
        'searchHint' => 'Cocktail dinatoire mariage, aperitif traiteur et vin d\'honneur a Compiegne.',
        'cta'        => 'Preparer mon devis aperitif',
    ],
    'plateaux-repas'      => [
        'eyebrow'    => 'Pratique sans compromis',
        'lead'       => 'Une solution claire pour vos reunions, formations et journees d\'equipe, avec des plateaux prets a servir et simples a coordonner.',
        'note'       => 'Vous choisissez la composition, nous vous aidons a cadrer les quantites, les regimes specifiques et la logistique.',
        'searchHint' => 'Plateaux repas entreprise a Compiegne, reunions, formations et dejeuners professionnels.',
        'cta'        => 'Composer mon devis plateaux',
    ],
    'aperitif-animation'  => [
        'eyebrow'    => 'Le moment signature',
        'lead'       => 'Des ateliers et animations qui donnent du relief a la reception, avec une lecture immediate des volumes et des envies.',
        'note'       => 'Ideal pour installer une ambiance, ouvrir un evenement ou rythmer un cocktail avec une proposition plus scenarisee.',
        'searchHint' => 'Animation culinaire et atelier aperitif pour mariage, evenement prive ou reception d\'entreprise.',
        'cta'        => 'Imaginer mon devis animation',
    ],
    'buffets'             => [
        'eyebrow'    => 'Convivial et evolutif',
        'lead'       => 'Des buffets penses pour laisser circuler vos invites, varier les plaisirs et tenir une belle ligne de service du debut a la fin.',
        'note'       => 'Nous pouvons calibrer la proposition selon le moment de la journee, le profil des convives et le niveau de service attendu.',
        'searchHint' => 'Buffet froid ou buffet traiteur pour anniversaire, mariage et reception a Compiegne.',
        'cta'        => 'Preparer mon devis buffet',
    ],
    'boissons'            => [
        'eyebrow'    => 'Les bons equilibres',
        'lead'       => 'Softs, vins, bulles ou options chaudes: la carte boissons vient completer votre selection sans surcharger l\'organisation.',
        'note'       => 'Ajoutez simplement ce qu\'il faut pour accompagner la selection choisie, nous vous aidons ensuite a ajuster les volumes.',
        'searchHint' => 'Boissons pour cocktail, buffet et reception avec ajustement des volumes selon vos invites.',
        'cta'        => 'Ajouter les boissons a mon devis',
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
                <img class="menuHero__img" src="/uploads/pages/menu/images/menuIllu2-1200.webp" alt=""
                    aria-hidden="true">
                <div>
                    <h1 class="menuHero__title">Carte évènementielle</h1>
                </div>
            </div>
        </div>

        <!-- RIGHT : panel carte -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--menu">

                <header class="menuIntro">
                    <span class="menuIntro__eyebrow">Compiègne • Oise • prestations sur mesure</span>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="menuIntro__title">Traiteur évènementiel à Compiègne pour mariages, cocktails et
                        réceptions</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="menuIntro__copy">La carte évènementielle rassemble nos formats pour mariage, anniversaire,
                        cocktail, repas d'entreprise et réception privée. Vous repérez ici les bases de votre
                        prestation, puis nous ajustons avec vous les quantités, le service, la livraison et les
                        contraintes alimentaires.</p>
                    <div class="menuIntro__story" aria-label="Signature Traiteur Passion">
                        <article class="menuIntroCard">
                            <span class="menuIntroCard__kicker">Cuisine</span>
                            <strong class="menuIntroCard__title">Une lecture plus gourmande que standardisée</strong>
                            <p class="menuIntroCard__copy">Pièces cocktail, buffets, plateaux et boissons sont pensés
                                pour garder de la fraîcheur, du rythme et une vraie cohérence de table.</p>
                        </article>
                        <article class="menuIntroCard">
                            <span class="menuIntroCard__kicker">Méthode</span>
                            <strong class="menuIntroCard__title">Une base claire, puis un devis affiné avec
                                vous</strong>
                            <p class="menuIntroCard__copy">Cette page sert à cadrer les formats, les volumes et l'esprit
                                de réception avant d'ajuster le service, la logistique et les contraintes alimentaires.
                            </p>
                        </article>
                        <article class="menuIntroCard">
                            <span class="menuIntroCard__kicker">Territoire</span>
                            <strong class="menuIntroCard__title">Compiègne, Oise et réceptions sur mesure</strong>
                            <p class="menuIntroCard__copy">Mariage, anniversaire, cocktail d'entreprise ou déjeuner
                                d'équipe: la carte s'adapte au contexte réel de votre évènement.</p>
                        </article>
                    </div>
                    <div class="menuIntro__meta" aria-label="Points forts de la carte évènementielle">
                        <span class="menuIntro__pill">Mariages et réceptions</span>
                        <span class="menuIntro__pill">Buffets, cocktails, brunchs</span>
                        <span class="menuIntro__pill">Compiègne et Oise</span>
                        <span class="menuIntro__pill">Devis sur mesure</span>
                    </div>
                </header>

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
                <?php foreach ($sections as $sectionIndex => $section): ?>
                <?php $editorial         = $sectionEditorial[$section['slug']] ?? null; ?>
                <?php $sectionSearchHint = trim((string) ($section['description'] !== '' ? $section['description'] : ($editorial['searchHint'] ?? ''))); ?>
                <section class="menuSection menuSection--tone-<?php echo($sectionIndex % 3) + 1; ?>"
                    id="<?php echo $e($section['slug']); ?>">

                    <header class="menuSectionTitle">
                        <span class="menuSectionTitle__line" aria-hidden="true"></span>
                        <?php if ($editorial !== null): ?>
                        <span class="menuSectionTitle__small"><?php echo $e($editorial['eyebrow']); ?></span>
                        <?php endif; ?>
                        <h2 class="menuSectionTitle__text"><?php echo $e($section['name']); ?></h2>
                        <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    </header>

                    <?php if ($editorial !== null || $sectionSearchHint !== '' || ($editorial['note'] ?? '') !== ''): ?>
                    <div class="menuSection__intro">
                        <?php if ($editorial !== null): ?>
                        <p class="menuSection__lead"><?php echo $e($editorial['lead']); ?></p>
                        <?php endif; ?>

                        <?php if ($sectionSearchHint !== ''): ?>
                        <p class="menuSection__searchHint"><?php echo $e($sectionSearchHint); ?></p>
                        <?php endif; ?>

                        <?php if (($editorial['note'] ?? '') !== ''): ?>
                        <p class="menuSection__note"><?php echo $e($editorial['note']); ?></p>
                        <?php endif; ?>
                    </div>
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

                <?php require dirname(__DIR__) . '/partials/menu-footer.php'; ?>

            </div>
        </div>

    </section>
</main>