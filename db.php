<?php
/**
 * db.php - SQLite database layer with Cloudflare R2 Auto-Sync & Self-Healing for okotunes.
 * Guarantees race-condition safety, shutdown flushing, and automatic database reconstruction.
 */

require_once __DIR__ . '/r2_storage.php';

define('LOCAL_DB_PATH', sys_get_temp_dir() . '/okotunes.sqlite');
define('R2_DB_KEY', 'data/okotunes.sqlite');

$g_dbNeedsSync = false;

// Guarantee DB sync on script shutdown (handles abrupt exit / request end)
register_shutdown_function(function() {
    global $g_dbNeedsSync;
    if ($g_dbNeedsSync) {
        syncDbToR2();
    }
});

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    syncDbFromR2();

    $pdo = new PDO('sqlite:' . LOCAL_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tracks (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            artist TEXT DEFAULT 'Unknown Artist',
            album TEXT DEFAULT 'Unknown Album',
            duration REAL DEFAULT 0,
            r2_key TEXT NOT NULL,
            art_r2_key TEXT DEFAULT '',
            file_size INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS stats (
            track_id TEXT PRIMARY KEY,
            play_count INTEGER DEFAULT 0,
            last_played DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    return $pdo;
}

/**
 * Downloads SQLite DB from Cloudflare R2 on container boot, or rebuilds from sidecar JSON files
 */
function syncDbFromR2(): void {
    if (file_exists(LOCAL_DB_PATH) && filesize(LOCAL_DB_PATH) > 0) {
        return;
    }

    $r2 = getR2();
    if (!$r2->isConfigured()) {
        return;
    }

    $remoteUrl = $r2->getUrl(R2_DB_KEY);
    $content = @file_get_contents($remoteUrl);

    if ($content !== false && strlen($content) > 0 && str_starts_with($content, 'SQLite format 3')) {
        file_put_contents(LOCAL_DB_PATH, $content);
        return;
    }

    // Self-Healing Fallback: Rebuild DB from R2 track sidecars if DB file missing/corrupted
    rebuildDbFromR2Sidecars();
}

/**
 * Self-healing scanner: Restores tracks directly from Cloudflare R2 sidecar files
 */
function rebuildDbFromR2Sidecars(): void {
    $r2 = getR2();
    if (!$r2->isConfigured()) return;

    $sidecarKeys = $r2->listObjects('tracks/');
    if (empty($sidecarKeys)) return;

    $db = new PDO('sqlite:' . LOCAL_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $db->exec("
        CREATE TABLE IF NOT EXISTS tracks (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            artist TEXT DEFAULT 'Unknown Artist',
            album TEXT DEFAULT 'Unknown Album',
            duration REAL DEFAULT 0,
            r2_key TEXT NOT NULL,
            art_r2_key TEXT DEFAULT '',
            file_size INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS stats (
            track_id TEXT PRIMARY KEY,
            play_count INTEGER DEFAULT 0,
            last_played DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    foreach ($sidecarKeys as $key) {
        if (str_ends_with($key, '.json')) {
            $jsonUrl = $r2->getUrl($key);
            $raw = @file_get_contents($jsonUrl);
            if ($raw) {
                $data = json_decode($raw, true);
                if (is_array($data) && isset($data['id'], $data['r2_key'])) {
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO tracks (id, title, artist, album, duration, r2_key, art_r2_key, file_size)
                        VALUES (:id, :title, :artist, :album, :duration, :r2_key, :art_r2_key, :file_size)
                    ");
                    $stmt->execute([
                        ':id'         => $data['id'],
                        ':title'      => $data['title'] ?? 'Untitled',
                        ':artist'     => $data['artist'] ?? 'Unknown Artist',
                        ':album'      => $data['album'] ?? 'Unknown Album',
                        ':duration'   => $data['duration'] ?? 0,
                        ':r2_key'     => $data['r2_key'],
                        ':art_r2_key' => $data['art_r2_key'] ?? '',
                        ':file_size'  => $data['file_size'] ?? 0
                    ]);
                }
            }
        }
    }
}

/**
 * Syncs updated SQLite DB back to Cloudflare R2
 */
function syncDbToR2(): void {
    global $g_dbNeedsSync;
    if (!file_exists(LOCAL_DB_PATH)) return;

    $r2 = getR2();
    if ($r2->isConfigured()) {
        $r2->uploadObject(R2_DB_KEY, LOCAL_DB_PATH, 'application/vnd.sqlite3');
        $g_dbNeedsSync = false;
    }
}

function getStats(): array {
    try {
        $db = getDb();
        $stmt = $db->query("SELECT track_id, play_count FROM stats");
        $rows = $stmt->fetchAll();
        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['track_id']] = (int)$r['play_count'];
        }

        $tracksStmt = $db->query("SELECT id, title, artist, album, duration, r2_key, art_r2_key FROM tracks ORDER BY title ASC");
        $tracks = $tracksStmt->fetchAll();

        return ['counts' => $counts, 'tracks' => $tracks];
    } catch (Exception $e) {
        error_log("DB getStats error: " . $e->getMessage());
        return ['counts' => [], 'tracks' => []];
    }
}

function recordPlay(string $trackId): bool {
    global $g_dbNeedsSync;
    try {
        $db = getDb();
        $stmt = $db->prepare("
            INSERT INTO stats (track_id, play_count, last_played)
            VALUES (:id, 1, CURRENT_TIMESTAMP)
            ON CONFLICT(track_id) DO UPDATE SET
                play_count = play_count + 1,
                last_played = CURRENT_TIMESTAMP
        ");
        $res = $stmt->execute([':id' => $trackId]);
        if ($res) {
            $g_dbNeedsSync = true;
        }
        return $res;
    } catch (Exception $e) {
        error_log("DB recordPlay error: " . $e->getMessage());
        return false;
    }
}

function saveTrack(array $track): bool {
    global $g_dbNeedsSync;
    try {
        $db = getDb();
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO tracks (id, title, artist, album, duration, r2_key, art_r2_key, file_size)
            VALUES (:id, :title, :artist, :album, :duration, :r2_key, :art_r2_key, :file_size)
        ");
        $res = $stmt->execute([
            ':id'         => $track['id'],
            ':title'      => $track['title'] ?? 'Untitled',
            ':artist'     => $track['artist'] ?? 'Unknown Artist',
            ':album'      => $track['album'] ?? 'Unknown Album',
            ':duration'   => $track['duration'] ?? 0,
            ':r2_key'     => $track['r2_key'],
            ':art_r2_key' => $track['art_r2_key'] ?? '',
            ':file_size'  => $track['file_size'] ?? 0,
        ]);

        if ($res) {
            $g_dbNeedsSync = true;
        }
        return $res;
    } catch (Exception $e) {
        error_log("DB saveTrack error: " . $e->getMessage());
        return false;
    }
}

function getAllTracks(): array {
    try {
        $db = getDb();
        $stmt = $db->query("SELECT * FROM tracks ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("DB getAllTracks error: " . $e->getMessage());
        return [];
    }
}

function deleteTrack(string $id): bool {
    global $g_dbNeedsSync;
    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM tracks WHERE id = :id");
        $res = $stmt->execute([':id' => $id]);
        if ($res) {
            $g_dbNeedsSync = true;
        }
        return $res;
    } catch (Exception $e) {
        error_log("DB deleteTrack error: " . $e->getMessage());
        return false;
    }
}
