<?php
/**
 * upload.php - Music upload handler for okotunes.
 * Uploads audio tracks directly to Cloudflare R2 or local volume and registers track in SQLite.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or post size limit exceeded']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File upload failed with error code: ' . $file['error']]);
    exit;
}

$allowedExt = ['mp3', 'flac', 'wav', 'ogg', 'aac', 'm4a', 'opus', 'mp4', 'wma'];
$originalName = basename($file['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported audio format. Allowed: ' . implode(', ', $allowedExt)]);
    exit;
}

$cleanTitle = trim(pathinfo($originalName, PATHINFO_FILENAME));
$trackId    = md5($originalName . microtime(true));
$r2Key      = "tracks/" . $trackId . '.' . $ext;

$r2 = getR2();
$r2Uploaded = false;

if ($r2->isConfigured()) {
    $mimeType = match($ext) {
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'ogg'  => 'audio/ogg',
        'm4a', 'mp4' => 'audio/mp4',
        default => 'audio/octet-stream'
    };
    $r2Uploaded = $r2->uploadObject($r2Key, $file['tmp_name'], $mimeType);
}

// Fallback: save to local volume if R2 is not enabled yet
if (!$r2Uploaded) {
    $targetDir = __DIR__ . '/data/uploads';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    $targetPath = $targetDir . '/' . $trackId . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed storing uploaded audio track']);
        exit;
    }
    $r2Key = $trackId . '.' . $ext;
}

// Save track record in SQLite
$trackData = [
    'id'         => $trackId,
    'title'      => $cleanTitle,
    'artist'     => $_POST['artist'] ?? 'Unknown Artist',
    'album'      => $_POST['album'] ?? 'Single',
    'duration'   => (float)($_POST['duration'] ?? 0),
    'r2_key'     => $r2Key,
    'art_r2_key' => '',
    'file_size'  => $file['size'],
];

// Save metadata sidecar to R2 for crash recovery
if ($r2->isConfigured()) {
    $sidecarKey = "tracks/" . $trackId . ".json";
    $r2->uploadObject($sidecarKey, json_encode($trackData, JSON_PRETTY_PRINT), 'application/json', true);
}

saveTrack($trackData);

echo json_encode([
    'success' => true,
    'track'   => [
        'id'     => $trackId,
        'name'   => $cleanTitle,
        'url'    => $r2->getUrl($r2Key),
        'artUrl' => "art.php?id=" . $trackId
    ]
]);
