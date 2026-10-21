<?php
/**
 * Export Overfitting Dataset (.jsonl / JSON)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAuth();

$format = $_GET['format'] ?? 'jsonl';

if (!file_exists(DATASET_FILE)) {
    touch(DATASET_FILE);
}

if ($format === 'json') {
    $rows = [];
    $handle = fopen(DATASET_FILE, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line !== '') {
                $rows[] = json_decode($line, true);
            }
        }
        fclose($handle);
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="okotunes_dataset_' . date('Ymd_His') . '.json"');
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Default .jsonl download
header('Content-Type: application/x-ndjson; charset=utf-8');
header('Content-Disposition: attachment; filename="okotunes_overfitting_' . date('Ymd_His') . '.jsonl"');
header('Content-Length: ' . filesize(DATASET_FILE));
readfile(DATASET_FILE);
exit;
