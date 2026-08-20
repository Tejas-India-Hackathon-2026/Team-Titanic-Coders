<?php
// payment-success.php - Transaction Confirmation & Official Receipt
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$txnId = isset($_GET['txn_id']) ? sanitize($_GET['txn_id']) : '';

if (empty($txnId)) {
    header("Location: owner-dashboard.php");
    exit;
}

// Fetch Payment record
$stmt = $pdo->prepare("
    SELECT pay.*, p.title as property_title, p.location as property_location, p.city as property_city, p.image as property_image,
           o.name as payer_name, o.email as payer_email, o.phone as payer_phone
    FROM payments pay
    JOIN properties p ON pay.property_id = p.id
    JOIN owners o ON pay.owner_id = o.id
    WHERE pay.transaction_id = :txn_id
");
$stmt->execute([':txn_id' => $txnId]);
$payment = $stmt->fetch();

if (!$payment) {
    set_flash_message('error', 'Transaction record not found.');
    header("Location: owner-dashboard.php");
    exit;
}

$page_title = "Payment Successful - RentNear";
$extra_css = "assets/css/payment.css";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    
    <div class="invoice-card">
        
        <div class="success-icon-badge">
            <i class="fa-solid fa-check"></i>
        </div>

        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge badge-success mb-2">Transaction Completed</span>
            <h1 style="font-size: 1.9rem; font-weight: 800; color: var(--dark);">Payment Successful!</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Your property is now upgraded to <strong style="color: #d97706;">⭐ Premium Featured</strong> status.
            </p>
        </div>

        <!-- Official Receipt Table -->
        <div style="background: var(--bg-body); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
                <div>
                    <strong style="font-size: 1.1rem; color: var(--dark);">RentNear Official Receipt</strong>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Hackathon Demo Simulation</div>
                </div>
                <span class="badge badge-premium">⭐ Featured Plan</span>
            </div>

            <table class="receipt-table">
                <tr>
                    <td class="label">Transaction Reference:</td>
                    <td class="value"><code style="font-size: 0.95rem; color: var(--primary);"><?php echo htmlspecialchars($payment['transaction_id']); ?></code></td>
                </tr>
                <tr>
                    <td class="label">Date & Time:</td>
                    <td class="value"><?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Account Holder:</td>
                    <td class="value"><?php echo htmlspecialchars($payment['payer_name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Payment Mode:</td>
                    <td class="value"><?php echo htmlspecialchars($payment['payment_method']); ?> (Mock Gateway)</td>
                </tr>
                <tr>
                    <td class="label">Property Boosted:</td>
                    <td class="value"><?php echo htmlspecialchars($payment['property_title']); ?></td>
                </tr>
                <tr>
                    <td class="label">Location:</td>
                    <td class="value"><?php echo htmlspecialchars($payment['property_location'] . ', ' . $payment['property_city']); ?></td>
                </tr>
                <tr>
                    <td class="label">Plan Duration:</td>
                    <td class="value">60 Days Featured Priority</td>
                </tr>
                <tr style="border-top: 1px solid var(--border-color);">
                    <td class="label" style="font-size: 1.1rem; font-weight: 800; color: var(--dark); padding-top: 1rem;">Total Paid:</td>
                    <td class="value" style="font-size: 1.3rem; font-weight: 800; color: var(--primary); padding-top: 1rem;">₹99.00</td>
                </tr>
            </table>
        </div>

        <!-- Action Buttons -->
        <div class="no-print" style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="window.print();">
                <i class="fa-solid fa-print"></i> Print Invoice Receipt
            </button>
            <a href="property-details.php?id=<?php echo $payment['property_id']; ?>" class="btn btn-outline">
                <i class="fa-solid fa-eye"></i> View Live Property
            </a>
            <a href="owner-dashboard.php" class="btn btn-primary">
                <i class="fa-solid fa-gauge-high"></i> Go to Dashboard
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
