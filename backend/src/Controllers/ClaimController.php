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

        // Prevent claiming your own item
        $stmt = $db->prepare('SELECT posted_by, title FROM items WHERE item_id = ?');
        $stmt->execute([(int) $data['item_id']]);
        $item = $stmt->fetch();

        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($item['posted_by'] === $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'You cannot claim your own item']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $proofPath = null;
        if (!empty($data['proof'])) {
            $uploadsDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $imageBytes = base64_decode($data['proof']);
            $filename   = uniqid('proof_', true) . '.jpg';
            file_put_contents($uploadsDir . $filename, $imageBytes);
            $proofPath  = '/uploads/' . $filename;
        }

        $db->prepare(
            'INSERT INTO claim_requests (item_id, claimed_by, description, proof_path, lost_item_id, status) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $data['item_id'],
            $user->sub,
            $data['description'],
            $proofPath,
            !empty($data['lost_item_id']) ? (int) $data['lost_item_id'] : null,
            'pending',
        ]);

        NotificationController::notify(
            (int) $item['posted_by'],
            'claim_submitted',
            'Someone submitted a claim for your item "' . $item['title'] . '".',
            '/dashboard?tab=reports'
        );

        $response->getBody()->write(json_encode(['message' => 'Claim submitted']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    // GET /claims/item/{itemId} — item owner fetches claims on their item
    public function getByItem(Request $request, Response $response, array $args): Response
    {
        $user   = $request->getAttribute('user');
        $itemId = (int) $args['itemId'];

        $db = Database::connect();

        // Verify the requester owns this item
        $stmt = $db->prepare('SELECT posted_by FROM items WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($item['posted_by'] !== $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare(
            'SELECT cr.*, u.email AS claimant_email, u.name AS claimant_name
             FROM claim_requests cr
             JOIN users u ON cr.claimed_by = u.user_id
             WHERE cr.item_id = ?
             ORDER BY cr.created_at DESC'
        );
        $stmt->execute([$itemId]);

        $claims = $stmt->fetchAll();

        $response->getBody()->write(json_encode($claims));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /claims/{id} — item owner approves or rejects a claim
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $user      = $request->getAttribute('user');
        $claimId   = (int) $args['id'];
        $data      = $request->getParsedBody();
        $newStatus = $data['status'] ?? '';

        if (!in_array($newStatus, ['approved', 'rejected'], true)) {
            $response->getBody()->write(json_encode(['error' => 'Status must be approved or rejected']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = Database::connect();

        // Fetch the claim and verify the authenticated user owns the item
        $stmt = $db->prepare(
            'SELECT cr.item_id, cr.claimed_by, i.posted_by, i.title
             FROM claim_requests cr
             JOIN items i ON cr.item_id = i.item_id
             WHERE cr.request_id = ?'
        );
        $stmt->execute([$claimId]);
        $row = $stmt->fetch();

        if (!$row) {
            $response->getBody()->write(json_encode(['error' => 'Claim not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($row['posted_by'] !== $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Update claim status
        $db->prepare('UPDATE claim_requests SET status = ? WHERE request_id = ?')
           ->execute([$newStatus, $claimId]);

        // If approved, mark item as claimed and reject all other pending claims
        // If approved, reject all other pending claims on the same item
        if ($newStatus === 'approved') {
            $db->prepare(
                'UPDATE claim_requests SET status = ? WHERE item_id = ? AND request_id != ? AND status = ?'
            )->execute(['rejected', $row['item_id'], $claimId, 'pending']);
        }

        NotificationController::notify(
            (int) $row['claimed_by'],
            'claim_' . $newStatus,
            'Your claim for "' . $row['title'] . '" was ' . $newStatus . '.',
            '/dashboard?tab=claims'
        );

        $response->getBody()->write(json_encode(['message' => 'Claim ' . $newStatus]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /claims/{id}/received — claimant confirms they got the item
    public function markReceived(Request $request, Response $response, array $args): Response
    {
        $user    = $request->getAttribute('user');
        $claimId = (int) $args['id'];

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT * FROM claim_requests WHERE request_id = ?');
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch();

        if (!$claim) {
            $response->getBody()->write(json_encode(['error' => 'Claim not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($claim['claimed_by'] !== $user->sub) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if ($claim['status'] !== 'approved') {
            $response->getBody()->write(json_encode(['error' => 'Claim is not approved yet']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Mark the found item as claimed
        $db->prepare('UPDATE items SET status = ? WHERE item_id = ?')
        ->execute(['claimed', $claim['item_id']]);

        // Mark the linked lost item as claimed if provided
        if (!empty($claim['lost_item_id'])) {
            $db->prepare('UPDATE items SET status = ? WHERE item_id = ? AND posted_by = ?')
            ->execute(['claimed', $claim['lost_item_id'], $user->sub]);
        }

        // Update claim status to received
        $db->prepare('UPDATE claim_requests SET status = ? WHERE request_id = ?')
        ->execute(['received', $claimId]);

        $itemStmt = $db->prepare('SELECT title, posted_by FROM items WHERE item_id = ?');
        $itemStmt->execute([$claim['item_id']]);
        $foundItem = $itemStmt->fetch();

        if ($foundItem) {
            NotificationController::notify(
                (int) $foundItem['posted_by'],
                'item_received',
                'The claimant confirmed they received "' . $foundItem['title'] . '".',
                '/dashboard?tab=reports'
            );
        }

        $response->getBody()->write(json_encode(['message' => 'Item marked as received']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}