<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use App\Services\ShopPromoService;
use PDO;

final class ShopOrder
{
    private const DELIVERY_MINIMUM_CENTS = 1500;

    public const STATUS_LABELS = [
        'new'       => 'Nouvelle',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'completed' => 'Retirée',
        'cancelled' => 'Annulée',
    ];

    private PDO $db;

    /** @var list<string>|null */
    private ?array $orderColumns = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @param array<string,mixed> $customerData
     * @param array<int,int> $quantitiesByItemId
     * @return array{success:bool,status:int,order_id?:int,error?:string,conflicts?:array<int,array<string,mixed>>}
     */
    public function createOrder(array $customerData, array $quantitiesByItemId): array
    {
        $requested = [];
        foreach ($quantitiesByItemId as $itemId => $quantity) {
            $itemId   = (int) $itemId;
            $quantity = (int) $quantity;
            if ($itemId > 0 && $quantity > 0) {
                $requested[$itemId] = $quantity;
            }
        }

        if ($requested === []) {
            return [
                'success' => false,
                'status'  => 400,
                'error'   => 'Votre panier est vide.',
            ];
        }

        $placeholders = implode(',', array_fill(0, count($requested), '?'));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT bi.id, bi.name, bi.price_cents, bi.price_label, bi.stock_quantity, bi.is_active,
                        bs.name AS section_name, bs.is_active AS section_active
                 FROM boutique_items bi
                 INNER JOIN boutique_sections bs ON bs.id = bi.section_id
                 WHERE bi.id IN ({$placeholders})
                 FOR UPDATE",
            );
            $stmt->execute(array_keys($requested));
            $rows = $stmt->fetchAll();

            $itemsById = [];
            foreach ($rows as $row) {
                $itemsById[(int) $row['id']] = $row;
            }

            $conflicts = [];
            foreach ($requested as $itemId => $quantity) {
                $row = $itemsById[$itemId] ?? null;
                if (! is_array($row)) {
                    $conflicts[] = [
                        'item_id'   => $itemId,
                        'name'      => 'Produit indisponible',
                        'requested' => $quantity,
                        'available' => 0,
                    ];
                    continue;
                }

                $available   = max(0, (int) ($row['stock_quantity'] ?? 0));
                $isAvailable = ! empty($row['is_active']) && ! empty($row['section_active']);
                $allowed     = $available;

                if (! $isAvailable || $quantity > $allowed) {
                    $conflicts[] = [
                        'item_id'   => $itemId,
                        'name'      => (string) ($row['name'] ?? 'Produit indisponible'),
                        'requested' => $quantity,
                        'available' => $allowed,
                    ];
                }
            }

            if ($conflicts !== []) {
                $this->db->rollBack();
                return [
                    'success'   => false,
                    'status'    => 409,
                    'error'     => 'Le stock a changé pendant votre sélection. Merci de vérifier les quantités disponibles.',
                    'conflicts' => $conflicts,
                ];
            }

            $orderTotalCents = 0;
            foreach ($requested as $itemId => $quantity) {
                $item             = $itemsById[$itemId];
                $unitPriceCents   = max(0, (int) ($item['price_cents'] ?? 0));
                $orderTotalCents += $unitPriceCents * $quantity;
            }

            $promoEvaluation = (new ShopPromoService())->evaluateCheckout(
                $orderTotalCents,
                isset($customerData['promo_code']) ? (string) $customerData['promo_code'] : null,
            );

