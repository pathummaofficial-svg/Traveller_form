<?php
require __DIR__ . '/db_mysqli.php';

function back($status, $msg) {
  header('Location: insert_traveller.php?' . http_build_query(['status'=>$status,'msg'=>$msg]));
  exit;
}

// Gather
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
  else if ($d >= new DateTime('today')) $errors[] = 'DOB must be in the past.';
}
if ($errors) back('error', implode(' ', $errors));

// Insert
$stmt = $mysqli->prepare(
  "INSERT INTO traveller (fullname, email, phone, dob, countryofresidence)
   VALUES (?,?,?,?,?)"
);
if (!$stmt) back('error', 'Prepare failed: ' . $mysqli->error);

// Allow NULLs for optional fields
$phoneParam = ($phone !== '') ? $phone : null;
$dobParam   = ($dob   !== '') ? $dob   : null;

$stmt->bind_param('sssss', $fullname, $email, $phoneParam, $dobParam, $country);

if ($stmt->execute()) {
  $stmt->close();
  back('ok', 'Traveller inserted successfully.');
} else {
  // 1062 = duplicate key (e.g., unique email)
  if ($stmt->errno == 1062) {
    $stmt->close();
    back('error', 'Insert failed. Email already exists.');
  }
  $err = $stmt->error;
  $stmt->close();
  back('error', 'Insert failed. ' . $err);
}
