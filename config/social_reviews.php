<?php
declare (strict_types = 1);

use App\Core\Config;

return [
    'enabled'         => Config::get('SOCIAL_REVIEWS_ENABLED', '1') !== '0',
    'cache_ttl'       => max(300, (int) Config::get('SOCIAL_REVIEWS_CACHE_TTL', 21600)),
    'request_timeout' => max(2, (int) Config::get('SOCIAL_REVIEWS_TIMEOUT', 6)),
    'display_limit'   => max(2, (int) Config::get('SOCIAL_REVIEWS_DISPLAY_LIMIT', 6)),
    'google'          => [
        'api_key'       => trim((string) Config::get('GOOGLE_PLACES_API_KEY', '')),
        'place_id'      => trim((string) Config::get('GOOGLE_PLACE_ID', '')),
        'reviews_link'  => trim((string) Config::get('GOOGLE_REVIEWS_LINK', 'https://www.google.com/search?sca_esv=95c4599e20cb2333&sxsrf=ANbL-n4UoR9hWZxE_DJOyM6c_ZDOCjkuoA:1769787102684&q=Traiteur+passion+Avis&rflfq=1&num=20&stick=H4sIAAAAAAAAAONgkxIxNDa3MLS0tDAzNTcyNjU0NLM0Nd7AyPiKUTSkKDGzJLW0SKEgsbg4Mz9PwbEss3gRK3ZxAH98L6dLAAAA&rldimm=13781998657235116953&tbm=lcl&hl=fr-FR&sa=X&ved=2ahUKEwj4o9XcyrOSAxW1caQEHbmhH6QQ9fQKegQIUxAG&biw=1920&bih=941&dpr=1&aic=0#lkt=LocalPoiReviews')),
        'reviews_limit' => max(1, (int) Config::get('GOOGLE_REVIEWS_LIMIT', 3)),
    ],
    'facebook'        => [
        'page_id'        => trim((string) Config::get('FACEBOOK_PAGE_ID', '')),
        'access_token'   => trim((string) Config::get('FACEBOOK_PAGE_ACCESS_TOKEN', '')),
        'page_url'       => trim((string) Config::get('FACEBOOK_PAGE_URL', 'https://www.facebook.com/kevinbrien6/reviews')),
        'graph_version'  => trim((string) Config::get('FACEBOOK_GRAPH_VERSION', 'v22.0')),
        'posts_limit'    => max(1, (int) Config::get('FACEBOOK_POSTS_LIMIT', 5)),
        'comments_limit' => max(1, (int) Config::get('FACEBOOK_COMMENTS_LIMIT', 5)),
    ],
    'fallback'        => [
        'google'   => [
            'provider'   => 'google',
            'aria_label' => 'Avis Google',
            'source'     => 'Google',
            'badge'      => '★★★★★',
            'text'       => 'Un repas pour 15 à la maison… Merci pour votre gentillesse et votre accueil.',
            'link'       => 'https://www.google.com/search?sca_esv=95c4599e20cb2333&sxsrf=ANbL-n4UoR9hWZxE_DJOyM6c_ZDOCjkuoA:1769787102684&q=Traiteur+passion+Avis&rflfq=1&num=20&stick=H4sIAAAAAAAAAONgkxIxNDa3MLS0tDAzNTcyNjU0NLM0Nd7AyPiKUTSkKDGzJLW0SKEgsbg4Mz9PwbEss3gRK3ZxAH98L6dLAAAA&rldimm=13781998657235116953&tbm=lcl&hl=fr-FR&sa=X&ved=2ahUKEwj4o9XcyrOSAxW1caQEHbmhH6QQ9fQKegQIUxAG&biw=1920&bih=941&dpr=1&aic=0#lkt=LocalPoiReviews',
            'cta'        => 'Lire l’avis →',
        ],
        'facebook' => [
            'provider'   => 'facebook',
            'aria_label' => 'Avis Facebook',
            'source'     => 'Facebook',
            'badge'      => '★★★★★',
            'text'       => 'Repas couscous pour l’anniversaire… je recommanderai pour d’autres occasions !',
            'link'       => 'https://www.facebook.com/kevinbrien6/reviews',
            'cta'        => 'Lire l’avis →',
        ],
    ],
];
