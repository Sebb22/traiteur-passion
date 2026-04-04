<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */
    /** @var string $selectedCategory */

    $selectedCategory     = trim((string) ($selectedCategory ?? ''));
    $selectedCategoryName = '';

    foreach ($sections as $section) {
    if ((string) ($section['slug'] ?? '') !== $selectedCategory) {
        continue;
    }

    $selectedCategoryName = (string) ($section['name'] ?? '');
    break;
    }

    $formId                 = 'quoteForm';
    $formAction             = '/devis';
    $formSubmitLabel        = 'Envoyer ma demande';
    $selectedSectionSlug    = $selectedCategory;
    $limitToSelectedSection = false;
    $accordionTitle         = $selectedCategoryName !== ''
    ? 'Composer ma commande pour ' . $selectedCategoryName
    : 'Sélectionner des items du menu (optionnel)';
    $accordionSummary = $selectedCategoryName !== ''
    ? 'Composer ma commande de ' . $selectedCategoryName
    : 'Choisir parmi nos menus';
    $accordionContext = $selectedCategoryName !== ''
    ? 'La catégorie choisie est mise en avant ci-dessous, mais vous pouvez compléter votre devis avec n’importe quelle autre catégorie sans revenir au menu.'
    : 'Sélectionnez les éléments qui vous intéressent et nous vous recontactons pour finaliser votre devis.';
    $selectionSummaryHint = $selectedCategoryName !== ''
    ? 'Votre catégorie de départ est déjà mise en avant. Vous pouvez enrichir la demande avec d’autres catégories juste en dessous.'
    : 'Votre sélection se met à jour en direct pendant que vous composez votre demande.';
?>

<main class="siteMain siteContainer snapY">
	<section class="menuSplit menuSplit--contact menuSplit--quote" data-wheel-redirect data-wheel-target=".menuPanel--contact">

		<div class="menuSplit__left" aria-label="Visuel de la page Devis">
			<div class="menuHero menuHero--contact menuHero--quote">
				<img class="menuHero__img" src="/uploads/pages/contact/contactIllu.png" alt="" aria-hidden="true">
				<h1 class="menuHero__title">Devis</h1>
			</div>
		</div>

		<div class="menuSplit__right">
			<div class="menuPanel menuPanel--contact">

				<header class="contactHead">
					<span class="menuSectionTitle__line" aria-hidden="true"></span>
					<h2 class="contactHead__title">Composer votre demande</h2>
					<span class="menuSectionTitle__line" aria-hidden="true"></span>
					<p class="contactHead__sub">
                        Composez une première intention de commande, puis nous vous aidons à la transformer en proposition précise, cohérente et réaliste.
					</p>
					<?php if ($selectedCategoryName !== ''): ?>
					<p class="contactHead__meta">
						Catégorie présélectionnée : <?php echo htmlspecialchars($selectedCategoryName, ENT_QUOTES, 'UTF-8'); ?>.
						Vous pouvez partir de cette base, l’enrichir librement, puis nous laisser affiner le bon calibrage.
					</p>
					<?php else: ?>
					<p class="contactHead__meta">Plateaux repas : prévoir un minimum de 72h avant l'événement ou la livraison. Intervention et livraison selon votre zone.</p>
					<?php endif; ?>
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
						<a class="contactInfo__v" href="mailto:contact@traiteur-passion.fr">contact@traiteur-passion.fr</a>
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
