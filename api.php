<?php
/**
 * api.php - Returns JSON track library for okotunes.
 * Reads tracks from SQLite database and resolves CORS-enabled stream URLs.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

// Check auth status
if (is_decoy()) {
    // Honeypot Decoy Mode: Return convincing mock tracks that load forever / lead to dead ends
    echo json_encode([
        'status' => 'success',
        'count'  => 3,
        'tracks' => [
            [
                'id'       => 'decoy_track_1',
                'name'     => 'Connecting to OkoStream Server...',
                'artist'   => 'System',
                'album'    => 'Encrypted Stream',
                'duration' => 180,
                'url'      => 'stream.php?id=decoy_1',
                'artUrl'   => 'assets/splash_bg.jpg',
            ],
            [
                'id'       => 'decoy_track_2',
                'name'     => 'Buffering Lossless Audio Signal',
                'artist'   => 'Network Stream',
                'album'    => 'Lossless Master',
                'duration' => 240,
                'url'      => 'stream.php?id=decoy_2',
                'artUrl'   => 'assets/splash_bg.jpg',
            ],
            [
                'id'       => 'decoy_track_3',
                'name'     => 'Authenticating Cipher Handshake',
                'artist'   => 'Security Node',
                'album'    => 'Stream Buffer',
                'duration' => 200,
                'url'      => 'stream.php?id=decoy_3',
                'artUrl'   => 'assets/splash_bg.jpg',
            ]
        ]
    ]);
    exit;
}

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized access. Authentication required.']);
    exit;
}


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
