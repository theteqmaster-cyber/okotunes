<?php
/**
 * fetch_art.php - Cover Art Register Batch Fetcher for okotunes.
 * Automatically cleans track titles with multi-tier sanitization,
 * queries iTunes & Deezer open APIs, and caches high-resolution album artwork directly in Cloudflare R2.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

require_auth(true);


$batchSize = isset($_GET['batch']) ? max(1, min(10, (int)$_GET['batch'])) : 4;
$trackId   = $_GET['id'] ?? null;
$force     = !empty($_GET['force']);

if ($force && !$trackId) {
    $db = getDb();
    $db->exec("UPDATE tracks SET art_status = 'pending' WHERE 1");
}

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
        'status'  => 'success',
        'message' => $force ? 'No tracks in library' : 'No pending tracks requiring artwork',
        'processed'         => 0,
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
    $artist = ($track['artist'] !== 'Unknown Artist') ? $track['artist'] : '';

    $imageUrl = performMultiTierArtSearch($raw, $artist);

    if ($imageUrl) {
        $imgData = fetchRemoteImageData($imageUrl);
        if ($imgData !== null) {
            $artR2Key = "art/" . $id . ".jpg";
            $r2Uploaded = false;

            if ($r2->isConfigured()) {
                $r2Uploaded = $r2->uploadObject($artR2Key, $imgData, 'image/jpeg', true);
            }

            if (!$r2Uploaded) {
                $cacheDir = __DIR__ . '/cache/art';
                if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
                file_put_contents($cacheDir . '/' . md5($id) . '.jpg', $imgData);
            }

            updateTrackArtStatus($id, 'fetched', $artR2Key);
            $fetched++;
            $results[] = [
                'id'     => $id,
                'title'  => $raw,
                'status' => 'fetched',
                'artUrl' => $r2->getUrl($artR2Key)
            ];
        } else {
            updateTrackArtStatus($id, 'failed', '');
            $results[] = ['id' => $id, 'title' => $raw, 'status' => 'failed_download'];
        }
    } else {
        updateTrackArtStatus($id, 'failed', '');
        $results[] = ['id' => $id, 'title' => $raw, 'status' => 'not_found'];
    }

    $processed++;
}

$remaining = count(getPendingArtTracks(50));

echo json_encode([
    'status'            => 'success',
    'processed'         => $processed,
    'fetched'           => $fetched,
    'remaining_pending' => $remaining,
    'results'           => $results
]);

/**
 * Multi-Tier Search Engine: Tries multiple sanitized query variants against iTunes & Deezer
 */
function performMultiTierArtSearch(string $rawTitle, string $artist = ''): ?string {
    // Generate candidate search queries from most specific to core title
    $queries = buildSearchQueries($rawTitle, $artist);

    foreach ($queries as $q) {
        if (empty($q)) continue;
        $url = searchAlbumArtUrl($q);
        if ($url) return $url;
    }

    return null;
}

/**
 * Builds candidate search query variants by stripping clutter tags and artist/by markers
 */
function buildSearchQueries(string $rawTitle, string $artist = ''): array {
    $queries = [];

    // Clean initial title
    $clean = $rawTitle;
    $clean = preg_replace('/\.(mp3|flac|wav|m4a|aac|ogg|wma)$/i', '', $clean);

    // Remove common clutter tags case-insensitively
    $clutterPatterns = [
        '/\(MP3[_\s]?\d*K?\)/i',
        '/\[MP3[_\s]?\d*K?\]/i',
        '/_\d+K$/i',
        '/\(Official\s+(Music\s+)?(Video|Audio|HD|Clip)\)/i',
        '/\[Official\s+(Music\s+)?(Video|Audio|HD|Clip)\]/i',
        '/\(Lyrics?[_\s]?(Testo|Video|Audio)?\)/i',
        '/\[Lyrics?[_\s]?(Testo|Video|Audio)?\]/i',
        '/\(Testo[_\s]?(Lyrics?)?\)/i',
        '/\[Testo[_\s]?(Lyrics?)?\]/i',
        '/\(8D\s+AUDIO\)/i',
        '/\[8D\s+AUDIO\]/i',
        '/_8D[_\s]?Audio/i',
        '/\((320|160|128)kbps\)/i',
        '/\((TikTok|Slowed|Reverb|Speed Up|Remix|Edit)\)/i',
        '/\[(TikTok|Slowed|Reverb|Speed Up|Remix|Edit)\]/i',
        '/\(\d+\)$/',
        '/\[\d+\]$/'
    ];

    foreach ($clutterPatterns as $pattern) {
        $clean = preg_replace($pattern, '', $clean);
    }

    // Tier 1: Cleaned Title + Artist if available
    $cleanSpacing = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $clean)));
    if (!empty($artist)) {
        $queries[] = trim($artist . ' ' . $cleanSpacing);
    }
    $queries[] = $cleanSpacing;

    // Tier 2: Strip 'By...', 'by...', 'feat...', 'ft...' trailing clauses
    $core = preg_replace('/\s+(by|feat\.?|ft\.?|prod\.?|and\.{2,})\s+.*$/i', '', $cleanSpacing);
    $core = trim($core);
    if (!empty($core) && !in_array($core, $queries)) {
        $queries[] = $core;
    }

    // Tier 3: Core words (first 3 words)
    $words = explode(' ', $core);
    if (count($words) >= 2) {
        $short = implode(' ', array_slice($words, 0, 3));
        if (!in_array($short, $queries)) {
            $queries[] = $short;
        }
    }

    return array_unique(array_filter($queries));
}

/**
 * Searches iTunes Search API & Deezer API for high-resolution cover art URL
 */
function searchAlbumArtUrl(string $query): ?string {
    if (empty($query) || strlen($query) < 2) return null;

    // 1. iTunes Search API
    $iTunesUrl = 'https://itunes.apple.com/search?term=' . rawurlencode($query) . '&entity=song&limit=1';
    $json = fetchRemoteJson($iTunesUrl);

    if ($json && isset($json['results'][0]['artworkUrl100'])) {
        $url100 = $json['results'][0]['artworkUrl100'];
        return str_replace('100x100bb', '600x600bb', $url100);
    }

    // 2. Deezer API Fallback
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
            'method'  => 'GET',
            'header'  => 'User-Agent: okotunes-CoverArtFetcher/1.0',
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
            'method'  => 'GET',
            'header'  => 'User-Agent: okotunes-CoverArtFetcher/1.0',
            'timeout' => 8
        ]
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    return ($data && strlen($data) > 100) ? $data : null;
}
