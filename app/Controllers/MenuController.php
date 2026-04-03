<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Menu;

final class MenuController
{
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
            'title'    => 'Traiteur Passion — Menu',
            'sections' => $sections,
        ]);
    }
}