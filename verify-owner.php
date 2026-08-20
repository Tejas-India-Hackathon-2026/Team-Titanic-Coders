<?php
// verify-owner.php - Paid Verification Subscription for Property Owners (₹199 Blue Tick)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

// Fetch fresh owner record to check verification status
$ownerStmt = $pdo->prepare("SELECT * FROM owners WHERE id = ?");
$ownerStmt->execute([$user['id']]);
$ownerData = $ownerStmt->fetch();

$isAlreadyVerified = !empty($ownerData['is_verified']) && (int)$ownerData['is_verified'] === 1;

$page_title = "Get Verified Owner Blue Tick - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 2.5rem;">
        <div style="display: inline-flex; align-items: center; gap: 6px; background: #fef3c7; color: #b45309; padding: 0.35rem 0.95rem; border-radius: 20px; font-weight: 800; font-size: 0.85rem; margin-bottom: 0.75rem; border: 1.5px solid #fde68a; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);">
            <?php echo render_verified_badge(false, 18); ?> RentNear Gold VIP Verified
        </div>
        <h1 style="font-size: 2.4rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem;">
            Get the Official <span style="background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Golden Tick Verified</span> Badge
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6;">
            Build 100% trust with tenants, eliminate fraud concerns, and get the iconic luxury <strong>Golden Tick badge</strong> on all your rental listings for only <strong>₹199 / Year</strong>.
        </p>
    </div>

    <?php if ($isAlreadyVerified): ?>
        <div class="alert alert-success" style="max-width: 640px; margin: 0 auto 2rem; text-align: center; border-radius: 12px; padding: 1.25rem; background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e;">
            <h4 style="font-weight: 800; margin-bottom: 0.25rem; display: flex; align-items: center; justify-content: center; gap: 6px;">
                <?php echo render_verified_badge(false, 22); ?> You are an Official Gold Verified Landlord!
            </h4>
            <p style="font-size: 0.88rem; margin: 0 0 0.75rem 0; color: #b45309;">
                Your Golden Tick badge is active on all your rental listings.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.75rem;">
                <a href="verify-receipt.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; font-weight: 800;">
                    <i class="fa-solid fa-certificate"></i> View My Gold Certificate
                </a>
                <a href="owner-dashboard.php" class="btn btn-secondary btn-sm">
                    Go to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 2.5rem; max-width: 1080px; margin: 0 auto; align-items: start;">
        
        <!-- Left: Verified Value Proposition & Benefits -->
        <div>
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-shield-halved text-primary"></i> What's Included in Verified Landlord:
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; border: 1px solid #fde68a;">
                            <?php echo render_verified_badge(false, 22); ?>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.2rem;">Luxury Golden Tick Verified Badge</h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                Display the recognized VIP Golden Tick next to your name across Property Details, Catalog Cards, Explore Map, and Receipts.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.2rem;">Govt ID & Ownership Verified Trust Tag</h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                Assures students, families, and working professionals that your property is genuine and safe.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.2rem;">5x Faster Room Closures</h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                Verified landlords receive up to 500% more direct calls and token booking payments from serious tenants.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fa-solid fa-award"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.2rem;">Official Printable Landlord Certificate</h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                Download and print your verified owner certificate with a unique Verification ID.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Live Preview Card -->
            <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: var(--radius-lg); padding: 1.25rem;">
                <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                    Live Preview of Your Verified Profile:
                </span>
                <div style="display: flex; align-items: center; gap: 0.75rem; background: #fff; padding: 0.85rem 1.25rem; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div class="owner-avatar-lg" style="width: 44px; height: 44px; font-size: 1.1rem;">
                        <?php echo strtoupper(substr($ownerData['name'] ?? 'O', 0, 1)); ?>
                    </div>
                    <div>
                        <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 4px;">
                            <?php echo htmlspecialchars($ownerData['name'] ?? $user['name']); ?>
                            <?php echo render_verified_badge(false, 18); ?>
                        </h4>
                        <p style="font-size: 0.76rem; color: #0284c7; font-weight: 700; margin: 0;">
                            <i class="fa-solid fa-shield-check text-primary"></i> Govt ID & Property Verified Owner
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Checkout Box & Payment Methods -->
        <div>
            <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-md);">
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0; color: #0f172a;">Verification Plan</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Annual Blue Tick Subscription</span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.75rem; font-weight: 900; color: #4338ca;">₹199</div>
                        <span style="font-size: 0.72rem; color: var(--text-muted);"><del>₹499</del> (60% Off)</span>
                    </div>
                </div>

                <!-- KYC Document Check -->
                <div style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.82rem; font-weight: 700; color: var(--dark); display: block; margin-bottom: 0.35rem;">
                        Government ID Type (Self-Declaration) <span class="text-danger">*</span>
                    </label>
                    <select id="verifyGovtId" class="form-select" style="font-size: 0.88rem;">
                        <option value="Aadhaar Card (UIDAI)">🆔 Aadhaar Card (UIDAI Verified)</option>
                        <option value="PAN Card">💳 Income Tax PAN Card</option>
                        <option value="Voter ID Card">🗳️ Indian Voter ID Card</option>
                        <option value="Electricity / Municipal Tax Bill">⚡ Property Electricity / Tax Bill</option>
                    </select>
                </div>

                <!-- Payment Method Tabs -->
                <div style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.82rem; font-weight: 700; color: var(--dark); display: block; margin-bottom: 0.5rem;">
                        Select Payment Method <span class="text-danger">*</span>
                    </label>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                        <label class="verify-pay-tab active" data-method="UPI">
                            <input type="radio" name="verify_pay_method" value="UPI" checked style="display: none;">
                            <i class="fa-solid fa-qrcode" style="font-size: 1.1rem; color: #059669;"></i>
                            <span style="font-size: 0.76rem; font-weight: 700;">UPI / QR</span>
                        </label>
                        <label class="verify-pay-tab" data-method="Card">
                            <input type="radio" name="verify_pay_method" value="Card" style="display: none;">
                            <i class="fa-solid fa-credit-card" style="font-size: 1.1rem; color: #4338ca;"></i>
                            <span style="font-size: 0.76rem; font-weight: 700;">Card</span>
                        </label>
                        <label class="verify-pay-tab" data-method="NetBanking">
                            <input type="radio" name="verify_pay_method" value="NetBanking" style="display: none;">
                            <i class="fa-solid fa-building-columns" style="font-size: 1.1rem; color: #d97706;"></i>
                            <span style="font-size: 0.76rem; font-weight: 700;">NetBanking</span>
                        </label>
                    </div>

                    <!-- UPI Container -->
                    <div id="method-UPI" class="verify-method-content">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 0.85rem; text-align: center; margin-bottom: 0.75rem;">
                            <div style="font-weight: 800; font-size: 0.85rem; color: #166534;">Scan QR & Pay with Any UPI App</div>
                            <div style="font-size: 0.75rem; color: #15803d;">GPay, PhonePe, Paytm, BHIM</div>
                            <div style="margin: 0.6rem auto; width: 110px; height: 110px; background: #fff; padding: 6px; border-radius: 8px; border: 1px solid #86efac; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-qrcode" style="font-size: 90px; color: #0f172a;"></i>
                            </div>
                        </div>
                        <input type="text" id="verifyUpiId" class="form-control" placeholder="yourname@okhdfcbank / paytm" value="<?php echo preg_replace('/[^a-z0-9]/i', '', strtolower($user['name'])); ?>@upi">
                    </div>

                    <!-- Card Container -->
                    <div id="method-Card" class="verify-method-content" style="display: none;">
                        <input type="text" class="form-control mb-2" placeholder="Card Number" value="4532 &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; 8921">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <input type="text" class="form-control" placeholder="MM / YY" value="12/28">
                            <input type="password" class="form-control" placeholder="CVV" value="892" maxlength="4">
                        </div>
                    </div>

                    <!-- NetBanking Container -->
                    <div id="method-NetBanking" class="verify-method-content" style="display: none;">
                        <select class="form-select">
                            <option value="HDFC">HDFC Bank</option>
                            <option value="SBI">State Bank of India (SBI)</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="Axis">Axis Bank</option>
                            <option value="Kotak">Kotak Mahindra Bank</option>
                        </select>
                    </div>
                </div>

                <!-- Simulation Info Alert -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    <i class="fa-solid fa-shield text-primary"></i> Simulated Gateway for Hackathon Demo. Instant Golden Tick activation without charging real money.
                </div>

                <!-- Pay Button -->
                <button type="button" id="btnPayVerification" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 800; padding: 0.85rem; font-size: 1rem; background: linear-gradient(135deg, #f59e0b, #d97706); border: none; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.45); color: #fff;">
                    <i class="fa-solid fa-lock me-1"></i> Pay ₹199 & Activate Golden Tick
                </button>

                <!-- Processing Overlay -->
                <div id="verifyProcessingOverlay" style="display: none; margin-top: 1rem; text-align: center;">
                    <div class="spinner-border text-warning" role="status" style="width: 1.8rem; height: 1.8rem;"></div>
                    <div style="font-weight: 800; font-size: 0.9rem; margin-top: 0.5rem; color: #d97706;">
                        Authorizing Payment & Activating Golden Tick Credentials...
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<style>
.verify-pay-tab {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.65rem;
    text-align: center;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s ease;
}
.verify-pay-tab.active {
    background: #eff6ff;
    border-color: #0095f6;
    color: #0284c7;
    box-shadow: 0 0 0 2px rgba(0, 149, 246, 0.15);
}
@media (max-width: 900px) {
    div[style*="grid-template-columns: 1.15fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.verify-pay-tab');
    const contents = document.querySelectorAll('.verify-method-content');
    let selectedMethod = 'UPI';

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            selectedMethod = tab.getAttribute('data-method');
            
            contents.forEach(c => c.style.display = 'none');
            const targetContent = document.getElementById('method-' + selectedMethod);
            if (targetContent) targetContent.style.display = 'block';
        });
    });

    const payBtn = document.getElementById('btnPayVerification');
    const overlay = document.getElementById('verifyProcessingOverlay');

    if (payBtn) {
        payBtn.addEventListener('click', async () => {
            payBtn.disabled = true;
            payBtn.style.opacity = '0.7';
            overlay.style.display = 'block';

            const govtId = document.getElementById('verifyGovtId').value;

            try {
                const res = await fetch('api/process_verification_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: 199.00,
                        payment_method: selectedMethod,
                        govt_id_type: govtId
                    })
                });

                const data = await res.json();

                if (data.status === 'success') {
                    setTimeout(() => {
                        window.location.href = 'verify-receipt.php?txn=' + encodeURIComponent(data.transaction_id);
                    }, 1200);
                } else {
                    alert('Payment Error: ' + (data.message || 'Unable to process transaction.'));
                    payBtn.disabled = false;
                    payBtn.style.opacity = '1';
                    overlay.style.display = 'none';
                }
            } catch (err) {
                console.error(err);
                alert('Network or server error. Please try again.');
                payBtn.disabled = false;
                payBtn.style.opacity = '1';
                overlay.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
