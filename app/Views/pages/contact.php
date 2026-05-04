<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $formId                 = 'contactForm';
    $formAction             = '/contact';
    $formSubmitLabel        = 'Recevoir une première proposition';
    $selectedSectionSlug    = '';
    $limitToSelectedSection = false;
    $accordionTitle         = 'Sélectionner des items de la carte évènementielle (optionnel)';
    $accordionSummary       = 'Choisir dans la carte évènementielle';
    $accordionContext       = 'Cette sélection sert à préparer votre devis. Notre équipe vous recontacte systématiquement pour confirmer le format, la livraison et les derniers détails.';
    $contactIcon            = static function (string $name): string {
    static $icons = null;

    if ($icons === null) {
        $icons = [
            'method'   => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 5 8 4-8 4-8-4z" fill="currentColor" opacity=".12"/><path d="m12 5 8 4-8 4-8-4z"/><path d="m6 13 6 3 6-3"/><path d="m6 16 6 3 6-3"/></svg>',
            'reactive' => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="currentColor" opacity=".12"/><circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 1.8"/></svg>',
            'location' => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s6-5.2 6-10a6 6 0 0 0-12 0c0 4.8 6 10 6 10z" fill="currentColor" opacity=".12"/><path d="M12 21s6-5.2 6-10a6 6 0 0 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>',
            'phone'    => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.4 5.8c.6-.6 1.6-.6 2.2 0l1.4 1.4c.5.5.6 1.2.3 1.8l-.8 1.4c1 1.8 2.4 3.2 4.2 4.2l1.4-.8c.6-.3 1.4-.2 1.8.3l1.4 1.4c.6.6.6 1.6 0 2.2l-.8.8c-.7.7-1.8 1-2.8.8-3.2-.8-6.2-3.7-7.9-7-.4-.9-.2-2 .5-2.7z" fill="currentColor" opacity=".12"/><path d="M8.4 5.8c.6-.6 1.6-.6 2.2 0l1.4 1.4c.5.5.6 1.2.3 1.8l-.8 1.4c1 1.8 2.4 3.2 4.2 4.2l1.4-.8c.6-.3 1.4-.2 1.8.3l1.4 1.4c.6.6.6 1.6 0 2.2l-.8.8c-.7.7-1.8 1-2.8.8-3.2-.8-6.2-3.7-7.9-7-.4-.9-.2-2 .5-2.7z"/></svg>',
            'email'    => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="6.5" width="16" height="11" rx="2.5" fill="currentColor" opacity=".12"/><rect x="4" y="6.5" width="16" height="11" rx="2.5"/><path d="m6.8 9.2 5.2 4 5.2-4"/></svg>',
            'clock'    => '<svg class="contactIconGlyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="currentColor" opacity=".12"/><circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 1.8"/></svg>',
        ];
    }

    return $icons[$name] ?? $icons['location'];
    };
?>

