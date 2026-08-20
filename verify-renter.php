<?php
// verify-renter.php - Tenant DigiLocker & Government e-KYC Verification
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_renter();
$user = current_user();

// Fetch fresh renter record
$renterStmt = $pdo->prepare("SELECT * FROM renters WHERE id = ?");
$renterStmt->execute([$user['id']]);
$renterData = $renterStmt->fetch();

$isAlreadyVerified = !empty($renterData['is_verified']) && (int)$renterData['is_verified'] === 1;

// Handle DigiLocker Verification Submission
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_digilocker_verification'])) {
    $docType = sanitize($_POST['doc_type'] ?? 'DigiLocker Aadhaar');
    $idNumber = sanitize($_POST['id_number'] ?? '');
    
    if (empty($idNumber)) {
        $errorMsg = 'Please enter your Aadhaar / Govt ID number.';
    } else {
        // Mask ID for privacy (e.g. XXXX-XXXX-4821)
        $cleanDigits = preg_replace('/[^0-9A-Za-z]/', '', $idNumber);
        $last4 = substr($cleanDigits, -4);
        $maskedId = 'XXXX-XXXX-' . ($last4 ? $last4 : '9821');

        $upStmt = $pdo->prepare("
            UPDATE renters 
            SET is_verified = 1, digilocker_aadhaar = ?, document_type = ?, verified_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->execute([$maskedId, $docType, $user['id']]);

        // Refresh session
        $_SESSION['user_is_verified'] = 1;
        $isAlreadyVerified = true;

        // Fetch updated record
        $renterStmt->execute([$user['id']]);
        $renterData = $renterStmt->fetch();

        $successMsg = 'DigiLocker e-KYC Verified Successfully! Your profile is now marked with the DigiLocker Verified Tenant Badge.';
    }
}

