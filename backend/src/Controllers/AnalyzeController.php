<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AnalyzeController
{
    public function analyze(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $imageB64 = $data['image'] ?? '';

        if (!$imageB64) {
            $response->getBody()->write(json_encode(['error' => 'No image provided']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $imageData = base64_decode($imageB64);
        $token     = $_ENV['HF_TOKEN'] ?? '';

        // Use ViT image classification — reliable and fast
        $ch = curl_init('https://api-inference.huggingface.co/models/google/vit-base-patch16-224');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $imageData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/octet-stream',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($result === false) {
            $response->getBody()->write(json_encode(['error' => 'Curl failed: ' . $curlError]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $decoded = json_decode($result, true);

        // Could not parse HF response — return raw for debugging
        if ($decoded === null) {
            $response->getBody()->write(json_encode([
                'error' => 'HuggingFace returned non-JSON',
                'raw'   => substr($result, 0, 1000),
                'http'  => $httpCode,
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        // Model still loading — tell frontend to retry
        if (isset($decoded['error'])) {
            if (str_contains($decoded['error'], 'loading') || str_contains($decoded['error'], 'Load')) {
                $response->getBody()->write(json_encode(['loading' => true]));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            }
            $response->getBody()->write(json_encode(['error' => $decoded['error']]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        // Always return 200 so Slim doesn't override with HTML error page
        $response->getBody()->write(json_encode($decoded));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
