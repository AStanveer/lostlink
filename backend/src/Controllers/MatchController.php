<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MatchController
{
    // Weight configuration for different match criteria
    private const WEIGHTS = [
        'exact_category' => 10,
        'similar_category' => 5,
        'exact_location' => 8,
        'similar_location' => 4,
        'exact_title_match' => 6,
        'partial_title_match' => 3,
        'keyword_match' => 2,
        'time_proximity' => 3,
        'description_similarity' => 4,
    ];

    // Category synonyms for fuzzy matching
    private const CATEGORY_SYNONYMS = [
        'electronics' => ['laptop', 'phone', 'tablet', 'computer', 'charger', 'headphone', 'earphone', 'powerbank'],
        'accessories' => ['watch', 'jewellery', 'jewelry', 'bag', 'wallet', 'belt', 'scarf', 'glasses', 'sunglasses'],
        'documents' => ['id', 'passport', 'certificate', 'license', 'card', 'student id', 'identity'],
        'books' => ['textbook', 'notebook', 'journal', 'magazine', 'novel'],
        'clothing' => ['jacket', 'coat', 'shirt', 'dress', 'shoe', 'sneaker', 'uniform'],
        'keys' => ['keychain', 'keyring', 'car key', 'house key', 'office key'],
        'bags' => ['backpack', 'handbag', 'tote', 'sling bag', 'luggage', 'suitcase'],
    ];

    public function index(Request $request, Response $response): Response
    {
        $db = Database::connect();
        $params = $request->getQueryParams();
        
        // Get date range filter 
        $daysLimit = isset($params['days']) ? (int)$params['days'] : 0;
        
        // Fetch active lost and found items with date filter
        $lostQuery = "SELECT * FROM items WHERE report_type = 'lost' AND status = 'active'";
        $foundQuery = "SELECT * FROM items WHERE report_type = 'found' AND status = 'active'";
        
        if ($daysLimit > 0) {
            $dateFilter = " AND date >= DATE_SUB(NOW(), INTERVAL {$daysLimit} DAY)";
            $lostQuery .= $dateFilter;
            $foundQuery .= $dateFilter;
        }
        
        $lost = $db->query($lostQuery)->fetchAll();
        $found = $db->query($foundQuery)->fetchAll();

        if (empty($lost) || empty($found)) {
            $response->getBody()->write(json_encode([
                'matches' => [],
                'message' => 'No active lost or found items to match'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $matches = [];

        foreach ($lost as $lostItem) {
            foreach ($found as $foundItem) {
                $score = $this->calculateMatchScore($lostItem, $foundItem);
                
                // Only include matches above threshold
                if ($score >= 18) {
                    $matches[] = [
                        'score' => $score,
                        'match_percentage' => min(100, round(($score / 40) * 100)),
                        'lost_item' => $lostItem,
                        'found_item' => $foundItem,
                        'match_details' => $this->getMatchDetails($lostItem, $foundItem),
                    ];
                }
            }
        }

        // Sort by score descending
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
        
        // Limit results
        $matches = array_slice($matches, 0, 50);

        $response->getBody()->write(json_encode([
            'matches' => $matches,
            'total_matches' => count($matches),
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Called right after a new item is created — checks it against active
    // opposite-type items and notifies the lost item's owner of any match.
    public function notifyMatchesForNewItem(array $newItem): void
    {
        $db = Database::connect();
        $oppositeType = $newItem['report_type'] === 'lost' ? 'found' : 'lost';

        $stmt = $db->prepare(
            "SELECT * FROM items WHERE report_type = ? AND status = 'active' AND posted_by != ?"
        );
        $stmt->execute([$oppositeType, $newItem['posted_by']]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $candidate) {
            $lost  = $newItem['report_type'] === 'lost' ? $newItem : $candidate;
            $found = $newItem['report_type'] === 'found' ? $newItem : $candidate;

            $score = $this->calculateMatchScore($lost, $found);

            if ($score >= 18) {
                NotificationController::notify(
                    (int) $lost['posted_by'],
                    'item_match',
                    'A possible match was found for your lost report "' . $lost['title'] . '": "' . $found['title'] . '".',
                    '/dashboard?tab=matches'
                );
            }
        }
    }

    private function calculateMatchScore(array $lost, array $found): float
    {
        $score = 0;
        $locationScore = $this->matchLocations($lost['location'], $found['location']);
        if ($locationScore === 0.0) return 0;

        $score = $locationScore;

   
        $categoryScore = $this->matchCategories($lost['category'], $found['category']);
        if ($categoryScore === 0.0) return 0;

        $score = $locationScore + $categoryScore;

    

        // 3. Title matching
        $titleScore = $this->matchTitles($lost['title'], $found['title']);
        $score += $titleScore;

        // 4. Description similarity
        $descScore = $this->matchDescriptions($lost['description'], $found['description']);
        $score += $descScore;

        // 5. Time proximity (items reported close to each other)
        $timeScore = $this->matchTimeProximity($lost['date'], $found['date']);
        $score += $timeScore;

        // 6. Keyword matching (combined title + description)
        $keywordScore = $this->matchKeywords(
            $lost['title'] . ' ' . $lost['description'],
            $found['title'] . ' ' . $found['description']
        );
        $score += $keywordScore;

        return round($score, 2);
    }

    private function matchCategories(?string $cat1, ?string $cat2): float
    {
        if (empty($cat1) || empty($cat2)) return 0;

        $cat1 = strtolower(trim($cat1));
        $cat2 = strtolower(trim($cat2));

        // Exact match
        if ($cat1 === $cat2) {
            return self::WEIGHTS['exact_category'];
        }

        // Check if categories are synonyms
        foreach (self::CATEGORY_SYNONYMS as $mainCategory => $synonyms) {
            $inCat1 = $cat1 === $mainCategory || in_array($cat1, $synonyms);
            $inCat2 = $cat2 === $mainCategory || in_array($cat2, $synonyms);
            
            if ($inCat1 && $inCat2) {
                return self::WEIGHTS['similar_category'];
            }
        }

        // Check if one contains the other
        if (strpos($cat1, $cat2) !== false || strpos($cat2, $cat1) !== false) {
            return self::WEIGHTS['similar_category'] * 0.6;
        }

        return 0;
    }

    private function matchLocations(?string $loc1, ?string $loc2): float
    {
        if (empty($loc1) || empty($loc2)) return 0;

        $loc1 = strtolower(trim($loc1));
        $loc2 = strtolower(trim($loc2));

        // Exact match
        if ($loc1 === $loc2) {
            return self::WEIGHTS['exact_location'];
        }

        // One contains the other (e.g., "N28" vs "N28 Building")
        if (strpos($loc1, $loc2) !== false || strpos($loc2, $loc1) !== false) {
            return self::WEIGHTS['similar_location'];
        }

        // Check if they share common building/area identifiers
        $tokens1 = $this->tokenize($loc1);
        $tokens2 = $this->tokenize($loc2);
        $common = array_intersect($tokens1, $tokens2);

        if (!empty($common)) {
            return self::WEIGHTS['similar_location'] * (count($common) / max(count($tokens1), count($tokens2)));
        }

        return 0;
    }

    private function matchTitles(string $title1, string $title2): float
    {
        if (empty($title1) || empty($title2)) return 0;

        $title1 = strtolower(trim($title1));
        $title2 = strtolower(trim($title2));

        // Exact match
        if ($title1 === $title2) {
            return self::WEIGHTS['exact_title_match'];
        }

        // One contains the other
        if (strpos($title1, $title2) !== false || strpos($title2, $title1) !== false) {
            return self::WEIGHTS['partial_title_match'];
        }

        // Similarity percentage
        similar_text($title1, $title2, $percent);
        if ($percent > 70) {
            return self::WEIGHTS['partial_title_match'] * ($percent / 100);
        }

        return 0;
    }

    private function matchDescriptions(?string $desc1, ?string $desc2): float
    {
        if (empty($desc1) || empty($desc2)) return 0;

        $desc1 = strtolower(trim($desc1));
        $desc2 = strtolower(trim($desc2));

        // Check similarity
        similar_text($desc1, $desc2, $percent);
        
        if ($percent > 50) {
            return self::WEIGHTS['description_similarity'] * ($percent / 100);
        }

        // Check for common keywords
        $tokens1 = $this->tokenize($desc1);
        $tokens2 = $this->tokenize($desc2);
        $common = array_intersect($tokens1, $tokens2);

        if (!empty($common)) {
            $maxTokens = max(count($tokens1), count($tokens2));
            $ratio = count($common) / ($maxTokens > 0 ? $maxTokens : 1);
            return self::WEIGHTS['description_similarity'] * $ratio * 0.5;
        }

        return 0;
    }

    private function matchTimeProximity(string $date1, string $date2): float
    {
        try {
            $time1 = strtotime($date1);
            $time2 = strtotime($date2);
            
            if ($time1 === false || $time2 === false) return 0;
            
            $daysDiff = abs($time1 - $time2) / (60 * 60 * 24);
            
            // Items reported within 3 days get full points
            if ($daysDiff <= 3) {
                return self::WEIGHTS['time_proximity'];
            }
            
            // Items reported within 7 days get partial points
            if ($daysDiff <= 7) {
                return self::WEIGHTS['time_proximity'] * 0.6;
            }
            
            // Items reported within 14 days get small bonus
            if ($daysDiff <= 14) {
                return self::WEIGHTS['time_proximity'] * 0.3;
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function matchKeywords(string $text1, string $text2): float
    {
        $tokens1 = $this->tokenize($text1);
        $tokens2 = $this->tokenize($text2);
        
        if (empty($tokens1) || empty($tokens2)) return 0;
        
        $common = array_intersect($tokens1, $tokens2);
        $totalCommon = count($common);
        
        if ($totalCommon === 0) return 0;
        
        // Calculate Jaccard similarity
        $union = count(array_unique(array_merge($tokens1, $tokens2)));
        $jaccard = $totalCommon / ($union > 0 ? $union : 1);
        
        return self::WEIGHTS['keyword_match'] * $jaccard * 2;
    }

    private function getMatchDetails(array $lost, array $found): array
    {
        $details = [];
        
        // Category match
        if ($lost['category'] === $found['category']) {
            $details[] = 'Same category: ' . $lost['category'];
        } elseif ($this->areCategoriesSimilar($lost['category'], $found['category'])) {
            $details[] = 'Similar category: ' . $lost['category'] . ' ↔ ' . $found['category'];
        }
        
        // Location match
        if ($lost['location'] === $found['location']) {
            $details[] = 'Same location: ' . $lost['location'];
        } elseif (!empty($lost['location']) && !empty($found['location']) && 
                  (strpos($lost['location'], $found['location']) !== false || 
                   strpos($found['location'], $lost['location']) !== false)) {
            $details[] = 'Nearby location: ' . $lost['location'] . ' ↔ ' . $found['location'];
        }
        
        // Title match
        if (strpos(strtolower($lost['title']), strtolower($found['title'])) !== false ||
            strpos(strtolower($found['title']), strtolower($lost['title'])) !== false) {
            $details[] = 'Similar title: "' . $lost['title'] . '" ↔ "' . $found['title'] . '"';
        }
        
        // Time proximity
        $daysDiff = abs(strtotime($lost['date']) - strtotime($found['date'])) / (60 * 60 * 24);
        if ($daysDiff <= 7) {
            $details[] = 'Reported within ' . round($daysDiff) . ' days of each other';
        }
        
        return $details;
    }

    private function areCategoriesSimilar(?string $cat1, ?string $cat2): bool
    {
        if (empty($cat1) || empty($cat2)) return false;
        
        $cat1 = strtolower(trim($cat1));
        $cat2 = strtolower(trim($cat2));
        
        foreach (self::CATEGORY_SYNONYMS as $mainCategory => $synonyms) {
            $inCat1 = $cat1 === $mainCategory || in_array($cat1, $synonyms);
            $inCat2 = $cat2 === $mainCategory || in_array($cat2, $synonyms);
            
            if ($inCat1 && $inCat2) {
                return true;
            }
        }
        
        return false;
    }

    private function tokenize(string $text): array
    {
        // Remove special characters and convert to lowercase
        $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
        $words = array_filter(explode(' ', $text), function($word) {
            // Filter out common words and very short words
            $stopWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'use', 'way', 'who', 'any', 'new', 'see', 'may', 'get', 'via', 'etc'];
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        return array_unique(array_values($words));
    }
}