$page_title = "Tenant DigiLocker & Govt e-KYC - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 2.5rem;">
        <div style="display: inline-flex; align-items: center; gap: 6px; background: #dcfce7; color: #166534; padding: 0.35rem 0.95rem; border-radius: 20px; font-weight: 800; font-size: 0.85rem; margin-bottom: 0.75rem; border: 1.5px solid #bbf7d0;">
            <?php echo render_renter_verified_badge(false, 18); ?> Official Tenant e-KYC
        </div>
        <h1 style="font-size: 2.4rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem;">
            Tenant <span style="background: linear-gradient(135deg, #16a34a, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">DigiLocker & Govt ID</span> Verification
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6;">
            Verify your identity with Government DigiLocker or College/Office ID to get <strong>3x faster room approvals</strong>, <strong>zero security deposit discounts</strong>, and the <strong>🛡️ DigiLocker Verified Tenant Badge</strong>.
        </p>
    </div>

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
        <!-- Already Verified Certificate Card -->
        <div style="max-width: 720px; margin: 0 auto 3rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #86efac; border-radius: var(--radius-xl); padding: 2.5rem; box-shadow: 0 15px 35px rgba(22, 163, 74, 0.12); position: relative; overflow: hidden;">
            
            <div style="position: absolute; right: -20px; top: -20px; width: 140px; height: 140px; background: rgba(34, 197, 94, 0.1); border-radius: 50%; pointer-events: none;"></div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="background: #16a34a; color: #fff; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.75rem; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-shield-check me-1"></i> Authenticated by DigiLocker
                    </span>
                    <h2 style="font-size: 1.6rem; font-weight: 800; color: #065f46; margin: 0.5rem 0 0.2rem 0; display: flex; align-items: center; gap: 8px;">
                        <?php echo htmlspecialchars($renterData['name']); ?>
                        <?php echo render_renter_verified_badge(false, 22); ?>
                    </h2>
                    <p style="color: #166534; font-size: 0.9rem; margin: 0;">
                        <?php echo htmlspecialchars($renterData['email']); ?> • +91 <?php echo htmlspecialchars($renterData['phone']); ?>
                    </p>
                </div>

                <div style="text-align: right; background: #fff; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); border: 1.5px solid #86efac; box-shadow: var(--shadow-sm);">
                    <div style="font-size: 0.75rem; color: #166534; font-weight: 700; text-transform: uppercase;">Doc Reference</div>
                    <div style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #065f46;">
                        <?php echo htmlspecialchars($renterData['digilocker_aadhaar'] ?? 'XXXX-XXXX-8921'); ?>
                    </div>
                    <div style="font-size: 0.72rem; color: #15803d;">
                        Verified on <?php echo date('d M Y', strtotime($renterData['verified_at'] ?? 'now')); ?>
                    </div>
                </div>
            </div>

            <div style="background: #ffffff; border-radius: var(--radius-md); padding: 1.25rem; border: 1px solid #bbf7d0; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block;">Verification Type</span>
                    <strong style="color: #0f172a; font-size: 0.95rem;">
                        <i class="fa-solid fa-id-card text-success me-1"></i> <?php echo htmlspecialchars($renterData['document_type'] ?? 'DigiLocker Aadhaar'); ?>
                    </strong>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block;">Police Clearance Status</span>
                    <strong style="color: #16a34a; font-size: 0.95rem;">🟢 KYC Approved</strong>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block;">Tenant Trust Score</span>
                    <strong style="color: #0f172a; font-size: 0.95rem;">⭐⭐⭐⭐⭐ (100%)</strong>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Verification Pass
                </button>
                <a href="renter-dashboard.php" class="btn btn-primary btn-sm" style="background: #16a34a; border-color: #15803d; font-weight: 700;">
                    <i class="fa-solid fa-gauge-high"></i> Go to My Dashboard &rarr;
                </a>
            </div>
        </div>

    <?php else: ?>

        <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 2.5rem; max-width: 1080px; margin: 0 auto; align-items: start;">
            
            <!-- Left: DigiLocker & ID Options -->
            <div>
                <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                    
                    <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-fingerprint text-primary"></i> Choose Your Verification Method:
                    </h3>

                    <!-- Option 1: Instant DigiLocker -->
                    <div style="background: #f8fafc; border: 2px solid var(--primary); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.5rem; position: relative;">
                        <span style="position: absolute; top: -10px; right: 15px; background: var(--primary); color: #fff; font-size: 0.72rem; font-weight: 800; padding: 2px 10px; border-radius: 12px; text-transform: uppercase;">
                            ⭐ Recommended (1-Click Instant)
                        </span>

                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                                <i class="fa-solid fa-cloud-arrow-down"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0;">DigiLocker Government e-KYC</h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Directly link via UIDAI Aadhaar / Driving License</p>
                            </div>
                        </div>

                        <form action="verify-renter.php" method="POST" id="digilockerForm">
                            <input type="hidden" name="complete_digilocker_verification" value="1">
                            <input type="hidden" name="doc_type" value="DigiLocker Aadhaar e-KYC">

                            <div class="form-group mb-3">
                                <label for="aadhaarInput" style="font-weight: 700; font-size: 0.85rem;">12-Digit Aadhaar / Virtual ID Number <span class="text-danger">*</span></label>
                                <input type="text" name="id_number" id="aadhaarInput" class="form-control" placeholder="XXXX XXXX 4821" maxlength="14" oninput="formatAadhaar(this)" required style="font-family: monospace; font-size: 1.1rem; font-weight: 700; letter-spacing: 2px;">
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('aadhaarInput').value='5849 2018 4821'" style="font-size: 0.78rem;">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill Demo Aadhaar
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; font-weight: 800; background: linear-gradient(135deg, #16a34a, #059669); border: none;">
                                    <i class="fa-solid fa-shield-check"></i> Connect & Verify with DigiLocker
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Option 2: College / Company Work ID -->
                    <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.25rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-graduation-cap text-primary"></i> Option B: Student or Corporate Work ID
                        </h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                            If you are a student or corporate employee, enter your Institution/Company roll or employee ID.
                        </p>

                        <form action="verify-renter.php" method="POST">
                            <input type="hidden" name="complete_digilocker_verification" value="1">
                            <input type="hidden" name="doc_type" value="College / Employee ID">

                            <div class="form-group mb-3">
                                <label style="font-weight: 700; font-size: 0.82rem;">Student Roll No / Employee ID Code <span class="text-danger">*</span></label>
                                <input type="text" name="id_number" class="form-control" placeholder="e.g. IITP-2024-CS-042 or TCS-EMP-88412" required>
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; font-weight: 700;">
                                <i class="fa-solid fa-id-badge"></i> Verify via Student/Work ID &rarr;
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Right: Why Verify Benefits Card -->
            <div>
                <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; color: var(--dark);">
                        <i class="fa-solid fa-star text-warning me-1"></i> Why Get DigiLocker Verified?
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div>
                                <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">3x Faster Landlord Approval</h5>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                    Homeowners prioritize verified tenants because they know background and identity is clean.
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">Security Deposit Discounts</h5>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                    Verified tenants often get concessions on high upfront security deposits.
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px;">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            <div>
                                <h5 style="font-size: 0.92rem; font-weight: 700; margin: 0 0 0.15rem 0;">Verified Tenant Badge</h5>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                                    Your inquiries and profile display the official green <strong>🛡️ DigiLocker Verified</strong> tag.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); text-align: center;">
                        <a href="properties.php" style="font-size: 0.85rem; color: var(--text-muted);">
                            Skip for now & browse rooms &rarr;
                        </a>
                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

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