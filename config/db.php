<?php
// config/db.php - RentNear Dual-Mode Database Connection (MySQL & SQLite)

// Database Configuration
// If your XAMPP MySQL is active, set $db_type = 'mysql'
// If you want zero-setup local SQLite file, set $db_type = 'sqlite'
$db_type       = 'mysql'; // Defaulting to MySQL since XAMPP MySQL is running on your machine!
$mysql_host    = '127.0.0.1';
$mysql_port    = '3306'; // Standard XAMPP MySQL port (change to 3006 if you customized it in my.ini)
$mysql_db      = 'rentnear_db';
$mysql_user    = 'root';
$mysql_pass    = '';
$mysql_charset = 'utf8mb4';

$sqlite_file   = __DIR__ . '/../database/rentnear.sqlite';

$pdo = null;

if ($db_type === 'mysql') {
    try {
        // Step 1: Connect to MySQL server and ensure database exists
        $initPdo = new PDO("mysql:host=$mysql_host;port=$mysql_port", $mysql_user, $mysql_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $initPdo->exec("CREATE DATABASE IF NOT EXISTS `$mysql_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        // Step 2: Connect directly to the database
        $dsn = "mysql:host=$mysql_host;port=$mysql_port;dbname=$mysql_db;charset=$mysql_charset";
        $pdo = new PDO($dsn, $mysql_user, $mysql_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);
    } catch (PDOException $e) {
        // If MySQL connection fails (e.g. port mismatch or password set), fallback to SQLite so app never breaks
        error_log("MySQL Connection Warning: " . $e->getMessage() . " - Falling back to SQLite.");
        $db_type = 'sqlite';
    }
}

if ($db_type === 'sqlite' || $pdo === null) {
    $db_dir = dirname($sqlite_file);
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0777, true);
    }
    
    $pdo = new PDO("sqlite:" . $sqlite_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
}

// Auto-run schema creation & seeder
require_once __DIR__ . '/setup_db.php';
initialize_database($pdo);
