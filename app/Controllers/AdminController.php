<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\HttpError;
use App\Core\View;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\Menu;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Services\ContactNotificationService;
use App\Services\MenuImageService;
use App\Services\ShopOrderNotificationService;
use App\Services\ShopPromoService;

final class AdminController
{

    // Gestion des options d'achat (lots) pour la boutique
    public function createShopItemOption(string $itemId): void
    {
        AdminAuth::requireAuth();
        $itemIdInt = (int) $itemId;
        try {
            $shopModel = new Shop();
            $newId     = $shopModel->createItemOption($itemIdInt, $_POST);
            $this->pushFlash('success', 'Option d\'achat créée.');
            $this->redirectShop('#item-' . $itemIdInt);
        } catch (\Throwable $e) {
            error_log('Shop item option create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer l\'option d\'achat.');
            $this->redirectShop('#item-' . $itemIdInt);
        }
    }

    public function reorderShopItemOptions(string $itemId): void
    {
        AdminAuth::requireAuth();

        $itemIdInt = (int) $itemId;
        $optionIds = $_POST['option_ids'] ?? [];
        if (is_string($optionIds)) {
            $optionIds = array_filter(array_map('trim', explode(',', $optionIds)));
        }

        if (! is_array($optionIds) || $optionIds === []) {
            $this->pushFlash('error', 'Ordre des options boutique invalide.');
            $this->redirectShop('#item-' . $itemIdInt);
        }

        try {
            (new Shop())->reorderItemOptions($itemIdInt, array_map('intval', $optionIds));
            $this->pushFlash('success', 'Ordre des options boutique mis à jour.');
        } catch (\Throwable $e) {
            error_log('Shop item option reorder error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de réordonner les options boutique.');
        }

        $this->redirectShop('#item-' . $itemIdInt);
    }

    public function updateShopItemOption(string $optionId): void
    {
        AdminAuth::requireAuth();
        $optionIdInt = (int) $optionId;
        try {
            $shopModel = new Shop();
            $shopModel->updateItemOption($optionIdInt, $_POST);
            $this->pushFlash('success', 'Option d\'achat mise à jour.');
        } catch (\Throwable $e) {
            error_log('Shop item option update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour l\'option d\'achat.');
        }
        $this->redirectShop('#option-' . $optionIdInt);
    }

    public function deleteShopItemOption(string $optionId): void
    {
        AdminAuth::requireAuth();
        $optionIdInt = (int) $optionId;
        $anchor      = '';

        try {
            $shopModel = new Shop();
            $option    = $shopModel->getItemOptionById($optionIdInt);
            $itemId    = is_array($option) ? (int) ($option['item_id'] ?? 0) : 0;
            $shopModel->deleteItemOption($optionIdInt);
            $this->pushFlash('success', 'Option d\'achat supprimée.');
            $anchor = $itemId > 0 ? '#item-' . $itemId : '';
        } catch (\Throwable $e) {
            error_log('Shop item option delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer l\'option d\'achat.');
        }

        $this->redirectShop($anchor);
    }
    public function dashboard(): void
    {
        AdminAuth::requireAuth();

        $searchQuery    = trim((string) ($_GET['q'] ?? ''));
        $searchScope    = $this->sanitizeDashboardSearchScope((string) ($_GET['scope'] ?? 'all'));
        $dashboardQuery = $searchQuery !== '' ? ['q' => $searchQuery, 'status' => ''] : ['status' => '', 'q' => ''];
        $contactModel   = new Contact();
        $blogModel      = new Blog();
        $menuModel      = new Menu();
        $contactStats   = $contactModel->getAdminSummary();
        $blogStats      = $blogModel->getAdminSummary();
        $catalogStats   = $menuModel->getAdminSummary();
        $recentContacts = $searchQuery !== ''
            ? $contactModel->getFiltered($dashboardQuery, 8)
            : $contactModel->getRecentWithMenuFlag(8);
        $contactResultsCount = $searchQuery !== ''
            ? $contactModel->countFiltered($dashboardQuery)
            : count($recentContacts);
        $typeBreakdown = $contactModel->getTypeBreakdown(6);
        $orderStats    = [
            'total'           => 0,
            'new_count'       => 0,
            'confirmed_count' => 0,
            'preparing_count' => 0,
            'ready_count'     => 0,
            'completed_count' => 0,
            'cancelled_count' => 0,
        ];
        $recentOrders       = [];
        $orderResultsCount  = 0;
        $clientResults      = [];
        $clientResultsCount = 0;
        $shopStats          = [
            'total_sections'  => 0,
            'active_sections' => 0,
            'total_items'     => 0,
            'active_items'    => 0,
            'sold_out_items'  => 0,
            'low_stock_items' => 0,
        ];
        $shopPromo     = (new ShopPromoService())->getAdminPromo();
        $shopLoadError = null;

        try {
            $shopModel         = new Shop();
            $orderModel        = new ShopOrder();
            $shopStats         = $shopModel->getAdminSummary();
            $orderStats        = $orderModel->getAdminSummary();
            $recentOrders      = $orderModel->getRecentOrders($searchQuery !== '' ? 8 : 5, $searchQuery !== '' ? $searchQuery : null);
            $orderResultsCount = $searchQuery !== ''
                ? $orderModel->countFilteredOrders($searchQuery)
                : count($recentOrders);
        } catch (\Throwable $e) {
            error_log('Dashboard shop load error: ' . $e->getMessage());
            $shopLoadError = 'Les donnees boutique sont temporairement indisponibles.';
        }

        if ($searchQuery !== '') {
            $clientResults      = $this->buildClientSearchResults($recentContacts, $recentOrders);
            $clientResultsCount = count($clientResults);
        }

        $showContactsResults = $searchQuery === '' || in_array($searchScope, ['all', 'contacts'], true);
        $showOrderResults    = $searchQuery === '' || in_array($searchScope, ['all', 'orders'], true);
        $showClientResults   = $searchQuery !== '' && in_array($searchScope, ['all', 'clients'], true);

        $visibleSearchResults = 0;
        if ($showContactsResults) {
            $visibleSearchResults += $contactResultsCount;
        }
        if ($showOrderResults) {
            $visibleSearchResults += $orderResultsCount;
        }
        if ($showClientResults) {
            $visibleSearchResults += $clientResultsCount;
        }

        View::render('admin/dashboard', [
            'title'               => 'Administration — Dashboard',
            'contactStats'        => $contactStats,
            'blogStats'           => $blogStats,
            'catalogStats'        => $catalogStats,
            'recentContacts'      => $recentContacts,
            'contactResultsCount' => $contactResultsCount,
            'clientResults'       => $clientResults,
            'clientResultsCount'  => $clientResultsCount,
            'typeBreakdown'       => $typeBreakdown,
            'orderStats'          => $orderStats,
            'orderStatusLabels'   => ShopOrder::STATUS_LABELS,
            'recentOrders'        => $recentOrders,
            'orderResultsCount'   => $orderResultsCount,
            'shopStats'           => $shopStats,
            'shopPromo'           => $shopPromo,
            'shopLoadError'       => $shopLoadError,
            'dashboardSearch'     => [
                'query'         => $searchQuery,
                'active'        => $searchQuery !== '',
                'scope'         => $searchScope,
                'total_results' => $visibleSearchResults,
                'show_contacts' => $showContactsResults,
                'show_orders'   => $showOrderResults,
                'show_clients'  => $showClientResults,
            ],
            'flash'               => $this->pullFlash(),
        ]);
    }

