<?php
declare (strict_types = 1);

namespace App\Core;

final class Navigation
{
    private static array $pageLabels = [
        '/'         => 'Accueil',
        '/menu'     => 'Menu',
        '/blog'     => 'Blog',
        '/a-propos' => 'À propos',
        '/contact'  => 'Contact',
        '/devis'    => 'Devis',
    ];

    private static array $descriptions = [
        '/'         => 'Traiteur à Compiègne spécialisé en mariages, réceptions privées et événements d\'entreprise. Cuisine de saison, prestation sur mesure.',
        '/menu'     => 'Découvrez nos formules traiteur : buffets, cocktails, brunch et plats signature pour vos événements à Compiègne et alentours.',
        '/blog'     => 'Conseils, recettes et actualités Traiteur Passion : inspirations culinaires et organisation d\'événements.',
        '/a-propos' => 'Découvrez Traiteur Passion : notre équipe, notre méthode et nos engagements pour des réceptions mémorables.',
        '/contact'  => 'Contactez Traiteur Passion pour votre mariage, réception privée ou événement professionnel. Réponse rapide et accompagnement personnalisé.',
        '/devis'    => 'Demandez votre devis traiteur sur mesure à Compiègne. Menus adaptés, budget maîtrisé et accompagnement complet.',
    ];

    public static function getCurrentPath(): string
    {
        $currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        return rtrim($currentPath, '/') ?: '/';
    }

    public static function isActivePath(string $href): bool
    {
        $currentPath    = self::getCurrentPath();
        $normalizedHref = rtrim($href, '/') ?: '/';

        if ($normalizedHref === '/') {
            return $currentPath === '/';
        }

        return $currentPath === $normalizedHref || strpos($currentPath, $normalizedHref . '/') === 0;
    }

    public static function getActivePageLabel(): string
    {
        foreach (self::$pageLabels as $href => $label) {
            if (self::isActivePath($href)) {
                return $label;
            }
        }

        return 'Accueil';
    }

    public static function getMetaDescription(string $currentPath): string
    {
        return self::$descriptions[$currentPath] ?? self::$descriptions['/'];
    }

    public static function getCanonicalUrl(string $currentPath): string
    {
        $isHttps = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme  = $isHttps ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'www.traiteur-passion.fr';

        return $scheme . '://' . $host . $currentPath;
    }

    public static function getBodyClass(string $currentPath): string
    {
        if (strpos($currentPath, '/admin') === 0) {
            return 'page--admin';
        }

        if (strpos($currentPath, '/blog/') === 0) {
            return 'page--blog-post';
        }

        $bodyClasses = [
            '/'         => 'page--home',
            '/menu'     => 'page--menu',
            '/blog'     => 'page--blog',
            '/a-propos' => 'page--about',
            '/contact'  => 'page--contact',
            '/devis'    => 'page--devis',
        ];

        return $bodyClasses[$currentPath] ?? 'page--generic';
    }

    public static function getBreadcrumbs(string $currentPath, string $pageTitle): array
    {
        $isHttps = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme  = $isHttps ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'www.traiteur-passion.fr';

        $breadcrumbs = [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Accueil',
                'item'     => $scheme . '://' . $host . '/',
            ],
        ];

        if ($currentPath === '/') {
            return $breadcrumbs;
        }

        $position     = 2;
        $canonicalUrl = self::getCanonicalUrl($currentPath);

        if (strpos($currentPath, '/blog/') === 0) {
            $breadcrumbs[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => 'Blog',
                'item'     => $scheme . '://' . $host . '/blog',
            ];

            $articleName = trim(preg_replace('/^Traiteur Passion\s+[—-]\s+/u', '', $pageTitle) ?? '');
            if ($articleName !== '') {
                $breadcrumbs[] = [
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'name'     => $articleName,
                    'item'     => $canonicalUrl,
                ];
            }
        } else {
            $label = self::$pageLabels[$currentPath] ?? trim(preg_replace('/^Traiteur Passion\s+[—-]\s+/u', '', $pageTitle) ?? '');
            if ($label !== '') {
                $breadcrumbs[] = [
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'name'     => $label,
                    'item'     => $canonicalUrl,
                ];
            }
        }

        return $breadcrumbs;
    }
}
