<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\View;
use App\Models\Contact;
use App\Models\Menu;
use App\Services\MenuImageService;

final class AdminController
{
    public function dashboard(): void
    {
        AdminAuth::requireAuth();

        $contactModel   = new Contact();
        $menuModel      = new Menu();
        $contactStats   = $contactModel->getAdminSummary();
        $catalogStats   = $menuModel->getAdminSummary();
        $recentContacts = $contactModel->getRecentWithMenuFlag(8);
        $typeBreakdown  = $contactModel->getTypeBreakdown(6);

        View::render('admin/dashboard', [
            'title'          => 'Administration — Dashboard',
            'contactStats'   => $contactStats,
            'catalogStats'   => $catalogStats,
            'recentContacts' => $recentContacts,
            'typeBreakdown'  => $typeBreakdown,
        ]);
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
            http_response_code(404);
            View::render('errors/404', ['title' => '404 — Demande introuvable']);
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

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
