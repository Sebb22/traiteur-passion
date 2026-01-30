<main class="siteMain siteContainer snapY">
    <section class="menuSplit menuSplit--about">
        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la page À propos">
            <div class="menuHero menuHero--about">
                <img class="menuHero__img" src="/uploads/images/about/about2Illu.jpg" alt="" aria-hidden="true">
                <h1 class="menuHero__title">À propos</h1>
            </div>
        </div>

        <!-- RIGHT : panel -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--about">
                <!-- tabs 
                <nav class="menuTabs menuTabs--about" aria-label="Navigation À propos">
                    <a href="#intro" class="menuTabs__tab is-active">Intro</a>
                    <a href="#galerie" class="menuTabs__tab">Galerie</a>
                    <a href="#recommandations" class="menuTabs__tab">Avis</a>
                    <a href="#histoire" class="menuTabs__tab">Histoire</a>
                    <a href="#contact" class="menuTabs__tab">Devis</a>
                </nav>
-->
                <!-- INTRO (card) -->
                <section class="aboutCard aboutCard--intro" id="intro" aria-label="Introduction">
                    <h2 class="aboutCard__title">Cuisine de saison, événements sur mesure</h2>
                    <p class="aboutCard__text">
                        Traiteur Passion accompagne vos moments importants : mariages, réceptions, cocktails,
                        plateaux repas et événements d’entreprise — avec une exigence simple : le goût, la justesse,
                        et une exécution impeccable.
                    </p>

                    <div class="aboutActions">
                        <a class="btn btn--primary" href="/devis">Demander un devis</a>
                        <a class="btn btn--ghost" href="/contact">Nous contacter</a>
                    </div>
                </section>

                <!-- GALERIE (card) -->
                <section class="aboutCard aboutCard--galerie" id="galerie" aria-label="Galerie">


                    <div class="aboutSlider" aria-label="Galerie">
                        <button class="aboutSlider__btn aboutSlider__btn--prev" type="button"
                            aria-label="Image précédente">‹</button>

                        <div class="aboutSlider__viewport" tabindex="0">
                            <div class="aboutSlider__track">
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/images/about/galery/BoudinBlancIllu.jpg" alt="Boudin blanc">
                                </figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/images/about/galery/BuffetCharcuterieIllu.jpg"
                                        alt="Buffet charcuterie"></figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/images/about/galery/PetitsFoursIllu.png" alt="Petits fours">
                                </figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/images/about/galery/SaladeCroquanteIllu.jpg"
                                        alt="Salade croquante"></figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/images/about/galery/AssietteDressee.jpg" alt="Assiette dressée">
                                </figure>
                            </div>
                        </div>

                        <button class="aboutSlider__btn aboutSlider__btn--next" type="button"
                            aria-label="Image suivante">›</button>
                    </div>
                </section>

                <!-- ROW “RATINGS” -->
                <div class="aboutRatingsRow" id="recommandations" aria-label="Avis et distinctions">

                    <!-- Google -->
                    <article class="aboutReview" aria-label="Avis Google">
                        <a class="aboutReview__link"
                            href="https://www.google.com/search?sca_esv=95c4599e20cb2333&sxsrf=ANbL-n4UoR9hWZxE_DJOyM6c_ZDOCjkuoA:1769787102684&q=Traiteur+passion+Avis&rflfq=1&num=20&stick=H4sIAAAAAAAAAONgkxIxNDa3MLS0tDAzNTcyNjU0NLM0Nd7AyPiKUTSkKDGzJLW0SKEgsbg4Mz9PwbEss3gRK3ZxAH98L6dLAAAA&rldimm=13781998657235116953&tbm=lcl&hl=fr-FR&sa=X&ved=2ahUKEwj4o9XcyrOSAxW1caQEHbmhH6QQ9fQKegQIUxAG&biw=1920&bih=941&dpr=1&aic=0#lkt=LocalPoiReviews"
                            target="_blank" rel="noopener">
                            <header class="aboutReview__head">
                                <div class="aboutReview__stars" aria-hidden="true">★★★★★</div>
                                <div class="aboutReview__source">Google</div>

                            </header>

                            <p class="aboutReview__text">
                                Un repas pour 15 à la maison… Merci pour votre gentillesse et votre accueil.
                            </p>

                            <span class="aboutReview__cta">Lire l’avis →</span>
                        </a>
                    </article>

                    <!-- Facebook -->
                    <article class="aboutReview" aria-label="Avis Facebook">
                        <a class="aboutReview__link" href="https://www.facebook.com/kevinbrien6/reviews" target="_blank"
                            rel="noopener">
                            <header class="aboutReview__head">
                                <div class="aboutReview__stars" aria-hidden="true">★★★★★</div>
                                <div class="aboutReview__source">Facebook</div>

                            </header>

                            <p class="aboutReview__text">
                                Repas couscous pour l’anniversaire… je recommanderai pour d’autres occasions !
                            </p>

                            <span class="aboutReview__cta">Lire l’avis →</span>
                        </a>
                    </article>

                    <!-- Presse -->
                    <article class="aboutReview aboutReview--press" aria-label="Article de presse">
                        <a class="aboutReview__link"
                            href="https://www.courrier-picard.fr/id473936/article/2023-12-09/battle-de-pate-croute-dans-loise-qui-du-chef-ou-du-traiteur-est-le-meilleur"
                            target="_blank" rel="noopener">
                            <header class="aboutReview__head">
                                <div class="aboutReview__stars" aria-hidden="true">★★★★★</div>
                                <div class="aboutReview__source">On parle de nous</div>

                            </header>

                            <p class="aboutReview__text">
                                Battle de pâté en croûte dans l’Oise — qui du chef ou du traiteur est le meilleur ?
                            </p>

                            <span class="aboutReview__cta">Lire l’article →</span>
                        </a>
                    </article>

                </div>


                <!-- ENGAGEMENTS (card) -->
                <section class="aboutCard aboutCard--engagements" id="engagements" aria-label="Nos engagements">
                    <h2 class="aboutCard__title">Nos engagements</h2>

                    <ul class="aboutPills">
                        <li class="aboutPills__pill">
                            <span class="aboutPills__title">Sur-mesure</span>
                            <span class="aboutPills__sub">Menus & formats adaptés</span>
                        </li>
                        <li class="aboutPills__pill">
                            <span class="aboutPills__title">Saisonnalité</span>
                            <span class="aboutPills__sub">Produits frais & justes</span>
                        </li>
                        <li class="aboutPills__pill">
                            <span class="aboutPills__title">Sérénité</span>
                            <span class="aboutPills__sub">Organisation & timing</span>
                        </li>
                    </ul>
                </section>

                <!-- HISTOIRE (card) -->
                <section class="aboutCard aboutCard--histoire" id="histoire" aria-label="Notre histoire">
                    <h2 class="aboutCard__title">Notre histoire</h2>

                    <p class="aboutCard__text">
                        Traiteur Passion est né d’une envie : proposer une cuisine sincère et élégante, pensée pour vos
                        événements…
                    </p>

                    <ul class="aboutFacts">
                        <li class="aboutFacts__item"><span class="aboutFacts__k">Zone</span><span
                                class="aboutFacts__v">Compiègne & alentours</span></li>
                        <li class="aboutFacts__item"><span class="aboutFacts__k">Formats</span><span
                                class="aboutFacts__v">Cocktails, buffets, repas servis</span></li>
                        <li class="aboutFacts__item"><span class="aboutFacts__k">Promesse</span><span
                                class="aboutFacts__v">Goût
                                + organisation</span></li>
                    </ul>
                </section>

                <!-- CTA (card) -->
                <section class="aboutCard aboutCard--contact" id="contact" aria-label="Demander un devis">
                    <h2 class="aboutCard__title">On imagine votre événement ?</h2>
                    <p class="aboutCard__text">Dites-nous le format, le lieu, et l’ambiance — on vous propose une
                        formule claire.</p>
                    <a class="btn btn--primary" href="/devis">Demander un devis</a>
                </section>

                <footer class="menuFooter">
                    <p>© <?php echo date('Y') ?> Traiteur Passion</p>
                </footer>
            </div>

        </div>
    </section>
</main>