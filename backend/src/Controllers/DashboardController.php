<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController
{
    public function index(Request $request, Response $response, array $args): Response
    {
        $user   = $request->getAttribute('user');
        $userId = (int) $args['userId'];

        if ($user->sub !== $userId) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $db = Database::connect();

        $stmtReports = $db->prepare('SELECT * FROM items WHERE posted_by = ? ORDER BY date DESC');
        $stmtReports->execute([$userId]);

        $stmtClaims = $db->prepare(
            'SELECT cr.*, i.title AS item_title FROM claim_requests cr
             JOIN items i ON cr.item_id = i.item_id
             WHERE cr.claimed_by = ? ORDER BY cr.request_id DESC'
        );
        $stmtClaims->execute([$userId]);

        $response->getBody()->write(json_encode([
            'reports' => $stmtReports->fetchAll(),
            'claims'  => $stmtClaims->fetchAll(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
