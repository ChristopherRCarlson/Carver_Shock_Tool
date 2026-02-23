<?php
// draft_lookup.php - Schema v2.0 (OE-First, Split Sleeves & Store Linking)

$csvFile = 'system_files/Carver_Shocks_Database.csv';

// 1. Standard Sanitizer (For non-linked text like Description/Headers)
function display_clean($data) {
    $val = trim($data ?? '');
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val)) {
        return '<span class="empty">-</span>';
    }
    return htmlspecialchars($val);
}

// 2. NEW: Linked Sanitizer (For searchable parts)
function display_linked_part($data) {
    $val = trim($data ?? '');
    
    // Clean and check for empty
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val) || $val === '') {
        return '<span class="empty">-</span>';
    }
    
    // Create the Carver Store Search URL
    $url = "https://www.carverperformance.com/cart.php?target=search&substring=" . urlencode($val);
    
    // Return the clickable link (marked for validation)
    return '<a href="' . $url . '" target="_blank" class="part-link validate-me" data-sku="' . htmlspecialchars($val) . '">' . htmlspecialchars($val) . '</a>';
}

$results = [];
$search = $_GET['search'] ?? '';

if ($search && ($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle); // Skip header row
    while (($data = fgetcsv($handle)) !== FALSE) {
        // Search OE (Index 0), Shock PN (Index 1), or Description (Index 3)
        if (stripos($data[0], $search) !== FALSE || 
            stripos($data[1], $search) !== FALSE || 
            stripos($data[3], $search) !== FALSE ) {
            $results[] = $data;
        }
    }
    fclose($handle);
    if (empty($results)) {
        $logFile = 'system_files/missing_skus.log';
        $timestamp = date("Y-m-d H:i:s");
        $logEntry = "[$timestamp] IP: {$_SERVER['REMOTE_ADDR']} | Searched: " . $search . PHP_EOL;
        
        $logHandle = fopen($logFile, 'a');
        if ($logHandle !== FALSE && flock($logHandle, LOCK_EX)) {
            fwrite($logHandle, $logEntry);
            flock($logHandle, LOCK_UN);
            fclose($logHandle);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carver Shock Lookup v2.0</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .search-box { text-align: center; margin-bottom: 20px; padding: 20px; background: #eee; border-radius: 8px; }
        input[type="text"] { padding: 10px; width: 60%; font-size: 16px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 20px; font-size: 16px; background-color: #d9534f; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #c9302c; }

        .result-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fff; border-left: 5px solid #d9534f; }
        
        .result-header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .oe-title { font-size: 1.4em; font-weight: bold; color: #d9534f; margin-bottom: 5px; }
        
        /* Grid Layout for Specs */
        .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .spec-item { background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 0.9em; }
        .spec-label { display: block; font-weight: bold; color: #555; font-size: 0.8em; margin-bottom: 2px; }
        .spec-value { display: block; color: #333; font-weight: 500; }
        
        /* NEW: Part Link Styling */
        .part-link { color: #d9534f; text-decoration: none; border-bottom: 1px dotted #d9534f; }
        .part-link:hover { background-color: #d9534f; color: white; text-decoration: none; border-bottom: none; }
        .part-link.dead-link{ color: #000!important; text-decoration: none!important; border-bottom: none!important; cursor: default!important; pointer-events: none; }

        .empty { color: #ccc; font-style: italic; }

        /* Mounting Box Styling (Sleeve Split) */
        .mounting-box { grid-column: 1 / -1; background: #fdfdfe; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; margin-top: 5px; }
        .section-title { display: block; font-size: 0.85em; font-weight: bold; color: #666; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #eee; padding-bottom: 3px; }
        .sleeve-pair { display: flex; gap: 10px; }
        .sleeve-pair > div { background: #fff; padding: 5px; border: 1px solid #eee; border-radius: 3px; }

        @media (max-width: 600px) {
            input[type="text"] { width: 100%; margin-bottom: 10px; }
            .sleeve-pair { flex-direction: column; }
        }

        .maintenance-section {
            display: flex;
            gap: 15px;
            margin-bottom: 15px 0;
            background: #fdfdfd;
            padding: 15px;
            justify-content: space-between;
        }

        .kit-card {
            flex: 1;
            max-width: 48%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            width: 150px;
            cursor: default;
            transition: transform 0.2s;
        }

        .kit-card:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .kit-thumb {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 8px;
            background: #eee;
            cursor: zoom-in;
        }

        .kit-type-label {
            display: block;
            font-size: 0.8em;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        /* Modal for expanding image */
        #kit-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
        }
        
        #kit-modal img {
            max-width: 90%;
            max-height: 90%;
            background: white;
            padding: 10px;
        }

        /* 1-Page Print Optimization */
        @media print {
            @page { margin: 0.5in; } /* Shrinks default browser paper margins */
            body { background: white !important; font-size: 12px; } /* Scales down text slightly */
            
            /* Hide non-essentials */
            .global-nav, #kit-modal { display: none !important; }
            
            /* Remove shadows and reset container */
            .container { box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            
            /* Tighten up the Search Box space */
            .search-box { padding: 10px; margin-bottom: 10px; background: white; border: 1px solid #ddd; }
            .search-box h2 { font-size: 16px; margin: 0 0 10px 0; }
            input[type="text"], button { padding: 5px; font-size: 12px; }
            
            /* Compress the Result Card and Images */
            .result-card { border: 2px solid #000; padding: 10px; margin-bottom: 10px; break-inside: avoid; page-break-inside: avoid; }
            .maintenance-section { padding: 10px; margin-bottom: 10px; }
            .kit-thumb { max-height: 80px; width: auto; margin-bottom: 2px; } /* Crucial for saving vertical space */
            
            /* Clean up the grid for ink */
            .spec-item { background: transparent; border: 1px solid #ccc; padding: 4px; }
            .part-link { text-decoration: none; color: black; border: none; }
        }
    </style>
    <script>
        // Core Logic placed in HEAD to avoid Race Conditions
        const CACHE_KEY = 'cp_sku_cache';
        const CACHE_TTL = 7 * 24 * 60 * 60 * 1000; // 7 Days in ms

        function getCache() {
            try { return JSON.parse(localStorage.getItem(CACHE_KEY) || '{}'); }
            catch (e) { return {}; }
        }

        function setCache(sku, isValid) {
            const cache = getCache();
            cache[sku] = { v: isValid, t: Date.now() };
            localStorage.setItem(CACHE_KEY, JSON.stringify(cache));
        }

        function invalidateLink(element, partNum) {
            const isNoSku = (!partNum || partNum.trim() === '-' || partNum.trim().toUpperCase() === 'N/A');
            
            // CASE 1: It's a Kit Image
            if (element.tagName === 'IMG') {
                element.src = "https://placehold.co/150x150?text=" + (isNoSku ? "No+SKU" : "No+Photo");
                element.style.cursor = "default";
                element.onclick = null;

                const linkContainer = element.nextElementSibling;
                if (linkContainer) {
                    const link = linkContainer.querySelector('a');
                    if (link) {
                        // Replace the text link below the image too
                        const span = document.createElement('span');
                        span.className = 'empty dead-link';
                        span.textContent = link.textContent;
                        link.parentNode.replaceChild(span, link);
                    }
                }
            } 
            // CASE 2: It's a Text Link
            else if (element.tagName === 'A') {
                // The Nuclear Option: Destroy the link and replace it with a span
                const span = document.createElement('span');
                span.className = 'empty dead-link'; // Inherits your grey/black styling
                span.textContent = element.textContent;
                
                // Swap them out in the DOM
                element.parentNode.replaceChild(span, element);
                
                // Update cache so we don't check this again
                setCache(partNum, false);
            }
        }

        function openKitModal(partNum) {
            if (!partNum || partNum === 'N/A' || partNum === '-') return;
            const modalImg = document.getElementById('modal-img');
            modalImg.src = "https://carverperformance.com/get_image.php?sku=" + encodeURIComponent(partNum);
            document.getElementById('kit-modal').style.display = 'flex';
        }
    </script>
</head>
<body>
    <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="index.php" style="color: #d9534f; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #d9534f; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>
    
    <div class="container">
        <h1 style="text-align: center; color: #d9534f; margin-bottom: 20px;">
            Carver Shock Lookup Tool
        </h1>

        <div class="search-box">
            <h2>Carver Digital Infrastructure: Shock Lookup</h2>
            <form method="GET">
                <input type="text" name="search" placeholder="Enter OE#, Shock#, or Vehicle Details..." value="<?= htmlspecialchars($search) ?>" autofocus>
                <button type="submit">SEARCH</button>
            </form>
        </div>

        <?php if ($search && empty($results)): ?>
            <div style="text-align:center; padding: 20px; color: #666;">
                No results found for "<strong><?= htmlspecialchars($search) ?></strong>".<br>
                Try entering part of the number (e.g., "51400" or "932-10").
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
                <div class="result-card">
                    <div class="result-header">
                        <div class="result-header" style="position: relative;">
                            <a href="mailto:christopherrcarlson101@gmail.com?subject=Data Error Report: SKU <?= htmlspecialchars($row[0]) ?>&body=Please describe the error for OE P/N <?= htmlspecialchars($row[0]) ?>:" 
                                style="position: absolute; right: 0; top: 0; font-size: 0.8em; color: #888; text-decoration: underline;">
                                Report Data Error
                            </a>
                            <div class="oe-title">OE: <?= display_clean($row[0]) ?></div>
                            <div style="color: #666; font-size: 1.1em;">Shock P/N: <strong><?= display_clean($row[1]) ?></strong></div>
                            <div style="margin-top:5px; font-style: italic; color: #000;"><?= display_clean($row[3]) ?></div>
                        </div>
                    </div>

                    <?php
                        // --- NEW: PHP SERVER-SIDE BLANK IMAGE HANDLER ---
                        
                        // 1. Process Rebuild Kit ($row[2])
                        $rebuild_sku = trim($row[2] ?? '');
                        if (!$rebuild_sku || $rebuild_sku === '-' || strtoupper($rebuild_sku) === 'N/A') {
                            $rebuild_img = "https://placehold.co/150x150?text=No+SKU";
                            $rebuild_data = ""; // Keep blank so JS Observer ignores it completely
                        } else {
                            $rebuild_img = "https://carverperformance.com/get_image.php?sku=" . urlencode($rebuild_sku);
                            $rebuild_data = htmlspecialchars($rebuild_sku);
                        }

                        // 2. Process Service Kit ($row[4])
                        $service_sku = trim($row[4] ?? '');
                        if (!$service_sku || $service_sku === '-' || strtoupper($service_sku) === 'N/A') {
                            $service_img = "https://placehold.co/150x150?text=No+SKU";
                            $service_data = ""; // Keep blank so JS Observer ignores it completely
                        } else {
                            $service_img = "https://carverperformance.com/get_image.php?sku=" . urlencode($service_sku);
                            $service_data = htmlspecialchars($service_sku);
                        }
                    ?>

                    <div class="maintenance-section">
                        <div class="kit-card">
                            <span class="kit-type-label" style="color: #1e7e34;">Rebuild Kit</span>
                            
                            <img class="kit-thumb" 
                                src="<?= $rebuild_img ?>" 
                                data-sku="<?= $rebuild_data ?>"
                                onclick="openKitModal('<?= addslashes($rebuild_sku) ?>')"
                                onerror="invalidateLink(this, '<?= addslashes($rebuild_sku) ?>')"
                                alt="Rebuild Kit">
                            
                            <div style="font-weight: bold; font-size: 0.9em;">
                                <?= display_linked_part($row[2]) ?>
                            </div>
                        </div>

                        <div class="kit-card">
                            <span class="kit-type-label" style="color: #856404;">Service Kit</span>
                            
                            <img class="kit-thumb" 
                                src="<?= $service_img ?>" 
                                data-sku="<?= $service_data ?>"
                                onclick="openKitModal('<?= addslashes($service_sku) ?>')"
                                onerror="invalidateLink(this, '<?= addslashes($service_sku) ?>')"
                                alt="Service Kit">
                            
                            <div style="font-weight: bold; font-size: 0.9em;">
                                <?= display_linked_part($row[4]) ?>
                            </div>
                        </div>
                    </div>

                    <div class="spec-grid">
                        <div class="spec-item"><span class="spec-label">Shaft</span><span class="spec-value"><?= display_linked_part($row[12]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Body</span><span class="spec-value"><?= display_linked_part($row[7]) ?></span></div>

                        <div class="mounting-box">
                            <span class="section-title">Body End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[21]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[24]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[25]) ?></div>
                            </div>
                        </div>

                        <div class="mounting-box">
                            <span class="section-title">Eyelet End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[26]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[29]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[30]) ?></div>
                            </div>
                        </div>
                    </div> 
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div id="kit-modal" onclick="this.style.display='none'">
            <img id="modal-img" src="">
        </div>

        <script>
            window.addEventListener('load', function() {
                // 1. Select BOTH Kit Images AND the Text Links
                // We combine them into one list called 'targets'
                const targets = document.querySelectorAll('img.kit-thumb, .part-link.validate-me');
                const cache = getCache();
                
                // --- BATCHING ENGINE ---
                let validationQueue = [];
                let processingTimeout = null;

                function processQueue() {
                    if (validationQueue.length === 0) return;
                    
                    const batch = validationQueue.splice(0, 4);
                    
                    Promise.all(batch.map(sku => {
                        return fetch("https://carverperformance.com/get_image.php?sku=" + encodeURIComponent(sku), { method: 'HEAD' })
                            .then(res => {
                                const isValid = res.status !== 404;
                                setCache(sku, isValid); 
                                
                                // If invalid, execute the "Nuclear Option" on ALL matching elements
                                if (!isValid) {
                                    document.querySelectorAll(`[data-sku="${sku}"]`).forEach(el => invalidateLink(el, sku));
                                }
                            })
                            .catch(() => {});
                    })).then(() => {
                        if (validationQueue.length > 0) {
                            setTimeout(processQueue, 250);
                        } else {
                            processingTimeout = null;
                        }
                    });
                }

                function scheduleSkuValidation(sku) {
                    if (!validationQueue.includes(sku)) {
                        validationQueue.push(sku);
                        if (!processingTimeout) {
                            processingTimeout = setTimeout(processQueue, 200);
                        }
                    }
                }
                // -----------------------

                // 2. The Observer (Upgraded with 0ms Cache Check & 150ms Dwell Time)
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        const el = entry.target;

                        if (entry.isIntersecting) {
                            let sku = el.dataset.sku;

                            // Fallback for images
                            if (!sku && el.tagName === 'IMG') {
                                const m = el.src.match(/sku=([^&]+)/);
                                if (m) sku = decodeURIComponent(m[1]);
                                if (sku) el.dataset.sku = sku;
                            }

                            // Actively apply the placeholder for empty or invalid SKUs
                            if (!sku || sku === '-' || sku.toUpperCase() === 'N/A') { 
                                invalidateLink(el, sku || ''); 
                                observer.unobserve(el); 
                                return; 
                            }

                            // FIX 1: CHECK CACHE IMMEDIATELY (0ms Delay)
                            const cached = cache[sku];
                            if (cached && (Date.now() - cached.t < CACHE_TTL)) {
                                if (cached.v === false) invalidateLink(el, sku);
                                
                                observer.unobserve(el);
                                return; 
                            }

                            // FIX 2: ONLY WAIT IF WE ACTUALLY NEED THE SERVER (150ms delay)
                            el.dataset.timeoutId = setTimeout(() => {
                                scheduleSkuValidation(sku);
                                observer.unobserve(el);
                            }, 150); 
                        } 
                        else {
                            // If it scrolls OFF screen, cancel the pending check
                            if (el.dataset.timeoutId) {
                                clearTimeout(el.dataset.timeoutId);
                                delete el.dataset.timeoutId;
                            }
                        }
                    });
                }, { root: null, rootMargin: '100px', threshold: 0.01 });

                // 3. THIS IS THE KEY FIX:
                // We loop through 'targets' (which includes the links), not just 'images'
                targets.forEach(el => observer.observe(el));
            });
        </script>
    </div>
</body>
</html>