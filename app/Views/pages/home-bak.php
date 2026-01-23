<section class="home-grid home-section">
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
                    <span class="corner-slot__label">Plateaux repas</span>
                    <span class="corner-slot__plus">+</span>
                </span>
            </div>
        </a>

        <a class="home-tile motion-card" href="/menu" data-reveal="up" data-stagger>
            <div class="home-tile__media" style="--tile-bg:url('/uploads/images/home/nousDécouvrirIllu.webp');">
                <span class="corner-slot corner-slot--tile" aria-hidden="true">
                    <span class="corner-slot__label">Nous découvrir</span>
                    <span class="corner-slot__plus">+</span>
                </span>
            </div>
        </a>
    </aside>
</section>




<!-- SECTION: Notre histoire 
<section class="home-section home-story" data-reveal="up" data-stagger>
    <header class="home-section__head">
        <p class="home-kicker">Le goût, le rythme, l’élégance</p>
        <h2 class="home-title">Une cuisine de saison, pensée pour votre moment</h2>
        <p>
            Basé à Compiègne, Traiteur Passion accompagne particuliers et professionnels
            dans l’organisation de réceptions, mariages et événements d’entreprise.
        </p>
    </header>

    <div class="home-story__grid">
         ✅ media visible 
        <div class="home-story__media motion-card" aria-hidden="true"
            style="--media-bg:url('/uploads/images/home/story2Illu.jpg');">
        </div>

        <div class="home-story__right">
            <article class="home-story__card motion-card">
                <p>
                    Chez Traiteur Passion, on cuisine comme on reçoit : avec attention.
                    Des produits soigneusement sélectionnés, une exécution précise, et une organisation
                    fluide pour que vous profitiez vraiment.
                </p>
                <p>
                    Mariage, événement d’entreprise ou réception privée : on construit une prestation
                    sur-mesure, simple à organiser et mémorable à vivre.
                </p>
                <p>
                    Découvrez notre <a href="/menu">carte de saison</a> ou
                    nos <a href="/prestations">prestations traiteur</a>.
                </p>

                <div class="home-story__actions">
                    <a class="btn btn--primary" href="/devis">Demander un devis</a>
                    <a class="btn btn--ghost" href="/nous-decouvrir">Découvrir l’équipe</a>
                </div>
            </article>

            <div class="home-story__proofs">
                <div class="home-proof motion-card">
                    <h3>Produits de saison</h3>
                    <p>Cartes courtes, goût net, options alimentaires.</p>
                </div>
                <div class="home-proof motion-card">
                    <h3>Organisation sereine</h3>
                    <p>Timing, logistique, service : tout est cadré.</p>
                </div>
                <div class="home-proof motion-card">
                    <h3>Présentation élégante</h3>
                    <p>Dressage, pièces cocktail, buffets et plateaux.</p>
                </div>
            </div>
        </div>
    </div>
</section>

 SECTION: Notre méthode 
<section class="home-section home-method" data-reveal="scale" data-stagger>
    <header class="home-section__head">
        <p class="home-kicker">Notre méthode</p>
        <h2 class="home-title">Un déroulé simple, une exécution impeccable</h2>
    </header>

    <div class="home-method__grid">
        <article class="home-step motion-card">
            <span class="home-step__num">01</span>
            <h3>On écoute</h3>
            <p>Date, lieu, budget, contraintes : on cadre les essentiels.</p>
        </article>

        <article class="home-step motion-card">
            <span class="home-step__num">02</span>
            <h3>On propose</h3>
            <p>Menu, boissons, options, quantité : une proposition claire.</p>
        </article>

        <article class="home-step motion-card">
            <span class="home-step__num">03</span>
            <h3>On organise</h3>
            <p>Timing, logistique, matériel : tout est prêt, sans stress.</p>
        </article>

        <article class="home-step motion-card">
            <span class="home-step__num">04</span>
            <h3>On régale</h3>
            <p>Service fluide, dressage soigné, goût au centre.</p>
        </article>
    </div>
</section>

 SECTION: Prestations 
