<?php
// index.php - RentNear Homepage
$page_title = "Find Your Next Place with RentNear";
require_once __DIR__ . '/includes/header.php';

// Fetch Featured / Premium Properties
$stmtPremium = $pdo->query("
    SELECT p.*, o.name as owner_name, o.phone as owner_phone 
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    WHERE p.is_premium = 1 AND p.status = 'available'
    ORDER BY p.id DESC 
    LIMIT 6
");
$premiumProperties = $stmtPremium->fetchAll();

// Fetch Latest Properties
$stmtLatest = $pdo->query("
    SELECT p.*, o.name as owner_name, o.phone as owner_phone 
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    WHERE p.status = 'available'
    ORDER BY p.is_premium DESC, p.id DESC 
    LIMIT 6
");
$latestProperties = $stmtLatest->fetchAll();

// Fetch counts for Stats
$totalProps = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalOwners = (int)$pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
$totalRenters = (int)$pdo->query("SELECT COUNT(*) FROM renters")->fetchColumn();
$totalUsers = $totalOwners + $totalRenters;
$totalPremium = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE is_premium = 1")->fetchColumn();

// Fetch All Available Properties for Interactive Map
$stmtAllProps = $pdo->query("
    SELECT id, title, price, property_type, location, city, is_premium, image, tenant_preference 
    FROM properties 
    WHERE status = 'available'
");
$allAvailableGeo = [];
while ($p = $stmtAllProps->fetch()) {
    $coords = get_property_coordinates($p['location'], $p['city'], $p['id']);
    $allAvailableGeo[] = [
        'id'                => (int)$p['id'],
        'title'             => $p['title'],
        'price'             => (float)$p['price'],
        'property_type'     => $p['property_type'],
        'location'          => $p['location'],
        'city'              => $p['city'],
        'tenant_preference' => $p['tenant_preference'] ?? 'Bachelors Allowed',
        'is_premium'        => (int)$p['is_premium'],
        'image'             => get_property_image($p['image']),
        'lat'               => $coords['lat'],
        'lng'               => $coords['lng']
    ];
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fa-solid fa-sparkles text-warning"></i> #1 Trusted Rental Platform
            </div>
            <h1 class="hero-title">
                Find Your Next Rental Home <span>Without Brokerage</span>
            </h1>
            <p class="hero-subtitle">
                Explore thousands of verified flats, apartments, and villas directly from owners.
            </p>

            <!-- Search Box Component -->
            <div class="search-box-card">
                <form action="properties.php" method="GET" class="search-form-grid">
                    <!-- Search Keyword -->
                    <div class="form-group-search">
                        <label><i class="fa-solid fa-magnifying-glass"></i> Keyword / Area</label>
                        <input type="text" name="search" class="form-control" placeholder="e.g. Indiranagar, Balcony, Sea View">
                    </div>

                    <!-- City Select -->
                    <div class="form-group-search">
                        <label><i class="fa-solid fa-location-dot"></i> City / Location</label>
                        <select name="city" class="form-select">
                            <option value="">All Cities (Pan India)</option>
                            <option value="Jamui">⭐ Jamui (Local Rooms Available)</option>
                            <option value="New Delhi">New Delhi / NCR</option>
                            <option value="Bengaluru">Bengaluru</option>
                            <option value="Pune">Pune</option>
                            <option value="Mumbai">Mumbai</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Gurugram">Gurugram</option>
                            <option value="Noida">Noida</option>
                            <option value="Kota">Kota (Coaching Hub)</option>
                            <option value="Jaipur">Jaipur</option>
                            <option value="Patna">Patna</option>
                            <option value="Lucknow">Lucknow</option>
                            <option value="Ahmedabad">Ahmedabad</option>
                            <option value="Kolkata">Kolkata</option>
                            <option value="Chennai">Chennai</option>
                        </select>
                    </div>

                    <!-- Property Type -->
                    <div class="form-group-search">
                        <label><i class="fa-solid fa-house"></i> Property / Room Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Rooms & Flats</option>
                            <option value="Single Room">🛏️ Single Local Room</option>
                            <option value="1 Room Set">🏠 1 Room Set / 1 RK</option>
                            <option value="Shared Room">👥 Shared Room / Bed Space</option>
                            <option value="PG Room">🍛 PG Room (With Food/Mess)</option>
                            <option value="1 BHK">🚪 1 BHK Flat</option>
                            <option value="2 BHK">🛋️ 2 BHK Flat</option>
                            <option value="3 BHK">🏢 3 BHK Apartment</option>
                            <option value="Villa">🏡 Independent Villa</option>
                        </select>
                    </div>

                    <!-- Search Submit -->
                    <div class="form-group-search">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.65rem 1.5rem;">
                            <i class="fa-solid fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Map Action CTA Buttons -->
            <div style="display: flex; justify-content: center; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap;">
                <a href="explore-map.php" class="btn btn-secondary" style="background: #ffffff; color: var(--primary); font-weight: 800; border-radius: 30px; padding: 0.55rem 1.4rem; box-shadow: var(--shadow-md); display: inline-flex; align-items: center; gap: 0.4rem; border: 1px solid #c7d2fe;">
                    <i class="fa-solid fa-map-location-dot text-primary"></i> 🗺️ Open Live Explore Map
                </a>
                <a href="explore-map.php?city=Jamui" class="btn btn-secondary" style="background: #ffffff; color: #059669; font-weight: 800; border-radius: 30px; padding: 0.55rem 1.4rem; box-shadow: var(--shadow-md); display: inline-flex; align-items: center; gap: 0.4rem; border: 1px solid #a7f3d0;">
                    📍 Jamui Rooms on Map (4 Vacant)
                </a>
                <a href="explore-map.php?tenant_preference=Bachelors" class="btn btn-secondary" style="background: #ffffff; color: #4338ca; font-weight: 800; border-radius: 30px; padding: 0.55rem 1.4rem; box-shadow: var(--shadow-md); display: inline-flex; align-items: center; gap: 0.4rem; border: 1px solid #c7d2fe;">
                    🎓 Bachelor Friendly Map
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Category Browser Section -->
<section class="section" style="background: #fff; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header text-center">
            <span class="section-tag">Browse by Local Room & Home Type</span>
            <h2 class="section-title">Local Rooms, PGs, 1 BHK & 2 BHK Rentals</h2>
            <p class="section-subtitle">Find budget local rooms for students, PG with food, 1 Room Sets, 1 BHK & 2 BHK family flats.</p>
        </div>

        <div class="categories-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
            <a href="properties.php?type=Single+Room" class="category-card" style="border-top: 3px solid #4f46e5;">
                <div class="category-icon" style="background: #eef2ff; color: #4f46e5;"><i class="fa-solid fa-bed"></i></div>
                <div class="category-name">Single Local Room</div>
                <div class="category-count">Private Rooms for Students</div>
            </a>
            <a href="properties.php?type=1+Room+Set" class="category-card" style="border-top: 3px solid #059669;">
                <div class="category-icon" style="background: #ecfdf5; color: #059669;"><i class="fa-solid fa-house-chimney-window"></i></div>
                <div class="category-name">1 Room Set / 1 RK</div>
                <div class="category-count">Room + Kitchen + Bath</div>
            </a>
            <a href="properties.php?type=Shared+Room" class="category-card" style="border-top: 3px solid #d97706;">
                <div class="category-icon" style="background: #fffbeb; color: #d97706;"><i class="fa-solid fa-people-arrows"></i></div>
                <div class="category-name">Shared Room</div>
                <div class="category-count">2/3 Sharing Budget Beds</div>
            </a>
            <a href="properties.php?type=PG+Room" class="category-card" style="border-top: 3px solid #dc2626;">
                <div class="category-icon" style="background: #fef2f2; color: #dc2626;"><i class="fa-solid fa-utensils"></i></div>
                <div class="category-name">PG Room (With Food)</div>
                <div class="category-count">Meals + AC + Laundry</div>
            </a>
            <a href="properties.php?type=1+BHK" class="category-card" style="border-top: 3px solid #0284c7;">
                <div class="category-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fa-solid fa-door-open"></i></div>
                <div class="category-name">1 BHK Flats</div>
                <div class="category-count">Couples & Professionals</div>
            </a>
            <a href="properties.php?type=2+BHK" class="category-card" style="border-top: 3px solid #10b981;">
                <div class="category-icon" style="background: #f0fdf4; color: #16a34a;"><i class="fa-solid fa-couch"></i></div>
                <div class="category-name">2 BHK Homes</div>
                <div class="category-count">Families & Sharing</div>
            </a>
            <a href="properties.php?type=3+BHK" class="category-card" style="border-top: 3px solid #7c3aed;">
                <div class="category-icon" style="background: #f5f3ff; color: #7c3aed;"><i class="fa-solid fa-building-user"></i></div>
                <div class="category-name">3 BHK & Villas</div>
                <div class="category-count">Spacious Living</div>
            </a>
            <a href="properties.php?tenant_preference=Bachelors" class="category-card" style="border-top: 3px solid #6366f1; background: #faf5ff;">
                <div class="category-icon" style="background: #ede9fe; color: #6366f1;"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="category-name" style="color: #4f46e5;">🎓 Bachelor Friendly</div>
                <div class="category-count">Students & Working Boys/Girls</div>
            </a>
        </div>
    </div>
</section>

<!-- Popular Rental Cities & Localities Section -->
<section class="section" style="background: #f1f5f9; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header text-center">
            <span class="section-tag"><i class="fa-solid fa-location-crosshairs text-primary"></i> Explore by City</span>
            <h2 class="section-title">Top Rental Hubs & Student Cities</h2>
            <p class="section-subtitle">Browse verified local rooms, 1 BHK flats, and PGs in major Indian education and IT centers.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;">
            <a href="properties.php?city=Jamui" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); border: 2px solid #86efac; box-shadow: 0 4px 6px -1px rgba(34,197,94,0.1); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-map-pin"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #15803d;">Jamui</h4>
                        <span class="badge badge-success" style="font-size: 0.65rem; padding: 0.1rem 0.35rem;">4 Vacant</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">KKM College, Station Rd</p>
                </div>
            </a>

            <a href="properties.php?city=New+Delhi" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Delhi / NCR</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Mukherjee Ngr, Laxmi Ngr</p>
                </div>
            </a>

            <a href="properties.php?city=Bengaluru" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Bengaluru</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Indiranagar, Marathahalli</p>
                </div>
            </a>

            <a href="properties.php?city=Pune" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Pune</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Viman Nagar, Hinjawadi</p>
                </div>
            </a>

            <a href="properties.php?city=Kota" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-book-open-reader"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Kota</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Rajeev Gandhi Ngr, Allen</p>
                </div>
            </a>

            <a href="properties.php?city=Jaipur" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fdf2f8; color: #db2777; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-sun"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Jaipur</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Malviya Nagar, WTP</p>
                </div>
            </a>

            <a href="properties.php?city=Mumbai" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-water"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Mumbai</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Bandra West, Andheri</p>
                </div>
            </a>

            <a href="properties.php?city=Patna" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-school"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Patna</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Boring Road, Kankarbagh</p>
                </div>
            </a>

            <a href="properties.php?city=Lucknow" class="category-card" style="background: #fff; padding: 1.25rem; text-align: left; display: flex; align-items: center; gap: 1rem; border-radius: var(--radius-lg); transition: var(--transition);">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="fa-solid fa-tree-city"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--dark);">Lucknow</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Gomti Nagar, Hazratganj</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Interactive City Map & Vacant Room Locator Section -->
<section class="section" style="background: #fff; border-bottom: 1px solid var(--border-color); padding: 4rem 0;">
    <div class="container">
        <div class="section-header text-center" style="margin-bottom: 2rem;">
            <span class="section-tag"><i class="fa-solid fa-map-location-dot text-primary"></i> Live Vacant Room Locator</span>
            <h2 class="section-title">🗺️ Interactive Map of Available Rental Rooms</h2>
            <p class="section-subtitle">Click on any city or zoom into areas like Jamui, Delhi, Pune, Kota, Patna to see where rooms are vacant and ready to move.</p>
            
            <!-- Quick City Switcher Pills -->
            <div style="display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 1.25rem;">
                <button type="button" class="btn btn-primary btn-sm active" onclick="jumpToCity('jamui', [24.9213, 86.2234], 14, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 Jamui (4 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="jumpToCity('delhi', [28.6139, 77.2090], 12, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 New Delhi (3 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="jumpToCity('pune', [18.5204, 73.8567], 12, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 Pune (3 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="jumpToCity('kota', [25.1800, 75.8300], 13, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 Kota (2 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="jumpToCity('bengaluru', [12.9716, 77.5946], 12, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 Bengaluru (2 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="jumpToCity('patna', [25.5941, 85.1376], 13, this)" style="border-radius: 20px; font-weight: 700;">
                    📍 Patna (2 Rooms)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="showAllIndia(this)" style="border-radius: 20px; font-weight: 700;">
                    🇮🇳 View All India
                </button>
            </div>
        </div>

        <div style="border-radius: var(--radius-xl); overflow: hidden; border: 1.5px solid var(--border-color); box-shadow: var(--shadow-lg); background: #f8fafc;">
            <div id="homeInteractiveMap" style="height: 480px; width: 100%;"></div>
        </div>
    </div>
</section>

<script>
let homeMap = null;
const allPropertiesGeoData = <?php echo json_encode($allAvailableGeo); ?>;
let homeBounds = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof L !== 'undefined') {
        homeBounds = L.latLngBounds();
        // Initial center on Jamui
        homeMap = L.map('homeInteractiveMap').setView([24.9213, 86.2234], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors | RentNear'
        }).addTo(homeMap);

        allPropertiesGeoData.forEach(p => {
            const latLng = [p.lat, p.lng];
            homeBounds.extend(latLng);

            const priceFormatted = '₹' + Number(p.price).toLocaleString('en-IN');
            const pinHtml = `
                <div style="background: ${p.is_premium ? '#d97706' : '#4338ca'}; color: #fff; padding: 4px 8px; border-radius: 18px; font-weight: 800; font-size: 11px; box-shadow: 0 4px 10px rgba(0,0,0,0.35); border: 2px solid #fff; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; transform: translate(-50%, -100%);">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                    ${priceFormatted}
                </div>
            `;

            const customIcon = L.divIcon({
                className: 'home-map-marker',
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
                        <a href="property-details.php?id=${p.id}" style="color: #0f172a; text-decoration: none;">${p.title}</a>
                    </h4>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                        <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> ${p.location}, ${p.city}
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 6px;">
                        <div style="font-size: 14px; font-weight: 800; color: #4338ca;">
                            ${priceFormatted}<span style="font-size: 10px; font-weight: normal; color: #64748b;">/mo</span>
                        </div>
                        <a href="property-details.php?id=${p.id}" style="background: #4f46e5; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-decoration: none; display: inline-block;">
                            View Details &rarr;
                        </a>
                    </div>
                </div>
            `;

            const marker = L.marker(latLng, { icon: customIcon }).addTo(homeMap);
            marker.bindPopup(popupContent);
        });
    }
});

function jumpToCity(cityName, coords, zoomLevel, btnElement) {
    if (homeMap) {
        homeMap.flyTo(coords, zoomLevel, { duration: 1.2 });
    }
    document.querySelectorAll('.section-header .btn-sm').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-secondary');
    });
    btnElement.classList.remove('btn-secondary');
    btnElement.classList.add('btn-primary', 'active');
}

function showAllIndia(btnElement) {
    if (homeMap && homeBounds && homeBounds.isValid()) {
        homeMap.fitBounds(homeBounds, { padding: [50, 50] });
    }
    document.querySelectorAll('.section-header .btn-sm').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-secondary');
    });
    btnElement.classList.remove('btn-secondary');
    btnElement.classList.add('btn-primary', 'active');
}
</script>

<!-- Featured / Premium Listings Section -->
<?php if (!empty($premiumProperties)): ?>
<section class="section" style="background: #f8fafc;">
    <div class="container">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag" style="color: #d97706;"><i class="fa-solid fa-crown text-warning"></i> Premium Highlights</span>
                <h2 class="section-title">⭐ Featured Premium Listings</h2>
                <p class="section-subtitle">Promoted by owners for higher visibility & top priority placement.</p>
            </div>
            <a href="properties.php?filter=premium" class="btn btn-outline btn-sm">
                View All Premium <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="properties-grid">
            <?php foreach ($premiumProperties as $prop): ?>
                <div class="property-card is-featured">
                    <div class="property-thumbnail">
                        <img src="<?php echo htmlspecialchars(get_property_image($prop['image'])); ?>" alt="<?php echo htmlspecialchars($prop['title']); ?>" loading="lazy">
                        <div class="property-badges-overlay">
                            <span class="badge badge-premium"><i class="fa-solid fa-star"></i> Featured</span>
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
                        <div class="property-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($prop['location'] . ', ' . $prop['city']); ?></span>
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
                            <span class="badge badge-role"><?php echo htmlspecialchars($prop['furnishing']); ?></span>
                            <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-primary btn-sm">
                                View Details <i class="fa-solid fa-angle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Value Banner / Statistics -->
<div class="container">
    <div class="stats-banner">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $totalProps; ?>+</div>
                <div class="stat-label">Active Listings</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $totalUsers; ?>+</div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $totalPremium; ?></div>
                <div class="stat-label">Featured Properties</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">0%</div>
                <div class="stat-label">Zero Brokerage Fee</div>
            </div>
        </div>
    </div>
</div>

<!-- Latest Properties Section -->
<section class="section">
    <div class="container">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag">Recently Listed</span>
                <h2 class="section-title">Explore Latest Homes for Rent</h2>
                <p class="section-subtitle">Fresh listings verified and updated daily.</p>
            </div>
            <a href="properties.php" class="btn btn-secondary btn-sm">
                Explore Full Directory <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="properties-grid">
            <?php foreach ($latestProperties as $prop): ?>
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
                        <div class="property-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($prop['location'] . ', ' . $prop['city']); ?></span>
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
                            <span class="badge badge-role"><?php echo htmlspecialchars($prop['furnishing']); ?></span>
                            <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-outline btn-sm">
                                Details <i class="fa-solid fa-angle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section" style="background: #ffffff; border-top: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header text-center">
            <span class="section-tag">Simple & Transparent</span>
            <h2 class="section-title">How RentNear Works</h2>
            <p class="section-subtitle">Connecting renters and owners in 3 effortless steps.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h4 class="step-title">Discover & Filter</h4>
                <p class="step-desc">Search by location, BHK configuration, rent range, and amenities to find your ideal home.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h4 class="step-title">Contact Owner Directly</h4>
                <p class="step-desc">Reach out via instant WhatsApp, direct phone call, or send inquiry messages with zero brokerage.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h4 class="step-title">Move In & Enjoy</h4>
                <p class="step-desc">Schedule a physical visit, verify rental terms, and settle into your new home smoothly.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="section" style="background: linear-gradient(135deg, #312e81 0%, #4f46e5 100%); color: #fff; text-align: center;">
    <div class="container">
        <h2 style="color: #fff; font-size: 2.3rem; margin-bottom: 1rem;">Are You a Property Owner?</h2>
        <p style="color: #e0e7ff; max-width: 600px; margin: 0 auto 2rem; font-size: 1.1rem;">
            List your flat or villa on RentNear in under 2 minutes. Upgrade to Premium for just ₹99 to get 3x more tenant inquiries!
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="register.php?role=owner" class="btn btn-premium btn-lg">
                <i class="fa-solid fa-crown"></i> Post Free Property Listing
            </a>
            <a href="login.php" class="btn btn-secondary btn-lg">
                <i class="fa-solid fa-user-tie"></i> Owner Login
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
