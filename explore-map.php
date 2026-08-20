<?php
// explore-map.php - Dedicated Interactive Explore Map for Vacant Rooms & Rentals
$page_title = "Explore Vacant Rooms on Map";
require_once __DIR__ . '/includes/header.php';

// Retrieve Filter Inputs if passed via GET
$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$city        = isset($_GET['city']) ? trim($_GET['city']) : '';
$type        = isset($_GET['type']) ? trim($_GET['type']) : '';
$tenant_pref   = isset($_GET['tenant_preference']) ? trim($_GET['tenant_preference']) : '';
$stay_duration = isset($_GET['stay_duration']) ? trim($_GET['stay_duration']) : '';
$max_price     = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;

// Construct SQL Query
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

if (!empty($type)) {
    $sql .= " AND p.property_type = :type";
    $params[':type'] = $type;
}

if (!empty($tenant_pref)) {
    if (stripos($tenant_pref, 'Bachelor') !== false) {
        $sql .= " AND (p.tenant_preference LIKE '%Bachelor%' OR p.tenant_preference = 'Anyone' OR p.tenant_preference = 'Boys Only' OR p.tenant_preference = 'Girls Only')";
    } elseif (stripos($tenant_pref, 'Family') !== false) {
        $sql .= " AND (p.tenant_preference LIKE '%Family%' OR p.tenant_preference = 'Anyone')";
    } else {
        $sql .= " AND p.tenant_preference = :tenant_pref";
        $params[':tenant_pref'] = $tenant_pref;
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

$sql .= " ORDER BY p.is_premium DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Build Geo JSON Array for Map
$geoList = [];
foreach ($properties as $p) {
    $coords = get_property_coordinates($p['location'], $p['city'], $p['id']);
    $finalLat = !empty($p['latitude']) ? (float)$p['latitude'] : $coords['lat'];
    $finalLng = !empty($p['longitude']) ? (float)$p['longitude'] : $coords['lng'];

    $geoList[] = [
        'id'                => (int)$p['id'],
        'title'             => $p['title'],
        'price'             => (float)$p['price'],
        'deposit'           => (float)$p['deposit'],
        'property_type'     => $p['property_type'],
        'location'          => $p['location'],
        'city'              => $p['city'],
        'landmark'          => $p['landmark'] ?? '',
        'bedrooms'          => $p['bedrooms'],
        'bathrooms'         => $p['bathrooms'],
        'area_sqft'         => $p['area_sqft'],
        'furnishing'        => $p['furnishing'],
        'tenant_preference' => $p['tenant_preference'] ?? 'Bachelors Allowed',
        'stay_duration'     => $p['stay_duration'] ?? '1 Month (Short Stay Allowed)',
        'is_verified'       => (int)($p['owner_is_verified'] ?? 0),
        'is_premium'        => (int)$p['is_premium'],
        'image'             => get_property_image($p['image']),
        'lat'               => $finalLat,
        'lng'               => $finalLng
    ];
}
?>

<div class="explore-map-wrapper">
    
    <!-- Top Filter Header Bar -->
    <header class="explore-top-bar">
        <div class="explore-top-container">
            <div class="explore-brand-area">
                <div class="explore-title-group">
                    <span class="explore-live-dot" title="Live Database Feed"></span>
                    <h1 class="explore-heading">
                        <i class="fa-solid fa-map-location-dot text-primary"></i> Explore Vacant Rentals
                    </h1>
                </div>
                <div class="explore-counter-pill" id="vacantCountBadge">
                    <span class="count-num" id="pinCountText"><?php echo count($geoList); ?></span> Vacant Rooms Available
                </div>
            </div>

            <!-- Quick City Jumper Pills -->
            <nav class="explore-city-pills" aria-label="City switcher">
                <button type="button" class="city-pill <?php echo strtolower($city) === 'jamui' ? 'active' : ''; ?>" onclick="flyToCity('jamui', [24.9213, 86.2234], 14, this)">
                    📍 Jamui <span class="pill-badge">4</span>
                </button>
                <button type="button" class="city-pill <?php echo (stripos($stay_duration, '1 Month') !== false) ? 'active' : ''; ?>" onclick="location.href='explore-map.php?stay_duration=1+Month'" style="background: #fffbeb; color: #b45309; border-color: #fde68a;">
                    ⏱️ 1-Month Stay
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'new delhi' ? 'active' : ''; ?>" onclick="flyToCity('delhi', [28.6139, 77.2090], 12, this)">
                    📍 New Delhi
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'pune' ? 'active' : ''; ?>" onclick="flyToCity('pune', [18.5204, 73.8567], 12, this)">
                    📍 Pune
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'kota' ? 'active' : ''; ?>" onclick="flyToCity('kota', [25.1800, 75.8300], 13, this)">
                    📍 Kota
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'patna' ? 'active' : ''; ?>" onclick="flyToCity('patna', [25.5941, 85.1376], 13, this)">
                    📍 Patna
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'bengaluru' ? 'active' : ''; ?>" onclick="flyToCity('bengaluru', [12.9716, 77.5946], 12, this)">
                    📍 Bengaluru
                </button>
                <button type="button" class="city-pill gps-pill" onclick="locateUserGps(this)">
                    <i class="fa-solid fa-crosshairs"></i> Near Me (GPS)
                </button>
                <button type="button" class="city-pill" onclick="fitAllPins(this)">
                    🇮🇳 View All India
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Split Screen Area -->
    <div class="explore-split-layout">
        
        <!-- Left Sidebar: Filters + Scrollable Property Cards -->
        <aside class="explore-sidebar" id="exploreSidebar">
            
            <!-- Live Search & Filter Box -->
            <div class="explore-filter-box">
                <form id="mapFilterForm" action="explore-map.php" method="GET">
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass search-ico"></i>
                        <input type="text" name="search" id="liveSearchInput" class="form-control explore-search-input" placeholder="Search locality, landmark, area..." value="<?php echo htmlspecialchars($search); ?>" oninput="applyClientFilter()">
                        <?php if (!empty($search)): ?>
                            <a href="explore-map.php" class="clear-search-btn" title="Clear Search">&times;</a>
                        <?php endif; ?>
                    </div>

                    <!-- Searchable City / District Selector (All India) -->
                    <div class="search-input-wrap" style="margin-top: 0.5rem;">
                        <i class="fa-solid fa-city search-ico"></i>
                        <input type="text" name="city" id="mapCityInput" class="form-control explore-search-input" list="mapCityDatalist" placeholder="Jump to City / District (e.g. Jamui, Patna, Delhi, Pune)..." value="<?php echo htmlspecialchars($city); ?>" autocomplete="off" onchange="document.getElementById('mapFilterForm').submit();">
                        <?php echo render_indian_city_datalist('mapCityDatalist'); ?>
                    </div>

                    <div class="filter-dropdowns-row">
                        <select name="type" id="filterType" class="form-select explore-select" onchange="document.getElementById('mapFilterForm').submit();">
                            <option value="">🏠 All Room Types</option>
                            <option value="Single Room" <?php echo $type === 'Single Room' ? 'selected' : ''; ?>>🛏️ Single Room</option>
                            <option value="1 Room Set" <?php echo $type === '1 Room Set' ? 'selected' : ''; ?>>🚪 1 Room Set (1 RK)</option>
                            <option value="Shared Room" <?php echo $type === 'Shared Room' ? 'selected' : ''; ?>>👥 Shared Room</option>
                            <option value="PG Room" <?php echo $type === 'PG Room' ? 'selected' : ''; ?>>🍛 PG Room (With Food)</option>
                            <option value="1 BHK" <?php echo $type === '1 BHK' ? 'selected' : ''; ?>>🏢 1 BHK Flat</option>
                            <option value="2 BHK" <?php echo $type === '2 BHK' ? 'selected' : ''; ?>>🛋️ 2 BHK Apartment</option>
                            <option value="3 BHK" <?php echo $type === '3 BHK' ? 'selected' : ''; ?>>🏘️ 3 BHK Family Home</option>
                        </select>

                        <select name="tenant_preference" id="filterTenant" class="form-select explore-select" onchange="document.getElementById('mapFilterForm').submit();">
                            <option value="">👥 All Tenants</option>
                            <option value="Bachelors" <?php echo (stripos($tenant_pref, 'Bachelor') !== false) ? 'selected' : ''; ?>>🎓 Bachelors Allowed</option>
                            <option value="Family Only" <?php echo ($tenant_pref === 'Family Only') ? 'selected' : ''; ?>>👨‍👩‍👧 Family Only</option>
                            <option value="Girls Only" <?php echo ($tenant_pref === 'Girls Only') ? 'selected' : ''; ?>>👩 Girls / Female</option>
                            <option value="Boys Only" <?php echo ($tenant_pref === 'Boys Only') ? 'selected' : ''; ?>>👨 Boys / Male</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- List of Vacant Property Cards -->
            <div class="explore-cards-list" id="cardsListContainer">
                <?php if (empty($geoList)): ?>
                    <div class="no-rooms-card">
                        <div class="no-rooms-icon">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <h4>No Vacant Rooms Found</h4>
                        <p>Try resetting filters or zooming out on the map to discover nearby listings.</p>
                        <a href="explore-map.php" class="btn btn-primary btn-sm">Reset All Filters</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($geoList as $item): ?>
                        <div class="map-property-card <?php echo $item['is_premium'] ? 'is-premium' : ''; ?>" id="prop-card-<?php echo $item['id']; ?>" onclick="highlightMapPin(<?php echo $item['id']; ?>)">
                            <div class="map-card-thumb">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                <div class="map-card-price">
                                    ₹<?php echo number_format($item['price']); ?><span class="period">/mo</span>
                                </div>
                                <?php if ($item['is_premium']): ?>
                                    <span class="badge-premium-pill"><i class="fa-solid fa-star"></i> Featured</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="map-card-body">
                                <div>
                                    <div class="map-card-header-meta">
                                        <span class="vacant-pill"><span class="dot"></span> READY TO MOVE</span>
                                        <span class="furnish-tag"><?php echo htmlspecialchars($item['furnishing']); ?></span>
                                    </div>
                                    <h4 class="map-card-title" title="<?php echo htmlspecialchars($item['title']); ?>">
                                        <a href="property-details.php?id=<?php echo $item['id']; ?>" target="_blank" onclick="event.stopPropagation();">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="map-card-loc" title="<?php echo htmlspecialchars($item['location'] . ', ' . $item['city']); ?>">
                                        <i class="fa-solid fa-location-dot loc-ico"></i>
                                        <span><?php echo htmlspecialchars($item['location'] . ', ' . $item['city']); ?></span>
                                    </div>
                                </div>

                                <div class="map-card-footer">
                                    <div class="meta-tags">
                                        <span class="spec-tag"><?php echo htmlspecialchars($item['property_type']); ?></span>
                                        <span class="pref-tag"><?php echo htmlspecialchars($item['tenant_preference']); ?></span>
                                        <?php if (stripos($item['stay_duration'] ?? '', '1 Month') !== false): ?>
                                            <span class="pref-tag" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">⏱️ 1-Mo Stay</span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['is_verified'])): ?>
                                            <span class="pref-tag" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;" title="Verified Owner">
                                                <?php echo render_verified_badge(false, 12); ?> Verified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="property-details.php?id=<?php echo $item['id']; ?>" target="_blank" class="card-action-btn" onclick="event.stopPropagation();">
                                        View &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Right Side: Full Height Interactive Leaflet Map -->
        <main class="explore-map-canvas-container">
            <div id="fullExploreMap"></div>
            
            <!-- Mobile Toggle View Button (Switch Between List and Map) -->
            <button type="button" class="mobile-map-toggle-btn" id="mobileMapToggleBtn" onclick="toggleMobileView()">
                <i class="fa-solid fa-list" id="toggleIcon"></i> <span id="toggleLabel">Show 20 Rooms</span>
            </button>
        </main>

    </div>
</div>

<style>
/* ===================================================================
   RentNear - Dedicated Explore Map Stylesheet (Modern UI/UX)
   =================================================================== */

.explore-map-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 72px);
    min-height: 560px;
    overflow: hidden;
    background: #f8fafc;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Top Header Bar */
.explore-top-bar {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.65rem 1.25rem;
    z-index: 100;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.explore-top-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.explore-brand-area {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.explore-title-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.explore-live-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    animation: livePulse 2s infinite;
}

@keyframes livePulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.7; }
}

