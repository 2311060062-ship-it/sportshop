<?php

class AdminRevenueModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function summary(string $from, string $to): array
    {
        $row = $this->db->fetchOne(
            "SELECT
                (SELECT COUNT(*) FROM `user` WHERE roles = 'USER') AS users,
                (SELECT COUNT(*) FROM product WHERE status_product = 'Active') AS products,
                COUNT(o.id) AS orders,
                COALESCE(SUM(o.total), 0) AS revenue,
                COALESCE(AVG(o.total), 0) AS average_order
             FROM orders o
             JOIN payments pay ON pay.order_id = o.id
             WHERE pay.status = 'Paid'
               AND o.status_order <> 'Da_Huy'
               AND o.date >= ?
               AND o.date < DATE_ADD(?, INTERVAL 1 DAY)",
            [$from, $to]
        );

        return $row ?: [
            'users' => 0,
            'products' => 0,
            'orders' => 0,
            'revenue' => 0,
            'average_order' => 0,
        ];
    }

    public function dailyRevenue(string $from, string $to): array
    {
        return $this->db->fetchAll(
            "SELECT DATE(o.date) AS period,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(o.total), 0) AS revenue
             FROM orders o
             JOIN payments pay ON pay.order_id = o.id
             WHERE pay.status = 'Paid'
               AND o.status_order <> 'Da_Huy'
               AND o.date >= ?
               AND o.date < DATE_ADD(?, INTERVAL 1 DAY)
             GROUP BY DATE(o.date)
             ORDER BY period",
            [$from, $to]
        );
    }

    public function monthlyRevenue(int $year): array
    {
        return $this->db->fetchAll(
            "SELECT MONTH(o.date) AS period,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(o.total), 0) AS revenue
             FROM orders o
             JOIN payments pay ON pay.order_id = o.id
             WHERE pay.status = 'Paid'
               AND o.status_order <> 'Da_Huy'
               AND YEAR(o.date) = ?
             GROUP BY MONTH(o.date)
             ORDER BY period",
            [$year]
        );
    }

    public function yearlyRevenue(int $fromYear, int $toYear): array
    {
        return $this->db->fetchAll(
            "SELECT YEAR(o.date) AS period,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(o.total), 0) AS revenue
             FROM orders o
             JOIN payments pay ON pay.order_id = o.id
             WHERE pay.status = 'Paid'
               AND o.status_order <> 'Da_Huy'
               AND YEAR(o.date) BETWEEN ? AND ?
             GROUP BY YEAR(o.date)
             ORDER BY period",
            [$fromYear, $toYear]
        );
    }

    public function getDetailedOrdersForExport(string $from, string $to): array
    {
        return $this->db->fetchAll(
            "SELECT o.id,
                    o.date,
                    o.total,
                    o.status_order,
                    o.address AS order_address,
                    o.phone AS order_phone,
                    u.full_name AS customer_name,
                    u.username AS customer_username,
                    u.phone AS customer_phone,
                    pay.provider AS payment_provider,
                    pay.status AS payment_status,
                    pay.updated_at AS paid_at
             FROM orders o
             JOIN payments pay ON pay.order_id = o.id
             LEFT JOIN `user` u ON u.id = o.user_id
             WHERE pay.status = 'Paid'
               AND o.status_order <> 'Da_Huy'
               AND o.date >= ?
               AND o.date < DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY o.date DESC",
            [$from, $to]
        );
    }
}

