<?php
// register.php - Enhanced 2-Step Registration with Strong Password & Live OTP Verification
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$step = 1;
$selectedRole = isset($_GET['role']) && $_GET['role'] === 'owner' ? 'owner' : 'renter';

// Handle Step 2: Verify OTP and Create Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_registration_otp'])) {
    $enteredOtp = trim($_POST['otp'] ?? '');
    
    if (empty($_SESSION['pending_reg_user']) || empty($_SESSION['pending_reg_otp'])) {
        $error = 'Session expired. Please register again.';
        $step = 1;
    } elseif ($enteredOtp != $_SESSION['pending_reg_otp']) {
        $error = 'Invalid OTP code. Please enter the correct 6-digit verification code.';
        $step = 2;
    } else {
        // OTP Validated! Commit user into DB
        $regData = $_SESSION['pending_reg_user'];
        $name = $regData['name'];
        $email = $regData['email'];
        $phone = $regData['phone'];
        $role = $regData['role'];
        $hashedPassword = $regData['password'];

        if ($role === 'owner') {
            $stmt = $pdo->prepare("INSERT INTO owners (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $email, $phone, $hashedPassword]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO renters (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $email, $phone, $hashedPassword]);
        }

        $newUserId = $pdo->lastInsertId();

        // Clear OTP Session
        unset($_SESSION['pending_reg_user']);
        unset($_SESSION['pending_reg_otp']);

        // Auto-login newly registered user
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_is_verified'] = 0;

        set_flash_message('success', 'Account created & mobile verified! Welcome to RentNear.');

        if ($role === 'owner') {
            // Prompt owner to verify documents / Golden Tick
            header("Location: verify-owner.php?new_signup=1");
        } else {
            // Prompt renter to complete instant DigiLocker KYC
            header("Location: verify-renter.php?new_signup=1");
        }
        exit;
    }
}

// Handle Step 1: Submit Registration Form & Generate OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration_step1'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $role = sanitize($_POST['role'] ?? 'renter');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Strip non-digits from phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Password Regex: Min 6 chars, uppercase, lowercase, number, special char (e.g. Abc@#$25)
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{6,}$/';

    // Validate
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $error = 'Please enter a valid email address (e.g. rahul@example.com).';
    } elseif (strlen($phone) !== 10 || !preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
    } elseif (!preg_match($passwordPattern, $password)) {
        $error = 'Password must include uppercase (A-Z), lowercase (a-z), number (0-9), and special symbol (@#$!%*?&), e.g. Abc@#$25';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please re-enter.';
    } elseif (!in_array($role, ['renter', 'owner'])) {
        $role = 'renter';
    } else {
        // Check if email already exists in owners or renters
        $tableToCheck = ($role === 'owner') ? 'owners' : 'renters';
        $checkStmt = $pdo->prepare("SELECT id FROM $tableToCheck WHERE LOWER(email) = LOWER(:email)");
        $checkStmt->execute([':email' => $email]);

        if ($checkStmt->fetch()) {
            $error = 'An account with this email already exists in ' . ucfirst($role) . 's. Please login.';
        } else {
            // Generate 6-Digit Mock Verification OTP
            $mockOtp = rand(100000, 999999);
            
            $_SESSION['pending_reg_user'] = [
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'role'     => $role,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            $_SESSION['pending_reg_otp'] = $mockOtp;
            $_SESSION['pending_reg_time'] = time();

            $step = 2;
        }
    }
}

// Resend OTP Action
if (isset($_GET['resend_otp']) && !empty($_SESSION['pending_reg_user'])) {
    $_SESSION['pending_reg_otp'] = rand(100000, 999999);
    $_SESSION['pending_reg_time'] = time();
    $step = 2;
}