.explore-heading {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.explore-counter-pill {
    background: #ecfdf5;
    color: #047857;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    border: 1px solid #a7f3d0;
}

.explore-counter-pill .count-num {
    font-weight: 800;
}

/* City Switcher Pills */
.explore-city-pills {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    align-items: center;
}

.city-pill {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.city-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-1px);
}

.city-pill.active {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: #ffffff;
    border-color: #4338ca;
    box-shadow: 0 3px 10px rgba(79, 70, 229, 0.3);
}

.city-pill .pill-badge {
    background: rgba(255, 255, 255, 0.25);
    padding: 1px 5px;
    border-radius: 10px;
    font-size: 0.7rem;
}

.city-pill.gps-pill {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}
.city-pill.gps-pill:hover {
    background: #dcfce7;
}

/* Split Layout */
.explore-split-layout {
    display: grid;
    grid-template-columns: 440px 1fr;
    flex: 1;
    min-height: 0;
    height: 100%;
    overflow: hidden;
    position: relative;
}

/* Left Sidebar */
.explore-sidebar {
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    z-index: 10;
}

.explore-filter-box {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    flex-shrink: 0;
}

.search-input-wrap {
    position: relative;
    margin-bottom: 0.6rem;
}

.search-ico {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
}

.explore-search-input {
    padding-left: 34px !important;
    padding-right: 28px !important;
    height: 38px;
    border-radius: 10px;
    font-size: 0.85rem;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    transition: all 0.2s ease;
}

