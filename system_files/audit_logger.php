<?php

// system_files/audit_logger.php

// THE FIX: Added $pdo = null to the end of the function arguments
function logAudit(string $tableName, int|string $recordId, string $action, ?array $oldData = null, ?array $newData = null, ?PDO $pdo = null): void
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

        // Convert the arrays directly to JSON strings to preserve the column names
        $oldDataStr = $oldData ? json_encode($oldData) : null;
        $newDataStr = $newData ? json_encode($newData) : null;

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
