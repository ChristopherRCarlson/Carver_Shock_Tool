<?php

// system_files/audit_logger.php

function logAudit(string $tableName, int|string $recordId, string $action, ?array $oldData = null, ?array $newData = null, ?PDO $pdo = null): void
{
    $dbFile = __DIR__ . '/carver_database.sqlite';

    try {
        if ($pdo === null) {
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $logId = uniqid('log_');

        // Force PHP to use Central Time for the timestamp
        date_default_timezone_set('America/Chicago');
        $timestamp = date('Y-m-d H:i:s');

        $changedBy = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        // Symmetrical ID Capture: If this is an INSERT/CREATE action and the auto-increment ID is missing from newData, fetch it dynamically
        if (($action === 'CREATE' || $action === 'INSERT') && is_array($newData) && !isset($newData['id'])) {
            $lastId = $pdo->lastInsertId();
            if ($lastId) {
                // Prepend 'id' so it perfectly mirrors the visual structure of old_data during DELETE operations
                $newData = ['id' => $lastId] + $newData;
            }
        }

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
        // Silently handle audit log failure to avoid disrupting main workflow
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