.explore-search-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.clear-search-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
    text-decoration: none;
}

.filter-dropdowns-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.explore-select {
    height: 34px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    color: #334155;
    cursor: pointer;
}

/* Cards List */
.explore-cards-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    background: #f8fafc;
}

/* Custom Scrollbar for Sidebar */
.explore-cards-list::-webkit-scrollbar {
    width: 6px;
}
.explore-cards-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.explore-cards-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

/* Property Card Design */
.map-property-card {
    display: flex;
    flex-direction: row;
    min-height: 122px;
    height: 122px;
    flex-shrink: 0;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.map-property-card:hover {
    border-color: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.12);
}

.map-property-card.active-pin {
    border-color: #4f46e5;
    background: #faf5ff;
    box-shadow: 0 0 0 2px #4f46e5, 0 8px 20px rgba(79, 70, 229, 0.18);
    transform: translateY(-2px);
}

.map-property-card.is-premium {
    border-left: 4.5px solid #f59e0b;
}

/* Card Thumbnail */
.map-card-thumb {
    width: 125px;
    min-width: 125px;
    max-width: 125px;
    height: 100%;
    position: relative;
    flex-shrink: 0;
    background: #e2e8f0;
    overflow: hidden;
}

.map-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.map-property-card:hover .map-card-thumb img {
    transform: scale(1.06);
}

