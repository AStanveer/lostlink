<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MatchController
{
    public function index(Request $request, Response $response): Response
    {
        $db = Database::connect();

        // Fetch all active lost and found items separately
        $lost  = $db->query("SELECT * FROM items WHERE report_type = 'lost'  AND status = 'active'")->fetchAll();
        $found = $db->query("SELECT * FROM items WHERE report_type = 'found' AND status = 'active'")->fetchAll();

        $matches = [];

        foreach ($lost as $l) {
            foreach ($found as $f) {
                $score = 0;

                // Category match
                if ($l['category'] === $f['category']) {
                    $score += 2;
                }

                // Location match (partial)
                if (
                    $l['location'] && $f['location'] &&
                    stripos($l['location'], $f['location']) !== false ||
                    stripos($f['location'], $l['location']) !== false
                ) {
                    $score += 2;
                }

                // Keyword overlap in title/description
                $lWords = $this->tokenize($l['title'] . ' ' . $l['description']);
                $fWords = $this->tokenize($f['title'] . ' ' . $f['description']);
                $common = array_intersect($lWords, $fWords);
                $score += count($common);

                if ($score >= 2) {
                    $matches[] = [
                        'score'      => $score,
                        'lost_item'  => $l,
                        'found_item' => $f,
                    ];
                }
            }
        }

        // Sort by score descending
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        $response->getBody()->write(json_encode($matches));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function tokenize(string $text): array
    {
        $text  = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
        $words = array_filter(explode(' ', $text), fn($w) => strlen($w) > 3);
        return array_unique(array_values($words));
    }
}
