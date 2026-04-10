<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\HttpError;
use App\Core\View;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\Menu;
use App\Services\MenuImageService;

final class AdminController
{
    public function dashboard(): void
    {
        AdminAuth::requireAuth();

        $contactModel   = new Contact();
        $blogModel      = new Blog();
        $menuModel      = new Menu();
        $contactStats   = $contactModel->getAdminSummary();
        $blogStats      = $blogModel->getAdminSummary();
        $catalogStats   = $menuModel->getAdminSummary();
        $recentContacts = $contactModel->getRecentWithMenuFlag(8);
        $typeBreakdown  = $contactModel->getTypeBreakdown(6);

        View::render('admin/dashboard', [
            'title'          => 'Administration — Dashboard',
            'contactStats'   => $contactStats,
            'blogStats'      => $blogStats,
            'catalogStats'   => $catalogStats,
            'recentContacts' => $recentContacts,
            'typeBreakdown'  => $typeBreakdown,
        ]);
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

        View::render('admin/contacts', [
            'title'         => 'Administration — Demandes et devis',
            'contacts'      => $contacts,
            'stats'         => $stats,
            'filteredCount' => $filteredCount,
            'filters'       => $filters,
            'statusOptions' => Contact::STATUS_LABELS,
            'flash'         => $this->pullFlash(),
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

        if ($contactModel->updateStatus($contactId, $status)) {
            $this->pushFlash('success', 'Statut mis à jour.');
        } else {
            $this->pushFlash('error', 'Impossible de mettre à jour le statut.');
        }

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
