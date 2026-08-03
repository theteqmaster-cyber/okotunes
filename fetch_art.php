<?php
/**
 * fetch_art.php - Cover Art Register Batch Fetcher for okotunes.
 * Automatically cleans track titles, queries iTunes & Deezer open APIs,
 * and caches high-resolution album artwork directly in Cloudflare R2.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

$batchSize = isset($_GET['batch']) ? max(1, min(10, (int)$_GET['batch'])) : 4;
$trackId   = $_GET['id'] ?? null;

$pendingTracks = [];
if ($trackId) {
    $db = getDb();
    $stmt = $db->prepare("SELECT id, title, artist, album, r2_key FROM tracks WHERE id = :id");
    $stmt->execute([':id' => $trackId]);
    $row = $stmt->fetch();
    if ($row) $pendingTracks = [$row];
} else {
    $pendingTracks = getPendingArtTracks($batchSize);
}

if (empty($pendingTracks)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'No pending tracks requiring artwork',
        'processed' => 0,
        'remaining_pending' => 0
    ]);
    exit;
}

$r2 = getR2();
$processed = 0;
$fetched = 0;
$results = [];

foreach ($pendingTracks as $track) {
    $id     = $track['id'];
    $raw    = $track['title'];
    $artist = $track['artist'] !== 'Unknown Artist' ? $track['artist'] : '';

    $cleanQuery = sanitizeSearchQuery($raw, $artist);
    $imageUrl   = searchAlbumArtUrl($cleanQuery);

    if ($imageUrl) {
        $imgData = fetchRemoteImageData($imageUrl);
        if ($imgData !== null) {
            $artR2Key = "art/" . $id . ".jpg";
            $r2Uploaded = false;

            if ($r2->isConfigured()) {
                $r2Uploaded = $r2->uploadObject($artR2Key, $imgData, 'image/jpeg', true);
            }

            if (!$r2Uploaded) {
                // Fallback to local disk cache
                $cacheDir = __DIR__ . '/cache/art';
                if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
                file_put_contents($cacheDir . '/' . md5($id) . '.jpg', $imgData);
            }

            updateTrackArtStatus($id, 'fetched', $artR2Key);
            $fetched++;
            $results[] = [
                'id' => $id,
                'title' => $raw,
                'query' => $cleanQuery,
                'status' => 'fetched',
                'artUrl' => $r2->getUrl($artR2Key)
            ];
        } else {
            updateTrackArtStatus($id, 'failed', '');
            $results[] = ['id' => $id, 'title' => $raw, 'status' => 'failed_download'];
        }
    } else {
        updateTrackArtStatus($id, 'failed', '');
        $results[] = ['id' => $id, 'title' => $raw, 'query' => $cleanQuery, 'status' => 'not_found'];
    }

    $processed++;
}

// Calculate remaining pending count
$remaining = count(getPendingArtTracks(50));

echo json_encode([
    'status' => 'success',
    'processed' => $processed,
    'fetched' => $fetched,
    'remaining_pending' => $remaining,
    'results' => $results
]);

/**
 * Strips clutter tags like (MP3_160K), [Official Music Video], (8D Audio), (1), etc.
 */
function sanitizeSearchQuery(string $rawTitle, string $artist = ''): string {
    $clean = $rawTitle;

    // Remove file extensions
    $clean = preg_replace('/\.(mp3|flac|wav|m4a|aac|ogg)$/i', '', $clean);

    // Remove common clutter tags
    $clutterPatterns = [
        '/\(MP3[_\s]?\d+K\)/i',
        '/\[MP3[_\s]?\d+K\]/i',
        '/_\d+K$/i',
        '/\(Official\s+(Music\s+)?Video\)/i',
        '/\[Official\s+(Music\s+)?Video\]/i',
        '/\(Official\s+Audio\)/i',
        '/\[Official\s+Audio\]/i',
        '/\(8D\s+Audio\)/i',
        '/\[8D\s+Audio\]/i',
        '/\(320kbps\)/i',
        '/\(160kbps\)/i',
        '/\(\d+\)$/', // e.g. (1), (2)
        '/\[\d+\]$/'
    ];

    foreach ($clutterPatterns as $pattern) {
        $clean = preg_replace($pattern, '', $clean);
    }

    // Replace underscores and dashes with spaces
    $clean = str_replace(['_', '-'], ' ', $clean);

    // Remove excess whitespace
    $clean = trim(preg_replace('/\s+/', ' ', $clean));

    if (!empty($artist) && stripos($clean, $artist) === false) {
        $clean = $artist . ' ' . $clean;
    }

    return $clean;
}

/**
 * Searches iTunes Search API & Deezer API for high-resolution cover art URL
 */
function searchAlbumArtUrl(string $query): ?string {
    if (empty($query)) return null;

    // 1. Try iTunes Search API
    $iTunesUrl = 'https://itunes.apple.com/search?term=' . rawurlencode($query) . '&entity=song&limit=1';
    $json = fetchRemoteJson($iTunesUrl);

    if ($json && isset($json['results'][0]['artworkUrl100'])) {
        $url100 = $json['results'][0]['artworkUrl100'];
        // Upgrade 100x100 thumbnail to 600x600 high resolution artwork
        return str_replace('100x100bb', '600x600bb', $url100);
    }

    // 2. Fallback: Try Deezer API
    $deezerUrl = 'https://api.deezer.com/search?q=' . rawurlencode($query) . '&limit=1';
    $deezerJson = fetchRemoteJson($deezerUrl);

    if ($deezerJson && isset($deezerJson['data'][0]['album']['cover_big'])) {
        return $deezerJson['data'][0]['album']['cover_big'];
    }

    return null;
}

function fetchRemoteJson(string $url): ?array {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: okotunes-CoverArtFetcher/1.0',
            'timeout' => 5
        ]
    ];
    $context = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $context);
    if (!$raw) return null;
    return json_decode($raw, true);
}

function fetchRemoteImageData(string $url): ?string {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: okotunes-CoverArtFetcher/1.0',
            'timeout' => 8
        ]
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    return ($data && strlen($data) > 100) ? $data : null;
}
