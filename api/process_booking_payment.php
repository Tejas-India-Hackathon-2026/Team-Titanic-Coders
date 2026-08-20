<?php
// api/process_booking_payment.php - Process Token Advance Payment for Room Reservation
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login as a renter to book a room.']);
    exit;
}

$user = current_user();

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$propertyId    = isset($input['property_id']) ? (int)$input['property_id'] : 0;
$inquiryId     = isset($input['inquiry_id']) ? (int)$input['inquiry_id'] : 0;
$amount        = isset($input['amount']) ? (float)$input['amount'] : 1000.00;
$paymentMethod = isset($input['payment_method']) ? sanitize($input['payment_method']) : 'UPI';

if ($propertyId <= 0 || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid property or payment amount.']);
    exit;
}

// Fetch property
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute([':id' => $propertyId]);
$property = $stmt->fetch();

if (!$property) {
    echo json_encode(['status' => 'error', 'message' => 'Property not found.']);
    exit;
}

try {
    // Generate Mock Transaction ID
    $transactionId = generate_mock_txn_id();

    // Begin Transaction
    $pdo->beginTransaction();

    // 1. Insert Payment Record
    $payStmt = $pdo->prepare("
        INSERT INTO payments (owner_id, renter_id, property_id, inquiry_id, amount, payment_type, payment_method, transaction_id, status)
        VALUES (?, ?, ?, ?, ?, 'room_booking_token', ?, ?, 'SUCCESS')
    ");
    $payStmt->execute([
        $property['owner_id'],
        $user['id'],
        $propertyId,
        $inquiryId > 0 ? $inquiryId : null,
        $amount,
        $paymentMethod,
        $transactionId
    ]);

    // 2. If inquiry ID provided or found by email, update inquiry status to token_paid
    if ($inquiryId > 0) {
        $inqStmt = $pdo->prepare("
            UPDATE inquiries 
            SET booking_status = 'token_paid', token_amount = ?, transaction_id = ?
            WHERE id = ?
        ");
        $inqStmt->execute([$amount, $transactionId, $inquiryId]);
    } else {
        // Find latest inquiry for this renter & property if exists
        $findInq = $pdo->prepare("
            UPDATE inquiries 
            SET booking_status = 'token_paid', token_amount = ?, transaction_id = ?
            WHERE property_id = ? AND (renter_id = ? OR LOWER(email) = LOWER(?))
        ");
        $findInq->execute([$amount, $transactionId, $propertyId, $user['id'], $user['email']]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'message' => 'Room booking token advance paid successfully! Receipt generated.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
