<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = db();
$action = (string)($_GET['action'] ?? '');
$m = method();

try {
  switch ($action) {

    case 'settings_set': {
      // Minimal key/value settings upsert (used by budgets groups JSON)
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      require_fields($in, ['k','v']);
      $k = trim((string)$in['k']);
      $v = (string)$in['v'];
      if ($k === '') json_out(['error' => 'Invalid key'], 400);

      $stmt = $pdo->prepare("INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v");
      $stmt->execute([$k, $v]);
      json_out(['ok' => true]);
    }

    case 'bootstrap': {
      if ($m !== 'GET') json_out(['error' => 'Method not allowed'], 405);
      $settings = [];
      foreach ($pdo->query("SELECT k,v FROM settings") as $row) $settings[$row['k']] = $row['v'];

      $cats = $pdo->query("SELECT id,type,name,icon,sort_order FROM categories ORDER BY type, sort_order, id")->fetchAll();
      json_out(['settings' => $settings, 'categories' => $cats]);
    }

    case 'categories': {
      if ($m === 'GET') {
        $items = $pdo->query("SELECT id,type,name,icon,sort_order FROM categories ORDER BY type, sort_order, id")->fetchAll();
        json_out(['items' => $items]);
      }
      if ($m === 'POST') {
        $in = json_in();
        require_fields($in, ['type','name']);
        $type = (string)$in['type'];
        if (!in_array($type, ['expense','income'], true)) json_out(['error' => 'Invalid type'], 400);
        $name = trim((string)$in['name']);
        if ($name === '') json_out(['error' => 'Name required'], 400);
        $icon = trim((string)($in['icon'] ?? '🏷️'));
        if ($icon === '') $icon = '🏷️';

        // Put at end of that type
        $max = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0) AS m FROM categories WHERE type=?");
        $max->execute([$type]);
        $next = ((int)$max->fetch()['m']) + 10;

        $stmt = $pdo->prepare("INSERT INTO categories(type,name,icon,sort_order) VALUES(?,?,?,?)");
        $stmt->execute([$type, $name, $icon, $next]);

        json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
      }
      json_out(['error' => 'Method not allowed'], 405);
    }

    case 'categories_reorder': {
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      require_fields($in, ['type','ids']);
      $type = (string)$in['type'];
      if (!in_array($type, ['expense','income'], true)) json_out(['error' => 'Invalid type'], 400);
      if (!is_array($in['ids'])) json_out(['error' => 'ids must be array'], 400);

      $ids = array_values(array_filter(array_map(fn($x)=> (int)$x, $in['ids']), fn($x)=> $x > 0));
      if (!$ids) json_out(['error' => 'Empty ids'], 400);

      $pdo->beginTransaction();
      $upd = $pdo->prepare("UPDATE categories SET sort_order=? WHERE id=? AND type=?");
      $order = 10;
      foreach ($ids as $id) {
        $upd->execute([$order, $id, $type]);
        $order += 10;
      }
      $pdo->commit();

      json_out(['ok' => true]);
    }

    case 'category': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) json_out(['error' => 'Invalid id'], 400);

      if ($m === 'PUT') {
        $in = json_in();
        $name = trim((string)($in['name'] ?? ''));
        $icon = trim((string)($in['icon'] ?? '🏷️'));
        if ($name === '') json_out(['error' => 'Name required'], 400);
        if ($icon === '') $icon = '🏷️';

        $stmt = $pdo->prepare("UPDATE categories SET name=?, icon=? WHERE id=?");
        $stmt->execute([$name, $icon, $id]);
        json_out(['ok' => true]);
      }

      if ($m === 'DELETE') {
        // Simple delete (will fail if used due to FK) - kept for compatibility
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
        $stmt->execute([$id]);
        json_out(['ok' => true]);
      }

      json_out(['error' => 'Method not allowed'], 405);
    }

    case 'category_delete': {
      // your JS calls POST action=category_delete with {id, move_to?}
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) json_out(['error' => 'Invalid id'], 400);

      $cat = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
      $cat->execute([$id]);
      $catRow = $cat->fetch();
      if (!$catRow) json_out(['error' => 'Category not found'], 404);

      $used = $pdo->prepare("SELECT COUNT(*) AS c FROM transactions WHERE category_id=?");
      $used->execute([$id]);
      $cnt = (int)$used->fetch()['c'];

      $moveTo = isset($in['move_to']) ? (int)$in['move_to'] : 0;

      $pdo->beginTransaction();

      if ($cnt > 0) {
        if ($moveTo <= 0) {
          $pdo->rollBack();
          json_out(['error' => 'CATEGORY_IN_USE'], 409);
        }
        // validate move_to same type
        $t = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
        $t->execute([$moveTo]);
        $tRow = $t->fetch();
        if (!$tRow || $tRow['type'] !== $catRow['type']) {
          $pdo->rollBack();
          json_out(['error' => 'Invalid move_to'], 400);
        }

        $mv = $pdo->prepare("UPDATE transactions SET category_id=? WHERE category_id=?");
        $mv->execute([$moveTo, $id]);
      }

      $del = $pdo->prepare("DELETE FROM categories WHERE id=?");
      $del->execute([$id]);

      $pdo->commit();
      json_out(['ok' => true]);
    }

    case 'transactions': {
      if ($m === 'GET') {
        $from = (string)($_GET['from'] ?? '');
        $to = (string)($_GET['to'] ?? '');
        if (!valid_date($from) || !valid_date($to)) json_out(['error' => 'Invalid date range'], 400);

        $type = (string)($_GET['type'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));

        $where = ["t.date >= :from", "t.date <= :to"];
        $params = [':from' => $from, ':to' => $to];

        if ($type !== '') {
          if (!in_array($type, ['expense','income'], true)) json_out(['error' => 'Invalid type'], 400);
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
            t.id, t.type, t.amount_cents, t.date, t.category_id, t.account, t.note, t.created_at,
            c.name AS category_name, c.icon AS category_icon
          FROM transactions t
          JOIN categories c ON c.id = t.category_id
          WHERE " . implode(" AND ", $where) . "
          ORDER BY t.date DESC, t.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        json_out(['items' => $items]);
      }

      if ($m === 'POST') {
        $in = json_in();
        require_fields($in, ['type','amount','date','category_id','account','note']);
        $type = (string)$in['type'];
        if (!in_array($type, ['expense','income'], true)) json_out(['error' => 'Invalid type'], 400);

        $date = (string)$in['date'];
        if (!valid_date($date)) json_out(['error' => 'Invalid date'], 400);

        $catId = (int)$in['category_id'];
        if ($catId <= 0) json_out(['error' => 'Invalid category_id'], 400);

        $account = trim((string)$in['account']);
        if ($account === '') $account = 'Card';

        $note = (string)$in['note'];

        $amountCents = to_cents((string)$in['amount']);
        if ($amountCents === 0) json_out(['error' => 'Amount cannot be 0'], 400);

        // ensure category exists and matches type (optional strictness)
        $chk = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
        $chk->execute([$catId]);
        $c = $chk->fetch();
        if (!$c) json_out(['error' => 'Category not found'], 404);
        if ($c['type'] !== $type) json_out(['error' => 'Category type mismatch'], 400);

        $stmt = $pdo->prepare("
          INSERT INTO transactions(type,amount_cents,date,category_id,account,note)
          VALUES(?,?,?,?,?,?)
        ");
        $stmt->execute([$type, $amountCents, $date, $catId, $account, $note]);

        json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
      }

      json_out(['error' => 'Method not allowed'], 405);
    }

    case 'transaction': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) json_out(['error' => 'Invalid id'], 400);

      if ($m === 'PUT') {
        $in = json_in();
        require_fields($in, ['type','amount','date','category_id','account','note']);
        $type = (string)$in['type'];
        if (!in_array($type, ['expense','income'], true)) json_out(['error' => 'Invalid type'], 400);

        $date = (string)$in['date'];
        if (!valid_date($date)) json_out(['error' => 'Invalid date'], 400);

        $catId = (int)$in['category_id'];
        if ($catId <= 0) json_out(['error' => 'Invalid category_id'], 400);

        $account = trim((string)$in['account']);
        if ($account === '') $account = 'Card';
        $note = (string)$in['note'];

        $amountCents = to_cents((string)$in['amount']);
        if ($amountCents === 0) json_out(['error' => 'Amount cannot be 0'], 400);

        $chk = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
        $chk->execute([$catId]);
        $c = $chk->fetch();
        if (!$c) json_out(['error' => 'Category not found'], 404);
        if ($c['type'] !== $type) json_out(['error' => 'Category type mismatch'], 400);

        $stmt = $pdo->prepare("
          UPDATE transactions
          SET type=?, amount_cents=?, date=?, category_id=?, account=?, note=?
          WHERE id=?
        ");
        $stmt->execute([$type, $amountCents, $date, $catId, $account, $note, $id]);

        json_out(['ok' => true]);
      }

      if ($m === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id=?");
        $stmt->execute([$id]);
        json_out(['ok' => true]);
      }

      json_out(['error' => 'Method not allowed'], 405);
    }

    case 'budget_delete': {
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      require_fields($in, ['month','category_id']);

      $month = (string)$in['month'];
      if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

      $categoryId = (int)$in['category_id'];
      if ($categoryId <= 0) json_out(['error' => 'Invalid category_id'], 400);

      $stmt = $pdo->prepare("DELETE FROM budgets WHERE month=? AND category_id=?");
      $stmt->execute([$month, $categoryId]);

      json_out(['ok' => true]);
    }

    case 'budget_move': {
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      require_fields($in, ['month','from_category_id','to_category_id','limit']);

      $month = (string)$in['month'];
      if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

      $from = (int)$in['from_category_id'];
      $to   = (int)$in['to_category_id'];
      if ($from <= 0 || $to <= 0) json_out(['error' => 'Invalid category id'], 400);

      $limitCents = to_cents((string)$in['limit']);
      if ($limitCents <= 0) json_out(['error' => 'Limit must be > 0'], 400);

      // validate target category is expense
      $chk = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
      $chk->execute([$to]);
      $c = $chk->fetch();
      if (!$c) json_out(['error' => 'Category not found'], 404);
      if ($c['type'] !== 'expense') json_out(['error' => 'Budget category must be expense'], 400);

      $pdo->beginTransaction();

      // delete old budget row (if exists)
      $del = $pdo->prepare("DELETE FROM budgets WHERE month=? AND category_id=?");
      $del->execute([$month, $from]);

      // upsert new one
      $stmt = $pdo->prepare("
        INSERT INTO budgets(month, category_id, limit_cents)
        VALUES(?,?,?)
        ON CONFLICT(month, category_id) DO UPDATE SET limit_cents=excluded.limit_cents
      ");
      $stmt->execute([$month, $to, $limitCents]);

      $pdo->commit();

      json_out(['ok' => true]);
    }

    case 'budgets': {
      if ($m === 'GET') {
        $month = (string)($_GET['month'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

        $stmt = $pdo->prepare("
          SELECT
            b.id, b.month, b.category_id, b.limit_cents,
            c.name AS category_name, c.icon AS category_icon,
            COALESCE((
              SELECT SUM(t.amount_cents)
              FROM transactions t
              WHERE t.type='expense'
                AND t.category_id = b.category_id
                AND t.date >= b.month
                AND t.date <= date(b.month, '+1 month', '-1 day')
            ), 0) AS used_cents
          FROM budgets b
          JOIN categories c ON c.id = b.category_id
          WHERE b.month = ?
          ORDER BY c.type, c.sort_order, c.id
        ");
        $stmt->execute([$month]);
        json_out(['items' => $stmt->fetchAll()]);
      }

      if ($m === 'POST') {
        $in = json_in();
        require_fields($in, ['month','category_id','limit']);
        $month = (string)$in['month'];
        if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

        $categoryId = (int)$in['category_id'];
        if ($categoryId <= 0) json_out(['error' => 'Invalid category_id'], 400);

        // Allow 0 as "disable" (delete) so the UI can remove a budget without a separate endpoint.
        // Negative limits are still invalid.
        $limitCents = to_cents((string)$in['limit']);
        if ($limitCents < 0) json_out(['error' => 'Limit must be >= 0'], 400);

        // only expense categories
        $chk = $pdo->prepare("SELECT id,type FROM categories WHERE id=?");
        $chk->execute([$categoryId]);
        $c = $chk->fetch();
        if (!$c) json_out(['error' => 'Category not found'], 404);
        if ($c['type'] !== 'expense') json_out(['error' => 'Budget category must be expense'], 400);

        // If limit is 0 => delete/disable the budget row
        if ($limitCents === 0) {
          $del = $pdo->prepare("DELETE FROM budgets WHERE month=? AND category_id=?");
          $del->execute([$month, $categoryId]);
          json_out(['ok' => true, 'deleted' => true]);
        }

        $stmt = $pdo->prepare("
          INSERT INTO budgets(month, category_id, limit_cents)
          VALUES(?,?,?)
          ON CONFLICT(month, category_id) DO UPDATE SET limit_cents=excluded.limit_cents
        ");
        $stmt->execute([$month, $categoryId, $limitCents]);

        json_out(['ok' => true]);
      }

      json_out(['error' => 'Method not allowed'], 405);
    }

    case 'budget_delete': {
      // Optional explicit delete endpoint (some clients call this)
      if ($m !== 'POST') json_out(['error' => 'Method not allowed'], 405);
      $in = json_in();
      require_fields($in, ['month','category_id']);

      $month = (string)$in['month'];
      if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

      $categoryId = (int)$in['category_id'];
      if ($categoryId <= 0) json_out(['error' => 'Invalid category_id'], 400);

      $del = $pdo->prepare("DELETE FROM budgets WHERE month=? AND category_id=?");
      $del->execute([$month, $categoryId]);
      json_out(['ok' => true]);
    }

    case 'market_data': {
        if ($m !== 'GET') json_out(['error' => 'Method not allowed'], 405);

        $symbolsRaw = trim((string)($_GET['symbols'] ?? ''));
        if ($symbolsRaw === '') json_out(['error' => 'symbols required'], 400);

        $days = (int)($_GET['days'] ?? 260);
        if ($days < 30) $days = 30;
        if ($days > 800) $days = 800;

        $symbols = array_values(array_filter(array_map(function($s){
          $s = strtoupper(trim($s));
          return $s !== '' ? $s : '';
        }, explode(',', $symbolsRaw))));
        if (!$symbols) json_out(['error' => 'symbols required'], 400);

        foreach ($symbols as $s) {
          if (!preg_match('/^[A-Z0-9\.\-]{1,20}$/', $s)) json_out(['error' => 'Invalid symbol: '.$s], 400);
        }

        // expected last trading day = yesterday, weekend -> Friday
        $tz = new DateTimeZone('Europe/Athens');
        $expected = (new DateTimeImmutable('now', $tz))->modify('-1 day');
        while (in_array((int)$expected->format('N'), [6,7], true)) {
          $expected = $expected->modify('-1 day');
        }
        $expectedStr = $expected->format('Y-m-d');

        $ttl = 6 * 3600; // 6h
        $now = time();

        $sel = $pdo->prepare("SELECT payload,last_date,fetched_at FROM market_cache WHERE symbol=?");
        $up  = $pdo->prepare("
          INSERT INTO market_cache(symbol,payload,last_date,fetched_at)
          VALUES(?,?,?,?)
          ON CONFLICT(symbol) DO UPDATE SET
            payload=excluded.payload,
            last_date=excluded.last_date,
            fetched_at=excluded.fetched_at
        ");

        $items = [];

        foreach ($symbols as $sym) {
          $payload = null;
          $lastDate = '';
          $fetchedAt = 0;

          // read cache
          $sel->execute([$sym]);
          $row = $sel->fetch();
          if ($row) {
            $payload = json_decode((string)$row['payload'], true);
            if (!is_array($payload)) $payload = null;
            $lastDate = (string)($row['last_date'] ?? '');
            $fetchedAt = (int)($row['fetched_at'] ?? 0);
          }

          $freshByTtl = ($fetchedAt > 0 && ($now - $fetchedAt) < $ttl);

          // force refresh if behind expected last trading day
          $behind = ($lastDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDate) && $lastDate < $expectedStr);

          $mustRefresh = (!$payload) || (!$freshByTtl) || $behind;

          if ($mustRefresh) {
            // Stooq CSV
            $url = 'https://stooq.com/q/d/l/?s=' . urlencode(strtolower($sym)) . '&i=d';

            $ctx = stream_context_create([
              'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'header' => "User-Agent: FinOffline/1.0\r\nAccept: text/csv\r\n"
              ],
              'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
              ]
            ]);

            $csv = @file_get_contents($url, false, $ctx);

            if ($csv === false || trim($csv) === '') {
              $payload = [
                'symbol' => $sym,
                'last_close' => 0,
                'series' => [],
                'error' => 'FETCH_FAILED',
                'expected_last' => $expectedStr
              ];
              $lastDate = '';
            } else {
              $lines = preg_split("/\r\n|\n|\r/", trim($csv));
              $series = [];

              for ($i = 1; $i < count($lines); $i++) {
                $parts = str_getcsv($lines[$i]);
                if (count($parts) < 5) continue;

                $d = trim((string)$parts[0]);
                $cStr = trim((string)$parts[4]);

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;
                if ($cStr === '' || strtolower($cStr) === 'null') continue;

                $c = (float)$cStr;
                if ($c <= 0) continue;

                $series[] = ['d' => $d, 'c' => $c];
              }

              usort($series, fn($a,$b)=> strcmp($a['d'],$b['d']));
              if (count($series) > $days) $series = array_slice($series, -$days);

              $last = $series ? (float)$series[count($series)-1]['c'] : 0;
              $lastDate = $series ? (string)$series[count($series)-1]['d'] : '';

              $payload = [
                'symbol' => $sym,
                'last_close' => $last,
                'series' => $series,
                'expected_last' => $expectedStr
              ];
            }

            // write cache (even on fetch failed, to avoid hammering)
            $up->execute([$sym, json_encode($payload, JSON_UNESCAPED_UNICODE), $lastDate, $now]);
          }

          // normalize output
          $items[] = [
            'symbol' => (string)($payload['symbol'] ?? $sym),
            'last_close' => (float)($payload['last_close'] ?? 0),
            'series' => (array)($payload['series'] ?? []),
            'error' => $payload['error'] ?? null,
            'expected_last' => $expectedStr,
            'last_date' => $lastDate,
            'cached_at' => $fetchedAt
          ];
        }

        json_out([
          'items' => $items,
          'expected_last' => $expectedStr
        ]);
      }

    case 'stats_month': {
      if ($m !== 'GET') json_out(['error' => 'Method not allowed'], 405);
      $month = (string)($_GET['month'] ?? '');
      if (!preg_match('/^\d{4}-\d{2}-01$/', $month)) json_out(['error' => 'Invalid month'], 400);

      // month end helper in SQL (safe because month is validated)
      $from = $month;
      $d = new DateTimeImmutable($month);
      $to = $d->modify('last day of this month')->format('Y-m-d');

      $sum = $pdo->prepare("
        SELECT
          COALESCE(SUM(CASE WHEN type='income' THEN amount_cents END), 0) AS income_cents,
          COALESCE(SUM(CASE WHEN type='expense' THEN amount_cents END), 0) AS expense_cents
        FROM transactions
        WHERE date >= ? AND date <= ?
      ");
      $sum->execute([$from, $to]);
      $s = $sum->fetch();

      $income = (int)$s['income_cents'];
      $expense = (int)$s['expense_cents'];
      $balance = $income - $expense;

      $top = $pdo->prepare("
        SELECT c.id, c.name, c.icon, SUM(t.amount_cents) AS sum_cents
        FROM transactions t
        JOIN categories c ON c.id = t.category_id
        WHERE t.type='expense' AND t.date >= ? AND t.date <= ?
        GROUP BY c.id
        ORDER BY SUM(t.amount_cents) DESC
        LIMIT 6
      ");
      $top->execute([$from, $to]);

      json_out([
        'month' => $month,
        'from' => $from,
        'to' => $to,
        'income_cents' => $income,
        'expense_cents' => $expense,
        'balance_cents' => $balance,
        'top_expenses' => $top->fetchAll()
      ]);
    }

    default:
      json_out(['error' => 'Unknown action'], 400);
  }
} catch (Throwable $e) {
  json_out(['error' => $e->getMessage()], 500);
}