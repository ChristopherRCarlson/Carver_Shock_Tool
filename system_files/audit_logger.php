<?php

// Define the path to our new audit log "table"
define('AUDIT_LOG_FILE', __DIR__ . '/audit_logs.csv');

/**
 * Initializes the audit log CSV if it doesn't exist and writes the header row.
 * This acts as our "Database Schema" for the audit trails.
 */
function initializeAuditLog() {
    if (!file_exists(AUDIT_LOG_FILE)) {
        $handle = fopen(AUDIT_LOG_FILE, 'w');
        if ($handle) {
            // The Audit Log Schema (Columns)
            fputcsv($handle, [
                'log_id',      // Unique ID for the log entry
                'table_name',  // Which CSV was modified (e.g., Carver_Shocks_Database)
                'record_id',   // The OE P/N that was changed
                'action',      // CREATE, UPDATE, or DELETE
                'old_data',    // JSON string of the previous state
                'new_data',    // JSON string of the new state
                'changed_by',  // User IP (since there's no login system yet)
                'timestamp'    // When it happened
            ]);
            fclose($handle);
        }
    }
}

/**
 * Helper function to write a new entry to the audit log.
 * We will use this extensively in Issue 2!
 */
function log_audit_action($tableName, $recordId, $action, $oldData = [], $newData = []) {
    initializeAuditLog(); // Ensure the file exists before writing

    $handle = fopen(AUDIT_LOG_FILE, 'a');
    if ($handle) {
        $logId = uniqid('log_');
        $changedBy = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $timestamp = date('Y-m-d H:i:s');

        // Convert data arrays to JSON strings so they fit in a single CSV cell
        $oldDataJson = !empty($oldData) ? json_encode($oldData) : '';
        $newDataJson = !empty($newData) ? json_encode($newData) : '';

        fputcsv($handle, [
            $logId,
            $tableName,
            $recordId,
            $action,
            $oldDataJson,
            $newDataJson,
            $changedBy,
            $timestamp
        ]);

        fclose($handle);
    }
}

// Automatically check/create the schema when this file is included
initializeAuditLog();
?>