<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
/* Configure these values using your hosting provider's database credentials. */
$DB_HOST = 'sql113.infinityfree.com';
$DB_USER = 'if0_43236999';
$DB_PASS = '7xlO2pgh9d';
$DB_NAME = 'if0_43236999_LOST';
try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database connection failed. Check the database settings in db.php and import schema.sql.');
}
