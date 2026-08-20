<?php
// api/process_payment.php - Process Mock ₹99 Payment for Premium Upgrade
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

$user = current_user();

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$propertyId = isset($input['property_id']) ? (int)$input['property_id'] : 0;
$amount = isset($input['amount']) ? (float)$input['amount'] : 99.00;
$paymentMethod = isset($input['payment_method']) ? sanitize($input['payment_method']) : 'UPI';

if ($propertyId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid property ID.']);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute([':id' => $propertyId]);
$property = $stmt->fetch();

if (!$property) {
    echo json_encode(['status' => 'error', 'message' => 'Property not found.']);
    exit;
}

if ($property['owner_id'] != $user['id'] && $user['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized upgrade attempt.']);
    exit;
}

try {
    // Generate Mock Transaction ID
    $transactionId = generate_mock_txn_id();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+60 days'));

    // Begin Transaction
    $pdo->beginTransaction();

    // 1. Record Payment
    $payStmt = $pdo->prepare("
        INSERT INTO payments (owner_id, property_id, amount, payment_type, payment_method, transaction_id, status)
        VALUES (?, ?, ?, 'premium_listing', ?, ?, 'SUCCESS')
    ");
    $payStmt->execute([
        $user['id'],
        $propertyId,
        $amount,
        $paymentMethod,
        $transactionId
    ]);

    // 2. Update Property to Premium Featured
    $propStmt = $pdo->prepare("
        UPDATE properties 
        SET is_premium = 1, premium_expires_at = ?
        WHERE id = ?
    ");
    $propStmt->execute([$expiresAt, $propertyId]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'transaction_id' => $transactionId,
        'message' => 'Payment authorized! ⭐ Premium featured badge activated for 60 days.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error during transaction: ' . $e->getMessage()
    ]);
}
