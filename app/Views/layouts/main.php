<?php use App\Core\Vite; ?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Traiteur Passion', ENT_QUOTES, 'UTF-8') ?></title>

    <?= Vite::styles() ?>
</head>

<body>
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <?= Vite::scripts() ?>
</body>

</html>