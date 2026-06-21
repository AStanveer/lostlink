<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function update(Request $request, Response $response, array $args): Response
    {
        $authUser = $request->getAttribute('user');
        $userId   = (int) $args['id'];

        if ($authUser->sub !== $userId) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $data  = $request->getParsedBody();
        $name  = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');

        if (!$name || !$email) {
            $response->getBody()->write(json_encode(['error' => 'Name and email required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email address']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = Database::connect();

        // Email must stay unique across other users
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Email already in use']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $newPassword = $data['new_password'] ?? '';

        if ($newPassword !== '') {
            $currentPassword = $data['current_password'] ?? '';

            $stmt = $db->prepare('SELECT password FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($currentPassword, $row['password'])) {
                $response->getBody()->write(json_encode(['error' => 'Current password is incorrect']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            if (strlen($newPassword) < 8) {
                $response->getBody()->write(json_encode(['error' => 'New password must be at least 8 characters']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?')
               ->execute([$name, $email, $hash, $userId]);
        } else {
            $db->prepare('UPDATE users SET name = ?, email = ? WHERE user_id = ?')
               ->execute([$name, $email, $userId]);
        }

        $response->getBody()->write(json_encode([
            'message' => 'Profile updated',
            'user'    => ['id' => $userId, 'name' => $name, 'email' => $email],
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
