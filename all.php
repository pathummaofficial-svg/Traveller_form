<?php
/*****************************************************
 * SIT772 9.2D — Single-file demo (MySQLi)
 * File: index.php  (put in C:\xampp\htdocs\Traveller\)
 *****************************************************/

/* ---------- 0) CONFIG: set your DB name here ---------- */
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';             // XAMPP default
$DB_NAME = 'traveller_db'; // <- your database name

/* ---------- 1) CONNECT ---------- */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
  // First connect to server, no DB yet (in case DB doesn't exist)
  $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
  $mysqli->set_charset('utf8mb4');

  // Ensure database exists, then select it
  $mysqli->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $mysqli->select_db($DB_NAME);

  // Ensure table exists (robust against first-run)
  $mysqli->query("
    CREATE TABLE IF NOT EXISTS traveller (
      travellerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
      fullname VARCHAR(100) NOT NULL,
      email VARCHAR(191) NOT NULL,
      phone VARCHAR(20) NULL,
      dob DATE NULL,
      countryofresidence VARCHAR(60) NOT NULL,
      PRIMARY KEY (travellerID),
      UNIQUE KEY uq_traveller_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

} catch (mysqli_sql_exception $e) {
  http_response_code(500);
  echo "<h1>Database error</h1><pre>".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}

/* ---------- 2) HELPERS ---------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- 3) HANDLE POST (no redirects needed) ---------- */
$status = null; $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $dob      = trim($_POST['dob'] ?? '');
  $country  = trim($_POST['countryofresidence'] ?? '');

  // Validate
  $errors = [];
  if ($fullname === '') $errors[] = 'Full name is required.';
  if ($email === '') $errors[] = 'Email is required.';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email format is invalid.';
  if ($country === '') $errors[] = 'Country of residence is required.';
  if ($phone !== '' && !preg_match('/^\+?[0-9()\-\s]{6,20}$/', $phone)) $errors[] = 'Phone format looks invalid.';
  if ($dob !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    $ok = $d && $d->format('Y-m-d') === $dob;
    if (!$ok) $errors[] = 'DOB must be YYYY-MM-DD.';
    else {
      $today = new DateTime('today');
      if ($d >= $today) $errors[] = 'DOB must be in the past.';
    }
  }

  if ($errors) {
    $status = 'error';
    $msg = implode(' ', $errors);
  } else {
    try {
      $stmt = $mysqli->prepare("
        INSERT INTO traveller (fullname, email, phone, dob, countryofresidence)
        VALUES (?, ?, ?, ?, ?)
      ");
      // Convert empty optional fields to NULL
      $phoneParam = ($phone === '') ? null : $phone;
      $dobParam   = ($dob   === '') ? null : $dob;

      $stmt->bind_param('sssss', $fullname, $email, $phoneParam, $dobParam, $country);
      $stmt->execute();

      $status = 'ok';
      $msg = 'Traveller inserted successfully.';
      $stmt->close();
    } catch (mysqli_sql_exception $e) {
      // 1062 = duplicate key (email unique)
      if ($e->getCode() == 1062) {
        $status = 'error';
        $msg = 'Insert failed. Email already exists.';
      } else {
        $status = 'error';
        $msg = 'Insert failed. ' . $e->getMessage();
      }
    }
  }
}

/* ---------- 4) FETCH LATEST 10 ROWS ---------- */
$rows = [];
try {
  $res = $mysqli->query("
    SELECT travellerID, fullname, email, phone, dob, countryofresidence
    FROM traveller
    ORDER BY travellerID DESC
    LIMIT 10
  ");
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $res->free();
} catch (mysqli_sql_exception $e) {
  // table creation above should prevent this, but handle anyway
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SIT772 9.2D – Single File (Traveller)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root { --b:#ddd; --ok:#eef9f0; --okb:#9ecf9e; --er:#fee; --erb:#f99; }
    body{font-family:system-ui,Arial,sans-serif;max-width:960px;margin:28px auto;padding:0 16px}
    h1{margin:0 0 6px}
    p.sub{color:#666;margin:0 0 14px}
    form{border:1px solid var(--b);border-radius:12px;padding:16px;margin:12px 0}
    label{display:block;margin:10px 0 4px}
    input,select,button{padding:9px 10px;width:100%;max-width:460px}
    input,select{border:1px solid var(--b);border-radius:8px}
    button{border-radius:10px;border:1px solid var(--b);cursor:pointer}
    .row{display:flex;gap:16px;flex-wrap:wrap}
    .row>div{flex:1 1 300px}
    small{color:#666}
    .msg{margin:12px 0;padding:10px;border-radius:8px}
    .ok{background:var(--ok);border:1px solid var(--okb);color:#064}
    .error{background:var(--er);border:1px solid var(--erb);color:#900}
    table{border-collapse:collapse;width:100%;margin-top:12px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    tfoot td{color:#666;font-size:.92rem}
    .note{font-size:.92rem;color:#444}
    code{background:#f6f8fa;padding:2px 4px;border-radius:4px}
  </style>
</head>
<body>
  <h1>Insert Traveller</h1>
  <p class="sub">Fields marked * are required. Database: <code><?=h($DB_NAME)?></code>, table: <code>traveller</code>.</p>

  <?php if ($status): ?>
    <div class="msg <?=$status==='ok'?'ok':'error'?>"><?=h($msg)?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="row">
      <div>
        <label for="fullname">Full name *</label>
        <input id="fullname" name="fullname" maxlength="100" required />
      </div>
      <div>
        <label for="email">Email *</label>
        <input id="email" name="email" type="email" maxlength="191" required placeholder="name@example.com" />
      </div>
    </div>

    <div class="row">
      <div>
        <label for="phone">Phone</label>
        <input id="phone" name="phone" maxlength="20" placeholder="+61 4xx xxx xxx" />
        <div class="note">Digits, spaces, parentheses, dashes, optional leading +.</div>
      </div>
      <div>
        <label for="dob">Date of birth</label>
        <input id="dob" name="dob" type="date" />
        <small>YYYY-MM-DD</small>
      </div>
    </div>

    <div class="row">
      <div>
        <label for="countryofresidence">Country of residence *</label>
        <select id="countryofresidence" name="countryofresidence" required>
          <option value="">Select a country</option>
          <option>Australia</option>
          <option>Thailand</option>
          <option>China</option>
          <option>India</option>
          <option>Vietnam</option>
          <option>Indonesia</option>
          <option>United States</option>
          <option>United Kingdom</option>
          <option>Other</option>
        </select>
      </div>
    </div>

    <p><button type="submit">Insert</button></p>
  </form>

  <h2>Recent Travellers (latest 10)</h2>
  <?php if (!$rows): ?>
    <p>No records yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>DOB</th><th>Country</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?=h($r['travellerID'])?></td>
            <td><?=h($r['fullname'])?></td>
            <td><?=h($r['email'])?></td>
            <td><?=h($r['phone'])?></td>
            <td><?=h($r['dob'])?></td>
            <td><?=h($r['countryofresidence'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="6">Showing up to 10 most recent records.</td></tr>
      </tfoot>
    </table>
  <?php endif; ?>

  <p class="note">
    If you ever see “table doesn’t exist,” this page already creates it.  
    If you need a different DB name or password, change the config at the top.
  </p>
</body>
</html>
