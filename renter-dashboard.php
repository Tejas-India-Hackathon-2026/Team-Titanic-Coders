<?php
// renter-dashboard.php - Renter Hub (Saved Wishlist & Inquiries)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

// Fetch Saved / Favorite Properties
$stmtFavs = $pdo->prepare("
    SELECT p.*, o.name as owner_name, o.phone as owner_phone, f.created_at as saved_at
    FROM favorites f
    JOIN properties p ON f.property_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE f.renter_id = :renter_id
    ORDER BY f.id DESC
");
$stmtFavs->execute([':renter_id' => $user['id']]);
$savedProperties = $stmtFavs->fetchAll();

// Fetch Sent Inquiries
$stmtInquiries = $pdo->prepare("
    SELECT i.*, p.title as property_title, p.city as property_city, p.price as property_price, p.image as property_image,
           o.name as owner_name, o.phone as owner_phone
    FROM inquiries i
    JOIN properties p ON i.property_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE (i.renter_id = :renter_id OR LOWER(i.email) = LOWER(:email))
    ORDER BY i.id DESC
");
$stmtInquiries->execute([
    ':renter_id' => $user['id'],
    ':email' => $user['email']
]);
$inquiries = $stmtInquiries->fetchAll();

$page_title = "Renter Dashboard - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <span class="badge badge-success mb-1">Renter Portal</span>
            <h1 style="font-size: 2rem; font-weight: 800;">Hello, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p style="color: var(--text-muted);">Manage your shortlisted rental homes and communication with property owners.</p>
        </div>
        <a href="profile.php" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="dashboard-grid-stats">
        <div class="stat-card">
            <div class="stat-card-info">
                <p>Saved Properties</p>
                <h4 style="color: var(--danger);"><?php echo count($savedProperties); ?></h4>
            </div>
            <div class="stat-card-icon" style="background: var(--danger-light); color: var(--danger);"><i class="fa-solid fa-heart"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Inquiries Sent</p>
                <h4 style="color: var(--primary);"><?php echo count($inquiries); ?></h4>
            </div>
            <div class="stat-card-icon"><i class="fa-solid fa-paper-plane"></i></div>
        </div>
    </div>

    <!-- Saved Wishlist Section -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800;"><i class="fa-solid fa-heart text-danger me-1"></i> Saved Properties</h3>
            <a href="properties.php" class="btn btn-outline btn-sm">Explore More Homes <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <?php if (empty($savedProperties)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem;">
                <div style="font-size: 2.5rem; color: #fca5a5; margin-bottom: 0.75rem;">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h4>No saved properties yet</h4>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.25rem; font-size: 0.9rem;">
                    Click the heart icon on any property card to bookmark it for quick access later.
                </p>
                <a href="properties.php" class="btn btn-primary btn-sm">Browse Rental Listings</a>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($savedProperties as $prop): ?>
                    <div class="property-card <?php echo $prop['is_premium'] ? 'is-featured' : ''; ?>">
                        <div class="property-thumbnail">
                            <img src="<?php echo htmlspecialchars(get_property_image($prop['image'])); ?>" alt="" loading="lazy">
                            <div class="property-badges-overlay">
                                <?php if ($prop['is_premium']): ?>
                                    <span class="badge badge-premium"><i class="fa-solid fa-star"></i> Featured</span>
                                <?php endif; ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($prop['property_type']); ?></span>
                            </div>
                            <div class="property-price-tag">
                                <?php echo format_inr($prop['price']); ?><span class="period">/mo</span>
                            </div>
                            <button class="property-fav-btn active" data-property-id="<?php echo $prop['id']; ?>" title="Remove from Saved">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                        </div>
                        <div class="property-card-body">
                            <div class="property-location">
                                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($prop['location'] . ', ' . $prop['city']); ?>
                            </div>
                            <h3 class="property-title">
                                <a href="property-details.php?id=<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['title']); ?></a>
                            </h3>
                            <div class="property-card-footer mt-2">
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Owner: <?php echo htmlspecialchars($prop['owner_name']); ?></span>
                                <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inquiries History Section -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;"><i class="fa-solid fa-paper-plane text-primary me-1"></i> Your Sent Inquiries</h3>

        <?php if (empty($inquiries)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">You haven't sent any inquiries to owners yet. Contact owners directly from any property page.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($inquiries as $inq): ?>
                    <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.6rem;">
                            <div>
                                <h4 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.2rem;">
                                    Inquiry for <a href="property-details.php?id=<?php echo $inq['property_id']; ?>" style="color: var(--primary);"><?php echo htmlspecialchars($inq['property_title']); ?></a>
                                </h4>
                                <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); flex-wrap: wrap;">
                                    <span>Owner: <strong><?php echo htmlspecialchars($inq['owner_name']); ?></strong> (<?php echo htmlspecialchars($inq['owner_phone']); ?>)</span>
                                    <span>Rent: <strong><?php echo format_inr($inq['property_price']); ?>/mo</strong></span>
                                </div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo time_ago($inq['created_at']); ?></span>
                        </div>

                        <div style="background: #fff; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; color: var(--dark-muted);">
                            <strong>Your Message:</strong> "<?php echo htmlspecialchars($inq['message']); ?>"
                        </div>

                        <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="tel:<?php echo htmlspecialchars($inq['owner_phone']); ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-phone"></i> Call Owner
                                </a>
                                <a href="property-details.php?id=<?php echo $inq['property_id']; ?>" class="btn btn-outline btn-sm">
                                    View Listing Details
                                </a>
                            </div>
                            <a href="delete-inquiry.php?id=<?php echo $inq['id']; ?>&from=renter" class="btn btn-danger btn-sm" style="background: var(--danger-light); color: var(--danger); border: 1px solid #fca5a5;" onclick="return confirm('Are you sure you want to delete this sent inquiry record?');" title="Delete Inquiry">
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
