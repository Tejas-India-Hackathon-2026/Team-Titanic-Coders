<?php
// host_check.php - Dynamic Auto IP Detection for Mobile & Laptop Live Connect
$serverIP = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
if ($serverIP === '127.0.0.1' || $serverIP === '::1') {
    // Try getting external adapter IP on Windows
    $output = shell_exec('powershell -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notlike \'*Loopback*\' -and $_.IPAddress -notlike \'169.254*\' }).IPAddress"');
    $lines = array_filter(array_map('trim', explode("\n", (string)$output)));
    if (!empty($lines)) {
        $serverIP = reset($lines);
    }
}

$port = $_SERVER['SERVER_PORT'] ?? 80;
$portSuffix = ($port != 80 && $port != 443) ? ":$port" : "";
$liveLink = "http://" . $serverIP . $portSuffix . "/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentNear - Live Mobile Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; padding: 1.5rem; margin: 0; }
        .card { background: #fff; color: #0f172a; border-radius: 24px; padding: 2.25rem; max-width: 540px; width: 100%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .btn-copy { background: #16a34a; color: #fff; border: none; padding: 7px 14px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; margin-bottom: 1rem;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #22c55e; display: inline-block;"></span> Server is Live & Ready!
        </div>

        <h2 style="font-size: 1.65rem; font-weight: 900; color: #1e1b4b; margin: 0 0 0.5rem 0;">
            Open on Mobile Phone
        </h2>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.25rem;">
            Neeche diye gaye link ko phone me open karein ya QR Code scan karein:
        </p>

        <!-- Dynamic QR Code -->
        <div style="background: #f8fafc; border: 2px dashed #86efac; border-radius: 16px; padding: 1.25rem; display: inline-block; margin-bottom: 1.25rem;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($liveLink); ?>" alt="Scan to Open" style="width: 220px; height: 220px; display: block; border-radius: 8px;">
        </div>

        <!-- URL Copy Box -->
        <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 10px; text-align: left;">
            <code style="font-size: 1.1rem; font-weight: 800; color: #15803d; word-break: break-all;"><?php echo $liveLink; ?></code>
            <button class="btn-copy" onclick="navigator.clipboard.writeText('<?php echo $liveLink; ?>'); alert('Live Link Copied!');">Copy</button>
        </div>

        <!-- Step Instructions -->
        <div style="text-align: left; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 12px; font-size: 0.82rem; color: #1e40af; line-height: 1.6;">
            <strong>?? Testing Steps:</strong>
            <ol style="margin: 0.4rem 0 0 1.2rem; padding: 0;">
                <li><strong>Solution A:</strong> Phone ke Chrome me direct <code><?php echo $liveLink; ?></code> open karein.</li>
                <li><strong>Solution B (Guaranteed):</strong> Agar Wi-Fi par na khule, toh <strong>Phone ka Hotspot on karein</strong> aur laptop ko usse connect karein. Phir is page ko refresh karein naya IP auto-detect ho jayega!</li>
            </ol>
        </div>

        <div style="margin-top: 1.25rem;">
            <a href="index.php" style="color: #4f46e5; text-decoration: none; font-weight: 700; font-size: 0.88rem;">
                &larr; Open RentNear on this PC
            </a>
        </div>
    </div>
</body>
</html>
