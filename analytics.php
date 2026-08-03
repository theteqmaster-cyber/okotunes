<?php
/**
 * analytics.php - Telemetry & Intelligence API for okotunes.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

$dataFile = __DIR__ . '/data/analytics_events.json';

if (!is_dir(__DIR__ . '/data')) {
    @mkdir(__DIR__ . '/data', 0755, true);
}

// GET: Return Analytics Summary for UI Dashboard
if ($method === 'GET') {
    $events = [];
    if (file_exists($dataFile)) {
        $raw = file_get_contents($dataFile);
        $events = json_decode($raw, true) ?: [];
    }

    $totalEvents = count($events);
    $totalSecondsListened = 0;
    $trackCounts = [];
    $transitions = [];
    $eventCounts = [
        'play' => 0, 'pause' => 0, 'resume' => 0, 'seek' => 0,
        'ended' => 0, 'skip_next' => 0, 'skip_prev' => 0
    ];

    foreach ($events as $evt) {
        $type = $evt['event'] ?? 'unknown';
        if (isset($eventCounts[$type])) {
            $eventCounts[$type]++;
        }

        $totalSecondsListened += floatval($evt['duration_played_sec'] ?? 0);
        $track = $evt['track_name'] ?? 'Unknown';

        if (!isset($trackCounts[$track])) {
            $trackCounts[$track] = 0;
        }
        $trackCounts[$track]++;

        if (!empty($evt['prev_track']) && !empty($evt['track_name'])) {
            $transKey = $evt['prev_track'] . ' ➔ ' . $evt['track_name'];
            $transitions[$transKey] = ($transitions[$transKey] ?? 0) + 1;
        }
    }

    arsort($trackCounts);
    arsort($transitions);

    $currentTrackReq = $_GET['current_track'] ?? '';
    $predictedQueue = [];
    if (!empty($currentTrackReq)) {
        foreach ($transitions as $trans => $count) {
            $parts = explode(' ➔ ', $trans);
            if (count($parts) === 2 && $parts[0] === $currentTrackReq) {
                $predictedQueue[] = ['name' => $parts[1], 'score' => $count];
            }
        }
    }

    $topTrackNames = array_keys($trackCounts);
    foreach ($topTrackNames as $topName) {
        if (count($predictedQueue) >= 3) break;
        if ($topName !== $currentTrackReq && !in_array($topName, array_column($predictedQueue, 'name'))) {
            $predictedQueue[] = ['name' => $topName, 'score' => 1];
        }
    }

    echo json_encode([
        'total_events' => $totalEvents,
        'total_hours_listened' => round($totalSecondsListened / 3600, 2),
        'event_breakdown' => $eventCounts,
        'top_tracks' => array_slice($trackCounts, 0, 10, true),
        'top_transitions' => array_slice($transitions, 0, 10, true),
        'predicted_queue' => array_slice($predictedQueue, 0, 10),
        'recent_events' => array_slice(array_reverse($events), 0, 15)
    ]);
    exit;
}

// POST: Record Telemetry Event
if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || empty($data['event'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid telemetry payload']);
        exit;
    }

    $eventRecord = [
        'id' => uniqid('evt_'),
        'event' => $data['event'],
        'track_name' => $data['track_name'] ?? 'Unknown',
        'url' => $data['url'] ?? '',
        'prev_track' => $data['prev_track'] ?? '',
        'duration_played_sec' => round(floatval($data['duration_played_sec'] ?? 0), 2),
        'track_duration_sec' => round(floatval($data['track_duration_sec'] ?? 0), 2),
        'completion_pct' => round(floatval($data['completion_pct'] ?? 0), 1),
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s')
    ];

    $events = [];
    if (file_exists($dataFile)) {
        $raw = file_get_contents($dataFile);
        $events = json_decode($raw, true) ?: [];
    }

    $events[] = $eventRecord;

    if (count($events) > 5000) {
        $events = array_slice($events, -5000);
    }

    file_put_contents($dataFile, json_encode($events, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'logged_event' => $eventRecord['id']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
