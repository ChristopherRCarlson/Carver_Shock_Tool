<?php
$sku = '11-02448';
$url = "https://carverperformance.com/?target=search&mode=search&substring=" . urlencode($sku) . "&including=all&by_sku=Y&by_title=Y";

echo "Searching: " . $url . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Landed on: " . $finalUrl . "\n\n";

if (stripos($html, '0 products found') !== false || stripos($html, 'no products found') !== false) {
    echo "FAILED: X-Cart returned '0 products found' trap.\n";
    exit;
}

echo "X-Cart confirms product exists. Scanning raw HTML for image tags...\n\n";

preg_match_all('/<img[^>]+>/i', $html, $all_imgs);
$found = false;

echo "--- FOUND THESE PRODUCT IMAGE TAGS ---\n\n";
foreach ($all_imgs[0] as $img) {
    // Filter out irrelevant UI elements (logos, cart icons) to keep output clean
    if (strpos($img, 'product') !== false || strpos($img, 'photo') !== false || strpos($img, '/var/images/') !== false) {
        echo trim($img) . "\n\n";
        $found = true;
    }
}

if (!$found) {
    echo "WARNING: Could not find ANY product-related <img> tags. X-Cart might be using Javascript to load them.\n";
}
?>