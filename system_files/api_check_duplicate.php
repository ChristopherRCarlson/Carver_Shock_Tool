<?php

// system_files/api_check_duplicate.php
header('Content-Type: application/json');

$oe_pn = $_GET['oe_pn'] ?? '';
$shock_pn = $_GET['shock_pn'] ?? '';

// If both are empty, it's not a duplicate
if (empty($oe_pn) && empty($shock_pn)) {
    echo json_encode(['isDuplicate' => false]);
    exit;
}

$dbFile = __DIR__ . '/carver_database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conditions = [];
    $params = [];

    if (!empty($oe_pn)) {
        $conditions[] = "oe_pn = :oe_pn";
        $params[':oe_pn'] = $oe_pn;
    }

    if (!empty($shock_pn)) {
        $conditions[] = "shock_pn = :shock_pn";
        $params[':shock_pn'] = $shock_pn;
    }

    // Explicitly select the exact columns we need
    $query = "SELECT oe_pn, shock_pn, Brand, product_use, location, rebuild_kit, service_kit, ifp_depth, nitrogen_psi, shaft, seal_head, bo_bumper, body, inner_body, body_cap, bearing_cap, reservoir, res_end_cap, metering_rod, rebound_adjuster, comp_adjuster, comp_adjuster_knob, comp_adjuster_screw, hose, res_clamp, bypass_screws, body_bearing, body_oring, body_reducer, body_spacer, body_inner_sleeve, body_outer_sleeve, shaft_eyelet, shaft_bearing, shaft_oring, shaft_reducer, shaft_spacer, shaft_inner_sleeve, shaft_outer_sleeve FROM shocks WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
    $stmt = $pdo->prepare($query);

    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Return a clean, simple response tailored specifically for the JS loop
        echo json_encode([
            'isDuplicate' => true,
            'matched_oe' => $result['oe_pn'],
            'matched_shock' => $result['shock_pn'],
            'assoc_data' => $result
        ]);
    } else {
        echo json_encode(['isDuplicate' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
