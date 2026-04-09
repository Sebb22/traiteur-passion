<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Contact;
use App\Models\Menu;
use App\Services\ContactNotificationService;
use App\Services\ContactSubmissionService;

final class ContactController
{
    /**
     * Show contact form
     */
    public function show(): void
    {
        $sections = [];

        try {
            $catalog  = (new Menu())->getCatalog();
            $sections = is_array($catalog['sections'] ?? null) ? $catalog['sections'] : [];
        } catch (\Throwable $e) {
            error_log('Contact catalog loading error: ' . $e->getMessage());
        }

        View::render('pages/contact', [
            'title'    => 'Traiteur Passion — Contact',
            'sections' => $sections,
        ]);
    }

    /**
     * Handle contact form submission
     */
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

        // Save to database
        $contactModel = new Contact();
        $contactId    = $contactModel->create(
            $result['contactData'] ?? [],
            $result['menuItems'] ?? []
        );

        if ($contactId === false) {
            $this->json(500, ['error' => 'Erreur lors de l\'enregistrement']);
            return;
        }

        $notificationResult = (new ContactNotificationService())->dispatch(
            'contact',
            (int) $contactId,
            $result['contactData'] ?? [],
            $result['menuItems'] ?? [],
        );

        if (($notificationResult['errors'] ?? []) !== []) {
            error_log('Contact notification errors: ' . implode(' | ', $notificationResult['errors']));
        }

        $this->json(200, [
            'success' => true,
            'message' => 'Votre demande de contact a été envoyée avec succès !',
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
