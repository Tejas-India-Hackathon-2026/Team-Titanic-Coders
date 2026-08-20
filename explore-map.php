<?php
// explore-map.php - Dedicated Interactive Explore Map for Vacant Rooms & Rentals
$page_title = "Explore Vacant Rooms on Map";
require_once __DIR__ . '/includes/header.php';

// Retrieve Filter Inputs if passed via GET
$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$city        = isset($_GET['city']) ? trim($_GET['city']) : '';
$type        = isset($_GET['type']) ? trim($_GET['type']) : '';
$tenant_pref = isset($_GET['tenant_preference']) ? trim($_GET['tenant_preference']) : '';
$max_price   = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;

// Construct SQL Query
$sql = "SELECT p.*, o.name as owner_name, o.phone as owner_phone 
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
    $geoList[] = [
        'id'                => (int)$p['id'],
        'title'             => $p['title'],
        'price'             => (float)$p['price'],
        'deposit'           => (float)$p['deposit'],
        'property_type'     => $p['property_type'],
        'location'          => $p['location'],
        'city'              => $p['city'],
        'bedrooms'          => $p['bedrooms'],
        'bathrooms'         => $p['bathrooms'],
        'area_sqft'         => $p['area_sqft'],
        'furnishing'        => $p['furnishing'],
        'tenant_preference' => $p['tenant_preference'] ?? 'Bachelors Allowed',
        'is_premium'        => (int)$p['is_premium'],
        'image'             => get_property_image($p['image']),
        'lat'               => $coords['lat'],
        'lng'               => $coords['lng']
    ];
}
?>

