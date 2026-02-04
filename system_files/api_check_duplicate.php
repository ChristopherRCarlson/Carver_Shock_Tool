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
    // Skip header row if necessary (optional but recommended)
    fgetcsv($handle); 

    // Column 0 is now OE P/N (the primary Search Key in Schema v2.0)
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (isset($data[0]) && strcasecmp(trim($data[0]), trim($query)) == 0) {
            $exists = true;
            break;
        }
    }
    fclose($handle);
}

echo json_encode(['exists' => $exists]);
?>