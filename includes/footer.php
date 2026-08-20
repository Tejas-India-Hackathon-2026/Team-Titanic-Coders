</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <div class="brand-logo mb-3" style="color: #fff;">
                    <div class="logo-icon">
                        <i class="fa-solid fa-house-chimney"></i>
                    </div>
                    <span>Rent<span class="accent-text" style="color: #818cf8;">Near</span></span>
                </div>
                <p>
                    Connecting verified property owners directly with reliable tenants. Browse rental flats, studios, and luxury villas across major cities with zero hassle.
                </p>
                <div class="mt-3" style="font-size: 0.85rem; color: #fbbf24; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-bolt"></i> <strong>₹99 Premium Listing Upgrade Demo Active</strong>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h5>Discover</h5>
                <ul class="footer-links">
                    <li><a href="explore-map.php" style="font-weight: 700; color: #6366f1;">🗺️ Live Explore Map</a></li>
                    <li><a href="properties.php">Explore All Properties</a></li>
                    <li><a href="properties.php?type=Single+Room">Single Local Rooms</a></li>
                    <li><a href="properties.php?type=1+Room+Set">1 Room Sets (1 RK)</a></li>
                    <li><a href="properties.php?type=1+BHK">1 BHK Flats</a></li>
                    <li><a href="properties.php?type=2+BHK">2 BHK Apartments</a></li>
                </ul>
            </div>

            <!-- Top Cities -->
            <div class="footer-col">
                <h5>Popular Cities</h5>
                <ul class="footer-links">
                    <li><a href="explore-map.php?city=Jamui">📍 Jamui Rooms on Map</a></li>
                    <li><a href="properties.php?city=New+Delhi">New Delhi</a></li>
                    <li><a href="properties.php?city=Pune">Pune</a></li>
                    <li><a href="properties.php?city=Kota">Kota</a></li>
                    <li><a href="properties.php?city=Bengaluru">Bengaluru</a></li>
                </ul>
            </div>

            <!-- Demo Quick Accounts & Info -->
            <div class="footer-col">
                <h5>Demo Accounts</h5>
                <p style="font-size: 0.8rem; margin-bottom: 0.8rem;">Click to test different roles instantly:</p>
                <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.8rem;">
                    <div><span class="badge badge-info">Owner</span> <code>owner@rentnear.com</code> / <code>owner123</code></div>
                    <div><span class="badge badge-success">Renter</span> <code>renter@rentnear.com</code> / <code>renter123</code></div>
                    <div><span class="badge badge-warning">Admin</span> <code>admin@rentnear.com</code> / <code>admin123</code></div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <strong>RentNear</strong> – Online Property Rental Platform. Built for Hackathon Demo.</p>
            <p style="color: #64748b;">Mock Payment Mode Enabled (₹99 Featured Listing Simulation)</p>
        </div>
    </div>
</footer>

<!-- App-Style Mobile Bottom Navigation Bar (Visible on Mobile Screens) -->
<?php 
$current_script = basename($_SERVER['PHP_SELF']);
$logged_user = function_exists('current_user') ? current_user() : null;
?>
<nav class="mobile-bottom-nav">
    <?php if (is_logged_in() && $logged_user['role'] === 'admin'): ?>
        <a href="admin-dashboard.php" class="mobile-nav-item <?php echo $current_script === 'admin-dashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high text-danger"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin-dashboard.php#propertiesTable" class="mobile-nav-item">
            <i class="fa-solid fa-building"></i>
            <span>Properties</span>
        </a>
        <a href="admin-dashboard.php#usersTable" class="mobile-nav-item">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>
        <a href="profile.php" class="mobile-nav-item <?php echo $current_script === 'profile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-shield"></i>
            <span>Profile</span>
        </a>
        <a href="logout.php" class="mobile-nav-item" style="color: #ef4444;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    <?php else: ?>
        <a href="index.php" class="mobile-nav-item <?php echo $current_script === 'index.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>

        <a href="explore-map.php" class="mobile-nav-item <?php echo $current_script === 'explore-map.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>Map</span>
        </a>

        <a href="<?php echo is_logged_in() ? ($logged_user['role'] === 'owner' ? 'add-property.php' : 'owner-dashboard.php') : 'login.php?redirect=add-property.php'; ?>" class="mobile-nav-item post-btn-item">
            <div class="post-circle">
                <i class="fa-solid fa-plus"></i>
            </div>
            <span>Post Ad</span>
        </a>

        <?php if (is_logged_in()): ?>
            <?php if ($logged_user['role'] === 'owner'): ?>
                <a href="owner-dashboard.php" class="mobile-nav-item <?php echo $current_script === 'owner-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Portal</span>
                </a>
            <?php elseif ($logged_user['role'] === 'renter'): ?>
                <a href="renter-dashboard.php" class="mobile-nav-item <?php echo $current_script === 'renter-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-heart"></i>
                    <span>Saved</span>
                </a>
            <?php endif; ?>

            <a href="profile.php" class="mobile-nav-item <?php echo $current_script === 'profile.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i>
                <span>Profile</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="mobile-nav-item <?php echo $current_script === 'login.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-heart"></i>
                <span>Saved</span>
            </a>
            <a href="login.php" class="mobile-nav-item <?php echo $current_script === 'login.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</nav>

<!-- Leaflet.js Interactive Map Engine -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- JavaScript Bundle -->
<script src="assets/js/main.js"></script>
<?php if (isset($extra_js)): ?>
    <script src="<?php echo htmlspecialchars($extra_js); ?>"></script>
<?php endif; ?>

</body>
</html>
