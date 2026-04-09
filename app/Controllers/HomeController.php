<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\HttpError;
use App\Core\View;
use App\Services\SocialReviewService;

final class HomeController
{
    public function index(): void
    {
        View::render('pages/home', ['title' => 'Traiteur Passion — Accueil']);
    }

    public function about(): void
    {
        $reviews = (new SocialReviewService())->getAboutReviews();

        View::render('pages/about', [
            'title'        => 'Traiteur Passion — À propos',
            'aboutReviews' => $reviews,
        ]);
    }

    public function blog(): void
    {
        View::render('pages/blog', [
            'title' => 'Traiteur Passion — Blog',
            'posts' => array_values($this->blogPosts()),
        ]);
    }

    public function blogPost(string $slug): void
    {
        $post = $this->blogPosts()[$slug] ?? null;

        if ($post === null) {
            HttpError::notFound([
                'title'           => '404 — Article introuvable',
                'eyebrow'         => 'Article introuvable',
                'headline'        => 'Cet article n\'est plus au menu.',
                'message'         => 'Le lien demandé ne correspond à aucun article publié. Vous pouvez revenir au blog ou découvrir nos inspirations culinaires ailleurs sur le site.',
                'primaryAction'   => [
                    'href'  => '/blog',
                    'label' => 'Retour au blog',
                ],
                'secondaryAction' => [
                    'href'  => '/',
                    'label' => 'Accueil',
                ],
                'hints'           => [
                    'Vérifiez l\'adresse saisie dans la barre du navigateur.',
                    'Consultez le blog pour retrouver les autres publications.',
                    'Revenez à l\'accueil si vous cherchiez une prestation plutôt qu\'un article.',
                ],
            ]);
            return;
        }

        View::render('pages/blog-post', [
            'title' => 'Traiteur Passion — ' . $post['title'],
            'post'  => $post,
        ]);
    }

    private function blogPosts(): array
    {
        return [
            'paella-en-poelon' => [
                'slug'        => 'paella-en-poelon',
                'title'       => 'La Paella en Poêlon',
                'date_iso'    => '2026-03-02',
                'date_label'  => '2 mars 2026',
                'author'      => 'Traiteur Passion',
                'excerpt'     => 'Découvrez notre recette signature de paella en poêlon, préparée devant vos invités pour un moment convivial et spectaculaire.',
                'categories'  => ['Recette', 'Poêlon'],
                'cover_image' => '/uploads/pages/blog/images/blogIllu.jpg',
                'video_url'   => '/uploads/pages/blog/videos/paella.mp4',
                'intro'       => 'La paella en poêlon fait partie de nos animations culinaires les plus demandées. Généreuse, parfumée et festive, elle transforme un repas en véritable expérience.',
                'sections'    => [
                    [
                        'title' => 'Une animation qui rassemble',
                        'text'  => 'Préparée en direct, la paella attire les regards et crée un vrai moment de partage. Les couleurs, les arômes et la cuisson au poêlon donnent immédiatement le ton de la réception.',
                    ],
                    [
                        'title' => 'Des produits choisis avec soin',
                        'text'  => 'Nous sélectionnons des ingrédients frais, un riz adapté à la cuisson longue, des épices équilibrées et des garnitures généreuses pour garantir une paella savoureuse et régulière.',
                    ],
                    [
                        'title' => 'Idéale pour vos événements',
                        'text'  => 'Mariages, anniversaires, événements d’entreprise ou réceptions privées : le format poêlon permet de servir un grand nombre de convives avec une présentation élégante et chaleureuse.',
                    ],
                ],
            ],
        ];
    }
}