    public function updateDashboardShopPromo(): void
    {
        AdminAuth::requireAuth();

        try {
            (new ShopPromoService())->saveFromInput($_POST);
            $this->pushFlash('success', 'Promotion boutique mise à jour.');
        } catch (\Throwable $e) {
            error_log('Shop promo update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour la promotion boutique.');
        }

        header('Location: /admin/dashboard#shop-promo');
        exit;
    }

    public function blog(): void
    {
        AdminAuth::requireAuth();

        $blogModel = new Blog();

        View::render('admin/blog', [
            'title' => 'Administration — Gérer le blog',
            'posts' => $blogModel->getAllForAdmin(),
            'stats' => $blogModel->getAdminSummary(),
            'flash' => $this->pullFlash(),
        ]);
    }

    public function createBlogPost(): void
    {
        AdminAuth::requireAuth();

        try {
            $payload = $_POST;
            $this->handleBlogMediaUploads($payload);
            $slug = (new Blog())->createPost($payload);
            $this->pushFlash('success', 'Article créé.');
            $this->redirectBlog('#post-' . $slug);
        } catch (\Throwable $e) {
            error_log('Blog create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer l’article.');
            $this->redirectBlog();
        }
    }

    public function updateBlogPost(string $slug): void
    {
        AdminAuth::requireAuth();

        try {
            $blogModel = new Blog();
            $existing  = $blogModel->getPostBySlugForAdmin($slug);
            if (! is_array($existing)) {
                throw new \RuntimeException('Article introuvable.');
            }

            $payload = $_POST;
            $this->handleBlogMediaUploads($payload, $existing);
            $newSlug = $blogModel->updatePost($slug, $payload);
            $this->pushFlash('success', 'Article mis à jour.');
            $this->redirectBlog('#post-' . $newSlug);
        } catch (\Throwable $e) {
            error_log('Blog update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour l’article.');
            $this->redirectBlog('#post-' . rawurlencode($slug));
        }
    }

    public function deleteBlogPost(string $slug): void
    {
        AdminAuth::requireAuth();

        try {
            $blogModel = new Blog();
            $existing  = $blogModel->getPostBySlugForAdmin($slug);
            $blogModel->deletePost($slug);
            if (is_array($existing)) {
                $this->cleanupBlogMediaFile((string) ($existing['cover_image'] ?? ''), '/uploads/pages/blog/images/');
                $this->cleanupBlogMediaFile((string) ($existing['video_url'] ?? ''), '/uploads/pages/blog/videos/');
            }
            $this->pushFlash('success', 'Article supprimé.');
        } catch (\Throwable $e) {
            error_log('Blog delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer l’article.');
        }

        $this->redirectBlog();
    }

    public function catalog(): void
    {
        AdminAuth::requireAuth();

        $menuModel = new Menu();
        $sections  = $menuModel->getCatalogForAdmin();

        $itemsCount   = 0;
        $optionsCount = 0;
        foreach ($sections as $section) {
            $itemsCount   += count($section['items'] ?? []);
            $optionsCount += (int) ($section['count_options'] ?? 0);
        }

        View::render('admin/catalog', [
            'title'        => 'Administration — Editer la carte',
            'sections'     => $sections,
            'stats'        => [
                'sections' => count($sections),
                'items'    => $itemsCount,
                'options'  => $optionsCount,
            ],
            'imageRuntime' => (new MenuImageService())->getRuntimeStatus(),
            'flash'        => $this->pullFlash(),
        ]);
    }

    public function shop(): void
    {
        AdminAuth::requireAuth();

        $sections = [];
        $stats    = [
            'total_sections'  => 0,
            'active_sections' => 0,
            'total_items'     => 0,
            'active_items'    => 0,
            'sold_out_items'  => 0,
            'low_stock_items' => 0,
        ];
        $lowStockItems = [];
        $loadError     = null;

        try {
            $shopModel     = new Shop();
            $sections      = $shopModel->getCatalogForAdmin();
            $stats         = $shopModel->getAdminSummary();
            $lowStockItems = $shopModel->getLowStockItems(10);
        } catch (\Throwable $e) {
            error_log('Shop admin load error: ' . $e->getMessage());
            $loadError = 'Les tables de boutique ne sont pas encore disponibles. Lancez la migration SQL avant d’utiliser cet écran.';
        }

        View::render('admin/shop', [
            'title'         => 'Administration — Boutique en ligne',
            'sections'      => $sections,
            'stats'         => $stats,
            'lowStockItems' => $lowStockItems,
            'imageRuntime'  => (new MenuImageService(null, 'shop'))->getRuntimeStatus(),
            'flash'         => $this->pullFlash(),
            'loadError'     => $loadError,
        ]);
    }

    public function updateShopSection(string $id): void
    {
        AdminAuth::requireAuth();

        try {
            (new Shop())->updateSection((int) $id, $_POST);
            $this->pushFlash('success', 'Section boutique mise à jour.');
        } catch (\Throwable $e) {
            error_log('Shop section update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour la section boutique.');
        }

        $this->redirectShop('#section-' . (int) $id);
    }

    public function createShopSection(): void
    {
        AdminAuth::requireAuth();

        try {
            $newId = (new Shop())->createSection($_POST);
            $this->pushFlash('success', 'Section boutique créée.');
            $this->redirectShop('#section-' . $newId);
        } catch (\Throwable $e) {
            error_log('Shop section create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer la section boutique.');
            $this->redirectShop();
        }
    }

    public function deleteShopSection(string $id): void
    {
        AdminAuth::requireAuth();

        $sectionIdInt = (int) $id;
        $shopModel    = new Shop();
        $imageService = new MenuImageService(null, 'shop');

        try {
            $imagePaths = $shopModel->getSectionItemImagePaths($sectionIdInt);
        } catch (\Throwable $e) {
            $imagePaths = [];
        }

        try {
            $shopModel->deleteSection($sectionIdInt);
            foreach ($imagePaths as $imagePath) {
                $imageService->cleanupFromDesktopPath($imagePath);
            }
            $this->pushFlash('success', 'Section boutique supprimée.');
        } catch (\Throwable $e) {
            error_log('Shop section delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer la section boutique.');
        }

        $this->redirectShop();
    }

    public function reorderShopSections(): void
    {
        AdminAuth::requireAuth();

        $sectionIds = $_POST['section_ids'] ?? [];
        if (is_string($sectionIds)) {
            $sectionIds = array_filter(array_map('trim', explode(',', $sectionIds)));
        }

        if (! is_array($sectionIds) || $sectionIds === []) {
            $this->pushFlash('error', 'Ordre des sections boutique invalide.');
            $this->redirectShop();
        }

        try {
            (new Shop())->reorderSections(array_map('intval', $sectionIds));
            $this->pushFlash('success', 'Ordre des sections boutique mis à jour.');
        } catch (\Throwable $e) {
            error_log('Shop section reorder error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de réordonner les sections boutique.');
        }

        $this->redirectShop();
    }

    public function createShopItem(string $sectionId): void
    {
        AdminAuth::requireAuth();

        $sectionIdInt = (int) $sectionId;
        try {
            $shopModel = new Shop();
            $newId     = $shopModel->createItem($sectionIdInt, $_POST);
            $this->handleShopItemImageUpload($shopModel, $newId);
            $this->pushFlash('success', 'Produit boutique créé.');
            $this->redirectShop('#item-' . $newId);
        } catch (\Throwable $e) {
            error_log('Shop item create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer le produit boutique.');
            $this->redirectShop('#section-' . $sectionIdInt);
        }
    }

