<?php
/**
 * Auto-redirect to Laravel public directory when accessed via XAMPP subfolder
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/pos/';
if (!str_contains($uri, '/pos/public')) {
    header('Location: ' . rtrim($uri, '/') . '/public/');
    exit;
}

require __DIR__ . '/public/index.php';
