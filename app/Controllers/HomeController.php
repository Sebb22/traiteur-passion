<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\HttpError;
use App\Core\View;
use App\Models\Blog;
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
        $blogModel = new Blog();

        View::render('pages/blog', [
            'title'     => 'Traiteur Passion — Blog',
            'bodyClass' => 'page--blog',
            'posts'     => $blogModel->getPublishedPosts(),
        ]);
    }

    public function blogPost(string $slug): void
    {
        $post = (new Blog())->getPublishedPostBySlug($slug);

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
            'title'     => 'Traiteur Passion — ' . $post['title'],
            'bodyClass' => 'page--blog-post',
            'post'      => $post,
        ]);
    }
}
