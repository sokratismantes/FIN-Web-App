<?php
declare(strict_types=1);

/**
 * SQLite storage (server-side). Keeps your app offline-friendly.
 * Make sure the folder is writable by PHP (for fin.sqlite).
 */

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $path = __DIR__ . DIRECTORY_SEPARATOR . 'fin.sqlite';
  $pdo = new PDO('sqlite:' . $path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Better concurrency + fewer "database is locked"
  $pdo->exec("PRAGMA journal_mode = WAL;");
  $pdo->exec("PRAGMA synchronous = NORMAL;");
  $pdo->exec("PRAGMA busy_timeout = 5000;"); // wait up to 5s if DB is busy
  $pdo->exec("PRAGMA foreign_keys = ON;");
  $pdo->exec("PRAGMA temp_store = MEMORY;");

  init_schema($pdo);
  return $pdo;
}

function init_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      k TEXT PRIMARY KEY,
      v TEXT NOT NULL
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      type TEXT NOT NULL CHECK(type IN ('expense','income')),
      name TEXT NOT NULL,
      icon TEXT NOT NULL DEFAULT '🏷️',
      sort_order INTEGER NOT NULL DEFAULT 0
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS transactions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      type TEXT NOT NULL CHECK(type IN ('expense','income')),
      amount_cents INTEGER NOT NULL,
      date TEXT NOT NULL, -- YYYY-MM-DD
      category_id INTEGER NOT NULL,
      account TEXT NOT NULL DEFAULT 'Card',
      note TEXT NOT NULL DEFAULT '',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS budgets (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      month TEXT NOT NULL, -- YYYY-MM-01
      category_id INTEGER NOT NULL,
      limit_cents INTEGER NOT NULL,
      UNIQUE(month, category_id),
      FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS market_cache (
      symbol TEXT PRIMARY KEY,
      payload TEXT NOT NULL,
      last_date TEXT NOT NULL DEFAULT '',
      fetched_at INTEGER NOT NULL DEFAULT 0
    );
  ");

  // Seed default categories if empty
  $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM categories")->fetch()['c'];
  if ($count === 0) {
    $seed = [
      ['expense','Food','🥗',10],
      ['expense','Groceries','🛒',20],
      ['expense','Coffee','☕',30],
      ['expense','Transport','🚌',40],
      ['expense','Bills','🧾',50],
      ['expense','Rent','🏠',60],
      ['expense','Health','🩺',70],
      ['expense','Entertainment','🎮',80],
      ['income','Salary','💼',10],
      ['income','Bonus','🎁',20],
      ['income','Refund','↩️',30],
      ['income','Other','✨',40],
    ];
    $ins = $pdo->prepare("INSERT INTO categories(type,name,icon,sort_order) VALUES(?,?,?,?)");
    foreach ($seed as $s) $ins->execute($s);
  }

  // Optional: default currency setting
  $hasCur = (int)$pdo->query("SELECT COUNT(*) AS c FROM settings WHERE k='currency'")->fetch()['c'];
  if ($hasCur === 0) {
    $pdo->prepare("INSERT INTO settings(k,v) VALUES('currency','EUR')")->execute();
  }
}

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function json_in(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || trim($raw) === '') return [];
  $data = json_decode($raw, true);
  if (!is_array($data)) json_out(['error' => 'Invalid JSON'], 400);
  return $data;
}

function method(): string {
  $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  // method override used by your JS: ?_method=PUT (with POST)
  if ($m === 'POST' && isset($_GET['_method'])) {
    $ov = strtoupper((string)$_GET['_method']);
    if (in_array($ov, ['PUT','DELETE','PATCH'], true)) return $ov;
  }
  return $m;
}

function require_fields(array $data, array $fields): void {
  foreach ($fields as $f) {
    if (!array_key_exists($f, $data)) json_out(['error' => "Missing field: $f"], 400);
  }
}

function to_cents(string $amount): int {
  // accepts "12", "12.3", "12.30"
  $amount = trim($amount);
  if ($amount === '') throw new RuntimeException('Amount required');
  if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $amount)) throw new RuntimeException('Invalid amount');
  $neg = str_starts_with($amount, '-');
  $n = ltrim($amount, '+');
  $parts = explode('.', $n, 2);
  $euros = (int)$parts[0];
  $dec = $parts[1] ?? '0';
  $dec = str_pad(substr($dec, 0, 2), 2, '0');
  $cents = abs($euros) * 100 + (int)$dec;
  if ($neg || $euros < 0) $cents = -$cents;
  return $cents;
}

function valid_date(string $d): bool {
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}