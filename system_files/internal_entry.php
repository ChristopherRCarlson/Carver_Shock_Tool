<?php
// internal_entry.php - V5.0 (Schema v2.0 & OE Integration)

$csvFile = __DIR__ . '/system_files/Carver_Shocks_Database.csv';
$message = "";

// Helper: Sanitizer
function clean_input($data) {
    $val = trim($data ?? '');
    // Added 'na' variants based on cleanup notes.
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val)) return '';
    return $val;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $handle = fopen($csvFile, "a");
    if ($handle !== FALSE && flock($handle, LOCK_EX)) {
        
        $partNum = clean_input($_POST['shock_pn']);

        $newRow = [
            clean_input($_POST['oe_pn']), // Main ID
            $partNum,
            clean_input($_POST['shock_kit']),
            clean_input($_POST['description']),
            clean_input($_POST['service_kit']),
            clean_input($_POST['bearing_cap']),
            clean_input($_POST['body_cap']),
            clean_input($_POST['body']),
            clean_input($_POST['inner_body']),
            clean_input($_POST['metering_rod']),
            clean_input($_POST['eyelet']),
            clean_input($_POST['reservoir']),
            clean_input($_POST['shaft']),
            clean_input($_POST['bearing_assembly']),
            clean_input($_POST['live_iqs_tractive']),
            clean_input($_POST['boc']),
            clean_input($_POST['res_end_cap']),
            clean_input($_POST['bypass_screws']),
            clean_input($_POST['hose']),
            clean_input($_POST['res_clamp']),
            clean_input($_POST['adjuster_rebound']),
            // Body Mount (Bifurcated)
            clean_input($_POST['body_bearing']),
            clean_input($_POST['body_oring']),
            clean_input($_POST['body_reducer']),
            clean_input($_POST['body_inner_sleeve']), // NEW
            clean_input($_POST['body_outer_sleeve']), // NEW
            // Eyelet Mount (Bifurcated)
            clean_input($_POST['eyelet_bearing']),
            clean_input($_POST['eyelet_oring']),
            clean_input($_POST['eyelet_reducer']),
            clean_input($_POST['eyelet_inner_sleeve']), // NEW
            clean_input($_POST['eyelet_outer_sleeve']) // NEW
        ];

        fputcsv($handle, $newRow);
        flock($handle, LOCK_UN);
        fclose($handle);
        header("Location: internal_entry.php?status=success&item=" . urlencode($partNum));
        exit();
    }
}