.map-card-price {
    position: absolute;
    bottom: 6px;
    left: 6px;
    background: rgba(15, 23, 42, 0.88);
    color: #ffffff;
    font-size: 0.74rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 5px;
    backdrop-filter: blur(4px);
    white-space: nowrap;
}

.map-card-price .period {
    font-size: 0.62rem;
    font-weight: 500;
    color: #cbd5e1;
}

.badge-premium-pill {
    position: absolute;
    top: 6px;
    left: 6px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    font-size: 0.6rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    white-space: nowrap;
}

/* Card Body */
.map-card-body {
    padding: 0.65rem 0.8rem;
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.map-card-header-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3px;
}

.vacant-pill {
    font-size: 0.66rem;
    font-weight: 800;
    color: #059669;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.vacant-pill .dot {
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
}

.furnish-tag {
    font-size: 0.68rem;
    color: #64748b;
    font-weight: 600;
}

.map-card-title {
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.25;
    margin: 0 0 2px 0;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.map-card-title a {
    color: #0f172a;
    text-decoration: none;
}
.map-card-title a:hover {
    color: #4f46e5;
}

.map-card-loc {
    font-size: 0.74rem;
    color: #64748b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 4px;
}

.loc-ico {
    color: #ef4444;
    font-size: 0.72rem;
}

.map-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
    padding-top: 3px;
}

.meta-tags {
    display: flex;
    gap: 4px;
    overflow: hidden;
}

.spec-tag, .pref-tag {
    font-size: 0.66rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 5px;
    white-space: nowrap;
}

.spec-tag {
    background: #eef2ff;
    color: #4338ca;
}

.pref-tag {
    background: #f1f5f9;
    color: #475569;
}

