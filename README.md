# 🏠 RentNear – Smart Rental & Local Room Discovery Platform

> **“Find Your Next Place with RentNear – Verified Rooms, Flexible Stays & Interactive Maps.”**  
> **Submitted for:** [Tejas India Hackathon 2026](https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders) | **Team:** Team Titanic Coders

[![PHP CI](https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders/actions/workflows/php.yml/badge.svg)](https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders/actions/workflows/php.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Hackathon](https://img.shields.io/badge/Hackathon-Tejas_India_2026-orange.svg)](https://github.com/Tejas-India-Hackathon-2026)

---

## 🏆 Project Overview & Team Contributions

**RentNear** is an all-in-one PropTech web application crafted by **Team Titanic Coders** to solve real-world housing and rental discovery challenges across Tier-1, Tier-2, and Tier-3 cities in India (including Jamui, Patna, Kota, New Delhi, Pune, Bengaluru, and beyond).

---

## 🌟 Key Innovations & Major Contributions

### 1. 🗺️ Interactive Live Explore Map (`explore-map.php`)
- **Full-Screen Split Map**: Side-by-side split layout with OpenStreetMap and Leaflet.js.
- **Custom Teardrop Price Pins**: Custom teardrop markers showing exact room prices, vacancy indicator pulses, and interactive pin-card synchronization.
- **City Switcher Pills**: 1-click smooth animated map fly-to for Jamui (Bihar), Kota, New Delhi, Pune, Patna, and Bengaluru.
- **GPS Location Detection**: "Near Me" GPS locator to instantly discover rooms closest to the user.

### 2. 📍 Interactive Map Pinpoint Location Picker (`add-property.php`, `edit-property.php`)
- Landlords can click anywhere on the live interactive map or use **"Detect My GPS Location"** to save exact latitude & longitude coordinates for their room listings.

### 3. ⏱️ 1-Month Short Stays & Flexible Renting
- Dedicated housing mode for competitive exam aspirants (UPSC/SSC/IIT-JEE/NEET in Jamui, Kota, Delhi, Patna), college interns, and temporary workers.
- Filter by **"1 Month Only (Short Stay / No Long Lock-in)"**, **"3 Months"**, or **"11 Months (Standard Agreement)"**.
- Amber gold `⏱️ 1-Mo Stay` badges across catalog cards and property detail overviews.

### 4. 💳 Room Booking & Refundable Token Advance Payments (`booking-payment.php`, `booking-receipt.php`)
- **Online Room Lock**: Renters can pay a small refundable token advance (₹500, ₹1,000, or full month rent) to reserve and hold a room online.
- **Simulated Multi-Gateway Checkout**: Supports UPI (Google Pay, PhonePe, Paytm, BHIM), Credit/Debit Cards, and NetBanking.
- **Official Booking Vouchers**: Generates verified printable receipts (`RN-BOOK-XXXXXX`) with property details, landlord contact, and **1-Click WhatsApp Landlord Connect**.
- **Sync With Dashboards**: Landlords instantly see `🟢 ₹1,000 Token Paid & Reserved` badges on received inquiries in `owner-dashboard.php`.

### 5. ⭐ Luxury Golden Tick Verified Landlord & Paid Subscription (₹199/Year)
- **VIP Trust & Anti-Fraud Verification**: Luxury Golden Tick badge (`#f59e0b` / `#d97706` radiant gold gradient) displayed next to authenticated landlords across:
  - Property details sidebar (`⭐ Govt ID & Property Gold Verified Owner`)
  - Property catalog cards
  - Explore Map sidebar and popups
  - Renter and Owner dashboards
  - Official printable verification certificates (`verify-receipt.php`)
- **Paid Landlord Verification Gateway (`verify-owner.php`)**: Owners can self-verify with Aadhaar / PAN / Voter ID and activate their Golden Tick via simulated UPI, Card, or NetBanking checkout (₹199 / Year).

### 6. ⭐ Owner Listing Boost & Premium Upgrades (₹99)
- Property owners can boost listings to **⭐ Featured Status** via interactive payment simulation.
- Generates official tax invoices with instant database promotion for 60 days.

### 7. 🎨 Complete Site-Wide UI/UX Overhaul
- Upgraded typography with Google Fonts (*Plus Jakarta Sans*).
- Indigo and amber gradient system, multi-level drop shadows, glassmorphism topbars, and sticky responsive bottom mobile navigation bar.

---

## 🚀 How to Run Locally with XAMPP

Since **XAMPP Apache (Port 80/443)** and **MySQL (Port 3306)** are configured:

1. **Direct Browser Access (XAMPP Apache)**:  
   Open your browser and navigate to:  
   👉 **[http://localhost/Rentnear/](http://localhost/Rentnear/)**

2. **PHP Built-in Server Alternative**:
   ```cmd
   C:\xampp\php\php.exe -S localhost:8000
   ```
   Then visit: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Pre-Seeded Demo Credentials

Use the **1-Click Demo Fill** buttons on `login.php` or enter manually:

| Role | Email | Password | Access / Capabilities |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@rentnear.com` | `owner123` | Post & edit rooms, pinpoint GPS map location, view token-paid inquiries, boost listings |
| **Renter** | `renter@rentnear.com` | `renter123` | Search map, filter 1-month stays, submit inquiries, pay booking token, download receipts |
| **Admin** | `admin@rentnear.com` | `admin123` | System metrics, revenue tracking, user management, global listing moderation |

---

## 🗄️ Database Setup

1. **SQLite (Default / Zero Configuration)**:
   - Stored in `database/rentnear.sqlite`.
   - Automatically migrated and seeded with local single rooms, PG bed spaces, 1 RKs, 1 BHK & 2 BHK flats across Jamui, Kota, Delhi, Pune, Bengaluru.
2. **MySQL / MariaDB (Optional)**:
   - Schema file available in `database/database.sql`.
   - Set `$db_type = 'mysql'` in `config/db.php`.

---

## 📁 Repository Structure

```
Rentnear/
├── config/
│   ├── db.php               # PDO Database handler (SQLite & MySQL)
│   └── setup_db.php         # Auto-migration & demo data seeder
├── database/
│   ├── database.sql         # MySQL schema export
│   └── rentnear.sqlite      # SQLite database file
├── includes/
│   ├── header.php           # Global responsive navigation bar
│   ├── footer.php           # Global footer with quick demo shortcuts
│   ├── auth_check.php       # Role-based middleware & sessions
│   └── functions.php        # Formatting, currency (₹), & verified badge helpers
├── assets/
│   ├── css/
│   │   ├── style.css        # Core stylesheet, glassmorphism & typography
│   │   └── payment.css      # Payment portal & tax invoice styling
│   └── js/
│       ├── main.js          # Dynamic filter & wishlist AJAX
│       └── payment.js       # Mock gateway simulation
├── api/
│   ├── process_payment.php  # ₹99 Featured listing upgrade endpoint
│   ├── process_booking_payment.php # Room booking token payment endpoint
│   └── toggle_favorite.php  # Wishlist bookmarking endpoint
├── index.php                # Homepage with hero search & featured rooms
├── explore-map.php          # Interactive full-screen OpenStreetMap & Leaflet UI
├── properties.php           # Catalog with multi-facet filters & 1-month stay selector
├── property-details.php     # Comprehensive details, owner blue tick, & booking CTA
├── booking-payment.php      # Room reservation checkout with simulated gateways
├── booking-receipt.php      # Official verified printable booking voucher
├── add-property.php         # New room listing form with GPS map pin picker
├── edit-property.php        # Property edit form with coordinate updater
├── owner-dashboard.php      # Owner control center with token payment alerts
├── renter-dashboard.php     # Renter dashboard with saved rooms & booking receipts
├── admin-dashboard.php      # Administrative revenue & moderation panel
├── payment.php              # ₹99 Featured upgrade checkout
├── payment-success.php      # Featured upgrade invoice
├── push_to_github.bat       # 1-Click repository sync script
└── composer.json            # Automated CI/CD dependency configuration
```

---

## 👥 Hackathon Team & Contributions

* **Repository**: [Tejas-India-Hackathon-2026/Team-Titanic-Coders](https://github.com/Tejas-India-Hackathon-2026/Team-Titanic-Coders)
* **Team**: **Team Titanic Coders**
* **Project**: **RentNear** – Empowering affordable and transparent rental housing in India.
