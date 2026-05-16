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
        $stmt = $db->prepare('SELECT posted_by FROM items WHERE item_id = ?');
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

        // Handle optional proof image (base64)
        $proofData = null;
        if (!empty($data['proof'])) {
            $proofData = base64_decode($data['proof']);
        }

        $db->prepare(
            'INSERT INTO claim_requests (item_id, claimed_by, description, proof, status) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            (int) $data['item_id'],
            $user->sub,
            $data['description'],
            $proofData,
            'pending',
        ]);

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
            'SELECT cr.*, u.email AS claimant_email
             FROM claim_requests cr
             JOIN users u ON cr.claimed_by = u.user_id
             WHERE cr.item_id = ?
             ORDER BY cr.created_at DESC'
        );
        $stmt->execute([$itemId]);

        $claims = $stmt->fetchAll();

        // Strip binary proof from listing — too heavy; served separately if needed
        foreach ($claims as &$c) {
            unset($c['proof']);
        }

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
            'SELECT cr.item_id, i.posted_by
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

        // Mark item as claimed
        $db->prepare('UPDATE items SET status = ? WHERE item_id = ?')
        ->execute(['claimed', $claim['item_id']]);

        // Update claim status to received
        $db->prepare('UPDATE claim_requests SET status = ? WHERE request_id = ?')
        ->execute(['received', $claimId]);

        $response->getBody()->write(json_encode(['message' => 'Item marked as received']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}