            if (($promoEvaluation['valid'] ?? false) !== true) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => (string) ($promoEvaluation['error'] ?? 'Le code promo est invalide.'),
                ];
            }

            $discountCents   = max(0, (int) ($promoEvaluation['discount_cents'] ?? 0));
            $finalTotalCents = max(0, (int) ($promoEvaluation['total_cents'] ?? $orderTotalCents));

            $fulfillmentMethod = $this->normalizeFulfillmentMethod($customerData['fulfillment_method'] ?? null);
            if ($fulfillmentMethod === 'delivery' && $finalTotalCents < self::DELIVERY_MINIMUM_CENTS) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'status'  => 400,
                    'error'   => 'La livraison est proposée à partir de 15 € de commande.',
                ];
            }

            $orderPayload = [
                'customer_name'        => trim((string) ($customerData['name'] ?? '')),
                'customer_email'       => trim((string) ($customerData['email'] ?? '')),
                'customer_phone'       => $this->nullableTrim($customerData['phone'] ?? null),
                'fulfillment_method'   => $fulfillmentMethod,
                'pickup_date'          => $this->nullableTrim($customerData['pickup_date'] ?? null),
                'pickup_slot'          => $this->nullableTrim($customerData['pickup_slot'] ?? null),
                'delivery_address'     => $this->nullableTrim($customerData['delivery_address'] ?? null),
                'delivery_postal_code' => $this->nullableTrim($customerData['delivery_postal_code'] ?? null),
                'delivery_city'        => $this->nullableTrim($customerData['delivery_city'] ?? null),
                'message'              => $this->nullableTrim($customerData['message'] ?? null),
                'promo_code'           => $discountCents > 0 ? $this->nullableTrim($promoEvaluation['promo_code'] ?? null) : null,
                'promo_label'          => $discountCents > 0 ? $this->nullableTrim($promoEvaluation['promo_label'] ?? null) : null,
                'discount_percent'     => $discountCents > 0 ? max(0, (int) ($promoEvaluation['discount_percent'] ?? 0)) : 0,
                'discount_cents'       => $discountCents,
                'status'               => 'new',
            ];

            $availableColumns = array_values(array_filter(
                array_keys($orderPayload),
                fn(string $column): bool => $this->hasOrderColumn($column),
            ));

            $orderStmt = $this->db->prepare(sprintf(
                'INSERT INTO boutique_orders (%s) VALUES (%s)',
                implode(', ', $availableColumns),
                implode(', ', array_map(static fn(string $column): string => ':' . $column, $availableColumns)),
            ));
            $orderStmt->execute(array_intersect_key($orderPayload, array_flip($availableColumns)));

            $orderId = (int) $this->db->lastInsertId();

            $lineStmt = $this->db->prepare(
                'INSERT INTO boutique_order_items (
                    order_id, item_id, item_name_snapshot, section_name_snapshot,
                    unit_price_cents, unit_price_label, quantity, line_total_cents
                 ) VALUES (
                    :order_id, :item_id, :item_name_snapshot, :section_name_snapshot,
                    :unit_price_cents, :unit_price_label, :quantity, :line_total_cents
                 )',
            );
            $stockStmt = $this->db->prepare(
                'UPDATE boutique_items
                 SET stock_quantity = stock_quantity - :quantity
                 WHERE id = :id',
            );

            foreach ($requested as $itemId => $quantity) {
                $item           = $itemsById[$itemId];
                $unitPriceCents = max(0, (int) ($item['price_cents'] ?? 0));

                $lineStmt->execute([
                    'order_id'              => $orderId,
                    'item_id'               => $itemId,
                    'item_name_snapshot'    => (string) ($item['name'] ?? ''),
                    'section_name_snapshot' => (string) ($item['section_name'] ?? ''),
                    'unit_price_cents'      => $unitPriceCents,
                    'unit_price_label'      => (string) ($item['price_label'] ?? ''),
                    'quantity'              => $quantity,
                    'line_total_cents'      => $unitPriceCents * $quantity,
                ]);

                $stockStmt->execute([
                    'id'       => $itemId,
                    'quantity' => $quantity,
                ]);
            }

            $this->db->commit();

            return [
                'success'  => true,
                'status'   => 200,
                'order_id' => $orderId,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Shop order create error: ' . $e->getMessage());

            return [
                'success' => false,
                'status'  => 500,
                'error'   => 'Impossible d’enregistrer la commande pour le moment.',
            ];
        }
    }

    public function getAdminSummary(): array
    {
        $summary = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
                    SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) AS preparing_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
             FROM boutique_orders",
        )->fetch();

        return [
            'total'           => (int) ($summary['total'] ?? 0),
            'new_count'       => (int) ($summary['new_count'] ?? 0),
            'confirmed_count' => (int) ($summary['confirmed_count'] ?? 0),
            'preparing_count' => (int) ($summary['preparing_count'] ?? 0),
            'completed_count' => (int) ($summary['completed_count'] ?? 0),
            'cancelled_count' => (int) ($summary['cancelled_count'] ?? 0),
        ];
    }

    public function getRecentOrders(int $limit = 12): array
    {
        $discountSelect = $this->hasOrderColumn('discount_cents')
            ? 'COALESCE(bo.discount_cents, 0)'
            : '0';

        $stmt = $this->db->prepare(
            "SELECT bo.*, COUNT(boi.id) AS line_count,
                    COALESCE(SUM(boi.quantity), 0) AS item_count,
                    COALESCE(SUM(boi.line_total_cents), 0) AS subtotal_cents,
                    {$discountSelect} AS discount_cents,
                    COALESCE(SUM(boi.line_total_cents), 0) - {$discountSelect} AS total_cents
             FROM boutique_orders bo
             LEFT JOIN boutique_order_items boi ON boi.order_id = bo.id
             GROUP BY bo.id
             ORDER BY bo.created_at DESC
             LIMIT :limit",
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getByIdWithItems(int $orderId): ?array
    {
        $discountSelect = $this->hasOrderColumn('discount_cents')
            ? 'COALESCE(bo.discount_cents, 0)'
            : '0';

        $stmt = $this->db->prepare(
            "SELECT bo.*, COUNT(boi.id) AS line_count,
                    COALESCE(SUM(boi.quantity), 0) AS item_count,
                    COALESCE(SUM(boi.line_total_cents), 0) AS subtotal_cents,
                    {$discountSelect} AS discount_cents,
                    COALESCE(SUM(boi.line_total_cents), 0) - {$discountSelect} AS total_cents
             FROM boutique_orders bo
             LEFT JOIN boutique_order_items boi ON boi.order_id = bo.id
             WHERE bo.id = :id
             GROUP BY bo.id
             LIMIT 1",
        );
        $stmt->execute(['id' => $orderId]);

        $order = $stmt->fetch();
        if (! is_array($order)) {
            return null;
        }

        $itemsStmt = $this->db->prepare(
            'SELECT *
             FROM boutique_order_items
             WHERE order_id = :order_id
             ORDER BY id ASC',
        );
        $itemsStmt->execute(['order_id' => $orderId]);
        $order['items'] = $itemsStmt->fetchAll();

        return $order;
    }

    public function updateStatus(int $orderId, string $status): bool
    {
        if (! isset(self::STATUS_LABELS[$status])) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'SELECT id, status, stock_restored_at
                 FROM boutique_orders
                 WHERE id = :id
                 FOR UPDATE',
            );
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (! is_array($order)) {
                $this->db->rollBack();
                return false;
            }

            $currentStatus   = (string) ($order['status'] ?? '');
            $stockRestoredAt = $order['stock_restored_at'] ?? null;

            if ($currentStatus === 'cancelled' && $stockRestoredAt !== null && $status !== 'cancelled') {
                $this->db->rollBack();
                return false;
            }

            if ($status === 'cancelled' && $stockRestoredAt === null) {
                $itemStmt = $this->db->prepare(
                    'SELECT item_id, quantity
                     FROM boutique_order_items
                     WHERE order_id = :order_id',
                );
                $itemStmt->execute(['order_id' => $orderId]);
                $items = $itemStmt->fetchAll();

                $restoreStmt = $this->db->prepare(
                    'UPDATE boutique_items
                     SET stock_quantity = stock_quantity + :quantity
                     WHERE id = :id',
                );

                foreach ($items as $item) {
                    $restoreStmt->execute([
                        'id'       => (int) ($item['item_id'] ?? 0),
                        'quantity' => max(0, (int) ($item['quantity'] ?? 0)),
                    ]);
                }

                $updateStmt = $this->db->prepare(
                    'UPDATE boutique_orders
                     SET status = :status,
                         stock_restored_at = NOW()
                     WHERE id = :id',
                );
                $updateStmt->execute([
                    'id'     => $orderId,
                    'status' => $status,
                ]);

                $this->db->commit();
                return true;
            }

            $updateStmt = $this->db->prepare(
                'UPDATE boutique_orders
                 SET status = :status
                 WHERE id = :id',
            );
            $updateStmt->execute([
                'id'     => $orderId,
                'status' => $status,
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Shop order status update error: ' . $e->getMessage());
            return false;
        }
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function normalizeFulfillmentMethod($value): string
    {
        return trim((string) $value) === 'delivery' ? 'delivery' : 'pickup';
    }

    private function hasOrderColumn(string $column): bool
    {
        return in_array($column, $this->getOrderColumns(), true);
    }

    /**
     * @return list<string>
     */
    private function getOrderColumns(): array
    {
        if ($this->orderColumns !== null) {
            return $this->orderColumns;
        }

        $stmt = $this->db->query('SHOW COLUMNS FROM boutique_orders');
        $rows = $stmt !== false ? $stmt->fetchAll() : [];

        $this->orderColumns = array_values(array_filter(array_map(
            static fn(array $row): string => (string) ($row['Field'] ?? ''),
            is_array($rows) ? $rows : [],
        )));

        return $this->orderColumns;
    }
}
