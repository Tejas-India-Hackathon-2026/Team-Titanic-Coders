<?php
// includes/functions.php - Helper Functions for RentNear

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format currency into Indian Rupees (INR)
 */
function format_inr($amount) {
    $amount = (float)$amount;
    // Format standard Indian currency with commas
    $decimal = '';
    if (floor($amount) != $amount) {
        $decimal = '.' . sprintf('%02d', round(($amount - floor($amount)) * 100));
    }
    $number = (string)floor($amount);
    $length = strlen($number);
    if ($length <= 3) {
        return '₹' . $number . $decimal;
    }
    $last3 = substr($number, -3);
    $rest = substr($number, 0, -3);
    $restFormatted = '';
    while (strlen($rest) > 2) {
        $restFormatted = ',' . substr($rest, -2) . $restFormatted;
        $rest = substr($rest, 0, -2);
    }
    $restFormatted = $rest . $restFormatted;
    return '₹' . $restFormatted . ',' . $last3 . $decimal;
}

/**
 * Clean & sanitize user input for XSS safety
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message helpers for UI notifications
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function display_flash_message() {
    $flash = get_flash_message();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $icon = $type === 'success' ? 'check-circle' : ($type === 'error' ? 'alert-triangle' : 'info');
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                <i class="feather-' . $icon . ' me-2"></i> ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="this.parentElement.remove();">&times;</button>
              </div>';
    }
}

/**
 * Generate a unique Mock Transaction ID
 */
