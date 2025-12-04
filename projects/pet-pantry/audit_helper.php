<?php
// audit_helper.php - Helper functions for audit trail logging

/**
 * Log an action to the audit trail
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User performing the action
 * @param string $userName User's name
 * @param string $action Action type (CREATE, UPDATE, DELETE, etc.)
 * @param string $tableName Table affected
 * @param int|null $recordId ID of affected record
 * @param array|null $oldValues Old values (for UPDATE/DELETE)
 * @param array|null $newValues New values (for CREATE/UPDATE)
 * @param string|null $description Optional description
 */
function logAudit($pdo, $userId, $userName, $action, $tableName, $recordId = null, $oldValues = null, $newValues = null, $description = null) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_trail 
            (user_id, user_name, action, table_name, record_id, old_values, new_values, description, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $userName,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $description,
            $ipAddress
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}
?>

