<section class="home-grid home-section container" aria-label="Page d'accueil">
    <!-- Colonne gauche : hero -->
    <div class="home-grid__main" data-reveal="fade">
        <div class="home-hero__wrap">
            <section class="home-hero">
                <video class="home-hero__video" autoplay muted loop playsinline preload="metadata"
                    poster="/uploads/images/home/hero-poster.jpg" aria-hidden="true">
                    <source src="/uploads/videos/videoHome1.mp4" type="video/mp4">
                </video>

                <div class="home-hero__logo" aria-hidden="true">
                    <img src="/uploads/images/logos/logo.png" alt="">
                </div>

                <div class="home-hero__content">
                    <span class="home-hero__kicker">Traiteur à Compiègne</span>

                    <h1 class="home-hero__title">
                        <span class="home-hero__titleLine">Traiteur Passion</span>
                        <span class="home-hero__titleLine">Traiteur événementiel à Compiègne</span>
                    </h1>

                    <p>Cuisine de saison &amp; événements sur mesure</p>

                    <div class="home-hero__actions">
                        <a class="btn btn--primary" href="/devis">Demander un devis</a>
                        <a class="btn btn--ghost" href="/menu">Voir la carte</a>
                    </div>
                </div>
                <!-- SLOT HERO  -->
                <div class="corner-slot corner-slot--hero" aria-label="Réseaux sociaux">
                    <a class="corner-slot__btn" href="#" aria-label="Facebook">f</a>
                    <a class="corner-slot__btn" href="#" aria-label="Instagram">⌁</a>
                    <a class="corner-slot__btn" href="#" aria-label="X">x</a>
                </div>

            </section>

        </div>
    </div>

    <!-- Colonne droite : tiles -->
    <aside class="home-grid__side" aria-label="Accès rapides">
        <a class="home-tile motion-card" href="/menu" data-reveal="up" data-stagger>
            <div class="home-tile__media" style="--tile-bg:url('/uploads/images/home/carteDuMomentIllu.jpg');">
                <!-- SLOT TILE (inside) -->
                <span class="corner-slot corner-slot--tile" aria-hidden="true">
                    <span class="corner-slot__label">Carte du moment</span>
                    <span class="corner-slot__plus">+</span>
                </span>
            </div>
        </a>

        <a class="home-tile motion-card" href="/menu" data-reveal="up" data-stagger>
            <div class="home-tile__media" style="--tile-bg:url('/uploads/images/home/plateauxRepasIllu.jpg');">
                <span class="corner-slot corner-slot--tile" aria-hidden="true">
                    <span class="corner-slot__label">Contact</span>
                    <span class="corner-slot__plus">+</span>
                </span>
            </div>
        </a>

        <a class="home-tile motion-card" href="/menu" data-reveal="fade" data-stagger>
            <div class="home-tile__media" style="--tile-bg:url('/uploads/images/home/nousDécouvrirIllu.webp');">
                <span class="corner-slot corner-slot--tile" aria-hidden="true">
                    <span class="corner-slot__label">Nous découvrir</span>
                    <span class="corner-slot__plus">+</span>
                </span>
            </div>
        </a>
    </aside>
</section>