$page_title = "Create an Account - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper" style="padding: 2.5rem 1rem;">
    <div class="auth-card" style="max-width: 540px; box-shadow: 0 15px 35px rgba(0,0,0,0.12);">
        
        <?php if ($step === 1): ?>
            <!-- STEP 1: Registration Credentials Setup -->
            <div class="auth-header text-center">
                <div class="brand-logo justify-content-center mb-2" style="justify-content: center;">
                    <div class="logo-icon">
                        <i class="fa-solid fa-house-chimney"></i>
                    </div>
                </div>
                <h2>Create Your Account</h2>
                <p class="text-muted" style="font-size: 0.9rem; color: var(--text-muted);">Join as a Property Owner or Tenant / Renter.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="font-size: 0.9rem; padding: 0.75rem 1rem;">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <input type="hidden" name="submit_registration_step1" value="1">
                
                <!-- Role Selector Tabs -->
                <div class="form-group mb-3">
                    <label style="font-weight: 700;">Select Account Type:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div id="roleCardRenter" onclick="selectRole('renter')" style="border: 2px solid <?php echo $selectedRole === 'renter' ? 'var(--primary)' : 'var(--border-color)'; ?>; padding: 0.85rem; border-radius: var(--radius-md); text-align: center; cursor: pointer; background: <?php echo $selectedRole === 'renter' ? 'var(--primary-light)' : '#fff'; ?>; box-shadow: <?php echo $selectedRole === 'renter' ? '0 4px 12px rgba(79, 70, 229, 0.18)' : 'none'; ?>; transition: all 0.2s ease;">
                            <input type="radio" name="role" id="roleInputRenter" value="renter" <?php echo $selectedRole === 'renter' ? 'checked' : ''; ?> style="display: none;">
                            <i class="fa-solid fa-user-tag" style="font-size: 1.35rem; color: var(--primary); display: block; margin-bottom: 0.35rem;"></i>
                            <strong style="display: block; font-size: 0.95rem; color: var(--dark);">Tenant / Renter</strong>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Saved in `renters` table</span>
                        </div>

                        <div id="roleCardOwner" onclick="selectRole('owner')" style="border: 2px solid <?php echo $selectedRole === 'owner' ? 'var(--primary)' : 'var(--border-color)'; ?>; padding: 0.85rem; border-radius: var(--radius-md); text-align: center; cursor: pointer; background: <?php echo $selectedRole === 'owner' ? 'var(--primary-light)' : '#fff'; ?>; box-shadow: <?php echo $selectedRole === 'owner' ? '0 4px 12px rgba(79, 70, 229, 0.18)' : 'none'; ?>; transition: all 0.2s ease;">
                            <input type="radio" name="role" id="roleInputOwner" value="owner" <?php echo $selectedRole === 'owner' ? 'checked' : ''; ?> style="display: none;">
                            <i class="fa-solid fa-house-chimney-user" style="font-size: 1.35rem; color: var(--primary); display: block; margin-bottom: 0.35rem;"></i>
                            <strong style="display: block; font-size: 0.95rem; color: var(--dark);">Property Owner</strong>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Saved in `owners` table</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="regName">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="regName" class="form-control" placeholder="e.g. Rahul Sharma" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="regEmail" class="form-control" placeholder="e.g. rahul@example.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g. abc@gah.com)" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <small style="font-size: 0.76rem; color: var(--text-muted);">Standard email format required (e.g. abc@gah.com)</small>
                </div>

                <div class="form-group">
                    <label for="regPhone">Mobile Number (10 Digits) <span class="text-danger">*</span></label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="background: var(--bg-alt); border: 1.5px solid var(--border-color); padding: 0.65rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.92rem; color: var(--dark);">+91</span>
                        <input type="tel" name="phone" id="regPhone" class="form-control" placeholder="9876543210" maxlength="10" minlength="10" pattern="[6-9][0-9]{9}" title="Please enter exact 10-digit Indian mobile number (e.g. 9876543210)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <small style="font-size: 0.76rem; color: var(--text-muted);">An instant 6-digit OTP will be sent to this number.</small>
                </div>

                <!-- Unique Password with Live Checklist -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label for="regPass">Create Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="regPass" class="form-control" placeholder="e.g. Abc@#$25" oninput="checkPasswordStrength(this.value)" required>
                    </div>
                    <div class="form-group">
                        <label for="regConfirmPass">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="regConfirmPass" class="form-control" placeholder="Re-type password" required>
                    </div>
                </div>

                <!-- Live Password Strength Visual Card -->
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--dark); margin-bottom: 0.4rem; display: flex; justify-content: space-between;">
                        <span><i class="fa-solid fa-shield-halved text-primary me-1"></i> Unique Password Security:</span>
                        <span id="strengthText" style="color: #64748b;">Enter Password</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem 0.5rem; font-size: 0.72rem; color: #64748b;">
                        <div id="ruleLength"><i class="fa-regular fa-circle me-1"></i> Min 6 characters</div>
                        <div id="ruleLower"><i class="fa-regular fa-circle me-1"></i> Lowercase letter (a-z)</div>
                        <div id="ruleUpper"><i class="fa-regular fa-circle me-1"></i> Uppercase letter (A-Z)</div>
                        <div id="ruleNumber"><i class="fa-regular fa-circle me-1"></i> Number digit (0-9)</div>
                        <div id="ruleSpecial" style="grid-column: span 2;"><i class="fa-regular fa-circle me-1"></i> Special symbol (@, #, $, %, &, *)</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700;">
                    <i class="fa-solid fa-mobile-screen-button me-1"></i> Verify Mobile Number & Proceed &rarr;
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="font-weight: 700;">Sign in here</a>
            </div>

        <?php else: ?>
            <!-- STEP 2: Real-time OTP Verification Screen -->
            <div class="auth-header text-center">
                <div class="logo-icon mb-2" style="width: 48px; height: 48px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 1.4rem; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto;">
                    <i class="fa-solid fa-comment-sms"></i>
                </div>
                <h2>Mobile & Email OTP Verification</h2>
                <p class="text-muted" style="font-size: 0.88rem; color: var(--text-muted);">
                    We sent a 6-digit code to <strong>+91 <?php echo htmlspecialchars($_SESSION['pending_reg_user']['phone'] ?? ''); ?></strong>
                </p>
            </div>

            <!-- Simulated SMS OTP Banner -->
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; text-align: center;">
                <div style="font-size: 0.78rem; color: #1e40af; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fa-solid fa-mobile-screen text-primary me-1"></i> Simulated SMS / WhatsApp OTP:
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: #1d4ed8; letter-spacing: 6px; margin: 0.3rem 0;">
                    <?php echo $_SESSION['pending_reg_otp'] ?? '------'; ?>
                </div>
                <div style="font-size: 0.75rem; color: #3b82f6;">
                    Generated for <strong><?php echo htmlspecialchars($_SESSION['pending_reg_user']['email'] ?? ''); ?></strong>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="font-size: 0.9rem; padding: 0.75rem 1rem;">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="verify_registration_otp" value="1">

                <div class="form-group">
                    <label for="otpCodeInput" style="font-weight: 700;"><i class="fa-solid fa-key me-1"></i> Enter 6-Digit OTP Code <span class="text-danger">*</span></label>
                    <input type="text" name="otp" id="otpCodeInput" class="form-control" placeholder="6-digit OTP" maxlength="6" pattern="[0-9]{6}" style="font-size: 1.4rem; font-weight: 800; letter-spacing: 4px; text-align: center;" required autofocus>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('otpCodeInput').value='<?php echo $_SESSION['pending_reg_otp'] ?? ''; ?>'" style="flex: 1;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill OTP
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1.5; font-weight: 700;">
                        <i class="fa-solid fa-check-double"></i> Confirm & Register
                    </button>
                </div>
            </form>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Didn't receive code? <a href="register.php?resend_otp=1" style="font-weight: 700; color: var(--primary);">Resend OTP</a></span>
                <a href="register.php" style="color: var(--text-muted);">Edit Details</a>
            </div>

        <?php endif; ?>

    </div>
</div>

<script>
function selectRole(role) {
    var renterCard = document.getElementById('roleCardRenter');
    var ownerCard = document.getElementById('roleCardOwner');
    var renterInput = document.getElementById('roleInputRenter');
    var ownerInput = document.getElementById('roleInputOwner');

    if (!renterCard || !ownerCard) return;

    if (role === 'owner') {
        ownerInput.checked = true;
        renterInput.checked = false;
        ownerCard.style.borderColor = 'var(--primary)';
        ownerCard.style.backgroundColor = 'var(--primary-light)';
        ownerCard.style.boxShadow = '0 4px 12px rgba(79, 70, 229, 0.18)';
        renterCard.style.borderColor = 'var(--border-color)';
        renterCard.style.backgroundColor = '#fff';
        renterCard.style.boxShadow = 'none';
    } else {
        renterInput.checked = true;
        ownerInput.checked = false;
        renterCard.style.borderColor = 'var(--primary)';
        renterCard.style.backgroundColor = 'var(--primary-light)';
        renterCard.style.boxShadow = '0 4px 12px rgba(79, 70, 229, 0.18)';
        ownerCard.style.borderColor = 'var(--border-color)';
        ownerCard.style.backgroundColor = '#fff';
        ownerCard.style.boxShadow = 'none';
    }
}

function checkPasswordStrength(val) {
    var hasLength = val.length >= 6;
    var hasLower = /[a-z]/.test(val);
    var hasUpper = /[A-Z]/.test(val);
    var hasNumber = /[0-9]/.test(val);
    var hasSpecial = /[@$!%*?&#]/.test(val);

    updateRuleItem('ruleLength', hasLength);
    updateRuleItem('ruleLower', hasLower);
    updateRuleItem('ruleUpper', hasUpper);
    updateRuleItem('ruleNumber', hasNumber);
    updateRuleItem('ruleSpecial', hasSpecial);

    var strengthText = document.getElementById('strengthText');
    var score = (hasLength?1:0) + (hasLower?1:0) + (hasUpper?1:0) + (hasNumber?1:0) + (hasSpecial?1:0);

    if (score === 5) {
        strengthText.textContent = '🔒 Excellent (Very Strong)';
        strengthText.style.color = '#16a34a';
    } else if (score >= 3) {
        strengthText.textContent = '🟡 Moderate';
        strengthText.style.color = '#d97706';
    } else {
        strengthText.textContent = '🔴 Weak Pattern';
        strengthText.style.color = '#dc2626';
    }
}

function updateRuleItem(id, passed) {
    var el = document.getElementById(id);
    if (!el) return;
    if (passed) {
        el.style.color = '#16a34a';
        el.style.fontWeight = '700';
        el.innerHTML = '<i class="fa-solid fa-circle-check me-1 text-success"></i> ' + el.textContent.replace(/^[•✓x\s]+/, '').replace(/^<i[^>]*><\/i>\s*/, '');
    } else {
        el.style.color = '#64748b';
        el.style.fontWeight = '400';
        el.innerHTML = '<i class="fa-regular fa-circle me-1"></i> ' + el.textContent.replace(/^[•✓x\s]+/, '').replace(/^<i[^>]*><\/i>\s*/, '');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