<div class="explore-map-wrapper">
    
    <!-- Top Filter Header Bar -->
    <div class="explore-top-bar">
        <div class="explore-top-container">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h1 style="font-size: 1.25rem; font-weight: 800; margin: 0; color: var(--dark); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-map-location-dot text-primary"></i> Explore Vacant Rooms Map
                </h1>
                <span class="badge badge-success" id="vacantCountBadge" style="font-size: 0.8rem;">
                    🟢 <span id="pinCountText"><?php echo count($geoList); ?></span> Rooms Ready to Move
                </span>
            </div>

            <!-- Quick City Jumper Buttons -->
            <div class="explore-city-pills">
                <button type="button" class="city-pill <?php echo strtolower($city) === 'jamui' ? 'active' : ''; ?>" onclick="flyToCity('jamui', [24.9213, 86.2234], 14, this)">
                    📍 Jamui
                </button>
                <button type="button" class="city-pill <?php echo strtolower($city) === 'new delhi' ? 'active' : ''; ?>" onclick="flyToCity('delhi', [28.6139, 77.2090], 12, this)">
                    📍 Delhi NCR
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
                <button type="button" class="city-pill" onclick="locateUserGps(this)" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0;">
                    <i class="fa-solid fa-crosshairs"></i> Near Me (GPS)
                </button>
                <button type="button" class="city-pill" onclick="fitAllPins(this)">
                    🇮🇳 View All India
                </button>
            </div>
        </div>
    </div>

    <!-- Main Split Screen Area -->
    <div class="explore-split-layout">
        
        <!-- Left Sidebar: Filters + Scrollable Property Cards -->
        <div class="explore-sidebar" id="exploreSidebar">
            
            <!-- Live Search & Filter Box -->
            <div class="explore-filter-box">
                <form id="mapFilterForm" action="explore-map.php" method="GET">
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <input type="text" name="search" id="liveSearchInput" class="form-control" placeholder="Search locality or room..." value="<?php echo htmlspecialchars($search); ?>" style="font-size: 0.9rem;" oninput="applyClientFilter()">
                        <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.9rem; font-size: 0.9rem;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div>
                            <select name="type" id="filterType" class="form-select form-select-sm" style="font-size: 0.82rem;" onchange="document.getElementById('mapFilterForm').submit();">
                                <option value="">All Room Types</option>
                                <option value="Single Room" <?php echo $type === 'Single Room' ? 'selected' : ''; ?>>🛏️ Single Room</option>
                                <option value="1 Room Set" <?php echo $type === '1 Room Set' ? 'selected' : ''; ?>>🏠 1 Room Set (1 RK)</option>
                                <option value="Shared Room" <?php echo $type === 'Shared Room' ? 'selected' : ''; ?>>👥 Shared Room</option>
                                <option value="PG Room" <?php echo $type === 'PG Room' ? 'selected' : ''; ?>>🍛 PG Room</option>
                                <option value="1 BHK" <?php echo $type === '1 BHK' ? 'selected' : ''; ?>>🚪 1 BHK Flat</option>
                                <option value="2 BHK" <?php echo $type === '2 BHK' ? 'selected' : ''; ?>>🛋️ 2 BHK Apartment</option>
                                <option value="3 BHK" <?php echo $type === '3 BHK' ? 'selected' : ''; ?>>🏢 3 BHK Flat</option>
                            </select>
                        </div>
                        <div>
                            <select name="tenant_preference" id="filterTenant" class="form-select form-select-sm" style="font-size: 0.82rem;" onchange="document.getElementById('mapFilterForm').submit();">
                                <option value="">All Tenants</option>
                                <option value="Bachelors" <?php echo (stripos($tenant_pref, 'Bachelor') !== false) ? 'selected' : ''; ?>>🎓 Bachelors</option>
                                <option value="Family Only" <?php echo ($tenant_pref === 'Family Only') ? 'selected' : ''; ?>>👨‍👩‍👧 Family Only</option>
                                <option value="Girls Only" <?php echo ($tenant_pref === 'Girls Only') ? 'selected' : ''; ?>>👩 Girls Only</option>
                                <option value="Boys Only" <?php echo ($tenant_pref === 'Boys Only') ? 'selected' : ''; ?>>👨 Boys Only</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- List of Vacant Property Cards -->
            <div class="explore-cards-list" id="cardsListContainer">
                <?php if (empty($geoList)): ?>
                    <div style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                        <i class="fa-solid fa-map-location" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                        <h4 style="font-weight: 700; color: var(--dark);">No Rooms Found in this Area</h4>
                        <p style="font-size: 0.85rem;">Try zooming out on the map or resetting your search filters.</p>
                        <a href="explore-map.php" class="btn btn-outline btn-sm mt-2">Reset Map</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($geoList as $item): ?>
                        <div class="map-property-card <?php echo $item['is_premium'] ? 'is-premium' : ''; ?>" id="prop-card-<?php echo $item['id']; ?>" onclick="highlightMapPin(<?php echo $item['id']; ?>)">
                            <div class="map-card-thumb">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                <span class="map-card-price">₹<?php echo number_format($item['price']); ?><span>/mo</span></span>
                                <?php if ($item['is_premium']): ?>
                                    <span class="badge badge-premium map-card-badge"><i class="fa-solid fa-star"></i> Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="map-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.2rem;">
                                    <span style="font-size: 0.72rem; font-weight: 700; color: #16a34a;">🟢 Vacant</span>
                                    <span style="font-size: 0.72rem; color: var(--text-muted);"><?php echo htmlspecialchars($item['furnishing']); ?></span>
                                </div>
                                <h4 class="map-card-title">
                                    <a href="property-details.php?id=<?php echo $item['id']; ?>" target="_blank"><?php echo htmlspecialchars($item['title']); ?></a>
                                </h4>
                                <div class="map-card-loc">
                                    <i class="fa-solid fa-location-dot text-danger"></i> <?php echo htmlspecialchars($item['location'] . ', ' . $item['city']); ?>
                                </div>
                                <div class="map-card-footer">
                                    <span class="badge badge-info" style="font-size: 0.7rem;"><?php echo htmlspecialchars($item['property_type']); ?></span>
                                    <span class="badge badge-role" style="font-size: 0.7rem;"><?php echo htmlspecialchars($item['tenant_preference']); ?></span>
                                    <a href="property-details.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.2rem 0.6rem; font-size: 0.72rem; margin-left: auto;">
                                        Details &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Side: Full Height Interactive Leaflet Map -->
        <div class="explore-map-canvas-container">
            <div id="fullExploreMap" style="width: 100%; height: 100%;"></div>
            
            <!-- Mobile Toggle View Button (Switch Between List and Map) -->
            <button type="button" class="mobile-map-toggle-btn" id="mobileMapToggleBtn" onclick="toggleMobileView()">
                <i class="fa-solid fa-list" id="toggleIcon"></i> <span id="toggleLabel">Show List</span>
            </button>
        </div>

    </div>
