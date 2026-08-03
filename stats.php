<?php
/**
 * stats.php - Play-count API for okotunes.
 *
 * GET  stats.php          → returns all play counts as JSON
 * POST stats.php          → increments count for a track, returns new count
 *       body: { "url": "stream.php?file=...", "name": "Track Name" }
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $data = getStats();
    $counts = $data['counts'] ?? [];
    
    arsort($counts);
    
    $map = [];
    foreach ($counts as $url => $count) {
        $map[$url] = (int)$count;
    }
    
    echo json_encode(['counts' => $map]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $url  = $body['url']  ?? null;
    $name = $body['name'] ?? '';

    if (!$url) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing url']);
        exit;
    }

    $trackId = md5($url);
    recordPlay($trackId);
    $data = getStats();

    echo json_encode(['url' => $url, 'count' => $data['counts'][$trackId] ?? 1]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
