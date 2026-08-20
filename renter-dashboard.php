<?php
// renter-dashboard.php - Renter Hub (Saved Wishlist, Inquiries & Room Bookings)
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
           o.name as owner_name, o.phone as owner_phone, o.is_verified as owner_is_verified
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

// Fetch Room Booking Token Payments made by this Renter
$stmtBookings = $pdo->prepare("
    SELECT pay.*, p.title as property_title, p.city as property_city, p.price as property_price, p.image as property_image,
           o.name as owner_name, o.phone as owner_phone
    FROM payments pay
    JOIN properties p ON pay.property_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE pay.renter_id = :renter_id AND pay.payment_type = 'room_booking_token'
    ORDER BY pay.id DESC
");
$stmtBookings->execute([':renter_id' => $user['id']]);
$bookings = $stmtBookings->fetchAll();

$page_title = "Renter Dashboard - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <span class="badge badge-success mb-1">Renter Portal</span>
            <h1 style="font-size: 2rem; font-weight: 800;">Hello, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p style="color: var(--text-muted);">Manage your shortlisted rental homes, inquiries, and online room bookings.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="properties.php" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Explore Rooms
            </a>
            <a href="profile.php" class="btn btn-secondary">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </a>
        </div>
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

        <div class="stat-card">
            <div class="stat-card-info">
                <p>Reserved Rooms</p>
                <h4 style="color: #059669;"><?php echo count($bookings); ?></h4>
            </div>
            <div class="stat-card-icon" style="background: #ecfdf5; color: #059669;"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <!-- Booked Rooms & Payments Section -->
    <?php if (!empty($bookings)): ?>
    <div style="background: #ffffff; border-radius: var(--radius-xl); border: 2px solid #a7f3d0; padding: 1.75rem; margin-bottom: 2.5rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <span class="badge badge-success mb-1"><i class="fa-solid fa-lock"></i> Confirmed Bookings</span>
                <h3 style="font-size: 1.3rem; font-weight: 800; color: #065f46; margin: 0;">
                    Your Reserved Rooms & Token Payments
                </h3>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($bookings as $b): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 14px; padding: 1.25rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <img src="<?php echo htmlspecialchars(get_property_image($b['property_image'])); ?>" alt="" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: var(--shadow-xs);">
                        <div>
                            <h4 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">
                                <a href="property-details.php?id=<?php echo $b['property_id']; ?>"><?php echo htmlspecialchars($b['property_title']); ?></a>
                            </h4>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                                📍 <?php echo htmlspecialchars($b['property_city']); ?> &bull; Owner: <strong><?php echo htmlspecialchars($b['owner_name']); ?></strong> (<?php echo htmlspecialchars($b['owner_phone']); ?>)
                            </p>
                            <span style="font-size: 0.76rem; color: #059669; font-weight: 700;">
                                ✓ Token Advance: ₹<?php echo number_format($b['amount']); ?> Paid on <?php echo date('d M Y', strtotime($b['created_at'])); ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="booking-receipt.php?txn_id=<?php echo urlencode($b['transaction_id']); ?>" class="btn btn-primary btn-sm" style="font-weight: 700;">
                            <i class="fa-solid fa-receipt"></i> View Official Receipt
                        </a>
                        <a href="tel:<?php echo htmlspecialchars($b['owner_phone']); ?>" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-phone"></i> Call Landlord
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inquiries History Section with Direct Token Payment Buttons -->
    <div style="background: #fff; border-radius: var(--radius-xl); border: 1.5px solid var(--border-color); padding: 1.75rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0;"><i class="fa-solid fa-paper-plane text-primary me-1"></i> Your Sent Inquiries & Visits</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo count($inquiries); ?> Total Inquiries</span>
        </div>

        <?php if (empty($inquiries)): ?>
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
                <i class="fa-regular fa-comment-dots" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <h4>No inquiries sent yet</h4>
                <p style="font-size: 0.85rem;">Browse rooms and click "Submit Inquiry" to connect directly with landlords.</p>
                <a href="properties.php" class="btn btn-primary btn-sm mt-2">Explore Available Rooms</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($inquiries as $inq): 
                    $isTokenPaid = ($inq['booking_status'] ?? '') === 'token_paid';
                ?>
                    <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md); border: 1.5px solid <?php echo $isTokenPaid ? '#a7f3d0' : 'var(--border-color)'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.6rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0;">
                                        Inquiry for <a href="property-details.php?id=<?php echo $inq['property_id']; ?>" style="color: var(--primary);"><?php echo htmlspecialchars($inq['property_title']); ?></a>
                                    </h4>
                                    <?php if ($isTokenPaid): ?>
                                        <span class="badge badge-success" style="font-size: 0.72rem;">🟢 Token Paid & Reserved</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); flex-wrap: wrap; margin-top: 3px;">
                                    <span>Owner: <strong style="color: var(--dark);"><?php echo htmlspecialchars($inq['owner_name']); ?></strong> <?php if (!empty($inq['owner_is_verified']) && (int)$inq['owner_is_verified'] === 1) echo render_verified_badge(false, 13); ?> (<?php echo htmlspecialchars($inq['owner_phone']); ?>)</span>
                                    <span>Rent: <strong><?php echo format_inr($inq['property_price']); ?>/mo</strong></span>
                                    <?php if (!empty($inq['move_in_date'])): ?>
                                        <span>Target Move-in: <strong><?php echo date('d M Y', strtotime($inq['move_in_date'])); ?></strong></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo time_ago($inq['created_at']); ?></span>
                        </div>

                        <div style="background: #fff; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; color: var(--dark-muted); margin-bottom: 0.75rem;">
                            <strong>Your Message:</strong> "<?php echo htmlspecialchars($inq['message']); ?>"
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if ($isTokenPaid && !empty($inq['transaction_id'])): ?>
                                    <a href="booking-receipt.php?txn_id=<?php echo urlencode($inq['transaction_id']); ?>" class="btn btn-primary btn-sm" style="font-weight: 700;">
                                        <i class="fa-solid fa-receipt"></i> View Booking Receipt
                                    </a>
                                <?php else: ?>
                                    <!-- Prompt to Pay Token & Reserve Room -->
                                    <a href="booking-payment.php?property_id=<?php echo $inq['property_id']; ?>&inquiry_id=<?php echo $inq['id']; ?>" class="btn btn-premium btn-sm" style="font-weight: 800;">
                                        <i class="fa-solid fa-credit-card"></i> Liked Room? Pay Token & Reserve (₹1,000)
                                    </a>
                                <?php endif; ?>

                                <a href="tel:<?php echo htmlspecialchars($inq['owner_phone']); ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-phone"></i> Call Owner
                                </a>
                                <a href="property-details.php?id=<?php echo $inq['property_id']; ?>" class="btn btn-outline btn-sm">
                                    View Room
                                </a>
                            </div>

                            <a href="delete-inquiry.php?id=<?php echo $inq['id']; ?>&from=renter" class="btn btn-danger btn-sm" style="background: var(--danger-light); color: var(--danger); border: 1px solid #fca5a5;" onclick="return confirm('Are you sure you want to delete this sent inquiry record?');" title="Delete Inquiry">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Saved Wishlist Section -->
    <div style="background: #fff; border-radius: var(--radius-xl); border: 1.5px solid var(--border-color); padding: 1.75rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0;"><i class="fa-solid fa-heart text-danger me-1"></i> Saved Properties</h3>
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
                                <a href="booking-payment.php?property_id=<?php echo $prop['id']; ?>" class="btn btn-premium btn-sm" style="font-size: 0.76rem;">
                                    <i class="fa-solid fa-credit-card"></i> Pay Token
                                </a>
                                <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
