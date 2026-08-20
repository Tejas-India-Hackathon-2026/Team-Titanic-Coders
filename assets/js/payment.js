/**
 * RentNear - Mock Payment Gateway Simulator (₹99 Premium Upgrade)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Payment Tabs Switcher (UPI, Card, NetBanking)
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    let currentPaymentMethod = 'UPI';

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-tab');
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            if (targetId === 'tabUpi') currentPaymentMethod = 'UPI';
            else if (targetId === 'tabCard') currentPaymentMethod = 'Card';
            else if (targetId === 'tabNetBanking') currentPaymentMethod = 'NetBanking';
        });
    });

    // 2. Card Number Auto-formatting
    const cardInput = document.getElementById('cardNumber');
    if (cardInput) {
        cardInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            val = val.substring(0, 16);
            let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
            e.target.value = formatted;
        });
    }

    // 3. Card Expiry Auto-formatting
    const expiryInput = document.getElementById('cardExpiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            val = val.substring(0, 4);
            if (val.length >= 2) {
                val = val.substring(0, 2) + '/' + val.substring(2);
            }
            e.target.value = val;
        });
    }

    // 4. Handle Mock Payment Submission
    const payForms = document.querySelectorAll('.mock-pay-form');
    const modal = document.getElementById('processingModal');
    const statusText = document.getElementById('paymentStatusText');

    payForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const propertyId = form.getAttribute('data-property-id');
            if (!propertyId) {
                alert('Property ID missing');
                return;
            }

            // Show realistic bank processing loader
            if (modal) modal.classList.add('show');
            if (statusText) statusText.textContent = 'Connecting to Secure Banking Gateway...';

            setTimeout(() => {
                if (statusText) statusText.textContent = 'Verifying Transaction & Authorizing ₹99.00...';
            }, 900);

            setTimeout(async () => {
                if (statusText) statusText.textContent = 'Activating ⭐ Premium Featured Status...';

                try {
                    const response = await fetch('api/process_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            amount: 99.00,
                            payment_method: currentPaymentMethod
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        setTimeout(() => {
                            window.location.href = `payment-success.php?txn_id=${encodeURIComponent(data.transaction_id)}`;
                        }, 600);
                    } else {
                        if (modal) modal.classList.remove('show');
                        alert(data.message || 'Payment simulation failed. Please try again.');
                    }
                } catch (err) {
                    console.error('Payment Error:', err);
                    if (modal) modal.classList.remove('show');
                    alert('Network error during payment processing.');
                }
            }, 1800);
        });
    });
});
