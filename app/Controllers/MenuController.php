<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Menu;

final class MenuController
{
    public function redirectLegacy(): void
    {
        header('Location: /carte-évènementielle', true, 301);
        exit;
    }

    public function index(): void
    {
        $sections = [];

        try {
            $catalog  = (new Menu())->getCatalog();
            $sections = is_array($catalog['sections'] ?? null) ? $catalog['sections'] : [];
        } catch (\Throwable $e) {
            error_log('Menu catalog loading error: ' . $e->getMessage());
        }

        View::render('pages/menu', [
            'title'           => 'Traiteur Passion — Carte évènementielle',
            'metaDescription' => 'Carte évènementielle Traiteur Passion à Compiègne : buffets, cocktails, brunchs, plateaux repas et boissons pour mariages, réceptions privées et événements d\'entreprise.',
            'sections'        => $sections,
        ]);
    }
}
