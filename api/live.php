<?php
declare(strict_types=1);
/*
 * SmartInvestama Live API — kompatibel dengan Google Apps Script lama.
 * Catatan: file ini meneruskan deposit_awal dan total_penarikan dari upstream.
 * File ini TIDAK menghitung transaksi pertama/top-up/withdrawal dari history MT5.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const UPSTREAM_URL = 'https://script.google.com/macros/s/AKfycbw8AEMeie38BBxW3S-22bf0WrI_7vGHPkpANGEsoQiTv7Jg86X8rrTEQ3AtDyzQol5D_Q/exec?action=live';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function numberOrNull(mixed $v): int|float|null {
    if ($v === null || $v === '' || !is_numeric($v)) return null;
    return 0 + $v;
}
if (!function_exists('curl_init')) respond(500, ['ok'=>false,'error'=>'PHP cURL extension is not enabled']);

$ch = curl_init(UPSTREAM_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>5,
    CURLOPT_CONNECTTIMEOUT=>8, CURLOPT_TIMEOUT=>20, CURLOPT_SSL_VERIFYPEER=>true,
    CURLOPT_HTTPHEADER=>['Accept: application/json']
]);
$raw = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false || $status !== 200) {
    respond(502, ['ok'=>false,'error'=>'Upstream API unavailable','http_status'=>$status,'detail'=>$curlError ?: null]);
}
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['ok']) || !isset($data['accounts']) || !is_array($data['accounts'])) {
    respond(502, ['ok'=>false,'error'=>'Invalid upstream API response']);
}

$allowed = ['ACC01','ACC02','ACC03'];
$accounts = [];
foreach ($data['accounts'] as $a) {
    if (!is_array($a)) continue;
    $id = (string)($a['account_id'] ?? '');
    if (!in_array($id, $allowed, true)) continue;

    $initial = numberOrNull($a['deposit_awal'] ?? null);
    if ($initial !== null && $initial <= 0) $initial = null;
    $withdrawals = numberOrNull($a['total_penarikan'] ?? $a['total_withdrawal'] ?? $a['total_wd'] ?? null);

    $accounts[] = [
        'account_id'=>$id,
        'status'=>$a['status'] ?? 'WAITING',
        'balance'=>numberOrNull($a['balance'] ?? null),
        'equity'=>numberOrNull($a['equity'] ?? null),
        'deposit_awal'=>$initial,
        'daily_profit'=>numberOrNull($a['daily_profit'] ?? null),
        'total_profit'=>numberOrNull($a['total_profit'] ?? null),
        'daily_dd'=>numberOrNull($a['daily_dd'] ?? null),
        'total_penarikan'=>$withdrawals,
        'updated_at'=>$a['updated_at'] ?? null
    ];
}
respond(200, [
    'ok'=>true,
    'accounts'=>$accounts,
    'rules'=>[
        'initial_deposit'=>'Upstream wajib mengirim deposit positif pertama saja; top-up dikecualikan.',
        'total_penarikan'=>'Upstream wajib mengirim akumulasi withdrawal saja.'
    ]
]);
