<?php

class AdminReviewModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getReviews(
        string $keyword = '',
        int $rating = 0,
        int $limit = 10,
        int $offset = 0
    ): array {
        [$whereSql, $params] = $this->buildFilters($keyword, $rating);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT c.*, u.username, u.full_name, p.name AS product_name
             FROM comment c
             JOIN `user` u ON u.id = c.user_id
             JOIN product p ON p.id = c.product_id
             {$whereSql}
             ORDER BY c.date DESC, c.id DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countReviews(string $keyword = '', int $rating = 0): int
    {
        [$whereSql, $params] = $this->buildFilters($keyword, $rating);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total
             FROM comment c
             JOIN `user` u ON u.id = c.user_id
             JOIN product p ON p.id = c.product_id
             {$whereSql}",
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT c.*, u.username, p.name AS product_name
             FROM comment c
             JOIN `user` u ON u.id = c.user_id
             JOIN product p ON p.id = c.product_id
             WHERE c.id = ?
             LIMIT 1",
            [$id]
        );
    }

    public function reply(int $id, string $message): bool
    {
        return $this->db->execute(
            'UPDATE comment
             SET admin_reply = ?, reply_date = NOW()
             WHERE id = ?',
            [$message, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM comment WHERE id = ?', [$id]) > 0;
    }

    private function buildFilters(string $keyword, int $rating): array
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $term = '%' . $keyword . '%';
            $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR p.name LIKE ? OR c.messages LIKE ?)';
            array_push($params, $term, $term, $term, $term);
        }
        if ($rating >= 1 && $rating <= 5) {
            $where[] = 'c.rate = ?';
            $params[] = $rating;
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', $where) : '',
            $params,
        ];
    }
}
