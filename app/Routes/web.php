<?php
declare (strict_types = 1);

use App\Core\Router;

/** @var Router $router */

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/carte-évènementielle', 'MenuController@index');
$router->get('/menu', 'MenuController@redirectLegacy');
$router->get('/boutique-en-ligne', 'ShopController@index');
$router->post('/boutique-en-ligne', 'ShopController@store');
$router->get('/api/boutique/stock', 'ShopController@stock');
$router->get('/blog', 'HomeController@blog');
$router->get('/blog/{slug}', 'HomeController@blogPost');
$router->get('/contact', 'ContactController@show');
$router->post('/contact', 'ContactController@store');
$router->get('/devis', 'QuoteController@show');
$router->post('/devis', 'QuoteController@store');
$router->get('/a-propos', 'HomeController@about');

// Admin auth routes
$router->get('/admin/login', 'AuthController@showLogin');
$router->post('/admin/login', 'AuthController@login');
$router->post('/admin/logout', 'AuthController@logout');

// Admin routes
$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->post('/admin/dashboard/shop-promo', 'AdminController@updateDashboardShopPromo');
$router->get('/admin/blog', 'AdminController@blog');
$router->post('/admin/blog/create', 'AdminController@createBlogPost');
$router->post('/admin/blog/{slug}/delete', 'AdminController@deleteBlogPost');
$router->post('/admin/blog/{slug}', 'AdminController@updateBlogPost');
$router->get('/admin/catalog', 'AdminController@catalog');
$router->post('/admin/catalog/sections/create', 'AdminController@createCatalogSection');
$router->post('/admin/catalog/sections/reorder', 'AdminController@reorderCatalogSections');
$router->post('/admin/catalog/sections/{id}', 'AdminController@updateCatalogSection');
$router->post('/admin/catalog/sections/{id}/delete', 'AdminController@deleteCatalogSection');
$router->post('/admin/catalog/sections/{id}/items/create', 'AdminController@createCatalogItem');
$router->post('/admin/catalog/sections/{id}/items/reorder', 'AdminController@reorderCatalogItems');
$router->post('/admin/catalog/items/{id}', 'AdminController@updateCatalogItem');
$router->post('/admin/catalog/image-preview', 'AdminController@previewCatalogImage');
$router->post('/admin/catalog/items/{id}/delete', 'AdminController@deleteCatalogItem');
$router->post('/admin/catalog/items/{id}/options/create', 'AdminController@createCatalogOption');
$router->post('/admin/catalog/options/{id}', 'AdminController@updateCatalogOption');
$router->post('/admin/catalog/options/{id}/delete', 'AdminController@deleteCatalogOption');
$router->get('/admin/boutique', 'AdminController@shop');
$router->post('/admin/boutique/sections/create', 'AdminController@createShopSection');
$router->post('/admin/boutique/sections/reorder', 'AdminController@reorderShopSections');
$router->post('/admin/boutique/sections/{id}', 'AdminController@updateShopSection');
$router->post('/admin/boutique/sections/{id}/delete', 'AdminController@deleteShopSection');
$router->post('/admin/boutique/sections/{id}/items/create', 'AdminController@createShopItem');
$router->post('/admin/boutique/sections/{id}/items/reorder', 'AdminController@reorderShopItems');
$router->post('/admin/boutique/items/{id}', 'AdminController@updateShopItem');
$router->post('/admin/boutique/image-preview', 'AdminController@previewShopImage');
$router->post('/admin/boutique/items/{id}/delete', 'AdminController@deleteShopItem');
$router->get('/admin/boutique/orders/{id}', 'AdminController@orderDetail');
$router->post('/admin/boutique/orders/{id}/status', 'AdminController@updateShopOrderStatus');
$router->get('/admin/contacts/export', 'AdminController@exportContacts');
$router->get('/admin/contacts', 'AdminController@contacts');
$router->post('/admin/contacts/{id}/status', 'AdminController@updateContactStatus');
$router->get('/admin/contacts/{id}', 'AdminController@contactDetail');
$router->post('/admin/boutique/items/{id}/options/create', 'AdminController@createShopItemOption');
$router->post('/admin/boutique/options/{id}', 'AdminController@updateShopItemOption');
$router->post('/admin/boutique/options/{id}/delete', 'AdminController@deleteShopItemOption');