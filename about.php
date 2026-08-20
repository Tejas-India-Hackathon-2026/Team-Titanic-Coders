<?php
// about.php - About RentNear Project
$page_title = "About RentNear - Online Property Rental Platform";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div style="max-width: 840px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 3rem;">
            <span class="section-tag">Project Overview</span>
            <h1 style="font-size: 2.4rem; font-weight: 800; margin-bottom: 1rem;">About RentNear</h1>
            <p style="font-size: 1.15rem; color: var(--text-muted); line-height: 1.7;">
                An end-to-end digital property rental platform designed to connect property owners with potential renters directly, eliminating brokerage fees while providing premium listing visibility.
            </p>
        </div>

        <div style="background: #fff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 2.5rem; box-shadow: var(--shadow-md); margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">
                <i class="fa-solid fa-bullseye me-2"></i> Problem Statement & Solution
            </h3>
            <p style="color: var(--dark-muted); line-height: 1.8; margin-bottom: 1.5rem;">
                Traditional property searching often involves scattered listings, heavy broker commissions, lack of verified owner contact info, and low visibility for newly posted properties. 
                <strong>RentNear</strong> solves these challenges by providing a unified, transparent marketplace where renters can filter by exact budget and amenities, and owners can boost their listings using an affordable <strong>₹99 Premium Featured Upgrade</strong>.
            </p>

            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">
                <i class="fa-solid fa-layer-group me-2"></i> System Architecture & Key Modules
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
                <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md);">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;"><i class="fa-solid fa-user-shield text-primary me-1"></i> Multi-Role Auth</h5>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Secure session management with Bcrypt password hashing for Owners, Renters, and Administrators.</p>
                </div>
                <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md);">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;"><i class="fa-solid fa-magnifying-glass text-primary me-1"></i> Search & Filters</h5>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Multi-facet filtering across City, Rent range, BHK type, Furnishing, and specific amenities.</p>
                </div>
                <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md);">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;"><i class="fa-solid fa-crown text-warning me-1"></i> ₹99 Mock Payment</h5>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Interactive payment gateway demo supporting UPI QR, Cards, and NetBanking with automated invoice receipts.</p>
                </div>
                <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md);">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;"><i class="fa-solid fa-database text-primary me-1"></i> Dual-Database PDO</h5>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Zero-configuration SQLite setup with companion MySQL export schema for XAMPP deployment.</p>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #fff; border-radius: var(--radius-xl); padding: 2.5rem; text-align: center;">
            <h3 style="color: #fff; font-size: 1.5rem; margin-bottom: 0.75rem;">“Find Your Next Place with RentNear.”</h3>
            <p style="color: #c7d2fe; font-size: 0.95rem; margin-bottom: 1.5rem;">Explore available listings or post your rental property today.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="properties.php" class="btn btn-primary">Browse Properties</a>
                <a href="register.php?role=owner" class="btn btn-secondary">Post Property</a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
