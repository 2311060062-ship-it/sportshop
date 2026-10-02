<?php

class AdminDashboardModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function summary(): array
    {
        return $this->db->fetchOne(
            "SELECT
                (SELECT COUNT(*) FROM `user` WHERE roles = 'USER') AS users,
                (SELECT COUNT(*) FROM product WHERE status_product = 'Active') AS products,
                (SELECT COUNT(*) FROM orders) AS orders,
                (SELECT COALESCE(SUM(o.total), 0)
                 FROM orders o
                 JOIN payments pay ON pay.order_id = o.id
                 WHERE pay.status = 'Paid' AND o.status_order <> 'Da_Huy') AS revenue,
                (SELECT COUNT(*) FROM orders WHERE status_order IN ('Cho_Thanh_Toan','Dang_Xu_Ly','Yeu_Cau_Huy')) AS pending_orders,
                (SELECT COUNT(*) FROM product WHERE status_product = 'Active' AND quantity <= 5) AS low_stock"
        ) ?: [];
    }

    public function recentOrders(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT o.id, o.date, o.total, o.status_order, u.username, u.full_name
             FROM orders o
             JOIN `user` u ON u.id = o.user_id
             ORDER BY o.date DESC, o.id DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function lowStockProducts(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.name, p.quantity, p.status_product,
                    (SELECT image_link FROM image_product
                     WHERE product_id = p.id ORDER BY id DESC LIMIT 1) AS thumbnail
             FROM product p
             WHERE p.status_product = 'Active' AND p.quantity <= 5
             ORDER BY p.quantity ASC, p.id DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function recentReviews(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.rate, c.messages, c.date,
                    u.username, u.full_name, p.name AS product_name
             FROM comment c
             JOIN `user` u ON u.id = c.user_id
             JOIN product p ON p.id = c.product_id
             ORDER BY c.date DESC, c.id DESC
             LIMIT ?",
            [$limit]
        );
    }
}
