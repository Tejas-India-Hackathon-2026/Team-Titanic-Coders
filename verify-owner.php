<?php
// verify-owner.php - Landlord DigiLocker & Government Document e-KYC & Golden Tick Gateway
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

$successMsg = '';
$errorMsg = '';

// Handle Owner DigiLocker Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_owner_digilocker'])) {
    $docType = sanitize($_POST['doc_type'] ?? 'DigiLocker Property KYC');
    $idNumber = sanitize($_POST['id_number'] ?? '');
    
    if (empty($idNumber)) {
        $errorMsg = 'Please enter your Aadhaar / Property document reference number.';
    } else {
        $cleanDigits = preg_replace('/[^0-9A-Za-z]/', '', $idNumber);
        $last4 = substr($cleanDigits, -4);
        $maskedId = 'XXXX-XXXX-' . ($last4 ? $last4 : '7819');

        $upStmt = $pdo->prepare("
            UPDATE owners 
            SET is_verified = 1, digilocker_aadhaar = ?, document_type = ?, verified_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->execute([$maskedId, $docType, $user['id']]);

        // Refresh session
        $_SESSION['user_is_verified'] = 1;
        $isAlreadyVerified = true;

        // Fetch updated record
        $ownerStmt->execute([$user['id']]);
        $ownerData = $ownerStmt->fetch();

        $successMsg = 'DigiLocker Landlord e-KYC Verified Successfully! Your listings now display the Golden Tick badge.';
    }
}

