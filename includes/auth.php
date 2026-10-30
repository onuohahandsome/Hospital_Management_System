<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

function requireRole(array $allowed_roles): void {
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        http_response_code(403);
        echo "<div style='padding: 40px; font-family: sans-serif; text-align: center;'>
                <h1 style='color: #dc3545;'>403 Forbidden</h1>
                <p>You do not have permission to view this page.</p>
                <a href='" . BASE_URL . "dashboard.php' style='padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a>
              </div>";
        exit();
    }
}

function logAction(?string $patient_id, string $action, ?string $reason = null): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, patient_id, action, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $patient_id, $action, $reason]);
}