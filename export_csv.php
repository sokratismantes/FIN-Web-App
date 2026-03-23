<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = db();

$from = (string)($_GET['from'] ?? '');
$to   = (string)($_GET['to'] ?? '');
if (!valid_date($from) || !valid_date($to)) {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Invalid from/to dates";
  exit;
}

$type = trim((string)($_GET['type'] ?? ''));        // optional
$categoryId = (int)($_GET['category_id'] ?? 0);     // optional
$q = trim((string)($_GET['q'] ?? ''));              // optional

$where = ["t.date >= :from", "t.date <= :to"];
$params = [':from' => $from, ':to' => $to];

if ($type !== '') {
  if (!in_array($type, ['expense','income'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid type";
    exit;
  }
  $where[] = "t.type = :type";
  $params[':type'] = $type;
}
if ($categoryId > 0) {
  $where[] = "t.category_id = :cid";
  $params[':cid'] = $categoryId;
}
if ($q !== '') {
  $where[] = "(t.note LIKE :q OR t.account LIKE :q OR c.name LIKE :q)";
  $params[':q'] = '%' . $q . '%';
}

$sql = "
  SELECT
    t.id,
    t.date,
    t.type,
    c.name AS category,
    c.icon AS icon,
    t.account,
    t.note,
    t.amount_cents
  FROM transactions t
  JOIN categories c ON c.id = t.category_id
  WHERE " . implode(" AND ", $where) . "
  ORDER BY t.date DESC, t.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$filename = 'fin-' . $from . '_to_' . $to . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$out = fopen('php://output', 'w');
fputcsv($out, ['id','date','type','category','icon','account','note','amount']); // header

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  // amount as decimal with 2 digits
  $amount = number_format(((int)$r['amount_cents']) / 100, 2, '.', '');
  fputcsv($out, [
    $r['id'],
    $r['date'],
    $r['type'],
    $r['category'],
    $r['icon'],
    $r['account'],
    $r['note'],
    $amount
  ]);
}
fclose($out);