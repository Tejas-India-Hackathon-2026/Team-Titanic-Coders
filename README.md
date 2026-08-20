# RentNear – Online Property Rental Platform

> **“Find Your Next Place with RentNear.”**

RentNear is a fullstack web application designed to connect property owners with potential renters directly. It features role-based access, multi-facet property search and filtering, property listing management, and an interactive **₹99 Mock Payment system** for Premium Featured Listing upgrades.

---

## 🌟 Key Features

### 1. 🧑‍💼 Tenant / Renter Portal
- **Smart Search & Filters**: Filter by City (Bengaluru, Mumbai, Delhi NCR, Pune, Hyderabad), BHK Configuration (1 RK, 1 BHK, 2 BHK, 3 BHK, Villa, Studio), Price Range Slider (₹10,000 – ₹1,00,000+), Furnishing status, and Amenities checklist.
- **Direct Owner Connect**: Call owner directly (`tel:`), instant WhatsApp chat with pre-filled message, or submit an inquiry message with expected move-in date.
- **Wishlist / Saved Properties**: Bookmark favorite listings with 1-click heart button and track sent inquiries.

### 2. 🏠 Property Owner Portal
- **Post Rental Listings**: Add listings with photos, floor area, rent, deposit, amenities checklist, and instant live image preview.
- **Listing Management**: View total views count, edit listing details, or remove properties.
- **⭐ Premium Upgrade (₹99)**: Upgrade any listing to **Featured Status** via the interactive Mock Payment gateway.

### 3. 💳 Mock Payment Gateway (₹99 Premium Upgrade)
- Realistic simulated payment portal supporting **UPI (QR Code scanning)**, **Debit/Credit Card**, and **Net Banking**.
- Dynamic transaction reference generation (`MOCK_TXN_XXXXXX`).
- Realistic bank authorization loader.
- Automatic database activation of the **⭐ Featured Ribbon** and top search ranking for 60 days.
- Printable / downloadable **Official Tax Invoice & Payment Receipt**.

### 4. 🛡️ Administrator Panel
- Metrics dashboard: Total registered users, total properties, active premium properties, and cumulative ₹99 revenue collected.
- Manage all platform properties with 1-click Premium toggle or deletion.
- User management table with role badges.

---

## 🚀 How to Run with XAMPP (Apache Port 80, MySQL Port 3306)

Since your **XAMPP Apache (Port 80/443)** and **MySQL (Port 3306)** are already active:

1. **Direct Browser Access (XAMPP Apache)**:
   Simply open your browser and go to:
   👉 **[http://localhost/Rentnear/](http://localhost/Rentnear/)**

2. **1-Click Built-in Server (Alternative)**:
   Double click [`start_server.bat`](file:///c:/Users/kumar/OneDrive/Desktop/Rentnear/start_server.bat) or run:
   ```cmd
   C:\xampp\php\php.exe -S localhost:8000
   ```
   Then open: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Pre-seeded Demo Accounts

Use the **1-Click Demo Fill** buttons on the login page or enter credentials manually:

| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@rentnear.com` | `owner123` | Post & manage listings, upgrade to Premium ₹99, view tenant inquiries |
| **Renter** | `renter@rentnear.com` | `renter123` | Search, filter, contact owners, save favorites, send inquiries |
| **Admin** | `admin@rentnear.com` | `admin123` | View revenue, manage all users, toggle listing statuses |

---

## 🗄️ Database Options

1. **SQLite (Default / Zero Setup)**:
   - Stored in `database/rentnear.sqlite`.
   - Auto-migrated and auto-seeded on first run without needing any database configuration.
2. **MySQL / XAMPP**:
   - Schema script available at `database/database.sql`.
   - To switch to MySQL, open `config/db.php` and set `$db_type = 'mysql'`.

---

## 📁 Project Architecture

```
Rentnear/
├── config/
│   ├── db.php               # PDO connection (auto SQLite / MySQL)
│   └── setup_db.php         # Schema migrator & sample seeder
├── database/
│   ├── database.sql         # MySQL schema export
│   └── rentnear.sqlite      # SQLite database file (auto-generated)
├── includes/
│   ├── header.php           # Global responsive navigation
│   ├── footer.php           # Global footer with quick demo links
│   ├── auth_check.php       # Role middleware & session helpers
│   └── functions.php        # Formatting & currency helpers (₹)
├── assets/
│   ├── css/
│   │   ├── style.css        # Responsive styling & components
│   │   └── payment.css      # Mock payment & invoice styling
│   └── js/
│       ├── main.js          # Search, filters, and wishlist AJAX
│       └── payment.js       # Mock payment gateway simulation
├── api/
│   ├── process_payment.php  # ₹99 Premium transaction handler
│   └── toggle_favorite.php  # Wishlist AJAX endpoint
├── index.php                # Homepage & featured showcase
├── properties.php           # Catalog with multi-facet filters
├── property-details.php     # Detailed view with owner contact
├── login.php                # Auth login with 1-click demo filler
├── register.php             # User registration (Owner vs Renter)
├── logout.php               # Session termination
├── owner-dashboard.php      # Owner control center
├── add-property.php         # New property listing form
├── edit-property.php        # Property edit form
├── delete-property.php      # Property deletion handler
├── renter-dashboard.php     # Tenant wishlist & inquiries
├── admin-dashboard.php      # Platform admin panel & revenue stats
├── payment.php              # ₹99 Premium checkout portal
├── payment-success.php      # Transaction success & invoice receipt
├── about.php                # Project background & architecture
├── contact.php              # Support ticket form & FAQs
└── start_server.bat         # 1-Click launcher
```
