<?php
/**
 * api.php - Returns JSON track library for okotunes.
 * Reads tracks from SQLite database and resolves CORS-enabled stream URLs.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

try {
    $dbTracks = getAllTracks();
    $r2 = getR2();
    $files = [];

    foreach ($dbTracks as $track) {
        $trackId = $track['id'];
        $r2Key   = $track['r2_key'];
        $artKey  = $track['art_r2_key'] ?? '';

        // Serve stream through stream.php proxy to enforce Access-Control-Allow-Origin: * for Web Audio API
        $streamUrl = "stream.php?id=" . rawurlencode($trackId);
        $artUrl    = !empty($artKey) ? $r2->getUrl($artKey) : "art.php?id=" . rawurlencode($trackId);

        $files[] = [
            'id'       => $trackId,
            'name'     => $track['title'],
            'artist'   => $track['artist'] ?? 'Unknown Artist',
            'album'    => $track['album'] ?? 'Unknown Album',
            'duration' => (float)($track['duration'] ?? 0),
            'url'      => $streamUrl,
            'artUrl'   => $artUrl,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'count'  => count($files),
        'tracks' => $files
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error'  => 'Failed fetching library: ' . $e->getMessage()
    ]);
}
