<?php
/**
 * Database Connection Configuration (config/db.php)
 * Project: Smart-Healthcare-Management-System
 * Database: smart_hms
 * Server Type: XAMPP (assuming standard MySQL port 3306)
 */

// --- Database Connection Parameters ---

// 1. Host: Always 'localhost' or '127.0.0.1' for local connections
$host = 'localhost'; 

// 2. Database Name
$db   = 'smart_hms';

// 3. User Credentials (XAMPP default)
$user = 'root'; 
$pass = '';      // Default password for 'root' is empty in XAMPP

// 4. MySQL Port
// IMPORTANT: Apache (web server) is on 3000, but MySQL (database server) 
// is almost certainly on 3306 (the default) or 3307. 
// Change this value if your XAMPP control panel shows a different port for MySQL.
$mysql_port = '3306'; 

$charset = 'utf8mb4';

// Data Source Name (DSN) string
$dsn = "mysql:host=$host;port=$mysql_port;dbname=$db;charset=$charset";

// --- PDO Options ---
$options = [
    // Throw exceptions on error for better debugging
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Fetch results as associative arrays
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulation of prepared statements (security/performance)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Connection Attempt ---
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
} catch (\PDOException $e) {
     // Display a clear error message if connection fails, guiding the user to check the port.
     $error_msg = "Database Connection Failed! Please check the following:";
     $error_msg .= "\n1. Ensure the MySQL service is running in XAMPP.";
     $error_msg .= "\n2. Verify the \$mysql_port variable (currently $mysql_port) in config/db.php is correct.";
     $error_msg .= "\nPDO Error: " . $e->getMessage();

     die("<pre>" . $error_msg . "</pre>");
}

// The $pdo variable is now your active database connection object.
?>