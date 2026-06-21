<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    // "input validation" 
    // three independent checks (presence, email format, password
    // length) each return a distinct 400 with a specific message, before
    // we ever touch the database.
    public function register(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $email    = trim($data['email'] ?? '');
        $name     = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password || !$name) {
            $response->getBody()->write(json_encode(['error' => 'Name, email and password required']));
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
        // Prepared statement with a bound parameter — not string-concatenated
        // SQL is what keeps this immune to SQL injection.
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Email already registered']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        // Security highlight: NEVER store the raw password. bcrypt hashes
        // it one-way with a built-in random salt, so even with full DB access
        // nobody can read back the original password.
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO users (email, name, password) VALUES (?, ?, ?)')->execute([$email, $name, $hash]);

        $response->getBody()->write(json_encode(['message' => 'User registered successfully']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    // The start of the JWT auth flow. Login
    // verifies the password, then issues a signed token the client will
    // attach to every future request — see JwtMiddleware for the other half.
    public function login(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $db   = Database::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // password_verify checks the plaintext against the bcrypt hash — the
        // same generic "Invalid credentials" message is used whether the
        // email doesn't exist or the password is wrong, so an attacker can't
        // use the error to enumerate which emails are registered.
        if (!$user || !password_verify($password, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // "sub" (subject) is the standard JWT claim for the authenticated
        // user's ID 
        $payload = [
            'sub' => $user['user_id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24, // token self-expires after 24 hours
        ];

        // Signed (not encrypted) with our server-side secret — anyone can
        // read the payload, but nobody can forge or modify it without the
        // secret, which is what JwtMiddleware verifies on the way back in.
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $token,
            'user'  => ['id' => $user['user_id'], 'email' => $user['email'], 'name' => $user['name']]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
