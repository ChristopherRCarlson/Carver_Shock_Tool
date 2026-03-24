<?php

// system_files/audit_logger.php

// THE FIX: Added $pdo = null to the end of the function arguments
function logAudit($tableName, $recordId, $action, $oldData = null, $newData = null, $pdo = null): void
{
    $dbFile = __DIR__ . '/carver_database.sqlite';

    try {
        // THE FIX: If a connection was passed in, use it. Otherwise, make a new one.
        if ($pdo === null) {
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $logId = uniqid('log_');

        // Force PHP to use Central Time for the timestamp
        date_default_timezone_set('America/Chicago');
        $timestamp = date('Y-m-d H:i:s');

        $changedBy = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        // Extract ONLY the values, throwing away all the column names/keys
        $oldDataValues = $oldData ? array_values($oldData) : null;
        $newDataValues = $newData ? array_values($newData) : null;

        // Convert the value-only arrays to JSON strings
        $oldDataStr = $oldDataValues ? json_encode($oldDataValues) : null;
        $newDataStr = $newDataValues ? json_encode($newDataValues) : null;

        $stmt = $pdo->prepare("INSERT INTO audit_logs (
            log_id, table_name, record_id, action, old_data, new_data, changed_by, timestamp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $logId, $tableName, $recordId, $action, $oldDataStr, $newDataStr, $changedBy, $timestamp
        ]);
    } catch (PDOException $e) {
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
