<main class="siteMain siteContainer">
    <section class="home-grid home-section container" aria-label="Page d'accueil">
        <!-- Colonne gauche : hero -->
        <div class="home-grid__main" data-reveal="fade">
            <div class="home-hero__wrap">
                <section class="home-hero">
                    <video class="home-hero__video" autoplay muted loop playsinline preload="metadata"
                        poster="/uploads/pages/home/hero-poster.jpg" aria-hidden="true">
                        <source media="(max-width: 767px)" src="/uploads/pages/home/videos/Intro%20TP.mp4"
                            type="video/mp4">
                        <source src="/uploads/pages/home/videos/videoHome2.mp4" type="video/mp4">
                    </video>
                    <!--
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

                    -->
                    <!-- SLOT HERO  -->
                    <div class="corner-slot corner-slot--hero" aria-label="Réseaux sociaux">
                        <a class="corner-slot__btn" href="https://www.facebook.com/kevinbrien6/?locale=fr_FR"
                            aria-label="Facebook" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M13.37 20.99v-7.64h2.56l.38-2.98h-2.94V8.46c0-.86.24-1.45 1.48-1.45h1.58V4.34c-.77-.08-1.54-.11-2.31-.1-2.28 0-3.85 1.39-3.85 3.95v2.18H7.69v2.98h2.58v7.64h3.1Z"
                                    fill="#1877F2" />
                            </svg>
                        </a>
                        <a class="corner-slot__btn" href="https://www.instagram.com/traiteur.passion60/"
                            aria-label="Instagram" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <defs>
                                    <linearGradient id="instagram-official-gradient" x1="3.4" y1="20.6" x2="20.6"
                                        y2="3.4" gradientUnits="userSpaceOnUse">
                                        <stop offset="0" stop-color="#FEDA75" />
                                        <stop offset="0.32" stop-color="#FA7E1E" />
                                        <stop offset="0.62" stop-color="#D62976" />
                                        <stop offset="0.85" stop-color="#962FBF" />
                                        <stop offset="1" stop-color="#4F5BD5" />
                                    </linearGradient>
                                </defs>
                                <path
                                    d="M7.2 3h9.6A4.2 4.2 0 0 1 21 7.2v9.6a4.2 4.2 0 0 1-4.2 4.2H7.2A4.2 4.2 0 0 1 3 16.8V7.2A4.2 4.2 0 0 1 7.2 3Zm0 1.5A2.7 2.7 0 0 0 4.5 7.2v9.6a2.7 2.7 0 0 0 2.7 2.7h9.6a2.7 2.7 0 0 0 2.7-2.7V7.2a2.7 2.7 0 0 0-2.7-2.7H7.2Zm10.05 1.13a1.05 1.05 0 1 1 0 2.1 1.05 1.05 0 0 1 0-2.1ZM12 7.05A4.95 4.95 0 1 1 7.05 12 4.96 4.96 0 0 1 12 7.05Zm0 1.5A3.45 3.45 0 1 0 15.45 12 3.45 3.45 0 0 0 12 8.55Z"
                                    fill="url(#instagram-official-gradient)" fill-rule="evenodd" clip-rule="evenodd" />
                            </svg>
                        </a>

                    </div>

                </section>

            </div>
        </div>

        <!-- Colonne droite : tiles -->
        <aside class="home-grid__side" aria-label="Accès rapides">
            <a class="home-tile motion-card" href="/menu" data-reveal="up" data-stagger>
                <div class="home-tile__media"
                    style="--tile-bg:url('/uploads/pages/home/images/carteDuMomentIllu.jpg');">
                    <!-- SLOT TILE (inside) -->
                    <span class="corner-slot corner-slot--tile" aria-hidden="true">
                        <span class="corner-slot__label">Carte</span>
                        <span class="corner-slot__plus">+</span>
                    </span>
                </div>
            </a>

            <a class="home-tile motion-card" href="/contact" data-reveal="up" data-stagger>
                <div class="home-tile__media"
                    style="--tile-bg:url('/uploads/pages/home/images/contactIllu.png'); --tile-bg-size:auto 100%; --tile-bg-size-hover:auto 108%; --tile-bg-position:center center;">
                    <span class="corner-slot corner-slot--tile" aria-hidden="true">
                        <span class="corner-slot__label">Contact</span>
                        <span class="corner-slot__plus">+</span>
                    </span>
                </div>
            </a>

            <a class="home-tile motion-card" href="/a-propos" data-reveal="fade" data-stagger>
                <div class="home-tile__media"
                    style="--tile-bg:url('/uploads/pages/home/images/nousDécouvrirIllu.webp');">
                    <span class="corner-slot corner-slot--tile" aria-hidden="true">
                        <span class="corner-slot__label">Nous découvrir</span>
                        <span class="corner-slot__plus">+</span>
                    </span>
                </div>
            </a>
        </aside>
    </section>
</main>
