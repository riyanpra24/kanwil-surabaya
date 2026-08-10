<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->post('login', 'Auth::login', ['filter' => 'csrf']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->get('data-aset', 'Assets::index', ['filter' => 'auth']);
$routes->post('data-aset', 'Assets::store', ['filter' => ['auth', 'csrf']]);
$routes->group('data-aset', ['filter' => 'auth'], static function ($routes) {
    $routes->get('new', 'Assets::create');
    $routes->get('(:num)', 'Assets::show/$1');
    $routes->get('(:num)/edit', 'Assets::edit/$1');
    $routes->post('(:num)', 'Assets::update/$1', ['filter' => 'csrf']);
    $routes->post('(:num)/delete', 'Assets::delete/$1', ['filter' => 'csrf']);
});
$routes->get('operasional-ti/monitoring', 'Endpoints::monitoring', ['filter' => 'auth']);
$routes->get('operasional-ti/monitoring/export', 'Endpoints::exportMonitoring', ['filter' => 'auth']);
$routes->get('operasional-ti/(:segment)', 'Endpoints::index/$1', ['filter' => 'auth']);
$routes->post('operasional-ti/endpoints', 'Endpoints::store', ['filter' => ['auth', 'csrf']]);
$routes->post('operasional-ti/endpoints/(:num)', 'Endpoints::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('operasional-ti/endpoints/(:num)/delete', 'Endpoints::delete/$1', ['filter' => ['auth', 'csrf']]);
$routes->get('data-karyawan/monitoring', 'Employees::monitoring', ['filter' => 'auth']);
$routes->get('data-karyawan/(:segment)', 'Employees::index/$1', ['filter' => 'auth']);
$routes->post('data-karyawan', 'Employees::store', ['filter' => ['auth', 'csrf']]);
$routes->post('data-karyawan/(:num)', 'Employees::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('data-karyawan/(:num)/delete', 'Employees::delete/$1', ['filter' => ['auth', 'csrf']]);
$routes->group('kelola-user', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'Users::index');
    $routes->post('', 'Users::store', ['filter' => 'csrf']);
    $routes->post('(:num)/ganti-password', 'Users::changePassword/$1', ['filter' => 'csrf']);
    $routes->post('(:num)/hapus', 'Users::delete/$1', ['filter' => 'csrf']);
});
