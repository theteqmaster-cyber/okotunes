<?php
/**
 * stream.php - Audio streaming & CORS proxy handler for okotunes.
 * Streams audio from Cloudflare R2 or local storage with full CORS & HTTP Range support.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Range, Content-Type, Authorization, Origin, Accept');
header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

// Decoy mode: return empty stream (infinite loading buffer)
if (is_decoy() || str_starts_with($_GET['id'] ?? '', 'decoy_')) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: 0');
    http_response_code(200);
    exit;
}

if (!is_authenticated()) {
    http_response_code(403);
    echo 'Forbidden: Authentication required to stream audio.';
    exit;
}

$trackId  = $_GET['id'] ?? null;

$filePath = $_GET['file'] ?? null;

$r2 = getR2();
$r2Key = null;

if ($trackId) {
    $db = getDb();
    $stmt = $db->prepare("SELECT r2_key FROM tracks WHERE id = :id");
    $stmt->execute([':id' => $trackId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['r2_key'])) {
        $r2Key = $row['r2_key'];
    }
} elseif ($filePath) {
    $r2Key = ltrim(rawurldecode($filePath), '/\\');
}

if (!$r2Key && !$filePath && !$trackId) {
    http_response_code(400);
    echo 'Missing track ID or file parameter';
    exit;
}

// 1. Stream from Cloudflare R2 if configured
if ($r2Key && $r2->isConfigured()) {
    $r2Url = $r2->getUrl($r2Key);

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: okotunes-Stream-Proxy/1.0'
        ]
    ];

    if (isset($_SERVER['HTTP_RANGE'])) {
        $opts['http']['header'] .= "\r\nRange: " . $_SERVER['HTTP_RANGE'];
    }

    $context = stream_context_create($opts);
    $stream = @fopen($r2Url, 'rb', false, $context);

    if ($stream !== false) {
        // Forward HTTP response status and relevant headers from R2
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('{^HTTP/\S+\s+(\d+)}', $header, $matches)) {
                    http_response_code(intval($matches[1]));
                } elseif (preg_match('{^(Content-Type|Content-Length|Content-Range|Accept-Ranges):}i', $header)) {
                    header($header);
                }
            }
        }

        if (isset($_GET['download'])) {
            header('Content-Disposition: attachment; filename="' . basename($r2Key) . '"');
        }

        fpassthru($stream);
        fclose($stream);
        exit;
    }
}

// 2. Fallback: Stream from local storage if file exists locally
$targetFile = $r2Key ?: ($filePath ?: $trackId);
$requested  = ltrim(rawurldecode($targetFile), '/\\');

if (strpos($requested, '../') !== false || strpos($requested, '..\\') !== false) {
    http_response_code(400);
    echo 'Invalid file path';
    exit;
}

$localPath = __DIR__ . '/data/uploads/' . basename($requested);

if (!file_exists($localPath)) {
    http_response_code(404);
    echo 'Track audio file not found';
    exit;
}

$extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/wav',
    'flac' => 'audio/flac',
    'aac'  => 'audio/aac',
    'ogg'  => 'audio/ogg',
    'm4a'  => 'audio/mp4',
    'opus' => 'audio/opus',
    'mp4'  => 'audio/mp4',
    'wma'  => 'audio/x-ms-wma'
];
$mime = $mimeTypes[$extension] ?? 'application/octet-stream';
$fileSize = filesize($localPath);

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');

if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . basename($localPath) . '"');
}

if (isset($_SERVER['HTTP_RANGE'])) {
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    list($start, $end) = explode('-', $range, 2);
    $start = intval($start);
    $end   = $end !== '' ? intval($end) : ($fileSize - 1);
    $length = $end - $start + 1;

    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header('Content-Length: ' . $length);

    $fh = fopen($localPath, 'rb');
    if ($fh !== false) {
        fseek($fh, $start);
        $chunkSize = 1024 * 256;
        $remaining = $length;
        while ($remaining > 0 && !feof($fh)) {
            if (connection_aborted()) break;
            $toRead = min($chunkSize, $remaining);
            $data   = fread($fh, $toRead);
            echo $data;
            $remaining -= strlen($data);
        }
        fclose($fh);
        exit;
    }
}

header('Content-Length: ' . $fileSize);
readfile($localPath);
