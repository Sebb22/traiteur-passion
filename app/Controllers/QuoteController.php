<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class QuoteController
{
    public function show(): void
    {
        View::render('pages/devis', [
            'title' => 'Traiteur Passion — Devis'
        ]);
    }
}