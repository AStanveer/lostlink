<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

use App\Controllers\AnalyzeController;
use App\Controllers\AuthController;
use App\Controllers\ClaimController;
use App\Controllers\DashboardController;
use App\Controllers\ItemController;
use App\Controllers\MatchController;
use App\Controllers\NotificationController;
use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;
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

// ── Public routes — no token needed ─────────────────────────────────
// Registration/login are themselves public 

$app->post('/register', AuthController::class . ':register');
$app->post('/login', AuthController::class . ':login');

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Public item browse
$app->get('/items', ItemController::class . ':index');
$app->get('/items/{id}', ItemController::class . ':show');
$app->post('/analyze', AnalyzeController::class . ':analyze');
$app->post('/vision/analyze', AnalyzeController::class . ':vision');

// ── Protected routes — every route in this group runs through ──────
// JwtMiddleware first. The middleware
// rejects the request with 401 before any of these methods even run if
// the Authorization header is missing/invalid 
$app->group('', function (RouteCollectorProxy $group) {
    // Items — full CRUD demo: GET is public above, these four are the
    // create/update/delete half, each requiring a valid token.
    $group->post('/items', ItemController::class . ':create');
    $group->put('/items/{id}', ItemController::class . ':update');
    $group->delete('/items/{id}', ItemController::class . ':delete');

    // Claims — the lost-and-found "handshake" workflow (submit -> approve
    // /reject -> mark received). See ClaimController for the state machine.
    $group->post('/claims', ClaimController::class . ':create');
    $group->get('/claims/item/{itemId}', ClaimController::class . ':getByItem');
    $group->put('/claims/{id}', ClaimController::class . ':updateStatus');
    $group->post('/claims/{id}/received', ClaimController::class . ':markReceived');

    // Matches — runs the scoring algorithm in MatchController over all
    // active lost/found pairs and returns ranked candidates.
    $group->get('/matches', MatchController::class . ':index');

    // Dashboard — aggregates a user's own reports + claims in one call.
    $group->get('/dashboard/{userId}', DashboardController::class . ':index');

    // Notifications — bell icon in the navbar polls this.
    $group->get('/notifications', NotificationController::class . ':index');
    $group->put('/notifications/{id}/read', NotificationController::class . ':markRead');
    $group->put('/notifications/read-all', NotificationController::class . ':markAllRead');

    // User profile — name/email/password self-service updates.
    $group->put('/users/{id}', UserController::class . ':update');

})->add(JwtMiddleware::class);

$app->run();