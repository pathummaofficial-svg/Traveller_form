<?php
// db_mysqli.php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'traveller_form'); // <- change DB name if needed
if ($mysqli->connect_errno) {
  http_response_code(500);
  exit('DB connect failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
