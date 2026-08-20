<?php
// config/setup_db.php - Database Schema Initialization with Complete Local Room, PG, 1 BHK & 2 BHK Variety

function initialize_database($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        // 1. Separate Owners Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS owners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                password VARCHAR(255) NOT NULL,
                address VARCHAR(255) DEFAULT '',
                city VARCHAR(100) DEFAULT '',
                avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Separate Renters Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS renters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                password VARCHAR(255) NOT NULL,
                occupation VARCHAR(100) DEFAULT '',
                preferred_city VARCHAR(100) DEFAULT '',
                avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 3. Separate Admins Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) DEFAULT '',
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 4. Properties Table (Linked to owners)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS properties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                property_type VARCHAR(50) NOT NULL,
                furnishing VARCHAR(50) NOT NULL,
                location VARCHAR(200) NOT NULL,
                city VARCHAR(100) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                deposit DECIMAL(10, 2) NOT NULL,
                bedrooms INT DEFAULT 1,
                bathrooms INT DEFAULT 1,
                area_sqft INT DEFAULT 200,
                image VARCHAR(500) NOT NULL,
                images_json TEXT DEFAULT NULL,
                amenities TEXT DEFAULT NULL,
                tenant_preference VARCHAR(50) DEFAULT 'Bachelors Allowed',
                is_premium TINYINT(1) DEFAULT 0,
                premium_expires_at DATETIME DEFAULT NULL,
                views_count INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 5. Payments Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_id INT NOT NULL,
                property_id INT NOT NULL,
                amount DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
                payment_type VARCHAR(50) NOT NULL DEFAULT 'premium_listing',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'UPI',
                transaction_id VARCHAR(100) NOT NULL UNIQUE,
                status VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 6. Inquiries Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                renter_id INT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                move_in_date DATE NULL,
                status VARCHAR(20) DEFAULT 'unread',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (renter_id) REFERENCES renters(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 7. Favorites Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS favorites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                renter_id INT NOT NULL,
                property_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_favorite (renter_id, property_id),
                FOREIGN KEY (renter_id) REFERENCES renters(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        // SQLite Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS owners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                password VARCHAR(255) NOT NULL,
                address VARCHAR(255) DEFAULT '',
                city VARCHAR(100) DEFAULT '',
                avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS renters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                password VARCHAR(255) NOT NULL,
                occupation VARCHAR(100) DEFAULT '',
                preferred_city VARCHAR(100) DEFAULT '',
                avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) DEFAULT '',
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS properties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INTEGER NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                property_type VARCHAR(50) NOT NULL,
                furnishing VARCHAR(50) NOT NULL,
                location VARCHAR(200) NOT NULL,
                city VARCHAR(100) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                deposit DECIMAL(10, 2) NOT NULL,
                bedrooms INTEGER DEFAULT 1,
                bathrooms INTEGER DEFAULT 1,
                area_sqft INTEGER DEFAULT 200,
                image VARCHAR(500) NOT NULL,
                images_json TEXT DEFAULT '[]',
                amenities TEXT DEFAULT '',
                tenant_preference VARCHAR(50) DEFAULT 'Bachelors Allowed',
                is_premium INTEGER DEFAULT 0,
                premium_expires_at DATETIME NULL,
                views_count INTEGER DEFAULT 0,
                status VARCHAR(20) DEFAULT 'available',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INTEGER NOT NULL,
                property_id INTEGER NOT NULL,
                amount DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
                payment_type VARCHAR(50) NOT NULL DEFAULT 'premium_listing',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'UPI',
                transaction_id VARCHAR(100) NOT NULL UNIQUE,
                status VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS inquiries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                property_id INTEGER NOT NULL,
                renter_id INTEGER NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                move_in_date DATE NULL,
                status VARCHAR(20) DEFAULT 'unread',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (renter_id) REFERENCES renters(id) ON DELETE SET NULL
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS favorites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                renter_id INTEGER NOT NULL,
                property_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(renter_id, property_id),
                FOREIGN KEY (renter_id) REFERENCES renters(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            );
        ");
    }

    // Auto-migration for tenant_preference
    try {
        $pdo->exec("ALTER TABLE properties ADD COLUMN tenant_preference VARCHAR(50) DEFAULT 'Bachelors Allowed'");
    } catch (Exception $e) {
        // Already exists or supported
    }

    // Check if initial seeding is needed
    $ownerCountStmt = $pdo->query("SELECT COUNT(*) FROM owners");
    $ownerCount = (int)$ownerCountStmt->fetchColumn();

    if ($ownerCount === 0) {
        seed_sample_data($pdo);
    }
}

