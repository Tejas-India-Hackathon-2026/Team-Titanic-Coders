<?php
// api/toggle_favorite.php - Add or Remove Property from Wishlist
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Please log in to save properties.']);
    exit;
}

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);
$propertyId = isset($input['property_id']) ? (int)$input['property_id'] : 0;

if ($propertyId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid property ID.']);
    exit;
}

// Check if favorite exists
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE renter_id = :uid AND property_id = :pid");
$stmt->execute([':uid' => $user['id'], ':pid' => $propertyId]);
$fav = $stmt->fetch();

if ($fav) {
    // Remove favorite
    $del = $pdo->prepare("DELETE FROM favorites WHERE id = :id");
    $del->execute([':id' => $fav['id']]);
    echo json_encode(['status' => 'success', 'is_favorite' => false, 'message' => 'Removed from saved properties.']);
} else {
    // Add favorite
    $ins = $pdo->prepare("INSERT INTO favorites (renter_id, property_id) VALUES (:uid, :pid)");
    $ins->execute([':uid' => $user['id'], ':pid' => $propertyId]);
    echo json_encode(['status' => 'success', 'is_favorite' => true, 'message' => 'Property saved!']);
}
