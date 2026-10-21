<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class ActivityLog
{
    public function __construct(private mysqli $db) {}

    public function log(int $userId, string $action, string $module, int $recordId, string $description, ?array $oldData = null, ?array $newData = null): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $oldJson = $oldData ? json_encode($oldData) : null;
            $newJson = $newData ? json_encode($newData) : null;

            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, module, record_id, description, old_data, new_data, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ississss', $userId, $action, $module, $recordId, $description, $oldJson, $newJson, $ip);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            error_log('Activity log failed: ' . $e->getMessage());
        }
    }

    public function getAll(int $limit = 50, int $offset = 0, string $q = '', string $module = ''): array
    {
        try {
            // ONLY fetch logs that have NOT been reverted
            $sql = "SELECT a.*, u.full_name, u.username, u.role 
                    FROM activity_logs a
                    LEFT JOIN users u ON u.id = a.user_id
                    WHERE a.is_reverted = 0";
            
            $params = [];
            $types = '';

            if ($q !== '') {
                $sql .= " AND (a.description LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
                $like = "%$q%";
                $params[] = $like; $params[] = $like; $params[] = $like;
                $types .= 'sss';
            }

            if ($module !== '') {
                $sql .= " AND a.module = ?";
                $params[] = $module;
                $types .= 's';
            }

            $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit; $params[] = $offset;
            $types .= 'ii';

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findLog(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

        public function revertUpdate(int $logId, int $adminId): bool|string
    {
        $log = $this->findLog($logId);
        if (!$log || $log['action'] !== 'UPDATE' || empty($log['old_data'])) {
            return "Log is not an UPDATE or missing old_data.";
        }

        $oldData = json_decode($log['old_data'], true);
        if (!$oldData || !is_array($oldData)) {
            return "old_data is not valid JSON.";
        }

        $table = '';
        switch ($log['module']) {
            case 'Inmate':    $table = 'inmates'; break;
            case 'Visitor':   $table = 'visitors'; break;
            case 'Incident':  $table = 'incidents'; break;
            case 'Headcount': $table = 'headcounts'; break;
            default: return "Unknown module: " . $log['module'];
        }

        $setParts = [];
        $params = [];
        $types = '';

        foreach ($oldData as $col => $val) {
            $setParts[] = "`$col` = ?";
            $params[] = $val;
            $types .= is_int($val) ? 'i' : 's';
        }

        $params[] = $log['record_id'];
        $types .= 'i';

        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return "Prepare failed: " . $this->db->error;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            return "SQL Error: " . $e->getMessage() . " | Query: " . $sql;
        }

        // Mark as reverted
        $markStmt = $this->db->prepare("UPDATE activity_logs SET is_reverted = 1 WHERE id = ?");
        $markStmt->bind_param('i', $logId);
        $markStmt->execute();
        $markStmt->close();

        // Log the revert
        $this->log($adminId, 'REVERT', $log['module'], $log['record_id'], 'Reverted Log ID: ' . $logId, null, $oldData);

        return true;
    }
}