function seed_sample_data($pdo) {
    // 1. Seed Admins Table
    $password_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $stmtAdmin = $pdo->prepare("INSERT INTO admins (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmtAdmin->execute(['Platform Admin', 'admin@rentnear.com', '+91 98765 00001', $password_admin]);

    // 2. Seed Owners Table
    $password_owner = password_hash('owner123', PASSWORD_DEFAULT);
    $stmtOwner = $pdo->prepare("INSERT INTO owners (name, email, phone, password, address, city) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtOwner->execute(['Rajesh Sharma', 'owner@rentnear.com', '+91 98111 22334', $password_owner, 'Indiranagar 100ft Road', 'Bengaluru']);
    $stmtOwner->execute(['Priya Patel', 'priya.owner@rentnear.com', '+91 98222 33445', $password_owner, 'Carter Road, Bandra West', 'Mumbai']);
    $stmtOwner->execute(['Suresh Kumar', 'suresh.owner@rentnear.com', '+91 98555 66778', $password_owner, 'Laxmi Nagar, Main Market', 'New Delhi']);
    $stmtOwner->execute(['Manoj Yadav', 'manoj.jamui@rentnear.com', '+91 98350 11223', $password_owner, 'Station Road, Jamui', 'Jamui']);

    // 3. Seed Renters Table
    $password_renter = password_hash('renter123', PASSWORD_DEFAULT);
    $stmtRenter = $pdo->prepare("INSERT INTO renters (name, email, phone, password, occupation, preferred_city) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtRenter->execute(['Amit Verma', 'renter@rentnear.com', '+91 98333 44556', $password_renter, 'Software Engineer', 'Bengaluru']);
    $stmtRenter->execute(['Sneha Kulkarni', 'sneha.renter@rentnear.com', '+91 98444 55667', $password_renter, 'Product Designer', 'Pune']);
    $stmtRenter->execute(['Rohan Gupta', 'rohan.renter@rentnear.com', '+91 98777 88990', $password_renter, 'Student / UPSC Aspirant', 'New Delhi']);

    // 4. Comprehensive Seed Properties: Local Rooms, Single Rooms, Shared, PG, 1 BHK & 2 BHK
    $properties = [
        // 0. Ultra-Budget Student Bed Space (₹800/mo)
        [
            'owner_id' => 3,
            'title' => 'Ultra-Budget Student Bed Space in Shared Study Room',
            'description' => 'Cheapest accommodation for students and competitive exam aspirants. Clean single bed, high-speed WiFi, RO drinking water, fan, common washroom, and peaceful study atmosphere. 24/7 power backup.',
            'property_type' => 'Shared Room',
            'furnishing' => 'Furnished',
            'location' => 'Mukherjee Nagar, Near Batra Cinema',
            'city' => 'New Delhi',
            'price' => 800,
            'deposit' => 800,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 150,
            'image' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Bed & Mattress,RO Water,Study Table,Power Backup',
            'is_premium' => 1,
            'views_count' => 380
        ],
        // 0.1 Economy Local Single Room (₹1,500/mo)
        [
            'owner_id' => 1,
            'title' => 'Economy Local Single Room for Students & Workers',
            'description' => 'Low-cost independent single room with ceiling fan, separate electricity sub-meter, 24-hour water facility, and bike parking. Zero brokerage direct from landlord.',
            'property_type' => 'Single Room',
            'furnishing' => 'Unfurnished',
            'location' => 'Katraj, Near Bharati Vidyapeeth',
            'city' => 'Pune',
            'price' => 1500,
            'deposit' => 2000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 160,
            'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Water Tank,Separate Sub-Meter,Bike Parking',
            'is_premium' => 0,
            'views_count' => 190
        ],
        // 1. Single Local Room (Delhi - Student/Bachelor)
        [
            'owner_id' => 3,
            'title' => 'Single Local Room for Students with Attached Washroom',
            'description' => 'Affordable and clean single room in coaching hub. Includes bed with mattress, study table, ceiling fan, attached private washroom, separate electricity meter, and 24/7 RO water supply. No brokerage, direct owner.',
            'property_type' => 'Single Room',
            'furnishing' => 'Furnished',
            'location' => 'Laxmi Nagar, Near Metro Pillar 38',
            'city' => 'New Delhi',
            'price' => 4500,
            'deposit' => 4500,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 180,
            'image' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Attached Washroom,Bed & Mattress,Study Table,RO Water,Separate Sub-Meter,Geyser',
            'is_premium' => 1,
            'views_count' => 245
        ],
        // 2. 1 Room Set / 1 RK (Pune - Working Professional)
        [
            'owner_id' => 1,
            'title' => '1 Room Set with Private Kitchen & Balcony',
            'description' => 'Complete 1 Room Kitchen (1 RK Set) on 1st floor. Big ventilated room, separate kitchen slab with sink, attached bathroom, and airy balcony. Ideal for single working professional or student.',
            'property_type' => '1 Room Set',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Viman Nagar, Datta Mandir Chowk',
            'city' => 'Pune',
            'price' => 7800,
            'deposit' => 12000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 320,
            'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Private Kitchen,Balcony,Water Tank,Bike Parking,Separate Sub-Meter',
            'is_premium' => 1,
            'views_count' => 180
        ],
        // 3. Shared Room (Bengaluru - Budget Sharing)
        [
            'owner_id' => 1,
            'title' => 'Shared Room (2-Sharing) for Boys near IT Hub',
            'description' => 'Twin sharing spacious room in a safe residential building. Comes with individual wooden cupboards, 2 separate single beds, high-speed fiber internet, and daily housekeeping. Walking distance to bus stop.',
            'property_type' => 'Shared Room',
            'furnishing' => 'Furnished',
            'location' => 'Marathahalli, Near Kalamandir',
            'city' => 'Bengaluru',
            'price' => 3800,
            'deposit' => 5000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 240,
            'image' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Bed & Mattress,Wardrobe,RO Water,Housekeeping,24/7 Power Backup',
            'is_premium' => 0,
            'views_count' => 134
        ],
        // 4. PG Room with Food (Delhi NCR - Gurugram)
        [
            'owner_id' => 3,
            'title' => 'Executive PG Room with 3-Time Homely Food & AC',
            'description' => 'Fully furnished luxury PG room for job seekers and corporate employees. Includes 3 daily hygienic home-cooked meals, 1.5 ton split AC, geyser, refrigerator, washing machine, and high-speed WiFi.',
            'property_type' => 'PG Room',
            'furnishing' => 'Furnished',
            'location' => 'Sector 22, Near Cyber Hub',
            'city' => 'Gurugram',
            'price' => 9500,
            'deposit' => 9500,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 250,
            'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,AC,Mess/Food Included,Geyser,Washing Machine,Daily Cleaning,CCTV',
            'is_premium' => 1,
            'views_count' => 310
        ],
        // 5. 1 BHK Flat (Pune - IT Hinjawadi)
        [
            'owner_id' => 1,
            'title' => 'Cozy 1 BHK Flat in Peaceful Society near Wipro Circle',
            'description' => 'Ready to move 1 BHK flat with bright living hall, modular kitchen with piped gas, bedroom with wardrobe, and attached bathroom. Gated society with 24/7 security and bike parking.',
            'property_type' => '1 BHK',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Hinjawadi Phase 1, Wakad Road',
            'city' => 'Pune',
            'price' => 14500,
            'deposit' => 30000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 620,
            'image' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Covered Parking,Lift,Piped Gas,Water Purifier,Balcony,24/7 Security',
            'is_premium' => 1,
            'views_count' => 165
        ],
        // 6. 1 BHK Independent Floor (Delhi South)
        [
            'owner_id' => 3,
            'title' => 'Independent 1 BHK Builder Floor with Open Terrace',
            'description' => 'Sunny top-floor 1 BHK with separate living room, bedroom, kitchen, bathroom, and access to open terrace. 5-minute walk to yellow line metro.',
            'property_type' => '1 BHK',
            'furnishing' => 'Furnished',
            'location' => 'Hauz Khas Village Road',
            'city' => 'New Delhi',
            'price' => 16500,
            'deposit' => 25000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 550,
            'image' => 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,AC,Geyser,Open Terrace,Water Tank,CCTV',
            'is_premium' => 0,
            'views_count' => 92
        ],
        // 7. 2 BHK Family Home (Bengaluru - Indiranagar)
        [
            'owner_id' => 1,
            'title' => 'Luxury 2 BHK Gated Apartment with Scenic Balcony',
            'description' => 'Spacious 2 BHK fully furnished apartment with modular kitchen, Italian tiles, swimming pool, gym, 24/7 power backup, and dedicated car parking.',
            'property_type' => '2 BHK',
            'furnishing' => 'Furnished',
            'location' => 'Indiranagar 100ft Road',
            'city' => 'Bengaluru',
            'price' => 36000,
            'deposit' => 90000,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1250,
            'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Covered Parking,Lift,Swimming Pool,Power Backup,Gym,24/7 Security,Club House',
            'is_premium' => 1,
            'views_count' => 280
        ],
        // 8. 2 BHK Affordable Family Flat (Bengaluru - Koramangala)
        [
            'owner_id' => 2,
            'title' => 'Well-Ventilated 2 BHK Apartment near Forum Mall',
            'description' => 'Peaceful 2 BHK home with 2 bathrooms, 2 balconies, east-facing entry, wood work wardrobes, and dedicated bike/car parking.',
            'property_type' => '2 BHK',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Koramangala 4th Block',
            'city' => 'Bengaluru',
            'price' => 28000,
            'deposit' => 70000,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1100,
            'image' => 'https://images.unsplash.com/photo-1502005229762-ee1b2b8ab98f?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Covered Parking,Lift,Power Backup,Rainwater Harvesting,Park View',
            'is_premium' => 0,
            'views_count' => 115
        ],
        // 9. 3 BHK Sea View Home (Mumbai - Bandra West)
        [
            'owner_id' => 2,
            'title' => 'Spacious 3 BHK Sea View Flat in Bandra West',
            'description' => 'Prime location sea-facing 3 BHK home with large marble living area, separate servant quarters, Italian kitchen, and 2 designated car parking slots.',
            'property_type' => '3 BHK',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Carter Road, Bandra West',
            'city' => 'Mumbai',
            'price' => 85000,
            'deposit' => 250000,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'area_sqft' => 1850,
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Covered Parking,Lift,24/7 Security,Sea View,CCTV,Gas Pipeline,Kids Play Area',
            'is_premium' => 1,
            'views_count' => 220
        ],
        // 10. Independent Villa (Hyderabad - Gachibowli)
        [
            'owner_id' => 2,
            'title' => 'Independent 4 BHK Luxury Villa with Private Garden',
            'description' => 'Magnificent independent luxury villa in a peaceful gated community. Features private lawn, terrace pergola, solar water heating, modular kitchen, and 3 car parkings.',
            'property_type' => 'Villa',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Gachibowli, Financial District',
            'city' => 'Hyderabad',
            'price' => 65000,
            'deposit' => 150000,
            'bedrooms' => 4,
            'bathrooms' => 4,
            'area_sqft' => 3200,
            'image' => 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Private Garden,Covered Parking,Gym,Club House,Swimming Pool,Solar Water,24/7 Security',
            'is_premium' => 1,
            'views_count' => 195
        ],
        // 11. Student Coaching Room (Kota - Rajeev Gandhi Nagar)
        [
            'owner_id' => 3,
            'title' => 'Student Study Room with Bed, AC & Mess Option',
            'description' => 'Dedicated quiet room for IIT-JEE/NEET aspirants. Includes wooden study desk with bookshelf, single bed, high-speed WiFi, silent AC, and nearby mess facility. Zero disturbance environment.',
            'property_type' => 'Single Room',
            'furnishing' => 'Furnished',
            'location' => 'Rajeev Gandhi Nagar, Near Allen Landmark',
            'city' => 'Kota',
            'price' => 5500,
            'deposit' => 5500,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 170,
            'image' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,AC,Attached Washroom,Bed & Mattress,Study Table,RO Water,Mess/Food Included',
            'is_premium' => 1,
            'views_count' => 340
        ],
        // 12. 1 BHK Flat in Jaipur (Malviya Nagar)
        [
            'owner_id' => 1,
            'title' => 'Furnished 1 BHK Flat near World Trade Park',
            'description' => 'Airy and modern 1 BHK apartment close to GT and WTP. Features modular kitchen, double bed with mattress, 32-inch LED TV, split AC, and dedicated bike parking.',
            'property_type' => '1 BHK',
            'furnishing' => 'Furnished',
            'location' => 'Malviya Nagar, Near Calgiri Hospital',
            'city' => 'Jaipur',
            'price' => 11000,
            'deposit' => 15000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 480,
            'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,AC,Geyser,Balcony,Water Tank,Bike Parking',
            'is_premium' => 1,
            'views_count' => 155
        ],
        // 13. Budget Room in Patna (Boring Road)
        [
            'owner_id' => 3,
            'title' => 'Private Single Room for Students on Boring Road',
            'description' => 'Ideal for college students and aspirants. Prime location on Boring Road. 24-hour water supply, separate sub-meter, study table, and CCTV surveillance in premises.',
            'property_type' => 'Single Room',
            'furnishing' => 'Furnished',
            'location' => 'Boring Road, Near AN College',
            'city' => 'Patna',
            'price' => 3500,
            'deposit' => 3500,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 190,
            'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Bed & Mattress,Study Table,RO Water,Separate Sub-Meter,CCTV',
            'is_premium' => 0,
            'views_count' => 210
        ],
        // 14. 2 BHK Flat in Lucknow (Gomti Nagar)
        [
            'owner_id' => 1,
            'title' => 'Spacious 2 BHK Society Apartment in Gomti Nagar',
            'description' => 'Peaceful family flat in Gomti Nagar Extension. 2 bedrooms, 2 bathrooms, modern kitchen, lift, power backup, and dedicated covered car parking.',
            'property_type' => '2 BHK',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Gomti Nagar Extension, Sector 4',
            'city' => 'Lucknow',
            'price' => 16000,
            'deposit' => 32000,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1050,
            'image' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Covered Parking,Lift,Power Backup,24/7 Security,Park View',
            'is_premium' => 1,
            'views_count' => 140
        ],
        // 15. Jamui - Student Room near KKM College
        [
            'owner_id' => 4,
            'title' => 'Single Local Room for Students near K.K.M. College Jamui',
            'description' => 'Clean and peaceful single room for college students and competitive aspirants in Jamui. Includes single bed, wooden study table with chair, ceiling fan, private washroom, separate electricity meter, and 24/7 water supply. No brokerage, direct contact with Manoj ji.',
            'property_type' => 'Single Room',
            'furnishing' => 'Furnished',
            'location' => 'K.K.M College Road, Near City Library',
            'city' => 'Jamui',
            'price' => 1800,
            'deposit' => 1800,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 160,
            'image' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'WiFi,Attached Washroom,Bed & Mattress,Study Table,RO Water,Separate Sub-Meter,Bike Parking',
            'is_premium' => 1,
            'views_count' => 290
        ],
        // 16. Jamui - 1 Room Set on Station Road
        [
            'owner_id' => 4,
            'title' => '1 Room Set (1 RK) with Kitchen & Balcony on Station Road Jamui',
            'description' => 'Complete 1 Room Kitchen set on Station Road near Malaypur. Features big airy room, private kitchen slab with water sink, attached clean bathroom, and front balcony. 2-minute walk to auto stand.',
            'property_type' => '1 Room Set',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Station Road, Near Malaypur Railway Station',
            'city' => 'Jamui',
            'price' => 2800,
            'deposit' => 3000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 280,
            'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Private Kitchen,Balcony,Water Tank,Separate Sub-Meter,Bike Parking',
            'is_premium' => 1,
            'views_count' => 220
        ],
        // 17. Jamui - 1 BHK Family Flat in Main Market
        [
            'owner_id' => 4,
            'title' => '1 BHK Ground Floor Family Flat in Main Market Jamui',
            'description' => 'Convenient ground floor 1 BHK for small families or job professionals. Large hall, 1 bedroom, modular kitchen, and clean tiled bathroom. Safe residential area near Sadar Hospital & Main Market.',
            'property_type' => '1 BHK',
            'furnishing' => 'Semi-Furnished',
            'location' => 'Main Market, Hospital Road',
            'city' => 'Jamui',
            'price' => 4500,
            'deposit' => 6000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 450,
            'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Water Tank,Separate Sub-Meter,Bike Parking,24/7 Security',
            'is_premium' => 0,
            'views_count' => 160
        ],
        // 18. Jamui - Student Bed Space at Bodhban Talab
        [
            'owner_id' => 4,
            'title' => 'Budget Student Bed Space in Jamui (Sharing Room)',
            'description' => 'Most affordable bed space in Jamui for students preparing for government exams. Single bed, study desk, 24/7 drinking water, and quiet study environment.',
            'property_type' => 'Shared Room',
            'furnishing' => 'Furnished',
            'location' => 'Bodhban Talab, Bypass Road',
            'city' => 'Jamui',
            'price' => 900,
            'deposit' => 900,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqft' => 140,
            'image' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1200&q=80',
            'amenities' => 'Bed & Mattress,Study Table,RO Water,Separate Sub-Meter',
            'is_premium' => 1,
            'views_count' => 310
        ]
    ];

    $propStmt = $pdo->prepare("
        INSERT INTO properties (
            owner_id, title, description, property_type, furnishing,
            location, city, price, deposit, bedrooms, bathrooms,
            area_sqft, image, amenities, tenant_preference, is_premium, views_count
        ) VALUES (
            :owner_id, :title, :description, :property_type, :furnishing,
            :location, :city, :price, :deposit, :bedrooms, :bathrooms,
            :area_sqft, :image, :amenities, :tenant_preference, :is_premium, :views_count
        )
    ");

    foreach ($properties as $p) {
        if (!isset($p['tenant_preference'])) {
            $p['tenant_preference'] = 'Bachelors Allowed';
        }
        $propStmt->execute($p);
    }

    // 5. Seed Mock Payments
    $payStmt = $pdo->prepare("
        INSERT INTO payments (owner_id, property_id, amount, payment_type, payment_method, transaction_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $payStmt->execute([3, 1, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_784912', 'SUCCESS']);
    $payStmt->execute([1, 2, 99.00, 'premium_listing', 'Card', 'MOCK_TXN_620194', 'SUCCESS']);
    $payStmt->execute([3, 4, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_991823', 'SUCCESS']);
    $payStmt->execute([1, 5, 99.00, 'premium_listing', 'NetBanking', 'MOCK_TXN_881923', 'SUCCESS']);
    $payStmt->execute([1, 7, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_904321', 'SUCCESS']);
    $payStmt->execute([2, 9, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_551234', 'SUCCESS']);
    $payStmt->execute([2, 10, 99.00, 'premium_listing', 'Card', 'MOCK_TXN_773412', 'SUCCESS']);
    $payStmt->execute([4, 15, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_448811', 'SUCCESS']);
    $payStmt->execute([4, 16, 99.00, 'premium_listing', 'UPI', 'MOCK_TXN_448822', 'SUCCESS']);

    // 6. Seed Sample Inquiry
    $inqStmt = $pdo->prepare("
        INSERT INTO inquiries (property_id, renter_id, name, email, phone, message, move_in_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $inqStmt->execute([
        1, 3, 'Rohan Gupta', 'rohan.renter@rentnear.com', '+91 98777 88990',
        'Hi Suresh ji, I am preparing for exams and need this single room starting next Monday. Is the study table and WiFi included?',
        date('Y-m-d', strtotime('+3 days')),
        'unread'
    ]);
}
