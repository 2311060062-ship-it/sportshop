<?php

class AdminUserModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getUsers(string $keyword, string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->filters($keyword, $status);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.gender,
                    u.avatar, u.status_user, u.create_date,
                    COUNT(o.id) AS order_count,
                    COALESCE(SUM(CASE WHEN o.status_order IN ('Da_Giao', 'Da_Nhan_Hang') THEN o.total ELSE 0 END), 0) AS total_spent
             FROM `user` u
             LEFT JOIN orders o ON o.user_id = u.id
             {$where}
             GROUP BY u.id
             ORDER BY u.create_date DESC, u.id DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countUsers(string $keyword, string $status): int
    {
        [$where, $params] = $this->filters($keyword, $status);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM `user` u {$where}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, username, full_name, status_user
             FROM `user`
             WHERE id = ? AND roles = 'USER'
             LIMIT 1",
            [$id]
        );
    }

    public function findByIdFull(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, username, full_name, email, phone, gender, avatar, roles, status_user, create_date, update_date
             FROM `user`
             WHERE id = ? AND roles = 'USER'
             LIMIT 1",
            [$id]
        );
    }

    public function updateUser(int $id, array $data): bool
    {
        $fields = [
            'full_name = ?',
            'email = ?',
            'phone = ?',
            'gender = ?',
            'status_user = ?',
            'update_date = NOW(6)',
        ];
        $params = [
            $data['full_name'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['gender'] ?? null,
            $data['status_user'] ?? 'Active',
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        $params[] = $id;
        $sql = "UPDATE `user` SET " . implode(', ', $fields) . " WHERE id = ? AND roles = 'USER'";

        return $this->db->execute($sql, $params) > 0;
    }

    public function deleteUser(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM `user` WHERE id = ? AND roles = 'USER'",
            [$id]
        ) > 0;
    }

    public function toggleStatus(int $id): bool
    {
        return $this->db->execute(
            "UPDATE `user`
             SET status_user = CASE WHEN status_user = 'Active' THEN 'Closed' ELSE 'Active' END,
                 update_date = NOW(6)
             WHERE id = ? AND roles = 'USER'",
            [$id]
        ) === 1;
    }

    private function filters(string $keyword, string $status): array
    {
        $where = ["u.roles = 'USER'"];
        $params = [];
        if ($keyword !== '') {
            $term = '%' . $keyword . '%';
            $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($params, $term, $term, $term, $term);
        }
        if (in_array($status, ['Active', 'Closed'], true)) {
            $where[] = 'u.status_user = ?';
            $params[] = $status;
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
