<?php
// admin-dashboard.php - Platform Administrator Panel with Separate Owners & Renters Tables
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Handle direct logout action from admin dashboard
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_unset();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: login.php?logged_out=1");
    exit;
}

require_admin();
$user = current_user();

// Handle Toggle Premium by Admin
if (isset($_GET['toggle_premium']) && is_numeric($_GET['toggle_premium'])) {
    $propId = (int)$_GET['toggle_premium'];
    $stmtP = $pdo->prepare("SELECT is_premium FROM properties WHERE id = ?");
    $stmtP->execute([$propId]);
    $curr = (int)$stmtP->fetchColumn();
    $newStatus = $curr ? 0 : 1;
    $pdo->prepare("UPDATE properties SET is_premium = ? WHERE id = ?")->execute([$newStatus, $propId]);
    set_flash_message('success', 'Property premium status toggled successfully.');
    header("Location: admin-dashboard.php");
    exit;
}

// Handle Toggle Owner Golden Tick Verification by Admin
if (isset($_GET['toggle_owner_verify']) && is_numeric($_GET['toggle_owner_verify'])) {
    $ownerId = (int)$_GET['toggle_owner_verify'];
    $stmtO = $pdo->prepare("SELECT is_verified FROM owners WHERE id = ?");
    $stmtO->execute([$ownerId]);
    $currVer = (int)$stmtO->fetchColumn();
    $newVer = $currVer ? 0 : 1;
    $verTime = $newVer ? date('Y-m-d H:i:s') : null;
    $pdo->prepare("UPDATE owners SET is_verified = ?, verified_at = ? WHERE id = ?")->execute([$newVer, $verTime, $ownerId]);
    set_flash_message('success', 'Owner Golden Tick status updated to ' . ($newVer ? '⭐ Gold Verified' : 'Standard Unverified') . '!');
    header("Location: admin-dashboard.php#usersTable");
    exit;
}

// Handle Toggle Renter DigiLocker KYC Verification by Admin
if (isset($_GET['toggle_renter_verify']) && is_numeric($_GET['toggle_renter_verify'])) {
    $renterId = (int)$_GET['toggle_renter_verify'];
    $stmtR = $pdo->prepare("SELECT is_verified FROM renters WHERE id = ?");
    $stmtR->execute([$renterId]);
    $currVer = (int)$stmtR->fetchColumn();
    $newVer = $currVer ? 0 : 1;
    $verTime = $newVer ? date('Y-m-d H:i:s') : null;
    $mockAadhaar = $newVer ? 'XXXX-XXXX-' . rand(1000, 9999) : null;
    $docType = $newVer ? 'DigiLocker Aadhaar (Admin Verified)' : null;
    $pdo->prepare("UPDATE renters SET is_verified = ?, verified_at = ?, digilocker_aadhaar = ?, document_type = ? WHERE id = ?")->execute([$newVer, $verTime, $mockAadhaar, $docType, $renterId]);
    set_flash_message('success', 'Renter DigiLocker status updated to ' . ($newVer ? '🛡️ DigiLocker Verified' : 'Standard Unverified') . '!');
    header("Location: admin-dashboard.php#usersTable");
    exit;
}

// Fetch Platform Stats from Separate Tables
$totalOwners = (int)$pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
$totalRenters = (int)$pdo->query("SELECT COUNT(*) FROM renters")->fetchColumn();
$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$totalUsers = $totalOwners + $totalRenters;
$totalProperties = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalPremium = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE is_premium = 1")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'SUCCESS'")->fetchColumn();

// Fetch Recent Transactions
$stmtPayments = $pdo->query("
    SELECT pay.*, o.name as owner_name, o.email as owner_email, p.title as property_title 
    FROM payments pay 
    JOIN owners o ON pay.owner_id = o.id 
    JOIN properties p ON pay.property_id = p.id 
    ORDER BY pay.id DESC 
    LIMIT 10
");
$recentPayments = $stmtPayments->fetchAll();

// Fetch All Properties
$stmtAllProps = $pdo->query("
    SELECT p.*, o.name as owner_name, o.email as owner_email, o.is_verified as owner_is_verified 
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    ORDER BY p.id DESC
");
$allProperties = $stmtAllProps->fetchAll();

// Fetch All Owners from dedicated 'owners' table with property stats
$stmtOwners = $pdo->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM properties WHERE owner_id = o.id) as props_count,
           (SELECT COUNT(*) FROM properties WHERE owner_id = o.id AND status = 'available') as active_props_count,
           (SELECT COUNT(*) FROM properties WHERE owner_id = o.id AND status = 'rented') as rented_props_count
    FROM owners o 
    ORDER BY o.id DESC
");
$allOwners = $stmtOwners->fetchAll();

// Fetch All Renters from dedicated 'renters' table with activity stats
$stmtRenters = $pdo->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM favorites WHERE renter_id = r.id) as saved_count,
           (SELECT COUNT(*) FROM inquiries WHERE renter_id = r.id) as inquiries_count
    FROM renters r 
    ORDER BY r.id DESC
