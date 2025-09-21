<?php
/* --- DB settings --- */
$host = '127.0.0.1';
$user = 'root';
$pass = '';                // XAMPP default
$db   = 'traveller_db';    // must match the DB you created

/* --- Connect --- */
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
  die('Connect error: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

/* --- Collect input --- */
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$country  = trim($_POST['countryofresidence'] ?? '');

/* --- Minimal validation --- */
$errors = [];
if ($fullname === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($country === '') $errors[] = 'Country is required.';
if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'DOB must be YYYY-MM-DD.';

if ($errors) {
  echo '❌ ' . implode(' ', $errors) . '<p><a href="form.html">Back</a></p>';
  exit;
}

/* --- Prepare & insert --- */
$stmt = $mysqli->prepare(
  "INSERT INTO traveller (fullname, email, phone, dob, countryofresidence) VALUES (?,?,?,?,?)"
);
$phoneParam = ($phone !== '') ? $phone : NULL;
$dobParam   = ($dob   !== '') ? $dob   : NULL;

$stmt->bind_param('sssss', $fullname, $email, $phoneParam, $dobParam, $country);

if ($stmt->execute()) {
  echo '✅ Traveller saved. <a href="form.html">Add another</a>';
} else {
  // 1062 = duplicate key on unique email
  if ($stmt->errno == 1062) {
    echo '❌ Email already exists. <a href="form.html">Back</a>';
  } else {
    echo '❌ Insert failed: ' . htmlspecialchars($stmt->error) . ' <a href="form.html">Back</a>';
  }
}
$stmt->close();

/* --- Show latest rows so you can verify in the browser --- */
$result = $mysqli->query("
  SELECT travellerID, fullname, email, phone, dob, countryofresidence
  FROM traveller ORDER BY travellerID DESC LIMIT 10
");

echo '<h3>Latest travellers</h3>
      <table border="1" cellpadding="6">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>DOB</th><th>Country</th></tr>';
while ($row = $result->fetch_assoc()) {
  echo '<tr>
          <td>'.htmlspecialchars($row['travellerID']).'</td>
          <td>'.htmlspecialchars($row['fullname']).'</td>
          <td>'.htmlspecialchars($row['email']).'</td>
          <td>'.htmlspecialchars($row['phone']).'</td>
          <td>'.htmlspecialchars($row['dob']).'</td>
          <td>'.htmlspecialchars($row['countryofresidence']).'</td>
        </tr>';
}
echo '</table>';
