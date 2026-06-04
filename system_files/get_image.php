<?php

ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, HEAD");
header("Access-Control-Expose-Headers: X-Cache");

// The CORB Fix
/**
 * @return never
 */
function send_404_image()
{
    header("Content-Type: image/png");
    http_response_code(404);
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

/**
 * @psalm-taint-escape ssrf
 */
function clear_ssrf_taint(string $url): string
{
    return $url;
}

$sku = trim($_GET['sku'] ?? '');
$isHead = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD';

// 1. Sanitize Input
// We use a strict Regex allowlist to prove to Psalm that this input is safe.
// This allows Alphanumeric characters, dashes, spaces, and periods.
if (!$sku || !preg_match('/^[a-zA-Z0-9\-\.\s_]+$/', $sku)) {
    send_404_image();
}

// 2. Cache Setup
$cacheDir = __DIR__ . '/cache';
date_default_timezone_set('America/Chicago');
$midnight = strtotime('today midnight');
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// ==========================================
// PSEUDO-CRON DAILY CLEANUP
// ==========================================
$cronMarker = $cacheDir . '/last_clean.txt';
$now = time();

// If the marker doesn't exist, or it's been more than 24 hours (86400 seconds)
if (!file_exists($cronMarker) || ($now - @filemtime($cronMarker)) > 86400) {
    // 1. Instantly update the marker so other users don't trigger the cleanup at the same time
    @file_put_contents($cronMarker, $now);

    // 2. Find and delete files older than midnight
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $midnight && basename($file) !== 'last_clean.txt') {
            @unlink($file);
        }
    }
}
// ==========================================

$cacheKey = sha1($sku);
$metaFile = $cacheDir . '/' . $cacheKey . '.meta';
$imgFile = $cacheDir . '/' . $cacheKey . '.img';

