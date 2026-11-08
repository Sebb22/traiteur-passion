<?php
    $adminNavigationPath  = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH);
    $adminNavigationPath  = is_string($adminNavigationPath) ? $adminNavigationPath : '/admin';
    $adminContactsView    = trim((string) ($_GET['view'] ?? 'contacts'));
    $adminNavigationItems = [
    ['href' => '/admin', 'label' => 'Tableau de bord', 'active' => $adminNavigationPath === '/admin' || $adminNavigationPath === '/admin/dashboard'],
    ['href' => '/admin/contacts?view=contacts', 'label' => 'Demandes', 'active' => (strpos($adminNavigationPath, '/admin/contacts') === 0 && $adminContactsView !== 'orders') || strpos($adminNavigationPath, '/admin/clients') === 0],
    ['href' => '/admin/contacts?view=orders', 'label' => 'Commandes', 'active' => (strpos($adminNavigationPath, '/admin/contacts') === 0 && $adminContactsView === 'orders') || strpos($adminNavigationPath, '/admin/boutique/orders') === 0],
    ['href' => '/admin/catalog', 'label' => 'Carte', 'active' => strpos($adminNavigationPath, '/admin/catalog') === 0],
    ['href' => '/admin/boutique', 'label' => 'Boutique', 'active' => $adminNavigationPath === '/admin/boutique'],
    ['href' => '/admin/blog', 'label' => 'Blog', 'active' => strpos($adminNavigationPath, '/admin/blog') === 0],
    ['href' => '/admin/campagnes', 'label' => 'Communication · Campagnes événementielles', 'active' => strpos($adminNavigationPath, '/admin/campagnes') === 0],
    ['href' => '/admin/ambiances-saisonnieres', 'label' => 'Ambiances saisonnières', 'active' => strpos($adminNavigationPath, '/admin/ambiances-saisonnieres') === 0],
    ];
?>
<nav class="adminToolNav" aria-label="Navigation administration">
    <?php foreach ($adminNavigationItems as $item): ?>
    <a href="<?php echo htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8'); ?>"
        class="adminToolNav__link<?php echo ! empty($item['active']) ? ' adminToolNav__link--active' : ''; ?>"
        <?php echo ! empty($item['active']) ? 'aria-current="page"' : ''; ?>>
        <?php echo htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <?php endforeach; ?>
</nav>
