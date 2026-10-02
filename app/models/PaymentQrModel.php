<?php

class PaymentQrModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getActiveSettings(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM payment_qr_settings WHERE is_active = 1 ORDER BY provider'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['provider']] = $row;
        }
        return $result;
    }

    public function getSetting(string $provider, bool $activeOnly = false): array|false
    {
        $sql = 'SELECT * FROM payment_qr_settings WHERE provider = ?';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return $this->db->fetchOne($sql . ' LIMIT 1', [$provider]);
    }

    public function saveSetting(string $provider, array $data, int $adminId): void
    {
        $this->db->execute(
            "INSERT INTO payment_qr_settings
                (provider, display_name, account_name, account_number, qr_image,
                 transfer_prefix, instructions, is_active, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                account_name = VALUES(account_name),
                account_number = VALUES(account_number),
                qr_image = VALUES(qr_image),
                transfer_prefix = VALUES(transfer_prefix),
                instructions = VALUES(instructions),
                is_active = VALUES(is_active),
                updated_by = VALUES(updated_by)",
            [
                $provider,
                $data['display_name'],
                $data['account_name'],
                $data['account_number'],
                $data['qr_image'],
                $data['transfer_prefix'],
                $data['instructions'],
                $data['is_active'],
                $adminId,
            ]
        );
    }

    public function findForCustomer(int $orderId, int $userId): array|false
    {
        return $this->db->fetchOne(
            "SELECT pay.*, o.total, o.status_order, o.user_id,
                    s.display_name, s.account_name, s.account_number,
                    s.qr_image, s.instructions
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             JOIN payment_qr_settings s ON s.provider = pay.provider
             WHERE pay.order_id = ? AND o.user_id = ?
               AND pay.provider = 'VNPayQR'
             LIMIT 1",
            [$orderId, $userId]
        );
    }

    public function submitReceipt(int $paymentId, int $userId, string $filename): bool
    {
        return $this->db->execute(
            "UPDATE payments pay
             JOIN orders o ON o.id = pay.order_id
             SET pay.receipt_image = ?,
                 pay.customer_submitted_at = NOW(6),
                 pay.updated_at = NOW(6)
             WHERE pay.id = ? AND o.user_id = ?
               AND pay.status = 'Pending'
               AND pay.provider = 'VNPayQR'",
            [$filename, $paymentId, $userId]
        ) === 1;
    }

    public function getTransactions(
        string $keyword,
        string $status,
        int $limit,
        int $offset
    ): array {
        [$where, $params] = $this->transactionFilters($keyword, $status);
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll(
            "SELECT pay.*, o.total, o.status_order, o.date,
                    u.username, u.full_name, u.phone
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             JOIN `user` u ON u.id = o.user_id
             {$where}
             ORDER BY pay.created_at DESC, pay.id DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countTransactions(string $keyword, string $status): int
    {
        [$where, $params] = $this->transactionFilters($keyword, $status);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             JOIN `user` u ON u.id = o.user_id
             {$where}",
            $params
        );
        return (int) ($row['total'] ?? 0);
    }

    public function findTransaction(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT pay.*, o.status_order
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             WHERE pay.id = ? AND pay.provider = 'VNPayQR'
             LIMIT 1",
            [$id]
        );
    }

    public function markReviewed(int $id, int $adminId): void
    {
        $this->db->execute(
            'UPDATE payments
             SET admin_reviewed_at = NOW(6), admin_reviewed_by = ?
             WHERE id = ?',
            [$adminId, $id]
        );
    }

    private function transactionFilters(string $keyword, string $status): array
    {
        $where = ["pay.provider IN ('VNPay','VNPayQR','VNPayStyleDemo')"];
        $params = [];
        if ($keyword !== '') {
            $term = '%' . $keyword . '%';
            $where[] = '(pay.provider_order_id LIKE ? OR u.username LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)';
            array_push($params, $term, $term, $term, $term);
        }
        if (in_array($status, ['Pending', 'Paid', 'Failed'], true)) {
            $where[] = 'pay.status = ?';
            $params[] = $status;
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
