<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            $response->getBody()->write(json_encode(['error' => 'Email and password required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email address']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (strlen($password) < 8) {
            $response->getBody()->write(json_encode(['error' => 'Password must be at least 8 characters']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Email already registered']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO users (email, password) VALUES (?, ?)')->execute([$email, $hash]);

        $response->getBody()->write(json_encode(['message' => 'User registered successfully']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function login(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $payload = [
            'sub' => $user['user_id'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24, // 24 hours
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $token,
            'user'  => ['id' => $user['user_id'], 'email' => $user['email']]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
