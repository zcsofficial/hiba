<?php
// Database configuration
$db_host = 'localhost';       // Database host (e.g., localhost)
$db_user = 'adnan';            // Database username (default for local setups)
$db_pass = 'Adnan@66202';                // Database password (default empty for local setups)
$db_name = 'hiba_db';         // Database name

// Create connection using MySQLi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set to UTF-8
mysqli_set_charset($conn, "utf8");

?>