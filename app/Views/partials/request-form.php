<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $formId                 = trim((string) ($formId ?? 'requestForm'));
    $formAction             = trim((string) ($formAction ?? '/contact'));
    $formSubmitLabel        = trim((string) ($formSubmitLabel ?? 'Envoyer la demande'));
    $selectedSectionSlug    = trim((string) ($selectedSectionSlug ?? ''));
    $limitToSelectedSection = (bool) ($limitToSelectedSection ?? false);
    $accordionTitle         = trim((string) ($accordionTitle ?? 'Sélectionner des items du menu (optionnel)'));
    $accordionSummary       = trim((string) ($accordionSummary ?? 'Choisir parmi nos menus'));
    $accordionContext       = trim((string) ($accordionContext ?? 'Cette sélection sert à préparer votre devis. Notre équipe vous recontacte systématiquement pour confirmer le format, la livraison et les derniers détails.'));
    $selectionSummaryTitle  = trim((string) ($selectionSummaryTitle ?? 'Récapitulatif de votre sélection'));
    $selectionSummaryEmpty  = trim((string) ($selectionSummaryEmpty ?? 'Aucun item sélectionné pour le moment.'));
    $selectionSummaryHint   = trim((string) ($selectionSummaryHint ?? 'Ajoutez librement d’autres items ou catégories avant l’envoi de votre demande.'));
    $selectionSummaryBadge  = trim((string) ($selectionSummaryBadge ?? 'Devis en préparation'));
?>

<div class="contactCard">
    <div class="contactAlert contactAlert--success" data-form-success style="display: none;">
        ✅ Votre demande a été envoyée avec succès ! Nous vous recontacterons rapidement.
    </div>

    <div class="contactAlert contactAlert--error" data-form-error style="display: none;">
        ❌ <span data-form-error-text>Une erreur est survenue. Veuillez réessayer.</span>
    </div>

    <form
        class="contactForm"
        id="<?php echo $e($formId); ?>"
        action="<?php echo $e($formAction); ?>"
        method="post"
        data-request-form
        data-min-lead-days="3"
        data-selected-category="<?php echo $e($selectedSectionSlug); ?>">
        <div class="contactGrid contactGrid--reservation">

            <label class="field">
                <span class="field__label">Nom</span>
                <input class="field__input" name="name" autocomplete="name" placeholder="Votre nom" required>
            </label>

            <label class="field">
                <span class="field__label">Email</span>
                <input class="field__input" type="email" name="email" autocomplete="email" placeholder="vous@email.fr" required>
            </label>

            <label class="field">
                <span class="field__label">Téléphone</span>
                <input class="field__input" name="phone" autocomplete="tel" placeholder="+33 …">
            </label>

            <label class="field">
                <span class="field__label">Nombre de personnes</span>
                <input class="field__input" type="number" name="people" min="1" max="500" placeholder="1–200">
            </label>

            <label class="field">
                <span class="field__label">Date de l'évènement</span>
                <input class="field__input" type="date" name="date">
            </label>

            <label class="field">
                <span class="field__label">Lieu</span>
                <input class="field__input" name="location" placeholder="Ville / Salle / Adresse">
            </label>

            <label class="field field--full">
                <span class="field__label">Type d’événement</span>
                <select class="field__input" name="type" required>
                    <option value="" disabled selected>…</option>
                    <option value="mariage">Mariage</option>
                    <option value="entreprise">Entreprise</option>
                    <option value="cocktail">Cocktail</option>
                    <option value="anniversaire">Anniversaire</option>
                    <option value="autre">Autre</option>
                </select>
            </label>

            <label class="field field--full">
                <span class="field__label">Message</span>
                <textarea class="field__input field__textarea" name="message" rows="6"
                    placeholder="Décrivez le format, vos envies, vos contraintes alimentaires…"
                    required></textarea>
            </label>

            <?php require __DIR__ . '/order-selection-widget.php'; ?>

            <section class="field field--full selectionSummary" data-selection-summary>
                <div class="selectionSummary__head">
                    <div class="selectionSummary__heading">
                        <span class="selectionSummary__badge"><?php echo $e($selectionSummaryBadge); ?></span>
                        <div class="selectionSummary__titles">
                            <span class="field__label"><?php echo $e($selectionSummaryTitle); ?></span>
                            <p class="selectionSummary__hint"><?php echo $e($selectionSummaryHint); ?></p>
                        </div>
                    </div>
                    <button type="button" class="selectionSummary__reset" data-reset-selection>
                        Réinitialiser la sélection
                    </button>
                </div>

                <div class="selectionSummary__body">
                    <div class="selectionSummary__stats" data-selection-summary-stats style="display:none"></div>
                    <p class="selectionSummary__empty" data-selection-summary-empty>
                        <?php echo $e($selectionSummaryEmpty); ?>
                    </p>
                    <div class="selectionSummary__groups" data-selection-summary-groups style="display:none"></div>
                    <p class="selectionSummary__meta" data-selection-summary-meta style="display:none"></p>
                </div>
            </section>

        </div>

        <button class="contactCta" type="submit"><?php echo $e($formSubmitLabel); ?></button>
    </form>
</div>