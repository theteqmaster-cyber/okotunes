<?php
/**
 * Thread Management Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function loadThreads(): array {
    if (file_exists(THREADS_FILE)) {
        $json = file_get_contents(THREADS_FILE);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return [];
}

function saveThreads(array $threads): bool {
    return file_put_contents(THREADS_FILE, json_encode($threads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

if ($method === 'GET') {
    $threadId = $_GET['id'] ?? null;
    if ($threadId) {
        $filePath = CHATS_DIR . "/{$threadId}.json";
        if (file_exists($filePath)) {
            $messages = json_decode(file_get_contents($filePath), true) ?? [];
            echo json_encode(['success' => true, 'id' => $threadId, 'messages' => $messages]);
            exit;
        }
        echo json_encode(['success' => true, 'id' => $threadId, 'messages' => []]);
        exit;
    }

    // List all threads
    $threads = loadThreads();
    echo json_encode(['success' => true, 'threads' => $threads]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $id = 'th_' . time() . '_' . substr(md5((string)rand()), 0, 6);
        $title = trim($input['title'] ?? 'New Conversation');
        $model = $input['model'] ?? 'qwen/qwen3.8-27b';

        $threads = loadThreads();
        $newThread = [
            'id' => $id,
            'title' => $title,
            'model' => $model,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        array_unshift($threads, $newThread);
        saveThreads($threads);

        // Initialize empty chat file
        file_put_contents(CHATS_DIR . "/{$id}.json", json_encode([]));

        echo json_encode(['success' => true, 'thread' => $newThread]);
        exit;
    }

    if ($action === 'rename') {
        $id = $input['id'] ?? '';
        $title = trim($input['title'] ?? 'Untitled');
        $threads = loadThreads();
        foreach ($threads as &$t) {
            if ($t['id'] === $id) {
                $t['title'] = $title;
                $t['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        saveThreads($threads);
        echo json_encode(['success' => true, 'id' => $id, 'title' => $title]);
        exit;
    }

    if ($action === 'mark_gold') {
        // Mark message as Gold Standard in thread file
        $id = $input['thread_id'] ?? '';
        $msgIndex = $input['message_index'] ?? null;
        if ($id && $msgIndex !== null) {
            $filePath = CHATS_DIR . "/{$id}.json";
            if (file_exists($filePath)) {
                $messages = json_decode(file_get_contents($filePath), true) ?? [];
                if (isset($messages[$msgIndex])) {
                    $messages[$msgIndex]['gold_standard'] = !($messages[$msgIndex]['gold_standard'] ?? false);
                    file_put_contents($filePath, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    // Append to dataset with gold flag
                    $pair = [
                        'thread_id' => $id,
                        'timestamp' => date('c'),
                        'gold_standard' => true,
                        'messages' => array_slice($messages, max(0, $msgIndex - 1), 2)
                    ];
                    file_put_contents(DATASET_FILE, json_encode($pair) . "\n", FILE_APPEND);

                    echo json_encode(['success' => true, 'is_gold' => $messages[$msgIndex]['gold_standard']]);
                    exit;
                }
            }
        }
        echo json_encode(['success' => false, 'error' => 'Message not found']);
        exit;
    }
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if ($id) {
        $threads = loadThreads();
        $threads = array_values(array_filter($threads, fn($t) => $t['id'] !== $id));
        saveThreads($threads);

        $filePath = CHATS_DIR . "/{$id}.json";
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => true, 'deleted' => $id]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Missing thread id']);
    exit;
}
