
<?php
// SmartInvestama Live Performance API
// File: public_html/api/live.php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$webAppURL = 'https://script.google.com/macros/s/AKfycbw8AEMeie38BBxW3S-22bf0WrI_7vGHPkpANGEsoQiTv7Jg86X8rrTEQ3AtDyzQol5D_Q/exec?action=live';

// Mengambil data dari Google Apps Script
$ch = curl_init($webAppURL);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// Memeriksa koneksi API
if ($response === false || $httpCode !== 200) {
    error_log('SmartInvestama API error: HTTP ' . $httpCode . ' ' . $curlError);

    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Upstream API unavailable'
    ]);
    exit;
}

$data = json_decode($response, true);

if (
    !is_array($data) ||
    empty($data['ok']) ||
    !isset($data['accounts']) ||
    !is_array($data['accounts'])
) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid upstream response'
    ]);
    exit;
}

// Daftar akun yang boleh ditampilkan
$allowedAccounts = ['ACC01', 'ACC02', 'ACC03'];

$accounts = [];

foreach ($data['accounts'] as $account) {

    $id = $account['account_id'] ?? '';

    if (!in_array($id, $allowedAccounts, true)) {
        continue;
    }

    $accounts[] = [
        'account_id' => $id,

        'status' => $account['status'] ?? 'WAITING',

        // Deposit awal tetap, bersumber dari
        // kolom Balance pada API Google Apps Script.
        'deposit_awal' => $account['deposit_awal']
            ?? $account['balance']
            ?? null,

        'equity' => $account['equity'] ?? null,

        'daily_profit' => $account['daily_profit'] ?? null,

        'total_profit' => $account['total_profit'] ?? null,

        'daily_dd' => $account['daily_dd'] ?? null,

        'updated_at' => $account['updated_at'] ?? null
    ];
}

// Mengirim data ke website
echo json_encode([
    'ok' => true,
    'accounts' => $accounts
], JSON_UNESCAPED_SLASHES);
