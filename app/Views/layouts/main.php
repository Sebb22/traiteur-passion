<?php use App\Core\Vite; ?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traiteur Passion – Traiteur événementiel à Compiègne</title>
    <meta name="description"
        content="Traiteur à Compiègne spécialisé en mariages, réceptions privées et événements d’entreprise. Cuisine de saison, prestation sur mesure.">

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Forum&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <?php echo Vite::styles() ?>
</head>

<body class="page--noScroll">
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>


    <?php echo $content ?>


    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <?php echo Vite::scripts() ?>
</body>

</html>