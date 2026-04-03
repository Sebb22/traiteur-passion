<?php
declare (strict_types = 1);

use App\Core\Router;

/** @var Router $router */

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/menu', 'MenuController@index');
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
$router->get('/admin/contacts/export', 'AdminController@exportContacts');
$router->get('/admin/contacts', 'AdminController@contacts');
$router->post('/admin/contacts/{id}/status', 'AdminController@updateContactStatus');
$router->get('/admin/contacts/{id}', 'AdminController@contactDetail');
