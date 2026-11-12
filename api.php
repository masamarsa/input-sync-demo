<?php
declare(strict_types=1);

const MAX_BODY_BYTES = 1048576;
const MAX_VALUE_BYTES = 524288;

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function emptyState(): array
{
    return ['revision' => 0, 'updated_at' => null, 'values' => []];
}

function openStateFile()
{
    $path = sys_get_temp_dir() . '/php-input-sync-' . hash('sha256', __DIR__) . '.json';
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        respond(['error' => 'storage_unavailable'], 500);
    }
    return $handle;
}

function decodeState(string $content): array
{
    $state = json_decode($content, true);
    if (!is_array($state) || !isset($state['values']) || !is_array($state['values'])) {
        return emptyState();
    }
    return [
        'revision' => (int) ($state['revision'] ?? 0),
        'updated_at' => isset($state['updated_at']) ? (int) $state['updated_at'] : null,
        'values' => $state['values'],
    ];
}

function readState($handle): array
{
    rewind($handle);
    $content = stream_get_contents($handle);
    return decodeState($content === false ? '' : $content);
}

function writeState($handle, array $state): void
{
    $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        respond(['error' => 'encoding_failed'], 500);
    }
    rewind($handle);
    if (!ftruncate($handle, 0) || fwrite($handle, $json) === false || !fflush($handle)) {
        respond(['error' => 'storage_write_failed'], 500);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $handle = openStateFile();
    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        respond(['error' => 'storage_lock_failed'], 500);
    }
    $state = readState($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    respond($state);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(['error' => 'method_not_allowed'], 405);
}

$rawBody = file_get_contents('php://input', false, null, 0, MAX_BODY_BYTES + 1);
if ($rawBody === false || strlen($rawBody) > MAX_BODY_BYTES) {
    respond(['error' => 'request_too_large'], 413);
}
$request = json_decode($rawBody, true);
if (!is_array($request)) {
    respond(['error' => 'invalid_json'], 400);
}

$handle = openStateFile();
if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    respond(['error' => 'storage_lock_failed'], 500);
}
$state = readState($handle);

if (($request['reset'] ?? false) === true) {
    $state['values'] = [];
} else {
    $updates = $request['updates'] ?? null;
    if (!is_array($updates) || $updates === []) {
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(['error' => 'updates_required'], 400);
    }
    foreach ($updates as $key => $value) {
        if (!is_string($key) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', $key)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            respond(['error' => 'invalid_key'], 400);
        }
        if (is_string($value)) {
            if (strlen($value) > MAX_VALUE_BYTES) {
                flock($handle, LOCK_UN);
                fclose($handle);
                respond(['error' => 'value_too_large'], 413);
            }
        } elseif (!is_bool($value)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            respond(['error' => 'invalid_value'], 400);
        }
        $state['values'][$key] = $value;
    }
}

$state['revision']++;
$state['updated_at'] = time();
writeState($handle, $state);
flock($handle, LOCK_UN);
fclose($handle);
respond($state);
