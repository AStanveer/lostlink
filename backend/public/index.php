<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

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

// Public routes
$app->post('/register', \App\Controllers\AuthController::class . ':register');
$app->post('/login',    \App\Controllers\AuthController::class . ':login');

// OPTIONS preflight for CORS
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Public item browse
$app->get('/items', \App\Controllers\ItemController::class . ':index');
$app->post('/analyze', \App\Controllers\AnalyzeController::class . ':analyze');

// Protected routes (JWT required)
$app->group('', function (RouteCollectorProxy $group) {
    // Items
    $group->post('/items',        \App\Controllers\ItemController::class . ':create');
    $group->put('/items/{id}',    \App\Controllers\ItemController::class . ':update');
    $group->delete('/items/{id}', \App\Controllers\ItemController::class . ':delete');

    // Claims
    $group->post('/claims',                    \App\Controllers\ClaimController::class . ':create');
    $group->get('/claims/item/{itemId}',       \App\Controllers\ClaimController::class . ':getByItem');
    $group->put('/claims/{id}',                \App\Controllers\ClaimController::class . ':updateStatus');

    // Matches
    $group->get('/matches', \App\Controllers\MatchController::class . ':index');

    // Dashboard
    $group->get('/dashboard/{userId}', \App\Controllers\DashboardController::class . ':index');
})->add(\App\Middleware\JwtMiddleware::class);

$app->run();