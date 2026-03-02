<?php
// history.php - Version History Viewer

$oe_pn = $_GET['oe'] ?? '';
$logFile = __DIR__ . '/system_files/audit_logs.csv';
$logs = [];
$searched = false;

// The 35 column headers so we can make the changes human-readable
$columns = [
    "OE P/N", "Shock P/N", "Product/Use", "Location", "Rebuild Kit", "Service Kit",
    "IFP Depth", "Nitrogen PSI", "Shaft", "Seal Head", "B.O. Bumper", "Body",
    "Inner Body", "Body Cap", "Bearing Cap", "Reservoir", "Res. End Cap", "Metering Rod",
    "Adj. Rebound", "Hose", "Res. Clamp", "Bypass Screws", "Body Bearing", "Body O-Ring",
    "Body Reducer", "Body Spacer", "Body Inner Sleeve", "Body Outer Sleeve", "Shaft Eyelet",
    "Shaft Bearing", "Shaft O-Ring", "Shaft Reducer", "Shaft Spacer", "Shaft Inner Sleeve", "Shaft Outer Sleeve"
];

if (!empty($oe_pn)) {
    $searched = true;
    if (file_exists($logFile) && ($handle = fopen($logFile, "r")) !== FALSE) {
        $headers = fgetcsv($handle); // Skip header row
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Check if record_id matches the requested OE
            if (isset($data[2]) && strcasecmp(trim($data[2]), $oe_pn) === 0) {
                $logs[] = [
                    'action' => strtoupper(trim($data[3] ?? 'UNKNOWN')),
                    'old_data' => json_decode(trim($data[4] ?? '[]'), true) ?: [],
                    'new_data' => json_decode(trim($data[5] ?? '[]'), true) ?: [],
                    'changed_by' => trim($data[6] ?? 'Unknown IP'),
                    'timestamp' => trim($data[7] ?? 'N/A')
                ];
            }
        }
        fclose($handle);
    }
}

// Sort logs by timestamp (Newest First)
usort($logs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Version History Lookup</title>
    <style>
        /* --- EXACT CSS FROM DRAFT_LOOKUP --- */
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

        /* --- AUDIT LOG SPECIFIC ADDITIONS --- */
        .log-entry { border: 1px solid #ddd; margin-bottom: 20px; border-radius: 4px; overflow: hidden; background: #fff; border-left: 5px solid #d9534f; }
        .log-header { background: #f8f9fa; padding: 12px 15px; display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #eee; }
        .log-body { padding: 20px; }
        .CREATE { color: #5cb85c; }
        .UPDATE { color: #0275d8; }
        .DELETE { color: #d9534f; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #eee; padding: 12px; text-align: left; font-size: 0.9em; }
        th { background-color: #fcfcfc; color: #555; font-size: 0.8em; text-transform: uppercase; }
        .old-val { color: #d9534f; text-decoration: line-through; background: #fff5f5; padding: 2px 5px; }
        .new-val { color: #5cb85c; font-weight: bold; background: #f5fff5; padding: 2px 5px; }
        .empty-state { text-align: center; color: #777; padding: 40px 0; font-style: italic; }
    </style>
</head>
<body>
    
    <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="index.php" style="color: #d9534f; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #d9534f; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>

    <div class="container">
        <div class="search-box">
            <form method="GET" action="history.php" style="display: flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap;">
                <input type="text" name="oe" placeholder="Enter OE P/N (e.g., 6964)" value="<?php echo htmlspecialchars($oe_pn); ?>" required autofocus>
                <button type="submit">SEARCH AUDIT LOGS</button>
            </form>
        </div>

        <?php if (!$searched): ?>
            <div class="empty-state">Enter a shock OE P/N above to view its version history.</div>
        <?php elseif (empty($logs)): ?>
            <div class="empty-state">No version history found for OE: <strong><?php echo htmlspecialchars($oe_pn); ?></strong></div>
        <?php else: ?>
            <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">Audit History for OE: <?php echo htmlspecialchars($oe_pn); ?></h3>
            
            <?php foreach ($logs as $log): ?>
                <div class="log-body">
                        <?php if ($log['action'] === 'CREATE'): ?>
                            <p style="margin: 0 0 15px 0; color: #5cb85c; font-weight: bold;">Initial record created with the following data:</p>
                            <table>
                                <tr>
                                    <th>Field</th>
                                    <th>Value Entered</th>
                                </tr>
                                <?php 
                                for ($i = 0; $i < count($columns); $i++) {
                                    $val = $log['new_data'][$i] ?? '';
                                    if ($val !== '') { // Only show fields that actually have data
                                        echo "<tr>";
                                        echo "<td><strong>{$columns[$i]}</strong></td>";
                                        echo "<td class='new-val'>" . htmlspecialchars($val) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </table>

                        <?php elseif ($log['action'] === 'DELETE'): ?>
                            <p style="margin: 0 0 15px 0; color: #d9534f; font-weight: bold;">Record was completely deleted. Final state before deletion:</p>
                            <table>
                                <tr>
                                    <th>Field</th>
                                    <th>Deleted Value</th>
                                </tr>
                                <?php 
                                for ($i = 0; $i < count($columns); $i++) {
                                    $val = $log['old_data'][$i] ?? '';
                                    if ($val !== '') {
                                        echo "<tr>";
                                        echo "<td><strong>{$columns[$i]}</strong></td>";
                                        echo "<td class='old-val'>" . htmlspecialchars($val) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </table>

                        <?php elseif ($log['action'] === 'UPDATE'): ?>
                            <p style="margin: 0 0 15px 0; color: #0275d8; font-weight: bold;">The following fields were modified:</p>
                            <table>
                                <tr>
                                    <th>Field</th>
                                    <th>Old Value</th>
                                    <th>New Value</th>
                                </tr>
                                <?php 
                                // Compare old and new arrays
                                $changesFound = false;
                                for ($i = 0; $i < count($columns); $i++) {
                                    $oldVal = $log['old_data'][$i] ?? '';
                                    $newVal = $log['new_data'][$i] ?? '';
                                    
                                    if ($oldVal !== $newVal) {
                                        $changesFound = true;
                                        echo "<tr>";
                                        echo "<td><strong>{$columns[$i]}</strong></td>";
                                        echo "<td class='old-val'>" . htmlspecialchars($oldVal) . "</td>";
                                        echo "<td class='new-val'>" . htmlspecialchars($newVal) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                if (!$changesFound) {
                                    echo "<tr><td colspan='3' style='text-align: center; color: #777;'>Form was saved, but no actual values were changed.</td></tr>";
                                }
                                ?>
                            </table>
                        <?php endif; ?>
                    </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>