");
$allRenters = $stmtRenters->fetchAll();

$page_title = "Admin Dashboard - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <span class="badge badge-warning mb-1"><i class="fa-solid fa-shield-halved"></i> Super Administrator</span>
            <h1 style="font-size: 2rem; font-weight: 800;">RentNear Platform Overview</h1>
            <p style="color: var(--text-muted);">Separate backend architecture for Owners (`owners`) and Tenants (`renters`).</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <a href="profile.php" class="btn btn-secondary btn-lg">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </a>
            <a href="logout.php" class="btn btn-danger btn-lg" style="background: #dc2626; border-color: #b91c1c; font-weight: 700;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-grid-stats">
        <div class="stat-card">
            <div class="stat-card-info">
                <p>Total Platform Revenue</p>
                <h4 style="color: var(--success);"><?php echo format_inr($totalRevenue); ?></h4>
            </div>
            <div class="stat-card-icon" style="background: var(--success-light); color: var(--success);"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Total Properties</p>
                <h4><?php echo $totalProperties; ?></h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-building"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>⭐ Premium Featured</p>
                <h4 style="color: #d97706;"><?php echo $totalPremium; ?></h4>
            </div>
            <div class="stat-card-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-crown"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Separated User Accounts</p>
                <h4 style="font-size: 1.25rem; margin-top: 0.3rem;">
                    <span style="color: #4f46e5;"><?php echo $totalOwners; ?> Landlords</span> &nbsp;|&nbsp; <span style="color: #059669;"><?php echo $totalRenters; ?> Tenants</span>
                </h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-database"></i></div>
        </div>
    </div>

    <!-- Recent Payments & Revenue Ledger -->
    <div id="revenueTable" style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm); scroll-margin-top: 80px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-receipt text-primary me-1"></i> Recent Payment Transactions</h3>
            <span class="badge badge-success">Mock Gateway Active</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>User / Owner</th>
                        <th>Associated Property</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No payment transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $pay): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($pay['transaction_id']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pay['owner_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($pay['owner_email']); ?></span>
                                </td>
                                <td><?php echo !empty($pay['property_title']) ? htmlspecialchars($pay['property_title']) : '<span style="color: var(--text-muted);">Account Verification</span>'; ?></td>
                                <td><strong style="color: var(--primary);"><?php echo format_inr($pay['amount']); ?></strong></td>
                                <td><span class="badge badge-role"><?php echo htmlspecialchars($pay['payment_method'] ?? 'UPI'); ?></span></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($pay['created_at'])); ?></td>
                                <td><span class="badge badge-success"><?php echo htmlspecialchars($pay['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Properties Management -->
    <div id="propertiesTable" style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm); scroll-margin-top: 80px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-list-check text-primary me-1"></i> Manage All Rental Properties (<?php echo count($allProperties); ?>)</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Full system control</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Property</th>
                        <th>Owner & Verification</th>
                        <th>City / Area</th>
                        <th>Rent</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProperties as $prop): ?>
                        <tr>
                            <td>#<?php echo $prop['id']; ?></td>
                            <td>
                                <strong><a href="property-details.php?id=<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['title']); ?></a></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($prop['property_type']); ?> • <?php echo htmlspecialchars($prop['furnishing']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prop['owner_name']); ?></strong>
                                <?php if (!empty($prop['owner_is_verified']) && (int)$prop['owner_is_verified'] === 1) echo render_verified_badge(false, 14); ?>
                                <br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($prop['owner_email']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prop['city']); ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($prop['location']); ?></span>
                            </td>
                            <td><strong><?php echo format_inr($prop['price']); ?></strong>/mo</td>
                            <td>
                                <?php if ($prop['status'] === 'available'): ?>
                                    <span class="badge badge-success" style="font-size: 0.72rem;">🟢 Available</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-size: 0.72rem;">🔴 Rented Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prop['is_premium']): ?>
                                    <span class="badge badge-premium">⭐ Featured</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="background: var(--bg-alt); color: var(--text-muted);">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.25rem;">
                                    <a href="admin-dashboard.php?toggle_premium=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="Toggle Premium Status">
                                        <i class="fa-solid fa-crown"></i>
                                    </a>
                                    <a href="edit-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="delete-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this property permanently?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- DEDICATED SEPARATE USER MANAGEMENT SECTION -->
    <!-- ========================================== -->
    <div id="usersTable" style="scroll-margin-top: 80px;">
        
        <!-- User Type Switcher Tabs -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <button type="button" id="tabBtnOwners" class="btn btn-primary" onclick="switchUserTab('owners')" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; padding: 0.75rem 1.5rem; border-radius: var(--radius-lg);">
                <i class="fa-solid fa-house-chimney-user"></i> 
                <span>Property Landlords / Owners</span>
                <span class="badge" style="background: rgba(255,255,255,0.25); color: #fff; margin-left: 4px;"><?php echo count($allOwners); ?></span>
            </button>

            <button type="button" id="tabBtnRenters" class="btn btn-secondary" onclick="switchUserTab('renters')" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; padding: 0.75rem 1.5rem; border-radius: var(--radius-lg);">
                <i class="fa-solid fa-user-group" style="color: #059669;"></i> 
                <span>Tenants / Renters</span>
                <span class="badge badge-success" style="margin-left: 4px;"><?php echo count($allRenters); ?></span>
            </button>
        </div>

        <!-- 1. Dedicated PROPERTY OWNERS Panel (`owners` table) -->
        <div id="ownersSection" style="background: #fff; border-radius: var(--radius-lg); border: 2px solid #e0e7ff; padding: 1.75rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 42px; height: 42px; border-radius: var(--radius-md); background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-house-chimney-user"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e1b4b; margin: 0;">
                            Registered Property Owners
                        </h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Database table: <code>owners</code> &bull; Manages room listings and receives tenant leads</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="badge" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                        Total: <strong><?php echo count($allOwners); ?> Landlords</strong>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Owner ID</th>
                            <th>Landlord Profile</th>
                            <th>Contact Info</th>
                            <th>Operating City / Address</th>
                            <th>Listings Count</th>
                            <th>Golden Tick Status</th>
                            <th>Registered Date</th>
                            <th style="text-align: right;">Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allOwners as $o): ?>
                            <tr>
                                <td><strong>#<?php echo $o['id']; ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                                        <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem; flex-shrink: 0; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                                            <?php echo strtoupper(substr($o['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong style="color: #0f172a; display: flex; align-items: center; gap: 3px;">
                                                <?php echo htmlspecialchars($o['name']); ?>
                                                <?php if (!empty($o['is_verified']) && (int)$o['is_verified'] === 1) echo render_verified_badge(false, 14); ?>
                                            </strong>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">Owner Account</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fa-solid fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($o['email']); ?><br>
                                    <i class="fa-solid fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($o['phone']); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(!empty($o['city']) ? $o['city'] : 'Pan India'); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars(!empty($o['address']) ? $o['address'] : 'Main City Area'); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-weight: 700;">
                                        <?php echo $o['props_count']; ?> Total
                                    </span>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                        🟢 <?php echo $o['active_props_count']; ?> Live &bull; 🔴 <?php echo $o['rented_props_count']; ?> Rented
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($o['is_verified']) && (int)$o['is_verified'] === 1): ?>
                                        <span class="badge badge-success" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 800; display: inline-flex; margin-bottom: 4px;">
                                            <i class="fa-solid fa-certificate"></i> ⭐ Gold Verified
                                        </span><br>
                                        <a href="admin-dashboard.php?toggle_owner_verify=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem; color: #dc2626;" onclick="return confirm('Revoke Golden Tick verification for this owner?');">
                                            Revoke Tick
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="font-size: 0.72rem; display: inline-flex; margin-bottom: 4px;">
                                            Standard
                                        </span><br>
                                        <a href="admin-dashboard.php?toggle_owner_verify=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem; color: #b45309; background: #fffbeb; border-color: #fde68a; font-weight: 700;" onclick="return confirm('Grant Golden Tick verification to this owner?');">
                                            ⭐ Grant Golden Tick
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="delete-user.php?role=owner&id=<?php echo $o['id']; ?>" class="btn btn-danger btn-sm" title="Permanently Delete Owner Account" onclick="return confirm('⚠️ WARNING: Delete owner <?php echo addslashes($o['name']); ?>? This will permanently erase this owner and all their properties and inquiries!');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Dedicated TENANTS / RENTERS Panel (`renters` table) -->
        <div id="rentersSection" style="background: #fff; border-radius: var(--radius-lg); border: 2px solid #dcfce7; padding: 1.75rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm); display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 42px; height: 42px; border-radius: var(--radius-md); background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: #065f46; margin: 0;">
                            Registered Tenants / Renters
                        </h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Database table: <code>renters</code> &bull; Explores rooms, saves favorites, and sends booking inquiries</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                        Total: <strong><?php echo count($allRenters); ?> Tenants</strong>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Renter ID</th>
                            <th>Tenant Profile</th>
                            <th>Contact Info</th>
                            <th>Occupation & City</th>
                            <th>Activity</th>
                            <th>DigiLocker Status</th>
                            <th>Registered Date</th>
                            <th style="text-align: right;">Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRenters as $r): ?>
                            <tr>
                                <td><strong>#<?php echo $r['id']; ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                                        <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem; flex-shrink: 0; background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                                            <?php echo strtoupper(substr($r['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong style="color: #0f172a; display: inline-flex; align-items: center; gap: 4px;">
                                                <?php echo htmlspecialchars($r['name']); ?>
                                                <?php if (!empty($r['is_verified']) && (int)$r['is_verified'] === 1) echo render_renter_verified_badge(false, 14); ?>
                                            </strong><br>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">Tenant Account</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fa-solid fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($r['email']); ?><br>
                                    <i class="fa-solid fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($r['phone']); ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 600; margin-bottom: 2px;">
                                        <?php echo htmlspecialchars(!empty($r['occupation']) ? $r['occupation'] : 'Student / Working'); ?>
                                    </span><br>
                                    <small style="color: #64748b; font-weight: 700;">📍 <?php echo htmlspecialchars(!empty($r['preferred_city']) ? $r['preferred_city'] : 'Pan India'); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-warning" style="font-weight: 700; margin-bottom: 2px; display: inline-flex;">
                                        <i class="fa-solid fa-heart me-1"></i> <?php echo $r['saved_count']; ?> Saved
                                    </span><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                                        <i class="fa-solid fa-envelope me-1"></i> <?php echo $r['inquiries_count']; ?> Inquiries
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($r['is_verified']) && (int)$r['is_verified'] === 1): ?>
                                        <a href="admin-dashboard.php?toggle_renter_verify=<?php echo $r['id']; ?>" class="badge badge-success" style="text-decoration: none; cursor: pointer; background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 4px 8px; font-weight: 800;" title="Click to Revoke DigiLocker Verification">
                                            <?php echo render_renter_verified_badge(false, 13); ?> DigiLocker Verified ✕
                                        </a><br>
                                        <small style="font-family: monospace; font-size: 0.7rem; color: #15803d;"><?php echo htmlspecialchars($r['digilocker_aadhaar'] ?? 'XXXX-XXXX-8921'); ?></small>
                                    <?php else: ?>
                                        <a href="admin-dashboard.php?toggle_renter_verify=<?php echo $r['id']; ?>" class="badge badge-secondary" style="text-decoration: none; cursor: pointer; padding: 4px 8px; font-weight: 700; background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b;" title="Click to Grant DigiLocker Verified Status">
                                            <i class="fa-solid fa-plus-circle"></i> Grant DigiLocker KYC
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="delete-user.php?role=renter&id=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" title="Permanently Delete Renter Account" onclick="return confirm('⚠️ Delete renter <?php echo addslashes($r['name']); ?> permanently? This will remove all their saved bookmarks and inquiry history.');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
function switchUserTab(tab) {
    const ownersSec = document.getElementById('ownersSection');
    const rentersSec = document.getElementById('rentersSection');
    const tabOwners = document.getElementById('tabBtnOwners');
    const tabRenters = document.getElementById('tabBtnRenters');

    if (tab === 'owners') {
        ownersSec.style.display = 'block';
        rentersSec.style.display = 'none';
        tabOwners.className = 'btn btn-primary';
        tabRenters.className = 'btn btn-secondary';
    } else {
        ownersSec.style.display = 'none';
        rentersSec.style.display = 'block';
        tabOwners.className = 'btn btn-secondary';
        tabRenters.className = 'btn btn-primary';
        tabRenters.style.background = '#059669';
        tabRenters.style.borderColor = '#047857';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
