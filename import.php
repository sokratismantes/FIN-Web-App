<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = db();
$in = json_in();

try {
  if (!is_array($in)) json_out(['error' => 'Invalid JSON'], 400);

  $categories = $in['categories'] ?? null;
  $transactions = $in['transactions'] ?? null;
  $budgets = $in['budgets'] ?? null;
  $settings = $in['settings'] ?? [];

  if (!is_array($categories) || !is_array($transactions) || !is_array($budgets)) {
    json_out(['error' => 'Backup missing categories/transactions/budgets arrays'], 400);
  }

  $pdo->beginTransaction();

  // wipe (respect FK order)
  $pdo->exec("DELETE FROM budgets;");
  $pdo->exec("DELETE FROM transactions;");
  $pdo->exec("DELETE FROM categories;");

  // reset autoincrement
  $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('budgets','transactions','categories');");

  // settings: replace keys
  $pdo->exec("DELETE FROM settings;");
  if (is_array($settings)) {
    $insS = $pdo->prepare("INSERT INTO settings(k,v) VALUES(?,?)");
    foreach ($settings as $k => $v) {
      $k = (string)$k;
      $v = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      if ($k !== '') $insS->execute([$k, $v]);
    }
  }

  // categories (keep original IDs if present)
  $insC = $pdo->prepare("INSERT INTO categories(id,type,name,icon,sort_order) VALUES(?,?,?,?,?)");
  foreach ($categories as $c) {
    if (!is_array($c)) continue;
    $id = (int)($c['id'] ?? 0);
    $type = (string)($c['type'] ?? '');
    $name = trim((string)($c['name'] ?? ''));
    $icon = trim((string)($c['icon'] ?? '🏷️'));
    $sort = (int)($c['sort_order'] ?? 0);

    if ($id <= 0 || !in_array($type, ['expense','income'], true) || $name === '') continue;
    if ($icon === '') $icon = '🏷️';

    $insC->execute([$id, $type, $name, $icon, $sort]);
  }

  // transactions
  $insT = $pdo->prepare("
    INSERT INTO transactions(id,type,amount_cents,date,category_id,account,note,created_at)
    VALUES(?,?,?,?,?,?,?,?)
  ");
  foreach ($transactions as $t) {
    if (!is_array($t)) continue;
    $id = (int)($t['id'] ?? 0);
    $type = (string)($t['type'] ?? '');
    $amount = (int)($t['amount_cents'] ?? 0);
    $date = (string)($t['date'] ?? '');
    $catId = (int)($t['category_id'] ?? 0);
    $account = (string)($t['account'] ?? 'Card');
    $note = (string)($t['note'] ?? '');
    $created = (string)($t['created_at'] ?? gmdate('c'));

    if ($id <= 0 || !in_array($type, ['expense','income'], true) || !valid_date($date) || $catId <= 0) continue;
    $insT->execute([$id, $type, $amount, $date, $catId, $account, $note, $created]);
  }

  // budgets
  $insB = $pdo->prepare("INSERT INTO budgets(id,month,category_id,limit_cents) VALUES(?,?,?,?)");
  foreach ($budgets as $b) {
    if (!is_array($b)) continue;
    $id = (int)($b['id'] ?? 0);
    $month = (string)($b['month'] ?? '');
    $catId = (int)($b['category_id'] ?? 0);
    $limit = (int)($b['limit_cents'] ?? 0);

    if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-01$/', $month) || $catId <= 0 || $limit <= 0) continue;
    $insB->execute([$id, $month, $catId, $limit]);
  }

  $pdo->commit();
  json_out(['ok' => true]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e->getMessage()], 500);
}