<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');
$routes->setDefaultNamespace('App\Controllers');
$routes->get('/', 'Login::index');
$routes->setDefaultController('Login');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(true);
$routes->set404Override();
$routes->setAutoRoute(true);



$routes->get('dashboard-user', 'Dashboard::dashboard_user');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('presensi/masuk', 'PresensiController::masuk');
$routes->get('presensi/pulang', 'PresensiController::pulang');
$routes->get('presensi/history', 'PresensiController::history');