    public function updateShopItem(string $id): void
    {
        AdminAuth::requireAuth();

        $itemId = (int) $id;
        try {
            $shopModel = new Shop();
            $shopModel->updateItem($itemId, $_POST);
            $this->handleShopItemImageUpload($shopModel, $itemId);
            $this->pushFlash('success', 'Produit boutique mis à jour.');
        } catch (\Throwable $e) {
            error_log('Shop item update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour le produit boutique.');
        }

        $this->redirectShop('#item-' . $itemId);
    }

    public function deleteShopItem(string $id): void
    {
        AdminAuth::requireAuth();

        $itemId       = (int) $id;
        $sectionId    = (int) ($_POST['section_id'] ?? 0);
        $shopModel    = new Shop();
        $imageService = new MenuImageService(null, 'shop');

        try {
            $existingItem = $shopModel->getItemById($itemId);
        } catch (\Throwable $e) {
            $existingItem = null;
        }

        try {
            $shopModel->deleteItem($itemId);
            if (is_array($existingItem)) {
                $imageService->cleanupFromDesktopPath((string) ($existingItem['image_path'] ?? ''));
            }
            $this->pushFlash('success', 'Produit boutique supprimé.');
        } catch (\Throwable $e) {
            error_log('Shop item delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer le produit boutique.');
        }

        $anchor = $sectionId > 0 ? '#section-' . $sectionId : '';
        $this->redirectShop($anchor);
    }

    public function reorderShopItems(string $sectionId): void
    {
        AdminAuth::requireAuth();

        $itemIds = $_POST['item_ids'] ?? [];
        if (is_string($itemIds)) {
            $itemIds = array_filter(array_map('trim', explode(',', $itemIds)));
        }

        if (! is_array($itemIds) || $itemIds === []) {
            $this->pushFlash('error', 'Ordre des produits boutique invalide.');
            $this->redirectShop('#section-' . (int) $sectionId);
        }

        try {
            (new Shop())->reorderItems((int) $sectionId, array_map('intval', $itemIds));
            $this->pushFlash('success', 'Ordre des produits boutique mis à jour.');
        } catch (\Throwable $e) {
            error_log('Shop item reorder error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de réordonner les produits boutique.');
        }

        $this->redirectShop('#section-' . (int) $sectionId);
    }

    public function updateShopOrderStatus(string $id): void
    {
        AdminAuth::requireAuth();

        $orderId  = (int) $id;
        $status   = trim((string) ($_POST['status'] ?? ''));
        $redirect = $this->safeAdminRedirect((string) ($_POST['redirect'] ?? '/admin/boutique#orders'));

        $orderModel = new ShopOrder();
        $order      = $orderModel->getByIdWithItems($orderId);

        if (! is_array($order)) {
            $this->pushFlash('error', 'Commande boutique introuvable.');
            header('Location: /admin/boutique#orders');
            exit;
        }

        $previousStatus = (string) ($order['status'] ?? 'new');
        if (! $orderModel->updateStatus($orderId, $status)) {
            $this->pushFlash('error', 'Impossible de mettre à jour ce statut de commande boutique.');
            header('Location: ' . $redirect);
            exit;
        }

        $notifyClient = $this->shouldNotifyClient($_POST);
        $mailResult   = null;

        if ($notifyClient) {
            $updatedOrder = $orderModel->getByIdWithItems($orderId);
            if (is_array($updatedOrder)) {
                $mailResult = (new ShopOrderNotificationService())->dispatchStatusUpdate(
                    $orderId,
                    $updatedOrder,
                    $previousStatus,
                    $status,
                    $this->statusUpdateMessageFromPost($_POST),
                    $this->statusUpdateSubjectFromPost($_POST),
                );
            } else {
                $mailResult = [
                    'enabled'            => false,
                    'client_status_sent' => false,
                    'errors'             => ['Commande boutique rechargement impossible apres mise a jour.'],
                ];
            }
        }

        $this->pushFlash(
            'success',
            $this->buildStatusFlashMessage('Statut de commande boutique mis a jour.', $notifyClient, $mailResult),
        );

        header('Location: ' . $redirect);
        exit;
    }

    public function orderDetail(string $id): void
    {
        AdminAuth::requireAuth();

        $orderModel = new ShopOrder();
        $order      = $orderModel->getByIdWithItems((int) $id);

        if (! is_array($order)) {
            HttpError::notFound([
                'title'           => '404 — Commande introuvable',
                'eyebrow'         => 'Commande introuvable',
                'headline'        => 'Cette commande boutique est introuvable.',
                'message'         => 'La commande demandée n’existe pas ou n’est plus accessible depuis l’administration.',
                'primaryAction'   => [
                    'href'  => '/admin/boutique#orders',
                    'label' => 'Retour aux commandes',
                ],
                'secondaryAction' => [
                    'href'  => '/admin/contacts#orders',
                    'label' => 'Voir les demandes',
                ],
                'hints'           => [
                    'Vérifiez l’identifiant dans l’URL.',
                    'Revenez à la liste des commandes boutique pour relancer la recherche.',
                    'Si la commande a été supprimée ou annulée, son lien peut ne plus être valide.',
                ],
            ]);
            return;
        }

        View::render('admin/order-detail', [
            'title'         => 'Administration — Détail commande boutique',
            'order'         => $order,
            'statusOptions' => ShopOrder::STATUS_LABELS,
            'flash'         => $this->pullFlash(),
        ]);
    }

    public function clientDetail(): void
    {
        AdminAuth::requireAuth();

        $email = trim((string) ($_GET['email'] ?? ''));
        $phone = trim((string) ($_GET['phone'] ?? ''));
        $view  = $this->sanitizeClientDetailView((string) ($_GET['view'] ?? 'all'));

        $contactModel = new Contact();
        $orderModel   = new ShopOrder();
        $contacts     = $contactModel->getByIdentity($email, $phone, 50);
        $orders       = $orderModel->getByIdentity($email, $phone, 50);

        if ($email === '' && $phone === '') {
            HttpError::notFound([
                'title'           => '404 — Client introuvable',
                'eyebrow'         => 'Client introuvable',
                'headline'        => 'Aucune identité client fournie.',
                'message'         => 'La fiche client agrégée nécessite au moins un email ou un téléphone pour regrouper les échanges.',
                'primaryAction'   => [
                    'href'  => '/admin',
                    'label' => 'Retour au dashboard',
                ],
                'secondaryAction' => [
                    'href'  => '/admin/contacts',
                    'label' => 'Voir les demandes',
                ],
            ]);
            return;
        }

        if ($contacts === [] && $orders === []) {
            HttpError::notFound([
                'title'           => '404 — Client introuvable',
                'eyebrow'         => 'Client introuvable',
                'headline'        => 'Aucune fiche client agrégée ne correspond à cette identité.',
                'message'         => 'L’email ou le téléphone fourni ne correspond à aucune demande ni commande connue.',
                'primaryAction'   => [
                    'href'  => '/admin',
                    'label' => 'Retour au dashboard',
                ],
                'secondaryAction' => [
                    'href'  => '/admin/contacts',
                    'label' => 'Voir les demandes',
                ],
            ]);
            return;
        }

        $clientProfile = $this->buildClientProfile($email, $phone, $contacts, $orders);
        $timeline      = $this->buildClientTimeline($contacts, $orders);
        $timeline      = $this->filterClientTimeline($timeline, $view);

        View::render('admin/client-detail', [
            'title'         => 'Administration — Fiche client',
            'client'        => $clientProfile,
            'contacts'      => $contacts,
            'orders'        => $orders,
            'timeline'      => $timeline,
            'clientView'    => [
                'active'        => $view,
                'show_contacts' => in_array($view, ['all', 'contacts'], true),
                'show_orders'   => in_array($view, ['all', 'orders'], true),
                'is_recent'     => $view === 'recent',
            ],
            'orderStatuses' => ShopOrder::STATUS_LABELS,
            'flash'         => $this->pullFlash(),
        ]);
    }

