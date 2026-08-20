<?php
// admin-dashboard.php - Platform Administrator Panel with Separate Owners & Renters Tables
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

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
    SELECT p.*, o.name as owner_name, o.email as owner_email 
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    ORDER BY p.id DESC
");
$allProperties = $stmtAllProps->fetchAll();

// Fetch All Owners from dedicated 'owners' table
$stmtOwners = $pdo->query("
    SELECT o.*, (SELECT COUNT(*) FROM properties WHERE owner_id = o.id) as props_count 
    FROM owners o 
    ORDER BY o.id DESC
");
$allOwners = $stmtOwners->fetchAll();

// Fetch All Renters from dedicated 'renters' table
$stmtRenters = $pdo->query("
    SELECT r.*, (SELECT COUNT(*) FROM favorites WHERE renter_id = r.id) as saved_count 
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
            <p style="color: var(--text-muted);">Dedicated backend database for Owners (`owners`) and Tenants (`renters`).</p>
        </div>
        <a href="profile.php" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-grid-stats">
        <div class="stat-card">
            <div class="stat-card-info">
                <p>Total Revenue (₹99 Upgrades)</p>
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
                <p>Database Records</p>
                <h4 style="font-size: 1.3rem; margin-top: 0.3rem;">
                    <span style="color: #2563eb;"><?php echo $totalOwners; ?> Owners</span> / <span style="color: #10b981;"><?php echo $totalRenters; ?> Renters</span>
                </h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-database"></i></div>
        </div>
    </div>

    <!-- Recent Payments & Revenue Ledger -->
    <div id="revenueTable" style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm); scroll-margin-top: 80px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-receipt text-primary me-1"></i> Recent ₹99 Payment Transactions</h3>
            <span class="badge badge-success">Mock Gateway Active</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Property Owner</th>
                        <th>Boosted Property</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No payment transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $pay): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($pay['transaction_id']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pay['owner_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($pay['owner_email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($pay['property_title']); ?></td>
                                <td><strong style="color: var(--primary);"><?php echo format_inr($pay['amount']); ?></strong></td>
                                <td><span class="badge badge-role"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
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
        <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;"><i class="fa-solid fa-list-check text-primary me-1"></i> Manage All Properties (<?php echo count($allProperties); ?>)</h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Property</th>
                        <th>Owner</th>
                        <th>City</th>
                        <th>Rent</th>
                        <th>Premium</th>
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
                                <?php echo htmlspecialchars($prop['owner_name']); ?><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($prop['owner_email']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($prop['city']); ?></td>
                            <td><strong><?php echo format_inr($prop['price']); ?></strong></td>
                            <td>
                                <?php if ($prop['is_premium']): ?>
                                    <span class="badge badge-premium">⭐ Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="background: var(--bg-alt); color: var(--text-muted);">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="admin-dashboard.php?toggle_premium=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="Toggle Premium Status">
                                    <i class="fa-solid fa-crown"></i>
                                </a>
                                <a href="edit-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="delete-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this property permanently?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. Dedicated Owners Table Section -->
    <div id="usersTable" style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm); scroll-margin-top: 80px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e1b4b;">
                <i class="fa-solid fa-house-chimney-user text-primary me-1"></i> Registered Property Owners (Database: <code>owners</code> table)
            </h3>
            <span class="badge badge-role"><?php echo count($allOwners); ?> Owners</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Owner ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Operating City</th>
                        <th>Listings</th>
                        <th>Registered Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allOwners as $o): ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($o['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($o['email']); ?></td>
                            <td><?php echo htmlspecialchars($o['phone']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($o['city']) ? $o['city'] : 'Pan India'); ?></td>
                            <td><span class="badge badge-info"><?php echo $o['props_count']; ?> properties</span></td>
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

    <!-- 2. Dedicated Renters Table Section -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #065f46;">
                <i class="fa-solid fa-user-group text-success me-1"></i> Registered Tenants / Renters (Database: <code>renters</code> table)
            </h3>
            <span class="badge badge-success"><?php echo count($allRenters); ?> Renters</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Renter ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Occupation</th>
                        <th>Preferred City</th>
                        <th>Saved Items</th>
                        <th>Registered Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allRenters as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['phone']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($r['occupation']) ? $r['occupation'] : 'Professional'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($r['preferred_city']) ? $r['preferred_city'] : 'Bengaluru'); ?></td>
                            <td><span class="badge badge-warning"><?php echo $r['saved_count']; ?> saved</span></td>
                            <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <a href="delete-user.php?role=renter&id=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" title="Permanently Delete Renter Account" onclick="return confirm('⚠️ Delete renter <?php echo addslashes($r['name']); ?> permanently?');">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
