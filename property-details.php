<?php
// property-details.php - Detailed Property View
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header("Location: properties.php");
    exit;
}

// Fetch property & owner details
$stmt = $pdo->prepare("
    SELECT p.*, o.name as owner_name, o.email as owner_email, o.phone as owner_phone, o.created_at as owner_joined, o.city as owner_city
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'The requested property could not be found or has been removed.'
    ];
    header("Location: properties.php");
    exit;
}

// Increment Views Count
$pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?")->execute([$property_id]);

// Parse Amenities
$amenities = !empty($property['amenities']) ? explode(',', $property['amenities']) : [];

// Handle Inquiry Form Submission
$inquirySuccess = false;
$inquiryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $senderName = sanitize($_POST['name'] ?? '');
    $senderEmail = sanitize($_POST['email'] ?? '');
    $senderPhone = sanitize($_POST['phone'] ?? '');
    $senderMessage = sanitize($_POST['message'] ?? '');
    $moveInDate = sanitize($_POST['move_in_date'] ?? null);
    $renterId = (is_logged_in() && user_role() === 'renter') ? current_user()['id'] : null;

    if (empty($senderName) || empty($senderPhone) || empty($senderMessage)) {
        $inquiryError = "Please enter your Name, Phone Number, and Message.";
    } else {
        $inqStmt = $pdo->prepare("
            INSERT INTO inquiries (property_id, renter_id, name, email, phone, message, move_in_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')
        ");
        $inqStmt->execute([
            $property_id,
            $renterId,
            $senderName,
            $senderEmail,
            $senderPhone,
            $senderMessage,
            $moveInDate ? $moveInDate : null
        ]);
        $inquirySuccess = true;
    }
}

// Fetch similar properties in same city
$stmtSimilar = $pdo->prepare("
    SELECT * FROM properties 
    WHERE city = :city AND id != :current_id AND status = 'available'
    ORDER BY is_premium DESC, id DESC 
    LIMIT 3
");
$stmtSimilar->execute([':city' => $property['city'], ':current_id' => $property_id]);
$similarProps = $stmtSimilar->fetchAll();

$page_title = $property['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
    
    <!-- Top Breadcrumb -->
    <div style="margin-bottom: 1.25rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="index.php">Home</a> &nbsp;/&nbsp; 
        <a href="properties.php">Properties</a> &nbsp;/&nbsp; 
        <a href="properties.php?city=<?php echo urlencode($property['city']); ?>"><?php echo htmlspecialchars($property['city']); ?></a> &nbsp;/&nbsp; 
        <span><?php echo htmlspecialchars($property['title']); ?></span>
    </div>

    <!-- Title & Badges Bar -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                <?php if ($property['is_premium']): ?>
                    <span class="badge badge-premium"><i class="fa-solid fa-crown"></i> Featured Listing</span>
                <?php endif; ?>
                <span class="badge badge-info"><?php echo htmlspecialchars($property['property_type']); ?></span>
                <span class="badge badge-role"><?php echo htmlspecialchars($property['furnishing']); ?></span>
                <?php 
                $tenantPref = $property['tenant_preference'] ?? 'Bachelors Allowed';
                if (stripos($tenantPref, 'Bachelor') !== false): ?>
                    <span class="badge" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                        <i class="fa-solid fa-graduation-cap"></i> Bachelors Allowed
                    </span>
                <?php elseif (stripos($tenantPref, 'Family') !== false): ?>
                    <span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">
                        <i class="fa-solid fa-people-roof"></i> Family Only
                    </span>
                <?php elseif (stripos($tenantPref, 'Girls') !== false): ?>
                    <span class="badge" style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8;">
                        <i class="fa-solid fa-female"></i> Girls Only
                    </span>
                <?php else: ?>
                    <span class="badge" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0;">
                        <i class="fa-solid fa-users"></i> Anyone Welcome
                    </span>
                <?php endif; ?>
                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Verified Owner</span>
            </div>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--dark);"><?php echo htmlspecialchars($property['title']); ?></h1>
            <p style="color: var(--text-muted); font-size: 1rem; margin-top: 0.25rem;">
                <i class="fa-solid fa-location-dot text-danger me-1"></i> <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?>
            </p>
        </div>

        <div style="text-align: right;">
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary); line-height: 1;">
                <?php echo format_inr($property['price']); ?><span style="font-size: 1rem; font-weight: 500; color: var(--text-muted);">/month</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.3rem;">
                Security Deposit: <strong><?php echo format_inr($property['deposit']); ?></strong>
            </p>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="property-details-layout">
        
        <!-- Left Details Column -->
        <div>
            <!-- Main Hero Image -->
            <div class="details-gallery">
                <img src="<?php echo htmlspecialchars(get_property_image($property['image'])); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="details-main-img">
            </div>

            <!-- Overview Badges Grid -->
            <div class="details-card">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.25rem;">Overview & Specifications</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem; text-align: center;">
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-bed" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Bedrooms</div>
                        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo $property['bedrooms']; ?> BHK</div>
                    </div>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-bath" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Bathrooms</div>
                        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo $property['bathrooms']; ?> Baths</div>
                    </div>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-ruler-combined" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Super Built-up</div>
                        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo $property['area_sqft']; ?> sq.ft</div>
                    </div>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-couch" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Furnishing</div>
                        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo htmlspecialchars($property['furnishing']); ?></div>
                    </div>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-user-check" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Preferred For</div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: #4338ca;"><?php echo htmlspecialchars($tenantPref); ?></div>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 1rem; border-radius: var(--radius-md);">
                        <i class="fa-solid fa-clock" style="font-size: 1.5rem; color: #b45309; margin-bottom: 0.4rem;"></i>
                        <div style="font-size: 0.8rem; color: #92400e; font-weight: 700;">Stay Duration</div>
                        <div style="font-weight: 800; font-size: 0.92rem; color: #b45309;"><?php echo htmlspecialchars($property['stay_duration'] ?? '1 Month (Short Stay)'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="details-card">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Property Description</h3>
                <p style="color: var(--dark-muted); line-height: 1.8; font-size: 1rem; white-space: pre-line;">
                    <?php echo htmlspecialchars($property['description']); ?>
                </p>
            </div>

            <!-- Amenities -->
            <?php if (!empty($amenities)): ?>
            <div class="details-card">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Society & Home Amenities</h3>
                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): 
                        $amenityTrim = trim($amenity);
                        if (empty($amenityTrim)) continue;
                    ?>
                        <div class="amenity-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?php echo htmlspecialchars($amenityTrim); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Location & Neighborhood Box with Embedded Interactive Pin Map -->
            <?php 
            $propCoords = get_property_coordinates($property['location'], $property['city'], $property['id']);
            $finalLat = !empty($property['latitude']) ? (float)$property['latitude'] : $propCoords['lat'];
            $finalLng = !empty($property['longitude']) ? (float)$property['longitude'] : $propCoords['lng'];
            ?>
            <div class="details-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0;"><i class="fa-solid fa-map-location-dot text-danger me-1"></i> Interactive Location Map</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                            <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?>
                            <?php if (!empty($property['landmark'])): ?>
                                &bull; <span style="color: var(--primary); font-weight: 600;">(Near <?php echo htmlspecialchars($property['landmark']); ?>)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($finalLat . ',' . $finalLng); ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #eef2ff; color: var(--primary); border: 1px solid #c7d2fe;">
                        <i class="fa-solid fa-diamond-turn-right"></i> Open in Google Maps App
                    </a>
                </div>

                <!-- Leaflet Interactive Pin Map -->
                <div id="singlePropertyMap" style="width: 100%; height: 320px; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--border-color); margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);"></div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof L !== 'undefined') {
                        const propLat = <?php echo json_encode($finalLat); ?>;
                        const propLng = <?php echo json_encode($finalLng); ?>;
                        const propTitle = <?php echo json_encode($property['title']); ?>;
                        const propPrice = <?php echo json_encode(format_inr($property['price'])); ?>;
                        const propLoc = <?php echo json_encode($property['location'] . ', ' . $property['city']); ?>;

                        const pMap = L.map('singlePropertyMap').setView([propLat, propLng], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors | RentNear'
                        }).addTo(pMap);

                        // Custom HTML Pin Marker
                        const customIcon = L.divIcon({
                            className: 'single-prop-pin',
                            html: `
                                <div style="background: #4338ca; color: #fff; padding: 5px 10px; border-radius: 20px; font-weight: 800; font-size: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); border: 2px solid #fff; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; transform: translate(-50%, -100%);">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                                    ${propPrice}/mo
                                </div>
                            `,
                            iconSize: [0, 0],
                            iconAnchor: [0, 0]
                        });

                        const marker = L.marker([propLat, propLng], { icon: customIcon }).addTo(pMap);
                        marker.bindPopup(`
                            <div style="font-family: inherit; font-size: 12px; line-height: 1.4;">
                                <strong style="font-size: 13px; color: #0f172a;">${propTitle}</strong><br>
                                <span style="color: #16a34a; font-weight: 700;">🟢 Vacant & Ready to Move</span><br>
                                <span style="color: #64748b;">📍 ${propLoc}</span><br>
                                <span style="font-weight: 800; color: #4338ca; font-size: 14px;">${propPrice}/mo</span>
                            </div>
                        `).openPopup();
                    }
                });
                </script>

                <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.8rem; display: block;">Locality / Area</span>
                            <strong><?php echo htmlspecialchars($property['location']); ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.8rem; display: block;">City / District</span>
                            <strong><?php echo htmlspecialchars($property['city']); ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.8rem; display: block;">Landmark / Connectivity</span>
                            <strong><?php echo !empty($property['landmark']) ? htmlspecialchars($property['landmark']) : 'Main Road Access'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Owner Contact & Inquiry Sidebar -->
        <div>
            <div class="owner-card-sticky">
                <div class="owner-contact-card">
                    <div class="owner-profile-header">
                        <div class="owner-avatar-lg">
                            <?php echo strtoupper(substr($property['owner_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h4 style="font-size: 1.12rem; font-weight: 800; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 4px;">
                                <?php echo htmlspecialchars($property['owner_name']); ?>
                                <?php echo render_verified_badge(false, 19); ?>
                            </h4>
                            <p style="font-size: 0.78rem; color: #0284c7; font-weight: 700; display: flex; align-items: center; gap: 4px; margin: 0;">
                                <i class="fa-solid fa-shield-check text-primary"></i> Govt ID & Property Verified Owner
                            </p>
                        </div>
                    </div>

                    <!-- Direct Connect Buttons -->
                    <div style="display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1.25rem;">
                        <!-- Instant Reserve Room / Pay Token Button -->
                        <a href="booking-payment.php?property_id=<?php echo $property_id; ?>" class="btn btn-premium" style="width: 100%; font-size: 0.92rem; padding: 0.75rem; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);">
                            <i class="fa-solid fa-credit-card"></i> Pay Token & Reserve Room (₹1,000)
                        </a>

                        <a href="tel:<?php echo htmlspecialchars($property['owner_phone']); ?>" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-phone"></i> Call Owner: <?php echo htmlspecialchars($property['owner_phone']); ?>
                        </a>
                        
                        <?php 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $property['owner_phone']);
                        $waMsg = urlencode("Hi " . $property['owner_name'] . ", I saw your property '" . $property['title'] . "' on RentNear and would like to schedule a visit.");
                        ?>
                        <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=<?php echo $waMsg; ?>" target="_blank" class="btn btn-secondary" style="width: 100%; background: #25D366; color: #fff; border: none;">
                            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>

                    <!-- Direct Inquiry Form -->
                    <div style="border-top: 1px solid var(--border-light); padding-top: 1.25rem;">
                        <h5 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.85rem;">Send Inquiry to Owner</h5>

                        <?php if ($inquirySuccess): ?>
                            <div class="alert alert-success" style="font-size: 0.85rem; padding: 0.85rem; flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                                <div><i class="fa-solid fa-check-circle me-1"></i> <strong>Inquiry Sent to Owner!</strong></div>
                                <p style="font-size: 0.8rem; margin: 0; color: #065f46;">
                                    Liked this room? Reserve it right now by paying a small refundable token advance before someone else books it!
                                </p>
                                <a href="booking-payment.php?property_id=<?php echo $property_id; ?>" class="btn btn-primary btn-sm mt-1" style="width: 100%; font-size: 0.82rem;">
                                    <i class="fa-solid fa-credit-card"></i> Pay Token & Lock Room Now &rarr;
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($inquiryError)): ?>
                            <div class="alert alert-danger" style="font-size: 0.85rem; padding: 0.75rem;">
                                <?php echo htmlspecialchars($inquiryError); ?>
                            </div>
                        <?php endif; ?>

                        <form action="property-details.php?id=<?php echo $property_id; ?>" method="POST">
                            <input type="hidden" name="send_inquiry" value="1">
                            
                            <div class="form-group">
                                <label style="font-size: 0.8rem;">Your Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Amit Verma" required value="<?php echo is_logged_in() ? htmlspecialchars(current_user()['name']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label style="font-size: 0.8rem;">Your Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" required value="<?php echo is_logged_in() ? htmlspecialchars(current_user()['phone']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label style="font-size: 0.8rem;">Email Address (Optional)</label>
                                <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo is_logged_in() ? htmlspecialchars(current_user()['email']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label style="font-size: 0.8rem;">Expected Move-in Date</label>
                                <input type="date" name="move_in_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label style="font-size: 0.8rem;">Message to Owner</label>
                                <textarea name="message" rows="3" class="form-control" placeholder="I would like to visit this property..." required>Hi <?php echo htmlspecialchars($property['owner_name']); ?>, I am interested in renting this property. Please let me know when we can arrange a visit.</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fa-solid fa-paper-plane"></i> Submit Inquiry
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Similar Properties in this City -->
    <?php if (!empty($similarProps)): ?>
    <div style="margin-top: 4rem;">
        <div class="section-header">
            <span class="section-tag">Explore More</span>
            <h3 class="section-title" style="font-size: 1.6rem;">More Properties in <?php echo htmlspecialchars($property['city']); ?></h3>
        </div>

        <div class="properties-grid">
            <?php foreach ($similarProps as $sim): ?>
                <div class="property-card <?php echo $sim['is_premium'] ? 'is-featured' : ''; ?>">
                    <div class="property-thumbnail">
                        <img src="<?php echo htmlspecialchars(get_property_image($sim['image'])); ?>" alt="<?php echo htmlspecialchars($sim['title']); ?>">
                        <div class="property-badges-overlay">
                            <?php if ($sim['is_premium']): ?>
                                <span class="badge badge-premium"><i class="fa-solid fa-star"></i> Featured</span>
                            <?php endif; ?>
                            <span class="badge badge-info"><?php echo htmlspecialchars($sim['property_type']); ?></span>
                        </div>
                        <div class="property-price-tag">
                            <?php echo format_inr($sim['price']); ?><span class="period">/mo</span>
                        </div>
                    </div>
                    <div class="property-card-body">
                        <div class="property-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($sim['location']); ?>
                        </div>
                        <h4 class="property-title">
                            <a href="property-details.php?id=<?php echo $sim['id']; ?>"><?php echo htmlspecialchars($sim['title']); ?></a>
                        </h4>
                        <div class="property-card-footer mt-2">
                            <span class="badge badge-role"><?php echo htmlspecialchars($sim['furnishing']); ?></span>
                            <a href="property-details.php?id=<?php echo $sim['id']; ?>" class="btn btn-outline btn-sm">View <i class="fa-solid fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Mobile Fixed Bottom Action Bar (Only visible on Phones) -->
<div class="mobile-sticky-action-bar">
    <div>
        <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary);">
            <?php echo format_inr($property['price']); ?><span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">/mo</span>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($property['city']); ?></div>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="tel:<?php echo htmlspecialchars($property['owner_phone']); ?>" class="btn btn-primary btn-sm" style="padding: 0.5rem 0.9rem; font-size: 0.85rem;">
            <i class="fa-solid fa-phone"></i> Call
        </a>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $property['owner_phone']); ?>?text=<?php echo urlencode('Hi ' . $property['owner_name'] . ', I saw your listing on RentNear: ' . $property['title']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #22c55e; color: #fff; border-color: #22c55e; padding: 0.5rem 0.9rem; font-size: 0.85rem;">
            <i class="fa-brands fa-whatsapp"></i> Chat
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
