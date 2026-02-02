<?php
// api_check_duplicate.php
// Returns JSON: {"exists": true} or {"exists": false}

header('Content-Type: application/json');
$query = $_GET['sku'] ?? '';

if (empty($query)) {
    echo json_encode(['exists' => false]);
    exit;
}

$csvFile = __DIR__ . '/Carver_Shocks_Database.csv';
$exists = false;

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Column 1 is usually Shock P/N (index 1 in 0-based array)
    // We check both col 0 (Kit) and col 1 (Shock PN) just in case
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (isset($data[1]) && strcasecmp(trim($data[1]), trim($query)) == 0) {
            $exists = true;
            break;
        }
    }
    fclose($handle);
}

echo json_encode(['exists' => $exists]);
?>