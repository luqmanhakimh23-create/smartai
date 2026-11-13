<?php
declare(strict_types=1);

/*
 * SmartInvestama Live API
 * Initial Deposit = transaksi deposit positif pertama pada riwayat MT5.
 *
 * PENTING:
 * File ini meneruskan deposit_awal dari Google Apps Script.
 * Google Apps Script / EA MT5 harus menghitung deposit_awal dari
 * transaksi deposit pertama saja, bukan total deposit, balance, atau equity.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const UPSTREAM_URL = 'https://script.google.com/macros/s/AKfycbw8AEMeie38BBxW3S-22bf0WrI_7vGHPkpANGEsoQiTv7Jg86X8rrTEQ3AtDyzQol5D_Q/exec?action=live';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('curl_init')) {
    respond(500, ['ok' => false, 'error' => 'PHP cURL extension is not enabled']);
}

$ch = curl_init(UPSTREAM_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);

$raw = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false || $status !== 200) {
    respond(502, [
        'ok' => false,
        'error' => 'Upstream API unavailable',
        'http_status' => $status,
        'detail' => $curlError ?: null
    ]);
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data['ok']) || !isset($data['accounts']) || !is_array($data['accounts'])) {
    respond(502, ['ok' => false, 'error' => 'Invalid upstream API response']);
}

$allowed = ['ACC01', 'ACC02', 'ACC03'];
$accounts = [];

foreach ($data['accounts'] as $account) {
    if (!is_array($account)) {
        continue;
    }

    $id = (string)($account['account_id'] ?? '');
    if (!in_array($id, $allowed, true)) {
        continue;
    }

    // Tidak pernah mengganti Initial Deposit dengan Equity/Balance/total deposit.
    $initialDeposit = null;
    if (isset($account['deposit_awal']) && is_numeric($account['deposit_awal'])) {
        $candidate = (float)$account['deposit_awal'];
        if ($candidate > 0) {
            $initialDeposit = $candidate;
        }
    }

    $accounts[] = [
        'account_id'   => $id,
        'status'       => $account['status'] ?? 'WAITING',
        'balance'      => $account['balance'] ?? null,
        'equity'       => $account['equity'] ?? null,
        'deposit_awal' => $initialDeposit,
        'daily_profit' => $account['daily_profit'] ?? null,
        'total_profit' => $account['total_profit'] ?? null,
        'daily_dd'     => $account['daily_dd'] ?? null,
        'updated_at'   => $account['updated_at'] ?? null,
    ];
}

respond(200, [
    'ok' => true,
    'accounts' => $accounts,
    'initial_deposit_rule' => 'First positive MT5 deposit transaction only; later top-ups are excluded.'
]);
