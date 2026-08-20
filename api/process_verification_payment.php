<?php
// api/process_verification_payment.php - Process ₹199 Mock Payment for Owner Verified Blue Tick Badge
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated. Please log in as an owner.']);
    exit;
}

$user = current_user();

if ($user['role'] !== 'owner' && $user['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Only property owners can apply for the Verified Landlord badge.']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$amount        = isset($input['amount']) ? (float)$input['amount'] : 199.00;
$paymentMethod = isset($input['payment_method']) ? sanitize($input['payment_method']) : 'UPI';
$govtIdType    = isset($input['govt_id_type']) ? sanitize($input['govt_id_type']) : 'Aadhaar / PAN Card';

try {
    // Generate Mock Verification Transaction ID
    $transactionId = 'TXN_VERIFY_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $now = date('Y-m-d H:i:s');

    // Begin Transaction
    $pdo->beginTransaction();

    // 1. Record in payments table
    $payStmt = $pdo->prepare("
        INSERT INTO payments (
            owner_id, property_id, amount, payment_type, payment_method, transaction_id, status, created_at
        ) VALUES (
            ?, NULL, ?, 'owner_verification', ?, ?, 'SUCCESS', ?
        )
    ");
    $payStmt->execute([
        $user['id'],
        $amount,
        $paymentMethod,
        $transactionId,
        $now
    ]);
    $paymentRecordId = $pdo->lastInsertId();

    // 2. Update owner status to verified
    $ownerStmt = $pdo->prepare("
        UPDATE owners 
        SET is_verified = 1, verified_at = ?, verification_txn_id = ?
        WHERE id = ?
    ");
    $ownerStmt->execute([
        $now,
        $transactionId,
        $user['id']
    ]);

    // Update active session data if applicable
    if (isset($_SESSION['user'])) {
        $_SESSION['user']['is_verified'] = 1;
    }

    $pdo->commit();

    echo json_encode([
        'status'         => 'success',
        'transaction_id' => $transactionId,
        'payment_id'     => $paymentRecordId,
        'message'        => 'Verification Payment Successful! 🔵 Verified Owner Blue Tick has been activated on all your listings.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status'  => 'error',
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
