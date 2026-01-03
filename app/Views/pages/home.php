<section class="home-grid">
    <!-- Colonne gauche : gros hero vidéo -->
    <div class="home-grid__main" data-reveal="up">
        <section class="home-hero">
            <video class="home-hero__video" autoplay muted loop playsinline preload="metadata"
                poster="/uploads/images/home/hero-poster.jpg" aria-hidden="true">
                <source src="/uploads/videos/videoHome1.mp4" type="video/mp4">
            </video>



            <div class="home-hero__content">
                <h1>Bienvenue 👨‍🍳</h1>
                <p>Traiteur Passion — Cuisine de saison &amp; événements sur mesure</p>

                <div class="home-hero__actions">
                    <a class="btn btn--primary" href="/devis">Demander un devis</a>
                    <a class="btn btn--ghost" href="/menu">Voir la carte</a>
                </div>
            </div>
        </section>
    </div>

    <!-- Colonne droite : 3 cartes empilées -->
    <aside class="home-grid__side">
        <a class="home-tile motion-card" href="/menu" data-reveal="up" data-stagger
            style="--tile-bg: url('/uploads/images/home/tile-carte.jpg');">
            <span class="home-tile__label">CARTE DU MOMENT</span>
            <span class="home-tile__icon" aria-hidden="true">+</span>
        </a>

        <a class="home-tile motion-card" href="/devis" data-reveal="up" data-stagger
            style="--tile-bg: url('/uploads/images/home/tile-plateaux.jpg');">
            <span class="home-tile__label">PLATEAUX REPAS</span>
            <span class="home-tile__icon" aria-hidden="true">+</span>
        </a>

        <a class="home-tile motion-card" href="/contact" data-reveal="up" data-stagger
            style="--tile-bg: url('/uploads/images/home/tile-decouvrir.jpg');">
            <span class="home-tile__label">NOUS DÉCOUVRIR</span>
            <span class="home-tile__icon" aria-hidden="true">+</span>
        </a>
    </aside>

</section>