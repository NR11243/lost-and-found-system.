<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function require_access_code(): void {
    if (empty($_SESSION['access_code_id'])) { header('Location: index.php'); exit; }
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function item_status_class(string $status): string {
    return match ($status) { 'Found' => 'found', 'Returned' => 'returned', default => 'lost' };
}
