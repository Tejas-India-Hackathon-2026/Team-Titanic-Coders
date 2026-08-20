<?php
// qr.php - Mobile Live Connect Options
$localIP = "10.2.6.62";
$url1 = "http://" . $localIP . ":8000/";
$url2 = "http://" . $localIP . "/Rentnear/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentNear - Instant Mobile Live Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; padding: 1.5rem;">
    <div style="background: #ffffff; color: #0f172a; border-radius: 24px; padding: 2.25rem; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.75rem;">
            <i class="fa-solid fa-signal"></i> High-Speed Local Port 8000 Live
        </div>

        <h2 style="font-size: 1.6rem; font-weight: 900; margin: 0 0 0.4rem 0; color: #1e1b4b;">
            Scan & Open on Phone
        </h2>
        <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 1.25rem;">
            Apne mobile browser me neeche diya gaya direct link open karein:
        </p>

        <!-- QR Code Box -->
        <div style="background: #f8fafc; border: 2px dashed #86efac; border-radius: 16px; padding: 1.25rem; display: inline-block; margin-bottom: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($url1); ?>" alt="Scan to Open RentNear" style="width: 220px; height: 220px; display: block; border-radius: 8px;">
        </div>

        <!-- Direct Live Link 1 -->
        <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; text-align: left;">
            <div style="font-size: 0.75rem; font-weight: 800; color: #166534; text-transform: uppercase;">
                ?? Direct Port 8000 Link (Recommended):
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <code style="font-size: 1.05rem; font-weight: 800; color: #15803d;"><?php echo $url1; ?></code>
                <button onclick="navigator.clipboard.writeText('<?php echo $url1; ?>'); alert('Copied!');" style="background: #16a34a; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; cursor: pointer;">Copy</button>
            </div>
        </div>

        <!-- Direct Live Link 2 -->
        <div style="background: #f1f5f9; border-radius: 12px; padding: 0.6rem 1rem; margin-bottom: 1.25rem; text-align: left;">
            <div style="font-size: 0.72rem; color: #64748b; font-weight: 700;">
                Port 80 Fallback Link:
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                <code style="font-size: 0.88rem; color: #475569;"><?php echo $url2; ?></code>
            </div>
        </div>

        <!-- Hotspot Instructions -->
        <div style="text-align: left; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 12px; font-size: 0.8rem; color: #1e40af; line-height: 1.5;">
            <strong>?? Agar Page Load Na Ho (College / Campus Wi-Fi Security):</strong><br>
            Apne mobile ka <strong>Mobile Hotspot on karein</strong> aur laptop ko us hotspot se connect karein. Uske baad link 100% open ho jayega!
        </div>

        <div style="margin-top: 1.25rem;">
            <a href="index.php" style="color: #4f46e5; text-decoration: none; font-weight: 700; font-size: 0.85rem;">
                &larr; Open RentNear on this PC
            </a>
        </div>
    </div>
</body>
</html>
