<?php
// properties.php - Properties Catalog with Multi-Facet Search and Filters
$page_title = "Explore Rental Properties";
require_once __DIR__ . '/includes/header.php';

// Retrieve Filter Inputs
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$city       = isset($_GET['city']) ? trim($_GET['city']) : '';
$type       = isset($_GET['type']) ? trim($_GET['type']) : '';
$types      = isset($_GET['types']) && is_array($_GET['types']) ? $_GET['types'] : ($type ? [$type] : []);
$furnishing = isset($_GET['furnishing']) ? trim($_GET['furnishing']) : '';
$max_price  = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;
$premium_only = isset($_GET['filter']) && $_GET['filter'] === 'premium' ? true : (isset($_GET['is_premium']) && $_GET['is_premium'] == '1');
$sort       = isset($_GET['sort']) ? trim($_GET['sort']) : 'featured';
$amenities_filter = isset($_GET['amenities']) && is_array($_GET['amenities']) ? $_GET['amenities'] : [];
$tenant_preference = isset($_GET['tenant_preference']) ? trim($_GET['tenant_preference']) : '';
$stay_duration     = isset($_GET['stay_duration']) ? trim($_GET['stay_duration']) : '';

// Construct Dynamic SQL Query
$sql = "SELECT p.*, o.name as owner_name, o.phone as owner_phone, o.is_verified as owner_is_verified 
        FROM properties p 
        JOIN owners o ON p.owner_id = o.id 
        WHERE p.status = 'available'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.title LIKE :s1 OR p.description LIKE :s2 OR p.location LIKE :s3 OR p.city LIKE :s4)";
    $searchTerm = '%' . $search . '%';
    $params[':s1'] = $searchTerm;
    $params[':s2'] = $searchTerm;
    $params[':s3'] = $searchTerm;
    $params[':s4'] = $searchTerm;
}

if (!empty($city)) {
    $sql .= " AND LOWER(p.city) = LOWER(:city)";
    $params[':city'] = $city;
}

if (!empty($types)) {
    $placeholders = [];
    foreach ($types as $idx => $t) {
        $key = ":type_" . $idx;
        $placeholders[] = $key;
        $params[$key] = $t;
    }
    $sql .= " AND p.property_type IN (" . implode(',', $placeholders) . ")";
}

if (!empty($furnishing)) {
    $sql .= " AND p.furnishing = :furnishing";
    $params[':furnishing'] = $furnishing;
}

if (!empty($tenant_preference)) {
    if (stripos($tenant_preference, 'Bachelor') !== false) {
        $sql .= " AND (p.tenant_preference LIKE '%Bachelor%' OR p.tenant_preference = 'Anyone' OR p.tenant_preference = 'Boys Only' OR p.tenant_preference = 'Girls Only')";
    } elseif (stripos($tenant_preference, 'Family') !== false) {
        $sql .= " AND (p.tenant_preference LIKE '%Family%' OR p.tenant_preference = 'Anyone')";
    } else {
        $sql .= " AND p.tenant_preference = :tenant_pref";
        $params[':tenant_pref'] = $tenant_preference;
    }
}

if (!empty($stay_duration)) {
    if (stripos($stay_duration, '1 Month') !== false) {
        $sql .= " AND (p.stay_duration LIKE '%1 Month%' OR p.stay_duration LIKE '%Flexible%')";
    } else {
        $sql .= " AND p.stay_duration LIKE :stay_dur";
        $params[':stay_dur'] = '%' . $stay_duration . '%';
    }
}

