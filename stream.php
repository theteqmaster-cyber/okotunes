<?php
/**
 * stream.php - Audio streaming handler for okotunes.
 * Redirects to Cloudflare R2 CDN or streams local audio files with HTTP Range support.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

$trackId  = $_GET['id'] ?? null;
$filePath = $_GET['file'] ?? null;

$r2 = getR2();

if ($trackId) {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM tracks WHERE id = :id");
    $stmt->execute([':id' => $trackId]);
    $track = $stmt->fetch();

    if ($track && !empty($track['r2_key'])) {
        $r2Url = $r2->getUrl($track['r2_key']);
        header("Location: " . $r2Url, true, 302);
        exit;
    }
}

if (!$filePath && !$trackId) {
    http_response_code(400);
    echo 'Missing track ID or file parameter';
    exit;
}

$targetFile = $filePath ?: $trackId;
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

// HTTP Range Requests for Seeking
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
    if ($fh === false) {
        http_response_code(500);
        echo 'Failed to open media stream';
        exit;
    }
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

header('Content-Length: ' . $fileSize);
readfile($localPath);
