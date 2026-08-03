<?php
/**
 * db.php - SQLite database layer with Cloudflare R2 Auto-Sync & Self-Healing for okotunes.
 * Guarantees race-condition safety, shutdown flushing, and automatic database reconstruction.
 */

require_once __DIR__ . '/r2_storage.php';

define('LOCAL_DB_PATH', sys_get_temp_dir() . '/okotunes.sqlite');
define('R2_DB_KEY', 'data/okotunes.sqlite');

$g_dbNeedsSync = false;

// Guarantee DB sync on script shutdown
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
            art_status TEXT DEFAULT 'pending',
            file_size INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS stats (
            track_id TEXT PRIMARY KEY,
            play_count INTEGER DEFAULT 0,
            last_played DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Auto-migrate schema if art_status column is missing
    try {
        $cols = $pdo->query("PRAGMA table_info(tracks)")->fetchAll();
        $colNames = array_column($cols, 'name');
        if (!in_array('art_status', $colNames)) {
            $pdo->exec("ALTER TABLE tracks ADD COLUMN art_status TEXT DEFAULT 'pending'");
        }
    } catch (Exception $e) {
        error_log("Migration notice: " . $e->getMessage());
    }

    return $pdo;
}

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

    rebuildDbFromR2Sidecars();
}

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
            art_status TEXT DEFAULT 'pending',
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
                    $artR2Key = $data['art_r2_key'] ?? '';
                    $artStatus = !empty($artR2Key) ? 'fetched' : ($data['art_status'] ?? 'pending');

                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO tracks (id, title, artist, album, duration, r2_key, art_r2_key, art_status, file_size)
                        VALUES (:id, :title, :artist, :album, :duration, :r2_key, :art_r2_key, :art_status, :file_size)
                    ");
                    $stmt->execute([
                        ':id'         => $data['id'],
                        ':title'      => $data['title'] ?? 'Untitled',
                        ':artist'     => $data['artist'] ?? 'Unknown Artist',
                        ':album'      => $data['album'] ?? 'Unknown Album',
                        ':duration'   => $data['duration'] ?? 0,
                        ':r2_key'     => $data['r2_key'],
                        ':art_r2_key' => $artR2Key,
                        ':art_status' => $artStatus,
                        ':file_size'  => $data['file_size'] ?? 0
                    ]);
                }
            }
        }
    }
}

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

        $tracksStmt = $db->query("SELECT id, title, artist, album, duration, r2_key, art_r2_key, art_status FROM tracks ORDER BY title ASC");
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
        $artR2Key = $track['art_r2_key'] ?? '';
        $artStatus = !empty($artR2Key) ? 'fetched' : ($track['art_status'] ?? 'pending');

        $stmt = $db->prepare("
            INSERT OR REPLACE INTO tracks (id, title, artist, album, duration, r2_key, art_r2_key, art_status, file_size)
            VALUES (:id, :title, :artist, :album, :duration, :r2_key, :art_r2_key, :art_status, :file_size)
        ");
        $res = $stmt->execute([
            ':id'         => $track['id'],
            ':title'      => $track['title'] ?? 'Untitled',
            ':artist'     => $track['artist'] ?? 'Unknown Artist',
            ':album'      => $track['album'] ?? 'Unknown Album',
            ':duration'   => $track['duration'] ?? 0,
            ':r2_key'     => $track['r2_key'],
            ':art_r2_key' => $artR2Key,
            ':art_status' => $artStatus,
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

/**
 * Get batch of tracks pending artwork fetch (default batch limit: 4)
 */
function getPendingArtTracks(int $limit = 4): array {
    try {
        $db = getDb();
        $stmt = $db->prepare("
            SELECT id, title, artist, album, r2_key 
            FROM tracks 
            WHERE (art_r2_key IS NULL OR art_r2_key = '') 
              AND (art_status IS NULL OR art_status = 'pending')
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("DB getPendingArtTracks error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update track art status and art R2 key
 */
function updateTrackArtStatus(string $id, string $status, string $artR2Key = ''): bool {
    global $g_dbNeedsSync;
    try {
        $db = getDb();
        $stmt = $db->prepare("
            UPDATE tracks 
            SET art_status = :status, art_r2_key = :art_key 
            WHERE id = :id
        ");
        $res = $stmt->execute([
            ':id'      => $id,
            ':status'  => $status,
            ':art_key' => $artR2Key
        ]);

        if ($res) {
            $g_dbNeedsSync = true;
        }
        return $res;
    } catch (Exception $e) {
        error_log("DB updateTrackArtStatus error: " . $e->getMessage());
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
