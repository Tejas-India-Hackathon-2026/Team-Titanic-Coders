<?php
// add-property.php - List a New Rental Property
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = sanitize($_POST['title'] ?? '');
    $description   = sanitize($_POST['description'] ?? '');
    $property_type = sanitize($_POST['property_type'] ?? '2 BHK');
    $furnishing    = sanitize($_POST['furnishing'] ?? 'Semi-Furnished');
    $city          = sanitize($_POST['city'] ?? '');
    $location      = sanitize($_POST['location'] ?? '');
    $price         = (float)($_POST['price'] ?? 0);
    $deposit       = (float)($_POST['deposit'] ?? 0);
    $bedrooms      = (int)($_POST['bedrooms'] ?? 1);
    $bathrooms     = (int)($_POST['bathrooms'] ?? 1);
    $area_sqft     = (int)($_POST['area_sqft'] ?? 500);
    $imageUrlInput = sanitize($_POST['image_url'] ?? '');
    $amenitiesArr  = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
    $amenities     = implode(',', array_map('sanitize', $amenitiesArr));
    $upgradeNow    = isset($_POST['upgrade_to_premium']) && $_POST['upgrade_to_premium'] == '1';

    // Handle File Upload if provided
    $finalImagePath = $imageUrlInput;

    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['property_image']['tmp_name'];
        $fileName = $_FILES['property_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'prop_' . uniqid() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $finalImagePath = 'uploads/' . $newFileName;
            }
        }
    }

    // Default fallback image if none provided
    if (empty($finalImagePath)) {
        $finalImagePath = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80';
    }

    $tenant_preference = sanitize($_POST['tenant_preference'] ?? 'Bachelors Allowed');
    $landmark          = sanitize($_POST['landmark'] ?? '');
    $latitude          = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude         = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    if (empty($latitude) || empty($longitude)) {
        $resolvedCoords = get_property_coordinates($location, $city);
        $latitude  = $resolvedCoords['lat'];
        $longitude = $resolvedCoords['lng'];
    }

    if (empty($title) || empty($city) || empty($location) || $price <= 0) {
        $error = 'Please fill out all required fields with valid price details.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO properties (
                owner_id, title, description, property_type, furnishing,
                location, city, landmark, latitude, longitude, price, deposit, bedrooms, bathrooms,
                area_sqft, image, amenities, tenant_preference, is_premium, views_count, status
            ) VALUES (
                :owner_id, :title, :description, :property_type, :furnishing,
                :location, :city, :landmark, :latitude, :longitude, :price, :deposit, :bedrooms, :bathrooms,
                :area_sqft, :image, :amenities, :tenant_preference, :is_premium, 0, 'available'
            )
        ");

        $stmt->execute([
            ':owner_id'          => $user['id'],
            ':title'             => $title,
            ':description'       => $description,
            ':property_type'     => $property_type,
            ':furnishing'        => $furnishing,
            ':location'          => $location,
            ':city'              => $city,
            ':landmark'          => $landmark,
            ':latitude'          => $latitude,
            ':longitude'         => $longitude,
            ':price'             => $price,
            ':deposit'           => $deposit,
            ':bedrooms'          => $bedrooms,
            ':bathrooms'         => $bathrooms,
            ':area_sqft'         => $area_sqft,
            ':image'             => $finalImagePath,
            ':amenities'         => $amenities,
            ':tenant_preference' => $tenant_preference,
            ':is_premium'        => 0 // Default 0 until paid
        ]);

        $newPropId = $pdo->lastInsertId();

        if ($upgradeNow) {
            header("Location: payment.php?property_id=" . $newPropId);
            exit;
        } else {
            set_flash_message('success', 'Your property listing was published successfully!');
            header("Location: owner-dashboard.php");
            exit;
        }
    }
}

