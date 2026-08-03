<?php
/**
 * delete.php - Track deletion handler for okotunes.
 * Deletes audio file, cover artwork, and metadata sidecar from Cloudflare R2 and SQLite database.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

$trackId = $_REQUEST['id'] ?? null;

if (!$trackId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing track ID']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM tracks WHERE id = :id");
    $stmt->execute([':id' => $trackId]);
    $track = $stmt->fetch();

    if (!$track) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Track not found in database']);
        exit;
    }

    $r2 = getR2();
    if ($r2->isConfigured()) {
        // Delete audio track object from R2
        if (!empty($track['r2_key'])) {
            $r2->deleteObject($track['r2_key']);
        }

        // Delete cover artwork object from R2
        if (!empty($track['art_r2_key'])) {
            $r2->deleteObject($track['art_r2_key']);
        }

        // Delete sidecar metadata JSON file from R2
        $sidecarKey = "tracks/" . $trackId . ".json";
        $r2->deleteObject($sidecarKey);
    }

    // Delete local cached artwork if exists
    $localArtPath = __DIR__ . '/cache/art/' . md5($trackId) . '.jpg';
    if (file_exists($localArtPath)) @unlink($localArtPath);

    // Delete track record and play stats from SQLite
    deleteTrack($trackId);
    $stmtStats = $db->prepare("DELETE FROM stats WHERE track_id = :id");
    $stmtStats->execute([':id' => $trackId]);

    echo json_encode([
        'success' => true,
        'message' => 'Track deleted successfully',
        'id'      => $trackId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed deleting track: ' . $e->getMessage()
    ]);
}
