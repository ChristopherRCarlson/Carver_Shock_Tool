<?php

declare(strict_types=1);

// cross_reference.php - Schema v4.1 (Reverse Lookup for Hardware & Accessories)

$dbFile = 'system_files/carver_database.sqlite';

/**
 * Sanitizes and formats text output.
 * * @param string|null $data
 * @return string
 */
function display_clean(?string $data): string
{
    $val = trim($data ?? '');
    if (preg_match('/^(n\/a|na|n\.a\.|none|null|#n\/a|nan|#ref!|#value!|unknown|-)$/i', $val)) {
        return '<span class="empty">-</span>';
    }
    return htmlspecialchars($val);
}

$search = $_GET['part_number'] ?? '';
$results = [];
$dbError = null;

if ($search !== '') {
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // EXISTS subqueries prevent duplicate rows when mapping tables are joined
        $sql = "SELECT s.id, s.oe_pn, s.shock_pn, s.product_use, s.location, s.Brand
                FROM shocks s
                WHERE :part_num IN (
                    s.rebuild_kit, s.service_kit, s.shaft, s.seal_head, s.bo_bumper_1,
                    s.body, s.inner_body, s.body_cap, s.bearing_cap, s.reservoir,
                    s.res_end_cap, s.metering_rod, s.rebound_adjuster, s.hose,
                    s.res_clamp, s.bypass_screws, s.body_bearing, s.body_oring,
                    s.body_reducer, s.body_spacer, s.body_inner_sleeve, s.body_outer_sleeve,
                    s.comp_adjuster, s.comp_adjuster_knob, s.comp_adjuster_screw,
                    s.bo_bumper_2, s.bo_bumper_3
                )
                OR EXISTS (
                    SELECT 1 FROM shock_tools_mapping stm
                    JOIN tools t ON stm.tool_id = t.id
                    WHERE stm.shock_id = s.id AND t.part_number = :part_num
                )
                OR EXISTS (
                    SELECT 1 FROM shock_decals_mapping sdm
                    JOIN decals d ON sdm.decal_id = d.id
                    WHERE sdm.shock_id = s.id AND d.part_number = :part_num
                )
                OR EXISTS (
                    SELECT 1 FROM shock_upgrades_mapping sumap
                    JOIN upgrades u ON sumap.upgrade_id = u.id
                    WHERE sumap.shock_id = s.id AND u.part_number = :part_num
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':part_num' => trim($search)]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $dbError = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Carver Part Cross-Reference Tool</title>
        <style>
            body { font-family: sans-serif; margin: 0; background-color: #f9f9f9; }
            .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

            .search-box { text-align: center; margin-bottom: 20px; padding: 20px; background: #eee; border-radius: 8px; }
            input[type="text"] { padding: 10px; width: 60%; font-size: 16px; border: 1px solid #ccc; border-radius: 4px; }

            button { padding: 10px 20px; font-size: 16px; background-color: #c62828; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background-color: #a52727; }

            .result-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fff; border-left: 5px solid #c62828; }
            .result-header { margin-bottom: 5px; }
            .oe-title { font-size: 1.4em; font-weight: bold; color: #333; }
            .empty { color: #595959; font-style: italic; }

            .shock-details { margin-top:5px; font-style: italic; color: #000; font-size: 0.9em; }

            .view-button {
                display: inline-block;
                margin-top: 15px;
                padding: 8px 15px;
                background-color: #333;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                font-size: 0.9em;
                font-weight: bold;
                transition: background-color 0.2s;
            }
            .view-button:hover { background-color: #555; }

            @media (max-width: 850px) {
                .result-card { margin: 5px; width: auto; }
                input[type="text"] { width: 100%; margin-bottom: 10px; }
            }

            @media print {
                @page { size: portrait; margin: 0.3in; }
                /* Hide navigation, error link, AND the new view button on print */
                .global-nav, .nav-link, [style*="position: absolute; right: 0; top: 0;"], .view-button { display: none !important; }

                body { background: white !important; font-family: "Helvetica", "Arial", sans-serif; font-size: 11pt; color: black; margin: 0; padding: 0; }
                .container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }

                .search-box { background: transparent !important; padding: 10px 0 !important; margin-bottom: 15px !important; border-bottom: 2px solid black; border-radius: 0; text-align: left; }
                .search-box input[type="text"] { border: none !important; font-weight: bold; font-size: 14pt; width: auto; padding: 0; }
                .search-box button { display: none !important; }

                .result-card { border: 2px solid black !important; padding: 15px !important; margin-bottom: 10px !important; border-radius: 0; page-break-inside: avoid !important; break-inside: avoid !important; }
                .oe-title { font-size: 16pt !important; border-bottom: 1px solid #ccc; margin-bottom: 5px; padding-bottom: 5px; }
            }
        </style>
    </head>
    <body>
        <div class="global-nav" style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            <span style="font-weight: bold; letter-spacing: 1px; font-size: 1.1em;">CARVER DIGITAL INFRASTRUCTURE</span>
            <a href="index.php" style="color: #ff8a80; text-decoration: none; font-weight: bold; font-size: 0.9em; border: 1px solid #ff8a80; padding: 5px 10px; border-radius: 4px;">&larr; BACK TO DASHBOARD</a>
        </div>

        <div class="container">
            <h1 style="text-align: center; color: #c62828; margin-bottom: 20px;">Part Cross-Reference</h1>

            <div class="search-box">
                <form method="GET">
                    <input type="text" name="part_number" placeholder="Enter Exact Part Number (e.g. SRV-KIT-99)" value="<?= htmlspecialchars($search) ?>" autofocus required>
                    <button type="submit">FIND SHOCKS</button>
                </form>
            </div>

            <?php if ($dbError) : ?>
                <div style="color: #c62828; background: #fee; padding: 10px; border-radius: 4px; margin-bottom: 10px;"><?= $dbError ?></div>
            <?php endif; ?>

            <?php if ($search !== '' && empty($results)) : ?>
                <div style="text-align:center; padding: 20px; color: #444;">
                    No shocks found utilizing part "<strong><?= htmlspecialchars($search) ?></strong>".
                </div>
            <?php endif; ?>

            <?php if (!empty($results)) : ?>
                <div style="margin-bottom: 15px; font-weight: bold; color: #333;">
                    Found <?= count($results) ?> shock(s) using "<?= htmlspecialchars($search) ?>":
                </div>

                <?php foreach ($results as $row) : ?>
                    <div class="result-card">
                        <div class="result-header">
                            <div style="position: relative;">
                                <a href="mailto:christopherrcarlson101@gmail.com?subject=Data Error Report: Cross Ref - <?= urlencode($search) ?> in SKU <?= urlencode($row['oe_pn']) ?>"
                                    style="position: absolute; right: 0; top: 0; font-size: 0.8em; color: #595959; text-decoration: underline;">
                                    Report Data Error
                                </a>
                                <div class="oe-title">OE: <?= display_clean($row['oe_pn']) ?></div>
                                <div style="color: #444; font-size: 1.1em;">Shock P/N: <strong><?= display_clean($row['shock_pn']) ?></strong></div>

                                <div class="shock-details">
                                    <?php
                                    $desc_parts = [];
                                    if (trim($row['Brand'] ?? '')) {
                                        $desc_parts[] = "Brand: " . trim($row['Brand']);
                                    }
                                    if (trim($row['product_use'] ?? '')) {
                                        $desc_parts[] = "Use: " . trim($row['product_use']);
                                    }
                                    if (trim($row['location'] ?? '')) {
                                        $desc_parts[] = "Position: " . trim($row['location']);
                                    }
                                    echo empty($desc_parts) ? '<span class="empty">-</span>' : htmlspecialchars(implode(" | ", $desc_parts));
                                    ?>
                                </div>
                                <div>
                                    <a href="lookup.php?search=<?= urlencode($row['oe_pn']) ?>" class="view-button" target="_blank" rel="noopener noreferrer">
                                        VIEW SHOCK DETAILS &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </body>
</html>