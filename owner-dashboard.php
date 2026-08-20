<?php
// owner-dashboard.php - Property Owner Management Hub
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

// Handle 1-Click Status Toggle (Available <-> Rented)
if (isset($_GET['toggle_status']) && !empty($_GET['prop_id'])) {
    $propId = (int)$_GET['prop_id'];
    $targetStatus = $_GET['toggle_status'] === 'rented' ? 'rented' : 'available';
    $updateStmt = $pdo->prepare("UPDATE properties SET status = :status WHERE id = :id AND owner_id = :owner_id");
    $updateStmt->execute([':status' => $targetStatus, ':id' => $propId, ':owner_id' => $user['id']]);
    
    if ($targetStatus === 'rented') {
        set_flash_message('success', 'Property marked as Rented Out! It is now hidden from public search and renters.');
    } else {
        set_flash_message('success', 'Property marked as Available! It is now live for all renters and map discovery.');
    }
    header("Location: owner-dashboard.php");
    exit;
}

// Fetch Owner's Properties
$stmtProps = $pdo->prepare("
    SELECT * FROM properties 
    WHERE owner_id = :owner_id 
    ORDER BY id DESC
");
$stmtProps->execute([':owner_id' => $user['id']]);
$properties = $stmtProps->fetchAll();

// Fetch Owner's Inquiries
$stmtInquiries = $pdo->prepare("
    SELECT i.*, p.title as property_title, r.is_verified as renter_is_verified, r.digilocker_aadhaar 
    FROM inquiries i 
    JOIN properties p ON i.property_id = p.id 
    LEFT JOIN renters r ON i.renter_id = r.id
    WHERE p.owner_id = :owner_id 
    ORDER BY i.id DESC
");
$stmtInquiries->execute([':owner_id' => $user['id']]);
$inquiries = $stmtInquiries->fetchAll();

// Compute Statistics
$totalProps = count($properties);
$activeProps = 0;
$rentedProps = 0;
$premiumProps = 0;
$totalViews = 0;

foreach ($properties as $p) {
    if ($p['status'] === 'available') $activeProps++;
    else $rentedProps++;
    if ($p['is_premium'] == 1) $premiumProps++;
    $totalViews += (int)$p['views_count'];
}
$totalInquiries = count($inquiries);

// Fetch fresh owner verification status
$ownerStmt = $pdo->prepare("SELECT * FROM owners WHERE id = ?");
$ownerStmt->execute([$user['id']]);
$ownerData = $ownerStmt->fetch();
$isOwnerVerified = !empty($ownerData['is_verified']) && (int)$ownerData['is_verified'] === 1;

$page_title = "Owner Dashboard - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-role">Owner Portal</span>
                <?php if ($isOwnerVerified): ?>
                    <a href="verify-receipt.php" style="text-decoration: none;">
                        <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.74rem; font-weight: 800;">
                            <?php echo render_verified_badge(false, 14); ?> Gold Verified Landlord &bull; View Certificate
                        </span>
                    </a>
                <?php endif; ?>
            </div>
            <h1 style="font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                Welcome back, <?php echo htmlspecialchars($user['name']); ?> 
                <?php if ($isOwnerVerified) echo render_verified_badge(false, 22); ?>
            </h1>
            <p style="color: var(--text-muted); margin: 0;">Manage your rental properties, tenant inquiries, and premium featured promotions.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <?php if (!$isOwnerVerified): ?>
                <a href="verify-owner.php" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4); color: #fff; font-weight: 800;">
                    <i class="fa-solid fa-shield-check me-1"></i> Verify with DigiLocker / Golden Tick
                </a>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-secondary btn-lg">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </a>
            <a href="add-property.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-plus-circle"></i> Add New Property
            </a>
        </div>
    </div>

    <!-- Verified Owner Promotion Banner (if not verified) -->
    <?php if (!$isOwnerVerified): ?>
        <div style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1.5px solid #fde68a; border-radius: var(--radius-xl); padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.15);">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #ffffff; color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35); border: 1.5px solid #fde68a; flex-shrink: 0;">
                    <?php echo render_verified_badge(false, 28); ?>
                </div>
                <div>
                    <h4 style="font-size: 1.12rem; font-weight: 800; color: #0f172a; margin: 0 0 0.2rem 0;">
                        Landlord <span style="color: #b45309;">DigiLocker & Govt e-KYC</span> Verification
                    </h4>
                    <p style="font-size: 0.86rem; color: #78350f; margin: 0;">
                        Verify via UIDAI Aadhaar or Property Electricity Bill to display the Golden Tick, build 100% tenant trust, and get 5x more inquiries!
                    </p>
                </div>
            </div>
            <a href="verify-owner.php" class="btn btn-primary" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; font-weight: 800; padding: 0.65rem 1.4rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4); color: #fff;">
                <i class="fa-solid fa-shield-check me-1"></i> Verify with DigiLocker Now &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- Metric Cards -->
    <div class="dashboard-grid-stats">
        <div class="stat-card">
            <div class="stat-card-info">
                <p>Total Listings</p>
                <h4><?php echo $totalProps; ?></h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-house"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Active Listings</p>
                <h4 style="color: var(--success);"><?php echo $activeProps; ?></h4>
            </div>
            <div class="stat-card-icon" style="background: var(--success-light); color: var(--success);"><i class="fa-solid fa-circle-check"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>⭐ Premium Listings</p>
                <h4 style="color: #d97706;"><?php echo $premiumProps; ?></h4>
            </div>
            <div class="stat-card-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-crown"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Tenant Inquiries</p>
                <h4 style="color: var(--primary);"><?php echo $totalInquiries; ?></h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
        </div>
    </div>

    <!-- Properties Management Table Section -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-list-check me-1"></i> Your Listed Properties</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $totalProps; ?> total items</span>
        </div>

        <?php if (empty($properties)): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 1rem;">
                    <i class="fa-solid fa-house-chimney-medical"></i>
                </div>
                <h4>No properties listed yet</h4>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.25rem;">
                    Start attracting verified tenants by listing your first property in under two minutes.
                </p>
                <a href="add-property.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Post Your First Property
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>City / Area</th>
                            <th>Monthly Rent</th>
                            <th>Vacancy Status</th>
                            <th>Promotion</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $prop): ?>
                            <tr style="<?php echo $prop['status'] === 'rented' ? 'background: #fff8f8;' : ''; ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                                        <img src="<?php echo htmlspecialchars(get_property_image($prop['image'])); ?>" alt="" style="width: 54px; height: 54px; border-radius: var(--radius-sm); object-fit: cover; <?php echo $prop['status'] === 'rented' ? 'opacity: 0.7; filter: grayscale(40%);' : ''; ?>">
                                        <div>
                                            <a href="property-details.php?id=<?php echo $prop['id']; ?>" style="font-weight: 700; color: var(--dark); display: block;">
                                                <?php echo htmlspecialchars($prop['title']); ?>
                                            </a>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($prop['property_type']); ?> • <?php echo htmlspecialchars($prop['furnishing']); ?> • <i class="fa-solid fa-eye me-1"></i><?php echo $prop['views_count']; ?> views
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prop['city']); ?></strong><br>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($prop['location']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo format_inr($prop['price']); ?></strong>/mo<br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Dep: <?php echo format_inr($prop['deposit']); ?></span>
                                </td>
                                <td>
                                    <?php if ($prop['status'] === 'available'): ?>
                                        <span class="badge badge-success" style="font-weight: 700; font-size: 0.76rem; display: inline-flex; margin-bottom: 0.35rem;">
                                            <i class="fa-solid fa-circle-check"></i> Available (Live)
                                        </span><br>
                                        <a href="owner-dashboard.php?toggle_status=rented&prop_id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.2rem 0.55rem; color: #dc2626; border-color: #fecaca; background: #fff5f5;" title="Mark as rented (hidden from renters)" onclick="return confirm('Mark this property as Rented Out? It will be hidden from renters and search.');">
                                            <i class="fa-solid fa-lock"></i> Mark Rented
                                        </a>
                                    <?php else: ?>
                                        <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-weight: 700; font-size: 0.76rem; display: inline-flex; margin-bottom: 0.35rem;">
                                            <i class="fa-solid fa-circle-xmark"></i> Rented Out (Hidden)
                                        </span><br>
                                        <a href="owner-dashboard.php?toggle_status=available&prop_id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.2rem 0.55rem; color: #15803d; border-color: #bbf7d0; background: #f0fdf4;" title="Mark as available again" onclick="return confirm('Make this property Available again? It will be live for renters.');">
                                            <i class="fa-solid fa-unlock"></i> Mark Available
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($prop['is_premium']): ?>
                                        <span class="badge badge-premium">
                                            <i class="fa-solid fa-star"></i> Featured
                                        </span>
                                    <?php else: ?>
                                        <a href="payment.php?property_id=<?php echo $prop['id']; ?>" class="btn btn-premium btn-sm" style="font-size: 0.74rem; padding: 0.3rem 0.55rem;">
                                            <i class="fa-solid fa-bolt"></i> Boost ₹99
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.35rem;">
                                        <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="View Property Details">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <a href="edit-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Property">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="delete-property.php?id=<?php echo $prop['id']; ?>" class="btn btn-danger btn-sm" title="Delete Property" onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tenant Inquiries Section -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-comments me-1"></i> Tenant Inquiries Received</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $totalInquiries; ?> inquiries</span>
        </div>

        <?php if (empty($inquiries)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No inquiries received yet. Once renters message you from your listings, they will show up here.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($inquiries as $inq): 
                    $cleanPhone = preg_replace('/[^0-9]/', '', $inq['phone']);
                    $isTokenPaid = ($inq['booking_status'] ?? '') === 'token_paid';
                ?>
                    <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md); border: 1.5px solid <?php echo $isTokenPaid ? '#a7f3d0' : 'var(--border-color)'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.6rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <h4 style="font-size: 1rem; font-weight: 700; margin: 0; display: inline-flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                        <?php echo htmlspecialchars($inq['name']); ?> 
                                        <?php if (!empty($inq['renter_is_verified']) && (int)$inq['renter_is_verified'] === 1): ?>
                                            <span class="badge badge-success" style="background: #dcfce7; color: #166534; border: 1px solid #86efac; font-size: 0.68rem; font-weight: 800; padding: 2px 6px;">
                                                <?php echo render_renter_verified_badge(false, 13); ?> DigiLocker Verified
                                            </span>
                                        <?php endif; ?>
                                        <span style="font-weight: 400; font-size: 0.85rem; color: var(--text-muted);">inquired for</span> 
                                        <strong style="color: var(--primary);"><?php echo htmlspecialchars($inq['property_title']); ?></strong>
                                    </h4>
                                    <?php if ($isTokenPaid): ?>
                                        <span class="badge badge-success" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-circle-check"></i> ₹<?php echo number_format($inq['token_amount'] ?? 1000); ?> Token Paid & Reserved
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--dark-muted); flex-wrap: wrap; margin-top: 3px;">
                                    <span><i class="fa-solid fa-phone me-1"></i> <a href="tel:<?php echo htmlspecialchars($inq['phone']); ?>"><?php echo htmlspecialchars($inq['phone']); ?></a></span>
                                    <?php if (!empty($inq['email'])): ?>
                                        <span><i class="fa-solid fa-envelope me-1"></i> <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>"><?php echo htmlspecialchars($inq['email']); ?></a></span>
                                    <?php endif; ?>
                                    <?php if (!empty($inq['move_in_date'])): ?>
                                        <span><i class="fa-solid fa-calendar me-1"></i> Move-in: <?php echo date('d M Y', strtotime($inq['move_in_date'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($inq['transaction_id'])): ?>
                                        <span style="font-family: monospace; color: #4338ca; font-weight: 700;">Txn: <?php echo htmlspecialchars($inq['transaction_id']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo time_ago($inq['created_at']); ?></span>
                        </div>

                        <div style="background: #fff; padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.9rem; color: var(--dark); border-left: 3px solid <?php echo $isTokenPaid ? '#10b981' : 'var(--primary)'; ?>;">
                            "<?php echo htmlspecialchars($inq['message']); ?>"
                        </div>

                        <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="tel:<?php echo htmlspecialchars($inq['phone']); ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-phone"></i> Call Back
                                </a>
                                <?php 
                                $waReplyMsg = $isTokenPaid 
                                    ? urlencode("Hi " . $inq['name'] . ", thank you for paying the room booking token advance on RentNear for '" . $inq['property_title'] . "'! Let's schedule your move-in.") 
                                    : urlencode("Hi " . $inq['name'] . ", regarding your inquiry for '" . $inq['property_title'] . "' on RentNear, when would you like to visit?");
                                ?>
                                <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=<?php echo $waReplyMsg; ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #25D366; color: #fff; border: none;">
                                    <i class="fa-brands fa-whatsapp"></i> Reply on WhatsApp
                                </a>
                            </div>
                            <a href="delete-inquiry.php?id=<?php echo $inq['id']; ?>&from=owner" class="btn btn-danger btn-sm" style="background: var(--danger-light); color: var(--danger); border: 1px solid #fca5a5;" onclick="return confirm('Are you sure you want to delete this tenant inquiry?');" title="Delete Inquiry">
                                <i class="fa-solid fa-trash-can"></i> Delete Inquiry
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