function generate_mock_txn_id() {
    return 'MOCK_TXN_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Render Luxury Golden Tick Verified Badge (VIP / Govt ID Verified Landlord)
 */
function render_verified_badge($showLabel = false, $size = 16) {
    $gradId = 'goldGrad_' . $size . '_' . mt_rand(100, 999);
    $svg = '<svg class="gold-tick-badge" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" style="vertical-align: -2.5px; margin-left: 2px; display: inline-block; flex-shrink: 0;" title="⭐ Gold Verified Landlord / Owner (Govt ID Authenticated)">
        <defs>
            <linearGradient id="' . $gradId . '" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fde047"/>
                <stop offset="40%" stop-color="#f59e0b"/>
                <stop offset="100%" stop-color="#d97706"/>
            </linearGradient>
        </defs>
        <path fill="url(#' . $gradId . ')" d="M22.5 12.5c0-1.58-.875-2.95-2.148-3.6.154-.435.238-.905.238-1.4 0-2.21-1.79-4-4-4-.495 0-.965.084-1.4.238C14.55 2.475 13.18 1.6 11.6 1.6c-1.58 0-2.95.875-3.6 2.148-.435-.154-.905-.238-1.4-.238-2.21 0-4 1.79-4 4 0 .495.084.965.238 1.4C1.575 9.55.7 10.92.7 12.5c0 1.58.875 2.95 2.148 3.6-.154.435-.238.905-.238 1.4 0 2.21 1.79 4 4 4 .495 0 .965-.084 1.4-.238.65 1.273 2.02 2.148 3.6 2.148 1.58 0 2.95-.875 3.6-2.148.435.154.905.238 1.4.238 2.21 0 4-1.79 4-4 0-.495-.084-.965-.238-1.4 1.273-.65 2.148-2.02 2.148-3.6zm-12.28 4.63l-4.15-4.15 1.42-1.42 2.73 2.73 6.88-6.88 1.42 1.42-8.3 8.3z"/>
    </svg>';
    if ($showLabel) {
        return '<span class="verified-badge-wrap" style="display: inline-flex; align-items: center; gap: 4px;" title="Government ID & Ownership Verified Landlord">' . $svg . '<span style="font-size: 0.72rem; font-weight: 800; color: #b45309; background: #fef3c7; padding: 1px 7px; border-radius: 12px; border: 1px solid #fde68a;">Gold Verified</span></span>';
    }
    return $svg;
}

/**
 * Return safe image URL or fallback
 */
function get_property_image($image_path) {
    if (!empty($image_path)) {
        if (str_starts_with($image_path, 'http://') || str_starts_with($image_path, 'https://')) {
            return $image_path;
        }
        if (file_exists(__DIR__ . '/../' . $image_path)) {
            return $image_path;
        }
    }
    return 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1000&q=80';
}

/**
 * Relative time helper (e.g. "2 hours ago", "Just now")
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    }
    $minutes = round($diff / 60);
    if ($minutes < 60) {
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    }
    $hours = round($diff / 3600);
    if ($hours < 24) {
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    }
    $days = round($diff / 86400);
    if ($days < 30) {
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    return date('d M Y', $time);
}

/**
 * Get base URL for absolute asset referencing
 */
function base_url($path = '') {
    // Detect current script directory relative to document root
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $script_dir = rtrim($script_dir, '/');
    
    // Normalize subfolders if executing from subdirectories like /api/
    if (str_ends_with($script_dir, '/api') || str_ends_with($script_dir, '/config')) {
        $script_dir = dirname($script_dir);
    }
    
    $clean_path = ltrim($path, '/');
    return ($script_dir ? $script_dir : '') . '/' . $clean_path;
}

/**
 * Resolve GPS coordinates for a property location and city
 */
function get_property_coordinates($location, $city, $id = 0) {
    $loc = strtolower($location ?? '');
    $c   = strtolower($city ?? '');

    // 1. Jamui Localities
    if (str_contains($c, 'jamui') || str_contains($loc, 'jamui')) {
        if (str_contains($loc, 'k.k.m') || str_contains($loc, 'kkm') || str_contains($loc, 'college')) {
            return ['lat' => 24.9213, 'lng' => 86.2234];
        }
        if (str_contains($loc, 'station') || str_contains($loc, 'malaypur') || str_contains($loc, 'railway')) {
            return ['lat' => 24.9150, 'lng' => 86.2500];
        }
        if (str_contains($loc, 'market') || str_contains($loc, 'hospital') || str_contains($loc, 'main')) {
            return ['lat' => 24.9200, 'lng' => 86.2270];
        }
        if (str_contains($loc, 'bodhban') || str_contains($loc, 'talab') || str_contains($loc, 'bypass')) {
            return ['lat' => 24.9250, 'lng' => 86.2200];
        }
        $offset = ($id % 5) * 0.003;
        return ['lat' => 24.9200 + $offset, 'lng' => 86.2250 - $offset];
    }

    // 2. Delhi / New Delhi Localities
    if (str_contains($c, 'delhi') || str_contains($loc, 'delhi')) {
        if (str_contains($loc, 'mukherjee') || str_contains($loc, 'batra')) {
            return ['lat' => 28.7075, 'lng' => 77.2085];
        }
        if (str_contains($loc, 'laxmi') || str_contains($loc, 'pillar')) {
            return ['lat' => 28.6304, 'lng' => 77.2773];
        }
        if (str_contains($loc, 'hauz khas') || str_contains($loc, 'iit')) {
            return ['lat' => 28.5494, 'lng' => 77.2001];
        }
        if (str_contains($loc, 'saket')) {
            return ['lat' => 28.5245, 'lng' => 77.2066];
        }
        if (str_contains($loc, 'rohini')) {
            return ['lat' => 28.7495, 'lng' => 77.0565];
        }
        $offset = ($id % 7) * 0.005;
        return ['lat' => 28.6139 + $offset, 'lng' => 77.2090 - $offset];
    }

    // 3. Pune Localities
    if (str_contains($c, 'pune') || str_contains($loc, 'pune')) {
        if (str_contains($loc, 'katraj') || str_contains($loc, 'bharati')) {
            return ['lat' => 18.4480, 'lng' => 73.8580];
        }
        if (str_contains($loc, 'viman') || str_contains($loc, 'phoenix')) {
            return ['lat' => 18.5679, 'lng' => 73.9143];
        }
        if (str_contains($loc, 'hinjawadi') || str_contains($loc, 'phase')) {
            return ['lat' => 18.5913, 'lng' => 73.7389];
        }
        if (str_contains($loc, 'kothrud')) {
            return ['lat' => 18.5074, 'lng' => 73.8077];
        }
        $offset = ($id % 5) * 0.004;
        return ['lat' => 18.5204 + $offset, 'lng' => 73.8567 - $offset];
    }

    // 4. Kota Localities
    if (str_contains($c, 'kota') || str_contains($loc, 'kota')) {
        if (str_contains($loc, 'rajeev') || str_contains($loc, 'allen')) {
            return ['lat' => 25.1388, 'lng' => 75.8488];
        }
        if (str_contains($loc, 'mahaveer') || str_contains($loc, 'resonance')) {
            return ['lat' => 25.1432, 'lng' => 75.8390];
        }
        $offset = ($id % 5) * 0.003;
        return ['lat' => 25.1800 + $offset, 'lng' => 75.8300 - $offset];
    }

    // 5. Bengaluru Localities
    if (str_contains($c, 'bengaluru') || str_contains($c, 'bangalore')) {
        if (str_contains($loc, 'indiranagar') || str_contains($loc, '100 ft')) {
            return ['lat' => 12.9784, 'lng' => 77.6408];
        }
        if (str_contains($loc, 'marathahalli') || str_contains($loc, 'bridge')) {
            return ['lat' => 12.9591, 'lng' => 77.6974];
        }
        if (str_contains($loc, 'koramangala')) {
            return ['lat' => 12.9352, 'lng' => 77.6245];
        }
        if (str_contains($loc, 'whitefield')) {
            return ['lat' => 12.9698, 'lng' => 77.7500];
        }
        $offset = ($id % 5) * 0.004;
        return ['lat' => 12.9716 + $offset, 'lng' => 77.5946 - $offset];
    }

    // 6. Patna Localities
    if (str_contains($c, 'patna')) {
        if (str_contains($loc, 'boring') || str_contains($loc, 'chauraha')) {
            return ['lat' => 25.6146, 'lng' => 85.1214];
        }
        if (str_contains($loc, 'kankarbagh')) {
            return ['lat' => 25.5941, 'lng' => 85.1610];
        }
        $offset = ($id % 5) * 0.003;
        return ['lat' => 25.5941 + $offset, 'lng' => 85.1376 - $offset];
    }

    // 7. Jaipur
    if (str_contains($c, 'jaipur')) {
        if (str_contains($loc, 'malviya') || str_contains($loc, 'gt')) {
            return ['lat' => 26.8549, 'lng' => 75.8243];
        }
        $offset = ($id % 5) * 0.004;
        return ['lat' => 26.9124 + $offset, 'lng' => 75.7873 - $offset];
    }

    // 8. Mumbai
    if (str_contains($c, 'mumbai')) {
        if (str_contains($loc, 'bandra')) {
            return ['lat' => 19.0596, 'lng' => 72.8295];
        }
        $offset = ($id % 5) * 0.004;
        return ['lat' => 19.0760 + $offset, 'lng' => 72.8777 - $offset];
    }

    // 9. Lucknow
    if (str_contains($c, 'lucknow')) {
        if (str_contains($loc, 'gomti')) {
            return ['lat' => 26.8500, 'lng' => 80.9900];
        }
        $offset = ($id % 5) * 0.003;
        return ['lat' => 26.8467 + $offset, 'lng' => 80.9462 - $offset];
    }

    // 10. Hyderabad
    if (str_contains($c, 'hyderabad')) {
        if (str_contains($loc, 'gachibowli')) {
            return ['lat' => 17.4401, 'lng' => 78.3489];
        }
        $offset = ($id % 5) * 0.004;
        return ['lat' => 17.3850 + $offset, 'lng' => 78.4867 - $offset];
    }

    // Comprehensive National Indian Cities Coordinates Dictionary
    $cityCoords = [
        'gaya' => [24.7914, 85.0002],
        'bhagalpur' => [25.2425, 86.9842],
        'muzaffarpur' => [26.1209, 85.3647],
        'darbhanga' => [26.1542, 85.8918],
        'purnia' => [25.7771, 87.4753],
        'begusarai' => [25.4182, 86.1272],
        'munger' => [25.3757, 86.4744],
        'samastipur' => [25.8629, 85.7811],
        'sasaram' => [24.9535, 84.0321],
        'bihar sharif' => [25.1982, 85.5149],
        'arrah' => [25.5560, 84.6603],
        'bhojpur' => [25.5560, 84.6603],
        'noida' => [28.5355, 77.3910],
        'greater noida' => [28.4744, 77.5040],
        'ghaziabad' => [28.6692, 77.4538],
        'gurugram' => [28.4595, 77.0266],
        'faridabad' => [28.4089, 77.3178],
        'kanpur' => [26.4499, 80.3319],
        'varanasi' => [25.3176, 82.9739],
        'agra' => [27.1767, 78.0081],
        'prayagraj' => [25.4358, 81.8463],
        'allahabad' => [25.4358, 81.8463],
        'meerut' => [28.9845, 77.7064],
        'bareilly' => [28.3670, 79.4304],
        'aligarh' => [27.8974, 78.0880],
        'gorakhpur' => [26.7606, 83.3732],
        'ayodhya' => [26.7922, 82.1998],
        'jhansi' => [25.4484, 78.5685],
        'mathura' => [27.4924, 77.6737],
        'ahmedabad' => [23.0225, 72.5714],
        'surat' => [21.1702, 72.8311],
        'vadodara' => [22.3072, 73.1812],
        'rajkot' => [22.3039, 70.8022],
        'bhavnagar' => [21.7645, 72.1519],
        'jamnagar' => [22.4707, 70.0577],
        'gandhinagar' => [23.2156, 72.6369],
        'kolkata' => [22.5726, 88.3639],
        'howrah' => [22.5958, 88.2636],
        'durgapur' => [23.5204, 87.3119],
        'asansol' => [23.6739, 86.9524],
        'siliguri' => [26.7271, 88.3953],
        'nagpur' => [21.1458, 79.0882],
        'thane' => [19.2183, 72.9781],
        'navi mumbai' => [19.0330, 73.0297],
        'nashik' => [19.9975, 73.7898],
        'aurangabad' => [19.8762, 75.3433],
        'solapur' => [17.6599, 75.9064],
        'kolhapur' => [16.7050, 74.2433],
        'bhopal' => [23.2599, 77.4126],
        'indore' => [22.7196, 75.8577],
        'gwalior' => [26.2183, 78.1828],
        'jabalpur' => [23.1815, 79.9864],
        'ujjain' => [23.1765, 75.7885],
        'ranchi' => [23.3441, 85.3096],
        'jamshedpur' => [22.8046, 86.2029],
        'dhanbad' => [23.7957, 86.4304],
        'bokaro' => [23.6693, 86.1511],
        'deoghar' => [24.4826, 86.7001],
        'bhubaneswar' => [20.2961, 85.8245],
        'cuttack' => [20.4625, 85.8828],
        'rourkela' => [22.2604, 84.8536],
        'puri' => [19.8135, 85.8312],
        'visakhapatnam' => [17.6868, 83.2185],
        'vijayawada' => [16.5062, 80.6480],
        'guntur' => [16.3067, 80.4365],
        'tirupati' => [13.6288, 79.4192],
        'warangal' => [17.9689, 79.5941],
        'chennai' => [13.0827, 80.2707],
        'coimbatore' => [11.0168, 76.9558],
        'madurai' => [9.9252, 78.1198],
        'tiruchirappalli' => [10.7905, 78.7047],
        'salem' => [11.6643, 78.1460],
        'kochi' => [9.9312, 76.2673],
        'thiruvananthapuram' => [8.5241, 76.9366],
        'kozhikode' => [11.2588, 75.7804],
        'thrissur' => [10.5276, 76.2144],
        'chandigarh' => [30.7333, 76.7794],
        'ludhiana' => [30.9010, 75.8573],
        'amritsar' => [31.6340, 74.8723],
        'jalandhar' => [31.3260, 75.5762],
        'panipat' => [29.3909, 76.9635],
        'ambala' => [30.3782, 76.7767],
        'dehradun' => [30.3165, 78.0322],
        'haridwar' => [29.9457, 78.1642],
        'rishikesh' => [30.0869, 78.2676],
        'shimla' => [31.1048, 77.1734],
        'dharamshala' => [32.2190, 76.3234],
        'guwahati' => [26.1445, 91.7362],
        'raipur' => [21.2514, 81.6296],
        'bilaspur' => [22.0797, 82.1409],
        'panaji' => [15.4909, 73.8278],
        'srinagar' => [34.0837, 74.7973],
        'jammu' => [32.7266, 74.8570],
        'mysuru' => [12.2958, 76.6394],
        'mangaluru' => [12.9141, 74.8560],
        'hubballi' => [15.3647, 75.1240],
        'jodhpur' => [26.2389, 73.0243],
        'udaipur' => [24.5854, 73.7125],
        'ajmer' => [26.4499, 74.6399],
        'bikaner' => [28.0229, 73.3119]
    ];

    foreach ($cityCoords as $key => $point) {
        if (str_contains($c, $key) || str_contains($loc, $key)) {
            $offset = ($id % 5) * 0.003;
            return ['lat' => $point[0] + $offset, 'lng' => $point[1] - $offset];
        }
    }

    // Fallback: Default India Center
    $hash = abs(crc32($location . $city . $id));
    $latOffset = (($hash % 100) / 2000.0);
    $lngOffset = ((($hash >> 4) % 100) / 2000.0);
    return ['lat' => 24.9200 + $latOffset, 'lng' => 86.2250 + $lngOffset];
}

/**
 * Master State-wise & District-wise Indian Cities Dataset
 */
function get_indian_cities_by_state() {
    return [
        'Bihar' => [
            'Jamui', 'Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Purnia',
            'Begusarai', 'Munger', 'Samastipur', 'Sasaram (Rohtas)', 'Bihar Sharif (Nalanda)',
            'Arrah (Bhojpur)', 'Katihar', 'Saharsa', 'Chapra (Saran)', 'Motihari (East Champaran)',
            'Bettiah (West Champaran)', 'Nawada', 'Buxar', 'Kishanganj', 'Sitamarhi', 'Gopalganj',
            'Madhubani', 'Siwan', 'Supaul', 'Khagaria', 'Banka', 'Lakhisarai', 'Sheikhpura',
            'Arwal', 'Jehanabad', 'Bhabua (Kaimur)', 'Sheohar', 'Hajipur (Vaishali)', 'Madhepura', 'Jamui District'
        ],
        'Uttar Pradesh' => [
            'Lucknow', 'Noida', 'Greater Noida', 'Ghaziabad', 'Kanpur', 'Varanasi', 'Agra',
            'Prayagraj (Allahabad)', 'Meerut', 'Bareilly', 'Aligarh', 'Moradabad', 'Saharanpur',
            'Gorakhpur', 'Ayodhya (Faizabad)', 'Jhansi', 'Mathura', 'Firozabad', 'Muzaffarnagar',
            'Rampur', 'Shahjahanpur', 'Farrukhabad', 'Hapur', 'Mirzapur', 'Bulandshahr', 'Sambhal',
            'Amroha', 'Hardoi', 'Fatehpur', 'Raebareli', 'Orai (Jalaun)', 'Sitapur', 'Bahraich',
            'Modinagar', 'Unnao', 'Jaunpur', 'Lakhimpur Kheri', 'Hathras', 'Banda', 'Pilibhit',
            'Barabanki', 'Deoria', 'Lalitpur', 'Mau', 'Ballia', 'Basti', 'Gonda', 'Etawah', 'Sultanpur'
        ],
        'Delhi NCR' => [
            'New Delhi', 'Central Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi',
            'North East Delhi', 'South West Delhi', 'North West Delhi', 'Dwarka', 'Rohini', 'Saket',
            'Laxmi Nagar', 'Mukherjee Nagar', 'Karol Bagh', 'Hauz Khas', 'Connaught Place', 'Janakpuri',
            'Pitampura', 'Mayur Vihar', 'Vasant Kunj', 'Gurugram', 'Noida', 'Greater Noida', 'Faridabad', 'Ghaziabad'
        ],
        'Maharashtra' => [
            'Mumbai', 'Pune', 'Nagpur', 'Thane', 'Navi Mumbai', 'Nashik', 'Chhatrapati Sambhajinagar (Aurangabad)',
            'Solapur', 'Amravati', 'Kolhapur', 'Nanded', 'Sangli', 'Jalgaon', 'Akola', 'Latur',
            'Dhule', 'Ahmednagar', 'Chandrapur', 'Parbhani', 'Jalna', 'Panvel', 'Satara', 'Beed',
            'Yavatmal', 'Gondia', 'Wardha', 'Dharashiv (Osmanabad)', 'Kalyan-Dombivli', 'Vasai-Virar', 'Mira-Bhayandar', 'Ratnagiri'
        ],
        'Karnataka' => [
            'Bengaluru', 'Mysuru', 'Hubballi-Dharwad', 'Mangaluru', 'Belagavi', 'Davanagere',
            'Ballari', 'Vijayapura', 'Shivamogga', 'Tumakuru', 'Raichur', 'Bidar', 'Hosapete',
            'Gadag', 'Hassan', 'Udupi', 'Kalaburagi (Gulbarga)', 'Chitradurga', 'Kolar', 'Mandya', 'Chikkamagaluru'
        ],
        'Rajasthan' => [
            'Kota', 'Jaipur', 'Jodhpur', 'Udaipur', 'Bikaner', 'Ajmer', 'Bhilwara', 'Alwar',
            'Sikar', 'Bharatpur', 'Pali', 'Sri Ganganagar', 'Beawar', 'Hanumangarh', 'Tonk',
            'Dausa', 'Chittorgarh', 'Churu', 'Jhunjhunu', 'Sawai Madhopur', 'Nagaur', 'Barmer', 'Jaisalmer'
        ],
        'Gujarat' => [
            'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Junagadh',
            'Gandhinagar', 'Anand', 'Navsari', 'Morbi', 'Nadiad', 'Surendranagar', 'Bharuch',
            'Mehsana', 'Bhuj', 'Porbandar', 'Vapi', 'Valsad', 'Palanpur', 'Godhra', 'Veraval', 'Patan'
        ],
        'West Bengal' => [
            'Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Bardhaman', 'Malda',
            'Baharampur', 'Habra', 'Kharagpur', 'Shantipur', 'Dankuni', 'Dhulian', 'Ranaghat',
            'Haldia', 'Darjeeling', 'Jalpaiguri', 'Cooch Behar', 'Purulia', 'Bankura', 'Krishnanagar'
        ],
        'Madhya Pradesh' => [
            'Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna',
            'Ratlam', 'Rewa', 'Murwara (Katni)', 'Singrauli', 'Burhanpur', 'Khandwa', 'Bhind',
            'Chhindwara', 'Guna', 'Shivpuri', 'Vidisha', 'Damoh', 'Mandsaur', 'Khargone', 'Neemuch', 'Hoshangabad'
        ],
        'Punjab & Haryana' => [
            'Chandigarh', 'Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Hoshiarpur',
            'Mohali', 'Pathankot', 'Faridabad', 'Gurugram', 'Panipat', 'Ambala', 'Yamunanagar',
            'Rohtak', 'Hisar', 'Karnal', 'Sonipat', 'Panchkula', 'Sirsa', 'Bhiwani', 'Bahadurgarh', 'Jind', 'Rewari'
        ],
        'Tamil Nadu' => [
            'Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tiruppur', 'Erode',
            'Tirunelveli', 'Vellore', 'Thoothukudi', 'Dindigul', 'Thanjavur', 'Ranipet', 'Sivakasi',
            'Karur', 'Udhagamandalam (Ooty)', 'Hosur', 'Nagercoil', 'Kanchipuram', 'Cuddalore', 'Kumbakonam'
        ],
        'Telangana & Andhra Pradesh' => [
            'Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Khammam', 'Ramagundam', 'Mahbubnagar', 'Nalgonda',
            'Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Kakinada', 'Rajamahendravaram',
            'Kadapa', 'Tirupati', 'Anantapur', 'Vizianagaram', 'Eluru', 'Ongole', 'Nandyal', 'Machilipatnam', 'Chittoor'
        ],
        'Kerala' => [
            'Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Kollam', 'Thrissur', 'Kannur',
            'Alappuzha', 'Kottayam', 'Palakkad', 'Malappuram', 'Manjeri', 'Thalassery', 'Ponnani', 'Kasaragod'
        ],
        'Jharkhand' => [
            'Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro Steel City', 'Deoghar', 'Phusro',
            'Hazaribagh', 'Giridih', 'Ramgarh', 'Medininagar (Daltonganj)', 'Chirkunda', 'Dumka', 'Chaibasa', 'Jhumri Telaiya'
        ],
        'Odisha' => [
            'Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri',
            'Balasore', 'Bhadrak', 'Baripada', 'Jharsuguda', 'Bargarh', 'Jeypore', 'Angul'
        ],
        'Uttarakhand & Himachal Pradesh' => [
            'Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Rishikesh', 'Kashipur', 'Nainital', 'Mussoorie',
            'Shimla', 'Dharamshala', 'Solan', 'Mandi', 'Kullu', 'Manali', 'Baddi', 'Palampur'
        ],
        'Assam & North-East' => [
            'Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur',
            'Shillong', 'Agartala', 'Imphal', 'Aizawl', 'Kohima', 'Dimapur', 'Gangtok', 'Itanagar'
        ],
        'Other States & UTs' => [
            'Raipur', 'Bilaspur', 'Durg-Bhilai', 'Korba', 'Rajnandgaon', 'Jagdalpur',
            'Panaji (Goa)', 'Margao', 'Vasco da Gama', 'Mapusa', 'Srinagar (J&K)', 'Jammu',
            'Anantnag', 'Puducherry', 'Port Blair (Andaman)'
        ]
    ];
}

/**
 * Returns Flat Sorted List of All Unique Indian Cities & Districts
 */
function get_all_indian_cities() {
    static $flatList = null;
    if ($flatList !== null) return $flatList;

    $byState = get_indian_cities_by_state();
    $list = [];
    foreach ($byState as $state => $cities) {
        foreach ($cities as $c) {
            $list[] = $c;
        }
    }
    sort($list, SORT_STRING | SORT_FLAG_CASE);
    $flatList = array_values(array_unique($list));
    return $flatList;
}

/**
 * Generate Datalist for Instant Type-to-Search on any City Input
 */
function render_indian_city_datalist($id = 'indianCitiesList') {
    $cities = get_all_indian_cities();
    $html = '<datalist id="' . htmlspecialchars($id) . '">';
    foreach ($cities as $city) {
        $html .= '<option value="' . htmlspecialchars($city) . '">';
    }
    $html .= '</datalist>';
    return $html;
}

/**
 * Render Grouped <optgroup> HTML for Select Dropdowns
 */
function render_indian_city_select_options($selectedCity = '') {
    $byState = get_indian_cities_by_state();
    $html = '';
    $selLower = strtolower(trim((string)$selectedCity));

    foreach ($byState as $state => $cities) {
        $html .= '<optgroup label="📍 ' . htmlspecialchars($state) . '">';
        foreach ($cities as $c) {
            $isSelected = ($selLower === strtolower($c)) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($c) . '" ' . $isSelected . '>' . htmlspecialchars($c) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