    public function updateCatalogSection(string $id): void
    {
        AdminAuth::requireAuth();

        try {
            (new Menu())->updateSection((int) $id, $_POST);
            $this->pushFlash('success', 'Section mise à jour.');
        } catch (\Throwable $e) {
            error_log('Catalog section update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour la section.');
        }

        $this->redirectCatalog('#section-' . (int) $id);
    }

    public function createCatalogSection(): void
    {
        AdminAuth::requireAuth();

        try {
            $newId = (new Menu())->createSection($_POST);
            $this->pushFlash('success', 'Section créée.');
            $this->redirectCatalog('#section-' . $newId);
        } catch (\Throwable $e) {
            error_log('Catalog section create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer la section.');
            $this->redirectCatalog();
        }
    }

    public function deleteCatalogSection(string $id): void
    {
        AdminAuth::requireAuth();

        try {
            (new Menu())->deleteSection((int) $id);
            $this->pushFlash('success', 'Section supprimée.');
        } catch (\Throwable $e) {
            error_log('Catalog section delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer la section.');
        }

        $this->redirectCatalog();
    }

    public function reorderCatalogSections(): void
    {
        AdminAuth::requireAuth();

        $sectionIds = $_POST['section_ids'] ?? [];
        if (is_string($sectionIds)) {
            $sectionIds = array_filter(array_map('trim', explode(',', $sectionIds)));
        }

        if (! is_array($sectionIds) || $sectionIds === []) {
            $this->pushFlash('error', 'Ordre des sections invalide.');
            $this->redirectCatalog();
        }

        try {
            (new Menu())->reorderSections(array_map('intval', $sectionIds));
            $this->pushFlash('success', 'Ordre des sections mis à jour.');
        } catch (\Throwable $e) {
            error_log('Catalog section reorder error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de réordonner les sections.');
        }

        $this->redirectCatalog();
    }

    public function updateCatalogItem(string $id): void
    {
        AdminAuth::requireAuth();

        $itemId    = (int) $id;
        $menuModel = new Menu();

        try {
            $menuModel->updateItem($itemId, $_POST);
            $this->handleMenuItemImageUpload($menuModel, $itemId);
            $this->pushFlash('success', 'Item mis à jour.');
        } catch (\Throwable $e) {
            error_log('Catalog item update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour l’item.');
        }

        $this->redirectCatalog('#item-' . $itemId);
    }