<section class="home-section home-services" data-reveal="fade" data-stagger>
    <div class="home-section__head">
        <p class=" home-kicker">Prestations</p>
        <h2 class="home-title">Traiteur à Compiègne pour mariages et événements</h2>
    </div>

    <div class="home-services__grid">
        <a class="home-service motion-card" href="/prestations/mariage" data-reveal="up" data-stagger
            style="--card-bg:url('/uploads/images/home/presta-mariage.jpg');">
            <h3>Mariages</h3>
            <p>Accompagnement complet, dégustation, service & timing maîtrisés.</p>
            <span class="home-service__cta">Demander un devis</span>
        </a>

        <a class="home-service motion-card" href="/prestations/particuliers" data-reveal="up" data-stagger
            style="--card-bg:url('/uploads/images/home/presta-particuliers.jpg');">
            <h3>Particuliers</h3>
            <p>Anniversaire, baptême, réception : une formule adaptée à vos envies.</p>
            <span class="home-service__cta">Voir les offres</span>
        </a>

        <a class="home-service motion-card" href="/prestations/professionnels" data-reveal="up" data-stagger
            style="--card-bg:url('/uploads/images/home/presta-pro.jpg');">
            <h3>Professionnels</h3>
            <p>Séminaires, cocktails, inaugurations : fiable, fluide, premium.</p>
            <span class="home-service__cta">Organiser un évènement</span>
        </a>
    </div>
</section>

 SECTION: Stats 
<section class="home-section home-stats motion-card" data-reveal="up">
    <div class="home-stats__item">
        <strong>+120</strong><span>événements / an</span>
    </div>
    <div class="home-stats__item">
        <strong>3 000+</strong><span>convives servis</span>
    </div>
    <div class="home-stats__item">
        <strong>10+</strong><span>années d’expérience</span>
    </div>
</section>
 SECTION: Ce que vous pouvez attendre 
<section class="home-section home-trust" data-reveal="fade">
    <header class="home-section__head">
        <p class="home-kicker">Confiance</p>
        <h2 class="home-title">Ce que vous pouvez attendre</h2>
    </header>

    <div class="home-trust__grid">
        <div class="home-trust__item motion-card">
            <h3>Zone</h3>
            <p>
                Compiègne & alentours (Margny-lès-Compiègne, Venette, Lacroix-Saint-Ouen…).
                <a href="/prestations">Traiteur à Compiègne</a> pour événements privés et professionnels.
            </p>
        </div>

        <div class="home-trust__item motion-card">
            <h3>Capacité</h3>
            <p>Du petit comité au grand événement.</p>
        </div>
        <div class="home-trust__item motion-card">
            <h3>Options</h3>
            <p>Végétarien, sans porc, allergènes : on adapte.</p>
        </div>
    </div>
</section>

 SECTION: Témoignages 
<section class="home-section home-reviews" data-reveal="up" data-stagger>
    <div class="home-section__head">
        <p class="home-kicker">Avis</p>
        <h2 class="home-title">Ils en parlent mieux que nous</h2>
    </div>

    <div class="home-reviews__grid">
        <article class="home-review motion-card">
            <p>“Une équipe incroyable, un service irréprochable, des plats tous aussi délicieux les uns que
                les
                autres...
                Notre mariage a pris une dimensions supérieur grâce à eux !
                Notre prochain événement sera avec eux c'est sur !
                Encore merci 🫶”</p>
            <footer>— Client mariage</footer>
        </article>

        <article class="home-review motion-card">
            <p>“Soirée de 140 personnes, que du fait maison, je recommande vivement ! Pas un retour négatif!
                Bravo et
                merci”</p>
            <footer>— Entreprise</footer>
        </article>

        <article class="home-review motion-card">
            <p>“Un repas pour 15 à la maison. Le plat, un Rougail saucisse, très apprécié. Nous avons passé
                un très bon
                moment. La quantité et la qualité étaient au rdv. Merci pour votre gentillesse et votre
                accueil.”</p>
            <footer>— Réception privée</footer>
        </article>
    </div>
</section>

 SECTION: CTA final 
<section class="home-section home-cta motion-card" data-reveal="up">
    <div class="home-cta__content">
        <h2>Parlez-nous de votre événement</h2>
        <p>Réponse rapide, devis clair, proposition sur-mesure.</p>
    </div>
    <div class="home-cta__actions">
        <a class="btn btn--primary" href="/devis">Demander un devis</a>
        <a class="btn btn--ghost" href="/contact">Nous contacter</a>
    </div>
    <p class="home-cta__meta">Réponse sous 24–48h • Devis clair • Sans engagement</p>

</section>-->