// Success Message and Styles remain the same.
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $savedItem = htmlspecialchars($_GET['item'] ?? 'Item');
    $message = "<div class='alert success'>SUCCESS: <strong>$savedItem</strong> saved.</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carver | Master Shock Entry</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e9ecef; margin: 0; padding: 0; }
        .container { max-width: 1100px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 3px solid #d9534f; padding-bottom: 10px; margin-top: 0; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; display:none; }
        
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .full { grid-column: span 4; }
        .half { grid-column: span 2; }
        
        .section-header { grid-column: span 4; background: #444; color: white; padding: 8px 12px; margin-top: 25px; font-weight: bold; border-radius: 4px; text-transform: uppercase; font-size: 0.9em; }
        
        label { display: block; font-size: 0.8em; font-weight: 700; margin-bottom: 4px; color: #555; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input:focus { border-color: #d9534f; outline: none; }
        
        /* Highlight the input being checked */
        .checking { border-color: #ffc107; background: #fffdf5; }
        .duplicate-found { border-color: #dc3545; background: #f8d7da; color: #721c24; }
        
        button { background: #d9534f; color: white; border: none; padding: 15px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; border-radius: 4px; margin-top:20px;}
        button:hover { background: #c9302c; }
        button:disabled { background: #ccc; cursor: not-allowed; }

        /* --- MOBILE OPTIMIZATION --- */
        @media (max-width: 850px) {
            /* Stack the inner/outer sleeve input boxes vertically */
            .sleeve-container {
                flex-direction: column;
                gap: 15px !important;
            }
            
            /* Ensure the inputs take up the full width of the screen */
            .sleeve-container > div,
            input[type="text"] {
                width: 100%;
            }
            
            /* Add some breathing room to the main form container */
            .form-container {
                padding: 15px;
                margin: 10px;
            }
        }
    </style>
    <script>
        function checkDuplicate(input) {
            const val = input.value.trim();
            const warningBox = document.getElementById('dup-warning');
            const submitBtn = document.getElementById('submit-btn');
            
            if (val.length < 3) return; // Don't check tiny strings

            input.classList.add('checking');
            
            fetch('api_check_duplicate.php?sku=' + encodeURIComponent(val))
                .then(response => response.json())
                .then(data => {
                    input.classList.remove('checking');
                    if (data.exists) {
                        // DUPLICATE FOUND
                        input.classList.add('duplicate-found');
                        warningBox.style.display = 'block';
                        warningBox.innerHTML = "⚠️ WARNING: Part Number <strong>" + val + "</strong> already exists in the database!";
                        submitBtn.disabled = true;
                    } else {
                        // ALL CLEAR
                        input.classList.remove('duplicate-found');
                        warningBox.style.display = 'none';
                        submitBtn.disabled = false;
                    }
                })
                .catch(err => console.error('API Error:', err));
        }
    </script>
</head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<body>
    <div style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="../index.php" style="color: #d9534f; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #d9534f; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>
    
    <div class="container">
        <h2>Master Shock Database Entry</h2>
        <?php echo $message; ?>
        <div id="dup-warning" class="alert warning"></div>
        
        <form method="POST">
            <div class="grid">
                <div class="section-header">1. Identification</div>
                <div class="half">
                    <label>OE P/N (Search Key) *</label>
                    <input type="text" name="oe_pn" required onblur="checkDuplicate(this)">
                </div>
                <div class="half"><label>Shock P/N</label><input type="text" name="shock_pn"></div>
                <div class="full"><label>Description</label><input type="text" name="description"></div>

                <div class="section-header">2. Core Components</div>
                <div><label>Rebuild Kit</label><input type="text" name="shock_kit"></div>
                <div><label>Service Kit</label><input type="text" name="service_kit"></div>
                <div><label>Shaft</label><input type="text" name="shaft"></div>
                <div><label>Body</label><input type="text" name="body"></div>
                <div><label>Inner Body</label><input type="text" name="inner_body"></div>
                <div><label>Body Cap</label><input type="text" name="body_cap"></div>
                <div><label>Bearing Cap</label><input type="text" name="bearing_cap"></div>
                <div><label>Reservoir</label><input type="text" name="reservoir"></div>
                <div><label>Res End Cap</label><input type="text" name="res_end_cap"></div>

                <div class="section-header">3. Valving & Internals</div>
                <div><label>Metering Rod</label><input type="text" name="metering_rod"></div>
                <div><label>B.O.C.</label><input type="text" name="boc"></div>
                <div><label>Bearing Assy</label><input type="text" name="bearing_assembly"></div>
                <div><label>Adj. Rebound</label><input type="text" name="adjuster_rebound"></div>
                <div class="half"><label>Live / IQS / Tractive</label><input type="text" name="live_iqs_tractive"></div>
                
                <div class="section-header">4. Reservoir Hardware</div>
                <div><label>Hose</label><input type="text" name="hose"></div>
                <div><label>Res Clamp</label><input type="text" name="res_clamp"></div>
                <div class="half"><label>Bypass Screws</label><input type="text" name="bypass_screws"></div>

                <div class="section-header">5. Mounting: Body End</div>
                <div><label>Bearing</label><input type="text" name="body_bearing"></div>
                <div><label>O-Ring</label><input type="text" name="body_oring"></div>
                <div><label>Reducer</label><input type="text" name="body_reducer"></div>
                
                <div class="full">
                    <div class="sleeve-container" style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label>Inner Sleeve</label>
                            <input type="text" name="body_inner_sleeve" placeholder="Inner">
                        </div>
                        <div style="flex: 1;">
                            <label>Outer Sleeve</label>
                            <input type="text" name="body_outer_sleeve" placeholder="Outer">
                        </div>
                    </div>
                </div>

                <div class="section-header">6. Mounting: Eyelet End</div>
                <div><label>Eyelet</label><input type="text" name="eyelet"></div> <div><label>Bearing</label><input type="text" name="eyelet_bearing"></div>
                <div><label>O-Ring</label><input type="text" name="eyelet_oring"></div>
                <div><label>Reducer</label><input type="text" name="eyelet_reducer"></div>
                

                <div class="full">
                    <div class="sleeve-container" style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label>Inner Sleeve</label>
                            <input type="text" name="eyelet_inner_sleeve" placeholder="Inner">
                        </div>
                        <div style="flex: 1;">
                            <label>Outer Sleeve</label>
                            <input type="text" name="eyelet_outer_sleeve" placeholder="Outer">
                        </div>
                </div>
            </div>
            
            <button type="submit" id="submit-btn">SAVE TO DATABASE</button>
        </form>
    </div>
</body>
</html>