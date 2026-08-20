/**
 * RentNear - Main JavaScript Bundle
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. User Dropdown Menu
    const userBtn = document.getElementById('userDropdownBtn');
    const userMenu = document.getElementById('userDropdownMenu');

    if (userBtn && userMenu) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }

    // 2. Mobile Menu Drawer Toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            navLinks.classList.toggle('mobile-open');
            const icon = mobileToggle.querySelector('i');
            if (icon) {
                if (navLinks.classList.contains('mobile-open')) {
                    icon.className = 'fa-solid fa-xmark';
                } else {
                    icon.className = 'fa-solid fa-bars';
                }
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!navLinks.contains(e.target) && !mobileToggle.contains(e.target)) {
                navLinks.classList.remove('mobile-open');
                const icon = mobileToggle.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-bars';
            }
        });
    }

    // 2.1 Mobile Properties Filter Drawer Toggle
    const filterToggleBtn = document.getElementById('mobileFilterToggle');
    const filterSidebar = document.getElementById('filterSidebar');
    const filterArrow = document.getElementById('filterArrow');

    if (filterToggleBtn && filterSidebar) {
        filterToggleBtn.addEventListener('click', () => {
            const isHidden = window.getComputedStyle(filterSidebar).display === 'none';
            if (isHidden) {
                filterSidebar.style.display = 'block';
                if (filterArrow) filterArrow.className = 'fa-solid fa-chevron-up';
            } else {
                filterSidebar.style.display = 'none';
                if (filterArrow) filterArrow.className = 'fa-solid fa-chevron-down';
            }
        });
    }

    // 3. Price Range Slider Display
    const priceSlider = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceValue');

    if (priceSlider && priceDisplay) {
        const updatePriceDisplay = () => {
            const val = parseInt(priceSlider.value, 10);
            priceDisplay.textContent = '₹' + val.toLocaleString('en-IN');
        };
        priceSlider.addEventListener('input', updatePriceDisplay);
        updatePriceDisplay();
    }

    // 4. Quick Demo Login Credentials Auto-Fill
    window.fillDemoCredentials = (role) => {
        const emailInput = document.getElementById('loginEmail');
        const passInput = document.getElementById('loginPassword');
        
        if (!emailInput || !passInput) return;

        if (role === 'owner') {
            emailInput.value = 'owner@rentnear.com';
            passInput.value = 'owner123';
        } else if (role === 'renter') {
            emailInput.value = 'renter@rentnear.com';
            passInput.value = 'renter123';
        } else if (role === 'admin') {
            emailInput.value = 'admin@rentnear.com';
            passInput.value = 'admin123';
        }
        
        // Highlight inputs briefly
        emailInput.style.borderColor = '#4f46e5';
        passInput.style.borderColor = '#4f46e5';
        setTimeout(() => {
            emailInput.style.borderColor = '';
            passInput.style.borderColor = '';
        }, 800);
    };

    // 5. Favorite / Wishlist AJAX Toggle
    const favButtons = document.querySelectorAll('.property-fav-btn');
    favButtons.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const propId = btn.getAttribute('data-property-id');
            if (!propId) return;

            try {
                const response = await fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ property_id: propId })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    if (data.is_favorite) {
                        btn.classList.add('active');
                        showToast('Added to your Saved Properties!', 'success');
                    } else {
                        btn.classList.remove('active');
                        showToast('Removed from Saved Properties.', 'info');
                    }
                } else if (data.status === 'unauthorized') {
                    showToast('Please log in as a Renter to save properties.', 'warning');
                    setTimeout(() => {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    }, 1200);
                } else {
                    showToast(data.message || 'Error updating favorite', 'error');
                }
            } catch (err) {
                console.error('Favorite error:', err);
            }
        });
    });

    // 6. Toast Notification Helper
    window.showToast = (message, type = 'info') => {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.style.position = 'fixed';
            toastContainer.style.bottom = '24px';
            toastContainer.style.right = '24px';
            toastContainer.style.zIndex = '9999';
            toastContainer.style.display = 'flex';
            toastContainer.style.flexDirection = 'column';
            toastContainer.style.gap = '10px';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.margin = '0';
        toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        toast.style.padding = '0.75rem 1.25rem';
        toast.style.minWidth = '260px';
        toast.style.animation = 'fadeIn 0.2s ease';
        toast.innerHTML = `<span>${message}</span>`;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
});