<main class="siteMain siteContainer snapY">
    <section class="menuSplit menuSplit--contact" data-wheel-redirect data-wheel-target=".menuPanel--contact">

        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la page Contact">
            <div class="menuHero menuHero--contact">
                <img class="menuHero__img" src="/uploads/pages/contact/adminIllu.png" alt="" aria-hidden="true">
                <h1 class="menuHero__title">Contact</h1>
            </div>
        </div>

        <!-- RIGHT : panel -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--contact">

                <header class="contactHead">
                    <span class="contactHead__eyebrow">Compiègne • Oise • accompagnement sur mesure</span>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="contactHead__title">Demander un devis</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="contactHead__sub">
                        Donnez-nous le cadre, l’envie et les contraintes de votre réception. Nous revenons vers vous
                        avec une proposition structurée, lisible et directement exploitable.
                    </p>
                    <p class="contactHead__meta">Plateaux repas : prévoir un minimum de 72h avant l'événement ou la
                        livraison. Intervention et livraison selon votre zone.</p>
                    <div class="contactHead__story" aria-label="Approche de la demande">
                        <article class="contactHeadCard">
                            <span class="contactHeadCard__kicker"><span class="contactHeadCard__icon" aria-hidden="true"><?php echo $contactIcon('method'); ?></span>Méthode</span>
                            <strong class="contactHeadCard__title">Un cadrage simple avant le devis</strong>
                            <p class="contactHeadCard__copy">Nombre de convives, format, lieu, contraintes et ambiance
                                souhaitée: nous utilisons ces éléments pour proposer une base réellement exploitable.
                            </p>
                        </article>
                        <article class="contactHeadCard">
                            <span class="contactHeadCard__kicker"><span class="contactHeadCard__icon" aria-hidden="true"><?php echo $contactIcon('reactive'); ?></span>Réactivité</span>
                            <strong class="contactHeadCard__title">Un retour clair, pas un échange flou</strong>
                            <p class="contactHeadCard__copy">L’objectif n’est pas seulement de répondre vite, mais de
                                formuler une proposition structurée, lisible et cohérente avec votre réception.</p>
                        </article>
                        <article class="contactHeadCard">
                            <span class="contactHeadCard__kicker"><span class="contactHeadCard__icon" aria-hidden="true"><?php echo $contactIcon('location'); ?></span>Zone</span>
                            <strong class="contactHeadCard__title">Compiègne, Oise et alentours</strong>
                            <p class="contactHeadCard__copy">Mariage, événement professionnel, cocktail ou repas privé:
                                nous ajustons ensuite la logistique, la livraison et le niveau de service selon votre
                                contexte.</p>
                        </article>
                    </div>
                </header>
                <?php require dirname(__DIR__) . '/partials/request-form.php'; ?>
                <section class="contactInfos" aria-label="Coordonnées">
                    <div class="contactInfo contactInfo--phone">
                        <span class="contactInfo__k"><span class="contactInfo__icon" aria-hidden="true"><?php echo $contactIcon('phone'); ?></span>Téléphone</span>
                        <a class="contactInfo__v" href="tel:+33659215349">
                            <span class="contactInfo__line">06 59 21 53 49</span>
                            <span class="contactInfo__meta">Mylène • relation clientèle</span>
                        </a>
                        <a class="contactInfo__v" href="tel:+330761603538">
                            <span class="contactInfo__line">07 61 60 35 38</span>
                            <span class="contactInfo__meta">Kevin • gérant</span>
                        </a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k"><span class="contactInfo__icon" aria-hidden="true"><?php echo $contactIcon('email'); ?></span>Email</span>
                        <a class="contactInfo__v"
                            href="mailto:contact@traiteurpassion.fr">contact@traiteurpassion.fr</a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k"><span class="contactInfo__icon" aria-hidden="true"><?php echo $contactIcon('location'); ?></span>Zone</span>
                        <span class="contactInfo__v">Vignemont • Oise</span>
                    </div>
                    <div class="contactInfo contactInfo--hours">
                        <span class="contactInfo__k"><span class="contactInfo__icon" aria-hidden="true"><?php echo $contactIcon('clock'); ?></span>Horaires</span>
                        <div class="contactHours" aria-label="Horaires d'ouverture">
                            <div class="contactHours__row">
                                <span class="contactHours__day">Mardi au vendredi</span>
                                <span class="contactHours__time">8:30 - 19:00</span>
                            </div>
                            <div class="contactHours__row">
                                <span class="contactHours__day">Samedi</span>
                                <span class="contactHours__time">8:30 - 15:30</span>
                            </div>
                            <div class="contactHours__row contactHours__row--closed">
                                <span class="contactHours__day">Dimanche et lundi</span>
                                <span class="contactHours__time">Fermé</span>
                            </div>
                        </div>
                        <p class="contactInfo__note">
                            Les horaires du week-end peuvent varier selon les prestations en cours. Si besoin, vous
                            pouvez nous transmettre votre demande via le <a href="#contactForm">formulaire de contact</a>.
                        </p>
                    </div>
                </section>

                <?php
                    $locationCardVariant     = 'full';
                    $locationCardTitle       = 'Nous trouver';
                    $locationCardEyebrow     = 'Traiteur Passion • lieu de retrait & entreprise';
                    $locationCardDescription = 'Pour un retrait boutique ou un premier repère avant votre devis, voici l’adresse de Traiteur Passion. Nous confirmons ensuite avec vous le bon créneau ou la logistique adaptée à votre événement.';
                    $locationCardClass       = 'contactLocationCard';
                    $locationCardShowFacts   = false;
                    require dirname(__DIR__) . '/partials/location-card.php';
                ?>

                <?php require dirname(__DIR__) . '/partials/menu-footer.php'; ?>

            </div>
        </div>
    </section>
</main>