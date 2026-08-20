<?php
// profile.php - User Profile Edit for Separate Owners, Renters, and Admins
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$userSession = current_user();
$role = $userSession['role'];
$userId = (int)$userSession['id'];

// Determine table name based on role
$table = 'renters';
if ($role === 'owner') $table = 'owners';
elseif ($role === 'admin') $table = 'admins';

// Fetch fresh user data from their respective table
$stmt = $pdo->prepare("SELECT * FROM $table WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$infoError = '';
$passError = '';

// 1. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_info'])) {
    $name  = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $extra1 = sanitize($_POST['extra1'] ?? ''); // Owner: address, Renter: occupation
    $extra2 = sanitize($_POST['extra2'] ?? ''); // Owner: city, Renter: preferred_city

    if (empty($name) || empty($email) || empty($phone)) {
        $infoError = 'Please provide your Full Name, Email, and Phone Number.';
    } else {
        // Check if email taken by someone else in the same table
        $checkStmt = $pdo->prepare("SELECT id FROM $table WHERE LOWER(email) = LOWER(:email) AND id != :id");
        $checkStmt->execute([':email' => $email, ':id' => $userId]);
        
        if ($checkStmt->fetch()) {
            $infoError = 'This email address is already registered.';
        } else {
            if ($role === 'owner') {
                $updateStmt = $pdo->prepare("
                    UPDATE owners 
                    SET name = :name, email = :email, phone = :phone, address = :address, city = :city
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':name'    => $name,
                    ':email'   => $email,
                    ':phone'   => $phone,
                    ':address' => $extra1,
                    ':city'    => $extra2,
                    ':id'      => $userId
                ]);
            } elseif ($role === 'renter') {
                $updateStmt = $pdo->prepare("
                    UPDATE renters 
                    SET name = :name, email = :email, phone = :phone, occupation = :occ, preferred_city = :pcity
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':occ'   => $extra1,
                    ':pcity' => $extra2,
                    ':id'    => $userId
                ]);
            } else {
                $updateStmt = $pdo->prepare("UPDATE admins SET name = :name, email = :email, phone = :phone WHERE id = :id");
                $updateStmt->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':id' => $userId]);
            }

            // Update Session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;

            set_flash_message('success', 'Profile details updated in ' . ucfirst($role) . ' database!');
            header("Location: profile.php");
            exit;
        }
    }
}

// 2. Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passError = 'Please fill out all password fields.';
    } elseif (!password_verify($currentPass, $user['password'])) {
        $passError = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $passError = 'New password must be at least 6 characters long.';
    } elseif ($newPass !== $confirmPass) {
        $passError = 'New password and confirmation do not match.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $upPassStmt = $pdo->prepare("UPDATE $table SET password = :password WHERE id = :id");
        $upPassStmt->execute([':password' => $hashed, ':id' => $userId]);

        set_flash_message('success', 'Password updated successfully!');
        header("Location: profile.php");
        exit;
    }
}

// Fetch stats
$totalListed = 0;
$totalSaved = 0;
if ($role === 'owner') {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?");
    $stmtCount->execute([$userId]);
    $totalListed = (int)$stmtCount->fetchColumn();
} elseif ($role === 'renter') {
    $stmtSaved = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE renter_id = ?");
    $stmtSaved->execute([$userId]);
    $totalSaved = (int)$stmtSaved->fetchColumn();
}

