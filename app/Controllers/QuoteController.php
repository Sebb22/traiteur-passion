<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Contact;
use App\Models\Menu;
use App\Services\ContactSubmissionService;

final class QuoteController
{
    public function show(): void
    {
        $sections         = [];
        $selectedCategory = trim((string) ($_GET['category'] ?? ''));

        try {
            $catalog  = (new Menu())->getCatalog();
            $sections = is_array($catalog['sections'] ?? null) ? $catalog['sections'] : [];
        } catch (\Throwable $e) {
            error_log('Quote catalog loading error: ' . $e->getMessage());
        }

        if ($selectedCategory !== '') {
            $knownCategorySlugs = array_map(
                static fn(array $section): string => (string) ($section['slug'] ?? ''),
                $sections,
            );

            if (! in_array($selectedCategory, $knownCategorySlugs, true)) {
                $selectedCategory = '';
            }
        }

        View::render('pages/devis', [
            'title'            => 'Traiteur Passion — Devis',
            'sections'         => $sections,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function store(): void
    {
        $submissionService = new ContactSubmissionService();
        $result            = $submissionService->parse($_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (($result['success'] ?? false) !== true) {
            $this->json($result['status'] ?? 400, [
                'error' => $result['error'] ?? 'Requête invalide',
            ]);
            return;
        }

        $contactModel = new Contact();
        $contactId    = $contactModel->create(
            $result['contactData'] ?? [],
            $result['menuItems'] ?? []
        );

        if ($contactId === false) {
            $this->json(500, ['error' => 'Erreur lors de l\'enregistrement']);
            return;
        }

        $this->json(200, [
            'success' => true,
            'message' => 'Votre demande a été envoyée avec succès !',
            'id'      => $contactId,
        ]);
    }

    private function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
