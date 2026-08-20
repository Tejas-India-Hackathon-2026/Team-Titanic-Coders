<?php
// payment.php - Mock Payment Gateway Checkout (₹99 Premium Listing Upgrade)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_owner();
$user = current_user();

$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($property_id <= 0) {
    header("Location: owner-dashboard.php");
    exit;
}

// Fetch property and verify ownership
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch();

if (!$property || ($property['owner_id'] != $user['id'] && $user['role'] !== 'admin')) {
    set_flash_message('error', 'Unauthorized: You can only upgrade properties you own.');
    header("Location: owner-dashboard.php");
    exit;
}

// If already premium, notify owner
$isAlreadyPremium = (int)$property['is_premium'] === 1;

$page_title = "Upgrade to Premium Featured - RentNear";
$extra_css = "assets/css/payment.css";
$extra_js  = "assets/js/payment.js";
require_once __DIR__ . '/includes/header.php';
?>

<div class="payment-page-container">
    
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="badge badge-premium mb-2"><i class="fa-solid fa-crown"></i> Premium Booster</span>
        <h1 style="font-size: 2.1rem; font-weight: 800;">Upgrade Your Property Listing</h1>
        <p style="color: var(--text-muted); max-width: 540px; margin: 0.25rem auto 0;">
            Maximize your reach, get top priority placement in search, and attract tenants 3x faster for only ₹99.
        </p>
    </div>

    <?php if ($isAlreadyPremium): ?>
        <div class="alert alert-success mb-4" style="text-align: center;">
            <i class="fa-solid fa-circle-check me-2"></i> This property already has an <strong>Active ⭐ Premium Featured Badge</strong>! You may renew or re-boost below.
        </div>
    <?php endif; ?>

    <div class="payment-card-layout">
        
        <!-- Left Summary Column -->
        <div class="order-summary-sidebar">
            <h3 class="order-summary-title">Upgrade Summary</h3>

            <div class="property-mini-preview">
                <img src="<?php echo htmlspecialchars(get_property_image($property['image'])); ?>" alt="">
                <div class="mini-preview-info">
                    <h5><?php echo htmlspecialchars($property['title']); ?></h5>
                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?></p>
                    <span style="font-weight: 700; color: var(--primary); font-size: 0.85rem;"><?php echo format_inr($property['price']); ?>/mo</span>
                </div>
            </div>

            <ul class="premium-perks-list">
                <li><i class="fa-solid fa-circle-check"></i> ⭐ Golden Featured Ribbon Badge</li>
                <li><i class="fa-solid fa-circle-check"></i> Top placement in search results</li>
                <li><i class="fa-solid fa-circle-check"></i> 60-day extended premium validity</li>
                <li><i class="fa-solid fa-circle-check"></i> Direct WhatsApp & Call lead boost</li>
                <li><i class="fa-solid fa-circle-check"></i> Priority customer support</li>
            </ul>

            <div class="price-breakdown">
                <div class="price-row">
                    <span>Premium 60-Day Boost</span>
                    <span>₹99.00</span>
                </div>
                <div class="price-row">
                    <span>Platform GST / Tax (Demo)</span>
                    <span style="color: var(--success);">₹0.00 (Waived)</span>
                </div>
                <div class="price-row total">
                    <span>Total Amount</span>
                    <span style="color: var(--primary);">₹99.00</span>
                </div>
            </div>
        </div>

        <!-- Right Payment Gateway Column -->
        <div class="gateway-main">
            <div class="gateway-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Select Payment Method</h3>
                    <span class="mock-badge"><i class="fa-solid fa-flask"></i> Mock Gateway Demo</span>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Safe & secure simulated payment for hackathon demonstration. No real money is charged.
                </p>
            </div>

            <!-- Payment Tabs -->
            <div class="payment-tabs">
                <button type="button" class="tab-btn active" data-tab="tabUpi">
                    <i class="fa-solid fa-qrcode"></i> UPI / QR
                </button>
                <button type="button" class="tab-btn" data-tab="tabCard">
                    <i class="fa-solid fa-credit-card"></i> Card
                </button>
                <button type="button" class="tab-btn" data-tab="tabNetBanking">
                    <i class="fa-solid fa-building-columns"></i> Net Banking
                </button>
            </div>

            <!-- Tab 1: UPI & QR Code -->
            <div class="tab-content active" id="tabUpi">
                <form class="mock-pay-form" data-property-id="<?php echo $property_id; ?>">
                    <div class="upi-qr-card">
                        <div class="qr-code-box">
                            <!-- SVG QR Code Representation -->
                            <svg viewBox="0 0 100 100" width="100%" height="100%">
                                <rect width="100" height="100" fill="#fff"/>
                                <path d="M10 10h30v30h-30z M15 15h20v20h-20z M20 20h10v10h-10z" fill="#0f172a"/>
                                <path d="M60 10h30v30h-30z M65 15h20v20h-20z M70 20h10v10h-10z" fill="#0f172a"/>
                                <path d="M10 60h30v30h-30z M15 65h20v20h-20z M20 70h10v10h-10z" fill="#0f172a"/>
                                <path d="M45 15h5v15h-5z M50 35h15v5h-15z M45 45h10v10h-10z M60 45h10v10h-10z M75 45h15v10h-15z M45 60h5v30h-5z M55 60h10v5h-10z M70 65h10v15h-10z M85 60h5v10h-5z M55 75h10v15h-10z M80 80h10v10h-10z" fill="#4f46e5"/>
                            </svg>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--dark-muted); margin-bottom: 0.5rem;">
                            Scan with Google Pay, PhonePe, Paytm or BHIM UPI
                        </p>
                        <div class="upi-id-badge">
                            <span>rentnear.mockpay@upi</span>
                            <i class="fa-solid fa-copy" style="cursor: pointer;" title="Copy UPI ID"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-premium btn-lg" style="width: 100%;">
                        <i class="fa-solid fa-lock"></i> Simulate Scan & Pay ₹99.00
                    </button>
                </form>
            </div>

            <!-- Tab 2: Credit / Debit Card -->
            <div class="tab-content" id="tabCard">
                <form class="mock-pay-form" data-property-id="<?php echo $property_id; ?>">
                    <div class="form-group">
                        <label style="font-size: 0.85rem;">Cardholder Name</label>
                        <input type="text" class="form-control" placeholder="Rajesh Sharma" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 0.85rem;">Card Number</label>
                        <div style="position: relative;">
                            <input type="text" id="cardNumber" class="form-control" placeholder="4532 8901 2345 6789" value="4532 8901 2345 6789" required>
                            <i class="fa-brands fa-cc-visa" style="position: absolute; right: 12px; top: 12px; font-size: 1.3rem; color: var(--primary);"></i>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label style="font-size: 0.85rem;">Expiry Date</label>
                            <input type="text" id="cardExpiry" class="form-control" placeholder="MM/YY" value="08/28" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem;">CVV</label>
                            <input type="password" class="form-control" placeholder="•••" maxlength="3" value="888" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-premium btn-lg" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-lock"></i> Pay ₹99.00 with Card
                    </button>
                </form>
            </div>

            <!-- Tab 3: Net Banking -->
            <div class="tab-content" id="tabNetBanking">
                <form class="mock-pay-form" data-property-id="<?php echo $property_id; ?>">
                    <div class="form-group mb-3">
                        <label style="font-size: 0.85rem;">Select Your Bank</label>
                        <select class="form-select" required>
                            <option value="HDFC">HDFC Bank</option>
                            <option value="SBI">State Bank of India</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="Axis">Axis Bank</option>
                            <option value="Kotak">Kotak Mahindra Bank</option>
                            <option value="PNB">Punjab National Bank</option>
                        </select>
                    </div>

                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--dark-muted); margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-info-circle text-primary me-1"></i> You will be securely redirected to your bank's mock portal to authorize ₹99.00.
                    </div>

                    <button type="submit" class="btn btn-premium btn-lg" style="width: 100%;">
                        <i class="fa-solid fa-lock"></i> Authorize & Pay ₹99.00
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- Realistic Bank Processing Overlay Modal -->
<div class="processing-modal" id="processingModal">
    <div class="processing-card">
        <div class="payment-spinner"></div>
        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.5rem;">Processing ₹99 Payment</h3>
        <p id="paymentStatusText" style="color: var(--text-muted); font-size: 0.95rem;">
            Connecting to Secure Banking Gateway...
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
