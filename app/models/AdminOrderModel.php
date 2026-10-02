<?php

class AdminOrderModel
{
    private Database $db;

    private const TRANSITIONS = [
        'Cho_Thanh_Toan' => ['Da_Huy'],
        'Dang_Xu_Ly' => ['Dang_Giao', 'Da_Huy'],
        'Yeu_Cau_Huy' => ['Dang_Xu_Ly', 'Da_Huy'],
        'Dang_Giao' => ['Da_Giao', 'Da_Huy'],
        'Da_Giao' => [],
        'Da_Nhan_Hang' => [],
        'Yeu_Cau_Tra_Hang' => ['Da_Giao', 'Da_Tra_Hang'],
        'Da_Tra_Hang' => [],
        'Da_Huy' => [],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getOrders(
        string $keyword = '',
        string $status = 'all',
        int $limit = 10,
        int $offset = 0
    ): array {
        [$whereSql, $params] = $this->buildFilters($keyword, $status);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT o.*, u.username, u.full_name,
                    pay.provider AS payment_provider,
                    pay.status AS payment_status,
                    pay.trans_id
             FROM orders o
             JOIN `user` u ON u.id = o.user_id
             LEFT JOIN payments pay ON pay.order_id = o.id
             {$whereSql}
             ORDER BY o.date DESC, o.id DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countOrders(string $keyword = '', string $status = 'all'): int
    {
        [$whereSql, $params] = $this->buildFilters($keyword, $status);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total
             FROM orders o
             JOIN `user` u ON u.id = o.user_id
             {$whereSql}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT o.*, u.username, u.full_name, u.email,
                    pay.provider AS payment_provider,
                    pay.status AS payment_status,
                    pay.trans_id, pay.paid_at, pay.result_code
             FROM orders o
             JOIN `user` u ON u.id = o.user_id
             LEFT JOIN payments pay ON pay.order_id = o.id
             WHERE o.id = ?
             LIMIT 1",
            [$id]
        );
    }

    public function getItems(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT od.*, p.name, p.color,
                    (SELECT img.image_link
                     FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM order_detail od
             JOIN product p ON p.id = od.product_id
             WHERE od.order_id = ?
             ORDER BY od.id",
            [$orderId]
        );
    }

    public function allowedNextStatuses(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Cập nhật theo luồng hợp lệ; hủy đơn sẽ hoàn kho đúng một lần.
     */
    public function changeStatus(int $orderId, string $newStatus): bool
    {
        $this->db->beginTransaction();
        try {
            $order = $this->db->fetchOne(
                'SELECT id, status_order FROM orders WHERE id = ? FOR UPDATE',
                [$orderId]
            );
            if (!$order) {
                $this->db->rollBack();
                return false;
            }

            $current = (string) $order['status_order'];
            if (!in_array($newStatus, $this->allowedNextStatuses($current), true)) {
                $this->db->rollBack();
                return false;
            }

            if (in_array($newStatus, ['Da_Huy', 'Da_Tra_Hang'], true)) {
                $decreaseSales = $current === 'Cho_Thanh_Toan' ? 0 : 1;
                $this->db->execute(
                    "UPDATE product p
                     JOIN order_detail od ON od.product_id = p.id
                     SET p.quantity = p.quantity + od.quantity,
                         p.quantity_sell = GREATEST(
                             0,
                             p.quantity_sell - (od.quantity * ?)
                         ),
                         p.update_date = NOW(6)
                     WHERE od.order_id = ?",
                    [$decreaseSales, $orderId]
                );
                $this->db->execute(
                    "UPDATE payments
                     SET status = 'Failed', result_code = -2, updated_at = NOW(6)
                     WHERE order_id = ? AND status = 'Pending'",
                    [$orderId]
                );
            }

            $updated = $this->db->execute(
                'UPDATE orders SET status_order = ? WHERE id = ? AND status_order = ?',
                [$newStatus, $orderId, $current]
            );
            if ($updated !== 1) {
                throw new RuntimeException('Trạng thái đơn hàng đã thay đổi.');
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function statuses(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    private function buildFilters(string $keyword, string $status): array
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            if (ctype_digit($keyword)) {
                $where[] = '(o.id = ? OR u.username LIKE ? OR o.phone LIKE ?)';
                $term = '%' . $keyword . '%';
                array_push($params, (int) $keyword, $term, $term);
            } else {
                $term = '%' . $keyword . '%';
                $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR o.phone LIKE ? OR o.address LIKE ?)';
                array_push($params, $term, $term, $term, $term);
            }
        }
        if (in_array($status, $this->statuses(), true)) {
            $where[] = 'o.status_order = ?';
            $params[] = $status;
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', $where) : '',
            $params,
        ];
    }
}