</div>

<style>
/* Explore Map Full-Width Experience */
.explore-map-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 72px);
    overflow: hidden;
    background: #f8fafc;
}

.explore-top-bar {
    background: #fff;
    border-bottom: 1px solid var(--border-color);
    padding: 0.75rem 1.5rem;
    z-index: 100;
}

.explore-top-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

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
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.city-pill:hover, .city-pill.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}

.explore-split-layout {
    display: grid;
    grid-template-columns: 420px 1fr;
    flex: 1;
    overflow: hidden;
    position: relative;
}

.explore-sidebar {
    background: #fff;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.explore-filter-box {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: #f8fafc;
}

.explore-cards-list {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.map-property-card {
    display: flex;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: var(--shadow-sm);
}

.map-property-card:hover, .map-property-card.active-pin {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.15);
}

.map-property-card.is-premium {
    border-left: 3.5px solid #d97706;
}

.map-card-thumb {
    width: 125px;
    position: relative;
    flex-shrink: 0;
}

.map-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.map-card-price {
    position: absolute;
    bottom: 6px;
    left: 6px;
    background: rgba(15, 23, 42, 0.85);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    backdrop-filter: blur(4px);
}

.map-card-price span {
    font-size: 0.65rem;
    font-weight: normal;
}

.map-card-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    font-size: 0.62rem;
    padding: 2px 5px;
}

.map-card-body {
    padding: 0.75rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.map-card-title {
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.3;
    margin: 0 0 0.25rem 0;
    color: var(--dark);
}

.map-card-title a {
    color: var(--dark);
    text-decoration: none;
}

.map-card-loc {
    font-size: 0.76rem;
    color: var(--text-muted);
    margin-bottom: 0.4rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.map-card-footer {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex-wrap: wrap;
}

.explore-map-canvas-container {
    height: 100%;
    position: relative;
}

.mobile-map-toggle-btn {
    display: none;
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: #fff;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.88rem;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    z-index: 1000;
    cursor: pointer;
}

@media (max-width: 900px) {
    .explore-split-layout {
        grid-template-columns: 1fr;
    }
    .explore-sidebar {
        display: none; /* Map full by default on mobile */
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
        attribution: '&copy; OpenStreetMap contributors | RentNear Map'
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
            <div id="marker-pin-${p.id}" style="background: ${p.is_premium ? '#d97706' : '#4338ca'}; color: #fff; padding: 4px 8px; border-radius: 18px; font-weight: 800; font-size: 11px; box-shadow: 0 4px 10px rgba(0,0,0,0.35); border: 2px solid #fff; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; transform: translate(-50%, -100%); transition: transform 0.2s ease;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
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
            <div style="width: 230px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <img src="${p.image}" alt="${p.title}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 8px; margin-bottom: 6px;">
                <div style="display: flex; gap: 4px; margin-bottom: 4px; flex-wrap: wrap;">
                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">${p.property_type}</span>
                    <span style="font-size: 10px; font-weight: 700; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px;">🟢 Vacant</span>
                </div>
                <h4 style="font-size: 13px; font-weight: 700; line-height: 1.3; margin: 0 0 4px 0; color: #0f172a;">
                    <a href="property-details.php?id=${p.id}" target="_blank" style="color: #0f172a; text-decoration: none;">${p.title}</a>
                </h4>
                <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> ${p.location}, ${p.city}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 6px;">
                    <div style="font-size: 14px; font-weight: 800; color: #4338ca;">
                        ${priceFormatted}<span style="font-size: 10px; font-weight: normal; color: #64748b;">/mo</span>
                    </div>
                    <a href="property-details.php?id=${p.id}" target="_blank" style="background: #4f46e5; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-decoration: none; display: inline-block;">
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
    if (fullLeafletMap && allBounds.isValid()) {
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
        toggleLabel.textContent = 'Show List';
    } else {
        sidebar.classList.add('show-mobile-list');
        toggleIcon.className = 'fa-solid fa-map';
        toggleLabel.textContent = 'Show Map';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
