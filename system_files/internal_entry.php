<?php
// internal_entry.php - V6.4 (Schema v3.0 - 35 Columns, Unified UI & Mobile Optimized)

$csvFile = __DIR__ . '/Carver_Shocks_Database.csv';
$message = "";

// --- MILESTONE 4: Include Audit Logger ---
require_once __DIR__ . '/audit_logger.php';

// Catch the redirect success messages
if (isset($_GET['status']) && isset($_GET['oe'])) {
    $safe_oe = htmlspecialchars($_GET['oe']);
    if ($_GET['status'] === 'updated') {
        $message = "<div class='success'>Success: Shock " . $safe_oe . " UPDATED!</div>";
    } elseif ($_GET['status'] === 'added') {
        $message = "<div class='success'>Success: New Shock " . $safe_oe . " ADDED!</div>";
    } elseif ($_GET['status'] === 'deleted') {
        $message = "<div class='success' style='background-color: #fff3cd; color: #856404; border-color: #ffeeba;'>Success: Shock " . $safe_oe . " DELETED!</div>";
    } elseif ($_GET['status'] === 'not_found') {
        $message = "<div class='error'>Error: Shock " . $safe_oe . " not found for deletion.</div>";
    }
}

// Helper: Sanitizer
function clean_input($data)
{
    $val = trim($data ?? '');
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val)) {
        return '';
    }
    return $val;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $oeNum = clean_input($_POST['oe_pn']);
    $actionType = $_POST['form_action'] ?? 'save'; // Determine if saving or deleting.

    // Build the 33-column array exactly as the CSV expects
    $newRow = [
        $oeNum,
        clean_input($_POST['shock_pn'] ?? ''),
        clean_input($_POST['product_use'] ?? ''),
        clean_input($_POST['location'] ?? ''),
        clean_input($_POST['rebuild_kit'] ?? ''),
        clean_input($_POST['service_kit'] ?? ''),
        clean_input($_POST['ifp_depth'] ?? ''),
        clean_input($_POST['nitrogen_psi'] ?? ''),
        clean_input($_POST['shaft'] ?? ''),
        clean_input($_POST['seal_head'] ?? ''),
        clean_input($_POST['bo_bumper'] ?? ''),
        clean_input($_POST['body'] ?? ''),
        clean_input($_POST['inner_body'] ?? ''),
        clean_input($_POST['body_cap'] ?? ''),
        clean_input($_POST['bearing_cap'] ?? ''),
        clean_input($_POST['reservoir'] ?? ''),
        clean_input($_POST['res_end_cap'] ?? ''),
        clean_input($_POST['metering_rod'] ?? ''),
        clean_input($_POST['adj_rebound'] ?? ''),
        clean_input($_POST['hose'] ?? ''),
        clean_input($_POST['res_clamp'] ?? ''),
        clean_input($_POST['bypass_screws'] ?? ''),
        clean_input($_POST['body_bearing'] ?? ''),
        clean_input($_POST['body_oring'] ?? ''),
        clean_input($_POST['body_reducer'] ?? ''),
        clean_input($_POST['body_spacer'] ?? ''),
        clean_input($_POST['body_inner_sleeve'] ?? ''),
        clean_input($_POST['body_outer_sleeve'] ?? ''),
        clean_input($_POST['shaft_eyelet'] ?? ''),
        clean_input($_POST['shaft_bearing'] ?? ''),
        clean_input($_POST['shaft_oring'] ?? ''),
        clean_input($_POST['shaft_reducer'] ?? ''),
        clean_input($_POST['shaft_spacer'] ?? ''),
        clean_input($_POST['shaft_inner_sleeve'] ?? ''),
        clean_input($_POST['shaft_outer_sleeve'] ?? '')
    ];

    $tempFile = $csvFile . '.tmp';
    $updated = false;
    $status = '';

    if (($handle = fopen($csvFile, "r")) !== false && ($tempHandle = fopen($tempFile, "w")) !== false) {
        $headers = fgetcsv($handle);
        if ($headers !== false) {
            fputcsv($tempHandle, $headers);
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (strcasecmp(trim($data[0]), $oeNum) === 0) {
                if ($actionType === 'delete') {
                    // MILESTONE 4: Log the DELETE action and skip writing to temp file.
                    log_audit_action('Carver_Shocks_Database', $oeNum, 'DELETE', $data, []);
                    $updated = true;
                    $status = 'deleted';
                } else {
                    // MILESTONE 4: Log the UPDATE action and write new row.
                    fputcsv($tempHandle, $newRow);
                    log_audit_action('Carver_Shocks_Database', $oeNum, 'UPDATE', $data, $newRow);
                    $updated = true;
                    $status = 'updated';
                }
            } else {
                fputcsv($tempHandle, $data);
            }
        }
        fclose($handle);

        if (!$updated && $actionType !== 'delete') {
            fputcsv($tempHandle, $newRow);
            // MILESTONE 4: Log the CREATE action.
            log_audit_action('Carver_Shocks_Database', $oeNum, 'CREATE', [], $newRow);
            $status = 'added';
        } elseif (!$updated && $actionType === 'delete') {
            // Tried to delete a shock that doesn't exist.
            $status = 'not_found';
        }
        fclose($tempHandle);

        if (rename($tempFile, $csvFile)) {
            $safe_redirect = basename(__FILE__);
            $header_str = "Location: " . $safe_redirect . "?status=" . urlencode($status) . "&oe=" . urlencode($oeNum);

            /** @psalm-suppress TaintedHeader, TaintedInput */
            header($header_str);
            exit;
        } else {
            $message = "<div class='error'>Error: Could not replace the database file. Check permissions.</div>";
        }
    } else {
        $message = "<div class='error'>Error: Could not open the database file.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carver Shock Tool | Data Entry</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding-bottom: 20px; }
        .container { max-width: 1200px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #d9534f; padding-bottom: 10px; color: #333; margin-top: 0; font-size: 1.8em; }
        .success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }

        .form-section { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #fafafa; }
        .form-section h3 { margin-top: 0; color: #d9534f; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-weight: 600; margin-bottom: 5px; font-size: 0.9em; color: #555; }
        input[type="text"], input[type="number"], select { padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; } /* Larger text for mobile taps */
        input[type="text"]:focus, select:focus { border-color: #d9534f; outline: none; box-shadow: 0 0 5px rgba(217, 83, 79, 0.2); }

        .btn { background-color: #d9534f; color: white; border: none; padding: 15px 20px; font-size: 18px; border-radius: 4px; cursor: pointer; display: block; width: 100%; font-weight: bold; margin-top: 10px; }
        .btn:hover { background-color: #c9302c; }

        .btn-clear { background-color: #f0ad4e; color: white; }
        .btn-clear:hover { background-color: #ec971f; }

        .btn-delete { background-color: #6c757d; color: white; }
        .btn-delete:hover { background-color: #5a6268; }

        /* --- MOBILE OPTIMIZATIONS --- */
        @media (max-width: 700px) {
            .container { margin: 10px; padding: 20px; width: auto; }
            .grid { grid-template-columns: 1fr; } /* Force single column */
            .global-nav { flex-direction: column; text-align: center; gap: 12px; }
            .global-nav span { font-size: 1em; }
            h1 { font-size: 1.5em; }
            .form-section { padding: 15px; }
        }

        /* --- PROFESSIONAL PRINT ENGINE --- */
        @media print {
            @page { size: portrait; margin: 0.4in; }
            .global-nav, .btn, .success, .error, .nav-link { display: none !important; }
            body { background: white !important; font-size: 11pt; color: black; margin: 0; }
            .container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
            h1 { font-size: 18pt !important; border-bottom: 3px solid black !important; }
            .print-header { display: block !important; text-align: right; font-weight: bold; border-bottom: 1px solid black; margin-bottom: 10px; font-size: 10pt; }

            .form-section { border: 2px solid black !important; background: transparent !important; margin-bottom: 15px !important; padding: 10px !important; page-break-inside: avoid; }
            .form-section h3 { color: black !important; border-bottom: 2px solid black !important; margin-bottom: 10px !important; font-size: 12pt !important; }

            .grid { display: grid !important; grid-template-columns: 1fr 1fr 1fr !important; gap: 10px !important; }
            .form-group { margin-bottom: 5px; }
            label { font-size: 9pt !important; text-transform: uppercase; color: #444 !important; }

            /* Turn inputs into clean text on paper */
            input[type="text"], select {
                border: none !important;
                border-bottom: 1px solid #000 !important;
                padding: 2px 0 !important;
                font-size: 11pt !important;
                font-weight: bold !important;
                background: transparent !important;
                -webkit-appearance: none;
                appearance: none;
            }
        }
    </style>
</head>
<body>
    <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="../index.php" style="color: #d9534f; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #d9534f; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>

    <div class="container">
        <h1>Shock Database Entry</h1>
        <?php echo $message; ?>

        <form method="POST" id="entryForm">
            <div class="form-section">
                <h3>General Spec</h3>
                <div class="grid">
                    <div class="form-group">
                        <label>OE P/N *</label>
                        <input type="text" name="oe_pn" id="oe_pn" required autocomplete="off" placeholder="Type OE, then Tab.">
                    </div>
                    <div class="form-group">
                        <label>Shock P/N</label>
                        <input type="text" name="shock_pn" id="shock_pn">
                    </div>
                    <div class="form-group">
                        <label>Product Use</label>
                        <input type="text" name="product_use" id="product_use" placeholder="e.g. ATV, Snow, SxS, Custom">
                    </div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="location" placeholder="e.g. Ski, Front, Rear"></div>
                    <div class="form-group"><label>Rebuild Kit</label><input type="text" name="rebuild_kit" id="rebuild_kit"></div>
                    <div class="form-group"><label>Service Kit</label><input type="text" name="service_kit" id="service_kit"></div>
                    <div class="form-group"><label>IFP Depth</label><input type="text" name="ifp_depth" id="ifp_depth"></div>
                    <div class="form-group"><label>Nitrogen PSI</label><input type="text" name="nitrogen_psi" id="nitrogen_psi"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Primary Hardware</h3>
                <div class="grid">
                    <div class="form-group"><label>Shaft</label><input type="text" name="shaft" id="shaft"></div>
                    <div class="form-group"><label>Seal Head - Bearing Assembly</label><input type="text" name="seal_head" id="seal_head"></div>
                    <div class="form-group"><label>BO Bumper</label><input type="text" name="bo_bumper" id="bo_bumper"></div>
                    <div class="form-group"><label>Body</label><input type="text" name="body" id="body"></div>
                    <div class="form-group"><label>Inner Body</label><input type="text" name="inner_body" id="inner_body"></div>
                    <div class="form-group"><label>Body Cap</label><input type="text" name="body_cap" id="body_cap"></div>
                    <div class="form-group"><label>Bearing Cap</label><input type="text" name="bearing_cap" id="bearing_cap"></div>
                    <div class="form-group"><label>Metering Rod</label><input type="text" name="metering_rod" id="metering_rod"></div>
                    <div class="form-group"><label>Adj Rebound</label><input type="text" name="adj_rebound" id="adj_rebound"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Reservoir Assembly</h3>
                <div class="grid">
                    <div class="form-group"><label>Reservoir</label><input type="text" name="reservoir" id="reservoir"></div>
                    <div class="form-group"><label>Res End Cap</label><input type="text" name="res_end_cap" id="res_end_cap"></div>
                    <div class="form-group"><label>Hose</label><input type="text" name="hose" id="hose"></div>
                    <div class="form-group"><label>Res Clamp</label><input type="text" name="res_clamp" id="res_clamp"></div>
                    <div class="form-group"><label>Bypass Screws</label><input type="text" name="bypass_screws" id="bypass_screws"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Body End Mounting</h3>
                <div class="grid">
                    <div class="form-group"><label>Body Bearing</label><input type="text" name="body_bearing" id="body_bearing"></div>
                    <div class="form-group"><label>Body O-Ring</label><input type="text" name="body_oring" id="body_oring"></div>
                    <div class="form-group"><label>Body Reducer</label><input type="text" name="body_reducer" id="body_reducer"></div>
                    <div class="form-group"><label>Body Spacer</label><input type="text" name="body_spacer" id="body_spacer"></div>
                    <div class="form-group"><label>Body Inner Sleeve</label><input type="text" name="body_inner_sleeve" id="body_inner_sleeve"></div>
                    <div class="form-group"><label>Body Outer Sleeve</label><input type="text" name="body_outer_sleeve" id="body_outer_sleeve"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Shaft / Eyelet End Mounting</h3>
                <div class="grid">
                    <div class="form-group"><label>Shaft Eyelet</label><input type="text" name="shaft_eyelet" id="shaft_eyelet"></div>
                    <div class="form-group"><label>Shaft Bearing</label><input type="text" name="shaft_bearing" id="shaft_bearing"></div>
                    <div class="form-group"><label>Shaft O-Ring</label><input type="text" name="shaft_oring" id="shaft_oring"></div>
                    <div class="form-group"><label>Shaft Reducer</label><input type="text" name="shaft_reducer" id="shaft_reducer"></div>
                    <div class="form-group"><label>Shaft Spacer</label><input type="text" name="shaft_spacer" id="shaft_spacer"></div>
                    <div class="form-group"><label>Shaft Inner Sleeve</label><input type="text" name="shaft_inner_sleeve" id="shaft_inner_sleeve"></div>
                    <div class="form-group"><label>Shaft Outer Sleeve</label><input type="text" name="shaft_outer_sleeve" id="shaft_outer_sleeve"></div>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" name="form_action" value="save" class="btn" style="flex: 2;">Save / Overwrite Shock Entry</button>
                <button type="button" class="btn btn-clear" style="flex: 1;" onclick="document.getElementById('entryForm').reset();">Clear Form</button>
                <button type="submit" name="form_action" value="delete" class="btn btn-delete" style="flex: 1;" onclick="return confirm('WARNING: Are you sure you want to completely DELETE this shock from the database?');">Delete Shock</button>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // --- 1. AUTO-FILL API LOGIC ---
                const oeInput = document.getElementById('oe_pn');

                if (oeInput) {
                    oeInput.addEventListener('blur', function() {
                        const oeValue = this.value.trim();
                        if (oeValue === '') return;

                        fetch('api_check_duplicate.php?oe=' + encodeURIComponent(oeValue))
                            .then(response => response.json())
                            .then(result => {
                                if (result.exists || result.success) {
                                    if (confirm('OE ' + oeValue + ' already exists in the database! Would you like to load its data to update it?')) {
                                        const shockData = result.data || result;
                                        for (const [key, value] of Object.entries(shockData)) {
                                            const inputField = document.querySelector(`[name="${key}"]`);
                                            if (inputField && key !== 'oe_pn') {
                                                inputField.value = value || '';
                                            }
                                        }
                                    }
                                }
                            })
                            .catch(error => console.error('Error fetching OE data:', error));
                    });
                }

                // --- 2. SUBMISSION SAFETY NET LOGIC ---
                const form = document.getElementById('entryForm');

                if (form) {
                    form.addEventListener('submit', function(event) {

                        // MILESTONE 4: If the delete button was clicked, ignore the save validation!
                        if (event.submitter && event.submitter.value === 'delete') {
                            return;
                        }

                        const inputs = form.querySelectorAll('input[type="text"]:not([name="oe_pn"]), select');
                        let hasData = false;

                        inputs.forEach(function(input) {
                            if (input.value.trim() !== '') {
                                hasData = true;
                            }
                        });

                        if (!hasData) {
                            const firstProceed = confirm("Are you sure? You have only entered an OE number. This will result in a mostly-blank entry!");
                            if (!firstProceed) {
                                event.preventDefault();
                            } else {
                                const secondProceed = confirm("FINAL WARNING: Are you absolutely certain you want to save/overwrite this as a blank shock?");
                                if (!secondProceed) {
                                    event.preventDefault();
                                }
                            }
                        } else {
                            const proceed = confirm("Are you sure you want to save this shock to the database?");
                            if (!proceed) {
                                event.preventDefault();
                            }
                        }
                    });
                }
            });
        </script>
    </div>
</body>
</html>