<?php
    $legalAddress = '631 rue de Compiègne, 60162 Vignemont';
    $legalEmail   = 'contact@trateurpassion.fr';
?>

<main class="siteMain siteContainer snapY">
    <section class="menuSplit menuSplit--contact menuSplit--legal" data-wheel-redirect data-wheel-target=".menuPanel--legal">
        <div class="menuSplit__left" aria-label="Visuel de la page Mentions légales">
            <div class="menuHero menuHero--contact menuHero--legal">
                <img class="menuHero__img" src="/uploads/pages/contact/adminIllu.png" alt="" aria-hidden="true">
                <h1 class="menuHero__title">Mentions légales</h1>
            </div>
        </div>

        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--contact menuPanel--legal">
                <header class="legalHead">
                    <span class="legalHead__eyebrow">Informations légales • site Traiteur Passion</span>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="legalHead__title">Cadre éditorial, hébergement et responsabilités</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="legalHead__intro">
                        Les présentes mentions légales précisent les informations relatives à l’édition, à l’hébergement,
                        au développement et à l’utilisation du site Traiteur Passion, conformément au droit français.
                    </p>
                    <p class="legalHead__meta">Dernière mise à jour : 28 avril 2026</p>
                </header>

                <section class="legalCard" aria-label="Résumé légal">
                    <article class="legalHighlight">
                        <span class="legalHighlight__kicker">Éditeur</span>
                        <strong class="legalHighlight__title">Kevin BRIEN · Traiteur Passion</strong>
                        <p class="legalHighlight__copy">Entreprise individuelle exerçant sous l’enseigne Traiteur Passion.</p>
                    </article>
                    <article class="legalHighlight">
                        <span class="legalHighlight__kicker">Hébergement</span>
                        <strong class="legalHighlight__title">OVH SAS</strong>
                        <p class="legalHighlight__copy">Infrastructure d’hébergement du site et services associés.</p>
                    </article>
                    <article class="legalHighlight">
                        <span class="legalHighlight__kicker">Développement</span>
                        <strong class="legalHighlight__title">SKDesignStudioDigital</strong>
                        <p class="legalHighlight__copy">Conception et développement du site web.</p>
                    </article>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">1. Éditeur du site</h3>
                    <div class="legalSection__content">
                        <p>Le site Traiteur Passion est édité par Kevin BRIEN, entrepreneur individuel, pour l’activité Traiteur Passion.</p>
                        <ul class="legalList">
                            <li><strong>Nom commercial :</strong> Traiteur Passion</li>
                            <li><strong>Nom de l’exploitant :</strong> Kevin BRIEN</li>
                            <li><strong>Adresse :</strong> <?php echo htmlspecialchars($legalAddress, ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Email :</strong> <a href="mailto:<?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?></a></li>
                            <li><strong>SIREN :</strong> 903 845 329</li>
                            <li><strong>SIRET :</strong> 903 845 329 00015</li>
                            <li><strong>Directeur de la publication :</strong> Kevin BRIEN</li>
                        </ul>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">2. Conception et développement</h3>
                    <div class="legalSection__content">
                        <p>Le site a été conçu et développé par SKDesignStudioDigital.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">3. Hébergement</h3>
                    <div class="legalSection__content">
                        <p>Le site est hébergé par OVH SAS.</p>
                        <ul class="legalList">
                            <li><strong>Hébergeur :</strong> OVH SAS</li>
                            <li><strong>Siège social :</strong> 2 rue Kellermann, 59100 Roubaix, France</li>
                            <li><strong>RCS :</strong> Lille Métropole 424 761 419 00045</li>
                            <li><strong>N° TVA :</strong> FR 22 424 761 419</li>
                            <li><strong>Téléphone :</strong> 09 72 10 10 07</li>
                            <li><strong>Site :</strong> <a href="https://www.ovhcloud.com/fr/" target="_blank" rel="noreferrer">ovhcloud.com</a></li>
                        </ul>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">4. Propriété intellectuelle</h3>
                    <div class="legalSection__content">
                        <p>Les contenus présents sur le site Traiteur Passion, notamment les textes, visuels, photographies, éléments graphiques, logo, icônes et structure du site, sont protégés par le droit de la propriété intellectuelle.</p>
                        <p>Sauf autorisation écrite préalable, toute reproduction, représentation, adaptation, publication ou exploitation, totale ou partielle, de ces éléments est interdite.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">5. Limitation de responsabilité</h3>
                    <div class="legalSection__content">
                        <p>Traiteur Passion s’efforce de fournir sur ce site des informations aussi précises que possible. Toutefois, des inexactitudes, omissions ou interruptions peuvent survenir.</p>
                        <p>L’éditeur ne pourra être tenu responsable des dommages directs ou indirects liés à l’utilisation du site, à l’impossibilité d’y accéder ou à l’usage des informations qui y figurent.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">6. Données personnelles</h3>
                    <div class="legalSection__content">
                        <p>Les données transmises via les formulaires du site ou la boutique en ligne sont utilisées uniquement pour traiter les demandes de contact, de devis et les commandes.</p>
                        <p>Conformément à la réglementation applicable, vous pouvez demander l’accès, la rectification ou la suppression de vos données en écrivant à <a href="mailto:<?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?></a>.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">7. Liens hypertextes et cookies</h3>
                    <div class="legalSection__content">
                        <p>Le site peut contenir des liens vers des sites tiers. Traiteur Passion n’exerce aucun contrôle sur ces contenus externes et ne saurait être tenu responsable de leur disponibilité ou de leur contenu.</p>
                        <p>Le site peut également utiliser des cookies ou technologies similaires pour améliorer l’expérience de navigation, mesurer la fréquentation et assurer certains services du site.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">8. Droit applicable</h3>
                    <div class="legalSection__content">
                        <p>Les présentes mentions légales sont soumises au droit français. En cas de litige, et à défaut de résolution amiable, les juridictions françaises compétentes pourront être saisies.</p>
                    </div>
                </section>

                <section class="legalSection">
                    <h3 class="legalSection__title">9. Contact</h3>
                    <div class="legalSection__content">
                        <p>Pour toute question relative au site ou à ces mentions légales, vous pouvez écrire à <a href="mailto:<?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($legalEmail, ENT_QUOTES, 'UTF-8'); ?></a>.</p>
                    </div>
                </section>

                <?php require dirname(__DIR__) . '/partials/menu-footer.php'; ?>
            </div>
        </div>
    </section>
</main>