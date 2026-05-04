<?php

// system_files/api_accessory.php
header('Content-Type: application/json');

$dbFile = __DIR__ . '/carver_database.sqlite';
require_once __DIR__ . '/audit_logger.php';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // GET REQUEST: Check if the part number exists
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = $_GET['type'] ?? '';
        $pn = $_GET['pn'] ?? '';
        $table = $type . 's'; // e.g., 'decal' becomes 'decals'

        if (in_array($table, ['decals', 'tools', 'upgrades']) && $pn) {
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE part_number = ?");
            $stmt->execute([$pn]);
            echo json_encode(['exists' => (bool)$stmt->fetchColumn()]);
        } else {
            echo json_encode(['error' => 'Invalid parameters']);
        }
        exit;
    }

    // POST REQUEST: Create a new accessory on the fly
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['type'] ?? '';
        $pn = trim((string)($data['pn'] ?? ''));
        $desc = trim((string)($data['desc'] ?? ''));
        $table = $type . 's';

        if (in_array($table, ['decals', 'tools', 'upgrades']) && $pn) {
            if ($type === 'tool') {
                $tool_type = trim((string)($data['tool_type'] ?? 'Standard'));
                $stmt = $pdo->prepare("INSERT INTO tools (part_number, description, tool_type) VALUES (?, ?, ?)");
                $stmt->execute([$pn, $desc, $tool_type]);
                logAudit('tools', $pn, 'CREATE', null, ['part_number' => $pn, 'description' => $desc, 'tool_type' => $tool_type], $pdo);
            } else {
                $stmt = $pdo->prepare("INSERT INTO $table (part_number, description) VALUES (?, ?)");
                $stmt->execute([$pn, $desc]);
                logAudit($table, $pn, 'CREATE', null, ['part_number' => $pn, 'description' => $desc], $pdo);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid parameters']);
        }
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
