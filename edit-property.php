<?php
// edit-property.php - Edit Existing Property Listing
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

$propId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propId <= 0) {
    header("Location: owner-dashboard.php");
    exit;
}

// Fetch property and verify ownership (allow admin override)
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute([':id' => $propId]);
$property = $stmt->fetch();

if (!$property || ($property['owner_id'] != $user['id'] && $user['role'] !== 'admin')) {
    set_flash_message('error', 'Unauthorized access: You can only edit your own listings.');
    header("Location: owner-dashboard.php");
    exit;
}

$error = '';
$currentAmenities = !empty($property['amenities']) ? explode(',', $property['amenities']) : [];

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
    $status        = sanitize($_POST['status'] ?? 'available');
    $imageUrlInput = sanitize($_POST['image_url'] ?? '');
    $amenitiesArr  = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
    $amenities     = implode(',', array_map('sanitize', $amenitiesArr));

    $finalImagePath = !empty($imageUrlInput) ? $imageUrlInput : $property['image'];
    $tenant_preference = sanitize($_POST['tenant_preference'] ?? 'Bachelors Allowed');

    // Handle File Upload if provided
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

    $tenant_preference = sanitize($_POST['tenant_preference'] ?? 'Bachelors Allowed');
    $stay_duration     = sanitize($_POST['stay_duration'] ?? '1 Month (Short Stay Allowed)');
    $landmark          = sanitize($_POST['landmark'] ?? '');
    $latitude          = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude         = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    if (empty($latitude) || empty($longitude)) {
        $resolvedCoords = get_property_coordinates($location, $city, $propId);
        $latitude  = $resolvedCoords['lat'];
        $longitude = $resolvedCoords['lng'];
    }

    if (empty($title) || empty($city) || empty($location) || $price <= 0) {
        $error = 'Please fill out all required fields with valid values.';
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE properties SET
                title = :title,
                description = :description,
                property_type = :property_type,
                furnishing = :furnishing,
                location = :location,
                city = :city,
                landmark = :landmark,
                latitude = :latitude,
                longitude = :longitude,
                price = :price,
                deposit = :deposit,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                area_sqft = :area_sqft,
                image = :image,
                amenities = :amenities,
                tenant_preference = :tenant_preference,
                stay_duration = :stay_duration,
                status = :status
            WHERE id = :id
        ");

        $updateStmt->execute([
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
            ':stay_duration'     => $stay_duration,
            ':status'            => $status,
            ':id'                => $propId
        ]);

        set_flash_message('success', 'Property listing updated successfully!');
        header("Location: owner-dashboard.php");
        exit;
    }
}

