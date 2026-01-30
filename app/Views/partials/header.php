<header class="header">
    <div class="header__container">
        <!-- Toggle (mobile + style qitchen) -->
        <button class="header__toggle" type="button" aria-controls="menuOverlay" aria-expanded="false">
            <span class="sr-only">Ouvrir le menu</span>
            <span class="header__burger" aria-hidden="true"></span>
        </button>
        <a href="/" class="header__logo" aria-label="Traiteur Passion - Accueil">
            <img src="/uploads/images/logos/logoNav.png" alt="Traiteur Passion" width="auto" height="80px"
                loading="eager">
        </a>
        <nav class="header__nav header__nav--desktop" aria-label="Navigation principale">
            <a href="/menu" class="header__link">Menu</a>
            <a href="/a-propos" class="header__link">A propos</a>

        </nav>
        <a href="/contact" class="header__cta">Contact</a>
    </div>


</header> <!-- Overlay menu -->
<div class="menu" id="menuOverlay" hidden>
    <div class="menu__backdrop" data-close aria-hidden="true"></div>

    <div class="menu__panel" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="menu__top">
            <button class="menu__close" type="button" data-close>
                <span class="sr-only">Fermer le menu</span>
                <span class="menu__closeIcon" aria-hidden="true"></span>
            </button>
        </div>

        <div class="menu__content">

            <div class="menu__links" aria-label="Accès">
                <span class="menuDeco__line" aria-hidden="true"></span>
                <a class="menu__link" href="/menu">
                    <span class="menu__label">Carte du moment</span>
                    <span class="menu__desc">Menus de saison, pièces cocktail, buffets</span>
                </a>

                <a class="menu__link" href="/prestations">
                    <span class="menu__label">Prestations</span>
                    <span class="menu__desc">Mariages, entreprises, réceptions privées</span>
                </a>

                <a class="menu__link" href="/a-propos">
                    <span class="menu__label">Nous découvrir</span>
                    <span class="menu__desc">L’équipe, la méthode, nos engagements</span>
                </a>

                <a class="menu__link" href="/contact">
                    <span class="menu__label">Contact</span>
                    <span class="menu__desc">Questions, disponibilités, infos pratiques</span>
                </a>
                <span class="menuDeco__line" aria-hidden="true"></span>
            </div>


        </div>
    </div>
</div>