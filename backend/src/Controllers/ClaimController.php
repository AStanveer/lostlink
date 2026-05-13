<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClaimController
{
    public function create(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        if (empty($data['item_id']) || empty($data['description'])) {
            $response->getBody()->write(json_encode(['error' => 'item_id and description required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = Database::connect();
        $db->prepare(
            'INSERT INTO claim_requests (item_id, claimed_by, description, status) VALUES (?, ?, ?, ?)'
        )->execute([
            (int) $data['item_id'],
            $user->sub,
            $data['description'],
            'pending',
        ]);

        $response->getBody()->write(json_encode(['message' => 'Claim submitted']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
}
