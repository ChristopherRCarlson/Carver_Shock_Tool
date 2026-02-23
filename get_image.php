<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, HEAD");
header("Access-Control-Expose-Headers: X-Cache"); 

// The CORB Fix
function send_404_image() {
    header("Content-Type: image/png");
    http_response_code(404);
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

$sku = trim($_GET['sku'] ?? '');
$isHead = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD';

// 1. Sanitize Input
if (!$sku || $sku === '-' || $sku === 'N/A' || $sku === 'NA' || strpos($sku, '+') !== false || strpos($sku, ' or ') !== false) {
    send_404_image();
}

// 2. Cache Setup
$cacheDir = __DIR__ . '/cache';
$cacheTtl = 7 * 24 * 3600; // 7 days
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

// 3. Probabilistic Cleanup
if (function_exists('mt_rand') && mt_rand(1, 100) === 1) {
    $maxDeletes = 50;
    $deleted = 0;
    $now = time();
    $dh = @opendir($cacheDir);
    if ($dh) {
        while (($f = readdir($dh)) !== false) {
            if ($deleted >= $maxDeletes) break;
            if ($f === '.' || $f === '..') continue;
            $path = $cacheDir . '/' . $f;
            if (!is_file($path)) continue;
            if (!preg_match('/\.(meta|img)$/', $f)) continue;
            $mtime = @filemtime($path) ?: 0;
            if ($now - $mtime > $cacheTtl) {
                @unlink($path);
                $deleted++;
            }
        }
        closedir($dh);
    }
}

$cacheKey = sha1($sku);
$metaFile = $cacheDir . '/' . $cacheKey . '.meta';
$imgFile = $cacheDir . '/' . $cacheKey . '.img';

// ==========================================
// CACHE CHECK (The "HIT" Phase)
// ==========================================
if (is_file($metaFile) && (time() - filemtime($metaFile) < $cacheTtl)) {
    $meta = json_decode(file_get_contents($metaFile), true);
    
    if ($meta) {
        header("X-Cache: HIT"); 
        
        if (empty($meta['exists'])) {
            send_404_image();
        }

        // NEW: If we know the product exists but has no photo
        if (isset($meta['content_type']) && $meta['content_type'] === 'redirect') {
            if ($isHead) {
                http_response_code(200); // Keeps text links alive
                exit;
            } else {
                header("Location: https://placehold.co/150x150?text=No+Photo"); // Keeps image links alive
                exit;
            }
        }

        if (!empty($meta['content_type'])) header("Content-Type: " . $meta['content_type']);
        
        if ($isHead) {
            http_response_code(200);
            exit;
        }

        if (is_file($imgFile)) {
            header("Content-Length: " . filesize($imgFile));
            readfile($imgFile); 
            exit;
        }
    }
}

// ==========================================
// SCRAPING (The "MISS" Phase)
// ==========================================
header("X-Cache: MISS"); 

$badFiles = ['placeholder.jpg', 'blank.gif', 'spacer.gif'];
$badFolders = ['/logo/', 'simplecms', 'common', '/skins/'];

$url = "https://carverperformance.com/?target=search&mode=search&substring=" . urlencode($sku) . "&including=all&by_sku=Y&by_title=Y";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);

// TRAP FOR NO RESULTS (This definitively proves the part DOES NOT exist)
if (stripos($html, '0 products found') !== false || stripos($html, 'no products found') !== false) {
    $meta = [
        'exists' => false,
        'content_type' => null,
        'content_length' => null,
        'timestamp' => time()
    ];
    @file_put_contents($metaFile, json_encode($meta));
    
    send_404_image();
}

// IF WE MADE IT PAST THE TRAP, THE PRODUCT DEFINITIVELY EXISTS. 

$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$isProductPage = (strpos($finalUrl, 'productid=') !== false || strpos($finalUrl, '.html') !== false) && strpos($finalUrl, 'target=search') === false;

