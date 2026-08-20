<?php
// contact.php - Contact & FAQs
$page_title = "Contact & FAQs - RentNear";
require_once __DIR__ . '/includes/header.php';

$messageSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $messageSent = true;
}
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 3rem;">
            <span class="section-tag">Help & Support</span>
            <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 0.75rem;">Get in Touch & Frequently Asked Questions</h1>
            <p style="color: var(--text-muted); font-size: 1.05rem;">Have questions about listing your home or finding rentals? We are here to help.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 2.5rem; align-items: start;">
            
            <!-- FAQs Accordion List -->
            <div>
                <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1.25rem;">Frequently Asked Questions</h3>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <h5 style="font-weight: 700; color: var(--dark); margin-bottom: 0.4rem;">
                            <i class="fa-solid fa-circle-question text-primary me-1"></i> How does the ₹99 Premium Upgrade work?
                        </h5>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                            When an owner upgrades a property to Premium for ₹99, the listing receives an eye-catching golden ribbon badge, top placement in all search queries, and 60 days of prioritized visibility.
                        </p>
                    </div>

                    <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <h5 style="font-weight: 700; color: var(--dark); margin-bottom: 0.4rem;">
                            <i class="fa-solid fa-circle-question text-primary me-1"></i> Is there any brokerage fee for renters?
                        </h5>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                            No. RentNear connects renters directly with verified homeowners via direct phone calls, WhatsApp chat, and message inquiries with 0% brokerage.
                        </p>
                    </div>

                    <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <h5 style="font-weight: 700; color: var(--dark); margin-bottom: 0.4rem;">
                            <i class="fa-solid fa-circle-question text-primary me-1"></i> What payment methods are simulated in the demo?
                        </h5>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                            The mock payment gateway allows testing UPI QR code scanning, Debit/Credit card authorization, and NetBanking with instant automated invoice generation.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;">Send Us a Message</h3>

                <?php if ($messageSent): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle me-1"></i> Thank you! Your support ticket has been recorded.
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <input type="hidden" name="send_contact" value="1">
                    
                    <div class="form-group">
                        <label>Your Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Rahul Sharma" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="rahul@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="Listing Query / Feedback" required>
                    </div>

                    <div class="form-group">
                        <label>Message <span class="text-danger">*</span></label>
                        <textarea name="message" rows="4" class="form-control" placeholder="Write your message here..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

        </div>

        <!-- Office & Helpdesk Live Map Section -->
        <div style="margin-top: 3.5rem;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <span class="section-tag"><i class="fa-solid fa-location-dot text-primary"></i> Visit Us</span>
                <h3 style="font-size: 1.4rem; font-weight: 800;">Our Support Center & Office Location</h3>
            </div>
            <div style="width: 100%; height: 320px; border-radius: var(--radius-xl); overflow: hidden; border: 1px solid var(--border-color); box-shadow: var(--shadow-md);">
                <iframe 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    loading="lazy" 
                    allowfullscreen 
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://maps.google.com/maps?q=Connaught+Place+New+Delhi&t=&z=14&ie=UTF8&iwloc=&output=embed">
                </iframe>
            </div>
        </div>

    </div>
</div>

<style>
@media (max-width: 768px) {
    .container > div > div[style*="grid-template-columns: 1.1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
s