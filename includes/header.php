<?php
// includes/header.php - Global Header & Navigation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - RentNear' : 'RentNear – Online Property Rental Platform'; ?></title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Leaflet.js Interactive Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($extra_css); ?>">
    <?php endif; ?>
</head>
<body>

<header class="site-header">
    <div class="container">
        <nav class="navbar">
            <!-- Brand Logo -->
            <?php if (is_logged_in() && $user['role'] === 'admin'): ?>
                <!-- Admin Role Brand Logo -->
                <a href="admin-dashboard.php" class="brand-logo" style="gap: 0.45rem;">
                    <div class="logo-icon" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); width: 34px; height: 34px; font-size: 0.95rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span>Rent<span class="accent-text" style="color: #dc2626;">Near</span></span>
                    <span style="font-size: 0.68rem; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: 800; border: 1px solid #fca5a5; margin-left: 2px;">ADMIN</span>
                </a>

                <!-- Dedicated Admin Navigation Links -->
                <ul class="nav-links" id="navLinks">
                    <li>
                        <a href="admin-dashboard.php" class="<?php echo $current_page === 'admin-dashboard.php' ? 'active' : ''; ?>" style="font-weight: 700;">
                            <i class="fa-solid fa-gauge-high me-1 text-danger"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="admin-dashboard.php#propertiesTable" style="font-weight: 600;">
                            <i class="fa-solid fa-building me-1"></i> Properties
                        </a>
                    </li>
                    <li>
                        <a href="admin-dashboard.php#usersTable" style="font-weight: 600;">
                            <i class="fa-solid fa-users me-1"></i> Users
                        </a>
                    </li>
                    <li>
                        <a href="admin-dashboard.php#revenueTable" style="font-weight: 600;">
                            <i class="fa-solid fa-chart-line me-1"></i> Revenue
                        </a>
                    </li>
                    <li>
                        <a href="index.php" target="_blank" style="font-weight: 600; color: #0284c7;">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Live Site
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <!-- Standard Public / User Brand Logo -->
                <a href="index.php" class="brand-logo">
                    <div class="logo-icon">
                        <i class="fa-solid fa-house-chimney"></i>
                    </div>
                    <span>Rent<span class="accent-text">Near</span></span>
                </a>

                <!-- Standard Navigation Links -->
                <ul class="nav-links" id="navLinks">
                    <li>
                        <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-compass me-1"></i> Home
                        </a>
                    </li>
                    <li>
                        <a href="properties.php" class="<?php echo $current_page === 'properties.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-building me-1"></i> Properties
                        </a>
                    </li>
                    <li>
                        <a href="explore-map.php" class="<?php echo $current_page === 'explore-map.php' ? 'active' : ''; ?>" style="font-weight: 700;">
                            <i class="fa-solid fa-map-location-dot me-1 text-primary"></i> Explore Map
                            <span style="background: #ef4444; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 1px 5px; border-radius: 10px; margin-left: 3px; vertical-align: middle;">LIVE</span>
                        </a>
                    </li>
                    <li>
                        <a href="about.php" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                            About Us
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                            Contact
                        </a>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <?php if ($user['role'] === 'owner'): ?>
                            <li>
                                <a href="owner-dashboard.php" class="<?php echo $current_page === 'owner-dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fa-solid fa-chart-pie me-1"></i> Owner Portal
                                </a>
                            </li>
                        <?php elseif ($user['role'] === 'renter'): ?>
                            <li>
                                <a href="renter-dashboard.php" class="<?php echo $current_page === 'renter-dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fa-solid fa-heart me-1"></i> Saved Listings
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <!-- Navigation Action Buttons / User Profile Dropdown -->
            <div class="nav-actions">
                <?php if (!is_logged_in()): ?>
                    <a href="login.php" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus"></i> Registration
                    </a>
                <?php else: ?>
                    <?php if ($user['role'] === 'owner'): ?>
                        <a href="add-property.php" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-plus"></i> Post Property
                        </a>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <a href="logout.php" class="btn btn-danger btn-sm" style="background: #dc2626; border-color: #b91c1c; font-weight: 700; font-size: 0.8rem; padding: 0.35rem 0.75rem;" title="Sign Out of Admin">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                        </a>
                    <?php endif; ?>

                    <!-- User Profile Dropdown -->
                    <div class="user-dropdown">
                        <button class="user-avatar-btn" id="userDropdownBtn" type="button" onclick="toggleUserDropdown(event)" style="<?php echo $user['role'] === 'admin' ? 'border-color: #fca5a5; background: #fff5f5;' : ''; ?>">
                            <div class="user-avatar" style="<?php echo $user['role'] === 'admin' ? 'background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);' : ''; ?>">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <span style="font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px;">
                                <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>
                                <?php if ($user['role'] === 'owner' && !empty($user['is_verified']) && (int)$user['is_verified'] === 1) echo render_verified_badge(false, 14); ?>
                            </span>
                            <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; color: var(--text-muted);"></i>
                        </button>

                        <div class="dropdown-menu" id="userDropdownMenu">
                            <div class="dropdown-header">
                                <h5 style="display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                    <?php if ($user['role'] === 'owner' && !empty($user['is_verified']) && (int)$user['is_verified'] === 1) echo render_verified_badge(false, 15); ?>
                                </h5>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                <div style="display: flex; gap: 4px; align-items: center; margin-top: 4px; flex-wrap: wrap;">
                                    <span class="badge badge-role"><?php echo ucfirst($user['role']); ?> Account</span>
                                    <?php if ($user['role'] === 'owner'): ?>
                                        <?php if (!empty($user['is_verified']) && (int)$user['is_verified'] === 1): ?>
                                            <span class="badge badge-success" style="font-size: 0.68rem;">⭐ Gold Verified</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="font-size: 0.68rem;">Standard Owner</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($user['role'] === 'owner'): ?>
                                <a href="owner-dashboard.php" class="dropdown-item">
                                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                                </a>
                                <a href="add-property.php" class="dropdown-item">
                                    <i class="fa-solid fa-circle-plus"></i> Add New Property
                                </a>
                                <?php if (empty($user['is_verified']) || (int)$user['is_verified'] !== 1): ?>
                                    <a href="verify-owner.php" class="dropdown-item" style="color: #b45309; font-weight: 700; background: #fffbeb;">
                                        <i class="fa-solid fa-crown text-warning"></i> Get Golden Tick (₹199)
                                    </a>
                                <?php else: ?>
                                    <a href="verify-receipt.php" class="dropdown-item" style="color: #15803d; font-weight: 700;">
                                        <i class="fa-solid fa-certificate text-success"></i> VIP Certificate
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($user['role'] === 'renter'): ?>
                                <a href="renter-dashboard.php" class="dropdown-item">
                                    <i class="fa-solid fa-bookmark"></i> My Wishlist & Inquiries
                                </a>
                            <?php elseif ($user['role'] === 'admin'): ?>
                                <a href="admin-dashboard.php" class="dropdown-item">
                                    <i class="fa-solid fa-gear"></i> Admin Dashboard
                                </a>
                            <?php endif; ?>

                            <a href="profile.php" class="dropdown-item">
                                <i class="fa-solid fa-user-pen"></i> Edit Profile
                            </a>

                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item" style="color: var(--danger);">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </nav>
    </div>
</header>

<main>
    <div class="container mt-3">
        <?php display_flash_message(); ?>
    </div>
