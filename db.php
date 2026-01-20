<?php
$servername = "localhost";     // Local server
$username   = "root";          // Default local DB username
$password   = "";              // Default local DB password (empty)
$dbname     = "school_db";       // Your local database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/*
|--------------------------------------------------------------------------
| AUTO ADD father_name COLUMN (RUNS ONCE)
|--------------------------------------------------------------------------
| This ensures father_name exists even without phpMyAdmin access
*/
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'father_name'");
if ($checkColumn && $checkColumn->num_rows == 0) {
    $conn->query("
        ALTER TABLE users
        ADD father_name VARCHAR(100) AFTER fullname
    ");
}
?>