    public function previewCatalogImage(): void
    {
        AdminAuth::requireAuth();

        $file = $_FILES['image_file'] ?? null;
        if (! is_array($file)) {
            $this->jsonResponse(['ok' => false, 'error' => 'Aucun fichier reçu.'], 400);
        }

        try {
            $removeBackground = isset($_POST['remove_bg']) && (string) $_POST['remove_bg'] === '1';
            $backgroundFuzz   = (int) ($_POST['background_fuzz'] ?? 12);
            $previewWidth     = (int) ($_POST['preview_width'] ?? 320);
            $imageService     = new MenuImageService();
            $previewModel     = trim((string) ($_POST['preview_model'] ?? ''));
            if ($previewModel === '') {
                $runtimeStatus = $imageService->getRuntimeStatus();
                $previewModel  = (string) ($runtimeStatus['preview_model'] ?? 'u2netp');
            }

            $previewResult = $imageService->generatePreviewResult($file, [
                'remove_background' => $removeBackground,
                'background_fuzz'   => $backgroundFuzz,
                'preview_width'     => $previewWidth,
                'preview_model'     => $previewModel,
            ]);

            $this->jsonResponse([
                'ok'               => true,
                'preview_data_uri' => (string) ($previewResult['data_uri'] ?? ''),
                'preview_token'    => (string) ($previewResult['preview_token'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            error_log('Catalog image preview error: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function previewShopImage(): void
    {
        $this->previewCatalogImage();
    }

    public function createCatalogItem(string $sectionId): void
    {
        AdminAuth::requireAuth();

        $menuModel    = new Menu();
        $sectionIdInt = (int) $sectionId;

        try {
            $newId = $menuModel->createItem($sectionIdInt, $_POST);
            $this->handleMenuItemImageUpload($menuModel, $newId);
            $this->pushFlash('success', 'Item créé.');
            $this->redirectCatalog('#item-' . $newId);
        } catch (\Throwable $e) {
            error_log('Catalog item create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer l’item.');
            $this->redirectCatalog('#section-' . $sectionIdInt);
        }
    }

    public function deleteCatalogItem(string $id): void
    {
        AdminAuth::requireAuth();

        $sectionId = (int) ($_POST['section_id'] ?? 0);

        try {
            (new Menu())->deleteItem((int) $id);
            $this->pushFlash('success', 'Item supprimé.');
        } catch (\Throwable $e) {
            error_log('Catalog item delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer l’item.');
        }

        $anchor = $sectionId > 0 ? '#section-' . $sectionId : '';
        $this->redirectCatalog($anchor);
    }

    public function reorderCatalogItems(string $sectionId): void
    {
        AdminAuth::requireAuth();

        $itemIds = $_POST['item_ids'] ?? [];
        if (is_string($itemIds)) {
            $itemIds = array_filter(array_map('trim', explode(',', $itemIds)));
        }

        if (! is_array($itemIds) || $itemIds === []) {
            $this->pushFlash('error', 'Ordre des items invalide.');
            $this->redirectCatalog('#section-' . (int) $sectionId);
        }

        try {
            (new Menu())->reorderItems((int) $sectionId, array_map('intval', $itemIds));
            $this->pushFlash('success', 'Ordre des items mis à jour.');
        } catch (\Throwable $e) {
            error_log('Catalog item reorder error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de réordonner les items.');
        }

        $this->redirectCatalog('#section-' . (int) $sectionId);
    }

    public function updateCatalogOption(string $id): void
    {
        AdminAuth::requireAuth();

        try {
            (new Menu())->updateOption((int) $id, $_POST);
            $this->pushFlash('success', 'Option mise à jour.');
        } catch (\Throwable $e) {
            error_log('Catalog option update error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de mettre à jour l’option.');
        }

        $this->redirectCatalog('#option-' . (int) $id);
    }

    public function createCatalogOption(string $itemId): void
    {
        AdminAuth::requireAuth();

        try {
            $newId = (new Menu())->createOption((int) $itemId, $_POST);
            $this->pushFlash('success', 'Option créée.');
            $this->redirectCatalog('#option-' . $newId);
        } catch (\Throwable $e) {
            error_log('Catalog option create error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de créer l’option.');
            $this->redirectCatalog('#item-' . (int) $itemId);
        }
    }

    public function deleteCatalogOption(string $id): void
    {
        AdminAuth::requireAuth();

        $itemId = (int) ($_POST['item_id'] ?? 0);

        try {
            (new Menu())->deleteOption((int) $id);
            $this->pushFlash('success', 'Option supprimée.');
        } catch (\Throwable $e) {
            error_log('Catalog option delete error: ' . $e->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer l’option.');
        }

        $anchor = $itemId > 0 ? '#item-' . $itemId : '';
        $this->redirectCatalog($anchor);
    }

    /**
     * Display all contact requests
     */
    public function contacts(): void
    {
        AdminAuth::requireAuth();

        $filters = [
            'status' => $this->sanitizeContactStatusFilter((string) ($_GET['status'] ?? '')),
            'q'      => trim((string) ($_GET['q'] ?? '')),
        ];

        $contactModel  = new Contact();
        $contacts      = $contactModel->getFiltered($filters, 100);
        $stats         = $contactModel->getAdminSummary();
        $filteredCount = $contactModel->countFiltered($filters);
        $orderStats    = [
            'total'           => 0,
            'new_count'       => 0,
            'confirmed_count' => 0,
            'preparing_count' => 0,
            'ready_count'     => 0,
            'completed_count' => 0,
            'cancelled_count' => 0,
        ];
        $recentOrders   = [];
        $ordersCount    = 0;
        $orderLoadError = null;

        try {
            $orderModel   = new ShopOrder();
            $orderStats   = $orderModel->getAdminSummary();
            $recentOrders = $orderModel->getRecentOrders(12, $filters['q']);
            $ordersCount  = $orderModel->countFilteredOrders($filters['q']);
        } catch (\Throwable $e) {
            error_log('Admin contacts order load error: ' . $e->getMessage());
            $orderLoadError = 'Les commandes boutique sont temporairement indisponibles sur cet écran.';
        }

        View::render('admin/contacts', [
            'title'              => 'Administration — Demandes, devis et commandes',
            'contacts'           => $contacts,
            'stats'              => $stats,
            'orderStats'         => $orderStats,
            'recentOrders'       => $recentOrders,
            'ordersCount'        => $ordersCount,
            'orderLoadError'     => $orderLoadError,
            'filteredCount'      => $filteredCount,
            'filters'            => $filters,
            'statusOptions'      => Contact::STATUS_LABELS,
            'orderStatusOptions' => ShopOrder::STATUS_LABELS,
            'flash'              => $this->pullFlash(),
        ]);
    }

    public function updateContactStatus(string $id): void
    {
        AdminAuth::requireAuth();

        $contactId     = (int) $id;
        $status        = trim((string) ($_POST['status'] ?? ''));
        $redirect      = $this->safeAdminRedirect((string) ($_POST['redirect'] ?? '/admin/contacts'));
        $contactModel  = new Contact();
        $contactExists = $contactModel->getById($contactId);

        if (! $contactExists) {
            $this->pushFlash('error', 'Demande introuvable.');
            header('Location: /admin/contacts');
            exit;
        }

        $previousStatus = (string) ($contactExists['status'] ?? 'new');
        if (! $contactModel->updateStatus($contactId, $status)) {
            $this->pushFlash('error', 'Impossible de mettre à jour le statut.');
            header('Location: ' . $redirect);
            exit;
        }

        $notifyClient = $this->shouldNotifyClient($_POST);
        $mailResult   = null;

        if ($notifyClient) {
            $updatedContact = $contactModel->getById($contactId);
            if (is_array($updatedContact)) {
                $mailResult = (new ContactNotificationService())->dispatchStatusUpdate(
                    $this->resolveContactRequestKind($updatedContact),
                    $contactId,
                    $updatedContact,
                    is_array($updatedContact['menu_items'] ?? null) ? $updatedContact['menu_items'] : [],
                    $previousStatus,
                    $status,
                    $this->statusUpdateMessageFromPost($_POST),
                    $this->statusUpdateSubjectFromPost($_POST),
                );
            } else {
                $mailResult = [
                    'enabled'            => false,
                    'client_status_sent' => false,
                    'errors'             => ['Dossier rechargement impossible apres mise a jour.'],
                ];
            }
        }

        $this->pushFlash(
            'success',
            $this->buildStatusFlashMessage('Statut mis a jour.', $notifyClient, $mailResult),
        );

        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Display a single contact request with menu items
     */
    public function contactDetail(string $id): void
    {
        AdminAuth::requireAuth();

        $contactModel = new Contact();
        $contact      = $contactModel->getById((int) $id);

        if (! $contact) {
            HttpError::notFound([
                'title'           => '404 — Demande introuvable',
                'eyebrow'         => 'Demande introuvable',
                'headline'        => 'Cette demande client est introuvable.',
                'message'         => 'L\'identifiant demandé ne correspond à aucune fiche disponible. Il est possible que la demande ait été supprimée ou que le lien soit incomplet.',
                'primaryAction'   => [
                    'href'  => '/admin/contacts',
                    'label' => 'Retour aux demandes',
                ],
                'secondaryAction' => [
                    'href'  => '/admin/dashboard',
                    'label' => 'Tableau de bord',
                ],
                'hints'           => [
                    'Revenez à la liste des demandes pour relancer votre recherche.',
                    'Contrôlez l\'identifiant dans l\'URL si vous avez collé un lien.',
                    'Si la demande a été archivée ailleurs, exportez les contacts pour la retrouver.',
                ],
            ]);
            return;
        }

        View::render('admin/contact-detail', [
            'title'         => 'Administration — Détail demande / devis',
            'contact'       => $contact,
            'statusOptions' => Contact::STATUS_LABELS,
            'flash'         => $this->pullFlash(),
        ]);
    }

    /**
     * Export contacts to CSV
     */
    public function exportContacts(): void
    {
        AdminAuth::requireAuth();

        $contactModel = new Contact();
        $contacts     = $contactModel->getAll(1000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contacts_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, [
            'ID',
            'Nom',
            'Email',
            'Téléphone',
            'Personnes',
            'Date événement',
            'Lieu',
            'Type',
            'Message',
            'Statut',
            'Date création',
        ]);

        // Data
        foreach ($contacts as $contact) {
            fputcsv($output, [
                $contact['id'],
                $contact['name'],
                $contact['email'],
                $contact['phone'],
                $contact['people'],
                $contact['date'],
                $contact['location'],
                $contact['type'],
                $contact['message'],
                $contact['status'],
                $contact['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    private function redirectCatalog(string $anchor = ''): void
    {
        header('Location: /admin/catalog' . $anchor);
        exit;
    }

    private function redirectShop(string $anchor = ''): void
    {
        header('Location: /admin/boutique' . $anchor);
        exit;
    }

    private function redirectBlog(string $anchor = ''): void
    {
        header('Location: /admin/blog' . $anchor);
        exit;
    }

    private function pushFlash(string $type, string $message): void
    {
        $_SESSION['admin_flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return is_array($flash) ? $flash : null;
    }

    private function safeAdminRedirect(string $redirect): string
    {
        if ($redirect === '' || strpos($redirect, '/admin') !== 0) {
            return '/admin/contacts';
        }

        return $redirect;
    }

    private function sanitizeContactStatusFilter(string $status): string
    {
        return isset(Contact::STATUS_LABELS[$status]) ? $status : '';
    }

    private function sanitizeDashboardSearchScope(string $scope): string
    {
        return in_array($scope, ['all', 'contacts', 'orders', 'clients'], true) ? $scope : 'all';
    }

    private function sanitizeClientDetailView(string $view): string
    {
        return in_array($view, ['all', 'contacts', 'orders', 'recent'], true) ? $view : 'all';
    }

    /**
     * @param array<int,array<string,mixed>> $contacts
     * @param array<int,array<string,mixed>> $orders
     * @return array<int,array<string,mixed>>
     */
    private function buildClientSearchResults(array $contacts, array $orders): array
    {
        $clients = [];

        foreach ($contacts as $contact) {
            $email      = trim((string) ($contact['email'] ?? ''));
            $phone      = trim((string) ($contact['phone'] ?? ''));
            $clientKey  = $this->normalizeClientSearchKey($email, $phone, 'contact-' . (int) ($contact['id'] ?? 0));
            $createdAt  = trim((string) ($contact['created_at'] ?? ''));
            $clientName = trim((string) ($contact['name'] ?? ''));

            if (! isset($clients[$clientKey])) {
                $clients[$clientKey] = [
                    'name'            => $clientName !== '' ? $clientName : 'Client',
                    'email'           => $email,
                    'phone'           => $phone,
                    'location'        => trim((string) ($contact['location'] ?? '')),
                    'last_activity'   => $createdAt,
                    'contacts_count'  => 0,
                    'orders_count'    => 0,
                    'client_link'     => null,
                    'contact_link'    => null,
                    'order_link'      => null,
                    'order_reference' => '',
                ];
            }

            $clients[$clientKey]['contacts_count']++;
            if ($clientName !== '' && strlen($clientName) > strlen((string) ($clients[$clientKey]['name'] ?? ''))) {
                $clients[$clientKey]['name'] = $clientName;
            }
            if ($email !== '' && (string) ($clients[$clientKey]['email'] ?? '') === '') {
                $clients[$clientKey]['email'] = $email;
            }
            if ($phone !== '' && (string) ($clients[$clientKey]['phone'] ?? '') === '') {
                $clients[$clientKey]['phone'] = $phone;
            }
            if ((string) ($clients[$clientKey]['location'] ?? '') === '') {
                $clients[$clientKey]['location'] = trim((string) ($contact['location'] ?? ''));
            }
            if ($createdAt !== '' && strcmp($createdAt, (string) ($clients[$clientKey]['last_activity'] ?? '')) > 0) {
                $clients[$clientKey]['last_activity'] = $createdAt;
            }
            if ($clients[$clientKey]['contact_link'] === null && (int) ($contact['id'] ?? 0) > 0) {
                $clients[$clientKey]['contact_link'] = '/admin/contacts/' . (int) ($contact['id'] ?? 0);
            }
            if ($clients[$clientKey]['client_link'] === null) {
                $clients[$clientKey]['client_link'] = $this->buildAdminClientDetailUrl(
                    (string) ($clients[$clientKey]['email'] ?? ''),
                    (string) ($clients[$clientKey]['phone'] ?? ''),
                );
            }
        }

        foreach ($orders as $order) {
            $email          = trim((string) ($order['customer_email'] ?? ''));
            $phone          = trim((string) ($order['customer_phone'] ?? ''));
            $clientKey      = $this->normalizeClientSearchKey($email, $phone, 'order-' . (int) ($order['id'] ?? 0));
            $createdAt      = trim((string) ($order['created_at'] ?? ''));
            $clientName     = trim((string) ($order['customer_name'] ?? ''));
            $orderReference = trim((string) ($order['order_reference'] ?? ''));
            $location       = trim(implode(', ', array_filter([
                trim((string) ($order['delivery_city'] ?? '')),
                trim((string) ($order['delivery_postal_code'] ?? '')),
            ])));

            if (! isset($clients[$clientKey])) {
                $clients[$clientKey] = [
                    'name'            => $clientName !== '' ? $clientName : 'Client',
                    'email'           => $email,
                    'phone'           => $phone,
                    'location'        => $location,
                    'last_activity'   => $createdAt,
                    'contacts_count'  => 0,
                    'orders_count'    => 0,
                    'client_link'     => null,
                    'contact_link'    => null,
                    'order_link'      => null,
                    'order_reference' => '',
                ];
            }

            $clients[$clientKey]['orders_count']++;
            if ($clientName !== '' && strlen($clientName) > strlen((string) ($clients[$clientKey]['name'] ?? ''))) {
                $clients[$clientKey]['name'] = $clientName;
            }
            if ($email !== '' && (string) ($clients[$clientKey]['email'] ?? '') === '') {
                $clients[$clientKey]['email'] = $email;
            }
            if ($phone !== '' && (string) ($clients[$clientKey]['phone'] ?? '') === '') {
                $clients[$clientKey]['phone'] = $phone;
            }
            if ((string) ($clients[$clientKey]['location'] ?? '') === '') {
                $clients[$clientKey]['location'] = $location;
            }
            if ($createdAt !== '' && strcmp($createdAt, (string) ($clients[$clientKey]['last_activity'] ?? '')) > 0) {
                $clients[$clientKey]['last_activity'] = $createdAt;
            }
            if ($clients[$clientKey]['order_link'] === null && (int) ($order['id'] ?? 0) > 0) {
                $clients[$clientKey]['order_link'] = '/admin/boutique/orders/' . (int) ($order['id'] ?? 0);
            }
            if ((string) ($clients[$clientKey]['order_reference'] ?? '') === '' && $orderReference !== '') {
                $clients[$clientKey]['order_reference'] = $orderReference;
            }
            if ($clients[$clientKey]['client_link'] === null) {
                $clients[$clientKey]['client_link'] = $this->buildAdminClientDetailUrl(
                    (string) ($clients[$clientKey]['email'] ?? ''),
                    (string) ($clients[$clientKey]['phone'] ?? ''),
                );
            }
        }

        usort($clients, static function (array $left, array $right): int {
            return strcmp((string) ($right['last_activity'] ?? ''), (string) ($left['last_activity'] ?? ''));
        });

        return array_values($clients);
    }

    private function normalizeClientSearchKey(string $email, string $phone, string $fallback): string
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail !== '') {
            return 'email:' . $normalizedEmail;
        }

        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';
        if ($normalizedPhone !== '') {
            return 'phone:' . $normalizedPhone;
        }

        return $fallback;
    }

    private function buildAdminClientDetailUrl(string $email, string $phone): string
    {
        $params = array_filter([
            'email' => trim($email),
            'phone' => trim($phone),
        ], static fn(string $value): bool => $value !== '');

        return '/admin/clients' . ($params !== [] ? '?' . http_build_query($params) : '');
    }

    /**
     * @param array<int,array<string,mixed>> $contacts
     * @param array<int,array<string,mixed>> $orders
     * @return array<string,mixed>
     */
    private function buildClientProfile(string $email, string $phone, array $contacts, array $orders): array
    {
        $name         = '';
        $location     = '';
        $lastActivity = '';
        $orderCount   = count($orders);
        $contactCount = count($contacts);
        $orderTotal   = 0;

        foreach ($contacts as $contact) {
            $candidateName = trim((string) ($contact['name'] ?? ''));
            if (strlen($candidateName) > strlen($name)) {
                $name = $candidateName;
            }

            if ($location === '') {
                $location = trim((string) ($contact['location'] ?? ''));
            }

            $createdAt = trim((string) ($contact['created_at'] ?? ''));
            if ($createdAt !== '' && strcmp($createdAt, $lastActivity) > 0) {
                $lastActivity = $createdAt;
            }
        }

        foreach ($orders as $order) {
            $candidateName = trim((string) ($order['customer_name'] ?? ''));
            if (strlen($candidateName) > strlen($name)) {
                $name = $candidateName;
            }

            if ($location === '') {
                $location = trim(implode(', ', array_filter([
                    trim((string) ($order['delivery_city'] ?? '')),
                    trim((string) ($order['delivery_postal_code'] ?? '')),
                ])));
            }

            $createdAt = trim((string) ($order['created_at'] ?? ''));
            if ($createdAt !== '' && strcmp($createdAt, $lastActivity) > 0) {
                $lastActivity = $createdAt;
            }

            $orderTotal += (int) ($order['total_cents'] ?? 0);
        }

        $latestContact   = $contacts[0] ?? null;
        $latestOrder     = $orders[0] ?? null;
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

        return [
            'name'            => $name !== '' ? $name : 'Client',
            'email'           => $email,
            'phone'           => $phone,
            'location'        => $location,
            'last_activity'   => $lastActivity,
            'contacts_count'  => $contactCount,
            'orders_count'    => $orderCount,
            'orders_total'    => $orderTotal,
            'primary_contact' => $contacts[0]['id'] ?? null,
            'primary_order'   => $orders[0]['id'] ?? null,
            'latest_contact'  => is_array($latestContact) ? [
                'id'         => (int) ($latestContact['id'] ?? 0),
                'status'     => (string) ($latestContact['status'] ?? 'new'),
                'type'       => trim((string) ($latestContact['type'] ?? '')),
                'date'       => trim((string) ($latestContact['date'] ?? '')),
                'created_at' => trim((string) ($latestContact['created_at'] ?? '')),
                'link'       => '/admin/contacts/' . (int) ($latestContact['id'] ?? 0),
            ] : null,
            'latest_order'    => is_array($latestOrder) ? [
                'id'          => (int) ($latestOrder['id'] ?? 0),
                'reference'   => trim((string) ($latestOrder['order_reference'] ?? '')),
                'status'      => (string) ($latestOrder['status'] ?? 'new'),
                'pickup_date' => trim((string) ($latestOrder['pickup_date'] ?? '')),
                'created_at'  => trim((string) ($latestOrder['created_at'] ?? '')),
                'total_cents' => (int) ($latestOrder['total_cents'] ?? 0),
                'fulfillment' => trim((string) ($latestOrder['fulfillment_method'] ?? 'pickup')),
                'link'        => '/admin/boutique/orders/' . (int) ($latestOrder['id'] ?? 0),
            ] : null,
            'actions'         => [
                'mailto'           => $email !== '' ? 'mailto:' . rawurlencode($email) : null,
                'tel'              => $normalizedPhone !== '' ? 'tel:' . $normalizedPhone : null,
                'dashboard_search' => '/admin' . (($email !== '' || $phone !== '') ? '?' . http_build_query(['q' => $email !== '' ? $email : $phone, 'scope' => 'all']) : ''),
                'contact_search'   => '/admin/contacts' . (($email !== '' || $phone !== '') ? '?' . http_build_query(['q' => $email !== '' ? $email : $phone]) : ''),
                'latest_contact'   => is_array($latestContact) ? '/admin/contacts/' . (int) ($latestContact['id'] ?? 0) : null,
                'latest_order'     => is_array($latestOrder) ? '/admin/boutique/orders/' . (int) ($latestOrder['id'] ?? 0) : null,
            ],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $contacts
     * @param array<int,array<string,mixed>> $orders
     * @return array<int,array<string,mixed>>
     */
    private function buildClientTimeline(array $contacts, array $orders): array
    {
        $timeline = [];

        foreach ($contacts as $contact) {
            $createdAt  = trim((string) ($contact['created_at'] ?? ''));
            $contactId  = (int) ($contact['id'] ?? 0);
            $timeline[] = [
                'kind'         => 'contact',
                'sort_at'      => $createdAt,
                'title'        => trim((string) ($contact['name'] ?? 'Demande')),
                'badge'        => 'Demande / devis',
                'status_label' => Contact::STATUS_LABELS[(string) ($contact['status'] ?? 'new')] ?? ucfirst((string) ($contact['status'] ?? 'new')),
                'meta'         => implode(' • ', array_filter([
                    trim((string) ($contact['type'] ?? '')),
                    trim((string) ($contact['location'] ?? '')),
                    trim((string) ($contact['date'] ?? '')) !== '' ? date('d/m/Y', strtotime((string) ($contact['date'] ?? ''))) : '',
                ])),
                'summary'      => trim((string) ($contact['message'] ?? '')),
                'link'         => $contactId > 0 ? '/admin/contacts/' . $contactId : '/admin/contacts',
                'link_label'   => 'Ouvrir la demande',
            ];
        }

        foreach ($orders as $order) {
            $createdAt      = trim((string) ($order['created_at'] ?? ''));
            $orderId        = (int) ($order['id'] ?? 0);
            $orderReference = trim((string) ($order['order_reference'] ?? ''));
            if ($orderReference === '') {
                $orderReference = '#' . $orderId;
            }

            $timeline[] = [
                'kind'         => 'order',
                'sort_at'      => $createdAt,
                'title'        => $orderReference,
                'badge'        => 'Commande boutique',
                'status_label' => ShopOrder::STATUS_LABELS[(string) ($order['status'] ?? 'new')] ?? ucfirst((string) ($order['status'] ?? 'new')),
                'meta'         => implode(' • ', array_filter([
                    trim((string) (($order['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Livraison' : 'Retrait')),
                    trim((string) ($order['pickup_date'] ?? '')) !== '' ? date('d/m/Y', strtotime((string) ($order['pickup_date'] ?? ''))) : '',
                    number_format(max(0, (int) ($order['total_cents'] ?? 0)) / 100, 2, ',', ' ') . ' €',
                ])),
                'summary'      => trim((string) ($order['message'] ?? '')),
                'link'         => $orderId > 0 ? '/admin/boutique/orders/' . $orderId : '/admin/boutique#orders',
                'link_label'   => 'Ouvrir la commande',
            ];
        }

        usort($timeline, static function (array $left, array $right): int {
            return strcmp((string) ($right['sort_at'] ?? ''), (string) ($left['sort_at'] ?? ''));
        });

        return $timeline;
    }

    /**
     * @param array<int,array<string,mixed>> $timeline
     * @return array<int,array<string,mixed>>
     */
    private function filterClientTimeline(array $timeline, string $view): array
    {
        if ($view === 'contacts') {
            return array_values(array_filter($timeline, static fn(array $event): bool => (string) ($event['kind'] ?? '') === 'contact'));
        }

        if ($view === 'orders') {
            return array_values(array_filter($timeline, static fn(array $event): bool => (string) ($event['kind'] ?? '') === 'order'));
        }

        if ($view === 'recent') {
            return array_slice($timeline, 0, 8);
        }

        return $timeline;
    }

    private function shouldNotifyClient(array $input): bool
    {
        return (string) ($input['notify_client'] ?? '') === '1';
    }

    private function statusUpdateMessageFromPost(array $input): ?string
    {
        $message = trim((string) ($input['client_message'] ?? ''));
        return $message === '' ? null : $message;
    }

    private function statusUpdateSubjectFromPost(array $input): ?string
    {
        $subject = trim((string) ($input['client_subject'] ?? ''));
        return $subject === '' ? null : $subject;
    }

    /**
     * @param array<string,mixed>|null $mailResult
     */
    private function buildStatusFlashMessage(string $baseMessage, bool $notifyClient, ?array $mailResult): string
    {
        if (! $notifyClient) {
            return $baseMessage;
        }

        if (! is_array($mailResult)) {
            return $baseMessage . ' Email client non envoye.';
        }

        if (($mailResult['client_status_sent'] ?? false) === true) {
            return $baseMessage . ' Email client envoye.';
        }

        if (($mailResult['enabled'] ?? true) !== true) {
            return $baseMessage . ' Service mail desactive : aucun email client envoye.';
        }

        $errors = is_array($mailResult['errors'] ?? null) ? $mailResult['errors'] : [];
        if ($errors !== []) {
            return $baseMessage . ' Email client non envoye : ' . implode(' | ', $errors);
        }

        return $baseMessage . ' Email client non envoye.';
    }

    /**
     * @param array<string,mixed> $contact
     */
    private function resolveContactRequestKind(array $contact): string
    {
        return ! empty($contact['menu_items']) ? 'quote' : 'contact';
    }

    private function handleMenuItemImageUpload(Menu $menuModel, int $itemId): void
    {
        $file = $_FILES['image_file'] ?? null;
        if (! is_array($file)) {
            return;
        }

        $imageService = new MenuImageService();
        if (! $imageService->hasUploadedImage($file)) {
            return;
        }

        $item = $menuModel->getItemById($itemId);
        if (! is_array($item)) {
            throw new \RuntimeException('Item introuvable pour traitement d’image.');
        }

        $slug             = trim((string) ($item['slug'] ?? ''));
        $baseName         = ($slug !== '' ? $slug : 'item') . '-' . $itemId . '-' . date('YmdHis');
        $removeBackground = isset($_POST['remove_bg']) && (string) $_POST['remove_bg'] === '1';
        $backgroundFuzz   = (int) ($_POST['background_fuzz'] ?? 12);

        $result = $imageService->processItemImage($file, $baseName, [
            'remove_background' => $removeBackground,
            'background_fuzz'   => $backgroundFuzz,
            'preview_token'     => (string) ($_POST['preview_token'] ?? ''),
        ]);
        $desktopPath = (string) ($result['desktop_path'] ?? '');
        if ($desktopPath === '') {
            throw new \RuntimeException('Chemin image générée introuvable.');
        }

        $previousImagePath = (string) ($item['image_path'] ?? '');
        $menuModel->updateItemImagePath($itemId, $desktopPath);
        $imageService->cleanupFromDesktopPath($previousImagePath);
    }

    private function handleShopItemImageUpload(Shop $shopModel, int $itemId): void
    {
        $file = $_FILES['image_file'] ?? null;
        if (! is_array($file)) {
            return;
        }

        $imageService = new MenuImageService(null, 'shop');
        if (! $imageService->hasUploadedImage($file)) {
            return;
        }

        $item = $shopModel->getItemById($itemId);
        if (! is_array($item)) {
            throw new \RuntimeException('Produit boutique introuvable pour traitement d’image.');
        }

        $slug             = trim((string) ($item['slug'] ?? ''));
        $baseName         = ($slug !== '' ? $slug : 'produit') . '-' . $itemId . '-' . date('YmdHis');
        $removeBackground = isset($_POST['remove_bg']) && (string) $_POST['remove_bg'] === '1';
        $backgroundFuzz   = (int) ($_POST['background_fuzz'] ?? 12);

        $result = $imageService->processItemImage($file, $baseName, [
            'remove_background' => $removeBackground,
            'background_fuzz'   => $backgroundFuzz,
            'preview_token'     => (string) ($_POST['preview_token'] ?? ''),
        ]);
        $desktopPath = (string) ($result['desktop_path'] ?? '');
        if ($desktopPath === '') {
            throw new \RuntimeException('Chemin image boutique générée introuvable.');
        }

        $previousImagePath = (string) ($item['image_path'] ?? '');
        $shopModel->updateItemImagePath($itemId, $desktopPath);
        $imageService->cleanupFromDesktopPath($previousImagePath);
    }

    private function handleBlogMediaUploads(array &$payload, ?array $existingPost = null): void
    {
        $previousImagePath = trim((string) ($existingPost['cover_image'] ?? ''));
        $previousVideoPath = trim((string) ($existingPost['video_url'] ?? ''));

        $imageFile = $_FILES['cover_image_file'] ?? null;
        if (is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $payload['cover_image'] = $this->storeBlogUpload(
                $imageFile,
                'images',
                ['image/jpeg', 'image/png', 'image/webp'],
                ['jpg', 'jpeg', 'png', 'webp'],
                (string) ($payload['slug'] ?? ($payload['title'] ?? 'article')),
                'image'
            );
        }

        $videoFile = $_FILES['video_file'] ?? null;
        if (is_array($videoFile) && (int) ($videoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $payload['video_url'] = $this->storeBlogUpload(
                $videoFile,
                'videos',
                ['video/mp4', 'video/webm', 'video/quicktime'],
                ['mp4', 'webm', 'mov'],
                (string) ($payload['slug'] ?? ($payload['title'] ?? 'article')),
                'video'
            );
        }

        $currentImagePath = trim((string) ($payload['cover_image'] ?? ''));
        $currentVideoPath = trim((string) ($payload['video_url'] ?? ''));

        if ($previousImagePath !== '' && $previousImagePath !== $currentImagePath) {
            $this->cleanupBlogMediaFile($previousImagePath, '/uploads/pages/blog/images/');
        }

        if ($previousVideoPath !== '' && $previousVideoPath !== $currentVideoPath) {
            $this->cleanupBlogMediaFile($previousVideoPath, '/uploads/pages/blog/videos/');
        }
    }

    private function storeBlogUpload(
        array $file,
        string $folder,
        array $allowedMimeTypes,
        array $allowedExtensions,
        string $baseName,
        string $label
    ): string {
        $this->assertUploadIsValid($file, $label);

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || ! is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Fichier ' . $label . ' invalide.');
        }

        $mimeType = $this->detectMimeType($tmpPath);
        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new \RuntimeException('Format de ' . $label . ' non supporté.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            $extension = $this->guessExtensionFromMimeType($mimeType, $allowedExtensions, $label);
        }

        $safeBaseName = $this->sanitizeUploadBaseName($baseName);
        $targetDir    = dirname(__DIR__, 2) . '/public/uploads/pages/blog/' . $folder;

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
            throw new \RuntimeException('Impossible de créer le dossier de destination du ' . $label . '.');
        }

        $targetFileName = $safeBaseName . '-' . date('YmdHis') . '.' . $extension;
        $targetPath     = $targetDir . '/' . $targetFileName;

        if (! move_uploaded_file($tmpPath, $targetPath)) {
            throw new \RuntimeException('Impossible d’enregistrer le ' . $label . '.');
        }

        return '/uploads/pages/blog/' . $folder . '/' . $targetFileName;
    }

    private function assertUploadIsValid(array $file, string $label): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $message = 'Le ' . $label . ' dépasse la taille autorisée.';
        } elseif ($error === UPLOAD_ERR_PARTIAL) {
            $message = 'Le téléversement du ' . $label . ' est incomplet.';
        } elseif ($error === UPLOAD_ERR_NO_FILE) {
            $message = 'Aucun ' . $label . ' reçu.';
        } else {
            $message = 'Erreur lors du téléversement du ' . $label . '.';
        }

        throw new \RuntimeException($message);
    }

    private function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        return is_string($mime) ? $mime : '';
    }

    private function guessExtensionFromMimeType(string $mimeType, array $allowedExtensions, string $label): string
    {
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
        ];

        $extension = $map[$mimeType] ?? '';
        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Extension de ' . $label . ' non reconnue.');
        }

        return $extension;
    }

    private function sanitizeUploadBaseName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['œ', 'æ'], ['oe', 'ae'], $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'article';
    }

    private function cleanupBlogMediaFile(string $publicPath, string $expectedPrefix): void
    {
        $publicPath = trim($publicPath);
        if ($publicPath === '' || strpos($publicPath, $expectedPrefix) !== 0) {
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . '/public' . $publicPath;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
