<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = db();

$settings = [];
foreach ($pdo->query("SELECT k,v FROM settings") as $row) $settings[$row['k']] = $row['v'];

$categories = $pdo->query("SELECT id,type,name,icon,sort_order FROM categories ORDER BY type, sort_order, id")->fetchAll();
$transactions = $pdo->query("SELECT id,type,amount_cents,date,category_id,account,note,created_at FROM transactions ORDER BY date DESC, id DESC")->fetchAll();
$budgets = $pdo->query("SELECT id,month,category_id,limit_cents FROM budgets ORDER BY month DESC, id DESC")->fetchAll();

$payload = [
  'app' => 'Fin Offline Finance',
  'version' => 1,
  'exported_at' => gmdate('c'),
  'settings' => $settings,
  'categories' => $categories,
  'transactions' => $transactions,
  'budgets' => $budgets
];

$filename = 'fin-backup-' . date('Y-m-d_H-i') . '.json';

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);