<?php require __DIR__ . '/db_mysqli.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SIT772 9.2D – Insert Traveller</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body{font-family:system-ui,Arial,sans-serif;max-width:920px;margin:28px auto;padding:0 16px}
    form{border:1px solid #ddd;border-radius:12px;padding:16px;margin:12px 0}
    label{display:block;margin:10px 0 4px}
    input,select,button{padding:8px;width:100%;max-width:460px}
    .row{display:flex;gap:16px;flex-wrap:wrap}.row>div{flex:1 1 300px}
    .msg{margin:12px 0;padding:10px;border-radius:8px}
    .ok{background:#eef9f0;border:1px solid #9ecf9e;color:#064}
    .error{background:#fee;border:1px solid #f99;color:#900}
    table{border-collapse:collapse;width:100%;margin-top:12px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
  </style>
</head>
<body>
  <h1>Insert Traveller</h1>
  <p><small>Fields marked * are required.</small></p>

  <?php
    if (isset($_GET['status'])) {
      $cls = $_GET['status'] === 'ok' ? 'ok' : 'error';
      $msg = $_GET['msg'] ?? '';
      echo '<div class="msg '.$cls.'">'.htmlspecialchars($msg).'</div>';
    }
  ?>

  <form method="post" action="process_traveller.php" novalidate>
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
      </div>
      <div>
        <label for="dob">Date of birth</label>
        <input id="dob" name="dob" type="date" />
      </div>
    </div>

    <div class="row">
      <div>
        <label for="countryofresidence">Country of residence *</label>
        <select id="countryofresidence" name="countryofresidence" required>
          <option value="">Select a country</option>
          <option>Australia</option><option>Thailand</option><option>China</option>
          <option>India</option><option>Vietnam</option><option>Indonesia</option>
          <option>United States</option><option>United Kingdom</option><option>Other</option>
        </select>
      </div>
    </div>

    <p><button type="submit">Insert</button></p>
  </form>

  <h2>Recent Travellers (latest 10)</h2>
  <?php
    $res = $mysqli->query("SELECT travellerID, fullname, email, phone, dob, countryofresidence
                           FROM traveller ORDER BY travellerID DESC LIMIT 10");
    if (!$res || $res->num_rows === 0) {
      echo "<p>No records yet.</p>";
    } else {
      echo "<table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>DOB</th><th>Country</th></tr></thead><tbody>";
      while ($r = $res->fetch_assoc()) {
        echo "<tr>
          <td>".htmlspecialchars($r['travellerID'])."</td>
          <td>".htmlspecialchars($r['fullname'])."</td>
          <td>".htmlspecialchars($r['email'])."</td>
          <td>".htmlspecialchars($r['phone'])."</td>
          <td>".htmlspecialchars($r['dob'])."</td>
          <td>".htmlspecialchars($r['countryofresidence'])."</td>
        </tr>";
      }
      echo "</tbody></table>";
      $res->free();
    }
  ?>
</body>
</html>
