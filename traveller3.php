<?php
require __DIR__ . '/db.php';

function back($status, $msg) {
  header('Location: insert_traveller.php?' . http_build_query(['status'=>$status,'msg'=>$msg]));
  exit;
}

// 1) Collect + trim
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$country  = trim($_POST['countryofresidence'] ?? '');

// 2) Validate
$errors = [];

// Required
if ($fullname === '') $errors[] = 'Full name is required.';
if ($email === '') $errors[] = 'Email is required.';
if ($country === '') $errors[] = 'Country of residence is required.';

// Formats
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Email format is invalid.';
}
if ($phone !== '' && !preg_match('/^\+?[0-9()\-\s]{6,20}$/', $phone)) {
  $errors[] = 'Phone format looks invalid.';
}
if ($dob !== '') {
  $d = DateTime::createFromFormat('Y-m-d', $dob);
  $valid = $d && $d->format('Y-m-d') === $dob;
  if (!$valid) {
    $errors[] = 'DOB must be YYYY-MM-DD.';
  } else {
    $today = new DateTime('today');
    if ($d >= $today) $errors[] = 'DOB must be in the past.';
  }
}

if ($errors) back('error', implode(' ', $errors));

// 3) INSERT
try {
  $sql = "INSERT INTO traveller (fullname, email, phone, dob, countryofresidence)
          VALUES (:fullname, :email, :phone, :dob, :country)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':fullname' => $fullname,
    ':email'    => $email,
    ':phone'    => ($phone !== '' ? $phone : null),
    ':dob'      => ($dob   !== '' ? $dob   : null),
    ':country'  => $country,
  ]);

  // Optional: fetch last ID if you want to mention it
  // $newId = $pdo->lastInsertId();
  back('ok', 'Traveller inserted successfully.');
} catch (PDOException $e) {
  // Duplicate email or other constraint issues
  if ($e->getCode() === '23000') {
    back('error', 'Insert failed. Email already exists or constraints were violated.');
  }
  back('error', 'Insert failed. ' . $e->getMessage());
}
