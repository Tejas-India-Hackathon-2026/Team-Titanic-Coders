<?php
// register.php - User Registration (Direct insertion into separate 'owners' or 'renters' tables)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$selectedRole = isset($_GET['role']) && $_GET['role'] === 'owner' ? 'owner' : 'renter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $role = sanitize($_POST['role'] ?? 'renter');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please re-enter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'owner') {
                $stmt = $pdo->prepare("INSERT INTO owners (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$name, $email, $phone, $hashedPassword]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO renters (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hashedPassword]);
            }

            $newUserId = $pdo->lastInsertId();

            // Auto-login newly registered user
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_is_verified'] = 0;

            set_flash_message('success', 'Account created successfully in ' . ucfirst($role) . ' database! Welcome to RentNear.');

            if ($role === 'owner') {
                header("Location: owner-dashboard.php");
            } else {
                header("Location: properties.php");
            }
            exit;
        }
    }
}

$page_title = "Create an Account - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-header">
            <div class="brand-logo justify-content-center mb-2" style="justify-content: center;">
                <div class="logo-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
            </div>
            <h2>Create Your Account</h2>
            <p class="text-muted" style="font-size: 0.9rem; color: var(--text-muted);">Join as a Property Owner or Tenant / Renter.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem; padding: 0.75rem 1rem; text-align: center; justify-content: center; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            
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
                <input type="email" name="email" id="regEmail" class="form-control" placeholder="e.g. rahul@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="regPhone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" name="phone" id="regPhone" class="form-control" placeholder="+91 98765 43210" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label for="regPass">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="regPass" class="form-control" placeholder="Min 6 chars" required>
                </div>
                <div class="form-group">
                    <label for="regConfirmPass">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" id="regConfirmPass" class="form-control" placeholder="Re-type" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i class="fa-solid fa-user-plus"></i> Complete Registration
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="login.php" style="font-weight: 700;">Sign in here</a>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    var renterCard = document.getElementById('roleCardRenter');
    var ownerCard = document.getElementById('roleCardOwner');
    var renterInput = document.getElementById('roleInputRenter');
    var ownerInput = document.getElementById('roleInputOwner');

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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
