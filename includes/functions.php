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

    // Fallback: Default India Coordinates
    $hash = abs(crc32($location . $city . $id));
    $latOffset = (($hash % 100) / 2000.0);
    $lngOffset = ((($hash >> 4) % 100) / 2000.0);
    return ['lat' => 24.9200 + $latOffset, 'lng' => 86.2250 + $lngOffset];
}
