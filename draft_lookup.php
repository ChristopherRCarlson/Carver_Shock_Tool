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
    
    // Return the clickable link
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
            stripos($data[3], $search) !== FALSE || 
            stripos($data[17], $search) !== FALSE) {
            $results[] = $data;
        }
    }
    fclose($handle);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carver Shock Lookup v2.0</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 20px auto; padding: 0 10px; background-color: #f9f9f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
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
            border-radius: 8px;
            border: 1px dashed #bbb;
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
    </style>
</head>
<body>
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
                        <div class="oe-title">OE: <?= display_clean($row[0]) ?></div>
                        <div style="color: #666; font-size: 1.1em;">
                            Shock P/N: <strong><?= display_clean($row[1]) ?></strong>
                        </div>
                        <div style="margin-top:5px; font-style: italic; color: #000;">
                            <?= display_clean($row[3]) ?>
                        </div>
                    </div>

                    <div class="maintenance-section">
                        <div class="kit-card">
                            <span class="kit-type-label" style="color: #1e7e34;">Rebuild Kit</span>
                            
                            <img class="kit-thumb" 
                                src="https://carverperformance.com/get_image.php?sku=<?= urlencode(trim($row[2])) ?>" 
                                onclick="openKitModal('<?= addslashes(trim($row[2])) ?>')"
                                onerror="invalidateLink(this, '<?= addslashes(trim($row[2])) ?>')"
                                alt="Rebuild Kit">
                            
                            <div style="font-weight: bold; font-size: 0.9em;">
                                <?= display_linked_part($row[2]) ?>
                            </div>
                        </div>

                        <div class="kit-card">
                            <span class="kit-type-label" style="color: #856404;">Service Kit</span>
                            
                            <img class="kit-thumb" 
                                src="https://carverperformance.com/get_image.php?sku=<?= urlencode(trim($row[4])) ?>" 
                                onclick="openKitModal('<?= addslashes(trim($row[4])) ?>')"
                                onerror="invalidateLink(this, '<?= addslashes(trim($row[4])) ?>')"
                                alt="Service Kit">
                            
                            <div style="font-weight: bold; font-size: 0.9em;">
                                <?= display_linked_part($row[4]) ?>
                            </div>
                        </div>
                    </div>

                    <div class="spec-grid">
                        <div class="spec-item"><span class="spec-label">Shaft</span><span class="spec-value"><?= display_linked_part($row[12]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Body</span><span class="spec-value"><?= display_linked_part($row[7]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Valve</span><span class="spec-value"><?= display_linked_part($row[17]) ?></span></div>
                        <div class="spec-item"><span class="spec-label">Base Valve</span><span class="spec-value"><?= display_linked_part($row[14]) ?></span></div>

                        <div class="mounting-box">
                            <span class="section-title">Body End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[23]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[26]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[27]) ?></div>
                            </div>
                        </div>

                        <div class="mounting-box">
                            <span class="section-title">Eyelet End Mounting</span>
                            <div class="sleeve-pair">
                                <div style="flex:1"><span class="spec-label">Bearing</span><?= display_linked_part($row[28]) ?></div>
                                <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_linked_part($row[31]) ?></div>
                                <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_linked_part($row[32]) ?></div>
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
            function openKitModal(partNum) {
                if (!partNum || partNum === 'N/A' || partNum === '-') return;
                
                const modal = document.getElementById('kit-modal');
                const modalImg = document.getElementById('modal-img');
                
                // Use the bridge script for the modal too
                modalImg.src = "https://carverperformance.com/get_image.php?sku=" + encodeURIComponent(partNum);
                modal.style.display = 'flex';
            }
            function invalidateLink(imgElement, partNum) {
                // 1. Determine which placeholder to show
                const isNoSku = (!partNum || partNum.trim() === '-' || partNum.trim().toUpperCase() === 'N/A');
                const label = isNoSku ? "No+SKU" : "No+Photo";
                
                // 2. Set the placeholder image
                imgElement.src = "https://placehold.co/150x150?text=" + label;
                
                // 3. REMOVE THE MAGNIFYING GLASS & MODAL
                imgElement.style.cursor = "default"; // Reverts zoom-in to standard arrow
                imgElement.onclick = null;           // Disables the openKitModal function

                // 4. Convert the link to black text (Co-Pilot's working logic)
                const linkContainer = imgElement.nextElementSibling;
                if (linkContainer) {
                    const link = linkContainer.querySelector('a');
                    if (link) {
                        link.outerHTML = '<span class="empty">' + link.textContent + '</span>';
                    }
                }
            }
            window.addEventListener('load', function() {
                // Find all links flagged for validation
                const linksToValidate = document.querySelectorAll('.part-link.validate-me');

                linksToValidate.forEach(link => {
                    const sku = link.getAttribute('data-sku');
                    
                    // Use a HEAD request to check existence without downloading the image
                    fetch("https://carverperformance.com/get_image.php?sku=" + encodeURIComponent(sku), {
                        method: 'HEAD'
                    })
                    .then(response => {
                        if (response.status === 404) {
                            // If the scraper can't find it, kill the link
                            link.classList.add('dead-link');
                        }
                    })
                    .catch(err => {
                        // If there's a network error, we leave the link active just in case
                    });
                });
            });
        </script> 
    </div>
</body>
</html>