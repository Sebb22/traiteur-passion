<?php
    /** @var list<array{id: int, slug: string, name: string, description: string, items: list<array>}> $sections */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $formId                 = trim((string) ($formId ?? 'requestForm'));
    $formAction             = trim((string) ($formAction ?? '/contact'));
    $formSubmitLabel        = trim((string) ($formSubmitLabel ?? 'Envoyer la demande'));
    $selectedSectionSlug    = trim((string) ($selectedSectionSlug ?? ''));
    $limitToSelectedSection = (bool) ($limitToSelectedSection ?? false);
    $accordionTitle         = trim((string) ($accordionTitle ?? 'Sélectionner des items de la carte évènementielle (optionnel)'));
    $accordionSummary       = trim((string) ($accordionSummary ?? 'Choisir dans la carte évènementielle'));
    $accordionContext       = trim((string) ($accordionContext ?? 'Cette sélection sert à préparer votre devis. Notre équipe vous recontacte systématiquement pour confirmer le format, la livraison et les derniers détails.'));
    $selectionSummaryTitle  = trim((string) ($selectionSummaryTitle ?? 'Récapitulatif de votre sélection'));
    $selectionSummaryEmpty  = trim((string) ($selectionSummaryEmpty ?? 'Aucun item sélectionné pour le moment.'));
    $selectionSummaryHint   = trim((string) ($selectionSummaryHint ?? 'Ajoutez librement d’autres items ou catégories avant l’envoi de votre demande.'));
    $selectionSummaryBadge  = trim((string) ($selectionSummaryBadge ?? 'Devis en préparation'));
    $formProtection         = isset($formProtection) && is_array($formProtection) ? $formProtection : [];
    $formTokenField         = (string) ($formProtection['token_field'] ?? '__form_token');
    $formToken              = (string) ($formProtection['token'] ?? '');
    $formIssuedField        = (string) ($formProtection['issued_field'] ?? '__form_issued_at');
    $formIssuedAt           = (string) ($formProtection['issued_at'] ?? '');
    $honeypotField          = (string) ($formProtection['honeypot_field'] ?? '__website');
?>

<div class="contactCard">
    <div class="contactAlert contactAlert--success" data-form-success style="display: none;">
        ✅ <span data-form-success-text>Votre demande a été envoyée avec succès ! Nous vous recontacterons
            rapidement.</span>
    </div>

    <div class="contactAlert contactAlert--error" data-form-error style="display: none;">
        ❌ <span data-form-error-text>Une erreur est survenue. Veuillez réessayer.</span>
    </div>

    <form class="contactForm" id="<?php echo $e($formId); ?>" action="<?php echo $e($formAction); ?>" method="post"
        data-request-form data-min-lead-days="3" data-selected-category="<?php echo $e($selectedSectionSlug); ?>">
        <input type="hidden" name="<?php echo $e($formTokenField); ?>" value="<?php echo $e($formToken); ?>">
        <input type="hidden" name="<?php echo $e($formIssuedField); ?>" value="<?php echo $e($formIssuedAt); ?>">
        <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
            <label>
                Ne pas remplir
                <input type="text" name="<?php echo $e($honeypotField); ?>" value="" tabindex="-1" autocomplete="off">
            </label>
        </div>
        <p class="contactForm__legend">
            <span class="contactForm__requiredMark" aria-hidden="true">*</span>
            Champs obligatoires pour préparer votre demande
        </p>
        <div class="contactGrid contactGrid--reservation">

            <label class="field field--required">
                <span class="field__label">Nom <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" name="name" autocomplete="name" placeholder="Votre nom" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Email <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" type="email" name="email" autocomplete="email" placeholder="vous@email.fr"
                    required>
            </label>

            <label class="field field--required">
                <span class="field__label">Téléphone <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" name="phone" autocomplete="tel" placeholder="+33 …" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Nombre de personnes <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" type="number" name="people" min="1" max="500" placeholder="1–200" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Date de l'évènement <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" type="date" name="date" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Lieu <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" name="location" placeholder="Ville / Salle / Adresse" required>
            </label>

            <label class="field field--full field--required">
                <span class="field__label">Adresse de facturation <span class="field__required"
                        aria-hidden="true">*</span></span>
                <input class="field__input" name="customer_address" autocomplete="street-address"
                    placeholder="Numéro et rue" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Code postal <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" name="customer_postal_code" inputmode="numeric" pattern="[0-9]{5}"
                    autocomplete="postal-code" placeholder="60200" required>
            </label>

            <label class="field field--required">
                <span class="field__label">Commune <span class="field__required" aria-hidden="true">*</span></span>
                <input class="field__input" name="customer_city" autocomplete="address-level2" placeholder="Compiègne"
                    required>
            </label>

            <label class="field field--full field--required">
                <span class="field__label">Type d’événement <span class="field__required"
                        aria-hidden="true">*</span></span>
                <select class="field__input" name="type" required>
                    <option value="" disabled selected>…</option>
                    <option value="mariage">Mariage</option>
                    <option value="entreprise">Entreprise</option>
                    <option value="cocktail">Cocktail</option>
                    <option value="anniversaire">Anniversaire</option>
                    <option value="autre">Autre</option>
                </select>
            </label>

            <label class="field field--full field--required">
                <span class="field__label">Message <span class="field__required" aria-hidden="true">*</span></span>
                <textarea class="field__input field__textarea" name="message" rows="6"
                    placeholder="Décrivez le format, vos envies, vos contraintes alimentaires…" required></textarea>
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