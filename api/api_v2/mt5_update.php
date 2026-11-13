<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(SMARTINVESTAMA_API_KEY, $key)) out(401, ['ok'=>false,'error'=>'Unauthorized']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok'=>false,'error'=>'POST required']);
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) out(400, ['ok'=>false,'error'=>'Invalid JSON']);
$id = (string)($body['account_id'] ?? '');
if (!in_array($id, ALLOWED_ACCOUNTS, true)) out(400, ['ok'=>false,'error'=>'Invalid account_id']);

$dir = dirname(DATA_FILE);
if (!is_dir($dir) && !mkdir($dir, 0750, true)) out(500, ['ok'=>false,'error'=>'Cannot create data directory']);
$fp = fopen(DATA_FILE, 'c+');
if (!$fp) out(500, ['ok'=>false,'error'=>'Cannot open storage']);
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$db = $raw ? json_decode($raw, true) : [];
if (!is_array($db)) $db = [];
$old = $db[$id] ?? [];

// Initial deposit is written once only. Later EA updates cannot overwrite it.
$lockedInitial = isset($old['deposit_awal']) && is_numeric($old['deposit_awal']) && (float)$old['deposit_awal'] > 0
    ? (float)$old['deposit_awal'] : null;
$candidate = $body['deposit_awal'] ?? null;
if ($lockedInitial === null && is_numeric($candidate) && (float)$candidate > 0) {
    $lockedInitial = (float)$candidate;
}
$fields = ['balance','equity','daily_profit','total_profit','daily_dd','total_penarikan'];
$row = [
    'account_id' => $id,
    'status' => 'ONLINE',
    'deposit_awal' => $lockedInitial,
    'updated_at' => gmdate('Y-m-d H:i:s') . ' UTC'
];
foreach ($fields as $field) {
    $v = $body[$field] ?? null;
    $row[$field] = is_numeric($v) ? (float)$v : null;
}
$db[$id] = $row;
rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);
out(200, ['ok'=>true,'account_id'=>$id,'deposit_awal'=>$lockedInitial,'total_penarikan'=>$row['total_penarikan']]);
