<?php

class CommentModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getUserReviewsForProducts(int $userId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            static fn (int $id): bool => $id > 0
        )));
        if (!$productIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM comment
             WHERE user_id = ? AND product_id IN ({$placeholders})
             ORDER BY date DESC, id DESC",
            array_merge([$userId], $productIds)
        );

        $reviews = [];
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            if (!isset($reviews[$productId])) {
                $reviews[$productId] = $row;
            }
        }
        return $reviews;
    }

    public function hasReviewed(int $userId, int $productId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT id FROM comment WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );
    }

    public function canReviewProduct(int $userId, int $orderId, int $productId): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT od.id
             FROM order_detail od
             JOIN orders o ON o.id = od.order_id
             WHERE od.order_id = ?
               AND od.product_id = ?
               AND o.user_id = ?
               AND o.status_order = 'Da_Nhan_Hang'
             LIMIT 1",
            [$orderId, $productId, $userId]
        );
    }

    public function create(int $userId, int $productId, int $rate, string $message): bool
    {
        return $this->db->execute(
            'INSERT INTO comment (date, messages, product_id, user_id, rate)
             VALUES (NOW(6), ?, ?, ?, ?)',
            [$message, $productId, $userId, $rate]
        ) > 0;
    }
}
