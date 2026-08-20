<?php
// delete-inquiry.php - Delete / Dismiss Inquiry
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$inquiryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$from = isset($_GET['from']) ? sanitize($_GET['from']) : 'owner';

if ($inquiryId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch inquiry with property owner info
$stmt = $pdo->prepare("
    SELECT i.*, p.owner_id, p.title as property_title
    FROM inquiries i
    JOIN properties p ON i.property_id = p.id
    WHERE i.id = :id
");
$stmt->execute([':id' => $inquiryId]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    set_flash_message('error', 'Inquiry not found or already deleted.');
} else {
    // Check permission: Must be the property owner, the renter who sent it, or an admin
    $isOwner  = ($inquiry['owner_id'] == $user['id']);
    $isRenter = ($inquiry['renter_id'] == $user['id']) || (!empty($inquiry['email']) && strtolower($inquiry['email']) === strtolower($user['email']));
    $isAdmin  = ($user['role'] === 'admin');

    if ($isOwner || $isRenter || $isAdmin) {
        $delStmt = $pdo->prepare("DELETE FROM inquiries WHERE id = :id");
        $delStmt->execute([':id' => $inquiryId]);
        set_flash_message('success', 'Inquiry for "' . $inquiry['property_title'] . '" was deleted successfully.');
    } else {
        set_flash_message('error', 'Unauthorized: You do not have permission to delete this inquiry.');
    }
}

// Redirect back to appropriate dashboard
if ($from === 'renter' || $user['role'] === 'renter') {
    header("Location: renter-dashboard.php");
} elseif ($from === 'admin' || $user['role'] === 'admin') {
    header("Location: admin-dashboard.php");
} else {
    header("Location: owner-dashboard.php");
}
exit;
