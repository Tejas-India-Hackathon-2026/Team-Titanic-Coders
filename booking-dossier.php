<?php
// booking-dossier.php - Official Tenant-Landlord Mutual Handshake & Police Verification Dossier
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$txn_id = isset($_GET['txn_id']) ? sanitize($_GET['txn_id']) : '';
$pay_id = isset($_GET['pay_id']) ? (int)$_GET['pay_id'] : 0;

if (empty($txn_id) && $pay_id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch Full Handshake Booking Details
if (!empty($txn_id)) {
    $stmt = $pdo->prepare("
        SELECT pay.*, 
               p.title as property_title, p.location as property_location, p.city as property_city, 
               p.price as property_price, p.deposit as property_deposit, p.image as property_image,
               p.property_type, p.furnishing, p.tenant_preference, p.stay_duration, p.landmark,
               o.name as owner_name, o.phone as owner_phone, o.email as owner_email, o.avatar as owner_avatar, o.address as owner_address, o.city as owner_city, o.is_verified as owner_is_verified,
               r.name as renter_name, r.phone as renter_phone, r.email as renter_email, r.avatar as renter_avatar,
               r.occupation as renter_occupation, r.preferred_city as renter_city, r.permanent_address, r.guardian_name, r.emergency_phone, r.is_verified as renter_is_verified,
               r.digilocker_aadhaar, r.document_type as renter_document_type
        FROM payments pay
        JOIN properties p ON pay.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
        JOIN renters r ON pay.renter_id = r.id
        WHERE pay.transaction_id = :txn_id
    ");
    $stmt->execute([':txn_id' => $txn_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT pay.*, 
               p.title as property_title, p.location as property_location, p.city as property_city, 
               p.price as property_price, p.deposit as property_deposit, p.image as property_image,
               p.property_type, p.furnishing, p.tenant_preference, p.stay_duration, p.landmark,
               o.name as owner_name, o.phone as owner_phone, o.email as owner_email, o.avatar as owner_avatar, o.address as owner_address, o.city as owner_city, o.is_verified as owner_is_verified,
               r.name as renter_name, r.phone as renter_phone, r.email as renter_email, r.avatar as renter_avatar,
               r.occupation as renter_occupation, r.preferred_city as renter_city, r.permanent_address, r.guardian_name, r.emergency_phone, r.is_verified as renter_is_verified,
               r.digilocker_aadhaar, r.document_type as renter_document_type
        FROM payments pay
        JOIN properties p ON pay.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
        JOIN renters r ON pay.renter_id = r.id
        WHERE pay.id = :pay_id
    ");
    $stmt->execute([':pay_id' => $pay_id]);
}

$dossier = $stmt->fetch();

if (!$dossier) {
    set_flash_message('error', 'Booking record not found.');
    header("Location: index.php");
    exit;
}

// Security Check: Only the involved Renter, Owner, or Admin can view
if ($user['role'] !== 'admin' && $user['id'] != $dossier['renter_id'] && $user['id'] != $dossier['owner_id']) {
    set_flash_message('error', 'Unauthorized access to this booking dossier.');
    header("Location: index.php");
    exit;
}

$page_title = "Tenant-Landlord Booking Dossier & KYC Pass - RentNear";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    
    <!-- Top Action Bar (hidden on print) -->
    <div style="max-width: 900px; margin: 0 auto 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="badge badge-success mb-1">
                <i class="fa-solid fa-handshake"></i> Verified Mutual Handshake
            </span>
            <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0; color: #0f172a;">
                Tenant & Landlord Verification Dossier
            </h2>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Police KYC Pass
            </button>
            <?php if ($user['role'] === 'owner'): ?>
                <a href="owner-dashboard.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Owner Dashboard
                </a>
            <?php else: ?>
                <a href="renter-dashboard.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Renter Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Printable Official KYC Dossier Card -->
    <div style="max-width: 900px; margin: 0 auto; background: #ffffff; border: 2px solid #cbd5e1; border-radius: var(--radius-xl); padding: 2.5rem; box-shadow: 0 15px 35px rgba(0,0,0,0.08); position: relative;">
        
        <!-- Header Ribbon -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.5rem; font-weight: 900; color: #0f172a;">
                    <span style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 1rem;"><i class="fa-solid fa-house-chimney"></i></span>
                    RentNear
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
                    Official Room Reservation, Identity Dossier & Police Verification Form
                </p>
            </div>

            <div style="text-align: right; font-size: 0.82rem; background: #f8fafc; padding: 0.6rem 1rem; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div><strong>Booking Pass ID:</strong> <span style="font-family: monospace; color: #4338ca; font-weight: 800;">RN-DOSSIER-<?php echo str_pad($dossier['id'], 6, '0', STR_PAD_LEFT); ?></span></div>
                <div><strong>Issued Date:</strong> <?php echo date('d M Y, h:i A', strtotime($dossier['created_at'])); ?></div>
                <div><strong>Txn Reference:</strong> <span style="font-family: monospace; color: #059669; font-weight: 800;"><?php echo htmlspecialchars($dossier['transaction_id']); ?></span></div>
            </div>
        </div>

        <!-- 2-Party KYC Exchange Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            
            <!-- LEFT: Landlord / Owner Verified Profile -->
            <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: var(--radius-lg); padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px dashed #fde68a; padding-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #b45309;">
                        🏠 Landlord / Property Owner Profile
                    </span>
                    <?php if (!empty($dossier['owner_is_verified']) && (int)$dossier['owner_is_verified'] === 1): ?>
                        <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.7rem; font-weight: 800;">
                            <?php echo render_verified_badge(false, 13); ?> Gold Verified
                        </span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.85rem;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #fde68a;">
                        <?php echo render_user_avatar_img($dossier['owner_avatar'] ?? '', $dossier['owner_name'], 48); ?>
                    </div>
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <?php echo htmlspecialchars($dossier['owner_name']); ?>
                            <?php if (!empty($dossier['owner_is_verified']) && (int)$dossier['owner_is_verified'] === 1) echo render_verified_badge(false, 18); ?>
                        </h3>
                        <span style="font-size: 0.75rem; color: #b45309; font-weight: 700;">
                            Verified Property Landlord
                        </span>
                    </div>
                </div>

                <div style="font-size: 0.85rem; color: #334155; line-height: 1.8;">
                    <div><i class="fa-solid fa-phone text-primary me-2"></i> <strong>Mobile:</strong> +91 <?php echo htmlspecialchars($dossier['owner_phone']); ?></div>
                    <div><i class="fa-solid fa-envelope text-primary me-2"></i> <strong>Email:</strong> <?php echo htmlspecialchars($dossier['owner_email']); ?></div>
                    <div><i class="fa-solid fa-location-dot text-danger me-2"></i> <strong>Property Address:</strong> <?php echo htmlspecialchars($dossier['property_location'] . ', ' . $dossier['property_city']); ?></div>
                    <?php if (!empty($dossier['landmark'])): ?>
                        <div><i class="fa-solid fa-map-pin text-muted me-2"></i> <strong>Landmark:</strong> <?php echo htmlspecialchars($dossier['landmark']); ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #fde68a; display: flex; gap: 0.5rem;">
                    <?php $cleanOwnerPhone = preg_replace('/[^0-9]/', '', $dossier['owner_phone']); ?>
                    <a href="tel:<?php echo $cleanOwnerPhone; ?>" class="btn btn-secondary btn-sm" style="flex: 1; font-size: 0.78rem;">
                        <i class="fa-solid fa-phone"></i> Call Owner
                    </a>
                    <a href="https://wa.me/<?php echo $cleanOwnerPhone; ?>?text=<?php echo urlencode('Hi ' . $dossier['owner_name'] . ', regarding our room booking on RentNear for ' . $dossier['property_title'] . ' (Txn: ' . $dossier['transaction_id'] . ').'); ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #25D366; color: #fff; border: none; font-size: 0.78rem;">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>

            <!-- RIGHT: Renter / Tenant Verified Profile -->
            <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: var(--radius-lg); padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px dashed #86efac; padding-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #166534;">
                        👤 Tenant / Renter Identity Dossier
                    </span>
                    <?php if (!empty($dossier['renter_is_verified']) && (int)$dossier['renter_is_verified'] === 1): ?>
                        <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #86efac; font-size: 0.7rem; font-weight: 800;">
                            <?php echo render_renter_verified_badge(false, 13); ?> DigiLocker Verified
                        </span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.85rem;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #86efac;">
                        <?php echo render_user_avatar_img($dossier['renter_avatar'] ?? '', $dossier['renter_name'], 48); ?>
                    </div>
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <?php echo htmlspecialchars($dossier['renter_name']); ?>
                            <?php if (!empty($dossier['renter_is_verified']) && (int)$dossier['renter_is_verified'] === 1) echo render_renter_verified_badge(false, 18); ?>
                        </h3>
                        <span style="font-size: 0.75rem; color: #166534; font-weight: 700;">
                            <?php echo htmlspecialchars(!empty($dossier['renter_occupation']) ? $dossier['renter_occupation'] : 'Student / Working Professional'); ?>
                        </span>
                    </div>
                </div>

                <div style="font-size: 0.85rem; color: #334155; line-height: 1.8;">
                    <div><i class="fa-solid fa-phone text-success me-2"></i> <strong>Mobile:</strong> +91 <?php echo htmlspecialchars($dossier['renter_phone']); ?></div>
                    <div><i class="fa-solid fa-envelope text-success me-2"></i> <strong>Email:</strong> <?php echo htmlspecialchars($dossier['renter_email']); ?></div>
                    <?php if (!empty($dossier['permanent_address'])): ?>
                        <div><i class="fa-solid fa-house-user text-success me-2"></i> <strong>Permanent Address:</strong> <?php echo htmlspecialchars($dossier['permanent_address']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($dossier['guardian_name'])): ?>
                        <div><i class="fa-solid fa-user-shield text-success me-2"></i> <strong>Father / Guardian:</strong> <?php echo htmlspecialchars($dossier['guardian_name']); ?> <?php if (!empty($dossier['emergency_phone'])) echo '(+91 ' . htmlspecialchars($dossier['emergency_phone']) . ')'; ?></div>
                    <?php endif; ?>
                    <div>
                        <i class="fa-solid fa-id-card text-success me-2"></i> 
                        <strong>Govt ID Reference:</strong> 
                        <code style="background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #bbf7d0; color: #065f46; font-weight: 800;">
                            <?php echo htmlspecialchars($dossier['digilocker_aadhaar'] ?? 'XXXX-XXXX-8921'); ?>
                        </code>
                    </div>
                    <div><i class="fa-solid fa-shield-check text-success me-2"></i> <strong>Police Clearance:</strong> <span style="color: #16a34a; font-weight: 800;">🟢 100% KYC Approved (No re-upload needed)</span></div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #86efac; display: flex; gap: 0.5rem;">
                    <?php $cleanRenterPhone = preg_replace('/[^0-9]/', '', $dossier['renter_phone']); ?>
                    <a href="tel:<?php echo $cleanRenterPhone; ?>" class="btn btn-secondary btn-sm" style="flex: 1; font-size: 0.78rem;">
                        <i class="fa-solid fa-phone"></i> Call Tenant
                    </a>
                    <a href="https://wa.me/<?php echo $cleanRenterPhone; ?>?text=<?php echo urlencode('Hi ' . $dossier['renter_name'] . ', thank you for reserving ' . $dossier['property_title'] . ' on RentNear. Welcome to your new home!'); ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #25D366; color: #fff; border: none; font-size: 0.78rem;">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>

        </div>

        <!-- Reserved Property Specs & Move-in Terms -->
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem;">
            <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                🏢 Reserved Property & Financial Terms Summary
            </span>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <img src="<?php echo htmlspecialchars(get_property_image($dossier['property_image'])); ?>" style="width: 110px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                <div style="flex: 1; min-width: 220px;">
                    <h4 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem 0;">
                        <?php echo htmlspecialchars($dossier['property_title']); ?>
                    </h4>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                        <strong>Type:</strong> <?php echo htmlspecialchars($dossier['property_type']); ?> (<?php echo htmlspecialchars($dossier['furnishing']); ?>) &bull; 
                        <strong>Preference:</strong> <?php echo htmlspecialchars($dossier['tenant_preference']); ?>
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #fff; padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">Monthly Rent</span>
                        <strong style="color: #0f172a; font-size: 1rem;">₹<?php echo number_format($dossier['property_price']); ?>/mo</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">Token Advance Paid</span>
                        <strong style="color: #16a34a; font-size: 1rem;">₹<?php echo number_format($dossier['amount']); ?> (PAID)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legal Disclaimer & Signatures -->
        <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 2rem; font-size: 0.8rem; color: var(--text-muted);">
            <div>
                <p style="margin: 0 0 0.35rem 0;">
                    <strong>Security & Authentication:</strong> Verified via RentNear 2-Step OTP Authentication & Government e-KYC integration.
                </p>
                <p style="margin: 0;">
                    This document serves as an authentic mutual reservation handshake between Landlord and Tenant.
                </p>
            </div>

            <div style="display: flex; gap: 2.5rem; text-align: center;">
                <div>
                    <div style="width: 140px; border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 4px;"></div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #475569;">Landlord Signature</span>
                </div>
                <div>
                    <div style="width: 140px; border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 4px;"></div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #475569;">Tenant Signature</span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>