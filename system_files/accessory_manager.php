<?php
// accessory_manager.php - Carver Digital Infrastructure

$dbFile = __DIR__ . '/carver_database.sqlite';
$message = "";

require_once __DIR__ . '/audit_logger.php';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- HANDLE POST REQUESTS (ADD & DELETE) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';

        try {
            // --- ADD LOGIC ---
            if ($action === 'add_decal') {
                $pn = trim($_POST['part_number']);
                $desc = trim($_POST['description']);
                if ($pn) {
                    $stmt = $pdo->prepare("INSERT INTO decals (part_number, description) VALUES (?, ?)");
                    $stmt->execute([$pn, $desc]);
                    logAudit('decals', $pn, 'CREATE', null, ['part_number' => $pn, 'description' => $desc], $pdo);
                    $message = "<div class='success'>Success: Decal '$pn' added to catalog.</div>";
                }
            } elseif ($action === 'add_tool') {
                $pn = trim($_POST['part_number']);
                $desc = trim($_POST['description']);
                $type = trim($_POST['tool_type']);
                if ($pn) {
                    $stmt = $pdo->prepare("INSERT INTO tools (part_number, description, tool_type) VALUES (?, ?, ?)");
                    $stmt->execute([$pn, $desc, $type]);
                    logAudit('tools', $pn, 'CREATE', null, ['part_number' => $pn, 'description' => $desc, 'tool_type' => $type], $pdo);
                    $message = "<div class='success'>Success: Tool '$pn' added to catalog.</div>";
                }
            } elseif ($action === 'add_upgrade') {
                $pn = trim($_POST['part_number']);
                $desc = trim($_POST['description']);
                if ($pn) {
                    $stmt = $pdo->prepare("INSERT INTO upgrades (part_number, description) VALUES (?, ?)");
                    $stmt->execute([$pn, $desc]);
                    logAudit('upgrades', $pn, 'CREATE', null, ['part_number' => $pn, 'description' => $desc], $pdo);
                    $message = "<div class='success'>Success: Upgrade '$pn' added to catalog.</div>";
                }
                // --- DELETE LOGIC ---
            } elseif (in_array($action, ['delete_decal', 'delete_tool', 'delete_upgrade'])) {
                $id = (int)$_POST['delete_id'];
                $table = str_replace('delete_', '', $action) . 's'; // e.g., 'decals'

                // Fetch info for audit log before deleting
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $delStmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                    $delStmt->execute([$id]);
                    logAudit($table, $item['part_number'], 'DELETE', $item, null, $pdo);
                    $message = "<div class='success' style='background-color:#fff3cd; color:#856404; border-color:#ffeeba;'>Item '{$item['part_number']}' deleted from $table catalog. All linked shock mappings were removed.</div>";
                }
            }
        } catch (PDOException $e) {
            // Handle Unique Constraint Violations smoothly
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $message = "<div class='error'>Error: That Part Number already exists in the catalog!</div>";
            } else {
                $message = "<div class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }

    // --- FETCH CURRENT CATALOGS FOR DISPLAY ---
    $decals = $pdo->query("SELECT * FROM decals ORDER BY part_number ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tools = $pdo->query("SELECT * FROM tools ORDER BY part_number ASC")->fetchAll(PDO::FETCH_ASSOC);
    $upgrades = $pdo->query("SELECT * FROM upgrades ORDER BY part_number ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carver | Accessory Manager</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding-bottom: 20px; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        h1 { border-bottom: 2px solid #d9534f; padding-bottom: 10px; color: #333; margin-top: 0; }
        .success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }

        .manager-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .manager-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 75vh; }
        .manager-card h3 { margin-top: 0; color: #d9534f; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        .add-form { background: #fafafa; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; }
        .add-form input { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-add { background-color: #c62828; color: white; border: none; padding: 10px; cursor: pointer; border-radius: 4px; width: 100%; font-weight: bold; }
        .btn-add:hover { background-color: #a52727; }

        .list-container { flex: 1; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; background: #fff; }
        .list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; font-size: 0.9em; }
        .list-item:nth-child(even) { background-color: #fafafa; }
        .item-details { display: flex; flex-direction: column; gap: 3px; }
        .item-pn { font-weight: bold; color: #333; }
        .item-desc { color: #666; font-size: 0.85em; }

        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.8em; }
        .btn-delete:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
        <a href="../index.php" style="color: #ff8a80; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #ff8a80; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
    </div>

    <div class="container">
        <h1>Master Accessory Catalog Manager</h1>
        <?php echo $message; ?>

        <div class="manager-grid">

            <div class="manager-card">
                <h3>Decals</h3>
                <div class="add-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_decal">
                        <input type="text" name="part_number" placeholder="Decal Part Number *" required>
                        <input type="text" name="description" placeholder="Description (e.g., Fox Factory Universal)">
                        <button type="submit" class="btn-add">+ Add Decal to Catalog</button>
                    </form>
                </div>
                <div class="list-container">
                    <?php foreach ($decals as $item) : ?>
                        <div class="list-item">
                            <div class="item-details">
                                <span class="item-pn"><?= htmlspecialchars($item['part_number']) ?></span>
                                <span class="item-desc"><?= htmlspecialchars($item['description']) ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('WARNING: Deleting this Decal will also remove it from EVERY shock it is currently attached to. Proceed?');">
                                <input type="hidden" name="action" value="delete_decal">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="manager-card">
                <h3>Tools</h3>
                <div class="add-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_tool">
                        <input type="text" name="part_number" placeholder="Tool Part Number *" required>
                        <input type="text" name="description" placeholder="Description">
                        <input type="text" name="tool_type" placeholder="Tool Type (e.g., Nitrogen Needle)">
                        <button type="submit" class="btn-add">+ Add Tool to Catalog</button>
                    </form>
                </div>
                <div class="list-container">
                    <?php foreach ($tools as $item) : ?>
                        <div class="list-item">
                            <div class="item-details">
                                <span class="item-pn"><?= htmlspecialchars($item['part_number']) ?></span>
                                <span class="item-desc"><?= htmlspecialchars($item['description']) ?> [<?= htmlspecialchars($item['tool_type']) ?>]</span>
                            </div>
                            <form method="POST" onsubmit="return confirm('WARNING: Deleting this Tool will also remove it from EVERY shock it is currently attached to. Proceed?');">
                                <input type="hidden" name="action" value="delete_tool">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="manager-card">
                <h3>Upgrades</h3>
                <div class="add-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_upgrade">
                        <input type="text" name="part_number" placeholder="Upgrade Part Number *" required>
                        <input type="text" name="description" placeholder="Description">
                        <button type="submit" class="btn-add">+ Add Upgrade to Catalog</button>
                    </form>
                </div>
                <div class="list-container">
                    <?php foreach ($upgrades as $item) : ?>
                        <div class="list-item">
                            <div class="item-details">
                                <span class="item-pn"><?= htmlspecialchars($item['part_number']) ?></span>
                                <span class="item-desc"><?= htmlspecialchars($item['description']) ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('WARNING: Deleting this Upgrade will also remove it from EVERY shock it is currently attached to. Proceed?');">
                                <input type="hidden" name="action" value="delete_upgrade">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