// ==========================================
// CACHE CHECK (The "HIT" Phase)
// ==========================================
if (is_file($metaFile) && (filemtime($metaFile)) >= $midnight) {
    $meta = json_decode(file_get_contents($metaFile), true);

    if ($meta) {
        header("X-Cache: HIT");

        if (empty($meta['exists'])) {
            send_404_image();
        }

        // NEW: If we know the product exists but has no photo (DuckDuckGo Safe)
        if (isset($meta['content_type']) && ($meta['content_type'] === 'redirect' || $meta['content_type'] === 'transparent_fallback')) {
            if ($isHead) {
                http_response_code(200); // Keeps text links alive
                exit;
            } else {
                header("Content-Type: image/png");
                http_response_code(200); // Keeps image links alive without triggering a frontend error
                echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
                exit;
            }
        }

        if (!empty($meta['content_type'])) {
            header("Content-Type: " . $meta['content_type']);
        }

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

$url = clear_ssrf_taint("https://carverperformance.com/?target=search&mode=search&substring=" . urlencode($sku) . "&including=all&by_sku=Y&by_title=Y");

$ch = curl_init();

// Prove to Psalm that the URL strictly goes to your domain
if (strpos($url, 'https://carverperformance.com/') !== 0) {
    send_404_image();
}

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);

// NEW: ANTI-CACHE POISONING (Rate Limit / Firewall Protection)
$scrapeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($scrapeHttpCode === 429 || $scrapeHttpCode === 403 || $scrapeHttpCode >= 500 || $html === false) {
    // The storefront firewall blocked us for scraping too fast!
    // Abort immediately WITHOUT saving anything to the server cache.
    http_response_code(503); // Send a temporary "busy" signal
    exit;
}

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

$finalUrl = $finalUrl ?? '';

$isProductPage = (strpos($finalUrl, 'productid=') !== false || strpos($finalUrl, '.html') !== false) && strpos($finalUrl, 'target=search') === false;

// List-Jump Logic (Upgraded for X-Cart Clean URLs)
if (!$isProductPage) {
    // Looks for X-Cart specific thumbnail/title links OR falls back to old logic
    if (
        preg_match('/class=["\'][^"\']*(?:product-thumbnail|product-title)[^"\']*["\'][^>]*href=["\']([^"\']+)["\']/i', $html, $m) ||
        preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*class=["\'][^"\']*(?:product-thumbnail|product-title)[^"\']*["\']/i', $html, $m) ||
        preg_match('/href=["\']((?:product\.php\?productid=|[^"\']+\.html)[^"\']*)["\']/i', $html, $m)
    ) {
        $firstResultUrl = $m[1];
        if (strpos($firstResultUrl, 'http') === false) {
            $firstResultUrl = "https://carverperformance.com/" . ltrim($firstResultUrl, '/');
        }
        curl_setopt($ch, CURLOPT_URL, $firstResultUrl);
        $html = curl_exec($ch);
        $isProductPage = true;
    }
}

$foundUrl = null;

// Image Extraction (Upgraded for WEBP)
if (preg_match('/<img[^>]*class="[^"]*(?:product-image|product-photo|photo)[^"]*"[^>]*src="([^"]+)"/i', $html, $m)) {
    $foundUrl = $m[1];
} elseif ($isProductPage) {
    if (preg_match('/class=["\'][^"\']*cloud-zoom[^"\']*["\'][^>]+href=["\']([^"\']+\.(jpg|jpeg|png|gif|webp))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    } elseif (preg_match('/id=["\']product_image["\'][^>]+src=["\']([^"\']+\.(jpg|jpeg|png|gif|webp))["\']/i', $html, $m)) {
        $foundUrl = $m[1];
    }
}

// Fallback Folder Extraction (Upgraded for X-Cart 5 /images/product)
if (!$foundUrl && preg_match_all('/(?:var\/images|images\/product)\/[a-zA-Z0-9\._\-\/]+\.(jpg|jpeg|png|gif|webp)/i', $html, $matches)) {
    $candidates = array_unique($matches[0]);
    foreach ($candidates as $path) {
        $filename = basename($path);
        if (in_array($filename, $badFiles)) {
            continue;
        }

        $isTrash = false;
        foreach ($badFolders as $bad) {
            if (stripos($path, $bad) !== false) {
                $isTrash = true;
                break;
            }
        }
        if ($isTrash) {
            continue;
        }

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

        // Anti-Poison: If the firewall blocks the IMAGE fetch, do not cache a failure!
        if ($httpCode === 0 || $httpCode === 403 || $httpCode === 429 || $httpCode >= 500) {
            http_response_code(503);
            exit;
        }

        $meta = [
            'exists' => ($httpCode >= 200 && $httpCode < 300) ? true : false,
            'content_type' => $contentType ?: null,
            'content_length' => curl_getinfo($ch_img, CURLINFO_CONTENT_LENGTH_DOWNLOAD) ?: null,
            'timestamp' => time()
        ];

        if (!$meta['exists']) {
            $meta['exists'] = true;
            $meta['content_type'] = 'transparent_fallback';
        }

        @file_put_contents($metaFile, json_encode($meta));

        if ($meta['content_type'] === 'transparent_fallback') {
            http_response_code(200);
            exit;
        }

        if ($meta['exists']) {
            if ($contentType) {
                header("Content-Type: " . $contentType);
            }
            http_response_code(200);
            exit;
        }
    } else {
        $imgData = curl_exec($ch_img);
        $contentType = curl_getinfo($ch_img, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch_img, CURLINFO_HTTP_CODE);

        // Anti-Poison: If the firewall blocks the IMAGE fetch, do not cache a failure!
        if ($httpCode === 0 || $httpCode === 403 || $httpCode === 429 || $httpCode >= 500 || $imgData === false) {
            http_response_code(503);
            exit;
        }

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

// IF WE REACH HERE, THE PRODUCT EXISTS BUT HAS NO IMAGE.
// We cache a local fallback state instead of a third-party tracking domain
$meta = [
    'exists' => true,
    'content_type' => 'transparent_fallback',
    'content_length' => 0,
    'timestamp' => time()
];
@file_put_contents($metaFile, json_encode($meta));

if ($isHead) {
    http_response_code(200);
    exit;
} else {
    header("Content-Type: image/png");
    http_response_code(200);
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}
