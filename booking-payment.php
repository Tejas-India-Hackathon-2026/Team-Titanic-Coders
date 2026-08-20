<?php
// booking-payment.php - Renter Room Booking & Token Advance Payment Gateway
$page_title = "Reserve & Book Room - Token Advance Payment";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$inquiry_id  = isset($_GET['inquiry_id']) ? (int)$_GET['inquiry_id'] : 0;

if ($property_id <= 0) {
    set_flash_message('error', 'Please select a valid property to book.');
    header("Location: properties.php");
    exit;
}

// Fetch Property & Owner Details
$stmt = $pdo->prepare("
    SELECT p.*, o.name as owner_name, o.phone as owner_phone, o.email as owner_email 
    FROM properties p 
    JOIN owners o ON p.owner_id = o.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch();

if (!$property) {
    set_flash_message('error', 'Property listing not found.');
    header("Location: properties.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    
    <div style="text-align: center; margin-bottom: 2.25rem;">
        <span class="badge badge-success mb-2" style="font-size: 0.82rem; padding: 0.35rem 0.85rem;">
            <i class="fa-solid fa-shield-check"></i> 100% Secure & Refundable Token Booking
        </span>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.4rem;">
            Reserve & Book Your Room Online
        </h1>
        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto; font-size: 0.95rem;">
            Pay a small token advance to lock this room directly with the landlord and stop further visits from other applicants.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 2rem; max-width: 1060px; margin: 0 auto;">
        
        <!-- Left Column: Property Summary & Token Amount Selection -->
        <div>
            <!-- Property Preview Card -->
            <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-house-chimney text-primary"></i> Room Details
                </h4>

                <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.25rem;">
                    <img src="<?php echo htmlspecialchars(get_property_image($property['image'])); ?>" alt="" style="width: 110px; height: 85px; object-fit: cover; border-radius: 12px; flex-shrink: 0; box-shadow: var(--shadow-xs);">
                    <div style="flex: 1; min-width: 0;">
                        <span class="badge badge-info mb-1" style="font-size: 0.7rem;"><?php echo htmlspecialchars($property['property_type']); ?></span>
                        <h5 style="font-size: 1rem; font-weight: 800; line-height: 1.3; margin: 0 0 0.3rem 0; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($property['title']); ?>
                        </h5>
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                            <i class="fa-solid fa-location-dot text-danger"></i> <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?>
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; background: var(--bg-alt); padding: 1rem; border-radius: 12px; font-size: 0.85rem;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">Monthly Rent</span>
                        <strong style="color: var(--primary); font-size: 1.05rem;">₹<?php echo number_format($property['price']); ?>/mo</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">Security Deposit</span>
                        <strong style="color: #0f172a; font-size: 1.05rem;">₹<?php echo number_format($property['deposit']); ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">Furnishing</span>
                        <strong><?php echo htmlspecialchars($property['furnishing']); ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">Owner / Landlord</span>
                        <strong style="display: inline-flex; align-items: center; gap: 3px;"><?php echo htmlspecialchars($property['owner_name']); ?> <?php echo render_verified_badge(false, 14); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Token Amount Selection Card -->
            <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; color: #0f172a;">
                    Select Token Advance Amount
                </h4>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem;">
                    This amount will be deducted from your first month's rent upon moving in.
                </p>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Option 1: ₹1,000 Standard (Recommended) -->
                    <label class="token-option-card selected" onclick="selectTokenAmount(1000, this)">
                        <input type="radio" name="token_choice" value="1000" checked style="display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <span class="badge badge-success" style="font-size: 0.68rem; margin-bottom: 3px;">⭐ MOST POPULAR</span>
                                <div style="font-weight: 800; font-size: 1rem; color: #0f172a;">₹1,000 - Standard Room Booking Token</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Blocks room for 7 days & initiates landlord agreement</div>
                            </div>
                            <div class="check-circle"><i class="fa-solid fa-check"></i></div>
                        </div>
                    </label>

                    <!-- Option 2: ₹500 Quick Hold -->
                    <label class="token-option-card" onclick="selectTokenAmount(500, this)">
                        <input type="radio" name="token_choice" value="500" style="display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: #0f172a;">₹500 - 48-Hour Priority Hold</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Temporary lock while you finalize your visit/shift</div>
                            </div>
                            <div class="check-circle"><i class="fa-solid fa-check"></i></div>
                        </div>
                    </label>

                    <!-- Option 3: Full Month Rent -->
                    <label class="token-option-card" onclick="selectTokenAmount(<?php echo (float)$property['price']; ?>, this)">
                        <input type="radio" name="token_choice" value="<?php echo (float)$property['price']; ?>" style="display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: #0f172a;">₹<?php echo number_format($property['price']); ?> - Complete 1st Month Rent</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Direct instant move-in ready confirmation</div>
                            </div>
                            <div class="check-circle"><i class="fa-solid fa-check"></i></div>
                        </div>
                    </label>
                </div>

                <!-- RentNear Trust Badges -->
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-light); display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-check text-success"></i> <strong>100% Refundable:</strong> Full refund if the landlord rejects your application.
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-receipt text-primary"></i> <strong>Instant Official Receipt:</strong> Downloadable verified payment certificate.
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-lock text-warning"></i> <strong>Zero Brokerage Guarantee:</strong> No agent commissions charged.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Interactive Mock Payment Gateway -->
        <div>
            <div style="background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-lg); position: sticky; top: 90px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0; color: #0f172a;">Payment Method</h3>
                    <span class="badge badge-info" style="font-size: 0.72rem;"><i class="fa-solid fa-flask"></i> Mock Gateway</span>
                </div>

                <!-- Payment Tabs -->
                <div class="payment-tabs-nav">
                    <button type="button" class="pay-tab-btn active" onclick="switchPayTab('upi', this)">
                        <i class="fa-brands fa-google-pay"></i> UPI / QR
                    </button>
                    <button type="button" class="pay-tab-btn" onclick="switchPayTab('card', this)">
                        <i class="fa-solid fa-credit-card"></i> Card
                    </button>
                    <button type="button" class="pay-tab-btn" onclick="switchPayTab('netbanking', this)">
                        <i class="fa-solid fa-building-columns"></i> NetBanking
                    </button>
                </div>

                <!-- Tab 1: UPI / QR Code -->
                <div id="tabContentUpi" class="pay-tab-content active">
                    <div style="text-align: center; padding: 1rem 0;">
                        <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 1.25rem; display: inline-block; margin-bottom: 0.75rem;">
                            <!-- Mock QR Code SVG -->
                            <svg width="140" height="140" viewBox="0 0 100 100" style="display: block; margin: 0 auto;">
                                <rect width="100" height="100" fill="#ffffff"/>
                                <path d="M10 10h30v30h-30z M50 10h10v10h-10z M70 10h20v20h-20z M60 20h10v10h-10z M20 20h10v10h-10z M80 20h10v10h-10z M10 50h10v10h-10z M30 50h20v10h-20z M60 50h30v30h-30z M70 60h10v10h-10z M10 70h30v20h-30z M20 80h10v10h-10z M50 70h10v20h-10z M80 80h10v10h-10z" fill="#0f172a"/>
                            </svg>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #4338ca; display: block; margin-top: 6px;">
                                Scan with any UPI App
                            </span>
                        </div>

                        <div style="display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 1rem;">
                            <span style="font-size: 0.75rem; font-weight: 700; background: #eef2ff; color: #4338ca; padding: 4px 8px; border-radius: 6px;">GPay</span>
                            <span style="font-size: 0.75rem; font-weight: 700; background: #f5f3ff; color: #6d28d9; padding: 4px 8px; border-radius: 6px;">PhonePe</span>
                            <span style="font-size: 0.75rem; font-weight: 700; background: #eff6ff; color: #0284c7; padding: 4px 8px; border-radius: 6px;">Paytm</span>
                            <span style="font-size: 0.75rem; font-weight: 700; background: #ecfdf5; color: #047857; padding: 4px 8px; border-radius: 6px;">BHIM</span>
                        </div>

                        <div class="form-group" style="text-align: left;">
                            <label style="font-size: 0.78rem;">Or Enter UPI ID / VPA</label>
                            <input type="text" id="upiIdInput" class="form-control" placeholder="yourname@okhdfcbank" value="<?php echo htmlspecialchars($user['email'] ? explode('@', $user['email'])[0] . '@upi' : 'renter@upi'); ?>" style="font-size: 0.88rem;">
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Credit / Debit Card -->
                <div id="tabContentCard" class="pay-tab-content" style="display: none;">
                    <div style="padding: 0.75rem 0;">
                        <div class="form-group">
                            <label style="font-size: 0.78rem;">Card Number</label>
                            <input type="text" id="cardNumberInput" class="form-control" placeholder="4532 •••• •••• 8892" value="4532 8901 2345 6789" maxlength="19">
                        </div>

                        <div class="form-group">
                            <label style="font-size: 0.78rem;">Cardholder Name</label>
                            <input type="text" id="cardNameInput" class="form-control" placeholder="Full Name on Card" value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div class="form-group">
                                <label style="font-size: 0.78rem;">Expiry (MM/YY)</label>
                                <input type="text" id="cardExpiryInput" class="form-control" placeholder="12/28" value="08/29">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.78rem;">CVV</label>
                                <input type="password" id="cardCvvInput" class="form-control" placeholder="•••" value="789" maxlength="4">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: NetBanking -->
                <div id="tabContentNetbanking" class="pay-tab-content" style="display: none;">
                    <div style="padding: 0.75rem 0;">
                        <label style="font-size: 0.78rem; font-weight: 700; margin-bottom: 0.5rem; display: block;">Select Your Bank</label>
                        <select id="bankSelect" class="form-select" style="font-size: 0.88rem; margin-bottom: 1rem;">
                            <option value="HDFC">HDFC Bank</option>
                            <option value="SBI">State Bank of India (SBI)</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="Axis">Axis Bank</option>
                            <option value="Kotak">Kotak Mahindra Bank</option>
                            <option value="PNB">Punjab National Bank</option>
                        </select>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">
                            You will be redirected to simulated banking portal for instant authorization.
                        </p>
                    </div>
                </div>

                <!-- Order Summary Breakdown -->
                <div style="background: var(--bg-alt); padding: 1rem; border-radius: 12px; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem;">
                        <span style="color: var(--text-muted);">Token Advance:</span>
                        <strong id="displayTokenAmt">₹1,000.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem;">
                        <span style="color: var(--text-muted);">Platform Fee:</span>
                        <span style="color: var(--success); font-weight: 700;">₹0.00 (FREE)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem;">
                        <span style="color: var(--text-muted);">GST (18%):</span>
                        <span style="color: var(--success); font-weight: 700;">₹0.00 (Waived)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 1px solid var(--border-color); padding-top: 0.5rem; margin-top: 0.5rem;">
                        <span>Total Payable:</span>
                        <span style="color: var(--primary);" id="displayTotalAmt">₹1,000.00</span>
                    </div>
                </div>

                <!-- Action Button -->
                <button type="button" id="payBtn" class="btn btn-primary btn-lg" style="width: 100%; font-size: 1rem; padding: 0.85rem;" onclick="processBookingPayment()">
                    <i class="fa-solid fa-lock"></i> <span id="payBtnLabel">Pay ₹1,000 & Reserve Room</span>
                </button>

                <p style="text-align: center; font-size: 0.74rem; color: var(--text-muted); margin-top: 0.75rem; margin-bottom: 0;">
                    🔒 256-Bit Encrypted Simulated Transaction
                </p>
            </div>
        </div>

    </div>

</div>

<style>
.token-option-card {
    background: #ffffff;
    border: 1.5px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
}

.token-option-card:hover {
    border-color: var(--primary);
    background: #f8fafc;
}

.token-option-card.selected {
    border-color: var(--primary);
    background: var(--primary-light);
    box-shadow: 0 0 0 2px var(--primary);
}

.check-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: transparent;
    flex-shrink: 0;
}

.token-option-card.selected .check-circle {
    background: var(--primary);
    border-color: var(--primary);
    color: #ffffff;
}

/* Payment Tabs */
.payment-tabs-nav {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.4rem;
    background: var(--bg-alt);
    padding: 4px;
    border-radius: 10px;
    margin-bottom: 1.25rem;
}

.pay-tab-btn {
    background: transparent;
    border: none;
    padding: 0.5rem 0.2rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.pay-tab-btn.active {
    background: #ffffff;
    color: var(--primary);
    box-shadow: var(--shadow-xs);
}

@media (max-width: 860px) {
    div[style*="grid-template-columns: 1.15fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
let selectedAmount = 1000;
let currentPaymentMethod = 'UPI';
const propertyId = <?php echo (int)$property['id']; ?>;
const inquiryId = <?php echo (int)$inquiry_id; ?>;

function selectTokenAmount(amt, el) {
    selectedAmount = amt;
    document.querySelectorAll('.token-option-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;

    const formatted = '₹' + Number(amt).toLocaleString('en-IN');
    document.getElementById('displayTokenAmt').textContent = formatted + '.00';
    document.getElementById('displayTotalAmt').textContent = formatted + '.00';
    document.getElementById('payBtnLabel').textContent = 'Pay ' + formatted + ' & Reserve Room';
}

function switchPayTab(tab, btn) {
    currentPaymentMethod = tab.toUpperCase();
    document.querySelectorAll('.pay-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.pay-tab-content').forEach(c => c.style.display = 'none');
    if (tab === 'upi') document.getElementById('tabContentUpi').style.display = 'block';
    if (tab === 'card') document.getElementById('tabContentCard').style.display = 'block';
    if (tab === 'netbanking') document.getElementById('tabContentNetbanking').style.display = 'block';
}

async function processBookingPayment() {
    const payBtn = document.getElementById('payBtn');
    const originalText = payBtn.innerHTML;

    payBtn.disabled = true;
    payBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authorizing Payment with Bank...';

    try {
        const response = await fetch('api/process_booking_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                property_id: propertyId,
                inquiry_id: inquiryId,
                amount: selectedAmount,
                payment_method: currentPaymentMethod
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            payBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Payment Confirmed! Generating Receipt...';
            setTimeout(() => {
                window.location.href = 'booking-receipt.php?txn_id=' + encodeURIComponent(data.transaction_id);
            }, 1000);
        } else {
            alert('Payment authorization failed: ' + (data.message || 'Unknown error.'));
            payBtn.disabled = false;
            payBtn.innerHTML = originalText;
        }
    } catch (err) {
        alert('Network error while processing payment. Please try again.');
        payBtn.disabled = false;
        payBtn.innerHTML = originalText;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
