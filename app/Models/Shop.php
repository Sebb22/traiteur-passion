<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Shop
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @return array{sections:list<array{id:int,slug:string,name:string,description:string,items:list<array<string,mixed>>}>}
     */
    public function getCatalog(): array
    {
        $sections = $this->db->query(
            "SELECT id, slug, name, description, sort_order
             FROM boutique_sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC",
        )->fetchAll(PDO::FETCH_ASSOC);

        $items = $this->db->query(
            "SELECT bi.id, bi.section_id, bi.slug, bi.name, bi.short_description, bi.image_path, bi.image_alt,
                    bi.price_cents, bi.price_label, bi.stock_quantity, bi.low_stock_threshold, bi.max_order_quantity,
                    bi.sort_order
             FROM boutique_items bi
             INNER JOIN boutique_sections bs ON bs.id = bi.section_id
             WHERE bi.is_active = 1 AND bs.is_active = 1
             ORDER BY bi.section_id ASC, bi.sort_order ASC, bi.id ASC",
        )->fetchAll(PDO::FETCH_ASSOC);

        $itemsBySectionId = [];
        foreach ($items as $item) {
            $sectionId = (int) $item['section_id'];
            $stock     = max(0, (int) ($item['stock_quantity'] ?? 0));

            $itemsBySectionId[$sectionId][] = [
                'id'                  => (int) $item['id'],
                'slug'                => (string) ($item['slug'] ?? ''),
                'name'                => (string) ($item['name'] ?? ''),
                'description'         => trim((string) ($item['short_description'] ?? '')),
                'image_path'          => (string) ($item['image_path'] ?? ''),
                'image_alt'           => (string) ($item['image_alt'] ?? ($item['name'] ?? '')),
                'price_cents'         => (int) ($item['price_cents'] ?? 0),
                'price_label'         => (string) ($item['price_label'] ?? ''),
                'stock_quantity'      => $stock,
                'low_stock_threshold' => max(0, (int) ($item['low_stock_threshold'] ?? 0)),
                'is_sold_out'         => $stock <= 0,
                'is_low_stock'        => $stock > 0 && $stock <= max(0, (int) ($item['low_stock_threshold'] ?? 0)),
            ];
        }

        $catalogSections = [];
        foreach ($sections as $section) {
            $sectionId         = (int) $section['id'];
            $catalogSections[] = [
                'id'          => $sectionId,
                'slug'        => (string) ($section['slug'] ?? ''),
                'name'        => (string) ($section['name'] ?? ''),
                'description' => trim((string) ($section['description'] ?? '')),
                'items'       => $itemsBySectionId[$sectionId] ?? [],
            ];
        }

        return ['sections' => $catalogSections];
    }

    public function getCatalogForAdmin(): array
    {
        $sections = $this->db->query(
            "SELECT id, slug, name, description, sort_order, is_active
             FROM boutique_sections
             ORDER BY sort_order ASC, id ASC",
        )->fetchAll();

        $items = $this->db->query(
            "SELECT id, section_id, slug, name, short_description, image_path, image_alt,
                    price_cents, price_label, stock_quantity, low_stock_threshold, max_order_quantity,
                    sort_order, is_active
             FROM boutique_items
             ORDER BY section_id ASC, sort_order ASC, id ASC",
        )->fetchAll();

        $itemsBySectionId = [];
        foreach ($items as $item) {
            $sectionId = (int) $item['section_id'];
            if (! isset($itemsBySectionId[$sectionId])) {
                $itemsBySectionId[$sectionId] = [];
            }

            $itemsBySectionId[$sectionId][] = $item;
        }

        foreach ($sections as &$section) {
            $sectionId              = (int) $section['id'];
            $section['items']       = $itemsBySectionId[$sectionId] ?? [];
            $section['count_items'] = count($section['items']);
        }
        unset($section);

        return $sections;
    }

    public function updateSection(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE boutique_sections
             SET name = :name,
                 description = :description,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id',
        );

        $stmt->execute([
            'id'          => $id,
            'name'        => trim((string) ($data['name'] ?? '')),
            'description' => $this->nullableString($data['description'] ?? null),
            'sort_order'  => $this->resolveSortOrderForUpdate('boutique_sections', $id, $data),
            'is_active'   => $this->toBoolInt($data['is_active'] ?? null),
        ]);
    }

    public function createSection(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Le nom de la section est requis.');
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        $slug = $this->ensureUniqueSectionSlug($slug);

        $stmt = $this->db->prepare(
            'INSERT INTO boutique_sections (slug, name, description, sort_order, is_active)
             VALUES (:slug, :name, :description, :sort_order, :is_active)',
        );

        $stmt->execute([
            'slug'        => $slug,
            'name'        => $name,
            'description' => $this->nullableString($data['description'] ?? null),
            'sort_order'  => $this->resolveSortOrderForCreate('boutique_sections', $data),
            'is_active'   => $this->toBoolInt($data['is_active'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteSection(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM boutique_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function reorderSections(array $sectionIds): void
    {
        $stmt = $this->db->prepare('UPDATE boutique_sections SET sort_order = :sort_order WHERE id = :id');

        $position = 10;
        foreach ($sectionIds as $sectionId) {
            $stmt->execute([
                'id'         => (int) $sectionId,
                'sort_order' => $position,
            ]);
            $position += 10;
        }
    }

    public function createItem(int $sectionId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Le nom de l’item est requis.');
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        $slug = $this->ensureUniqueItemSlugInSection($sectionId, $slug);

        $stmt = $this->db->prepare(
            'INSERT INTO boutique_items (
                section_id, slug, name, short_description, image_path, image_alt,
                price_cents, price_label, stock_quantity, low_stock_threshold,
                max_order_quantity, sort_order, is_active
             ) VALUES (
                :section_id, :slug, :name, :short_description, :image_path, :image_alt,
                :price_cents, :price_label, :stock_quantity, :low_stock_threshold,
                :max_order_quantity, :sort_order, :is_active
             )',
        );

        $stmt->execute([
            'section_id'          => $sectionId,
            'slug'                => $slug,
            'name'                => $name,
            'short_description'   => $this->nullableString($data['short_description'] ?? null),
            'image_path'          => $this->nullableString($data['image_path'] ?? null),
            'image_alt'           => $this->nullableString($data['image_alt'] ?? null),
            'price_cents'         => $this->resolvePriceCents($data),
            'price_label'         => $this->nullableString($data['price_label'] ?? null),
            'stock_quantity'      => max(0, $this->toInt($data['stock_quantity'] ?? 0)),
            'low_stock_threshold' => max(0, $this->toInt($data['low_stock_threshold'] ?? 0)),
            'max_order_quantity'  => $this->resolveMaxOrderQuantityForCreate($data),
            'sort_order'          => $this->resolveSortOrderForCreate('boutique_items', $data, 'section_id', $sectionId),
            'is_active'           => $this->toBoolInt($data['is_active'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateItem(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE boutique_items
             SET name = :name,
                 short_description = :short_description,
                 image_path = :image_path,
                 image_alt = :image_alt,
                 price_cents = :price_cents,
                 price_label = :price_label,
                 stock_quantity = :stock_quantity,
                 low_stock_threshold = :low_stock_threshold,
                 max_order_quantity = :max_order_quantity,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id',
        );

        $stmt->execute([
            'id'                  => $id,
            'name'                => trim((string) ($data['name'] ?? '')),
            'short_description'   => $this->nullableString($data['short_description'] ?? null),
            'image_path'          => $this->nullableString($data['image_path'] ?? null),
            'image_alt'           => $this->nullableString($data['image_alt'] ?? null),
            'price_cents'         => $this->resolvePriceCents($data),
            'price_label'         => $this->nullableString($data['price_label'] ?? null),
            'stock_quantity'      => max(0, $this->toInt($data['stock_quantity'] ?? 0)),
            'low_stock_threshold' => max(0, $this->toInt($data['low_stock_threshold'] ?? 0)),
            'max_order_quantity'  => $this->resolveMaxOrderQuantityForUpdate($id, $data),
            'sort_order'          => $this->resolveSortOrderForUpdate('boutique_items', $id, $data),
            'is_active'           => $this->toBoolInt($data['is_active'] ?? null),
        ]);
    }

    public function deleteItem(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM boutique_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function getItemById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM boutique_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $item = $stmt->fetch();
        return is_array($item) ? $item : null;
    }

    public function updateItemImagePath(int $itemId, string $imagePath): void
    {
        $stmt = $this->db->prepare(
            'UPDATE boutique_items
             SET image_path = :image_path
             WHERE id = :id',
        );

        $stmt->execute([
            'id'         => $itemId,
            'image_path' => $this->nullableString($imagePath),
        ]);
    }

    public function reorderItems(int $sectionId, array $itemIds): void
    {
        $stmt = $this->db->prepare(
            'UPDATE boutique_items
             SET sort_order = :sort_order
             WHERE id = :id AND section_id = :section_id',
        );

        $position = 10;
        foreach ($itemIds as $itemId) {
            $stmt->execute([
                'id'         => (int) $itemId,
                'section_id' => $sectionId,
                'sort_order' => $position,
            ]);
            $position += 10;
        }
    }

    public function getAdminSummary(): array
    {
        $sections = $this->db->query(
            'SELECT COUNT(*) AS total_sections,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_sections
             FROM boutique_sections',
        )->fetch();

        $items = $this->db->query(
            'SELECT COUNT(*) AS total_items,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_items,
                    SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS sold_out_items,
                    SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_items
             FROM boutique_items',
        )->fetch();

        return [
            'total_sections'  => (int) ($sections['total_sections'] ?? 0),
            'active_sections' => (int) ($sections['active_sections'] ?? 0),
            'total_items'     => (int) ($items['total_items'] ?? 0),
            'active_items'    => (int) ($items['active_items'] ?? 0),
            'sold_out_items'  => (int) ($items['sold_out_items'] ?? 0),
            'low_stock_items' => (int) ($items['low_stock_items'] ?? 0),
        ];
    }

    public function getLowStockItems(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT bi.id, bi.name, bi.stock_quantity, bi.low_stock_threshold, bi.is_active,
                    bs.name AS section_name
             FROM boutique_items bi
             INNER JOIN boutique_sections bs ON bs.id = bi.section_id
             WHERE bi.stock_quantity <= bi.low_stock_threshold
             ORDER BY bi.stock_quantity ASC, bi.updated_at ASC
             LIMIT :limit",
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getStockSnapshot(): array
    {
        $rows = $this->db->query(
            "SELECT id, name, stock_quantity, is_active
             FROM boutique_items
             ORDER BY id ASC",
        )->fetchAll();

        $snapshot = [];
        foreach ($rows as $row) {
            $itemId            = (int) ($row['id'] ?? 0);
            $snapshot[$itemId] = [
                'id'             => $itemId,
                'name'           => (string) ($row['name'] ?? ''),
                'stock_quantity' => max(0, (int) ($row['stock_quantity'] ?? 0)),
                'is_active'      => ! empty($row['is_active']),
            ];
        }

        return $snapshot;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function toInt($value): int
    {
        return (int) ($value ?? 0);
    }

    private function toBoolInt($value): int
    {
        return $value === null ? 0 : 1;
    }

    private function resolveSortOrderForCreate(string $table, array $data, ?string $parentColumn = null, ?int $parentId = null): int
    {
        if ($this->hasNonEmptyInput($data, 'sort_order')) {
            return $this->toInt($data['sort_order']);
        }

        return $this->nextSortOrder($table, $parentColumn, $parentId);
    }

    private function resolveSortOrderForUpdate(string $table, int $id, array $data): int
    {
        if ($this->hasNonEmptyInput($data, 'sort_order')) {
            return $this->toInt($data['sort_order']);
        }

        return $this->currentSortOrder($table, $id);
    }

    private function hasNonEmptyInput(array $data, string $key): bool
    {
        return array_key_exists($key, $data) && trim((string) $data[$key]) !== '';
    }

    private function nextSortOrder(string $table, ?string $parentColumn = null, ?int $parentId = null): int
    {
        $this->assertSortableTable($table);
        if ($parentColumn !== null) {
            $this->assertSortableParentColumn($parentColumn);
        }

        $sql    = 'SELECT COALESCE(MAX(sort_order), 0) FROM ' . $table;
        $params = [];

        if ($parentColumn !== null && $parentId !== null) {
            $sql                 .= ' WHERE ' . $parentColumn . ' = :parent_id';
            $params['parent_id']  = $parentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) + 10;
    }

    private function currentSortOrder(string $table, int $id): int
    {
        $this->assertSortableTable($table);

        $stmt = $this->db->prepare('SELECT sort_order FROM ' . $table . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $value = $stmt->fetchColumn();
        return $value === false ? 0 : (int) $value;
    }

    private function resolveMaxOrderQuantityForCreate(array $data): int
    {
        if ($this->hasNonEmptyInput($data, 'max_order_quantity')) {
            return max(1, $this->toInt($data['max_order_quantity']));
        }

        return max(1, $this->toInt($data['stock_quantity'] ?? 0));
    }

    private function resolveMaxOrderQuantityForUpdate(int $id, array $data): int
    {
        if ($this->hasNonEmptyInput($data, 'max_order_quantity')) {
            return max(1, $this->toInt($data['max_order_quantity']));
        }

        $stmt = $this->db->prepare('SELECT max_order_quantity FROM boutique_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $value = $stmt->fetchColumn();
        return $value === false ? max(1, $this->toInt($data['stock_quantity'] ?? 0)) : max(1, (int) $value);
    }

    private function assertSortableTable(string $table): void
    {
        if (! in_array($table, ['boutique_sections', 'boutique_items'], true)) {
            throw new \InvalidArgumentException('Table de tri non autorisee.');
        }
    }

    private function assertSortableParentColumn(string $column): void
    {
        if (! in_array($column, ['section_id'], true)) {
            throw new \InvalidArgumentException('Colonne parente non autorisee.');
        }
    }

    private function requiredPositiveInt($value): int
    {
        $number = (int) ($value ?? 0);
        if ($number < 0) {
            throw new \InvalidArgumentException('La valeur numérique ne peut pas être négative.');
        }

        return $number;
    }

    private function resolvePriceCents(array $data): int
    {
        $priceEuros = trim((string) ($data['price_euros'] ?? ''));
        if ($priceEuros !== '') {
            return $this->parseMoneyToCents($priceEuros);
        }

        return $this->requiredPositiveInt($data['price_cents'] ?? 0);
    }

    private function parseMoneyToCents(string $value): int
    {
        $normalized = str_replace([' ', "\xc2\xa0"], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);

        if ($normalized === '' || ! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('Le prix doit être saisi au format 12,50.');
        }

        $amount = (float) $normalized;
        if ($amount < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif.');
        }

        return (int) round($amount * 100);
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['œ', 'æ'], ['oe', 'ae'], $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'item' : $value;
    }

    private function ensureUniqueSectionSlug(string $slug): string
    {
        $candidate = $slug;
        $suffix    = 2;

        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM boutique_sections WHERE slug = :slug');
            $stmt->execute(['slug' => $candidate]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    private function ensureUniqueItemSlugInSection(int $sectionId, string $slug): string
    {
        $candidate = $slug;
        $suffix    = 2;

        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM boutique_items WHERE section_id = :section_id AND slug = :slug');
            $stmt->execute([
                'section_id' => $sectionId,
                'slug'       => $candidate,
            ]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }
}
