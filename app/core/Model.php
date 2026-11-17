<?php
// app/core/Model.php - Tenant-Scoped Base Model

namespace App\Core;

abstract class Model {
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $tenantScoped = true;

    public function getTable(): string {
        return $this->table;
    }

    public function getPrimaryKey(): string {
        return $this->primaryKey;
    }

    public function isTenantScoped(): bool {
        return $this->tenantScoped;
    }

    /**
     * Get all records for current tenant
     */
    public function all(string $orderBy = 'id DESC'): array {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $sql .= " WHERE `concern_id` = ?";
            $params[] = $cid;
        }

        $sql .= " ORDER BY {$orderBy}";
        return DB::fetchAll($sql, $params);
    }

    /**
     * Find single record by primary key, strictly scoped to current tenant
     */
    public function find($id): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $params = [$id];

        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $sql .= " AND `concern_id` = ?";
            $params[] = $cid;
        }

        $sql .= " LIMIT 1";
        return DB::fetch($sql, $params);
    }

    /**
     * Query with custom conditions, automatically injecting tenant isolation
     */
    public function where(string $conditions, array $params = [], string $orderBy = ''): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE ";
        
        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $sql .= "`concern_id` = ? AND ({$conditions})";
            array_unshift($params, $cid);
        } else {
            $sql .= "{$conditions}";
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return DB::fetchAll($sql, $params);
    }

    /**
     * Find first record matching condition
     */
    public function findWhere(string $conditions, array $params = []): ?array {
        $res = $this->where($conditions, $params, '');
        return !empty($res) ? $res[0] : null;
    }

    /**
     * Count records with optional condition
     */
    public function count(string $conditions = '', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        
        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            if (!empty($conditions)) {
                $sql .= " WHERE `concern_id` = ? AND ({$conditions})";
                array_unshift($params, $cid);
            } else {
                $sql .= " WHERE `concern_id` = ?";
                $params = [$cid];
            }
        } elseif (!empty($conditions)) {
            $sql .= " WHERE {$conditions}";
        }

        return (int)DB::fetchValue($sql, $params);
    }

    /**
     * Insert new record. Injects concern_id automatically from session if tenant-scoped.
     */
    public function insert(array $data): int {
        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $data['concern_id'] = $cid;
        }

        $fields = array_keys($data);
        $escapedFields = array_map(fn($f) => "`{$f}`", $fields);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO `{$this->table}` (" . implode(', ', $escapedFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        DB::query($sql, array_values($data));
        
        $insertId = (int)DB::lastInsertId();

        // Audit log
        \App\Services\AuditLog::log('CREATE', $this->table, $insertId, null, $data);

        return $insertId;
    }

    /**
     * Update record by ID, strictly enforcing tenant boundary
     */
    public function update($id, array $data): bool {
        // Prevent altering concern_id
        unset($data['concern_id']);

        // Fetch old state for audit
        $oldRecord = $this->find($id);
        if (!$oldRecord) {
            return false;
        }

        $fields = [];
        $values = [];
        foreach ($data as $key => $val) {
            $fields[] = "`{$key}` = ?";
            $values[] = $val;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE `{$this->primaryKey}` = ?";
        $values[] = $id;

        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $sql .= " AND `concern_id` = ?";
            $values[] = $cid;
        }

        $stmt = DB::query($sql, $values);

        // Audit log
        \App\Services\AuditLog::log('UPDATE', $this->table, $id, $oldRecord, $data);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete record by ID, strictly enforcing tenant boundary
     */
    public function delete($id): bool {
        $oldRecord = $this->find($id);
        if (!$oldRecord) {
            return false;
        }

        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $params = [$id];

        if ($this->tenantScoped && ($cid = Auth::concernId()) !== null) {
            $sql .= " AND `concern_id` = ?";
            $params[] = $cid;
        }

        $stmt = DB::query($sql, $params);

        // Audit log
        \App\Services\AuditLog::log('DELETE', $this->table, $id, $oldRecord, null);

        return $stmt->rowCount() > 0;
    }
}
