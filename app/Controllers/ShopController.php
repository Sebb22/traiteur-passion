<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Services\ShopOrderNotificationService;
use App\Services\ShopOrderSubmissionService;
use App\Services\ShopPromoService;

final class ShopController
{
    public function index(): void
    {
        $sections  = [];
        $loadError = null;
        $shopPromo = null;

        try {
            $catalog   = (new Shop())->getCatalog();
            $sections  = is_array($catalog['sections'] ?? null) ? $catalog['sections'] : [];
            $shopPromo = (new ShopPromoService())->getPublicPromo();
        } catch (\Throwable $e) {
            error_log('Shop catalog loading error: ' . $e->getMessage());
            $loadError = 'La boutique est temporairement indisponible. Revenez dans quelques instants.';
        }

        View::render('pages/shop', [
            'title'           => 'Traiteur Passion — Boutique en ligne',
            'bodyClass'       => 'page--shop',
            'metaDescription' => 'Commandez en ligne nos créations du moment. Stocks limités, disponibilité mise à jour en temps réel et retrait organisé simplement.',
            'sections'        => $sections,
            'loadError'       => $loadError,
            'shopPromo'       => $shopPromo,
        ]);
    }

    public function store(): void
    {
        $result = (new ShopOrderSubmissionService())->parse($_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (($result['success'] ?? false) !== true) {
            $this->json((int) ($result['status'] ?? 400), [
                'error' => $result['error'] ?? 'Requête invalide',
            ]);
            return;
        }

        $orderResult = (new ShopOrder())->createOrder(
            $result['customerData'] ?? [],
            $result['quantities'] ?? [],
        );

        $status = (int) ($orderResult['status'] ?? 500);
        if (($orderResult['success'] ?? false) !== true) {
            $this->json($status, [
                'error'     => $orderResult['error'] ?? 'Impossible d’enregistrer la commande.',
                'conflicts' => $orderResult['conflicts'] ?? [],
                'stock'     => $this->safeStockSnapshot(),
            ]);
            return;
        }

        $orderId = (int) ($orderResult['order_id'] ?? 0);
        if ($orderId > 0) {
            $orderData = (new ShopOrder())->getByIdWithItems($orderId);
            if (is_array($orderData)) {
                $notificationResult = (new ShopOrderNotificationService())->dispatch($orderId, $orderData);
                if (($notificationResult['errors'] ?? []) !== []) {
                    error_log('Shop order notification error(s): ' . implode(' | ', $notificationResult['errors']));
                }
            }
        }

        $this->json(200, [
            'success' => true,
            'message' => 'Votre commande a bien été enregistrée. Nous vous confirmerons rapidement sa préparation.',
            'id'      => $orderResult['order_id'] ?? null,
            'stock'   => $this->safeStockSnapshot(),
        ]);
    }

    public function stock(): void
    {
        try {
            $this->json(200, [
                'items' => $this->safeStockSnapshot(),
            ]);
        } catch (\Throwable $e) {
            error_log('Shop stock endpoint error: ' . $e->getMessage());
            $this->json(503, [
                'error' => 'Le stock n’est pas disponible pour le moment.',
                'items' => [],
            ]);
        }
    }

    private function safeStockSnapshot(): array
    {
        try {
            return array_values((new Shop())->getStockSnapshot());
        } catch (\Throwable $e) {
            error_log('Shop stock snapshot error: ' . $e->getMessage());
            return [];
        }
    }

    private function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
