<?php
// profile.php - User Profile & Profile Picture Settings for Owners, Renters, and Admins
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

// 1. Handle Profile Picture (Avatar) Upload or Preset Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    $avatarUpdated = false;
    $newAvatarPath = '';

    // Check if preset avatar was selected
    if (!empty($_POST['preset_avatar'])) {
        $newAvatarPath = sanitize($_POST['preset_avatar']);
        $avatarUpdated = true;
    }
    // Check if user uploaded a file
    elseif (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $infoError = 'Invalid image format. Please upload JPG, PNG, or WEBP.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $infoError = 'Image size must be under 5MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'avatar_' . $role . '_' . $userId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $newAvatarPath = 'uploads/avatars/' . $fileName;
                $avatarUpdated = true;
            } else {
                $infoError = 'Failed to save uploaded picture. Please try again.';
            }
        }
    }

    if ($avatarUpdated && !empty($newAvatarPath)) {
        $upAvStmt = $pdo->prepare("UPDATE $table SET avatar = :avatar WHERE id = :id");
        $upAvStmt->execute([':avatar' => $newAvatarPath, ':id' => $userId]);
        
        $_SESSION['user_avatar'] = $newAvatarPath;
        set_flash_message('success', 'Profile picture updated successfully!');
        header("Location: profile.php");
        exit;
    }
}

