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

    <?= Vite::styles() ?>
</head>

<body class="page--noScroll">
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <?= Vite::scripts() ?>
</body>

</html>