<?php
declare (strict_types = 1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Blog
{
    private PDO $db;
    private string $legacyStoragePath;

    public function __construct(?string $projectRoot = null)
    {
        $root                    = $projectRoot ?? dirname(__DIR__, 2);
        $this->db                = Database::getInstance();
        $this->legacyStoragePath = $root . '/storage/cache/blog-posts.json';

        $this->ensureSchema();
        $this->importLegacyJsonIfNeeded();
    }

    public function getAdminSummary(): array
    {
        $posts = $this->readPosts();

        $published = 0;
        $drafts    = 0;
        $videos    = 0;

        foreach ($posts as $post) {
            if (! empty($post['is_published'])) {
                $published++;
            } else {
                $drafts++;
            }

            if (trim((string) ($post['video_url'] ?? '')) !== '') {
                $videos++;
            }
        }

        return [
            'total'     => count($posts),
            'published' => $published,
            'drafts'    => $drafts,
            'videos'    => $videos,
        ];
    }

    public function getPublishedPosts(): array
    {
        return array_values(array_filter(
            $this->readPosts(),
            static fn(array $post): bool => ! empty($post['is_published'])
        ));
    }

    public function getAllForAdmin(): array
    {
        return $this->readPosts();
    }

    public function getPostBySlugForAdmin(string $slug): ?array
    {
        return $this->findPostRecordBySlug($slug);
    }

    public function getPublishedPostBySlug(string $slug): ?array
    {
        foreach ($this->readPosts() as $post) {
            if ((string) ($post['slug'] ?? '') !== $slug) {
                continue;
            }

            return ! empty($post['is_published']) ? $post : null;
        }

        return null;
    }

    public function createPost(array $data): string
    {
        $post         = $this->hydratePost($data);
        $post['slug'] = $this->ensureUniqueSlug((string) $post['slug']);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO blog_posts (
                    slug, title, date_iso, author, excerpt, categories_json, cover_image, video_url, intro, is_published, created_at, updated_at
                 ) VALUES (
                    :slug, :title, :date_iso, :author, :excerpt, :categories_json, :cover_image, :video_url, :intro, :is_published, :created_at, :updated_at
                 )'
            );

            $stmt->execute($this->postStatementParams($post));

            $postId = (int) $this->db->lastInsertId();
            $this->replaceSections($postId, is_array($post['sections'] ?? null) ? $post['sections'] : []);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return (string) $post['slug'];
    }

    public function updatePost(string $existingSlug, array $data): string
    {
        $existing = $this->findPostRecordBySlug($existingSlug);
        if ($existing === null) {
            throw new \RuntimeException('Article introuvable.');
        }

        $updated         = $this->hydratePost($data, $existing);
        $updated['slug'] = $this->ensureUniqueSlug((string) $updated['slug'], (int) $existing['id']);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'UPDATE blog_posts
                 SET slug = :slug,
                     title = :title,
                     date_iso = :date_iso,
                     author = :author,
                     excerpt = :excerpt,
                     categories_json = :categories_json,
                     cover_image = :cover_image,
                     video_url = :video_url,
                     intro = :intro,
                     is_published = :is_published,
                     updated_at = :updated_at
                 WHERE id = :id'
            );

            $params       = $this->postStatementParams($updated);
            $params['id'] = (int) $existing['id'];
            unset($params['created_at']);

            $stmt->execute($params);
            $this->replaceSections((int) $existing['id'], is_array($updated['sections'] ?? null) ? $updated['sections'] : []);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return (string) $updated['slug'];
    }

    public function deletePost(string $slug): void
    {
        $stmt = $this->db->prepare('DELETE FROM blog_posts WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Article introuvable.');
        }
    }

    private function hydratePost(array $data, array $existing = []): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est requis.');
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        $dateIso = $this->normalizeDate((string) ($data['date_iso'] ?? ($existing['date_iso'] ?? '')));
        if ($dateIso === '') {
            $dateIso = date('Y-m-d');
        }

        $now = $this->currentTimestamp();

        return $this->normalizePost([
            'slug'         => $slug,
            'title'        => $title,
            'date_iso'     => $dateIso,
            'author'       => trim((string) ($data['author'] ?? ($existing['author'] ?? 'Traiteur Passion'))),
            'excerpt'      => trim((string) ($data['excerpt'] ?? '')),
            'categories'   => $this->parseCategories((string) ($data['categories_text'] ?? '')),
            'cover_image'  => trim((string) ($data['cover_image'] ?? '')),
            'video_url'    => $this->nullableString($data['video_url'] ?? null),
            'intro'        => trim((string) ($data['intro'] ?? '')),
            'sections'     => $this->buildSections(
                is_array($data['section_titles'] ?? null) ? $data['section_titles'] : [],
                is_array($data['section_texts'] ?? null) ? $data['section_texts'] : []
            ),
            'is_published' => isset($data['is_published']),
            'created_at'   => (string) ($existing['created_at'] ?? $now),
            'updated_at'   => $now,
        ]);
    }

    private function buildSections(array $titles, array $texts): array
    {
        $sections = [];
        $count    = max(count($titles), count($texts));

        for ($index = 0; $index < $count; $index++) {
            $title = trim((string) ($titles[$index] ?? ''));
            $text  = trim((string) ($texts[$index] ?? ''));

            if ($title === '' && $text === '') {
                continue;
            }

            $sections[] = [
                'title' => $title,
                'text'  => $text,
            ];
        }

        return $sections;
    }

    private function parseCategories(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, static fn(string $value): bool => $value !== ''));

        return array_values(array_unique($parts));
    }

    private function normalizePost(array $post): array
    {
        $dateIso = $this->normalizeDate((string) ($post['date_iso'] ?? ''));

        return [
            'slug'           => (string) ($post['slug'] ?? ''),
            'title'          => trim((string) ($post['title'] ?? '')),
            'date_iso'       => $dateIso,
            'date_label'     => $this->formatDateLabel($dateIso),
            'author'         => trim((string) ($post['author'] ?? 'Traiteur Passion')),
            'excerpt'        => trim((string) ($post['excerpt'] ?? '')),
            'categories'     => array_values(array_filter(array_map(
                static fn($value): string => trim((string) $value),
                is_array($post['categories'] ?? null) ? $post['categories'] : []
            ), static fn(string $value): bool => $value !== '')),
            'categories_csv' => implode(', ', array_values(array_filter(array_map(
                static fn($value): string => trim((string) $value),
                is_array($post['categories'] ?? null) ? $post['categories'] : []
            ), static fn(string $value): bool => $value !== ''))),
            'cover_image'    => trim((string) ($post['cover_image'] ?? '')),
            'video_url'      => $this->nullableString($post['video_url'] ?? null),
            'intro'          => trim((string) ($post['intro'] ?? '')),
            'sections'       => $this->normalizeSections(is_array($post['sections'] ?? null) ? $post['sections'] : []),
            'is_published'   => ! empty($post['is_published']),
            'created_at'     => $this->normalizeDateTime((string) ($post['created_at'] ?? '')),
            'updated_at'     => $this->normalizeDateTime((string) ($post['updated_at'] ?? '')),
        ];
    }

    private function normalizeSections(array $sections): array
    {
        $normalized = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $title = trim((string) ($section['title'] ?? ''));
            $text  = trim((string) ($section['text'] ?? ''));

            if ($title === '' && $text === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'text'  => $text,
            ];
        }

        return $normalized;
    }

    private function readPosts(): array
    {
        $posts = $this->db->query(
            'SELECT id, slug, title, date_iso, author, excerpt, categories_json, cover_image, video_url, intro, is_published, created_at, updated_at
             FROM blog_posts
             ORDER BY date_iso DESC, id DESC'
        )->fetchAll();

        if ($posts === []) {
            return [];
        }

        $postIds          = array_map(static fn(array $post): int => (int) $post['id'], $posts);
        $sectionsByPostId = $this->fetchSectionsByPostId($postIds);

        $posts = array_map(function (array $post) use ($sectionsByPostId): array {
            $postId = (int) ($post['id'] ?? 0);

            return $this->normalizePost([
                'slug'         => $post['slug'] ?? '',
                'title'        => $post['title'] ?? '',
                'date_iso'     => $post['date_iso'] ?? '',
                'author'       => $post['author'] ?? '',
                'excerpt'      => $post['excerpt'] ?? '',
                'categories'   => $this->decodeCategories((string) ($post['categories_json'] ?? '')),
                'cover_image'  => $post['cover_image'] ?? '',
                'video_url'    => $post['video_url'] ?? null,
                'intro'        => $post['intro'] ?? '',
                'sections'     => $sectionsByPostId[$postId] ?? [],
                'is_published' => (int) ($post['is_published'] ?? 0) === 1,
                'created_at'   => $post['created_at'] ?? '',
                'updated_at'   => $post['updated_at'] ?? '',
            ]);
        }, $posts);

        usort($posts, static function (array $left, array $right): int {
            return strcmp((string) ($right['date_iso'] ?? ''), (string) ($left['date_iso'] ?? ''));
        });

        return $posts;
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoredId = null): string
    {
        $candidate = $slug;
        $suffix    = 2;

        while (true) {
            $sql    = 'SELECT COUNT(*) FROM blog_posts WHERE slug = :slug';
            $params = ['slug' => $candidate];

            if ($ignoredId !== null) {
                $sql          .= ' AND id <> :id';
                $params['id']  = $ignoredId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $exists = (int) $stmt->fetchColumn() > 0;

            if (! $exists) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? '' : date('Y-m-d', $timestamp);
    }

    private function formatDateLabel(string $dateIso): string
    {
        if ($dateIso === '') {
            return '';
        }

        $timestamp = strtotime($dateIso);
        if ($timestamp === false) {
            return $dateIso;
        }

        $months = [
            1  => 'janvier',
            2  => 'fevrier',
            3  => 'mars',
            4  => 'avril',
            5  => 'mai',
            6  => 'juin',
            7  => 'juillet',
            8  => 'aout',
            9  => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'decembre',
        ];

        return (string) date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['œ', 'æ'], ['oe', 'ae'], $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'article' : $value;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function currentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function normalizeDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? '' : date('Y-m-d H:i:s', $timestamp);
    }

    private function ensureSchema(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(160) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                date_iso DATE NOT NULL,
                author VARCHAR(160) NOT NULL,
                excerpt TEXT NULL,
                categories_json TEXT NULL,
                cover_image VARCHAR(255) NULL,
                video_url VARCHAR(255) NULL,
                intro TEXT NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_blog_posts_date (date_iso),
                INDEX idx_blog_posts_published (is_published)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS blog_post_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                title VARCHAR(255) NULL,
                body TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
                INDEX idx_blog_post_sections_order (post_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function importLegacyJsonIfNeeded(): void
    {
        $postsCount = (int) $this->db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
        if ($postsCount > 0 || ! is_file($this->legacyStoragePath)) {
            return;
        }

        $raw = @file_get_contents($this->legacyStoragePath);
        if ($raw === false || trim($raw) === '') {
            return;
        }

        $payload     = json_decode($raw, true);
        $legacyPosts = is_array($payload['posts'] ?? null) ? $payload['posts'] : [];
        if ($legacyPosts === []) {
            return;
        }

        $this->db->beginTransaction();

        try {
            foreach ($legacyPosts as $legacyPost) {
                if (! is_array($legacyPost)) {
                    continue;
                }

                $post         = $this->normalizePost($legacyPost);
                $post['slug'] = $this->ensureUniqueSlug((string) $post['slug']);

                $stmt = $this->db->prepare(
                    'INSERT INTO blog_posts (
                        slug, title, date_iso, author, excerpt, categories_json, cover_image, video_url, intro, is_published, created_at, updated_at
                     ) VALUES (
                        :slug, :title, :date_iso, :author, :excerpt, :categories_json, :cover_image, :video_url, :intro, :is_published, :created_at, :updated_at
                     )'
                );

                $stmt->execute($this->postStatementParams($post));
                $postId = (int) $this->db->lastInsertId();
                $this->replaceSections($postId, is_array($post['sections'] ?? null) ? $post['sections'] : []);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function fetchSectionsByPostId(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
        $stmt         = $this->db->prepare(
            'SELECT post_id, title, body, sort_order
             FROM blog_post_sections
             WHERE post_id IN (' . $placeholders . ')
             ORDER BY post_id ASC, sort_order ASC, id ASC'
        );
        $stmt->execute($postIds);

        $sectionsByPostId = [];
        foreach ($stmt->fetchAll() as $row) {
            $postId                      = (int) ($row['post_id'] ?? 0);
            $sectionsByPostId[$postId][] = [
                'title' => trim((string) ($row['title'] ?? '')),
                'text'  => trim((string) ($row['body'] ?? '')),
            ];
        }

        return $sectionsByPostId;
    }

    private function replaceSections(int $postId, array $sections): void
    {
        $deleteStmt = $this->db->prepare('DELETE FROM blog_post_sections WHERE post_id = :post_id');
        $deleteStmt->execute(['post_id' => $postId]);

        if ($sections === []) {
            return;
        }

        $insertStmt = $this->db->prepare(
            'INSERT INTO blog_post_sections (post_id, title, body, sort_order)
             VALUES (:post_id, :title, :body, :sort_order)'
        );

        $sortOrder = 10;
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $insertStmt->execute([
                'post_id'    => $postId,
                'title'      => $this->nullableString($section['title'] ?? null),
                'body'       => $this->nullableString($section['text'] ?? null),
                'sort_order' => $sortOrder,
            ]);

            $sortOrder += 10;
        }
    }

    private function decodeCategories(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn($value): string => trim((string) $value),
            $decoded
        ), static fn(string $value): bool => $value !== ''));
    }

    private function postStatementParams(array $post): array
    {
        $categories = is_array($post['categories'] ?? null) ? $post['categories'] : [];

        return [
            'slug'            => (string) ($post['slug'] ?? ''),
            'title'           => trim((string) ($post['title'] ?? '')),
            'date_iso'        => (string) ($post['date_iso'] ?? date('Y-m-d')),
            'author'          => trim((string) ($post['author'] ?? 'Traiteur Passion')),
            'excerpt'         => $this->nullableString($post['excerpt'] ?? null),
            'categories_json' => json_encode(array_values($categories), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'cover_image'     => $this->nullableString($post['cover_image'] ?? null),
            'video_url'       => $this->nullableString($post['video_url'] ?? null),
            'intro'           => $this->nullableString($post['intro'] ?? null),
            'is_published'    => ! empty($post['is_published']) ? 1 : 0,
            'created_at'      => $this->normalizeDateTime((string) ($post['created_at'] ?? $this->currentTimestamp())),
            'updated_at'      => $this->normalizeDateTime((string) ($post['updated_at'] ?? $this->currentTimestamp())),
        ];
    }

    private function findPostRecordBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, slug, title, date_iso, author, excerpt, categories_json, cover_image, video_url, intro, is_published, created_at, updated_at
             FROM blog_posts
             WHERE slug = :slug
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);

        $post = $stmt->fetch();
        if (! is_array($post)) {
            return null;
        }

        $sections = $this->fetchSectionsByPostId([(int) $post['id']]);

        return $this->normalizePost([
            'id'           => (int) $post['id'],
            'slug'         => $post['slug'] ?? '',
            'title'        => $post['title'] ?? '',
            'date_iso'     => $post['date_iso'] ?? '',
            'author'       => $post['author'] ?? '',
            'excerpt'      => $post['excerpt'] ?? '',
            'categories'   => $this->decodeCategories((string) ($post['categories_json'] ?? '')),
            'cover_image'  => $post['cover_image'] ?? '',
            'video_url'    => $post['video_url'] ?? null,
            'intro'        => $post['intro'] ?? '',
            'sections'     => $sections[(int) $post['id']] ?? [],
            'is_published' => (int) ($post['is_published'] ?? 0) === 1,
            'created_at'   => $post['created_at'] ?? '',
            'updated_at'   => $post['updated_at'] ?? '',
        ]) + ['id' => (int) $post['id']];
    }
}
