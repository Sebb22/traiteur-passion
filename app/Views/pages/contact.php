<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $formId                 = 'contactForm';
    $formAction             = '/contact';
    $formSubmitLabel        = 'Recevoir une première proposition';
    $selectedSectionSlug    = '';
    $limitToSelectedSection = false;
    $accordionTitle         = 'Sélectionner des items du menu (optionnel)';
    $accordionSummary       = 'Choisir parmi nos menus';
    $accordionContext       = 'Cette sélection sert à préparer votre devis. Notre équipe vous recontacte systématiquement pour confirmer le format, la livraison et les derniers détails.';
?>

<main class="siteMain siteContainer snapY">
    <section class="menuSplit menuSplit--contact" data-wheel-redirect data-wheel-target=".menuPanel--contact">

        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la page Contact">
            <div class="menuHero menuHero--contact">
                <img class="menuHero__img" src="/uploads/pages/contact/contactIllu.png" alt="" aria-hidden="true">
                <h1 class="menuHero__title">Contact</h1>
            </div>
        </div>

        <!-- RIGHT : panel -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--contact">

                <header class="contactHead">
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="contactHead__title">Demander un devis</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="contactHead__sub">
                        Donnez-nous le cadre, l’envie et les contraintes de votre réception. Nous revenons vers vous avec une proposition structurée, lisible et directement exploitable.
                    </p>
                    <p class="contactHead__meta">Plateaux repas : prévoir un minimum de 72h avant l'événement ou la livraison. Intervention et livraison selon votre zone.</p>
                </header>
                <?php require dirname(__DIR__) . '/partials/request-form.php'; ?>
                <section class="contactInfos" aria-label="Coordonnées">
                    <div class="contactInfo">
                        <span class="contactInfo__k">Téléphone</span>
                        <a class="contactInfo__v" href="tel:+33659215349">0659215349</a>
                        <a class="contactInfo__v" href="tel:+330761603538">0761603538</a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k">Email</span>
                        <a class="contactInfo__v"
                            href="mailto:contact@traiteur-passion.fr">contact@traiteur-passion.fr</a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k">Zone</span>
                        <span class="contactInfo__v">Vignemont • Oise</span>
                    </div>
                </section>

                <footer class="menuFooter">
                    <p>© <?php echo date('Y') ?> Traiteur Passion</p>
                </footer>

            </div>
        </div>
    </section>
</main>