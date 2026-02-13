<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$sku = $_GET['sku'] ?? '';

if (!$sku || $sku === '-' || $sku === 'N/A') {
    header("Location: https://placehold.co/200x150?text=No+SKU");
    exit;
}

$badFiles = [
    'placeholder.jpg', 
    'blank.gif', 
    'spacer.gif'
];

$badFolders = ['/logo/', 'simplecms', 'common', '/skins/'];

$url = "https://carverperformance.com/?target=search&mode=search&substring=" . urlencode($sku) . "&including=all";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
$html = curl_exec($ch);

$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

$isProductPage = (strpos($finalUrl, 'productid=') !== false || strpos($finalUrl, '.html') !== false) && strpos($finalUrl, 'target=search') === false;

$foundUrl = null;

if ($isProductPage) {
    if (preg_match('/class=["\'][^"\']*cloud-zoom[^"\']*["\'][^>]+href=["\']([^"\']+\.(jpg|jpeg|png|gif))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    } 
    elseif (preg_match('/id=["\']product_image["\'][^>]+src=["\']([^"\']+\.(jpg|jpeg|png|gif))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    }
} 
else {
    if (preg_match_all('/\/var\/images\/[a-zA-Z0-9\._\-\/]+\.(jpg|jpeg|png|gif)/i', $html, $matches)) {
        $candidates = array_unique($matches[0]);
        
        foreach ($candidates as $path) {
            $filename = basename($path);
            
            if (in_array($filename, $badFiles)) continue;
            
            $isTrash = false;
            foreach ($badFolders as $bad) {
                if (stripos($path, $bad) !== false) {
                    $isTrash = true;
                    break;
                }
            }
            if ($isTrash) continue;

            $foundUrl = $path;
            break; 
        }
    }
}

if ($foundUrl) {
    if (strpos($foundUrl, 'http') === false) {
        $foundUrl = "https://carverperformance.com/" . ltrim($foundUrl, '/');
    }

    $ch_img = curl_init($foundUrl);
    curl_setopt($ch_img, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_img, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_img, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $imgData = curl_exec($ch_img);
    $contentType = curl_getinfo($ch_img, CURLINFO_CONTENT_TYPE);
    curl_close($ch_img);

    if ($imgData) {
        header("Content-Type: " . $contentType);
        header("Content-Length: " . strlen($imgData));
        echo $imgData;
        exit;
    }
}

header("Location: https://placehold.co/200x150?text=Not+Found");
exit;
?>