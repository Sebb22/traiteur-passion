<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Contact
{
    public const STATUS_LABELS = [
        'new'         => 'Nouveau',
        'in_progress' => 'En cours',
        'quoted'      => 'Devis envoye',
        'completed'   => 'Termine',
        'cancelled'   => 'Annule',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new contact request
     *
     * @param array $data Contact form data
     * @param array $menuItems Selected menu items
     * @return int|false The last insert ID or false on failure
     */
    public function create(array $data, array $menuItems = [])
    {
        try {
            $this->db->beginTransaction();

            // Insert main contact request
            $sql = "INSERT INTO contact_requests
                    (name, email, phone, people, date, location, type, message, created_at)
                    VALUES
                    (:name, :email, :phone, :people, :date, :location, :type, :message, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name'     => $data['name'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'] ?? null,
                ':people'   => $data['people'] ?? null,
                ':date'     => $data['date'] ?? null,
                ':location' => $data['location'] ?? null,
                ':type'     => $data['type'] ?? null,
                ':message'  => $data['message'],
            ]);

            $contactId = (int) $this->db->lastInsertId();

            // Insert selected menu items
            if (! empty($menuItems)) {
                $sql = "INSERT INTO contact_menu_items
                        (contact_id, menu_item_name, menu_item_category, menu_item_price, quantity)
                        VALUES
                        (:contact_id, :name, :category, :price, :quantity)";

                $stmt = $this->db->prepare($sql);

                foreach ($menuItems as $item) {
                    $stmt->execute([
                        ':contact_id' => $contactId,
                        ':name'       => $item['name'],
                        ':category'   => $item['category'],
                        ':price'      => $item['price'] ?? null,
                        ':quantity'   => $item['quantity'] ?? 1,
                    ]);
                }
            }

            $this->db->commit();
            return $contactId;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Contact creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all contact requests
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM contact_requests
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get contact requests with optional admin filters.
     */
    public function getFiltered(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildFilterClauses($filters);

        $sql = "SELECT cr.*
                FROM contact_requests cr
                {$whereSql}
                ORDER BY cr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countFiltered(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildFilterClauses($filters);

        $sql  = "SELECT COUNT(*) FROM contact_requests cr {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Dashboard summary counters for admin home and contacts pages.
     */
    public function getAdminSummary(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                    SUM(CASE WHEN status = 'quoted' THEN 1 ELSE 0 END) AS quoted_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
                    SUM(CASE WHEN date IS NOT NULL AND date >= CURDATE() THEN 1 ELSE 0 END) AS upcoming_events
                FROM contact_requests";

        $summary = $this->db->query($sql)->fetch();
        if (! is_array($summary)) {
            $summary = [];
        }

        $withMenuItems = (int) $this->db->query(
            'SELECT COUNT(DISTINCT contact_id) FROM contact_menu_items',
        )->fetchColumn();

        return [
            'total'             => (int) ($summary['total'] ?? 0),
            'new_count'         => (int) ($summary['new_count'] ?? 0),
            'in_progress_count' => (int) ($summary['in_progress_count'] ?? 0),
            'quoted_count'      => (int) ($summary['quoted_count'] ?? 0),
            'completed_count'   => (int) ($summary['completed_count'] ?? 0),
            'cancelled_count'   => (int) ($summary['cancelled_count'] ?? 0),
            'upcoming_events'   => (int) ($summary['upcoming_events'] ?? 0),
            'with_menu_items'   => $withMenuItems,
        ];
    }

    public function getRecentWithMenuFlag(int $limit = 8): array
    {
        $sql = "SELECT cr.*,
                       COUNT(cmi.id) AS menu_items_count
                FROM contact_requests cr
                LEFT JOIN contact_menu_items cmi ON cmi.contact_id = cr.id
                GROUP BY cr.id
                ORDER BY cr.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTypeBreakdown(int $limit = 6): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(type), ''), 'non-renseigne') AS type_key,
                    COUNT(*) AS total
                FROM contact_requests
                GROUP BY type_key
                ORDER BY total DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $typeKey      = (string) ($row['type_key'] ?? 'non-renseigne');
            $row['label'] = $typeKey === 'non-renseigne' ? 'Non renseigne' : ucfirst($typeKey);
            $row['total'] = (int) ($row['total'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (! isset(self::STATUS_LABELS[$status])) {
            return false;
        }

        $sql  = "UPDATE contact_requests SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id,
        ]);
    }

    /**
     * Get a single contact request with menu items
     */
    public function getById(int $id): ?array
    {
        $sql  = "SELECT * FROM contact_requests WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $contact = $stmt->fetch();
        if (! $contact) {
            return null;
        }

        // Get associated menu items
        $sql  = "SELECT * FROM contact_menu_items WHERE contact_id = :contact_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':contact_id' => $id]);

        $contact['menu_items'] = $stmt->fetchAll();

        return $contact;
    }

    /**
     * Delete a contact request
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            // Delete menu items first (foreign key)
            $sql  = "DELETE FROM contact_menu_items WHERE contact_id = :contact_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':contact_id' => $id]);

            // Delete contact
            $sql  = "DELETE FROM contact_requests WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Contact deletion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilterClauses(array $filters): array
    {
        $whereParts = [];
        $params     = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && isset(self::STATUS_LABELS[$status])) {
            $whereParts[]      = 'cr.status = :status';
            $params[':status'] = $status;
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $whereParts[] = '(cr.name LIKE :search
                             OR cr.email LIKE :search
                             OR cr.phone LIKE :search
                             OR cr.type LIKE :search
                             OR cr.location LIKE :search
                             OR cr.message LIKE :search)';
            $params[':search'] = '%' . $query . '%';
        }

        $whereSql = $whereParts !== [] ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        return [$whereSql, $params];
    }
}
