<?php

// api_check_duplicate.php

header('Content-Type: application/json');

$csvFile = __DIR__ . '/Carver_Shocks_Database.csv';

if (!file_exists($csvFile)) {
    echo json_encode(['error' => 'Database file not found.']);
    exit;
}

$searchOE = $_GET['oe'] ?? '';

if (empty($searchOE)) {
    echo json_encode(['error' => 'No OE number provided.']);
    exit;
}

$found = false;
$data = [];

if (($handle = fopen($csvFile, "r")) !== false) {
    $headers = fgetcsv($handle);

    // Clean headers to ensure perfect matching
    $headers = array_map('trim', $headers);

    while (($row = fgetcsv($handle)) !== false) {
        // Check if the first column (OE P/N) matches what was typed
        if (strcasecmp(trim($row[0]), trim($searchOE)) === 0) {
            $found = true;

            // Map the CSV headers directly to the row values
            foreach ($headers as $index => $colName) {
                if (!empty($colName) && isset($row[$index])) {
                    $data[$colName] = trim($row[$index]);
                }
            }
            break; // Stop searching once we find the match
        }
    }
    fclose($handle);
}

if ($found) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false]);
}
