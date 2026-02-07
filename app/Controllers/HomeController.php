<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('pages/home', ['title' => 'Traiteur Passion — Accueil']);
    }

    public function menu(): void
    {
        View::render('pages/menu', ['title' => 'Traiteur Passion — Menu']);
    }

    public function contact(): void
    {
        View::render('pages/contact', ['title' => 'Traiteur Passion — Contact']);
    }

    public function about(): void
    {
        View::render('pages/about', ['title' => 'Traiteur Passion — À propos']);
    }

}