if ($max_price > 0 && $max_price < 100000) {
    $sql .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

if ($premium_only) {
    $sql .= " AND p.is_premium = 1";
}

if (!empty($amenities_filter)) {
    foreach ($amenities_filter as $idx => $amenity) {
        $key = ":amenity_" . $idx;
        $sql .= " AND p.amenities LIKE " . $key;
        $params[$key] = '%' . $amenity . '%';
    }
}

// Sorting logic
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.id DESC";
        break;
    case 'featured':
    default:
        $sql .= " ORDER BY p.is_premium DESC, p.id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();
$totalFound = count($properties);
$propertiesGeo = [];
foreach ($properties as $p) {
    $coords = get_property_coordinates($p['location'], $p['city'], $p['id']);
    $propertiesGeo[] = [
        'id'                => (int)$p['id'],
        'title'             => $p['title'],
        'price'             => (float)$p['price'],
        'property_type'     => $p['property_type'],
        'location'          => $p['location'],
        'city'              => $p['city'],
        'bedrooms'          => $p['bedrooms'],
        'bathrooms'         => $p['bathrooms'],
        'furnishing'        => $p['furnishing'],
        'tenant_preference' => $p['tenant_preference'] ?? 'Bachelors Allowed',
        'is_premium'        => (int)$p['is_premium'],
        'image'             => get_property_image($p['image']),
        'lat'               => $coords['lat'],
        'lng'               => $coords['lng']
    ];
}
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Page Breadcrumb & Title -->
    <div class="mb-4">
        <h1 style="font-size: 2rem; font-weight: 800;">Rental Properties</h1>
        <p class="text-muted" style="color: var(--text-muted);">Browse verified rental homes with direct owner contact.</p>
    </div>

    <!-- Mobile Filter Toggle Button -->
    <button type="button" class="btn btn-outline mobile-filter-btn" style="display: none; width: 100%; margin-bottom: 1rem; border-color: var(--primary); color: var(--primary); font-weight: 700;" onclick="toggleMobileFilters()">
        <i class="fa-solid fa-sliders me-1"></i> Filter & Search Options ⚙️
    </button>

    <div style="display: grid; grid-template-columns: 290px 1fr; gap: 2rem; align-items: start;">
        
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar" id="filterSidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.15rem; font-weight: 800;"><i class="fa-solid fa-sliders me-1"></i> Filters</h3>
                <a href="properties.php" style="font-size: 0.85rem; font-weight: 600; color: var(--danger);">Reset All</a>
            </div>

            <form action="properties.php" method="GET" id="filterForm">
                
                <!-- Keyword Search -->
                <div class="filter-group">
                    <label class="filter-title">Search Location / Keyword</label>
                    <input type="text" name="search" class="form-control" placeholder="e.g. Indiranagar, Metro..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- City -->
                <div class="filter-group">
                    <label class="filter-title">City / District</label>
                    <select name="city" class="form-select" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Cities / Districts</option>
                        <?php 
                        $cities = ['Jamui', 'Patna', 'New Delhi', 'Pune', 'Bengaluru', 'Kota', 'Jaipur', 'Lucknow', 'Mumbai', 'Hyderabad', 'Gurugram', 'Noida', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Ranchi', 'Ahmedabad', 'Kolkata', 'Chennai', 'Chandigarh', 'Indore', 'Bhopal', 'Dehradun'];
                        foreach ($cities as $c): 
                        ?>
                            <option value="<?php echo $c; ?>" <?php echo (strtolower($city) === strtolower($c)) ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range Slider -->
                <div class="filter-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="filter-title" style="margin-bottom: 0;">Max Monthly Rent</label>
                        <span id="priceValue" style="font-weight: 800; color: var(--primary); font-size: 0.95rem;">₹<?php echo number_format($max_price); ?></span>
                    </div>
                    <input type="range" name="max_price" id="priceRange" min="500" max="100000" step="500" value="<?php echo $max_price; ?>" style="width: 100%; accent-color: var(--primary);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                        <span>₹500</span>
                        <span>₹1,00,000+</span>
                    </div>
                </div>

                <!-- Property BHK / Room Type -->
                <div class="filter-group">
                    <label class="filter-title">Room & Property Type</label>
                    <?php 
                    $typeOptions = [
                        'Single Room' => '🛏️ Single Local Room',
                        '1 Room Set'  => '🏠 1 Room Set (1 RK)',
                        'Shared Room' => '👥 Shared Room',
                        'PG Room'     => '🍛 PG Room (With Food)',
                        '1 BHK'       => '🚪 1 BHK Flat',
                        '2 BHK'       => '🛋️ 2 BHK Home',
                        '3 BHK'       => '🏢 3 BHK Apartment',
                        'Villa'       => '🏡 Villa / House'
                    ];
                    foreach ($typeOptions as $val => $label):
                        $isChecked = in_array($val, $types);
                    ?>
                        <label class="form-check">
                            <input type="checkbox" name="types[]" value="<?php echo $val; ?>" <?php echo $isChecked ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Furnishing -->
                <div class="filter-group">
                    <label class="filter-title">Furnishing Status</label>
                    <select name="furnishing" class="form-select" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Any Furnishing</option>
                        <option value="Furnished" <?php echo $furnishing === 'Furnished' ? 'selected' : ''; ?>>Fully Furnished</option>
                        <option value="Semi-Furnished" <?php echo $furnishing === 'Semi-Furnished' ? 'selected' : ''; ?>>Semi-Furnished</option>
                        <option value="Unfurnished" <?php echo $furnishing === 'Unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                    </select>
                </div>

                <!-- Tenant Preference (Bachelors / Family) -->
                <div class="filter-group">
                    <label class="filter-title">Tenant Preference (Bachelors / Family)</label>
                    <select name="tenant_preference" class="form-select" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Tenants (Anyone Welcome)</option>
                        <option value="Bachelors" <?php echo (stripos($tenant_preference, 'Bachelor') !== false) ? 'selected' : ''; ?>>🎓 Bachelors Friendly (Students / Jobs)</option>
                        <option value="Family Only" <?php echo ($tenant_preference === 'Family Only') ? 'selected' : ''; ?>>👨‍👩‍👧 Family Only</option>
                        <option value="Girls Only" <?php echo ($tenant_preference === 'Girls Only') ? 'selected' : ''; ?>>👩 Girls / Female Only</option>
                        <option value="Boys Only" <?php echo ($tenant_preference === 'Boys Only') ? 'selected' : ''; ?>>👨 Boys / Male Only</option>
                    </select>
                </div>

                <!-- Stay Duration Filter (1 Month Short Stay) -->
                <div class="filter-group">
                    <label class="filter-title">⏱️ Stay Duration / Minimum Lock-in</label>
                    <select name="stay_duration" class="form-select" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Durations (Any Stay)</option>
                        <option value="1 Month" <?php echo (stripos($stay_duration, '1 Month') !== false) ? 'selected' : ''; ?>>⏱️ 1 Month Only (Short Stay / Exam / Flexible)</option>
                        <option value="3 Months" <?php echo (stripos($stay_duration, '3 Month') !== false) ? 'selected' : ''; ?>>⏱️ 3 Months Minimum</option>
                        <option value="6 Months" <?php echo (stripos($stay_duration, '6 Month') !== false) ? 'selected' : ''; ?>>⏱️ 6 Months Minimum</option>
                        <option value="11 Months" <?php echo (stripos($stay_duration, '11 Month') !== false) ? 'selected' : ''; ?>>⏱️ 11 Months (Standard Annual)</option>
                    </select>
                </div>

                <!-- Amenities Checklist -->
                <div class="filter-group">
                    <label class="filter-title">Amenities & Facilities</label>
                    <?php 
                    $amenityList = ['WiFi', 'Attached Washroom', 'Bed & Mattress', 'Study Table', 'RO Water', 'Mess/Food Included', 'AC', 'Separate Sub-Meter', 'Covered Parking', 'Lift', '24/7 Security', 'Power Backup', 'Balcony', 'Geyser'];
                    foreach ($amenityList as $am):
                        $isAmChecked = in_array($am, $amenities_filter);
                    ?>
                        <label class="form-check">
                            <input type="checkbox" name="amenities[]" value="<?php echo $am; ?>" <?php echo $isAmChecked ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                            <span><?php echo $am; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Premium Filter Checkbox -->
                <div class="filter-group">
                    <label class="form-check" style="color: #d97706; font-weight: 700;">
                        <input type="checkbox" name="is_premium" value="1" <?php echo $premium_only ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                        <span>⭐ Premium Featured Only</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Apply Filters
                </button>
            </form>
        </aside>

        <!-- Main Listings Content -->
        <main>
            <!-- Results Bar & Sorting Control -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: #fff; padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1rem;">
                <div>
                    <span style="font-weight: 800; color: var(--dark); font-size: 1.1rem;">
                        <i class="fa-solid fa-house-circle-check text-success me-1"></i> <?php echo $totalFound; ?> <?php echo $totalFound === 1 ? 'Vacant Room / Property' : 'Vacant Rooms / Properties'; ?> Available
                    </span>
                    <?php if (!empty($city)): ?>
                        <span class="badge badge-info ms-2" style="font-size: 0.85rem;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($city); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($search)): ?>
                        <span class="badge badge-role ms-1" style="font-size: 0.85rem;">"<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <button type="button" class="btn btn-secondary btn-sm" id="toggleMapBtn" onclick="togglePropertyMap()" style="font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem; background: #eef2ff; color: #4338ca; border: 1.5px solid #c7d2fe;">
                        <i class="fa-solid fa-map-location-dot"></i> <span id="mapBtnText">🗺️ View on Map (<?php echo count($propertiesGeo); ?> Pins)</span>
                    </button>
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Sort by:</label>
                    <select class="form-select" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;" onchange="location = this.value;">
                        <option value="<?php echo 'properties.php?' . http_build_query(array_merge($_GET, ['sort' => 'featured'])); ?>" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>⭐ Featured & Priority</option>
                        <option value="<?php echo 'properties.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="<?php echo 'properties.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="<?php echo 'properties.php?' . http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Added</option>
                    </select>
                </div>
            </div>

            <!-- Interactive Multi-Pin Leaflet Map Container -->
            <div id="catalogMapContainer" style="display: none; width: 100%; border-radius: var(--radius-lg); overflow: hidden; border: 1.5px solid #c7d2fe; margin-bottom: 1.5rem; box-shadow: var(--shadow-md); background: #f8fafc;">
                <div style="display: flex; justify-content: space-between; align-items: center; background: #f0f4ff; padding: 0.75rem 1rem; border-bottom: 1px solid #c7d2fe;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #1e1b4b; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fa-solid fa-map-pin text-danger"></i> 
                        <span>Interactive Vacant Rooms & Properties Map (<?php echo count($propertiesGeo); ?> Pins Available)</span>
                    </div>
                    <span style="font-size: 0.78rem; color: #4338ca; font-weight: 600;">Click any pin on the map to see photo, rent & contact details</span>
                </div>
                <div id="propertiesLeafletMap" style="height: 420px; width: 100%;"></div>
            </div>

            <script>
            let rentnearLeafletMap = null;
            const geoPropertiesData = <?php echo json_encode($propertiesGeo); ?>;

            function togglePropertyMap() {
                const mapBox = document.getElementById('catalogMapContainer');
                const btnText = document.getElementById('mapBtnText');
                
                if (mapBox.style.display === 'none') {
                    mapBox.style.display = 'block';
                    btnText.textContent = 'Hide Map';
                    
                    // Initialize Leaflet Map once opened
                    if (!rentnearLeafletMap) {
                        initRentnearMap();
                    } else {
                        setTimeout(() => { rentnearLeafletMap.invalidateSize(); }, 200);
                    }
                } else {
                    mapBox.style.display = 'none';
                    btnText.textContent = '🗺️ View on Map (' + geoPropertiesData.length + ' Pins)';
                }
            }

            function initRentnearMap() {
                if (typeof L === 'undefined') {
                    console.error('Leaflet not loaded');
                    return;
                }

                // Default center (Jamui or first property or India center)
                let defaultCenter = [24.9213, 86.2234];
                let defaultZoom = 13;

                if (geoPropertiesData.length > 0) {
                    defaultCenter = [geoPropertiesData[0].lat, geoPropertiesData[0].lng];
                }

                rentnearLeafletMap = L.map('propertiesLeafletMap').setView(defaultCenter, defaultZoom);

                // Add OpenStreetMap Tile Layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors | RentNear'
                }).addTo(rentnearLeafletMap);

                const bounds = L.latLngBounds();

                // Add interactive pins for every vacant room
                geoPropertiesData.forEach(p => {
                    const latLng = [p.lat, p.lng];
                    bounds.extend(latLng);

                    // Custom HTML Pin Marker with Price Bubble
                    const priceFormatted = '₹' + Number(p.price).toLocaleString('en-IN');
                    const pinHtml = `
                        <div class="custom-map-pin ${p.is_premium ? 'is-premium-pin' : ''}">
                            <span class="pin-dot"></span>
                            ${priceFormatted}
                        </div>
                    `;

                    const customIcon = L.divIcon({
                        className: 'rentnear-map-marker',
                        html: pinHtml,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0]
                    });

                    // Interactive Popup with Photo and Details
                    const popupContent = `
                        <div style="width: 230px; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                            <img src="${p.image}" alt="${p.title}" style="width: 100%; height: 115px; object-fit: cover; border-radius: 10px; margin-bottom: 6px;">
                            <div style="display: flex; gap: 4px; margin-bottom: 4px; flex-wrap: wrap;">
                                <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">${p.property_type}</span>
                                <span style="font-size: 10px; font-weight: 700; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px;">🟢 Vacant</span>
                            </div>
                            <h4 style="font-size: 13px; font-weight: 800; line-height: 1.3; margin: 0 0 3px 0; color: #0f172a;">
                                <a href="property-details.php?id=${p.id}" style="color: #0f172a; text-decoration: none;">${p.title}</a>
                            </h4>
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                                <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> ${p.location}, ${p.city}
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 6px;">
                                <div style="font-size: 14px; font-weight: 800; color: #4338ca;">
                                    ${priceFormatted}<span style="font-size: 10px; font-weight: normal; color: #64748b;">/mo</span>
                                </div>
                                <a href="property-details.php?id=${p.id}" style="background: #4f46e5; color: #fff; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">
                                    View Details &rarr;
                                </a>
                            </div>
                        </div>
                    `;

                    const marker = L.marker(latLng, { icon: customIcon }).addTo(rentnearLeafletMap);
                    marker.bindPopup(popupContent);
                });

                if (geoPropertiesData.length > 0) {
                    rentnearLeafletMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                }

                // If user searched for Jamui, ensure it opens open and zoomed
                <?php if (strtolower($city) === 'jamui' || strtolower($search) === 'jamui'): ?>
                    rentnearLeafletMap.setView([24.9213, 86.2234], 14);
                <?php endif; ?>
            }

            // Auto open map if URL contains ?map=1 or on large screens when user specifically searches
            document.addEventListener('DOMContentLoaded', () => {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('map') === '1' || urlParams.get('view') === 'map') {
                    togglePropertyMap();
                }
            });
            </script>

            <!-- Locality / Area Availability Breakdown Box -->
            <?php 
            // Group available properties by area/locality
            $availableAreas = [];
            foreach ($properties as $p) {
                $areaName = trim($p['location']);
                if (!isset($availableAreas[$areaName])) {
                    $availableAreas[$areaName] = 0;
                }
                $availableAreas[$areaName]++;
            }
            ?>
            <?php if (!empty($availableAreas)): ?>
            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #bbf7d0; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.6rem;">
                    <i class="fa-solid fa-map-location-dot" style="color: #16a34a; font-size: 1.1rem;"></i>
                    <strong style="color: #166534; font-size: 0.95rem;">
                        📍 Available Rooms by Area & Locality (Ready to Move):
                    </strong>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <?php foreach ($availableAreas as $area => $cnt): ?>
                        <a href="properties.php?search=<?php echo urlencode($area); ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?>" style="display: inline-flex; align-items: center; gap: 0.35rem; background: #ffffff; color: #166534; border: 1px solid #86efac; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></span>
                            <?php echo htmlspecialchars($area); ?>
                            <span style="background: #dcfce7; color: #15803d; border-radius: 10px; padding: 0.1rem 0.45rem; font-size: 0.75rem;"><?php echo $cnt; ?> <?php echo $cnt === 1 ? 'room' : 'rooms'; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Properties Grid -->
            <?php if (empty($properties)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                    <div style="width: 72px; height: 72px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                        <i class="fa-solid fa-house-circle-xmark"></i>
                    </div>
                    <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem;">No properties found</h3>
                    <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.5rem;">
                        We couldn't find any rentals matching your exact filter combination. Try adjusting the price slider or clearing filters.
                    </p>
                    <a href="properties.php" class="btn btn-secondary">
                        <i class="fa-solid fa-rotate-left"></i> Reset All Filters
                    </a>
                </div>
            <?php else: ?>
                <div class="properties-grid">
                    <?php foreach ($properties as $prop): ?>
                        <div class="property-card <?php echo $prop['is_premium'] ? 'is-featured' : ''; ?>">
                            <div class="property-thumbnail">
                                <img src="<?php echo htmlspecialchars(get_property_image($prop['image'])); ?>" alt="<?php echo htmlspecialchars($prop['title']); ?>" loading="lazy">
                                <div class="property-badges-overlay">
                                    <?php if ($prop['is_premium']): ?>
                                        <span class="badge badge-premium"><i class="fa-solid fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($prop['property_type']); ?></span>
                                </div>
                                <div class="property-price-tag">
                                    <?php echo format_inr($prop['price']); ?><span class="period">/mo</span>
                                </div>
                                <button class="property-fav-btn" data-property-id="<?php echo $prop['id']; ?>" title="Save Property">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </div>

                            <div class="property-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                    <div class="property-location" style="margin: 0;">
                                        <i class="fa-solid fa-location-dot text-danger"></i>
                                        <span><?php echo htmlspecialchars($prop['location'] . ', ' . $prop['city']); ?></span>
                                    </div>
                                    <span style="font-size: 0.74rem; color: #475569; display: inline-flex; align-items: center; gap: 2px;">
                                        <?php echo htmlspecialchars($prop['owner_name']); ?> 
                                        <?php if (!empty($prop['owner_is_verified']) && (int)$prop['owner_is_verified'] === 1) echo render_verified_badge(false, 13); ?>
                                    </span>
                                </div>
                                <h3 class="property-title">
                                    <a href="property-details.php?id=<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['title']); ?></a>
                                </h3>

                                <div class="property-features">
                                    <div class="property-feature-item">
                                        <i class="fa-solid fa-bed text-muted"></i> <?php echo $prop['bedrooms']; ?> Beds
                                    </div>
                                    <div class="property-feature-item">
                                        <i class="fa-solid fa-bath text-muted"></i> <?php echo $prop['bathrooms']; ?> Baths
                                    </div>
                                    <div class="property-feature-item">
                                        <i class="fa-solid fa-ruler-combined text-muted"></i> <?php echo $prop['area_sqft']; ?> sqft
                                    </div>
                                </div>

                                <div class="property-card-footer">
                                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        <span class="badge badge-role"><?php echo htmlspecialchars($prop['furnishing']); ?></span>
                                        <?php 
                                        $pref = $prop['tenant_preference'] ?? 'Bachelors Allowed';
                                        if (stripos($pref, 'Bachelor') !== false): ?>
                                            <span class="badge" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 0.72rem;">
                                                <i class="fa-solid fa-graduation-cap"></i> Bachelors
                                            </span>
                                        <?php elseif (stripos($pref, 'Family') !== false): ?>
                                            <span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.72rem;">
                                                <i class="fa-solid fa-people-roof"></i> Family
                                            </span>
                                        <?php elseif (stripos($pref, 'Girls') !== false): ?>
                                            <span class="badge" style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; font-size: 0.72rem;">
                                                <i class="fa-solid fa-female"></i> Girls Only
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 0.72rem;">
                                                <i class="fa-solid fa-users"></i> Anyone
                                            </span>
                                        <?php endif; ?>
                                        <?php if (stripos($prop['stay_duration'] ?? '', '1 Month') !== false): ?>
                                            <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.72rem;">
                                                <i class="fa-solid fa-clock"></i> 1-Mo Stay
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-primary btn-sm">
                                        View Details <i class="fa-solid fa-angle-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<style>
@media (max-width: 860px) {
    .container > div[style*="grid-template-columns: 290px 1fr"] {
        grid-template-columns: 1fr !important;
    }
    #filterSidebar {
        display: none; /* Collapsed by default on mobile for easy scrolling */
        margin-bottom: 1.5rem;
    }
    .mobile-filter-btn {
        display: block !important;
    }
}
@media (min-width: 861px) {
    #filterSidebar {
        display: block !important;
    }
    .mobile-filter-btn {
        display: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
