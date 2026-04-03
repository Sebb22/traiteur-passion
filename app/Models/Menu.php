<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Menu
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Returns fully-assembled catalog sections for menu rendering.
     * Each section contains its ordered items, each item its ordered options.
     *
     * @return array{sections: list<array{id: int, slug: string, name: string, description: string, items: list<array>}>}
     */
    public function getCatalog(): array
    {
        $sections = $this->db->query(
            "SELECT id, slug, name, description, sort_order
             FROM menu_sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC",
        )->fetchAll(PDO::FETCH_ASSOC);

        $items = $this->db->query(
            "SELECT mi.id, mi.section_id, mi.slug, mi.name, mi.short_description, mi.image_path, mi.image_alt,
                    mi.price_from_label, mi.sort_order
             FROM menu_items mi
             INNER JOIN menu_sections ms ON ms.id = mi.section_id
             WHERE mi.is_active = 1 AND ms.is_active = 1
             ORDER BY mi.section_id ASC, mi.sort_order ASC, mi.id ASC",
        )->fetchAll(PDO::FETCH_ASSOC);

        $options = $this->db->query(
            "SELECT mo.id, mo.item_id, mo.option_key, mo.label, mo.description, mo.price_cents, mo.price_label,
                    mo.is_quote_only, mo.sort_order
             FROM menu_item_options mo
             INNER JOIN menu_items mi ON mi.id = mo.item_id
             WHERE mo.is_active = 1 AND mi.is_active = 1
             ORDER BY mi.id ASC, mo.sort_order ASC, mo.id ASC",
        )->fetchAll(PDO::FETCH_ASSOC);

        // ── Indexation des options par item_id ────────────────────────────────
        $optionsByItemId = [];
        foreach ($options as $option) {
            $itemId                     = (int) $option['item_id'];
            $optionsByItemId[$itemId][] = [
                'id'            => (int) $option['id'],
                'option_key'    => (string) $option['option_key'],
                'label'         => (string) ($option['label'] ?? ''),
                'description'   => trim((string) ($option['description'] ?? '')),
                'price_cents'   => $option['price_cents'] !== null ? (int) $option['price_cents'] : null,
                'price_label'   => (string) ($option['price_label'] ?? ''),
                'is_quote_only' => (bool) ($option['is_quote_only'] ?? false),
            ];
        }

        // ── Assemblage des items par section_id ───────────────────────────────
        $itemsBySectionId = [];
        foreach ($items as $item) {
            $itemId    = (int) $item['id'];
            $sectionId = (int) $item['section_id'];
            $slug      = (string) ($item['slug'] ?? '');
            $imagePath = (string) ($item['image_path'] ?? '');

            // Fallback image : convention /uploads/pages/menu/{slug}-1200.webp
            if ($imagePath === '' && $slug !== '') {
                $imagePath = "/uploads/pages/menu/{$slug}-1200.webp";
            }

            $itemsBySectionId[$sectionId][] = [
                'id'               => $itemId,
                'slug'             => $slug,
                'name'             => (string) ($item['name'] ?? ''),
                'description'      => trim((string) ($item['short_description'] ?? '')),
                'image_path'       => $imagePath,
                'image_alt'        => (string) ($item['image_alt'] ?? ($item['name'] ?? '')),
                'price_from_label' => (string) ($item['price_from_label'] ?? ''),
                'options'          => $optionsByItemId[$itemId] ?? [],
            ];
        }

        // ── Construction de la liste de sections ──────────────────────────────
        $catalogSections = [];
        foreach ($sections as $section) {
            $sectionId = (int) $section['id'];

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
             FROM menu_sections
             ORDER BY sort_order ASC, id ASC",
        )->fetchAll();

        $items = $this->db->query(
            "SELECT mi.id, mi.section_id, mi.slug, mi.name, mi.short_description, mi.image_path, mi.image_alt,
                    mi.price_from_label, mi.sort_order, mi.is_active
             FROM menu_items mi
             ORDER BY mi.section_id ASC, mi.sort_order ASC, mi.id ASC",
        )->fetchAll();

        $options = $this->db->query(
            "SELECT mo.id, mo.item_id, mo.option_key, mo.label, mo.description, mo.price_cents, mo.price_label,
                    mo.is_quote_only, mo.sort_order, mo.is_active
             FROM menu_item_options mo
             ORDER BY mo.item_id ASC, mo.sort_order ASC, mo.id ASC",
        )->fetchAll();

        $optionsByItemId = [];
        foreach ($options as $option) {
            $itemId = (int) $option['item_id'];
            if (! isset($optionsByItemId[$itemId])) {
                $optionsByItemId[$itemId] = [];
            }
            $optionsByItemId[$itemId][] = $option;
        }

        $itemsBySectionId = [];
        foreach ($items as $item) {
            $sectionId       = (int) $item['section_id'];
            $itemId          = (int) $item['id'];
            $item['options'] = $optionsByItemId[$itemId] ?? [];

            if (! isset($itemsBySectionId[$sectionId])) {
                $itemsBySectionId[$sectionId] = [];
            }

            $itemsBySectionId[$sectionId][] = $item;
        }

        foreach ($sections as &$section) {
            $sectionId              = (int) $section['id'];
            $section['items']       = $itemsBySectionId[$sectionId] ?? [];
            $section['count_items'] = count($section['items']);

            $countOptions = 0;
            foreach ($section['items'] as $item) {
                $countOptions += count($item['options'] ?? []);
            }
            $section['count_options'] = $countOptions;
        }
        unset($section);

        return $sections;
    }

    public function updateSection(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_sections
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
            'sort_order'  => $this->toInt($data['sort_order'] ?? 0),
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
            'INSERT INTO menu_sections (slug, name, description, sort_order, is_active)
             VALUES (:slug, :name, :description, :sort_order, :is_active)',
        );

        $stmt->execute([
            'slug'        => $slug,
            'name'        => $name,
            'description' => $this->nullableString($data['description'] ?? null),
            'sort_order'  => $this->toInt($data['sort_order'] ?? 0),
            'is_active'   => $this->toBoolInt($data['is_active'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteSection(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM menu_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function reorderSections(array $sectionIds): void
    {
        $stmt = $this->db->prepare('UPDATE menu_sections SET sort_order = :sort_order WHERE id = :id');

        $position = 10;
        foreach ($sectionIds as $sectionId) {
            $stmt->execute([
                'id'         => (int) $sectionId,
                'sort_order' => $position,
            ]);
            $position += 10;
        }
    }

    public function updateItem(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items
             SET name = :name,
                 short_description = :short_description,
                 image_path = :image_path,
                 image_alt = :image_alt,
                 price_from_label = :price_from_label,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id',
        );

        $stmt->execute([
            'id'                => $id,
            'name'              => trim((string) ($data['name'] ?? '')),
            'short_description' => $this->nullableString($data['short_description'] ?? null),
            'image_path'        => $this->nullableString($data['image_path'] ?? null),
            'image_alt'         => $this->nullableString($data['image_alt'] ?? null),
            'price_from_label'  => $this->nullableString($data['price_from_label'] ?? null),
            'sort_order'        => $this->toInt($data['sort_order'] ?? 0),
            'is_active'         => $this->toBoolInt($data['is_active'] ?? null),
        ]);
    }

    public function updateOption(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_item_options
             SET label = :label,
                 description = :description,
                 price_cents = :price_cents,
                 price_label = :price_label,
                 is_quote_only = :is_quote_only,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id',
        );

        $stmt->execute([
            'id'            => $id,
            'label'         => trim((string) ($data['label'] ?? '')),
            'description'   => $this->nullableString($data['description'] ?? null),
            'price_cents'   => $this->nullableInt($data['price_cents'] ?? null),
            'price_label'   => $this->nullableString($data['price_label'] ?? null),
            'is_quote_only' => $this->toBoolInt($data['is_quote_only'] ?? null),
            'sort_order'    => $this->toInt($data['sort_order'] ?? 0),
            'is_active'     => $this->toBoolInt($data['is_active'] ?? null),
        ]);
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
            'INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
             VALUES (:section_id, :slug, :name, :short_description, :image_path, :image_alt, :price_from_label, :sort_order, :is_active)',
        );

        $stmt->execute([
            'section_id'        => $sectionId,
            'slug'              => $slug,
            'name'              => $name,
            'short_description' => $this->nullableString($data['short_description'] ?? null),
            'image_path'        => $this->nullableString($data['image_path'] ?? null),
            'image_alt'         => $this->nullableString($data['image_alt'] ?? null),
            'price_from_label'  => $this->nullableString($data['price_from_label'] ?? null),
            'sort_order'        => $this->toInt($data['sort_order'] ?? 0),
            'is_active'         => $this->toBoolInt($data['is_active'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteItem(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function reorderItems(int $sectionId, array $itemIds): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items
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

    public function createOption(int $itemId, array $data): int
    {
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            throw new \InvalidArgumentException('Le libellé de l’option est requis.');
        }

        $optionKey = trim((string) ($data['option_key'] ?? ''));
        if ($optionKey === '') {
            $optionKey = $this->slugify($label);
        }
        $optionKey = $this->ensureUniqueOptionKeyForItem($itemId, $optionKey);

        $stmt = $this->db->prepare(
            'INSERT INTO menu_item_options (item_id, option_key, label, description, price_cents, price_label, is_quote_only, sort_order, is_active)
             VALUES (:item_id, :option_key, :label, :description, :price_cents, :price_label, :is_quote_only, :sort_order, :is_active)',
        );

        $stmt->execute([
            'item_id'       => $itemId,
            'option_key'    => $optionKey,
            'label'         => $label,
            'description'   => $this->nullableString($data['description'] ?? null),
            'price_cents'   => $this->nullableInt($data['price_cents'] ?? null),
            'price_label'   => $this->nullableString($data['price_label'] ?? null),
            'is_quote_only' => $this->toBoolInt($data['is_quote_only'] ?? null),
            'sort_order'    => $this->toInt($data['sort_order'] ?? 0),
            'is_active'     => $this->toBoolInt($data['is_active'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteOption(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM menu_item_options WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function getAdminSummary(): array
    {
        $sections = $this->db->query(
            'SELECT COUNT(*) AS total_sections,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_sections
             FROM menu_sections',
        )->fetch();

        $items = $this->db->query(
            'SELECT COUNT(*) AS total_items,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_items
             FROM menu_items',
        )->fetch();

        $options = $this->db->query(
            'SELECT COUNT(*) AS total_options,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_options
             FROM menu_item_options',
        )->fetch();

        $totalOptions  = (int) (($options['total_options'] ?? 0));
        $activeOptions = (int) (($options['active_options'] ?? 0));

        return [
            'total_sections'   => (int) (($sections['total_sections'] ?? 0)),
            'active_sections'  => (int) (($sections['active_sections'] ?? 0)),
            'total_items'      => (int) (($items['total_items'] ?? 0)),
            'active_items'     => (int) (($items['active_items'] ?? 0)),
            'total_options'    => $totalOptions,
            'active_options'   => $activeOptions,
            'inactive_options' => max(0, $totalOptions - $activeOptions),
        ];
    }

    public function getItemById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $item = $stmt->fetch();
        return is_array($item) ? $item : null;
    }

    public function updateItemImagePath(int $itemId, string $imagePath): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items
             SET image_path = :image_path
             WHERE id = :id',
        );

        $stmt->execute([
            'id'         => $itemId,
            'image_path' => $this->nullableString($imagePath),
        ]);
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function toInt($value): int
    {
        return (int) ($value ?? 0);
    }

    private function toBoolInt($value): int
    {
        return $value === null ? 0 : 1;
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

    private function ensureUniqueItemSlugInSection(int $sectionId, string $slug): string
    {
        $candidate = $slug;
        $suffix    = 2;

        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM menu_items WHERE section_id = :section_id AND slug = :slug');
            $stmt->execute(['section_id' => $sectionId, 'slug' => $candidate]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    private function ensureUniqueOptionKeyForItem(int $itemId, string $optionKey): string
    {
        $candidate = $optionKey;
        $suffix    = 2;

        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM menu_item_options WHERE item_id = :item_id AND option_key = :option_key');
            $stmt->execute(['item_id' => $itemId, 'option_key' => $candidate]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = $optionKey . '-' . $suffix;
            $suffix++;
        }
    }

    private function ensureUniqueSectionSlug(string $slug): string
    {
        $candidate = $slug;
        $suffix    = 2;

        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM menu_sections WHERE slug = :slug');
            $stmt->execute(['slug' => $candidate]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }
}
