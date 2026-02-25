<?php
// draft_lookup.php - Schema v3.0 (33-Column Array Integration & Restored UI)

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
    return '<a href="' . $url . '" target="_blank" class="part-link validate-link" data-sku="' . htmlspecialchars($val) . '">' . htmlspecialchars($val) . '</a>';
}

$search = $_GET['search'] ?? '';
$results = [];

if ($search && ($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle);
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $found = false;
        foreach ($data as $col) {
            if (stripos($col, $search) !== false) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $results[] = $data;
        }
    }
    fclose($handle);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Carver Draft Lookup - V3.0 Restored</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .search-box { text-align: center; margin-bottom: 20px; padding: 20px; background: #eee; border-radius: 8px; }
        input[type="text"] { padding: 10px; width: 60%; font-size: 16px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 20px; font-size: 16px; background-color: #d9534f; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #c9302c; }

        .result-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fff; border-left: 5px solid #d9534f; }
        
        .result-header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .oe-title { font-size: 1.4em; font-weight: bold; color: #333; }
        
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
        .sleeve-pair { display: flex; gap: 10px; flex-wrap: wrap; }
        .sleeve-pair > div { background: #fff; padding: 5px; border: 1px solid #eee; border-radius: 3px; }

        /* --- MOBILE OPTIMIZATION (Tablets & Phones) --- */
        @media (max-width: 850px) {
            /* Stack multiple shock result cards into a single column */
            .results-grid {
                display: grid;
                grid-template-columns: 1fr; 
                gap: 15px;
            }

            /* Stack internal sections vertically */
            .flex-container, 
            .mounting-section {
                display: flex;
                flex-direction: column; 
                gap: 10px;
            }

            /* KEEP YOUR EXISTING SLEEVE LOGIC: Stack inner/outer sleeves */
            .sleeve-pair { 
                flex-direction: column; 
            }

            /* Give the cards a bit more breathing room on phone screens */
            .result-card {
                margin: 5px;
                width: auto;
            }

            /* Make the top search bar take up the full width */
            input[type="text"] { 
                width: 100%; 
                margin-bottom: 10px; 
            }
        }

        .maintenance-section {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
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

        /* --- PROFESSIONAL SHOP SHEET PRINT ENGINE --- */
        @media print {
            @page { 
                size: portrait;
                margin: 0.3in; 
            }
            
            /* 1. Kill the 'Website' junk entirely */
            .global-nav, .search-box, #kit-modal, .nav-link, form, 
            [style*="position: absolute; right: 0; top: 0;"] { 
                display: none !important; 
            }
            
            body { 
                background: white !important; 
                font-family: "Helvetica", "Arial", sans-serif;
                font-size: 11pt; 
                color: black; 
                margin: 0;
                padding: 0;
            }

            .container { 
                box-shadow: none !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                max-width: 100% !important; 
            }

            /* 2. Format the Header (OE & Shock P/N) */
            .result-card { 
                border: 2px solid black !important; 
                padding: 15px !important; 
                margin: 0 !important;
                page-break-inside: avoid;
                border-radius: 0;
            }

            .oe-title { 
                font-size: 22pt !important; 
                border-bottom: 2px solid black;
                margin-bottom: 5px;
            }

            /* 3. The 'Use | IFP | Nitrogen' Bar */
            .result-header div[style*="italic"] {
                font-size: 12pt !important;
                font-weight: bold !important;
                margin-bottom: 15px !important;
                color: black !important;
            }

            /* 4. Compact Kit Section */
            .maintenance-section { 
                display: flex !important;
                justify-content: flex-start !important;
                gap: 20px !important;
                margin-bottom: 15px !important;
                padding: 10px !important;
                border: 1px solid #ccc;
            }

            .kit-card { 
                border: 1px solid black !important;
                padding: 5px !important;
                width: 180px !important;
                height: auto !important;
            }

            .kit-thumb { 
                max-height: 90px !important; 
                width: auto !important;
                display: block;
                margin: 0 auto 5px auto;
            }

            /* 5. The 33-Column Grid Fix */
            .spec-grid { 
                display: grid !important; 
                grid-template-columns: 1fr 1fr 1fr !important; /* 3-Column layout for readability */
                gap: 8px !important; 
            }

            .spec-item { 
                background: transparent !important; 
                border: 1px solid #ddd !important; 
                padding: 5px !important; 
            }

            .spec-label { 
                font-size: 8pt !important; 
                text-transform: uppercase;
                color: #444 !important;
            }

            .spec-value { 
                font-size: 11pt !important; 
                font-weight: bold !important;
            }

            /* 6. Mounting Boxes (Keep grouped) */
            .mounting-box { 
                grid-column: 1 / -1 !important; 
                border: 1px solid black !important; 
                margin-top: 10px !important;
                padding: 10px !important;
            }

            .section-title { 
                font-size: 10pt !important; 
                background: #eee !important;
                padding: 3px 5px !important;
                border-bottom: 1px solid black !important;
            }

            .sleeve-pair { 
                display: flex !important; 
                flex-direction: row !important; /* Force side-by-side even on 'mobile' print */
                gap: 10px !important;
                margin-top: 5px;
            }

            .sleeve-pair > div { 
                border: 1px dashed #666 !important;
                flex: 1;
            }

            /* 7. Clean up Links */
            .part-link { 
                text-decoration: none !important; 
                color: black !important; 
                border-bottom: none !important; 
            }
            
            .empty { color: #aaa !important; }
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
                span.className = 'empty dead-link'; 
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <?php foreach ($results as $row): 
                // Ensure row has exactly 33 columns before referencing them
                $row = array_pad($row, 33, '');
            ?>
                <div class="result-card">
                    <div class="result-header">
                        <div class="result-header" style="position: relative;">
                            <a href="mailto:christopherrcarlson101@gmail.com?subject=Data Error Report: SKU <?= htmlspecialchars($row[0]) ?>&body=Please describe the error for OE P/N <?= htmlspecialchars($row[0]) ?>:" 
                                style="position: absolute; right: 0; top: 0; font-size: 0.8em; color: #888; text-decoration: underline;">
                                Report Data Error
                            </a>
                            <div class="oe-title">OE: <?= display_clean($row[0]) ?></div>
                            <div style="color: #666; font-size: 1.1em;">Shock P/N: <strong><?= display_clean($row[1]) ?></strong></div>
                            
                            <div style="margin-top:5px; font-style: italic; color: #000;">
                                <?php 
                                    // Combine specific details logically
                                    $desc_parts = [];
                                    if (trim($row[2])) $desc_parts[] = "Use: " . trim($row[2]);
                                    if (trim($row[5])) $desc_parts[] = "IFP: " . trim($row[5]);
                                    if (trim($row[6])) $desc_parts[] = "Nitrogen: " . trim($row[6]) . " PSI";
                                    
                                    if (empty($desc_parts)) {
                                        echo '<span class="empty">-</span>';
                                    } else {
                                        echo htmlspecialchars(implode(" | ", $desc_parts));
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php
                        // --- PHP SERVER-SIDE BLANK IMAGE HANDLER ---
                        
                        // 1. Process Rebuild Kit ($row[3])
                        $rebuild_sku = trim($row[3] ?? '');
                        if (!$rebuild_sku || $rebuild_sku === '-' || strtoupper($rebuild_sku) === 'N/A') {
                            $rebuild_img = "https://placehold.co/150x150?text=No+SKU";
                            $rebuild_data = ""; // Keep blank so JS Observer ignores it
                        } else {
                            $rebuild_img = "https://carverperformance.com/get_image.php?sku=" . urlencode($rebuild_sku);
                            $rebuild_data = htmlspecialchars($rebuild_sku);
                        }

                        // 2. Process Service Kit ($row[4])
                        $service_sku = trim($row[4] ?? '');
                        if (!$service_sku || $service_sku === '-' || strtoupper($service_sku) === 'N/A') {
                            $service_img = "https://placehold.co/150x150?text=No+SKU";
                            $service_data = ""; // Keep blank so JS Observer ignores it
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
                                <?= display_linked_part($row[3]) ?>
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
                        <div class="spec-item"><span class="spec-label">Shaft</span><span class="spec-value"><?= display_linked_part($row[7]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Seal Head</span><span class="spec-value"><?= display_linked_part($row[8]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">BO Bumper</span><span class="spec-value"><?= display_linked_part($row[9]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Body</span><span class="spec-value"><?= display_linked_part($row[10]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Inner Body</span><span class="spec-value"><?= display_linked_part($row[11]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Body Cap</span><span class="spec-value"><?= display_linked_part($row[12]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Bearing Cap</span><span class="spec-value"><?= display_linked_part($row[13]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Metering Rod</span><span class="spec-value"><?= display_linked_part($row[16]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Knob - Rebound Adjust</span><span class="spec-value"><?= display_linked_part($row[17]) ?></span></div>

                        <div class="mounting-box">
                            <span class="section-title">Reservoir Assembly</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Reservoir</span><?= display_linked_part($row[14]) ?></div>
                                <div style="flex:1"><span class="spec-label">End Cap</span><?= display_linked_part($row[15]) ?></div>
                                <div style="flex:1"><span class="spec-label">Hose</span><?= display_linked_part($row[18]) ?></div>
                                <div style="flex:1"><span class="spec-label">Clamp</span><?= display_linked_part($row[19]) ?></div>
                                <div style="flex:1"><span class="spec-label">Bypass Screws</span><?= display_linked_part($row[20]) ?></div>
                            </div>
                        </div>

                        <div class="mounting-box">
                            <span class="section-title">Body End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[21]) ?></div>
                                <div style="flex:1"><span class="spec-label">O-Ring</span><?= display_linked_part($row[22]) ?></div>
                                <div style="flex:1"><span class="spec-label">Reducer</span><?= display_linked_part($row[23]) ?></div>
                                <div style="flex:1"><span class="spec-label">Spacer</span><?= display_linked_part($row[24]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[25]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[26]) ?></div>
                            </div>
                        </div>

                        <div class="mounting-box">
                            <span class="section-title">Shaft End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Eyelet</span><?= display_linked_part($row[27]) ?></div>
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[28]) ?></div>
                                <div style="flex:1"><span class="spec-label">O-Ring</span><?= display_linked_part($row[29]) ?></div>
                                <div style="flex:1"><span class="spec-label">Reducer</span><?= display_linked_part($row[30]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[31]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[32]) ?></div>
                            </div>
                        </div>
                    </div> 
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div id="kit-modal" onclick="this.style.display='none'">
            <img id="modal-img" src="" alt="Expanded View">
        </div>

        <script>
            // --- JAVASCRIPT OBSERVER FIX (Scroll Trigger Validation) ---
            document.addEventListener("DOMContentLoaded", function() {
                const cache = getCache();

                // 1. Grab ALL links and images that have a data-sku attached
                const targets = document.querySelectorAll('.validate-link[data-sku], .kit-thumb[data-sku]');
                
                const observer = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        const el = entry.target;
                        const sku = el.dataset.sku;
                        
                        if (!sku) return;

                        if (entry.isIntersecting) {
                            // FIX 1: CHECK CACHE IMMEDIATELY (0ms Delay)
                            const cached = cache[sku];
                            if (cached && (Date.now() - cached.t < CACHE_TTL)) {
                                if (cached.v === false) invalidateLink(el, sku);
                                
                                observer.unobserve(el);
                                return; 
                            }

                            // FIX 2: ONLY WAIT IF WE ACTUALLY NEED THE SERVER (150ms delay)
                            el.dataset.timeoutId = setTimeout(() => {
                                // Double check if we already checked this in another frame
                                const freshCache = getCache();
                                if (freshCache[sku] && freshCache[sku].v === false) {
                                    invalidateLink(el, sku);
                                } else {
                                    // Send a very fast HEAD request
                                    fetch('https://carverperformance.com/get_image.php?sku=' + encodeURIComponent(sku), { method: 'HEAD' })
                                        .then(response => {
                                            const isValid = response.ok;
                                            setCache(sku, isValid);
                                            if (!isValid) invalidateLink(el, sku);
                                        })
                                        .catch(() => {});
                                }
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

                // 3. Loop through everything needing validation
                targets.forEach(el => observer.observe(el));
            });
        </script>
    </div>
</body>
</html>