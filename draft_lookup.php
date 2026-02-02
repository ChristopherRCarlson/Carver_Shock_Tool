<?php
// draft_lookup.php - V7.0 (Boss Schema)
class ShockLookup {
    public function getResults() {
        $query = isset($_GET['sku']) ? trim($_GET['sku']) : '';
        if (empty($query)) return [];

        $results = [];
        $csvFile = __DIR__ . '/system_files/Carver_Shocks_Database.csv';

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            // 31 Columns - Matches Boss's Excel
            $headers = [
                'shock_kit','shock_pn','oe_pn','description','service_kit',
                'bearing_cap','body_cap','body','inner_body','metering_rod',
                'eyelet','reservoir','shaft','bearing_assembly','base_valve',
                'live_iqs_tractive','boc','valve_code','res_end_cap','bypass_screws',
                'hose','res_clamp','adjuster_rebound','body_bearing','body_oring',
                'body_reducer','body_sleeve','eyelet_bearing','eyelet_oring',
                'eyelet_reducer','eyelet_sleeve'
            ];
            
            $headerCount = count($headers);
            
            while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                // Pad/Trim Logic
                $rowLength = count($data);
                if ($rowLength < $headerCount) $data = array_pad($data, $headerCount, "");
                else if ($rowLength > $headerCount) $data = array_slice($data, 0, $headerCount);
                
                $row = array_combine($headers, $data);
                if ($row['shock_pn'] == 'shock_pn') continue;

                if (stripos($row['shock_pn'], $query) !== false || 
                    stripos($row['oe_pn'], $query) !== false || 
                    stripos($row['description'], $query) !== false) {
                    $results[] = $row;
                }
            }
            fclose($handle);
        }
        return $results;
    }
}
$app = new ShockLookup();
$results = $app->getResults();

function render($sku, $name) {
    if(empty($sku)) return '';
    return '<a href="https://www.carverperformance.com/cart.php?target=search&substring='.urlencode($sku).'" target="_blank" class="btn-link">'.$name.': <strong>'.$sku.'</strong></a> ';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shock Lookup</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        input[type="text"] { flex-grow: 1; padding: 12px; font-size: 16px; border: 2px solid #ddd; border-radius: 4px; }
        button { padding: 12px 25px; background: #d9534f; color: white; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th { background: #333; color: white; padding: 10px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-link { display: inline-block; padding: 2px 6px; background: #eee; color: #333; text-decoration: none; border-radius: 3px; font-size: 0.85em; border: 1px solid #ccc; margin-right: 4px; margin-bottom: 4px; white-space: nowrap; }
        .btn-link:hover { background: #ddd; }
        .section-title { font-weight: bold; color: #777; font-size: 0.75em; text-transform: uppercase; margin-bottom: 4px; display: block; border-bottom: 1px dashed #ccc; }
    </style>
</head>
<body>
<div class="container">
    <h2>Carver Performance Master Lookup</h2>
    <form method="GET" class="search-box">
        <input type="text" name="sku" placeholder="Enter Shock P/N, OE P/N, or Description..." value="<?php echo isset($_GET['sku']) ? htmlspecialchars($_GET['sku']) : ''; ?>">
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($results)): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Shock Identity</th>
                    <th style="width: 25%;">Core Parts</th>
                    <th style="width: 25%;">Valving & Res</th>
                    <th style="width: 25%;">Mounting</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['shock_pn']); ?></strong><br>
                            <small style="color:#666;"><?php echo htmlspecialchars($row['description']); ?></small><br>
                            <small>OE: <?php echo htmlspecialchars($row['oe_pn']); ?></small><br>
                            <small>Kit: <?php echo htmlspecialchars($row['shock_kit']); ?></small><br><br>
                            <?php if($row['service_kit']) echo '<a href="#" class="btn-link" style="background:#5cb85c; color:white;">Service: '.$row['service_kit'].'</a>'; ?>
                        </td>
                        <td>
                            <span class="section-title">Main Assembly</span>
                            <?php 
                            echo render($row['shaft'], 'Shaft');
                            echo render($row['body'], 'Body');
                            echo render($row['body_cap'], 'BodyCap');
                            echo render($row['bearing_cap'], 'BrngCap');
                            echo render($row['eyelet'], 'Eyelet');
                            echo render($row['reservoir'], 'Res');
                            echo render($row['res_end_cap'], 'ResCap');
                            ?>
                        </td>
                        <td>
                            <span class="section-title">Internals</span>
                            <?php 
                            echo render($row['metering_rod'], 'MetRod');
                            echo render($row['base_valve'], 'BaseVlv');
                            echo render($row['bearing_assembly'], 'BrngAssy');
                            echo render($row['boc'], 'BOC');
                            ?>
                            <div style="margin-top:8px;">
                                <span class="section-title">Hardware</span>
                                <?php 
                                echo render($row['hose'], 'Hose');
                                echo render($row['res_clamp'], 'Clamp');
                                echo render($row['bypass_screws'], 'BypScr');
                                echo render($row['adjuster_rebound'], 'Adj');
                                ?>
                            </div>
                        </td>
                        <td>
                            <span class="section-title">Body End</span>
                            <?php 
                            echo render($row['body_bearing'], 'Brng');
                            echo render($row['body_oring'], 'Org');
                            echo render($row['body_reducer'], 'Red');
                            echo render($row['body_sleeve'], 'Slv');
                            ?>
                            <div style="margin-top:8px;">
                                <span class="section-title">Eyelet End</span>
                                <?php 
                                echo render($row['eyelet_bearing'], 'Brng');
                                echo render($row['eyelet_oring'], 'Org');
                                echo render($row['eyelet_reducer'], 'Red');
                                echo render($row['eyelet_sleeve'], 'Slv');
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>