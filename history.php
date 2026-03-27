<?php
// history.php - V3.0 (SQLite Integrated with Carver Styling)

$oe_pn = $_GET['oe'] ?? '';
$dbFile = __DIR__ . '/system_files/carver_database.sqlite';
$logs = [];
$searched = false;

// The 38 column headers so we can make the changes human-readable
$columns = [
    "OE P/N", "Shock P/N", "Product Use", "Location", "Rebuild Kit", "Service Kit", "IFP Depth",
    "Nitrogen PSI", "Shaft", "Seal Head", "B/O Bumper", "Body", "Inner Body", "Body Cap",
    "Bearing Cap", "Reservoir", "Res End Cap", "Metering Rod", "Rebound Adjuster",
    "Compression Adjuster", "Compression Adjuster Knob", "Compression Adjuster Screw",
    "Hose", "Res Clamp", "Bypass Screws", "Body Bearing", "Body O-Ring", "Body Reducer", "Body Spacer",
    "Body Inner Sleeve", "Body Outer Sleeve", "Shaft Eyelet", "Shaft Bearing", "Shaft O-Ring",
    "Shaft Reducer", "Shaft Spacer", "Shaft Inner Sleeve", "Shaft Outer Sleeve", "Brand"
];

if (!empty($oe_pn)) {
    $searched = true;
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch logs for this specific OE Part Number, newest first
        $stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE record_id = :oe_pn ORDER BY id DESC");
        $stmt->execute([':oe_pn' => $oe_pn]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Decode the JSON strings back into PHP arrays so the HTML loop can read them
            $row['old_data'] = $row['old_data'] ? json_decode($row['old_data'], true) : [];
            $row['new_data'] = $row['new_data'] ? json_decode($row['new_data'], true) : [];
            $logs[] = $row;
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
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

        /* Fixed: Button color darkened for AA contrast */
        button { padding: 10px 20px; font-size: 16px; background-color: #c62828; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #a52727; }

        /* Fixed: Border left color darkened for AA contrast */
        .result-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fff; border-left: 5px solid #c62828; }

        .result-header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .oe-title { font-size: 1.4em; font-weight: bold; color: #333; }

        .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .spec-item { background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 0.9em; }
        .spec-label { display: block; font-weight: bold; color: #555; font-size: 0.8em; margin-bottom: 2px; }
        .spec-value { display: block; color: #333; font-weight: 500; }

        /* NEW: Part Link Styling */
        /* Fixed: Link color darkened for AA contrast */
        .part-link { color: #c62828; text-decoration: none; border-bottom: 1px dotted #c62828; }
        .part-link:hover { background-color: #c62828; color: white; text-decoration: none; border-bottom: none; }
        .part-link.dead-link{ color: #000!important; text-decoration: none!important; border-bottom: none!important; cursor: default!important; pointer-events: none; }

        .empty { color: #ccc; font-style: italic; }

        /* Mounting Box Styling (Sleeve Split) */
        .mounting-box { grid-column: 1 / -1; background: #fdfdfe; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; margin-top: 5px; }
        .section-title { display: block; font-size: 0.85em; font-weight: bold; color: #666; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #eee; padding-bottom: 3px; }
        .sleeve-pair { display: flex; gap: 10px; flex-wrap: wrap; }
        .sleeve-pair > div { background: #fff; padding: 5px; border: 1px solid #eee; border-radius: 3px; }

        /* --- MOBILE OPTIMIZATION (Tablets & Phones) --- */
        @media (max-width: 850px) {
            .results-grid { display: grid; grid-template-columns: 1fr; gap: 15px; }
            .flex-container, .mounting-section { display: flex; flex-direction: column; gap: 10px; }
            .sleeve-pair { flex-direction: column; }
            .result-card { margin: 5px; width: auto; }
            input[type="text"] { width: 100%; margin-bottom: 10px; }
        }

        .maintenance-section { display: flex; gap: 15px; margin-bottom: 15px; background: #fdfdfd; padding: 15px; justify-content: space-between; }
        .kit-card { flex: 1; max-width: 48%; background: white; border: 1px solid #ddd; border-radius: 6px; padding: 10px; text-align: center; width: 150px; cursor: default; transition: transform 0.2s; }
        .kit-card:hover { transform: scale(1.02); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .kit-thumb { width: 100%; height: 150px; object-fit: contain; margin-bottom: 8px; background: #eee; cursor: zoom-in; }
        .kit-type-label { display: block; font-size: 0.8em; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }

        #kit-modal { display: none; position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        #kit-modal img { max-width: 90%; max-height: 90%; background: white; padding: 10px; }

        /* --- PROFESSIONAL SHOP SHEET PRINT ENGINE --- */
        @media print {
            @page { size: portrait; margin: 0.3in; }
            .global-nav, .search-box, #kit-modal, .nav-link, form, [style*="position: absolute; right: 0; top: 0;"] { display: none !important; }
            body { background: white !important; font-family: "Helvetica", "Arial", sans-serif; font-size: 11pt; color: black; margin: 0; padding: 0; }
            .container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
            .result-card { border: 2px solid black !important; padding: 15px !important; margin: 0 !important; page-break-inside: avoid; border-radius: 0; }
            .oe-title { font-size: 22pt !important; border-bottom: 2px solid black; margin-bottom: 5px; }
            .result-header div[style*="italic"] { font-size: 12pt !important; font-weight: bold !important; margin-bottom: 15px !important; color: black !important; }
            .maintenance-section { display: flex !important; justify-content: flex-start !important; gap: 20px !important; margin-bottom: 15px !important; padding: 10px !important; border: 1px solid #ccc; }
            .kit-card { border: 1px solid black !important; padding: 5px !important; width: 180px !important; height: auto !important; }
            .kit-thumb { max-height: 90px !important; width: auto !important; display: block; margin: 0 auto 5px auto; }
            .spec-grid { display: grid !important; grid-template-columns: 1fr 1fr 1fr !important; gap: 8px !important; }
            .spec-item { background: transparent !important; border: 1px solid #ddd !important; padding: 5px !important; }
            .spec-label { font-size: 8pt !important; text-transform: uppercase; color: #444 !important; }
            .spec-value { font-size: 11pt !important; font-weight: bold !important; }
            .mounting-box { grid-column: 1 / -1 !important; border: 1px solid black !important; margin-top: 10px !important; padding: 10px !important; }
            .section-title { font-size: 10pt !important; background: #eee !important; padding: 3px 5px !important; border-bottom: 1px solid black !important; }
            .sleeve-pair { display: flex !important; flex-direction: row !important; gap: 10px !important; margin-top: 5px; }
            .sleeve-pair > div { border: 1px dashed #666 !important; flex: 1; }
            .part-link { text-decoration: none !important; color: black !important; border-bottom: none !important; }
            .empty { color: #aaa !important; }
        }

        /* --- AUDIT LOG SPECIFIC ADDITIONS --- */
        /* Fixed: Border left color darkened */
        .log-entry { border: 1px solid #ddd; margin-bottom: 20px; border-radius: 4px; overflow: hidden; background: #fff; border-left: 5px solid #c62828; }
        .log-header { background: #f8f9fa; padding: 12px 15px; display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #eee; }
        .log-body { padding: 20px; }

        /* Fixed: Audit action colors updated for AA contrast */
        .CREATE { color: #2e7d32; }
        .UPDATE { color: #0056b3; }
        .DELETE { color: #c62828; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #eee; padding: 12px; text-align: left; font-size: 0.9em; }
        th { background-color: #fcfcfc; color: #555; font-size: 0.8em; text-transform: uppercase; }

        /* Fixed: Cell text colors updated for AA contrast */
        .old-val { color: #c62828; text-decoration: line-through; background: #fff5f5; padding: 2px 5px; }
        .new-val { color: #2e7d32; font-weight: bold; background: #f5fff5; padding: 2px 5px; }

        .empty-state { text-align: center; color: #777; padding: 40px 0; font-style: italic; }
    </style>
</head>
<body>

    <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="index.php" style="color: #ff8a80; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #ff8a80; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>

    <div class="container">
        <div class="search-box">
            <form method="GET" action="history.php" style="display: flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap;">
                <input type="text" name="oe" placeholder="Enter OE P/N (e.g., 6964)" value="<?php echo htmlspecialchars($oe_pn); ?>" required autofocus>
                <button type="submit">SEARCH AUDIT LOGS</button>
            </form>
        </div>

        <?php if (isset($error)) : ?>
            <div class="empty-state" style="color: #c62828;"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!$searched) : ?>
            <div class="empty-state">Enter a shock OE P/N above to view its version history.</div>
        <?php elseif (empty($logs)) : ?>
            <div class="empty-state">No version history found for OE: <strong><?php echo htmlspecialchars($oe_pn); ?></strong></div>
        <?php else : ?>
            <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">Audit History for OE: <?php echo htmlspecialchars($oe_pn); ?></h3>

            <?php foreach ($logs as $log) : ?>
                <div class="log-entry">
                    <div class="log-header">
                        <span class="<?php echo htmlspecialchars($log['action']); ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                        <span><?php echo htmlspecialchars(date('m/d/Y g:i A', strtotime($log['timestamp']))); ?> (IP: <?php echo htmlspecialchars($log['changed_by']); ?>)</span>
                    </div>
                    <div class="log-body">
                            <?php if ($log['action'] === 'CREATE') : ?>
                                <p style="margin: 0 0 15px 0; color: #2e7d32; font-weight: bold;">Initial record created with the following data:</p>
                                <table>
                                    <tr>
                                        <th>Field</th>
                                        <th>Value Entered</th>
                                    </tr>
                                    <?php
                                    foreach ($columns as $i => $colName) {
                                        $val = $log['new_data'][$i] ?? '';
                                        if ($val !== '') { // Only show fields that actually have data
                                            echo "<tr>";
                                            echo "<td><strong>{$colName}</strong></td>";
                                            echo "<td class='new-val'>" . htmlspecialchars($val) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </table>

                            <?php elseif ($log['action'] === 'DELETE') : ?>
                                <p style="margin: 0 0 15px 0; color: #c62828; font-weight: bold;">Record was completely deleted. Final state before deletion:</p>
                                <table>
                                    <tr>
                                        <th>Field</th>
                                        <th>Deleted Value</th>
                                    </tr>
                                    <?php
                                    foreach ($columns as $i => $colName) {
                                        $val = $log['old_data'][$i] ?? '';
                                        if ($val !== '') {
                                            echo "<tr>";
                                            echo "<td><strong>{$colName}</strong></td>";
                                            echo "<td class='old-val'>" . htmlspecialchars($val) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </table>

                            <?php elseif ($log['action'] === 'UPDATE') : ?>
                                <p style="margin: 0 0 15px 0; color: #0056b3; font-weight: bold;">The following fields were modified:</p>
                                <table>
                                    <tr>
                                        <th>Field</th>
                                        <th>Old Value</th>
                                        <th>New Value</th>
                                    </tr>
                                    <?php
                                    // Compare old and new arrays
                                    $changesFound = false;
                                    foreach ($columns as $i => $colName) {
                                        $oldVal = $log['old_data'][$i] ?? '';
                                        $newVal = $log['new_data'][$i] ?? '';

                                        if ($oldVal !== $newVal) {
                                            $changesFound = true;
                                            echo "<tr>";
                                            echo "<td><strong>{$colName}</strong></td>";
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>