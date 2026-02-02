<?php
// run_cleanup.php - SAFER VERSION (Handles Quoted Text & #N/A)
$file = __DIR__ . '/system_files/Carver_Shocks_Database.csv';
$temp = __DIR__ . '/system_files/temp_clean.csv';

echo "<h3>Scrubbing Database...</h3>";

$input = fopen($file, 'r');
$output = fopen($temp, 'w');
$count = 0;

if ($input && $output) {
    while (($data = fgetcsv($input)) !== FALSE) {
        // CLEANUP LOGIC
        $cleanRow = array_map(function($val) {
            $v = trim($val ?? '');
            // Kill Excel Artifacts
            if ($v === 'NaN') return '';
            if ($v === '0') return '';
            if ($v === 'NULL') return '';
            if ($v === '#N/A') return ''; 
            return $v;
        }, $data);

        fputcsv($output, $cleanRow);
        $count++;
    }
    fclose($input);
    fclose($output);

    // Replace old file with clean file
    if (rename($temp, $file)) {
        echo "<h2>DONE! Cleaned $count rows.</h2>";
        echo "<p>You can now delete this script.</p>";
    } else {
        echo "ERROR: Could not save clean file. Check permissions.";
    }
} else {
    echo "ERROR: Could not open database.";
}
?>