// List-Jump Logic
if (!$isProductPage) {
    if (preg_match('/href=["\']((?:product\.php\?productid=|[^"\']+\.html)[^"\']*)["\']/i', $html, $m)) {
        $firstResultUrl = $m[1];
        if (strpos($firstResultUrl, 'http') === false) {
            $firstResultUrl = "https://carverperformance.com/" . ltrim($firstResultUrl, '/');
        }

        curl_setopt($ch, CURLOPT_URL, $firstResultUrl);
        $html = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); 
        $isProductPage = true;
    }
}
curl_close($ch);

$foundUrl = null;

// Image Extraction
if (preg_match('/<img[^>]*class="[^"]*(?:product-image|product-photo|photo)[^"]*"[^>]*src="([^"]+)"/i', $html, $m)) {
    $foundUrl = $m[1];
} 
elseif ($isProductPage) {
    if (preg_match('/class=["\'][^"\']*cloud-zoom[^"\']*["\'][^>]+href=["\']([^"\']+\.(jpg|jpeg|png|gif))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    } 
    elseif (preg_match('/id=["\']product_image["\'][^>]+src=["\']([^"\']+\.(jpg|jpeg|png|gif))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    }
} 

if (!$foundUrl && preg_match_all('/\/var\/images\/[a-zA-Z0-9\._\-\/]+\.(jpg|jpeg|png|gif)/i', $html, $matches)) {
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

// ==========================================
// FETCH AND SAVE TO CACHE
// ==========================================
if ($foundUrl) {
    if (substr($foundUrl, 0, 2) === '//') {
        $foundUrl = 'https:' . $foundUrl;
    } elseif (strpos($foundUrl, 'http') !== 0) {
        $foundUrl = "https://carverperformance.com/" . ltrim($foundUrl, '/');
    }

    $ch_img = curl_init($foundUrl);
    curl_setopt($ch_img, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_img, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_img, CURLOPT_USERAGENT, 'Mozilla/5.0');

    if ($isHead) {
        curl_setopt($ch_img, CURLOPT_NOBODY, true);
        curl_exec($ch_img);
        $httpCode = curl_getinfo($ch_img, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch_img, CURLINFO_CONTENT_TYPE);
        curl_close($ch_img);

        $meta = [
            'exists' => ($httpCode >= 200 && $httpCode < 300) ? true : false,
            'content_type' => $contentType ?: null,
            'content_length' => curl_getinfo($ch_img, CURLINFO_CONTENT_LENGTH_DOWNLOAD) ?: null,
            'timestamp' => time()
        ];
        
        // NEW: If the image link is broken but the product exists, override the failure!
        if (!$meta['exists']) {
            $meta['exists'] = true;
            $meta['content_type'] = 'redirect';
        }

        @file_put_contents($metaFile, json_encode($meta));

        if ($meta['content_type'] === 'redirect') {
            http_response_code(200);
            exit;
        }

        if ($meta['exists']) {
            if ($contentType) header("Content-Type: " . $contentType);
            http_response_code(200);
            exit;
        }
    } else {
        $imgData = curl_exec($ch_img);
        $contentType = curl_getinfo($ch_img, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch_img, CURLINFO_HTTP_CODE);
        curl_close($ch_img);

        if ($imgData && $httpCode >= 200 && $httpCode < 300) {
            @file_put_contents($imgFile, $imgData);
            $meta = [
                'exists' => true,
                'content_type' => $contentType ?: null,
                'content_length' => strlen($imgData),
                'timestamp' => time()
            ];
            @file_put_contents($metaFile, json_encode($meta));

            header("Content-Type: " . $contentType);
            header("Content-Length: " . strlen($imgData));
            echo $imgData;
            exit;
        }
    }
}

// NEW: IF WE REACH HERE, THE PRODUCT EXISTS BUT HAS NO IMAGE.
// We cache a "redirect" state instead of sending a 404.
$meta = [
    'exists' => true,
    'content_type' => 'redirect',
    'content_length' => 0,
    'timestamp' => time()
];
@file_put_contents($metaFile, json_encode($meta));

if ($isHead) {
    http_response_code(200); // Keeps Text Links active
    exit;
} else {
    header("Location: https://placehold.co/150x150?text=No+Photo"); // Keeps Kit Images active
    exit;
}
?>