$page_title = "Post New Property - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <div class="mb-4">
            <a href="owner-dashboard.php" style="font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1 style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">List a New Rental Property</h1>
            <p style="color: var(--text-muted);">Reach verified renters looking for homes in your city.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="background: #fff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 2.25rem; box-shadow: var(--shadow-md);">
            <form action="add-property.php" method="POST" enctype="multipart/form-data">
                
                <!-- Basic Property Info -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary);">
                        1. Basic Property Details
                    </h4>

                    <div class="form-group">
                        <label>Property Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Spacious 2 BHK Gated Society Flat in Indiranagar" required>
                    </div>

                    <div class="form-group">
                        <label>Property Description <span class="text-danger">*</span></label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Describe layout, nearby landmarks, sunlight, ventilation, and house rules..." required></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Property / Room Type <span class="text-danger">*</span></label>
                            <select name="property_type" class="form-select" required>
                                <option value="Single Room">🛏️ Single Local Room</option>
                                <option value="1 Room Set">🏠 1 Room Set (1 RK)</option>
                                <option value="Shared Room">👥 Shared Room (Bed Space)</option>
                                <option value="PG Room">🍛 PG Room (With Food/Mess)</option>
                                <option value="1 BHK">🚪 1 BHK Flat</option>
                                <option value="2 BHK" selected>🛋️ 2 BHK Apartment</option>
                                <option value="3 BHK">🏢 3 BHK Apartment</option>
                                <option value="Villa">🏡 Independent Villa / House</option>
                                <option value="Studio">💻 Studio Apartment</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Furnishing Status <span class="text-danger">*</span></label>
                            <select name="furnishing" class="form-select" required>
                                <option value="Furnished">Fully Furnished</option>
                                <option value="Semi-Furnished" selected>Semi-Furnished</option>
                                <option value="Unfurnished">Unfurnished</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Preferred Tenant Type <span class="text-danger">*</span></label>
                        <select name="tenant_preference" class="form-select" required>
                            <option value="Bachelors Allowed" selected>🎓 Bachelors Allowed (Students & Working)</option>
                            <option value="Family Only">👨‍👩‍👧 Family Only</option>
                            <option value="Anyone">👥 Anyone (Family or Bachelors)</option>
                            <option value="Girls Only">👩 Girls / Female Only</option>
                            <option value="Boys Only">👨 Boys / Male Only</option>
                        </select>
                    </div>
                </div>

                <!-- Location & Financials -->
                <div style="margin-bottom: 2rem; border-top: 1px solid var(--border-light); padding-top: 1.5rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary);">
                        2. Location & Pricing
                    </h4>

                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <select name="city" class="form-select" required>
                                <option value="">Select City / District</option>
                                <option value="Jamui">Jamui (Bihar)</option>
                                <option value="Patna">Patna</option>
                                <option value="New Delhi">New Delhi</option>
                                <option value="Bengaluru">Bengaluru</option>
                                <option value="Pune">Pune</option>
                                <option value="Mumbai">Mumbai</option>
                                <option value="Hyderabad">Hyderabad</option>
                                <option value="Gurugram">Gurugram</option>
                                <option value="Noida">Noida</option>
                                <option value="Kota">Kota</option>
                                <option value="Jaipur">Jaipur</option>
                                <option value="Patna">Patna</option>
                                <option value="Lucknow">Lucknow</option>
                                <option value="Ahmedabad">Ahmedabad</option>
                                <option value="Kolkata">Kolkata</option>
                                <option value="Chennai">Chennai</option>
                                <option value="Chandigarh">Chandigarh</option>
                                <option value="Indore">Indore</option>
                                <option value="Bhopal">Bhopal</option>
                                <option value="Ranchi">Ranchi</option>
                                <option value="Dehradun">Dehradun</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Area / Locality / Street <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="inputLocation" class="form-control" placeholder="e.g. K.K.M College Road / Station Road / Indiranagar" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 0.75rem;">
                        <label>Nearby Landmark / Transit (Optional)</label>
                        <input type="text" name="landmark" class="form-control" placeholder="e.g. Near Malaypur Railway Station / Opposite City Hospital / 2 min from Metro">
                    </div>

                    <!-- Interactive Pinpoint Map Location Picker -->
                    <div style="margin-top: 1rem; background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <label style="font-weight: 700; color: var(--dark); font-size: 0.9rem; margin: 0;">
                                    <i class="fa-solid fa-map-pin text-danger"></i> Pin Exact Location on Map
                                </label>
                                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">Click anywhere on the map or drag the pin to set where renters will find your room.</p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="detectOwnerGps()" style="font-size: 0.78rem; font-weight: 700; background: #ecfdf5; color: #059669; border-color: #a7f3d0;">
                                <i class="fa-solid fa-crosshairs"></i> Detect My Current GPS Location
                            </button>
                        </div>

                        <div id="addPropertyPickerMap" style="height: 260px; width: 100%; border-radius: var(--radius-md); border: 1px solid #cbd5e1;"></div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.78rem; color: var(--text-muted);">
                            <span>Selected GPS Coordinates: <strong id="coordsBadge" style="color: var(--primary);">24.9213, 86.2234</strong></span>
                            <span style="color: #16a34a; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Pinpoint Ready</span>
                        </div>

                        <input type="hidden" name="latitude" id="inputLatitude" value="24.9213">
                        <input type="hidden" name="longitude" id="inputLongitude" value="86.2234">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.25rem;">
                        <div class="form-group">
                            <label>Monthly Rent (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" placeholder="e.g. 35000" min="500" step="500" required>
                        </div>

                        <div class="form-group">
                            <label>Security Deposit (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="deposit" class="form-control" placeholder="e.g. 70000" min="0" step="1000" required>
                        </div>
                    </div>
                </div>

                <!-- Specifications & Photos -->
                <div style="margin-bottom: 2rem; border-top: 1px solid var(--border-light); padding-top: 1.5rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary);">
                        3. Specifications & Photos
                    </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" value="2" min="1" max="10">
                        </div>
                        <div class="form-group">
                            <label>Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" value="2" min="1" max="10">
                        </div>
                        <div class="form-group">
                            <label>Area (Sq. Ft.)</label>
                            <input type="number" name="area_sqft" class="form-control" value="1150" min="100">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Main Property Image URL (or upload below)</label>
                        <input type="url" name="image_url" id="imageUrlInput" class="form-control" placeholder="https://images.unsplash.com/photo-...">
                    </div>

                    <div class="form-group">
                        <label>Or Upload Photo from Computer</label>
                        <input type="file" name="property_image" id="imageFileInput" class="form-control" accept="image/*">
                    </div>

                    <!-- Live Image Preview Box -->
                    <div id="imagePreviewContainer" style="display: none; margin-top: 1rem; border-radius: var(--radius-md); overflow: hidden; max-height: 250px; border: 1px solid var(--border-color);">
                        <img id="imagePreview" src="" alt="Property Preview" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>

                <!-- Amenities Checklist -->
                <div style="margin-bottom: 2rem; border-top: 1px solid var(--border-light); padding-top: 1.5rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--primary);">
                        4. Amenities Available
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.75rem;">
                        <?php 
                        $allAmenities = ['WiFi', 'Attached Washroom', 'Bed & Mattress', 'Study Table', 'RO Water', 'Mess/Food Included', 'AC', 'Separate Sub-Meter', 'Covered Parking', 'Lift', '24/7 Security', 'Power Backup', 'Balcony', 'Geyser'];
                        foreach ($allAmenities as $am):
                        ?>
                            <label class="form-check">
                                <input type="checkbox" name="amenities[]" value="<?php echo $am; ?>" checked>
                                <span><?php echo $am; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ⭐ Premium Listing Upgrade Promo Box -->
                <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #fde68a; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #f59e0b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.1rem; font-weight: 800; color: #92400e; margin-bottom: 0.35rem;">
                                Upgrade to Premium Featured Listing for ₹99
                            </h4>
                            <p style="font-size: 0.85rem; color: #78350f; margin-bottom: 0.75rem;">
                                Premium listings receive a ⭐ Featured Ribbon, top search rank, and get up to 3x more calls and WhatsApp inquiries from tenants.
                            </p>
                            <label class="form-check" style="color: #92400e; font-weight: 700; cursor: pointer;">
                                <input type="checkbox" name="upgrade_to_premium" value="1" checked style="accent-color: #d97706; transform: scale(1.2);">
                                <span>Proceed directly to ₹99 Mock Payment after listing</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="owner-dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-check"></i> Publish Property Listing
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Image Preview Script
const urlInput = document.getElementById('imageUrlInput');
const fileInput = document.getElementById('imageFileInput');
const previewContainer = document.getElementById('imagePreviewContainer');
const previewImg = document.getElementById('imagePreview');

if (urlInput) {
    urlInput.addEventListener('input', () => {
        if (urlInput.value.startsWith('http')) {
            previewImg.src = urlInput.value;
            previewContainer.style.display = 'block';
        }
    });
}

if (fileInput) {
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImg.src = event.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Location Picker Leaflet Map Script
let pickerMap = null;
let pickerMarker = null;

const cityCoordinatesMap = {
    'Jamui': [24.9213, 86.2234],
    'Patna': [25.5941, 85.1376],
    'New Delhi': [28.6139, 77.2090],
    'Bengaluru': [12.9716, 77.5946],
    'Pune': [18.5204, 73.8567],
    'Kota': [25.1800, 75.8300],
    'Jaipur': [26.9124, 75.7873],
    'Lucknow': [26.8467, 80.9462],
    'Mumbai': [19.0760, 72.8777],
    'Hyderabad': [17.3850, 78.4867],
    'Gurugram': [28.4595, 77.0266],
    'Noida': [28.5355, 77.3910]
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof L !== 'undefined') {
        let defaultLat = 24.9213;
        let defaultLng = 86.2234;

        pickerMap = L.map('addPropertyPickerMap').setView([defaultLat, defaultLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors | RentNear'
        }).addTo(pickerMap);

        // Add Draggable Marker
        pickerMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(pickerMap);
        pickerMarker.bindPopup("📍 Drag pin or click anywhere on map to set property location").openPopup();

        // Update inputs on drag
        pickerMarker.on('dragend', function(e) {
            const pos = pickerMarker.getLatLng();
            updateCoordinates(pos.lat, pos.lng);
        });

        // Click anywhere to move pin
        pickerMap.on('click', function(e) {
            pickerMarker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
            pickerMarker.openPopup();
        });

        // Listen for City Dropdown Change
        const citySelect = document.querySelector('select[name="city"]');
        if (citySelect) {
            citySelect.addEventListener('change', () => {
                const selectedCity = citySelect.value;
                if (cityCoordinatesMap[selectedCity]) {
                    const coords = cityCoordinatesMap[selectedCity];
                    pickerMap.flyTo(coords, 14);
                    pickerMarker.setLatLng(coords);
                    updateCoordinates(coords[0], coords[1]);
                }
            });
        }
    }
});

function updateCoordinates(lat, lng) {
    document.getElementById('inputLatitude').value = lat.toFixed(6);
    document.getElementById('inputLongitude').value = lng.toFixed(6);
    document.getElementById('coordsBadge').textContent = lat.toFixed(4) + ', ' + lng.toFixed(4);
}

function detectOwnerGps() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            pickerMap.flyTo([lat, lng], 16);
            pickerMarker.setLatLng([lat, lng]);
            updateCoordinates(lat, lng);
            pickerMarker.bindPopup("📍 GPS Detected Location (Updated!)").openPopup();
        }, err => {
            alert("Could not access GPS. Please allow location permission in your browser.");
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
