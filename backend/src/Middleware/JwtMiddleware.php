<?php
declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// This is the single choke point for "JWT-protected
// access". Slim runs this BEFORE the controller for every route registered
// inside the protected group in index.php. If it doesn't call $handler->handle(),
// the controller method never executes at all — auth is enforced here once,
// not re-implemented in every controller.
class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');

        // Expecting "Authorization: Bearer <token>" — no header, no token, no entry.
        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            $token = substr($auth, 7); // strip the "Bearer " prefix
            // JWT::decode verifies the signature against our server-side secret
            // AND checks the "exp" expiry claim — both invalid signature and an
            // expired token throw here and land in the catch block below.
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            // Attach the decoded payload (user_id as "sub", email, name) to the
            // request so controllers can read $request->getAttribute('user')
            // instead of re-decoding the token themselves.
            $request = $request->withAttribute('user', $decoded);
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
