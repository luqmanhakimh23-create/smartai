
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
$days = max(1, min(365, $days));

$baseUrl = 'https://script.google.com/macros/s/AKfycbw8AEMeie38BBxW3S-22bf0WrI_7vGHPkpANGEsoQiTv7Jg86X8rrTEQ3AtDyzQol5D_Q/exec';

$url = $baseUrl . '?action=history&days=' . $days;

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'SmartInvestama-History/1.0',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Connection failed',
        'detail' => $curlError
    ]);
    exit;
}

$data = json_decode($response, true);

if (
    $httpCode !== 200 ||
    !is_array($data) ||
    ($data['ok'] ?? false) !== true ||
    !isset($data['history']) ||
    !is_array($data['history'])
) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'History API unavailable',
        'upstream_http_code' => $httpCode,
        'upstream_response' => substr($response, 0, 300)
    ]);
    exit;
}

$allowed = ['ACC01', 'ACC02', 'ACC03'];
$history = [];

foreach ($data['history'] as $item) {
    if (!is_array($item)) {
        continue;
    }

    $id = $item['account_id'] ?? '';
    $equity = $item['equity'] ?? null;
    $timestamp = $item['timestamp'] ?? '';

    if (
        !in_array($id, $allowed, true) ||
        !is_numeric($equity) ||
        !is_string($timestamp) ||
        strtotime($timestamp) === false
    ) {
        continue;
    }

    $history[] = [
        'timestamp' => $timestamp,
        'account_id' => $id,
        'equity' => (float) $equity
    ];
}

echo json_encode([
    'ok' => true,
    'days' => $days,
    'history' => $history
], JSON_UNESCAPED_SLASHES);
