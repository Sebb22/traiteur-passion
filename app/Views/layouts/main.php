<?php
use App\Core\Vite;
use App\Core\Navigation;

$defaultTitle = 'Traiteur Passion – Traiteur événementiel à Compiègne';
$pageTitle = isset($title) && is_string($title) && $title !== '' ? $title : $defaultTitle;

$currentPath = Navigation::getCurrentPath();
$resolvedBodyClass = isset($bodyClass) && is_string($bodyClass) && $bodyClass !== ''
    ? $bodyClass
    : Navigation::getBodyClass($currentPath);
$resolvedMetaDescription = isset($metaDescription) && is_string($metaDescription) && $metaDescription !== ''
    ? $metaDescription
    : Navigation::getMetaDescription($currentPath);
$canonicalUrl = Navigation::getCanonicalUrl($currentPath);
$metaRobots = isset($metaRobots) && is_string($metaRobots) && $metaRobots !== '' ? $metaRobots : null;
$breadcrumbs = ! empty($disableStructuredBreadcrumbs)
    ? []
    : Navigation::getBreadcrumbs($currentPath, $pageTitle);
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
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
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
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>


    <?php echo $content ?>


    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <?php echo Vite::scripts() ?>
</body>

</html>
