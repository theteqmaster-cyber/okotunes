<?php
/**
 * upload.php - Music upload handler for okotunes.
 * Uploads audio tracks directly to Cloudflare R2 or local volume and registers track in SQLite.
 */

@ini_set('upload_max_filesize', '500M');
@ini_set('post_max_size', '500M');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '600');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth.php';
require_auth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}


// Handle payload exceeding post_max_size
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds server post limit (' . ini_get('post_max_size') . ')']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    $errMsg = match($errCode) {
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize limit (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload folder on server',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write upload file to server disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
        default => 'File upload error code: ' . $errCode
    };
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

$file = $_FILES['file'];
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

// Fallback: save to local volume if R2 is not enabled
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
