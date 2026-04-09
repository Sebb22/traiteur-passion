<?php $aboutReviews = isset($aboutReviews) && is_array($aboutReviews) ? $aboutReviews : []; ?>

<main class="siteMain siteContainer">
    <section class="menuSplit menuSplit--about">
        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la page À propos">
            <div class="menuHero menuHero--about">
                <img class="menuHero__img" src="/uploads/pages/about/about2Illu.jpg" alt="" aria-hidden="true">
                <h1 class="menuHero__title">À propos</h1>
            </div>
        </div>

        <!-- RIGHT : panel -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--about">

                <!-- INTRO (card) -->
                <section class="aboutCard aboutCard--intro" id="intro" aria-label="Introduction">
                    <h2 class="aboutCard__title">Votre événement, notre passion gourmande</h2>
                    <p class="aboutCard__text">
                        Traiteur Passion, c’est une maison familiale menée par Kévin Brien, charcutier-traiteur formé au
                        CEPROC à Paris
                        et passé chez Joly Traiteur (MOF). De Vignemont à toute la Picardie, nous imaginons des
                        prestations sur-mesure
                        pour mariages, cocktails, événements privés et d’entreprise — avec une promesse simple : du 100%
                        maison,
                        des produits de saison, et une équipe à l’écoute.
                    </p>

                    <div class="aboutActions">
                        <a class="btn btn--primary" href="/contact">Demander un devis</a>
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
                                        src="/uploads/pages/about/galery/PetitsFoursIllu.png" alt="Petits fours">
                                </figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/pages/about/galery/BuffetCharcuterieIllu.jpg"
                                        alt="Buffet charcuterie"></figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/pages/about/galery/BoudinBlancIllu.jpg" alt="Boudin blanc">
                                </figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/pages/about/galery/SaladeCroquanteIllu.jpg"
                                        alt="Salade croquante"></figure>
                                <figure class="aboutSlider__slide"><img
                                        src="/uploads/pages/about/galery/AssietteDressee.jpg" alt="Assiette dressée">
                                </figure>
                            </div>
                        </div>

                        <button class="aboutSlider__btn aboutSlider__btn--next" type="button"
                            aria-label="Image suivante">›</button>
                    </div>
                </section>

                <!-- ROW “RATINGS” -->
                <div class="aboutRatingsRow" id="recommandations" aria-label="Avis et distinctions">
                    <?php foreach ($aboutReviews as $review): ?>
                        <article class="aboutReview" aria-label="<?php echo htmlspecialchars((string) ($review['aria_label'] ?? 'Avis client'), ENT_QUOTES, 'UTF-8'); ?>">
                            <a class="aboutReview__link"
                                href="<?php echo htmlspecialchars((string) ($review['link'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank" rel="noopener">
                                <header class="aboutReview__head">
                                    <div class="aboutReview__stars" aria-hidden="true"><?php echo htmlspecialchars((string) ($review['badge'] ?? '★★★★★'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="aboutReview__source"><?php echo htmlspecialchars((string) ($review['source'] ?? 'Avis client'), ENT_QUOTES, 'UTF-8'); ?></div>
                                </header>
                                <p class="aboutReview__text"><?php echo htmlspecialchars((string) ($review['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                <span class="aboutReview__cta"><?php echo htmlspecialchars((string) ($review['cta'] ?? 'Lire plus →'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </article>
                    <?php endforeach; ?>

                    <!-- Presse -->
                    <article class="aboutReview aboutReview--press" aria-label="Article de presse">
                        <a class="aboutReview__link"
                            href="https://www.courrier-picard.fr/id473936/article/2023-12-09/battle-de-pate-croute-dans-loise-qui-du-chef-ou-du-traiteur-est-le-meilleur"
                            target="_blank" rel="noopener">
                            <header class="aboutReview__head">
                                <div class="aboutReview__stars" aria-hidden="true">★★★★★</div>
                                <div class="aboutReview__source">On parle de nous</div>
                            </header>
                            <p class="aboutReview__text">Battle de pâté en croûte dans l’Oise — qui du chef ou du
                                traiteur est le meilleur ?</p>
                            <span class="aboutReview__cta">Lire l’article →</span>
                        </a>
                    </article>
                </div>

                <!-- CHIFFRES CLÉS (card) -->
                <section class="aboutCard aboutCard--stats" id="engagements" aria-label="Chiffres clés">
                    <h2 class="aboutCard__title">En 2025, Traiteur Passion, c’est…</h2>

                    <div class="aboutStats" role="list" aria-label="Chiffres clés">
                        <article class="aboutStat" role="listitem" aria-label="Mariages réalisés">
                            <span class="aboutStat__num">308</span>
                            <span class="aboutStat__label">Plateaux repas livrés</span>
                        </article>

                        <article class="aboutStat" role="listitem" aria-label="Événements professionnels réalisés">
                            <span class="aboutStat__num">1255</span>
                            <span class="aboutStat__label">Dégustations de poêlons</span>
                        </article>

                        <article class="aboutStat" role="listitem" aria-label="Personnes servies par an">
                            <span class="aboutStat__num">11 035</span>
                            <span class="aboutStat__label">Pièces cocktails réalisées au labo </span>
                        </article>
                    </div>

                    <p class="aboutCard__text aboutCard__text--muted">
                        Des chiffres indicatifs — l’essentiel reste le sur-mesure et la qualité du “fait maison”.
                    </p>
                </section>


                <!-- HISTOIRE (card) -->
                <section class="aboutCard aboutCard--histoire" id="histoire" aria-label="Notre histoire">
                    <h2 class="aboutCard__title">Notre histoire</h2>

                    <p class="aboutCard__text">
                        Traiteur Passion est né en 2022, après un parcours construit sur le terrain : CAP
                        charcutier-traiteur au CEPROC à Paris,
                        apprentissage en mention complémentaire chez un MOF (Pascal Joly), puis plusieurs années en
                        maison de traiteur
                        avant un retour dans l’Oise. Depuis, nous mettons la même exigence dans chaque prestation : une
                        cuisine élégante,
                        sincère, et parfaitement orchestrée. Et parce que la passion compte aussi en concours, Kévin a
                        été médaillé de bronze
                        au championnat de pâté en croûte des jeunes (moins de 30 ans), en version traditionnelle comme
                        moderne.
                    </p>

                    <ul class="aboutFacts">
                        <li class="aboutFacts__item">
                            <span class="aboutFacts__k">Zone</span>
                            <span class="aboutFacts__v">Vignemont • Picardie</span>
                        </li>
                        <li class="aboutFacts__item">
                            <span class="aboutFacts__k">Formats</span>
                            <span class="aboutFacts__v">Mariages, cocktails, entreprises, anniversaires</span>
                        </li>
                        <li class="aboutFacts__item">
                            <span class="aboutFacts__k">Promesse</span>
                            <span class="aboutFacts__v">Familiale • 100% maison • à l’écoute</span>
                        </li>
                    </ul>
                </section>

                <section class="aboutCard aboutCard--team" id="equipe" aria-label="Notre équipe">
                    <h2 class="aboutCard__title">Les mains derrière vos réceptions</h2>
                    <p class="aboutCard__text">
                        Traiteur Passion, c’est une équipe proche du terrain, polyvalente et soudée. Chacun apporte sa
                        signature, du laboratoire jusqu’au service, pour que chaque événement reste fluide, généreux et
                        soigné jusque dans les détails.
                    </p>

                    <div class="aboutTeam" role="list" aria-label="Membres de l'équipe">
                        <article class="aboutTeamMember" role="listitem" aria-label="Kévin, gérant">
                            <p class="aboutTeamMember__role">Gérant • laboratoire</p>
                            <h3 class="aboutTeamMember__name">Kévin</h3>
                            <p class="aboutTeamMember__text">
                                Il imagine les recettes et orchestre toute la partie laboratoire, de l’apéritif à
                                l’entrée, jusqu’aux plats et aux fromages. C’est la main qui donne le ton de la maison.
                            </p>
                            <p class="aboutTeamMember__signature">Signature : la précision du fait maison et le goût du détail.</p>
                        </article>

                        <article class="aboutTeamMember" role="listitem" aria-label="Mylène, responsable clientèle">
                            <p class="aboutTeamMember__role">Clientèle • coordination</p>
                            <h3 class="aboutTeamMember__name">Mylène, dite Mymy</h3>
                            <p class="aboutTeamMember__text">
                                Elle pilote la relation client, la gestion et la mise en place des événements. C’est le
                                visage du lien, de l’organisation et du déroulé bien tenu.
                            </p>
                            <p class="aboutTeamMember__signature">Signature : une réception cadrée avec calme, écoute et réactivité.</p>
                        </article>

                        <article class="aboutTeamMember" role="listitem" aria-label="Maëlle, cheffe de la partie sucrée">
                            <p class="aboutTeamMember__role">Sucré • salé • service</p>
                            <h3 class="aboutTeamMember__name">Maëlle</h3>
                            <p class="aboutTeamMember__text">
                                Petite sœur de Kévin et fondatrice de L’Atelier Gourmand de Maëlle, elle signe toute la
                                partie sucrée : pièces montées, gâteaux et mignardises. Diplômée en cuisine, elle prête
                                aussi main forte au salé comme au service.
                            </p>
                            <p class="aboutTeamMember__signature">Signature : plusieurs casquettes, toujours avec gourmandise.</p>
                        </article>

                        <article class="aboutTeamMember" role="listitem" aria-label="Kylian, renfort laboratoire et service">
                            <p class="aboutTeamMember__role">Renfort • laboratoire • service</p>
                            <h3 class="aboutTeamMember__name">Kylian, dit Bappe</h3>
                            <p class="aboutTeamMember__text">
                                Présent dès qu’il faut renforcer l’équipe, il intervient aussi bien au laboratoire qu’au
                                service. Un appui fiable quand le rythme s’accélère.
                            </p>
                            <p class="aboutTeamMember__signature">Signature : répondre présent, vite et bien.</p>
                        </article>

                        <article class="aboutTeamMember" role="listitem" aria-label="Magali, renfort polyvalent">
                            <p class="aboutTeamMember__role">Renfort polyvalent</p>
                            <h3 class="aboutTeamMember__name">Magali</h3>
                            <p class="aboutTeamMember__text">
                                Maman de Kévin, elle aide partout où il le faut : laboratoire, vaisselle, service et
                                logistique du quotidien. Une présence précieuse sur tous les fronts.
                            </p>
                            <p class="aboutTeamMember__signature">Signature : l’énergie de la famille quand il faut tenir le tempo.</p>
                        </article>
                    </div>
                </section>

                <!-- CTA (card) -->
                <section class="aboutCard aboutCard--contact" id="contact" aria-label="Demander un devis">
                    <h2 class="aboutCard__title">On imagine votre événement ?</h2>
                    <p class="aboutCard__text">
                        Dites-nous le format, le lieu, vos envies (et vos contraintes alimentaires si besoin) — on vous
                        propose une formule claire.
                    </p>
                    <a class="btn btn--primary" href="/contact">Demander un devis</a>
                </section>

                <footer class="menuFooter">
                    <p>© <?php echo date('Y') ?> Traiteur Passion</p>
                </footer>
            </div>
        </div>
    </section>
</main>