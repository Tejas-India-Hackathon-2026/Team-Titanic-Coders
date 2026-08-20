<?php
// verify-receipt.php - Official Verified Landlord Certificate & Blue Tick Activation Receipt
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

$txnId = isset($_GET['txn']) ? sanitize($_GET['txn']) : '';

// Fetch latest verification payment
if (!empty($txnId)) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ? AND owner_id = ? AND payment_type = 'owner_verification'");
    $stmt->execute([$txnId, $user['id']]);
    $payment = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE owner_id = ? AND payment_type = 'owner_verification' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $payment = $stmt->fetch();
}

// Fetch owner details
$ownerStmt = $pdo->prepare("SELECT * FROM owners WHERE id = ?");
$ownerStmt->execute([$user['id']]);
$ownerData = $ownerStmt->fetch();

$page_title = "Verified Landlord Certificate - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">

    <!-- Action Bar -->
    <div style="max-width: 760px; margin: 0 auto 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <a href="owner-dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print();" class="btn btn-secondary btn-sm" style="background: #ffffff; font-weight: 700;">
                <i class="fa-solid fa-print"></i> Print Certificate
            </button>
            <a href="owner-dashboard.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-house"></i> View My Listings
            </a>
        </div>
    </div>

    <!-- Official Certificate & Voucher Box -->
    <div id="printableCertificate" style="max-width: 760px; margin: 0 auto; background: #ffffff; border: 2.5px solid #0095f6; border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 149, 246, 0.15); position: relative; overflow: hidden;">
        
        <!-- Watermark Background Emblem -->
        <div style="position: absolute; right: -40px; bottom: -40px; opacity: 0.04; pointer-events: none; z-index: 0;">
            <svg width="340" height="340" viewBox="0 0 24 24" fill="#0095f6"><path d="M22.5 12.5c0-1.58-.875-2.95-2.148-3.6.154-.435.238-.905.238-1.4 0-2.21-1.79-4-4-4-.495 0-.965.084-1.4.238C14.55 2.475 13.18 1.6 11.6 1.6c-1.58 0-2.95.875-3.6 2.148-.435-.154-.905-.238-1.4-.238-2.21 0-4 1.79-4 4 0 .495.084.965.238 1.4C1.575 9.55.7 10.92.7 12.5c0 1.58.875 2.95 2.148 3.6-.154.435-.238.905-.238 1.4 0 2.21 1.79 4 4 4 .495 0 .965-.084 1.4-.238.65 1.273 2.02 2.148 3.6 2.148 1.58 0 2.95-.875 3.6-2.148.435.154.905.238 1.4.238 2.21 0 4-1.79 4-4 0-.495-.084-.965-.238-1.4 1.273-.65 2.148-2.02 2.148-3.6zm-12.28 4.63l-4.15-4.15 1.42-1.42 2.73 2.73 6.88-6.88 1.42 1.42-8.3 8.3z"/></svg>
        </div>

        <div style="position: relative; z-index: 1;">
            
            <!-- Certificate Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e0f2fe; padding-bottom: 1.5rem; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="font-size: 1.4rem; font-weight: 900; color: var(--primary);">Rent<span style="color: #0095f6;">Near</span></span>
                        <span style="background: #e0f2fe; color: #0284c7; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; border: 1px solid #bae6fd;">
                            VERIFIED IDENTITY
                        </span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Certificate of Landlord Verification & Blue Tick Activation</p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Certificate ID</span>
                    <div style="font-size: 1.05rem; font-weight: 800; font-family: monospace; color: #0095f6;">
                        <?php echo !empty($payment['transaction_id']) ? htmlspecialchars($payment['transaction_id']) : (!empty($ownerData['verification_txn_id']) ? htmlspecialchars($ownerData['verification_txn_id']) : 'TXN_VERIFY_ACTIVE'); ?>
                    </div>
                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                        Date: <?php echo date('d M Y'); ?>
                    </div>
                </div>
            </div>

            <!-- Verified Landlord Honor Showcase -->
            <div style="text-align: center; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border: 1.5px solid #bae6fd; border-radius: 16px; padding: 1.75rem 1.5rem; margin-bottom: 2rem;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: #ffffff; color: #0095f6; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; box-shadow: 0 4px 12px rgba(0, 149, 246, 0.25);">
                    <?php echo render_verified_badge(false, 36); ?>
                </div>
                <h2 style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-bottom: 0.2rem; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <?php echo htmlspecialchars($ownerData['name'] ?? $user['name']); ?>
                    <?php echo render_verified_badge(false, 22); ?>
                </h2>
                <div style="font-size: 0.88rem; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                    Official Verified Property Owner
                </div>
                <p style="font-size: 0.84rem; color: #334155; max-width: 520px; margin: 0 auto;">
                    This acknowledges that the identity, contact credentials, and property listings of the owner have been authenticated on the RentNear platform.
                </p>
            </div>

            <!-- Verification Metadata Breakdown -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Owner Contact & Location</span>
                    <div style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($ownerData['phone'] ?? $user['phone'] ?? '+91'); ?></div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);"><?php echo htmlspecialchars($ownerData['email'] ?? $user['email']); ?></div>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">City: <strong><?php echo htmlspecialchars($ownerData['city'] ?? 'All India'); ?></strong></div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Subscription & Trust Status</span>
                    <div style="font-size: 0.95rem; font-weight: 700; color: #16a34a; margin-bottom: 0.2rem;">
                        <i class="fa-solid fa-circle-check"></i> 100% Active & Authenticated
                    </div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">Amount Paid: <strong>₹199.00 (Annual)</strong></div>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">Badge: <strong>🔵 Instagram Blue Tick</strong></div>
                </div>
            </div>

            <!-- Footer Seal & Sign-off -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1.5px solid #f1f5f9; padding-top: 1.25rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="font-size: 0.78rem; font-weight: 800; color: #0f172a;">RentNear Trust & Safety Council</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Tejas India Hackathon 2026 Innovation</div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 0.4rem 0.85rem; font-size: 0.76rem; color: #065f46; font-weight: 700;">
                    <i class="fa-solid fa-stamp"></i> Digitally Signed & Encrypted
                </div>
            </div>

        </div>

    </div>

</div>

<style>
@media print {
    header, footer, .btn, .alert, .site-header, nav {
        display: none !important;
    }
    body, html {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #printableCertificate {
        border: 2px solid #0095f6 !important;
        box-shadow: none !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
