<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chronological_database-sis-1');
define('DB_PORT', 3307); // Add the port number

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
