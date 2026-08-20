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
                price = :price,
                deposit = :deposit,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                area_sqft = :area_sqft,
                image = :image,
                amenities = :amenities,
                tenant_preference = :tenant_preference,
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
            ':price'             => $price,
            ':deposit'           => $deposit,
            ':bedrooms'          => $bedrooms,
            ':bathrooms'         => $bathrooms,
            ':area_sqft'         => $area_sqft,
            ':image'             => $finalImagePath,
            ':amenities'         => $amenities,
            ':tenant_preference' => $tenant_preference,
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
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($property['location']); ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