.card-action-btn {
    background: #4f46e5;
    color: #ffffff !important;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.card-action-btn:hover {
    background: #4338ca;
    transform: translateY(-1px);
}

/* No Rooms State */
.no-rooms-card {
    text-align: center;
    padding: 3.5rem 1.5rem;
    background: #ffffff;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
    margin-top: 1rem;
}

.no-rooms-icon {
    font-size: 2.8rem;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.no-rooms-card h4 {
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.4rem;
}

.no-rooms-card p {
    font-size: 0.82rem;
    color: #64748b;
    margin-bottom: 1rem;
}

/* Right Map Canvas */
.explore-map-canvas-container {
    height: 100%;
    width: 100%;
    position: relative;
    background: #e2e8f0;
}

#fullExploreMap {
    width: 100%;
    height: 100%;
}

/* Leaflet Pin Markers */
.custom-map-pin {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: #ffffff;
    padding: 5px 9px;
    border-radius: 20px;
    font-weight: 800;
    font-size: 11px;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.45);
    border: 2px solid #ffffff;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transform: translate(-50%, -100%);
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.custom-map-pin.is-premium-pin {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.5);
}

.custom-map-pin .pin-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22c55e;
    display: inline-block;
}

.custom-map-pin:hover, .custom-map-pin.pin-active {
    transform: translate(-50%, -108%) scale(1.18);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    z-index: 1000 !important;
}

/* Mobile Toggle View Button */
.mobile-map-toggle-btn {
    display: none;
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: #ffffff;
    border: none;
    padding: 0.75rem 1.4rem;
    border-radius: 30px;
    font-weight: 800;
    font-size: 0.88rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    z-index: 1000;
    cursor: pointer;
}

@media (max-width: 900px) {
    .explore-split-layout {
        grid-template-columns: 1fr;
    }
    .explore-sidebar {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
    }
    .explore-sidebar.show-mobile-list {
        display: flex;
    }
    .mobile-map-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
}
</style>

<script>
let fullLeafletMap = null;
const allGeoPins = <?php echo json_encode($geoList); ?>;
const mapMarkersMap = {};
let allBounds = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof L === 'undefined') {
        console.error('Leaflet library is required');
        return;
    }

    allBounds = L.latLngBounds();

    // Default center (Jamui or first property)
    let initialCenter = [24.9213, 86.2234];
    let initialZoom = 13;

    <?php if (strtolower($city) === 'jamui' || strtolower($search) === 'jamui'): ?>
        initialCenter = [24.9213, 86.2234];
        initialZoom = 14;
    <?php elseif (!empty($geoList)): ?>
        initialCenter = [<?php echo $geoList[0]['lat']; ?>, <?php echo $geoList[0]['lng']; ?>];
    <?php endif; ?>

    fullLeafletMap = L.map('fullExploreMap').setView(initialCenter, initialZoom);

    // OpenStreetMap Tile Layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | RentNear'
    }).addTo(fullLeafletMap);

    renderPinsOnMap(allGeoPins);
});

function renderPinsOnMap(pins) {
    // Clear existing markers
    Object.values(mapMarkersMap).forEach(m => fullLeafletMap.removeLayer(m));

    pins.forEach(p => {
        const latLng = [p.lat, p.lng];
        allBounds.extend(latLng);

        const priceFormatted = '₹' + Number(p.price).toLocaleString('en-IN');
        const pinHtml = `
            <div id="marker-pin-${p.id}" class="custom-map-pin ${p.is_premium ? 'is-premium-pin' : ''}">
                <span class="pin-dot"></span>
                ${priceFormatted}
            </div>
        `;

        const customIcon = L.divIcon({
            className: 'rentnear-explore-marker',
            html: pinHtml,
            iconSize: [0, 0],
            iconAnchor: [0, 0]
        });

        const popupContent = `
            <div style="width: 230px; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <img src="${p.image}" alt="${p.title}" style="width: 100%; height: 115px; object-fit: cover; border-radius: 10px; margin-bottom: 6px;">
                <div style="display: flex; gap: 4px; margin-bottom: 4px; flex-wrap: wrap;">
                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">${p.property_type}</span>
                    <span style="font-size: 10px; font-weight: 700; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px;">🟢 Vacant</span>
                    ${p.is_verified ? '<span style="font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px;">✓ Verified</span>' : ''}
                </div>
                <h4 style="font-size: 13px; font-weight: 800; line-height: 1.3; margin: 0 0 3px 0; color: #0f172a;">
                    <a href="property-details.php?id=${p.id}" target="_blank" style="color: #0f172a; text-decoration: none;">${p.title}</a>
                </h4>
                <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> ${p.location}, ${p.city}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 6px;">
                    <div style="font-size: 14px; font-weight: 800; color: #4338ca;">
                        ${priceFormatted}<span style="font-size: 10px; font-weight: normal; color: #64748b;">/mo</span>
                    </div>
                    <a href="property-details.php?id=${p.id}" target="_blank" style="background: #4f46e5; color: #fff; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">
                        View & Contact &rarr;
                    </a>
                </div>
            </div>
        `;

        const marker = L.marker(latLng, { icon: customIcon }).addTo(fullLeafletMap);
        marker.bindPopup(popupContent);
        
        marker.on('click', () => {
            highlightSidebarCard(p.id);
        });

        mapMarkersMap[p.id] = marker;
    });

    if (pins.length > 0) {
        <?php if (!empty($city) || !empty($search)): ?>
            fullLeafletMap.fitBounds(allBounds, { padding: [40, 40], maxZoom: 15 });
        <?php endif; ?>
    }
}

