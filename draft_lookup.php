<?php
// draft_lookup.php - Schema v2.0 (OE-First & Split Sleeves)

$csvFile = 'system_files/Carver_Shocks_Database.csv';

// Expanded Sanitizer for Display
function display_clean($data) {
    $val = trim($data ?? '');
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val)) {
        return '<span class="empty">-</span>';
    }
    return htmlspecialchars($val);
}

$results = [];
$search = $_GET['search'] ?? '';

if ($search && ($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle); // Skip header row
    while (($data = fgetcsv($handle)) !== FALSE) {
        // Search OE (Index 0), Shock PN (Index 1), or Description (Index 3)
        if (stripos($data[0], $search) !== FALSE || 
            stripos($data[1], $search) !== FALSE || 
            stripos($data[3], $search) !== FALSE) {
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
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .search-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input[type="text"] { width: 70%; padding: 10px; font-size: 1.1em; }
        
        .result-card { background: white; margin-bottom: 20px; border-radius: 8px; overflow: hidden; border-left: 5px solid #007bff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .result-header { background: #e9ecef; padding: 15px; border-bottom: 1px solid #ddd; }
        .oe-title { font-size: 1.4em; color: #d9534f; font-weight: bold; }
        .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 15px; }
        
        .spec-item { border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .spec-label { font-size: 0.8em; color: #666; display: block; text-transform: uppercase; }
        .spec-value { font-weight: bold; color: #333; }
        .empty { color: #ccc; font-weight: normal; }
        
        .mounting-box { background: #fcf8e3; padding: 10px; grid-column: span 2; border-radius: 4px; border: 1px solid #faebcc; }
        .sleeve-pair { display: flex; gap: 10px; margin-top: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1 style="text-align: center; color: #d9534f; margin-bottom: 20px;">Carver Shock Lookup v2.0</h1>

<div class="search-box">
    <h2>Carver Digital Infrastructure: Shock Lookup</h2>
    <form method="GET">
        <input type="text" name="search" placeholder="Enter OE#, Shock#, or Vehicle Details..." value="<?= htmlspecialchars($search) ?>" autofocus>
        <button type="submit" style="padding: 10px 20px;">SEARCH</button>
    </form>
</div>

<?php if ($search): ?>
    <p>Showing <?= count($results) ?> results for "<?= htmlspecialchars($search) ?>"</p>
    
    <?php foreach ($results as $row): ?>
        <div class="result-card">
            <div class="result-header">
                <div class="oe-title">OE: <?= display_clean($row[0]) ?></div>
                
                <div style="color: #666666; font-size: 1.1em;">
                    Shock P/N: <strong><?= display_clean($row[1]) ?></strong>
                </div>
                
                <div style="margin-top:5px; font-style: italic;"><?= display_clean($row[3]) ?></div>
            </div>

            <div class="spec-grid">
                <div class="spec-item" style="background: #eef9f0; padding: 5px; border-radius: 4px; border: 1px solid #c3e6cb;">
                    <span class="spec-label" style="color: #1e7e34;">Shock Kit</span>
                    <span class="spec-value"><?= display_clean($row[2]) ?></span>
                </div>

                <div class="spec-item" style="background: #fff3cd; padding: 5px; border-radius: 4px; border: 1px solid #ffeeba;">
                    <span class="spec-label" style="color: #856404;">Service Kit (Seals)</span>
                    <span class="spec-value"><?= display_clean($row[4]) ?></span>
                </div>

                <div class="spec-item"><span class="spec-label">Shaft</span><span class="spec-value"><?= display_clean($row[12]) ?></span></div>
                <div class="spec-item"><span class="spec-label">Body</span><span class="spec-value"><?= display_clean($row[7]) ?></span></div>
                <div class="spec-item"><span class="spec-label">Valve</span><span class="spec-value"><?= display_clean($row[17]) ?></span></div>

                <div class="mounting-box">
                    <span class="section-title">Body End Mounting</span>
                    <div class="sleeve-pair">
                        <div style="flex:1"><span class="spec-label">Bearing</span><?= display_clean($row[23]) ?></div>
                        <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_clean($row[26]) ?></div>
                        <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_clean($row[27]) ?></div>
                    </div>
                </div>

                <div class="mounting-box">
                    <span class="section-title">Eyelet End Mounting</span>
                    <div class="sleeve-pair">
                        <div style="flex:1"><span class="spec-label">Bearing</span><?= display_clean($row[28]) ?></div>
                        <div style="flex:1"><span class="spec-label">Inner Sleeve</span><?= display_clean($row[31]) ?></div>
                        <div style="flex:1"><span class="spec-label">Outer Sleeve</span><?= display_clean($row[32]) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>