$page_title = "Edit Profile - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        
        <!-- Profile Header Banner -->
        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); color: #fff; border-radius: var(--radius-xl); padding: 2rem 2.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: #ffffff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; border: 3px solid rgba(255,255,255,0.4); box-shadow: var(--shadow-md);">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.25rem; flex-wrap: wrap;">
                        <h2 style="color: #fff; font-size: 1.6rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 4px;">
                            <?php echo htmlspecialchars($user['name']); ?>
                            <?php 
                            if ($role === 'owner') {
                                $oCheck = $pdo->prepare("SELECT is_verified FROM owners WHERE id = ?");
                                $oCheck->execute([$userId]);
                                $isOwnerVer = (int)$oCheck->fetchColumn() === 1;
                                if ($isOwnerVer) {
                                    echo render_verified_badge(false, 20);
                                }
                            }
                            ?>
                        </h2>
                        <?php if ($role === 'owner'): ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-house-chimney-user"></i> Property Owner</span>
                            <?php if (!empty($isOwnerVer)): ?>
                                <a href="verify-receipt.php" class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; text-decoration: none; font-weight: 800;">
                                    <i class="fa-solid fa-certificate"></i> ⭐ Gold Verified
                                </a>
                            <?php else: ?>
                                <a href="verify-owner.php" class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; text-decoration: none; font-weight: 800;">
                                    <i class="fa-solid fa-circle-check"></i> Get Golden Tick (₹199)
                                </a>
                            <?php endif; ?>
                        <?php elseif ($role === 'admin'): ?>
                            <span class="badge badge-danger"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="fa-solid fa-user"></i> Tenant / Renter</span>
                        <?php endif; ?>
                    </div>
                    <p style="color: #c7d2fe; font-size: 0.9rem; margin: 0;">
                        <i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?> &nbsp;•&nbsp; 
                        <i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($user['phone']); ?>
                    </p>
                </div>
            </div>

            <div>
                <?php if ($role === 'owner'): ?>
                    <a href="owner-dashboard.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard (<?php echo $totalListed; ?> Listings)
                    </a>
                <?php elseif ($role === 'renter'): ?>
                    <a href="renter-dashboard.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fa-solid fa-heart"></i> Saved (<?php echo $totalSaved; ?>)
                    </a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="admin-dashboard.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fa-solid fa-gear"></i> Admin Panel
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; align-items: start;">
            
            <!-- 1. Personal Information Card -->
            <div style="background: #fff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 1rem;">
                    <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0;">Personal Details (Database: `<?php echo $table; ?>`)</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Update details in your dedicated table.</p>
                    </div>
                </div>

                <?php if (!empty($infoError)): ?>
                    <div class="alert alert-danger" style="font-size: 0.85rem; padding: 0.75rem 1rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($infoError); ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_profile_info" value="1">

                    <div class="form-group">
                        <label for="profName">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="profName" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="profEmail">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="profEmail" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="profPhone">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="profPhone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>

                    <?php if ($role === 'owner'): ?>
                        <div class="form-group">
                            <label>Owner Operating Address</label>
                            <input type="text" name="extra1" class="form-control" placeholder="e.g. Main Market, Station Road" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-city me-1"></i> Primary Operating City / District</label>
                            <input type="text" name="extra2" class="form-control" list="profileCityDatalist" placeholder="e.g. Jamui, Patna, Delhi, Pune" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" autocomplete="off">
                            <?php echo render_indian_city_datalist('profileCityDatalist'); ?>
                        </div>
                    <?php elseif ($role === 'renter'): ?>
                        <div class="form-group">
                            <label>Occupation / Profession</label>
                            <input type="text" name="extra1" class="form-control" placeholder="e.g. Software Engineer, Doctor, Student" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-city me-1"></i> Preferred Rental City / District</label>
                            <input type="text" name="extra2" class="form-control" list="profileCityDatalist" placeholder="e.g. Jamui, Patna, Pune, Bengaluru" value="<?php echo htmlspecialchars($user['preferred_city'] ?? ''); ?>" autocomplete="off">
                            <?php echo render_indian_city_datalist('profileCityDatalist'); ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- 2. Security & Password Update Card -->
            <div style="background: #fff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 1rem;">
                    <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0;">Change Password</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Keep your credentials secure.</p>
                    </div>
                </div>

                <?php if (!empty($passError)): ?>
                    <div class="alert alert-danger" style="font-size: 0.85rem; padding: 0.75rem 1rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($passError); ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_password" value="1">

                    <div class="form-group">
                        <label for="currentPass">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" id="currentPass" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="newPass">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmPass">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Re-type new password" required>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </form>

                <!-- Account Info Box -->
                <div style="margin-top: 1.5rem; background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); font-size: 0.8rem; color: var(--dark-muted);">
                    <div><strong>Member Since:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                    <div class="mt-1"><strong>Database Table:</strong> <code><?php echo $table; ?></code> (ID: #<?php echo $user['id']; ?>)</div>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
@media (max-width: 768px) {
    .container > div > div[style*="grid-template-columns: 1.2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