$page_title = "Get Verified Owner Golden Tick - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 2.5rem;">
        <div style="display: inline-flex; align-items: center; gap: 6px; background: #fef3c7; color: #b45309; padding: 0.35rem 0.95rem; border-radius: 20px; font-weight: 800; font-size: 0.85rem; margin-bottom: 0.75rem; border: 1.5px solid #fde68a; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);">
            <?php echo render_verified_badge(false, 18); ?> RentNear Gold VIP Landlord Verified
        </div>
        <h1 style="font-size: 2.4rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem;">
            Landlord <span style="background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">DigiLocker & Govt ID</span> Verification
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6;">
            Build 100% trust with prospective tenants, eliminate fraud concerns, and get the official <strong>⭐ Golden Tick Verified badge</strong> on all your rental listings.
        </p>
    </div>

    <?php if (isset($_GET['new_signup'])): ?>
        <div class="alert alert-success" style="max-width: 720px; margin: 0 auto 2rem; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 0.75rem; background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #065f46;">
            <div style="font-size: 1.5rem;"><i class="fa-solid fa-circle-check text-success"></i></div>
            <div>
                <strong>🎉 Step 1 & 2 Complete: Account & Mobile OTP Verified!</strong>
                <p style="margin: 0; font-size: 0.85rem; color: #047857;">Complete Step 3 below: Connect your DigiLocker or Property Proof to activate your Golden Tick.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success" style="max-width: 720px; margin: 0 auto 2rem; border-radius: 12px; padding: 1.25rem;">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="max-width: 720px; margin: 0 auto 2rem; border-radius: 12px; padding: 1rem;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <?php if ($isAlreadyVerified): ?>
        <div class="alert alert-success" style="max-width: 720px; margin: 0 auto 2.5rem; text-align: center; border-radius: 12px; padding: 1.5rem; background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.1);">
            <h4 style="font-weight: 800; margin-bottom: 0.35rem; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 1.3rem;">
                <?php echo render_verified_badge(false, 24); ?> You are an Official Gold Verified Landlord!
            </h4>
            <p style="font-size: 0.9rem; margin: 0 0 1rem 0; color: #b45309;">
                Your Golden Tick badge is active on all your rental properties and inquiries.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
                <a href="verify-receipt.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; font-weight: 800;">
                    <i class="fa-solid fa-certificate"></i> View My Gold Certificate & Pass
                </a>
                <a href="owner-dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-gauge-high"></i> Go to Owner Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 2.5rem; max-width: 1080px; margin: 0 auto; align-items: start;">
        
        <!-- Left: DigiLocker & Property Document Verification -->
        <div>
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-fingerprint text-primary"></i> Choose Your Landlord Verification Method:
                </h3>

                <!-- Method 1: Instant DigiLocker Aadhaar e-KYC -->
                <div style="background: #fffbeb; border: 2px solid #f59e0b; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.5rem; position: relative;">
                    <span style="position: absolute; top: -10px; right: 15px; background: #d97706; color: #fff; font-size: 0.72rem; font-weight: 800; padding: 2px 10px; border-radius: 12px; text-transform: uppercase;">
                        ⭐ Recommended (1-Click Instant)
                    </span>

                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; border: 1px solid #fde68a;">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #78350f;">DigiLocker Government Landlord e-KYC</h4>
                            <p style="font-size: 0.8rem; color: #92400e; margin: 0;">Link via UIDAI Aadhaar / Land Registry Document</p>
                        </div>
                    </div>

                    <form action="verify-owner.php" method="POST">
                        <input type="hidden" name="complete_owner_digilocker" value="1">
                        <input type="hidden" name="doc_type" value="DigiLocker Aadhaar & Property e-KYC">

                        <div class="form-group mb-3">
                            <label for="ownerAadhaarInput" style="font-weight: 700; font-size: 0.85rem; color: #78350f;">
                                12-Digit Owner Aadhaar / Virtual ID Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="id_number" id="ownerAadhaarInput" class="form-control" placeholder="XXXX XXXX 7819" maxlength="14" oninput="formatAadhaar(this)" required style="font-family: monospace; font-size: 1.1rem; font-weight: 700; letter-spacing: 2px;">
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('ownerAadhaarInput').value='4910 3829 7819'" style="font-size: 0.78rem;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill Demo Aadhaar
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; font-weight: 800; background: linear-gradient(135deg, #f59e0b, #d97706); border: none;">
                                <i class="fa-solid fa-shield-check"></i> Verify with DigiLocker (Instant)
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Method 2: Electricity Bill / Property Registration -->
                <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-bolt text-warning"></i> Option B: Electricity Consumer No / Property Tax Bill
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Verify using your state electricity consumer number or municipal property tax receipt.
                    </p>

                    <form action="verify-owner.php" method="POST">
                        <input type="hidden" name="complete_owner_digilocker" value="1">
                        <input type="hidden" name="doc_type" value="Electricity / Property Tax Bill">

                        <div class="form-group mb-3">
                            <label style="font-weight: 700; font-size: 0.82rem;">Consumer / Tax Assessment ID <span class="text-danger">*</span></label>
                            <input type="text" name="id_number" class="form-control" placeholder="e.g. SBPDCL-CA-902184 or NDMC-TAX-4421" required>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; font-weight: 700;">
                            <i class="fa-solid fa-file-invoice"></i> Verify via Utility Bill &rarr;
                        </button>
                    </form>
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
                        <p style="font-size: 0.76rem; color: #b45309; font-weight: 700; margin: 0;">
                            <i class="fa-solid fa-shield-check text-warning"></i> Govt ID & DigiLocker Verified Landlord
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Benefits Card -->
        <div>
            <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; color: var(--dark);">
                    <i class="fa-solid fa-star text-warning me-1"></i> Why Get Golden Tick Verified?
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div>
                            <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">Iconic Golden Tick Badge</h5>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                Your profile and listings display the recognized Golden Tick across search results, map pins, and cards.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">5x More Tenant Inquiries</h5>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                Students and families prefer verified owners to avoid broker scams and fake listings.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                            <i class="fa-solid fa-certificate"></i>
                        </div>
                        <div>
                            <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">Printable VIP Landlord Certificate</h5>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                Download your official digital landlord credential for legal tenant lease agreements.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); text-align: center;">
                    <a href="owner-dashboard.php" style="font-size: 0.85rem; color: var(--text-muted);">
                        Skip for now & go to Dashboard &rarr;
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
function formatAadhaar(input) {
    var val = input.value.replace(/\D/g, '');
    var formatted = '';
    for (var i = 0; i < val.length && i < 12; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += val[i];
    }
    input.value = formatted;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>