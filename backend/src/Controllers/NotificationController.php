<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController
{
    public static function notify(int $userId, string $type, string $message, ?string $link = null): void
    {
        $db = Database::connect();
        $db->prepare(
            'INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $type, $message, $link]);
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $db   = Database::connect();

        $stmt = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30'
        );
        $stmt->execute([$user->sub]);
        $notifications = $stmt->fetchAll();

        $countStmt = $db->prepare(
            'SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $countStmt->execute([$user->sub]);
        $unread = (int) $countStmt->fetch()['unread'];

        $response->getBody()->write(json_encode([
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function markRead(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id   = (int) $args['id'];
        $db   = Database::connect();

        $db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?'
        )->execute([$id, $user->sub]);

        $response->getBody()->write(json_encode(['message' => 'Marked as read']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function markAllRead(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $db   = Database::connect();

        $db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        )->execute([$user->sub]);

        $response->getBody()->write(json_encode(['message' => 'All marked as read']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
