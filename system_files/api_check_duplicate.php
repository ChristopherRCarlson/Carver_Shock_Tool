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

    // Grab ALL columns from the matching shock
    $query = "SELECT * FROM shocks WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
    $stmt = $pdo->prepare($query);

    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $shock_id = (int) $result['id'];
        // FIX: Grab both the part number AND the specific note field, then fetch as an associative array
        $result['decals'] = $pdo->query("SELECT d.part_number, m.placement_note as note FROM shock_decals_mapping m JOIN decals d ON m.decal_id = d.id WHERE m.shock_id = $shock_id")->fetchAll(PDO::FETCH_ASSOC);
        $result['tools'] = $pdo->query("SELECT t.part_number, m.usage_note as note FROM shock_tools_mapping m JOIN tools t ON m.tool_id = t.id WHERE m.shock_id = $shock_id")->fetchAll(PDO::FETCH_ASSOC);
        $result['upgrades'] = $pdo->query("SELECT u.part_number, m.note FROM shock_upgrades_mapping m JOIN upgrades u ON m.upgrade_id = u.id WHERE m.shock_id = $shock_id")->fetchAll(PDO::FETCH_ASSOC);

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