// 2. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_info'])) {
    $name  = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $extra1 = sanitize($_POST['extra1'] ?? ''); // Owner: address, Renter: occupation
    $extra2 = sanitize($_POST['extra2'] ?? ''); // Owner: city, Renter: preferred_city

    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (empty($name) || empty($email) || empty($phone)) {
        $infoError = 'Please provide your Full Name, Email, and Phone Number.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $infoError = 'Please enter a valid email address (e.g. abc@gah.com).';
    } elseif (strlen($phone) !== 10 || !preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $infoError = 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
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
                $permAddress   = sanitize($_POST['permanent_address'] ?? '');
                $guardianName  = sanitize($_POST['guardian_name'] ?? '');
                $emerPhone     = sanitize($_POST['emergency_phone'] ?? '');

                $updateStmt = $pdo->prepare("
                    UPDATE renters 
                    SET name = :name, email = :email, phone = :phone, occupation = :occ, preferred_city = :pcity,
                        permanent_address = :paddr, guardian_name = :gname, emergency_phone = :ephone
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':name'   => $name,
                    ':email'  => $email,
                    ':phone'  => $phone,
                    ':occ'    => $extra1,
                    ':pcity'  => $extra2,
                    ':paddr'  => $permAddress,
                    ':gname'  => $guardianName,
                    ':ephone' => $emerPhone,
                    ':id'     => $userId
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

// 3. Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passError = 'Please fill out all password fields.';
    } elseif (!password_verify($currentPass, $user['password'])) {
        $passError = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 8) {
        $passError = 'New password must be at least 8 characters long.';
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

// Stats for dashboard pill
$totalListed = 0;
$totalSaved = 0;
if ($role === 'owner') {
    $pStmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?");
    $pStmt->execute([$userId]);
    $totalListed = (int)$pStmt->fetchColumn();
} elseif ($role === 'renter') {
    $fStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE renter_id = ?");
    $fStmt->execute([$userId]);
    $totalSaved = (int)$fStmt->fetchColumn();
}

$page_title = "Edit Profile - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        
        <!-- Profile Header Banner with Avatar -->
        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); color: #fff; border-radius: var(--radius-xl); padding: 2rem 2.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                
                <!-- Avatar with Camera Button -->
                <div style="position: relative;">
                    <div style="width: 82px; height: 82px; border-radius: 50%; overflow: hidden; border: 3.5px solid rgba(255,255,255,0.6); box-shadow: var(--shadow-md); display: flex; align-items: center; justify-content: center; background: #fff;">
                        <?php echo render_user_avatar_img($user['avatar'] ?? '', $user['name'], 82); ?>
                    </div>
                    <button type="button" onclick="document.getElementById('avatarModal').style.display='flex'" style="position: absolute; bottom: -2px; right: -2px; background: #4f46e5; color: #fff; border: 2px solid #fff; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.78rem; box-shadow: 0 2px 6px rgba(0,0,0,0.3);" title="Change Profile Picture">
                        <i class="fa-solid fa-camera"></i>
                    </button>
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
                            } elseif ($role === 'renter') {
                                if (!empty($user['is_verified']) && (int)$user['is_verified'] === 1) {
                                    echo render_renter_verified_badge(false, 20);
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
                                    <i class="fa-solid fa-circle-check"></i> Get Golden Tick
                                </a>
                            <?php endif; ?>
                        <?php elseif ($role === 'admin'): ?>
                            <span class="badge badge-danger"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="fa-solid fa-user"></i> Tenant / Renter</span>
                            <?php if (!empty($user['is_verified']) && (int)$user['is_verified'] === 1): ?>
                                <a href="verify-renter.php" class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #86efac; text-decoration: none; font-weight: 800;">
                                    <i class="fa-solid fa-shield-check"></i> 🛡️ DigiLocker Verified
                                </a>
                            <?php else: ?>
                                <a href="verify-renter.php" class="badge" style="background: #16a34a; color: #fff; text-decoration: none; font-weight: 800;">
                                    <i class="fa-solid fa-id-card"></i> Verify DigiLocker KYC
                                </a>
                            <?php endif; ?>
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

        <!-- Main Form Cards Grid -->
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
                        <input type="email" name="email" id="profEmail" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g. abc@gah.com)" required>
                    </div>

                    <div class="form-group">
                        <label for="profPhone">Mobile Number (10 Digits) <span class="text-danger">*</span></label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="background: var(--bg-alt); border: 1.5px solid var(--border-color); padding: 0.65rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.92rem; color: var(--dark);">+91</span>
                            <input type="tel" name="phone" id="profPhone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" maxlength="10" minlength="10" pattern="[6-9][0-9]{9}" title="Enter exact 10-digit Indian mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                        </div>
                    </div>

                    <?php if ($role === 'owner'): ?>
                        <div class="form-group">
                            <label><i class="fa-solid fa-house-chimney me-1"></i> Owner Operating Address</label>
                            <input type="text" name="extra1" class="form-control" placeholder="e.g. Main Market, Station Road" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-city me-1"></i> Primary Operating City / District</label>
                            <input type="text" name="extra2" class="form-control" list="profileCityDatalist" placeholder="e.g. Jamui, Patna, Delhi, Pune" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" autocomplete="off">
                            <?php echo render_indian_city_datalist('profileCityDatalist'); ?>
                        </div>
                    <?php elseif ($role === 'renter'): ?>
                        <div class="form-group">
                            <label><i class="fa-solid fa-graduation-cap me-1"></i> Occupation / College / Workplace</label>
                            <input type="text" name="extra1" class="form-control" placeholder="e.g. Student at KKM College, Software Engineer" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-city me-1"></i> Preferred Rental City / District</label>
                            <input type="text" name="extra2" class="form-control" list="profileCityDatalist" placeholder="e.g. Jamui, Patna, Pune, Bengaluru" value="<?php echo htmlspecialchars($user['preferred_city'] ?? ''); ?>" autocomplete="off">
                            <?php echo render_indian_city_datalist('profileCityDatalist'); ?>
                        </div>

                        <div class="form-group">
                            <label><i class="fa-solid fa-house-user me-1"></i> Permanent Home Address (Native Town / Village)</label>
                            <input type="text" name="permanent_address" class="form-control" placeholder="e.g. Vill: Sikandra, Dist: Jamui, Bihar - 811315" value="<?php echo htmlspecialchars($user['permanent_address'] ?? ''); ?>">
                            <small style="font-size: 0.74rem; color: var(--text-muted);">Auto-shared with landlord upon booking so you never have to do manual paperwork.</small>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-user-shield me-1"></i> Father / Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-control" placeholder="e.g. Ram Prasad Sharma" value="<?php echo htmlspecialchars($user['guardian_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-phone-volume me-1"></i> Family / Emergency Phone</label>
                                <input type="tel" name="emergency_phone" class="form-control" placeholder="9876543210" maxlength="10" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                            </div>
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
                        <div style="position: relative;">
                            <input type="password" name="current_password" id="currentPass" class="form-control" placeholder="••••••••" required style="padding-right: 2.5rem;">
                            <button type="button" onclick="togglePasswordVisibility('currentPass', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Show/Hide Password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPass">New Password <span class="text-danger">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 8 characters" minlength="8" required style="padding-right: 2.5rem;">
                            <button type="button" onclick="togglePasswordVisibility('newPass', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Show/Hide Password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPass">Confirm New Password <span class="text-danger">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Re-type new password" minlength="8" required style="padding-right: 2.5rem;">
                            <button type="button" onclick="togglePasswordVisibility('confirmPass', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Show/Hide Password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </form>

                <!-- Account Info Box -->
                <div style="margin-top: 1.5rem; background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); font-size: 0.8rem; color: var(--dark-muted);">
                    <div><strong>Member Since:</strong> <?php echo date('d M Y', strtotime($user['created_at'] ?? 'now')); ?></div>
                    <div class="mt-1"><strong>Database Table:</strong> <code><?php echo $table; ?></code> (ID: #<?php echo $user['id']; ?>)</div>
                </div>

                <!-- Danger Zone (Owners & Renters only) -->
                <?php if ($role !== 'admin'): ?>
                    <div style="margin-top: 1.5rem; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--danger); display: block; margin-bottom: 0.35rem;">Danger Zone:</span>
                        <button type="button" class="btn btn-danger btn-sm" style="width: 100%; font-size: 0.8rem;" onclick="document.getElementById('deleteAccountModal').style.display='flex'">
                            <i class="fa-solid fa-trash-can"></i> Delete My Account & Data
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<!-- Avatar Upload & Preset Chooser Modal -->
<div id="avatarModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; border-radius: var(--radius-xl); max-width: 500px; width: 100%; padding: 2rem; box-shadow: var(--shadow-xl); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-camera text-primary"></i> Change Profile Picture
            </h3>
            <button type="button" onclick="document.getElementById('avatarModal').style.display='none'" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form 1: Upload from Device -->
        <form action="profile.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: 12px; border: 1.5px dashed #cbd5e1;">
            <input type="hidden" name="update_avatar" value="1">
            <label style="font-weight: 700; font-size: 0.88rem; display: block; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-upload me-1 text-primary"></i> Upload from Device (JPG, PNG, WEBP):
            </label>
            <input type="file" name="avatar_file" accept="image/*" class="form-control mb-3" required>
            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; font-weight: 700;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Save Picture
            </button>
        </form>

        <!-- Form 2: 1-Click Preset Avatars -->
        <div>
            <label style="font-weight: 700; font-size: 0.88rem; display: block; margin-bottom: 0.75rem; color: #334155;">
                Or Pick a 1-Click Avatar Style:
            </label>
            <form action="profile.php" method="POST">
                <input type="hidden" name="update_avatar" value="1">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.25rem;">
                    <?php
                    $presets = [
                        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80'
                    ];
                    foreach ($presets as $idx => $pUrl):
                    ?>
                        <label style="cursor: pointer; text-align: center; display: block;">
                            <input type="radio" name="preset_avatar" value="<?php echo htmlspecialchars($pUrl); ?>" style="display: none;" onchange="this.form.submit()">
                            <img src="<?php echo htmlspecialchars($pUrl); ?>" style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2.5px solid #e2e8f0; transition: all 0.2s ease; box-shadow: var(--shadow-xs);" onmouseover="this.style.borderColor='#4f46e5'; this.style.transform='scale(1.08)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)'">
                        </label>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <div style="text-align: right;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('avatarModal').style.display='none'">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Modal: Delete Account Confirmation -->
<div id="deleteAccountModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; border-radius: var(--radius-xl); max-width: 440px; width: 100%; padding: 2rem; box-shadow: var(--shadow-xl); text-align: center;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        
        <h3 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Delete Account Permanently?</h3>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
            Are you sure you want to delete your <strong><?php echo ucfirst($role); ?></strong> account (<code><?php echo htmlspecialchars($user['email']); ?></code>)? All your properties, saved listings, and inquiries will be permanently removed.
        </p>

        <form action="delete-user.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $userId; ?>">
            <input type="hidden" name="type" value="<?php echo $role; ?>">
            <input type="hidden" name="confirm_self_delete" value="1">

            <div style="display: flex; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('deleteAccountModal').style.display='none';">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger" style="flex: 1; font-weight: 800;">
                    <i class="fa-solid fa-trash-can"></i> Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .container > div > div[style*="grid-template-columns: 1.2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>