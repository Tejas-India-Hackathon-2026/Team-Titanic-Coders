<?php
// delete-property.php - Delete Property Listing
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

$propId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($propId > 0) {
    // Check ownership
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
    $stmt->execute([':id' => $propId]);
    $prop = $stmt->fetch();

    if ($prop && ($prop['owner_id'] == $user['id'] || $user['role'] === 'admin')) {
        $delStmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
        $delStmt->execute([':id' => $propId]);
        set_flash_message('success', 'Property "' . $prop['title'] . '" was permanently removed.');
    } else {
        set_flash_message('error', 'Unauthorized deletion attempt.');
    }
}

if ($user['role'] === 'admin') {
    header("Location: admin-dashboard.php");
} else {
    header("Location: owner-dashboard.php");
}
exit;