function highlightMapPin(propId) {
    const marker = mapMarkersMap[propId];
    if (marker) {
        fullLeafletMap.flyTo(marker.getLatLng(), 15, { duration: 0.8 });
        marker.openPopup();
    }
    highlightSidebarCard(propId);
}

function highlightSidebarCard(propId) {
    document.querySelectorAll('.map-property-card').forEach(c => c.classList.remove('active-pin'));
    const targetCard = document.getElementById('prop-card-' + propId);
    if (targetCard) {
        targetCard.classList.add('active-pin');
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function flyToCity(cityName, coords, zoomLevel, btnElement) {
    if (fullLeafletMap) {
        fullLeafletMap.flyTo(coords, zoomLevel, { duration: 1.2 });
    }
    document.querySelectorAll('.city-pill').forEach(p => p.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
}

function fitAllPins(btnElement) {
    if (fullLeafletMap && allBounds && allBounds.isValid()) {
        fullLeafletMap.fitBounds(allBounds, { padding: [50, 50] });
    }
    document.querySelectorAll('.city-pill').forEach(p => p.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
}

function locateUserGps(btnElement) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(position => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            
            fullLeafletMap.flyTo([userLat, userLng], 14, { duration: 1.2 });
            
            // Add GPS Marker
            L.circleMarker([userLat, userLng], {
                radius: 10,
                fillColor: "#3b82f6",
                color: "#fff",
                weight: 3,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(fullLeafletMap).bindPopup("📍 Your Current Location").openPopup();

            document.querySelectorAll('.city-pill').forEach(p => p.classList.remove('active'));
            if (btnElement) btnElement.classList.add('active');
        }, error => {
            alert("Could not access GPS location. Please allow location permissions in your browser.");
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }
}

function applyClientFilter() {
    const q = document.getElementById('liveSearchInput').value.toLowerCase().trim();
    const filtered = allGeoPins.filter(p => {
        return p.title.toLowerCase().includes(q) ||
               p.location.toLowerCase().includes(q) ||
               p.city.toLowerCase().includes(q) ||
               p.property_type.toLowerCase().includes(q);
    });

    document.querySelectorAll('.map-property-card').forEach(c => {
        const id = parseInt(c.id.replace('prop-card-', ''));
        const matches = filtered.some(f => f.id === id);
        c.style.display = matches ? 'flex' : 'none';
    });

    document.getElementById('pinCountText').textContent = filtered.length;
}

function toggleMobileView() {
    const sidebar = document.getElementById('exploreSidebar');
    const toggleIcon = document.getElementById('toggleIcon');
    const toggleLabel = document.getElementById('toggleLabel');

    if (sidebar.classList.contains('show-mobile-list')) {
        sidebar.classList.remove('show-mobile-list');
        toggleIcon.className = 'fa-solid fa-list';
        toggleLabel.textContent = 'Show 20 Rooms';
    } else {
        sidebar.classList.add('show-mobile-list');
        toggleIcon.className = 'fa-solid fa-map';
        toggleLabel.textContent = 'Show Map';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
