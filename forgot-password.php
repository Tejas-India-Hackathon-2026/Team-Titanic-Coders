<?php
// forgot-password.php - Forgot & Reset Password Flow for Owners, Renters, and Admins
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$step = 1;
$error = '';
$success = '';
$email = sanitize($_GET['email'] ?? ($_POST['email'] ?? ''));

// Handle Step 1: Request OTP / Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your registered email address.';
    } else {
        $accountFound = false;
        $userRole = '';
        $userName = '';

        // 1. Check Owners Table
        $stmtO = $pdo->prepare("SELECT id, name, email FROM owners WHERE LOWER(email) = LOWER(:email)");
        $stmtO->execute([':email' => $email]);
        $owner = $stmtO->fetch();
        if ($owner) {
            $accountFound = true;
            $userRole = 'owner';
            $userName = $owner['name'];
        }

        // 2. Check Renters Table
        if (!$accountFound) {
            $stmtR = $pdo->prepare("SELECT id, name, email FROM renters WHERE LOWER(email) = LOWER(:email)");
            $stmtR->execute([':email' => $email]);
            $renter = $stmtR->fetch();
            if ($renter) {
                $accountFound = true;
                $userRole = 'renter';
                $userName = $renter['name'];
            }
        }

        // 3. Check Admins Table
        if (!$accountFound) {
            $stmtA = $pdo->prepare("SELECT id, name, email FROM admins WHERE LOWER(email) = LOWER(:email)");
            $stmtA->execute([':email' => $email]);
            $admin = $stmtA->fetch();
            if ($admin) {
                $accountFound = true;
                $userRole = 'admin';
                $userName = $admin['name'];
            }
        }

        if ($accountFound) {
            // Generate a 6-digit mock OTP and save in session
            $mockOtp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $userRole;
            $_SESSION['reset_otp'] = $mockOtp;
            $_SESSION['reset_time'] = time();

            $step = 2;
            $success = "Verification code generated for $userName ($email).";
        } else {
            $error = 'No account found with this email address in our database.';
        }
    }
}

// Handle Step 2: Verify OTP and Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = sanitize($_SESSION['reset_email'] ?? ($_POST['email'] ?? ''));
    $enteredOtp = trim($_POST['otp'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $sessionOtp = $_SESSION['reset_otp'] ?? '';
    $userRole = $_SESSION['reset_role'] ?? '';

    $step = 2; // Keep on step 2 unless success

    if (empty($email) || empty($enteredOtp) || empty($newPass) || empty($confirmPass)) {
        $error = 'Please fill out all fields.';
    } elseif ($enteredOtp != $sessionOtp) {
        $error = 'Invalid verification OTP code. Please enter the correct code.';
    } elseif (strlen($newPass) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'New password and confirmation password do not match.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $tableName = ($userRole === 'owner') ? 'owners' : (($userRole === 'admin') ? 'admins' : 'renters');

        // Update password in the respective table
        $updateStmt = $pdo->prepare("UPDATE $tableName SET password = :pass WHERE LOWER(email) = LOWER(:email)");
        $updateStmt->execute([
            ':pass' => $hashed,
            ':email' => $email
        ]);

        // Clear reset session
        unset($_SESSION['reset_email'], $_SESSION['reset_role'], $_SESSION['reset_otp'], $_SESSION['reset_time']);

        set_flash_message('success', 'Your password has been reset successfully! You can now login with your new password.');
        header("Location: login.php");
        exit;
    }
}

// If already generated OTP in session, show step 2
if (isset($_SESSION['reset_otp']) && !empty($_SESSION['reset_email']) && $step === 1 && !isset($_POST['request_otp'])) {
    $step = 2;
    $email = $_SESSION['reset_email'];
}

$page_title = "Forgot Password - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-header">
            <div class="brand-logo justify-content-center mb-2" style="justify-content: center;">
                <div class="logo-icon" style="background: var(--primary-light); color: var(--primary);">
                    <i class="fa-solid fa-key"></i>
                </div>
            </div>
            <h2>Password Recovery</h2>
            <p class="text-muted" style="font-size: 0.9rem; color: var(--text-muted);">
                <?php echo $step === 1 ? 'Enter your registered email to receive an OTP code.' : 'Enter the OTP and your new password.'; ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem; padding: 0.75rem 1rem;">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 2 && isset($_SESSION['reset_otp'])): ?>
            <!-- Simulated SMS/Email OTP Banner for Easy Hackathon / Local Testing -->
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; text-align: center;">
                <div style="font-size: 0.8rem; color: #1e40af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fa-solid fa-envelope-circle-check text-primary me-1"></i> Simulated Verification OTP:
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #1d4ed8; letter-spacing: 4px; margin: 0.3rem 0;">
                    <?php echo $_SESSION['reset_otp']; ?>
                </div>
                <div style="font-size: 0.75rem; color: #3b82f6;">
                    Auto-generated for <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Step 1 Form: Email Input -->
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="request_otp" value="1">

                <div class="form-group">
                    <label for="resetEmail"><i class="fa-solid fa-envelope me-1"></i> Registered Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="resetEmail" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g. abc@gah.com)" required autofocus>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    <i class="fa-solid fa-paper-plane"></i> Send Verification OTP
                </button>
            </form>

            <!-- 1-Click Fast Fill for Evaluators -->
            <div class="demo-account-box" style="margin-top: 1.5rem;">
                <h6><i class="fa-solid fa-wand-magic-sparkles"></i> 1-Click Demo Accounts to Test:</h6>
                <div class="demo-btn-group">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('resetEmail').value='owner@rentnear.com'">
                        <i class="fa-solid fa-house-user"></i> Owner
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('resetEmail').value='renter@rentnear.com'">
                        <i class="fa-solid fa-user"></i> Renter
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('resetEmail').value='admin@rentnear.com'">
                        <i class="fa-solid fa-shield-halved"></i> Admin
                    </button>
                </div>
            </div>

        <?php else: ?>
            <!-- Step 2 Form: OTP & New Password -->
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="form-group">
                    <label for="otpInput"><i class="fa-solid fa-shield-halved me-1"></i> 6-Digit Verification OTP <span class="text-danger">*</span></label>
                    <input type="text" name="otp" id="otpInput" class="form-control" placeholder="Enter 6-digit code" maxlength="6" value="<?php echo isset($_SESSION['reset_otp']) ? $_SESSION['reset_otp'] : ''; ?>" required style="letter-spacing: 2px; font-weight: 700; font-size: 1.1rem; text-align: center;">
                </div>

                <div class="form-group">
                    <label for="newPass"><i class="fa-solid fa-lock me-1"></i> New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirmPass"><i class="fa-solid fa-check-double me-1"></i> Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Re-type new password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    <i class="fa-solid fa-check"></i> Reset & Update Password
                </button>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="forgot-password.php?step=1" style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                        <i class="fa-solid fa-arrow-left"></i> Change Email Address
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted); border-top: 1px solid var(--border-light); padding-top: 1rem;">
            Remembered your password? <a href="login.php" style="font-weight: 700;">Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
