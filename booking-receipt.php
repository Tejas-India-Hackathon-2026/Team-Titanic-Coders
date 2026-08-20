<?php
// booking-receipt.php - Official Room Booking & Token Advance Receipt
$page_title = "Official Room Booking Receipt - RentNear";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$txn_id = isset($_GET['txn_id']) ? sanitize($_GET['txn_id']) : '';

if (empty($txn_id)) {
    header("Location: renter-dashboard.php");
    exit;
}

// Fetch Payment Details
$stmt = $pdo->prepare("
    SELECT pay.*, 
           p.title as property_title, p.location as property_location, p.city as property_city, 
           p.price as property_price, p.deposit as property_deposit, p.image as property_image,
           p.property_type, p.furnishing,
           o.name as owner_name, o.phone as owner_phone, o.email as owner_email
    FROM payments pay
    JOIN properties p ON pay.property_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE pay.transaction_id = :txn_id
");
$stmt->execute([':txn_id' => $txn_id]);
$payment = $stmt->fetch();

if (!$payment) {
    set_flash_message('error', 'Booking transaction record not found.');
    header("Location: renter-dashboard.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    
    <!-- Success Banner -->
    <div style="max-width: 780px; margin: 0 auto 1.75rem; text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #ecfdf5; color: #059669; font-size: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.35rem;">
            Room Reserved Successfully!
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Your token advance has been authorized. The landlord has been notified to block this room for you.
        </p>
    </div>

    <!-- Printable Official Receipt Card -->
    <div class="receipt-card" id="printableReceipt" style="max-width: 780px; margin: 0 auto; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 20px; padding: 2.5rem; box-shadow: var(--shadow-xl); position: relative; overflow: hidden;">
        
        <!-- Watermark / Stamp -->
        <div style="position: absolute; right: 2rem; top: 2rem; border: 2px solid #059669; color: #059669; font-weight: 900; font-size: 0.85rem; padding: 4px 12px; border-radius: 6px; text-transform: uppercase; letter-spacing: 1px; transform: rotate(-8deg); pointer-events: none;">
            ✓ TOKEN VERIFIED & PAID
        </div>

        <!-- Receipt Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 0.25rem;">
                    <span style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 1rem;"><i class="fa-solid fa-house-chimney"></i></span>
                    RentNear
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Official Room Booking & Token Advance Voucher</p>
            </div>
            <div style="text-align: right; font-size: 0.82rem; color: var(--text-muted);">
                <div><strong>Receipt Date:</strong> <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></div>
                <div><strong>Booking ID:</strong> <span style="font-family: monospace; color: #0f172a; font-weight: 700;">RN-BOOK-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></span></div>
                <div><strong>Txn Ref:</strong> <span style="font-family: monospace; color: #4338ca; font-weight: 700;"><?php echo htmlspecialchars($payment['transaction_id']); ?></span></div>
            </div>
        </div>

        <!-- Party Information Columns -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; background: #f8fafc; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.75rem; border: 1px solid #e2e8f0;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Renter Details</span>
                <h4 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0.2rem 0;"><?php echo htmlspecialchars($user['name']); ?></h4>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?><br>
                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </p>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Landlord / Owner Details</span>
                <h4 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0.2rem 0; display: flex; align-items: center; gap: 4px;">
                    <?php echo htmlspecialchars($payment['owner_name']); ?>
                    <?php echo render_verified_badge(false, 16); ?>
                </h4>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($payment['owner_phone']); ?><br>
                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($payment['owner_email']); ?>
                </p>
            </div>
        </div>

        <!-- Booked Property Summary -->
        <div style="margin-bottom: 1.75rem;">
            <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Reserved Property Details</span>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0.85rem;">
                <img src="<?php echo htmlspecialchars(get_property_image($payment['property_image'])); ?>" alt="" style="width: 85px; height: 65px; object-fit: cover; border-radius: 8px;">
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 0.98rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">
                        <?php echo htmlspecialchars($payment['property_title']); ?>
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                        <i class="fa-solid fa-location-dot text-danger"></i> <?php echo htmlspecialchars($payment['property_location'] . ', ' . $payment['property_city']); ?> &bull; 
                        <strong><?php echo htmlspecialchars($payment['property_type']); ?> (<?php echo htmlspecialchars($payment['furnishing']); ?>)</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Breakdown Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.75rem; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background: #f8fafc;">
                    <th style="padding: 0.75rem 1rem; text-align: left; color: var(--dark-muted); font-weight: 700;">Payment Description</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; color: var(--dark-muted); font-weight: 700;">Method</th>
                    <th style="padding: 0.75rem 1rem; text-align: right; color: var(--dark-muted); font-weight: 700;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 0.85rem 1rem;">
                        <strong>Room Booking Token Advance</strong><br>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">Adjustable against 1st month rent of ₹<?php echo number_format($payment['property_price']); ?></span>
                    </td>
                    <td style="padding: 0.85rem 1rem; text-align: center;">
                        <span class="badge badge-info" style="font-size: 0.75rem;"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                    </td>
                    <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 800; color: #0f172a;">
                        ₹<?php echo number_format($payment['amount'], 2); ?>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 0.6rem 1rem; color: var(--text-muted);">Platform Convenience Fee</td>
                    <td style="padding: 0.6rem 1rem; text-align: center;">-</td>
                    <td style="padding: 0.6rem 1rem; text-align: right; color: var(--success); font-weight: 700;">₹0.00 (Free)</td>
                </tr>
                <tr style="background: #f8fafc; font-size: 1.05rem;">
                    <td style="padding: 1rem; font-weight: 800; color: #0f172a;">Total Paid Online</td>
                    <td style="padding: 1rem; text-align: center; color: var(--success); font-weight: 800;">SUCCESS</td>
                    <td style="padding: 1rem; text-align: right; font-weight: 900; color: #4338ca;">
                        ₹<?php echo number_format($payment['amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Landlord Direct Connect CTA Box -->
        <div style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h5 style="font-size: 0.95rem; font-weight: 800; color: #065f46; margin: 0 0 2px 0;">
                    <i class="fa-solid fa-user-check"></i> Next Step: Connect with Landlord
                </h5>
                <p style="font-size: 0.8rem; color: #047857; margin: 0;">
                    Inform <?php echo htmlspecialchars($payment['owner_name']); ?> that your token has been deposited.
                </p>
            </div>
            
            <div style="display: flex; gap: 0.5rem;">
                <?php 
                $cleanPhone = preg_replace('/[^0-9]/', '', $payment['owner_phone']);
                $waMsg = urlencode("Hi " . $payment['owner_name'] . ", I have paid the booking token advance (₹" . number_format($payment['amount']) . ") on RentNear for '" . $payment['property_title'] . "'. Receipt Txn ID: " . $payment['transaction_id'] . ". Let's finalize the room agreement!");
                ?>
                <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=<?php echo $waMsg; ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #25D366; color: #fff; border: none; font-weight: 700;">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Landlord
                </a>
                <a href="tel:<?php echo htmlspecialchars($payment['owner_phone']); ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-phone"></i> Call Now
                </a>
            </div>
        </div>

        <p style="text-align: center; font-size: 0.72rem; color: var(--text-muted); margin: 0;">
            This is a computer-generated simulated receipt for RentNear Hackathon Demo &bull; 100% Zero Brokerage
        </p>
    </div>

    <!-- Action Buttons -->
    <div style="max-width: 780px; margin: 1.5rem auto 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print / Save as PDF
        </button>
        <div style="display: flex; gap: 0.75rem;">
            <a href="renter-dashboard.php" class="btn btn-outline">
                <i class="fa-solid fa-table-columns"></i> Go to Renter Dashboard
            </a>
            <a href="properties.php" class="btn btn-primary">
                Browse More Rooms <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableReceipt, #printableReceipt * {
        visibility: visible;
    }
    #printableReceipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
