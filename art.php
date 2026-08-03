<?php
/**
 * art.php - Album Cover Art Handler & Cache for okotunes.
 * Redirects to Cloudflare R2 artwork URL or extracts embedded ID3v2 APIC artwork.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2_storage.php';

if (is_decoy() || !is_authenticated()) {
    $fallback = __DIR__ . '/assets/splash_bg.jpg';
    if (file_exists($fallback)) {
        header('Content-Type: image/jpeg');
        readfile($fallback);
        exit;
    }
}


$trackId  = $_GET['id'] ?? null;
$filePath = $_GET['file'] ?? null;
$cacheDir = __DIR__ . '/cache/art';

$r2 = getR2();

if ($trackId) {
    $db = getDb();
    $stmt = $db->prepare("SELECT art_r2_key FROM tracks WHERE id = :id");
    $stmt->execute([':id' => $trackId]);
    $row = $stmt->fetch();

    if ($row && !empty($row['art_r2_key'])) {
        header("Location: " . $r2->getUrl($row['art_r2_key']), true, 302);
        exit;
    }
}

if (!$filePath && !$trackId) {
    http_response_code(400);
    echo 'Missing file or track ID parameter';
    exit;
}

$localPath = __DIR__ . '/data/uploads/' . basename(ltrim(rawurldecode($filePath ?: $trackId), '/\\'));

if (!file_exists($localPath)) {
    http_response_code(204); // No content - client shows placeholder artwork
    exit;
}

$hash      = md5($localPath);
$cacheBase = $cacheDir . '/' . $hash;

// Serve cached art if available
foreach (['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $ext => $mime) {
    $cachePath = $cacheBase . '.' . $ext;
    if (is_file($cachePath)) {
        sendCachedImage($cachePath, $mime);
        exit;
    }
}

if (is_file($cacheBase . '.none')) {
    http_response_code(204);
    exit;
}

// Extract ID3 embedded art
$image = extractCoverArt($localPath);
if ($image === null) {
    @mkdir($cacheDir, 0755, true);
    @touch($cacheBase . '.none');
    http_response_code(204);
    exit;
}

@mkdir($cacheDir, 0755, true);
$ext = ($image['mime'] === 'image/png') ? 'png' : (($image['mime'] === 'image/webp') ? 'webp' : 'jpg');
$savePath = $cacheBase . '.' . $ext;
file_put_contents($savePath, $image['data']);

sendCachedImage($savePath, $image['mime']);

function sendCachedImage(string $path, string $mime): void {
    $size = filesize($path);
    $etag = '"' . md5($path . $size) . '"';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Cache-Control: public, max-age=604800, immutable');
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        http_response_code(304);
        return;
    }
    readfile($path);
}

function extractCoverArt(string $filePath): ?array {
    $fh = @fopen($filePath, 'rb');
    if (!$fh) return null;

    $header = fread($fh, 10);
    if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
        fclose($fh);
        return null;
    }

    $majorVersion = ord($header[3]);
    $flags        = ord($header[5]);
    $hasExtHeader = ($flags & 0x40) !== 0;
    $tagSize      = synchsafeToInt(substr($header, 6, 4));

    $safeSize = min($tagSize, 20 * 1024 * 1024);
    $tagData  = fread($fh, $safeSize);
    fclose($fh);
    if ($tagData === false || strlen($tagData) < 4) return null;

    $offset = 0;
    if ($majorVersion >= 3 && $hasExtHeader) {
        $extSize = ($majorVersion === 4)
            ? synchsafeToInt(substr($tagData, 0, 4))
            : unpack('N', substr($tagData, 0, 4))[1];
        $offset += $extSize;
    }

    $frameIdLen = ($majorVersion >= 3) ? 4 : 3;

    while ($offset < strlen($tagData) - ($frameIdLen + 6)) {
        $frameId = substr($tagData, $offset, $frameIdLen);
        if ($frameId === str_repeat("\x00", $frameIdLen)) break;
        $offset += $frameIdLen;

        if ($majorVersion >= 3) {
            $frameSize = ($majorVersion === 4)
                ? synchsafeToInt(substr($tagData, $offset, 4))
                : unpack('N', substr($tagData, $offset, 4))[1];
            $offset += 6;
        } else {
            $sz = substr($tagData, $offset, 3);
            $frameSize = (ord($sz[0]) << 16) | (ord($sz[1]) << 8) | ord($sz[2]);
            $offset += 3;
        }

        if ($frameSize <= 0 || $offset + $frameSize > strlen($tagData)) break;

        $frameData = substr($tagData, $offset, $frameSize);
        $offset   += $frameSize;

        if ($frameId !== 'APIC' && $frameId !== 'PIC') continue;

        $pos = 1;
        if ($frameId === 'APIC') {
            $nullPos = strpos($frameData, "\x00", $pos);
            if ($nullPos === false) continue;
            $mimeType = strtolower(substr($frameData, $pos, $nullPos - $pos));
            $pos = $nullPos + 2;
            $enc = ord($frameData[0]);
            $nullSeq = ($enc === 1 || $enc === 2) ? "\x00\x00" : "\x00";
            $descEnd = strpos($frameData, $nullSeq, $pos);
            if ($descEnd === false) continue;
            $pos = $descEnd + strlen($nullSeq);
        } else {
            $imgFmt   = strtolower(substr($frameData, $pos, 3));
            $mimeType = match($imgFmt) { 'jpg' => 'image/jpeg', 'png' => 'image/png', default => 'image/jpeg' };
            $pos += 4;
            $nullPos = strpos($frameData, "\x00", $pos);
            if ($nullPos === false) continue;
            $pos = $nullPos + 1;
        }

        if ($mimeType === 'image/jpg' || empty($mimeType)) $mimeType = 'image/jpeg';
        $imageData = substr($frameData, $pos);
        if (strlen($imageData) < 16) continue;

        return ['mime' => $mimeType, 'data' => $imageData];
    }

    return null;
}

function synchsafeToInt(string $bytes): int {
    $n = 0;
    for ($i = 0; $i < strlen($bytes); $i++) {
        $n = ($n << 7) | (ord($bytes[$i]) & 0x7F);
    }
    return $n;
}
