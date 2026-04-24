<?php
    use App\Core\Navigation;
    use App\Core\Vite;
    use App\Services\ShopPromoService;

    $defaultTitle = 'Traiteur Passion – Traiteur événementiel à Compiègne';
    $pageTitle    = isset($title) && is_string($title) && $title !== '' ? $title : $defaultTitle;

    $currentPath       = Navigation::getCurrentPath();
    $resolvedBodyClass = isset($bodyClass) && is_string($bodyClass) && $bodyClass !== ''
    ? $bodyClass
    : Navigation::getBodyClass($currentPath);
    $resolvedMetaDescription = isset($metaDescription) && is_string($metaDescription) && $metaDescription !== ''
    ? $metaDescription
    : Navigation::getMetaDescription($currentPath);
    $canonicalUrl = Navigation::getCanonicalUrl($currentPath);
    $metaRobots   = isset($metaRobots) && is_string($metaRobots) && $metaRobots !== '' ? $metaRobots : null;
    $breadcrumbs  = ! empty($disableStructuredBreadcrumbs)
    ? []
    : Navigation::getBreadcrumbs($currentPath, $pageTitle);
    $shopPromoBanner = null;

    if (strpos($currentPath, '/admin') !== 0) {
    try {
        $shopPromoBanner = (new ShopPromoService())->getPublicPromo();
    } catch (\Throwable $e) {
        $shopPromoBanner = null;
    }
    }
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars((string) $resolvedMetaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($metaRobots !== null): ?>
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobots, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo htmlspecialchars((string) $canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FoodEstablishment",
        "name": "Traiteur Passion",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Compiègne",
            "addressCountry": "FR"
        },
        "servesCuisine": "Cuisine de saison",
        "url": "https://www.traiteur-passion.fr",
        "areaServed": "Compiègne et alentours"
    }
    </script>
    <?php if (count($breadcrumbs) > 1): ?>
    <script type="application/ld+json">
    <?php
        echo json_encode(
            [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    ?>
    </script>
    <?php endif; ?>
    <link rel="icon" type="image/png" href="/uploads/images/logos/logo.png">
    <link rel="apple-touch-icon" href="/uploads/images/logos/logo-mobile.png">
    <meta name="theme-color" content="#000000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Forum&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <?php echo Vite::styles() ?>
</head>

<body class="<?php echo htmlspecialchars($resolvedBodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (is_array($shopPromoBanner)): ?>
    <div class="sitePromoSticky" data-countdown-root>
        <div class="sitePromoSticky__inner">
            <button class="sitePromoSticky__handle" type="button" aria-label="Masquer la promotion" tabindex="0"></button>
            <div class="sitePromoSticky__content">
                <span class="sitePromoSticky__eyebrow"><?php echo htmlspecialchars((string) ($shopPromoBanner['title'] ?? 'Offre boutique'), ENT_QUOTES, 'UTF-8'); ?></span>
                <p class="sitePromoSticky__text">
                    <?php echo htmlspecialchars((string) ($shopPromoBanner['banner_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    <strong>Code <?php echo htmlspecialchars((string) ($shopPromoBanner['promo_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="sitePromoSticky__countdown" data-countdown-target="<?php echo htmlspecialchars((string) ($shopPromoBanner['countdown_iso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Fin dans --</span>
                </p>
            </div>
            <?php if (strpos($currentPath, '/boutique-en-ligne') === false): ?>
            <a class="sitePromoSticky__link" href="/boutique-en-ligne"><?php echo htmlspecialchars((string) ($shopPromoBanner['cta_label'] ?? 'Voir la boutique'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>
        <!-- Onglet promo déplacé en dehors de la bannière pour rester visible -->
    </div>
    <button class="sitePromoSticky__tab" type="button" aria-label="Afficher la promotion" tabindex="0" style="display:none"><span>Promo</span></button>
    </div>
    <?php endif; ?>
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>


    <?php echo $content ?>


    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <?php echo Vite::scripts() ?>
</body>

</html>
