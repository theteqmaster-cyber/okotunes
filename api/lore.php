<?php
/**
 * Lore & Ecosystem Memory API
 * Handles reading, updating directives, and dataset stats
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function loadLoreData(): array {
    if (file_exists(LORE_FILE)) {
        $json = file_get_contents(LORE_FILE);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return ['system_constitution' => '', 'directives' => []];
}

function saveLoreData(array $data): bool {
    return file_put_contents(LORE_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

if ($method === 'GET') {
    $lore = loadLoreData();

    // Calculate overfitting stats
    $datasetRows = 0;
    $datasetSize = 0;
    $goldStandards = 0;
    if (file_exists(DATASET_FILE)) {
        $datasetSize = filesize(DATASET_FILE);
        $handle = fopen(DATASET_FILE, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) !== '') {
                    $datasetRows++;
                    if (str_contains($line, '"gold_standard":true')) {
                        $goldStandards++;
                    }
                }
            }
            fclose($handle);
        }
    }

    echo json_encode([
        'success' => true,
        'lore' => $lore,
        'stats' => [
            'total_interactions' => $datasetRows,
            'gold_standard_pairs' => $goldStandards,
            'dataset_bytes' => $datasetSize,
            'dataset_formatted' => round($datasetSize / 1024, 2) . ' KB'
        ]
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $lore = loadLoreData();

    if (!empty($input['new_directive'])) {
        $newDir = [
            'id' => 'dir_' . time() . '_' . substr(md5((string)rand()), 0, 4),
            'category' => $input['category'] ?? 'Custom Directive',
            'rule' => trim($input['new_directive']),
            'pinned' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $lore['directives'][] = $newDir;
        saveLoreData($lore);
        echo json_encode(['success' => true, 'directive' => $newDir, 'lore' => $lore]);
        exit;
    }

    if (!empty($input['system_constitution'])) {
        $lore['system_constitution'] = trim($input['system_constitution']);
        saveLoreData($lore);
        echo json_encode(['success' => true, 'lore' => $lore]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing directive ID']);
        exit;
    }
    $lore = loadLoreData();
    $lore['directives'] = array_values(array_filter($lore['directives'] ?? [], fn($d) => ($d['id'] ?? '') !== $id));
    saveLoreData($lore);
    echo json_encode(['success' => true, 'lore' => $lore]);
    exit;
}
