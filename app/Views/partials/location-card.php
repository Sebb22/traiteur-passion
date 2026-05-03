<?php
    $locationCompany = [
    'name'         => 'Traiteur Passion',
    'address_line' => '631 rue de Compiègne',
    'postal_code'  => '60162',
    'city'         => 'Vignemont',
    'zone_label'   => 'Vignemont • Oise',
    'pickup_label' => 'Retrait sur créneau confirmé avec l’équipe',
    ];

    $locationVariant = isset($locationCardVariant) && is_string($locationCardVariant) && $locationCardVariant !== ''
    ? $locationCardVariant
    : 'full';
    $locationTitle = isset($locationCardTitle) && is_string($locationCardTitle) && $locationCardTitle !== ''
    ? $locationCardTitle
    : ($locationVariant === 'compact' ? 'Lieu de retrait' : 'Nous trouver');
    $locationEyebrow = isset($locationCardEyebrow) && is_string($locationCardEyebrow) && $locationCardEyebrow !== ''
    ? $locationCardEyebrow
    : ($locationVariant === 'compact' ? 'Retrait boutique • Vignemont' : 'Traiteur Passion • ancrage local');
    $locationDescription = isset($locationCardDescription) && is_string($locationCardDescription) && $locationCardDescription !== ''
    ? $locationCardDescription
    : ($locationVariant === 'compact'
        ? 'Le retrait est organisé à cette adresse. Nous confirmons avec vous le créneau exact après validation de la demande.'
        : 'Basés à Vignemont, nous organisons les retraits sur rendez-vous et intervenons sur Compiègne, l’Oise et les alentours selon le format de votre réception.');
    $locationClass           = isset($locationCardClass) && is_string($locationCardClass) ? trim($locationCardClass) : '';
    $locationShowFacts       = isset($locationCardShowFacts) ? (bool) $locationCardShowFacts : true;
    $locationShowDescription = isset($locationCardShowDescription) ? (bool) $locationCardShowDescription : true;
    $locationShowMap         = isset($locationCardShowMap) ? (bool) $locationCardShowMap : true;
    $locationAddress         = $locationCompany['address_line'] . ', ' . $locationCompany['postal_code'] . ' ' . $locationCompany['city'];
    $locationMapQuery        = rawurlencode($locationAddress . ', France');
    $locationMapsUrl         = 'https://www.google.com/maps/search/?api=1&query=' . $locationMapQuery;
    $locationEmbedUrl        = 'https://www.google.com/maps?q=' . $locationMapQuery . '&z=15&output=embed';
    $locationRootClass       = trim('locationCard locationCard--' . $locationVariant . ' ' . $locationClass);
?>

<section class="<?php echo htmlspecialchars($locationRootClass, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($locationTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="locationCard__content">
        <span class="locationCard__eyebrow"><?php echo htmlspecialchars($locationEyebrow, ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="locationCard__intro">
            <h3 class="locationCard__title"><?php echo htmlspecialchars($locationTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="locationCard__primary"><?php echo htmlspecialchars($locationAddress, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php if ($locationShowDescription): ?>
        <p class="locationCard__text"><?php echo htmlspecialchars($locationDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($locationShowFacts): ?>
        <dl class="locationCard__facts" aria-label="Informations de localisation">
            <div class="locationCard__fact">
                <dt>Adresse</dt>
                <dd><?php echo htmlspecialchars($locationAddress, ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <div class="locationCard__fact">
                <dt>Zone</dt>
                <dd><?php echo htmlspecialchars($locationCompany['zone_label'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <div class="locationCard__fact">
                <dt>Retrait</dt>
                <dd><?php echo htmlspecialchars($locationCompany['pickup_label'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
        </dl>
        <?php endif; ?>

        <div class="locationCard__actions">
            <a class="btn btn--ghost locationCard__action" href="<?php echo htmlspecialchars($locationMapsUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                Voir l’itinéraire
            </a>
        </div>
    </div>

    <?php if ($locationShowMap): ?>
    <div class="locationCard__map" data-location-card>
        <div class="locationCard__mapPreview" data-location-map-preview>
            <div class="locationCard__mapScene" aria-hidden="true">
                <span class="locationCard__mapGlow"></span>
                <span class="locationCard__mapRoute"></span>
                <span class="locationCard__mapPin"></span>
            </div>
            <div class="locationCard__mapOverlay">
                <span class="locationCard__mapBadge">Carte externe • Google Maps</span>
                <strong class="locationCard__mapTitle">Afficher le plan d’accès</strong>
                <p class="locationCard__mapCaption">Chargement à la demande pour garder une lecture plus propre avant ouverture.</p>
                <button class="btn btn--ghost locationCard__mapButton" type="button" data-location-map-load>
                    Ouvrir la carte
                </button>
            </div>
        </div>
        <iframe
            data-location-map-frame
            data-src="<?php echo htmlspecialchars($locationEmbedUrl, ENT_QUOTES, 'UTF-8'); ?>"
            title="Carte de localisation Traiteur Passion"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
            hidden>
        </iframe>
        <noscript>
            <iframe
                src="<?php echo htmlspecialchars($locationEmbedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                title="Carte de localisation Traiteur Passion"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen>
            </iframe>
        </noscript>
    </div>
    <?php endif; ?>
</section>