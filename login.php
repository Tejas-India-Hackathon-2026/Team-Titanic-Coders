<?php
// login.php - User Login with Multi-Table Check (Owners, Renters, Admins)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Handle explicit logout notification
if (isset($_GET['logged_out'])) {
    $_SESSION = [];
    session_unset();
    set_flash_message('success', 'You have been successfully signed out.');
}

// Redirect if already logged in (skip if explicit logged_out is present)
if (!isset($_GET['logged_out']) && is_logged_in()) {
    $role = user_role();
    if ($role === 'owner') header("Location: owner-dashboard.php");
    elseif ($role === 'admin') header("Location: admin-dashboard.php");
    else header("Location: index.php");
    exit;
}

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cyber Defense: Brute-Force Bot Rate-Limiter (Max 5 attempts / 60 seconds)
    $failedAttempts = $_SESSION['login_fail_count'] ?? 0;
    $lastFailTime   = $_SESSION['login_fail_time'] ?? 0;
    
    if ($failedAttempts >= 5 && (time() - $lastFailTime) < 60) {
        $remainingSec = 60 - (time() - $lastFailTime);
        $error = "⚠️ Too many failed attempts. Security Cooldown active: Please wait {$remainingSec}s before trying again.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email address and password.';
        } else {
            $user = null;
            $role = null;

            // 1. Check in separate 'owners' table
            $stmtO = $pdo->prepare("SELECT * FROM owners WHERE LOWER(email) = LOWER(:email)");
            $stmtO->execute([':email' => $email]);
            $owner = $stmtO->fetch();
            if ($owner && password_verify($password, $owner['password'])) {
                $user = $owner;
                $role = 'owner';
            }

            // 2. Check in separate 'renters' table
            if (!$user) {
                $stmtR = $pdo->prepare("SELECT * FROM renters WHERE LOWER(email) = LOWER(:email)");
                $stmtR->execute([':email' => $email]);
                $renter = $stmtR->fetch();
                if ($renter && password_verify($password, $renter['password'])) {
                    $user = $renter;
                    $role = 'renter';
                }
            }

            // 3. Check in separate 'admins' table
            if (!$user) {
                $stmtA = $pdo->prepare("SELECT * FROM admins WHERE LOWER(email) = LOWER(:email)");
                $stmtA->execute([':email' => $email]);
                $admin = $stmtA->fetch();
                if ($admin && password_verify($password, $admin['password'])) {
                    $user = $admin;
                    $role = 'admin';
                }
            }

            if ($user && $role) {
                // Reset failed attempts on success
                unset($_SESSION['login_fail_count']);
                unset($_SESSION['login_fail_time']);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $role;
                $_SESSION['user_phone'] = $user['phone'] ?? '';

                set_flash_message('success', 'Welcome back, ' . $user['name'] . '!');

                if (!empty($redirect)) {
                    header("Location: " . $redirect);
                } else {
                    if ($role === 'owner') header("Location: owner-dashboard.php");
                    elseif ($role === 'admin') header("Location: admin-dashboard.php");
                    elseif ($role === 'renter') header("Location: renter-dashboard.php");
                    else header("Location: index.php");
                }
                exit;
            } else {
                // Track failed attempt
                $_SESSION['login_fail_count'] = ($failedAttempts >= 5 && (time() - $lastFailTime) >= 60) ? 1 : ($failedAttempts + 1);
                $_SESSION['login_fail_time']  = time();

                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}

$page_title = "Login to RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-logo justify-content-center mb-2" style="justify-content: center;">
                <div class="logo-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
            </div>
            <h2>Sign In to RentNear</h2>
            <p class="text-muted" style="font-size: 0.9rem; color: var(--text-muted);">Access your Owner portal or Renter account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem; padding: 0.75rem 1rem; text-align: center; justify-content: center; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" method="POST">
            <div class="form-group">
                <label for="loginEmail"><i class="fa-solid fa-envelope me-1"></i> Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" id="loginEmail" class="form-control" placeholder="name@example.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g. user@domain.com)" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label for="loginPassword" style="margin-bottom: 0;"><i class="fa-solid fa-lock me-1"></i> Password <span class="text-danger">*</span></label>
                    <a href="forgot-password.php" style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <!-- 1-Click Demo Buttons for Fast Evaluation -->
        <div class="demo-account-box">
            <h6><i class="fa-solid fa-wand-magic-sparkles"></i> 1-Click Demo Credentials:</h6>
            <div class="demo-btn-group">
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillDemoCredentials('owner')">
                    <i class="fa-solid fa-key"></i> Fill Owner
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillDemoCredentials('renter')">
                    <i class="fa-solid fa-key"></i> Fill Renter
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillDemoCredentials('admin')">
                    <i class="fa-solid fa-key"></i> Fill Admin
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account? <a href="register.php" style="font-weight: 700;">Sign up for free</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
