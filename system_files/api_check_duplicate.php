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

    // 1. Tell Psalm: "Only do this if the file wasn't completely empty"
    if ($headers !== false) {
        // Clean headers to ensure perfect matching
        $headers = array_map('trim', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            // 2. Tell Psalm: "Only check this if row[0] actually exists (prevents blank line errors)"
            if (isset($row[0]) && strcasecmp(trim($row[0]), trim($searchOE)) === 0) {
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
    } // End of the if ($headers !== false) check

    fclose($handle);
}

if ($found) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false]);
}
