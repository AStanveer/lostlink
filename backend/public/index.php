<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Routes
$app->post('/register', \App\Controllers\AuthController::class . ':register');
$app->post('/login',    \App\Controllers\AuthController::class . ':login');

$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/items',          \App\Controllers\ItemController::class . ':index');
    $group->post('/items',         \App\Controllers\ItemController::class . ':create');
    $group->put('/items/{id}',     \App\Controllers\ItemController::class . ':update');
    $group->delete('/items/{id}',  \App\Controllers\ItemController::class . ':delete');

    $group->post('/claims',        \App\Controllers\ClaimController::class . ':create');
    $group->get('/matches',        \App\Controllers\MatchController::class . ':index');
    $group->get('/dashboard/{userId}', \App\Controllers\DashboardController::class . ':index');
})->add(\App\Middleware\JwtMiddleware::class);

$app->run();