$page_title = "Edit Property - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <div class="mb-4">
            <a href="owner-dashboard.php" style="font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1 style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Edit Property Listing</h1>
            <p style="color: var(--text-muted);">Update details, change rental terms, or modify images.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="background: #fff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 2.25rem; box-shadow: var(--shadow-md);">
            <form action="edit-property.php?id=<?php echo $propId; ?>" method="POST" enctype="multipart/form-data">
                
                <!-- Basic Info -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary);">
                        1. Basic Property Details
                    </h4>

                    <div class="form-group">
                        <label>Property Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Property Description <span class="text-danger">*</span></label>
                        <textarea name="description" rows="4" class="form-control" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Property Type</label>
                            <select name="property_type" class="form-select" required>
                                <?php foreach (['Single Room', '1 Room Set', 'Shared Room', 'PG Room', '1 BHK', '2 BHK', '3 BHK', 'Villa', 'Studio'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $property['property_type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Furnishing Status</label>
                            <select name="furnishing" class="form-select" required>
                                <?php foreach (['Furnished', 'Semi-Furnished', 'Unfurnished'] as $f): ?>
                                    <option value="<?php echo $f; ?>" <?php echo $property['furnishing'] === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Listing Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>Rented Out</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Preferred Tenant Type <span class="text-danger">*</span></label>
                            <?php $currentPref = $property['tenant_preference'] ?? 'Bachelors Allowed'; ?>
                            <select name="tenant_preference" class="form-select" required>
                                <option value="Bachelors Allowed" <?php echo ($currentPref === 'Bachelors Allowed') ? 'selected' : ''; ?>>🎓 Bachelors Allowed (Students & Working)</option>
                                <option value="Family Only" <?php echo ($currentPref === 'Family Only') ? 'selected' : ''; ?>>👨‍👩‍👧 Family Only</option>
                                <option value="Anyone" <?php echo ($currentPref === 'Anyone') ? 'selected' : ''; ?>>👥 Anyone (Family or Bachelors)</option>
                                <option value="Girls Only" <?php echo ($currentPref === 'Girls Only') ? 'selected' : ''; ?>>👩 Girls / Female Only</option>
                                <option value="Boys Only" <?php echo ($currentPref === 'Boys Only') ? 'selected' : ''; ?>>👨 Boys / Male Only</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Minimum Stay Duration <span class="text-danger">*</span></label>
                            <?php $currentStay = $property['stay_duration'] ?? '1 Month (Short Stay Allowed)'; ?>
                            <select name="stay_duration" class="form-select" required>
                                <option value="1 Month (Short Stay Allowed)" <?php echo (stripos($currentStay, '1 Month') !== false) ? 'selected' : ''; ?>>⏱️ 1 Month (Short Stay / Flexible)</option>
                                <option value="3 Months Minimum" <?php echo (stripos($currentStay, '3 Month') !== false) ? 'selected' : ''; ?>>⏱️ 3 Months Minimum</option>
                                <option value="6 Months Minimum" <?php echo (stripos($currentStay, '6 Month') !== false) ? 'selected' : ''; ?>>⏱️ 6 Months Minimum</option>
                                <option value="11 Months (Standard)" <?php echo (stripos($currentStay, '11 Month') !== false) ? 'selected' : ''; ?>>⏱️ 11 Months (Standard Agreement)</option>
                            </select>
                        </div>
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
                                <?php 
                                $cityList = ['Jamui', 'Patna', 'New Delhi', 'Pune', 'Bengaluru', 'Kota', 'Jaipur', 'Lucknow', 'Mumbai', 'Hyderabad', 'Gurugram', 'Noida', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Ranchi', 'Ahmedabad', 'Kolkata', 'Chennai', 'Chandigarh', 'Indore', 'Bhopal', 'Dehradun'];
                                foreach ($cityList as $c): 
                                ?>
                                    <option value="<?php echo $c; ?>" <?php echo (strtolower($property['city']) === strtolower($c)) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Area / Locality <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="editInputLocation" class="form-control" value="<?php echo htmlspecialchars($property['location']); ?>" required>
                        </div>
                    </div>

                    <?php 
                    $propCoords = get_property_coordinates($property['location'], $property['city'], $property['id']);
                    $curLat = !empty($property['latitude']) ? (float)$property['latitude'] : $propCoords['lat'];
                    $curLng = !empty($property['longitude']) ? (float)$property['longitude'] : $propCoords['lng'];
                    $curLandmark = $property['landmark'] ?? '';
                    ?>

                    <div class="form-group" style="margin-top: 0.75rem;">
                        <label>Nearby Landmark / Transit (Optional)</label>
                        <input type="text" name="landmark" class="form-control" value="<?php echo htmlspecialchars($curLandmark); ?>" placeholder="e.g. Near Malaypur Railway Station / Opposite City Hospital / 2 min from Metro">
                    </div>

                    <!-- Interactive Pinpoint Map Location Picker -->
                    <div style="margin-top: 1rem; background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <label style="font-weight: 700; color: var(--dark); font-size: 0.9rem; margin: 0;">
                                    <i class="fa-solid fa-map-pin text-danger"></i> Pin Exact Location on Map
                                </label>
                                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">Drag the pin or click on the map to fine-tune your room's location.</p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="detectEditOwnerGps()" style="font-size: 0.78rem; font-weight: 700; background: #ecfdf5; color: #059669; border-color: #a7f3d0;">
                                <i class="fa-solid fa-crosshairs"></i> Use My GPS Location
                            </button>
                        </div>

                        <div id="editPropertyPickerMap" style="height: 260px; width: 100%; border-radius: var(--radius-md); border: 1px solid #cbd5e1;"></div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.78rem; color: var(--text-muted);">
                            <span>Current GPS Coordinates: <strong id="editCoordsBadge" style="color: var(--primary);"><?php echo number_format($curLat, 4) . ', ' . number_format($curLng, 4); ?></strong></span>
                            <span style="color: #16a34a; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Location Active</span>
                        </div>

                        <input type="hidden" name="latitude" id="editLatitude" value="<?php echo htmlspecialchars($curLat); ?>">
                        <input type="hidden" name="longitude" id="editLongitude" value="<?php echo htmlspecialchars($curLng); ?>">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.25rem;">
                        <div class="form-group">
                            <label>Monthly Rent (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" value="<?php echo (int)$property['price']; ?>" min="500" step="500" required>
                        </div>

                        <div class="form-group">
                            <label>Security Deposit (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="deposit" class="form-control" value="<?php echo (int)$property['deposit']; ?>" min="0" step="1000" required>
                        </div>
                    </div>
                </div>

                <!-- Specifications & Images -->
                <div style="margin-bottom: 2rem; border-top: 1px solid var(--border-light); padding-top: 1.5rem;">
                    <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary);">
                        3. Specifications & Photos
                    </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" value="<?php echo $property['bedrooms']; ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" value="<?php echo $property['bathrooms']; ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Area (Sq. Ft.)</label>
                            <input type="number" name="area_sqft" class="form-control" value="<?php echo $property['area_sqft']; ?>" min="100">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Main Property Image URL</label>
                        <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($property['image']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Or Replace with New Local Image</label>
                        <input type="file" name="property_image" class="form-control" accept="image/*">
                    </div>

                    <div style="margin-top: 1rem;">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem;">Current Photo:</p>
                        <img src="<?php echo htmlspecialchars(get_property_image($property['image'])); ?>" alt="" style="max-height: 160px; border-radius: var(--radius-md); object-fit: cover;">
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
                            $checked = in_array($am, $currentAmenities);
                        ?>
                            <label class="form-check">
                                <input type="checkbox" name="amenities[]" value="<?php echo $am; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                <span><?php echo $am; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="owner-dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
let editPickerMap = null;
let editPickerMarker = null;

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
        let defaultLat = <?php echo $curLat; ?>;
        let defaultLng = <?php echo $curLng; ?>;

        editPickerMap = L.map('editPropertyPickerMap').setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors | RentNear'
        }).addTo(editPickerMap);

        // Add Draggable Marker
        editPickerMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(editPickerMap);
        editPickerMarker.bindPopup("📍 Drag pin or click on map to adjust exact location").openPopup();

        // Update inputs on drag
        editPickerMarker.on('dragend', function(e) {
            const pos = editPickerMarker.getLatLng();
            updateEditCoordinates(pos.lat, pos.lng);
        });

        // Click anywhere to move pin
        editPickerMap.on('click', function(e) {
            editPickerMarker.setLatLng(e.latlng);
            updateEditCoordinates(e.latlng.lat, e.latlng.lng);
            editPickerMarker.openPopup();
        });

        // Listen for City Dropdown Change
        const citySelect = document.querySelector('select[name="city"]');
        if (citySelect) {
            citySelect.addEventListener('change', () => {
                const selectedCity = citySelect.value;
                if (cityCoordinatesMap[selectedCity]) {
                    const coords = cityCoordinatesMap[selectedCity];
                    editPickerMap.flyTo(coords, 14);
                    editPickerMarker.setLatLng(coords);
                    updateEditCoordinates(coords[0], coords[1]);
                }
            });
        }
    }
});

function updateEditCoordinates(lat, lng) {
    document.getElementById('editLatitude').value = lat.toFixed(6);
    document.getElementById('editLongitude').value = lng.toFixed(6);
    document.getElementById('editCoordsBadge').textContent = lat.toFixed(4) + ', ' + lng.toFixed(4);
}

function detectEditOwnerGps() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            editPickerMap.flyTo([lat, lng], 16);
            editPickerMarker.setLatLng([lat, lng]);
            updateEditCoordinates(lat, lng);
            editPickerMarker.bindPopup("📍 GPS Location (Updated!)").openPopup();
        }, err => {
            alert("Could not access GPS. Please allow location permission in your browser.");
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
