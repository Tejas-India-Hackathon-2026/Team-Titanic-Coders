<?php
// delete-user.php - Admin endpoint to permanently delete Owner or Renter accounts
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_admin();

$role = isset($_GET['role']) && in_array($_GET['role'], ['owner', 'renter']) ? $_GET['role'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($role) || $userId <= 0) {
    set_flash_message('error', 'Invalid user deletion request.');
    header("Location: admin-dashboard.php");
    exit;
}

try {
    $pdo->beginTransaction();

    if ($role === 'owner') {
        // Fetch owner details
        $stmtOwner = $pdo->prepare("SELECT name, email FROM owners WHERE id = ?");
        $stmtOwner->execute([$userId]);
        $owner = $stmtOwner->fetch();

        if (!$owner) {
            $pdo->rollBack();
            set_flash_message('error', 'Owner account not found.');
            header("Location: admin-dashboard.php");
            exit;
        }

        // Delete inquiries for this owner's properties
        $pdo->prepare("DELETE FROM inquiries WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?)")->execute([$userId]);

        // Delete favorites for this owner's properties
        $pdo->prepare("DELETE FROM favorites WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?)")->execute([$userId]);

        // Delete payments
        $pdo->prepare("DELETE FROM payments WHERE owner_id = ?")->execute([$userId]);

        // Delete properties
        $pdo->prepare("DELETE FROM properties WHERE owner_id = ?")->execute([$userId]);

        // Delete owner record
        $pdo->prepare("DELETE FROM owners WHERE id = ?")->execute([$userId]);

        $pdo->commit();
        set_flash_message('success', "Owner account '{$owner['name']}' (#$userId) and all their listings were permanently deleted.");
    } elseif ($role === 'renter') {
        // Fetch renter details
        $stmtRenter = $pdo->prepare("SELECT name, email FROM renters WHERE id = ?");
        $stmtRenter->execute([$userId]);
        $renter = $stmtRenter->fetch();

        if (!$renter) {
            $pdo->rollBack();
            set_flash_message('error', 'Renter account not found.');
            header("Location: admin-dashboard.php");
            exit;
        }

        // Delete favorites
        $pdo->prepare("DELETE FROM favorites WHERE renter_id = ?")->execute([$userId]);

        // Delete inquiries
        $pdo->prepare("DELETE FROM inquiries WHERE renter_id = ?")->execute([$userId]);

        // Delete payments
        $pdo->prepare("DELETE FROM payments WHERE renter_id = ?")->execute([$userId]);

        // Delete renter record
        $pdo->prepare("DELETE FROM renters WHERE id = ?")->execute([$userId]);

        $pdo->commit();
        set_flash_message('success', "Renter account '{$renter['name']}' (#$userId) was permanently deleted.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('error', 'Failed to delete user: ' . $e->getMessage());
}

header("Location: admin-dashboard.php");
exit;
