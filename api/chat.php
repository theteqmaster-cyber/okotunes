<?php
/**
 * Chat Completion & Streaming Inference Engine
 * Powered by Groq LPU + Multimodal Vision Support
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/r2.php';
requireAuth();

// Prevent PHP output buffering from blocking streaming
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
for ($i = 0; $i < ob_get_level(); $i++) {
    ob_end_flush();
}
ob_implicit_flush(true);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$threadId = $input['thread_id'] ?? '';
$userText = trim($input['message'] ?? '');
$userImage = trim($input['image'] ?? ''); // base64 data URL
$model = $input['model'] ?? 'qwen/qwen3.8-27b';
$stream = $input['stream'] ?? true;

if (empty($userText) && empty($userImage)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message or image must be provided.']);
    exit;
}

// If an image is attached, force a vision-capable model (qwen/qwen3.8-27b)
if (!empty($userImage)) {
    $model = 'qwen/qwen3.8-27b';
}

// 1. Ensure thread exists
if (empty($threadId)) {
    $threadId = 'th_' . time() . '_' . substr(md5((string)rand()), 0, 6);
    $threads = file_exists(THREADS_FILE) ? (json_decode(file_get_contents(THREADS_FILE), true) ?? []) : [];
    $title = mb_substr($userText ?: 'Image Analysis', 0, 36);
    array_unshift($threads, [
        'id' => $threadId,
        'title' => $title,
        'model' => $model,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    file_put_contents(THREADS_FILE, json_encode($threads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// 2. Load thread history
$threadFile = CHATS_DIR . "/{$threadId}.json";
$history = file_exists($threadFile) ? (json_decode(file_get_contents($threadFile), true) ?? []) : [];

// 3. Build System Prompt with Lore
$lore = file_exists(LORE_FILE) ? (json_decode(file_get_contents(LORE_FILE), true) ?? []) : [];
$constitution = $lore['system_constitution'] ?? 'You are Okotune AI, sovereign assistant for Mphathisi Ndlovu and the M.P.A.S ecosystem.';

$directivesText = "";
if (!empty($lore['directives'])) {
    $directivesText .= "\nACTIVE DIRECTIVES & LORE:\n";
    foreach ($lore['directives'] as $d) {
        $directivesText .= "- [{$d['category']}]: {$d['rule']}\n";
    }
}

if (!empty($lore['ecosystem_assets'])) {
    $directivesText .= "\nECOSYSTEM ENTITIES:\n";
    foreach ($lore['ecosystem_assets'] as $a) {
        $directivesText .= "- {$a['name']} ({$a['domain']}): {$a['role']}\n";
    }
}

$systemPrompt = "{$constitution}\n{$directivesText}\n\nCurrent local timestamp: " . date('Y-m-d H:i:s') . "\nTarget User: Mphathisi Ndlovu (The Teq-master).";

// 4. Build messages payload
$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

// Append sliding window of last 10 messages (convert previous text format)
$recent = array_slice($history, -10);
foreach ($recent as $msg) {
    if (isset($msg['role'], $msg['content'])) {
        $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
    }
}

// Format current user message (multimodal if image present)
if (!empty($userImage)) {
    $currentContent = [
        ['type' => 'text', 'text' => $userText !== '' ? $userText : 'Please inspect and analyze this image in detail.'],
        ['type' => 'image_url', 'image_url' => ['url' => $userImage]]
    ];
} else {
    $currentContent = $userText;
}

$messages[] = ['role' => 'user', 'content' => $currentContent];

// 5. Prepare Groq API call
$groqUrl = "https://api.groq.com/openai/v1/chat/completions";
$groqPayload = [
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.6,
    'stream' => $stream
];

$groqKey = GROQ_API_KEY;
if (empty($groqKey)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'GROQ_API_KEY is not configured on the server.']);
    exit;
}

if ($stream) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-transform');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    $fullAssistantReply = '';
    $ch = curl_init($groqUrl);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groqPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$groqKey}",
        "Content-Type: application/json",
        "User-Agent: OkotuneAI/1.0"
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, 'OkotuneAI/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

    // Stream chunk callback
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fullAssistantReply) {
        echo $data;
        flush();

        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'data: ') && $line !== 'data: [DONE]') {
                $jsonStr = substr($line, 6);
                $json = json_decode($jsonStr, true);
                $delta = $json['choices'][0]['delta']['content'] ?? '';
                if ($delta !== '') {
                    $fullAssistantReply .= $delta;
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "data: " . json_encode(['error' => $curlErr]) . "\n\n";
        echo "data: [DONE]\n\n";
        flush();
    }

    // 6. Save Interaction to Thread
    if ($fullAssistantReply !== '') {
        $timestamp = date('c');

        $userMsgObj = [
            'role' => 'user',
            'content' => $userText ?: 'Image Analysis',
            'image' => $userImage ?: null,
            'timestamp' => $timestamp
        ];
        $assistantMsgObj = [
            'role' => 'assistant',
            'content' => $fullAssistantReply,
            'model' => $model,
            'timestamp' => $timestamp
        ];

        $history[] = $userMsgObj;
        $history[] = $assistantMsgObj;
        file_put_contents($threadFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Update thread title and timestamp
        if (file_exists(THREADS_FILE)) {
            $threads = json_decode(file_get_contents(THREADS_FILE), true) ?? [];
            foreach ($threads as &$t) {
                if ($t['id'] === $threadId) {
                    if ($t['title'] === 'New Conversation' || $t['title'] === '') {
                        $t['title'] = mb_substr($userText ?: 'Image Analysis', 0, 36);
                    }
                    $t['updated_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            file_put_contents(THREADS_FILE, json_encode($threads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // Save to background training corpus
        $datasetPair = [
            'thread_id' => $threadId,
            'timestamp' => $timestamp,
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $userText ?: 'Image Analysis'],
                ['role' => 'assistant', 'content' => $fullAssistantReply]
            ]
        ];
        file_put_contents(DATASET_FILE, json_encode($datasetPair, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
    }

    exit;
} else {
    // Non-streaming fallback
    $ch = curl_init($groqUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groqPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$groqKey}",
        "Content-Type: application/json",
        "User-Agent: OkotuneAI/1.0"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    header('Content-Type: application/json');
    if ($code >= 400) {
        http_response_code($code);
    }
    echo $response;
    exit;
}
