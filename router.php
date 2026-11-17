<?php

namespace Paheko\Plugin\Perm;

use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Web\Router;

/*--------------*/
/*     CRON     */
/*--------------*/

$perm = Perm::getInstance();
$perm->asyncCron();

/*--------------*/
/*    ROUTES    */
/*  First found */
/*  First sent  */
/*--------------*/

$base = Router::getRequestURI(Plugins::getPrivateURL($this->name));

// Redirects all /app/ route to the index.html
// Vue.js deals with its own router
if (str_starts_with(Router::getRequestURI(), $base . 'app')) {
    include 'admin/app/index.php';
    exit;
}

$router = new ApiRouter($base . 'api');

/** ASSOCIATION */
$router->get('/organization/config', [Organization::class, 'config']);

/**  ACCOUNTING */
$router->get('/accounting/accounts', [Accounting::class, 'accounts']);
$router->get('/accounting/years', [Accounting::class, 'years']);
$router->get('/accounting/types', [Accounting::class, 'types']);
$router->get('/accounting/transactions', [Accounting::class, 'transactions']);
$router->get('/accounting/transactions/{id}', [Accounting::class, 'transaction']);
$router->get('/accounting/balances', [Accounting::class, 'balances']);
$router->get('/accounting/templates', [Accounting::class, 'templates']);
$router->get('/accounting/model', [$perm, 'getAccConfig']);

$router->post('/accounting/transactions', [Accounting::class, 'save']);
$router->post('/accounting/accounts', [Accounting::class, 'createAccount']);

/**  USER */
$router->get('/user', [User::class, 'getAll']);
$router->get('/user/services', [User::class, 'services']);
$router->get('/user/fields', [User::class, 'fields']);
$router->get('/user/{id}', [User::class, 'get']);

$router->post('/user', [User::class, 'save']);
$router->post('/user/{id}', [User::class, 'save']);

/** EQUIPMENT */
$router->get('/equipment', [Equipment::class, 'getAll']);
$router->get('/equipment/category', [Equipment::class, 'getCategories']);
$router->get('/equipment/config', [Equipment::class, 'getConfig']);
$router->get('/equipment/{key}', [Equipment::class, 'get']);

$router->post('/equipment/{key}', [Equipment::class, 'addMovement']);

/** HELLOASSO */
$router->get('/helloasso/forms', [HelloAsso::class, 'list']);

/** STATS */
$router->get('/stats', [Stats::class, 'all']);

$router->all('/[...path]', [$router, 'er404']);

throw new UserException('Cette page ne semble pas exister. ', 404);
