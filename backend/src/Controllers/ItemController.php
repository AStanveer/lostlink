<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// This controller is the full CRUD demo for items —
// index/show (GET) are public, create/update/delete require a JWT and are
// wired up in index.php's protected group.
class ItemController
{
    // GET /items — builds the WHERE clause dynamically based on whichever
    // query params were sent (?q=&category=&location=&type=), but every
    // value is still passed through as a bound parameter, never concatenated
    // into the SQL string directly — that's what keeps free-text search safe.
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

    // GET /items/{id} — JOINs items to users so the response includes who
    // posted it (name/email), not just the raw posted_by foreign key id.
    public function show(Request $request, Response $response, array $args): Response
    {
        $db   = Database::connect();
        $stmt = $db->prepare(
            'SELECT i.*, u.email AS posted_by_email, u.name AS posted_by_name
             FROM items i
             JOIN users u ON i.posted_by = u.user_id
             WHERE i.item_id = ?'
        );
        $stmt->execute([(int) $args['id']]);
        $item = $stmt->fetch();

        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($item));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /items (protected) — the "Create" in CRUD. $user comes from
    // JwtMiddleware (see that file), not from the request body, so a caller
    // can never post an item "as" someone else.
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Required-field validation — reject early with 400 before touching
        // the database or the filesystem.
        $required = ['title', 'description', 'category', 'location', 'date', 'report_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "$field is required"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $user = $request->getAttribute('user');

        // Optional photo: decoded from base64 
        // written to disk; only the relative path is stored in
        // the DB, the actual bytes live under public/uploads/.
        $imagePath = null;
        if (!empty($data['image'])) {
            $uploadsDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $imageBytes = base64_decode($data['image']);
            $filename   = uniqid('item_', true) . '.jpg';
            file_put_contents($uploadsDir . $filename, $imageBytes);
            $imagePath = '/uploads/' . $filename;
        }

        $db = Database::connect();
        $db->prepare(
            'INSERT INTO items (title, description, category, location, date, report_type, status, image_path, posted_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['location'],
            $data['date'],
            $data['report_type'],
            'active',
            $imagePath,
            $user->sub,
        ]);

        $id = $db->lastInsertId();

        // Bonus feature hook: as soon as this item exists, check it against
        // every active opposite-type item (lost vs found) using the same
        // scoring logic the Matches tab uses, and notify the lost item's
        // owner immediately if a likely match shows up. See MatchController.
        (new MatchController())->notifyMatchesForNewItem([
            'item_id'     => $id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'location'    => $data['location'],
            'date'        => $data['date'],
            'report_type' => $data['report_type'],
            'status'      => 'active',
            'posted_by'   => $user->sub,
        ]);

        $response->getBody()->write(json_encode(['message' => 'Item reported', 'item_id' => $id]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    // PUT /items/{id} (protected) — the "Update" in CRUD.
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

        // Partial update: any field not sent falls back to its current
        // value via ??, so the frontend can send just the fields it changed.
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

    // DELETE /items/{id} (protected) — the "Delete" in CRUD, same ownership
    // check pattern as update() above.
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
