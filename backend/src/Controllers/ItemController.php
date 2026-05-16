<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ItemController
{
    public function index(Request $request, Response $response): Response
    {
        $db     = Database::connect();
        $params = $request->getQueryParams();

        $sql    = 'SELECT * FROM items WHERE 1=1';
        $binds  = [];

        if (!empty($params['q'])) {
            $sql   .= ' AND (title LIKE ? OR description LIKE ?)';
            $binds[] = '%' . $params['q'] . '%';
            $binds[] = '%' . $params['q'] . '%';
        }
        if (!empty($params['category'])) {
            $sql   .= ' AND category = ?';
            $binds[] = $params['category'];
        }
        if (!empty($params['location'])) {
            $sql   .= ' AND location LIKE ?';
            $binds[] = '%' . $params['location'] . '%';
        }
        if (!empty($params['type'])) {
            $sql   .= ' AND report_type = ?';
            $binds[] = $params['type'];
        }

        $sql .= ' ORDER BY date DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);

        $response->getBody()->write(json_encode($stmt->fetchAll()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $required = ['title', 'description', 'category', 'location', 'date', 'report_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "$field is required"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $user = $request->getAttribute('user');

        $db = Database::connect();
        $db->prepare(
            'INSERT INTO items (title, description, category, location, date, report_type, status, posted_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['location'],
            $data['date'],
            $data['report_type'],
            'active',
            $user->sub,
        ]);

        $id = $db->lastInsertId();
        $response->getBody()->write(json_encode(['message' => 'Item reported', 'item_id' => $id]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user   = $request->getAttribute('user');
        $data   = $request->getParsedBody();
        $itemId = (int) $args['id'];

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT * FROM items WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item || $item['posted_by'] !== $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $db->prepare(
            'UPDATE items SET title=?, description=?, category=?, location=?, date=?, status=? WHERE item_id=?'
        )->execute([
            $data['title']       ?? $item['title'],
            $data['description'] ?? $item['description'],
            $data['category']    ?? $item['category'],
            $data['location']    ?? $item['location'],
            $data['date']        ?? $item['date'],
            $data['status']      ?? $item['status'],
            $itemId,
        ]);

        $response->getBody()->write(json_encode(['message' => 'Item updated']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user   = $request->getAttribute('user');
        $itemId = (int) $args['id'];

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT posted_by FROM items WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item || $item['posted_by'] !== $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $db->prepare('DELETE FROM items WHERE item_id = ?')->execute([$itemId]);
        $response->getBody()->write(json_encode(['message' => 'Item deleted']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
