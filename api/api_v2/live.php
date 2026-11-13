<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');
$stored = [];
if (is_file(DATA_FILE)) {
    $decoded = json_decode((string)file_get_contents(DATA_FILE), true);
    if (is_array($decoded)) $stored = $decoded;
}
$accounts = [];
foreach (ALLOWED_ACCOUNTS as $id) {
    $a = $stored[$id] ?? [];
    $accounts[] = [
        'account_id'=>$id,
        'status'=>$a['status'] ?? 'WAITING',
        'balance'=>$a['balance'] ?? null,
        'equity'=>$a['equity'] ?? null,
        'deposit_awal'=>$a['deposit_awal'] ?? null,
        'daily_profit'=>$a['daily_profit'] ?? null,
        'total_profit'=>$a['total_profit'] ?? null,
        'daily_dd'=>$a['daily_dd'] ?? null,
        'total_penarikan'=>$a['total_penarikan'] ?? null,
        'updated_at'=>$a['updated_at'] ?? null
    ];
}
echo json_encode(['ok'=>true,'accounts'=>$accounts], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
