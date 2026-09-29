<?php
// ============================================================
// DATABASE CONNECTION
// ============================================================
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'login_system';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}