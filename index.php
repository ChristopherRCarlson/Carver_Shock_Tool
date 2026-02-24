<?php
date_default_timezone_set('America/Chicago');
$dbFile = 'system_files/Carver_Shocks_Database.csv';
$lastUpdated = "Unknown";
if (file_exists($dbFile)) {
    $lastUpdated = date("F j, Y, g:i a", filemtime($dbFile));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carver Digital Infrastructure | Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .dashboard-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 600px; width: 90%; border-top: 8px solid #d9534f; }
        h1 { color: #333; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        p { color: #666; margin-bottom: 30px; }

        .status-bar { background: #e9ecef; color: #495057; padding: 10px; border-radius: 6px; font-size: 0.85em; margin-bottom: 30px; border-left: 4px solid #17a2b8; text-align: left;}
        .status-bar strong { color: #333; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .nav-card { background: #fff; border: 2px solid #eee; padding: 25px; border-radius: 8px; text-decoration: none; color: #333; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; }
        .nav-card:hover { border-color: #d9534f; transform: translateY(-5px); box-shadow: 0 5px 15px rgba(217, 83, 79, 0.2); }
        .nav-card i { font-size: 2.5em; margin-bottom: 15px; color: #d9534f; }
        .nav-card span { font-weight: bold; font-size: 1.1em; }
        .nav-card small { color: #888; margin-top: 5px; font-weight: normal; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #aaa; border-top: 1px solid #eee; padding-top: 15px; }

        /* --- MOBILE OPTIMIZATION --- */
        @media (max-width: 850px) {
            body {
                align-items: flex-start; /* Pushes the dashboard to the top instead of the center */
                padding-top: 30px; /* Gives it just a little breathing room from the phone's top notch */
                height: auto;
                min-height: 100vh;
            }
            
            .grid {
                grid-template-columns: 1fr; /* Forces exactly one column */
                gap: 15px;
            }
            
            .dashboard-container {
                padding: 20px; 
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Carver Shock Tool</h1>
        <p>Internal Digital Infrastructure for Technicians</p>

        <div class="status-bar">
            <strong>Database Status:</strong> Online &bull;
            <strong>Last Updated:</strong> <?php echo $lastUpdated; ?>
        </div>
        
        <div class="grid">
            <a href="draft_lookup.php" class="nav-card">
                <span>🔍 SHOCK LOOKUP</span>
                <small>Search specs & BOMs</small>
            </a>

            <a href="system_files/internal_entry.php" class="nav-card">
                <span>📥 DATA ENTRY</span>
                <small>Add new shocks to DB</small>
            </a>
        </div>

        <div class="footer">
            Server Active: <?php echo $_SERVER['SERVER_ADDR'] ?? 'Localhost'; ?>:<?php echo $_SERVER['SERVER_PORT']; ?><br>
            Authorized Personnel Only
        </div>
    </div>
</body>
</html>