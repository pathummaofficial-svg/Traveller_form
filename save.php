<?php
// save.php — minimal + private redirect

$formPage = 'index.html';     // <- we renamed the form to index.html

// DB config
$host = '127.0.0.1';
$user = 'root';
$pass = '';                   // XAMPP default
$db   = 'traveller_db';

// connect
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
  header('Location: '.$formPage.'?' . http_build_query([
    'status' => 'error',
    'msg'    => 'DB connect failed: ' . $mysqli->connect_error
  ])); exit;
}
$mysqli->set_charset('utf8mb4');

// collect
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$country  = trim($_POST['countryofresidence'] ?? '');

// minimal validation
$errors = [];
if ($fullname === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($country === '') $errors[] = 'Country is required.';
if ($dob !== '') {
  // Expect YYYY-MM-DD from <input type="date">
  $dt = DateTime::createFromFormat('Y-m-d', $dob);
  $validFormat = $dt && $dt->format('Y-m-d') === $dob;

  if (!$validFormat) {
    $errors[] = 'DOB must be YYYY-MM-DD.';
  } else {
    $today = new DateTime('today');
    if ($dt > $today) {
      $errors[] = 'Date of birth cannot be in the future.';
    }
  }
}
if ($phone !== '') {
  // Must match +xx xxx xxx xxx
  if (!preg_match('/^\+\d{8,15}$/', $phone)) {
    $errors[] = 'Phone must be with country code e.g. +61 xxx xxx xxx.';
  }
}

if ($errors) {
  header('Location: '.$formPage.'?' . http_build_query([
    'status' => 'error',
    'msg'    => implode(' ', $errors)
  ])); exit;
}

// insert
$stmt = $mysqli->prepare(
  "INSERT INTO traveller (fullname, email, phone, dob, countryofresidence) VALUES (?,?,?,?,?)"
);
$phoneParam = ($phone !== '') ? $phone : NULL;
$dobParam   = ($dob   !== '') ? $dob   : NULL;
$stmt->bind_param('sssss', $fullname, $email, $phoneParam, $dobParam, $country);

if ($stmt->execute()) {
  $stmt->close();
  header('Location: '.$formPage.'?' . http_build_query([
    'status' => 'ok',
    'msg'    => 'Traveller saved.'
  ])); exit;
} else {
  $msg = ($stmt->errno == 1062) ? 'Email already exists.' : ('Insert failed: ' . $stmt->error);
  $stmt->close();
  header('Location: '.$formPage.'?' . http_build_query([
    'status' => 'error',
    'msg'    => $msg
  ])); exit;
}
