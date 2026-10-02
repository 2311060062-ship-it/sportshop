<?php
/**
 * ============================================================
 *  SPORT SHOP – Order Model
 *  File: app/models/OrderModel.php
 *  Bảng: orders, order_detail
 * ============================================================
 */

class OrderModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy tất cả đơn hàng của user
     */
    public function getOrdersByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT o.*, pay.status AS payment_status
             FROM orders o
             LEFT JOIN payments pay ON pay.order_id = o.id
             WHERE o.user_id = ?
             ORDER BY o.date DESC",
            [$userId]
        );
    }

    /**
     * Lấy chi tiết 1 đơn hàng
     */
    public function findById(int $orderId): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? LIMIT 1",
            [$orderId]
        );
    }

    /**
     * Lấy chi tiết các sản phẩm trong đơn hàng
     */
    public function getOrderDetail(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT od.*, p.name, p.color,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id LIMIT 1) AS thumbnail
             FROM order_detail od
             JOIN product p ON od.product_id = p.id
             WHERE od.order_id = ?",
            [$orderId]
        );
    }

    /**
     * Tạo đơn hàng mới từ giỏ hàng
     * @param  array  $orderData  [user_id, address, phone, total, quantity_product]
     * @param  array  $cartItems  Danh sách sản phẩm trong giỏ
     * @return int    ID đơn hàng vừa tạo
     */
    public function createOrder(array $orderData, array $cartItems): int
    {
        $this->db->beginTransaction();

        try {
            // 1. Tạo đơn hàng
            $this->db->execute(
                "INSERT INTO orders
                    (user_id, address, phone, total, quantity_product, status_order, date,
                     ghn_total_fee, to_district_id, to_ward_code, shipping_status)
                 VALUES (?, ?, ?, ?, ?, 'Dang_Xu_Ly', NOW(6), ?, ?, ?, ?)",
                array_merge([
                    $orderData['user_id'],
                    $orderData['address'],
                    $orderData['phone'],
                    $orderData['total'],
                    $orderData['quantity_product'],
                ], $this->shippingValues($orderData))
            );
            $orderId = (int) $this->db->lastInsertId();
            $codSuffix = date('YmdHis') . strtoupper(bin2hex(random_bytes(5)));

            // 2. Thêm chi tiết từng sản phẩm
            foreach ($cartItems as $item) {
                $finalPrice = (int) round($item['price'] * (1 - $item['discount'] / 100));
                $itemTotal  = $finalPrice * $item['quantity'];
                $itemSize   = !empty($item['size']) ? trim((string) $item['size']) : '40';

                $this->db->execute(
                    "INSERT INTO order_detail (order_id, product_id, quantity, size, price, discount, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $itemSize,
                        $item['price'],
                        $item['discount'],
                        $itemTotal,
                    ]
                );

                // 3. Trừ số lượng tồn kho
                $affected = $this->db->execute(
                    "UPDATE product
                     SET quantity = quantity - ?,
                         quantity_sell = quantity_sell + ?,
                         update_date = NOW(6)
                     WHERE id = ? AND status_product = 'Active' AND quantity >= ?",
                    [$item['quantity'], $item['quantity'], $item['product_id'], $item['quantity']]
                );
                if ($affected !== 1) {
                    throw new RuntimeException('Sản phẩm không hoạt động hoặc không đủ tồn kho.');
                }

                $this->db->execute(
                    'DELETE FROM cart WHERE user_id = ? AND product_id = ?',
                    [$orderData['user_id'], $item['product_id']]
                );
            }

            $this->db->execute(
                "INSERT INTO payments
                    (order_id, request_id, provider_order_id, provider, amount, status, payload, created_at, updated_at)
                 VALUES (?, ?, ?, 'COD', ?, 'Pending', ?, NOW(6), NOW(6))",
                [
                    $orderId,
                    'CODREQ' . $codSuffix,
                    'COD' . $codSuffix,
                    $orderData['total'],
                    json_encode(['method' => 'cash_on_delivery'], JSON_UNESCAPED_SLASHES),
                ]
            );

            $this->db->commit();
            return $orderId;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tạo đơn chờ thanh toán, giữ kho và payment trong cùng transaction.
     */
    public function createPendingOrder(
        array $orderData,
        array $cartItems,
        string $requestId,
        string $providerOrderId,
        string $provider = 'MoMo'
    ): array {
        if (!$cartItems) {
            throw new InvalidArgumentException('Giỏ hàng đang trống.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO orders
                    (user_id, address, phone, total, quantity_product, status_order, date,
                     ghn_total_fee, to_district_id, to_ward_code, shipping_status)
                 VALUES (?, ?, ?, ?, ?, 'Cho_Thanh_Toan', NOW(6), ?, ?, ?, ?)",
                array_merge([
                    $orderData['user_id'],
                    $orderData['address'],
                    $orderData['phone'],
                    $orderData['total'],
                    $orderData['quantity_product'],
                ], $this->shippingValues($orderData))
            );
            $orderId = (int) $this->db->lastInsertId();

            foreach ($cartItems as $item) {
                $quantity = (int) $item['quantity'];
                $price = (int) $item['price'];
                $discount = (int) $item['discount'];
                $finalPrice = (int) round($price * (1 - $discount / 100));
                $itemSize = !empty($item['size']) ? trim((string) $item['size']) : '40';

                $affected = $this->db->execute(
                    "UPDATE product
                     SET quantity = quantity - ?, update_date = NOW(6)
                     WHERE id = ?
                       AND status_product = 'Active'
                       AND quantity >= ?",
                    [$quantity, $item['product_id'], $quantity]
                );
                if ($affected !== 1) {
                    throw new RuntimeException(
                        'Sản phẩm "' . ($item['name'] ?? $item['product_id']) . '" không đủ tồn kho.'
                    );
                }

                $this->db->execute(
                    "INSERT INTO order_detail
                        (order_id, product_id, quantity, size, price, discount, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $item['product_id'],
                        $quantity,
                        $itemSize,
                        $price,
                        $discount,
                        $finalPrice * $quantity,
                    ]
                );
            }

            $this->db->execute(
                "INSERT INTO payments
                    (order_id, request_id, provider_order_id, provider, amount, status, payload, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'Pending', NULL, NOW(6), NOW(6))",
                [$orderId, $requestId, $providerOrderId, $provider, $orderData['total']]
            );
            $paymentId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'request_id' => $requestId,
                'provider_order_id' => $providerOrderId,
                'amount' => (int) $orderData['total'],
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     * @return array{orders: array<int, array<string, mixed>>, notices: array<int, string>}
     */
    public function syncWithGhn(array $orders): array
    {
        require_once APP_PATH . '/services/GhnService.php';
        $ghn = new GhnService();
        $notices = [];
        $labels = [
            'Da_Huy' => 'Đã hủy',
            'Dang_Giao' => 'Đang giao',
            'Da_Giao' => 'Đã giao',
            'Da_Tra_Hang' => 'Đã trả hàng',
        ];

        foreach ($orders as &$order) {
            $code = trim((string) ($order['ghn_order_code'] ?? ''));
            $status = (string) ($order['status_order'] ?? '');
            if ($code === '' || in_array($status, ['Da_Huy', 'Da_Tra_Hang', 'Da_Nhan_Hang', 'Cho_Thanh_Toan'], true)) {
                continue;
            }

            $detail = $ghn->getOrderDetail($code);
            if (($detail['code'] ?? 0) !== 200) {
                continue;
            }

            $interpreted = $ghn->interpretStatus((string) ($detail['data']['status'] ?? ''));
            $newStatus = $this->applyGhnStatus(
                (int) $order['id'],
                $interpreted['shipping_status'],
                $interpreted['order_status']
            );
            $order['shipping_status'] = $interpreted['shipping_status'];
            if ($newStatus !== null && $newStatus !== $status) {
                $order['status_order'] = $newStatus;
                $notices[] = 'Đơn #' . (int) $order['id']
                    . ' chuyển sang ' . ($labels[$newStatus] ?? $newStatus)
                    . ' theo trạng thái GHN (' . GhnService::statusLabel($interpreted['shipping_status']) . ').';
            }
        }
        unset($order);

        return ['orders' => $orders, 'notices' => $notices];
    }

    public function applyGhnStatus(int $orderId, string $shippingStatus, ?string $targetStatus): ?string
    {
        $order = $this->db->fetchOne(
            'SELECT id, status_order, shipping_status FROM orders WHERE id = ? LIMIT 1',
            [$orderId]
        );
        if (!$order) {
            return null;
        }

        $current = (string) $order['status_order'];
        $shippingStatus = substr($shippingStatus, 0, 40);
        if ((string) ($order['shipping_status'] ?? '') !== $shippingStatus) {
            $this->db->execute(
                'UPDATE orders SET shipping_status = ? WHERE id = ?',
                [$shippingStatus, $orderId]
            );
        }

        if ($targetStatus === null || $targetStatus === $current
            || in_array($current, ['Da_Nhan_Hang', 'Da_Huy', 'Da_Tra_Hang'], true)) {
            return null;
        }

        require_once APP_PATH . '/models/AdminOrderModel.php';
        $admin = new AdminOrderModel();
        $movedTo = null;
        for ($guard = 0; $current !== $targetStatus && $guard < 4; $guard++) {
            $next = $this->ghnNextStatus($current, $targetStatus);
            if ($next === null || !in_array($next, $admin->allowedNextStatuses($current), true)) {
                break;
            }
            if (!$admin->changeStatus($orderId, $next)) {
                break;
            }
            $current = $next;
            $movedTo = $current;
        }

        return $movedTo;
    }

    private function ghnNextStatus(string $current, string $target): ?string
    {
        if ($target === 'Da_Huy' && in_array($current, ['Cho_Thanh_Toan', 'Dang_Xu_Ly', 'Yeu_Cau_Huy', 'Dang_Giao'], true)) {
            return 'Da_Huy';
        }
        if ($target === 'Dang_Giao' && $current === 'Dang_Xu_Ly') {
            return 'Dang_Giao';
        }
        if ($target === 'Da_Giao' && $current === 'Dang_Xu_Ly') {
            return 'Dang_Giao';
        }
        if ($target === 'Da_Giao' && $current === 'Dang_Giao') {
            return 'Da_Giao';
        }
        if ($target === 'Da_Tra_Hang' && $current === 'Yeu_Cau_Tra_Hang') {
            return 'Da_Tra_Hang';
        }

        return null;
    }

    public function attachGhnShipment(int $orderId, string $orderCode, string $status): void
    {
        $this->db->execute(
            "UPDATE orders SET ghn_order_code = ?, shipping_status = ? WHERE id = ?",
            [$orderCode, $status, $orderId]
        );
    }

    private function shippingValues(array $orderData): array
    {
        return [
            (int) ($orderData['ghn_total_fee'] ?? 0),
            isset($orderData['to_district_id']) ? (int) $orderData['to_district_id'] : null,
            ($orderData['to_ward_code'] ?? '') !== '' ? (string) $orderData['to_ward_code'] : null,
            (string) ($orderData['shipping_status'] ?? 'not_shipped'),
        ];
    }

    public function saveGatewayPayload(int $paymentId, array $payload): void
    {
        $this->db->execute(
            "UPDATE payments SET payload = ?, updated_at = NOW(6) WHERE id = ?",
            [json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $paymentId]
        );
    }

    public function getPaymentByProviderOrderId(string $providerOrderId): array|false
    {
        return $this->db->fetchOne(
            "SELECT p.*, o.user_id, o.status_order
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             WHERE p.provider_order_id = ?
             LIMIT 1",
            [$providerOrderId]
        );
    }

    public function getPaymentByOrderId(int $orderId): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM payments WHERE order_id = ? LIMIT 1",
            [$orderId]
        );
    }

    public function reopenMomoPayment(int $orderId, string $requestId, string $providerOrderId): ?array
    {
        $order = $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? AND status_order = 'Cho_Thanh_Toan' LIMIT 1",
            [$orderId]
        );
        $payment = $order
            ? $this->db->fetchOne('SELECT * FROM payments WHERE order_id = ? LIMIT 1', [$orderId])
            : false;
        if (!$order || !$payment || !in_array((string) $payment['status'], ['Pending', 'Failed'], true)) {
            return null;
        }
        $updated = $this->db->execute(
            "UPDATE payments
             SET request_id = ?, provider_order_id = ?, provider = 'MoMo', status = 'Pending',
                 result_code = NULL, trans_id = NULL, updated_at = NOW(6)
             WHERE id = ? AND status IN ('Pending', 'Failed')",
            [$requestId, $providerOrderId, $payment['id']]
        );
        if ($updated !== 1) {
            return null;
        }

        return [
            'order_id' => $orderId,
            'payment_id' => (int) $payment['id'],
            'request_id' => $requestId,
            'provider_order_id' => $providerOrderId,
            'amount' => (int) $order['total'],
        ];
    }

    public function markGatewayAttemptFailed(string $providerOrderId, array $payload): bool
    {
        $payment = $this->db->fetchOne(
            'SELECT * FROM payments WHERE provider_order_id = ? LIMIT 1',
            [$providerOrderId]
        );
        if (!$payment || (string) $payment['status'] === 'Paid') {
            return false;
        }
        if ((string) $payment['status'] === 'Failed') {
            return true;
        }

        return $this->db->execute(
            "UPDATE payments
             SET status = 'Failed', result_code = ?, payload = ?, updated_at = NOW(6)
             WHERE id = ? AND status = 'Pending'",
            [
                isset($payload['resultCode']) ? (int) $payload['resultCode'] : null,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $payment['id'],
            ]
        ) === 1;
    }

    public function findOrderForUser(int $orderId, int $userId): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1",
            [$orderId, $userId]
        );
    }

    /**
     * Chỉ chuyển Pending -> Paid một lần; callback lặp không gây side effect.
     */
    public function finalizePaymentSuccess(string $providerOrderId, array $payload): bool
    {
        $this->db->beginTransaction();
        try {
            $payment = $this->db->fetchOne(
                "SELECT p.*, o.user_id
                 FROM payments p
                 JOIN orders o ON o.id = p.order_id
                 WHERE p.provider_order_id = ?
                 FOR UPDATE",
                [$providerOrderId]
            );
            if (!$payment) {
                $this->db->rollBack();
                return false;
            }
            if ($payment['status'] === 'Paid') {
                $this->db->commit();
                return true;
            }
            if ($payment['status'] !== 'Pending') {
                $this->db->commit();
                return false;
            }

            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->db->execute(
                "UPDATE payments
                 SET status = 'Paid', trans_id = ?, result_code = ?, payload = ?,
                     paid_at = NOW(6), updated_at = NOW(6)
                 WHERE id = ? AND status = 'Pending'",
                [
                    (string) ($payload['transId'] ?? ''),
                    (int) ($payload['resultCode'] ?? 0),
                    $encoded,
                    $payment['id'],
                ]
            );
            $this->db->execute(
                "UPDATE orders SET status_order = 'Dang_Xu_Ly'
                 WHERE id = ? AND status_order = 'Cho_Thanh_Toan'",
                [$payment['order_id']]
            );
            $this->db->execute(
                "UPDATE product p
                 JOIN order_detail od ON od.product_id = p.id
                 SET p.quantity_sell = p.quantity_sell + od.quantity,
                     p.update_date = NOW(6)
                 WHERE od.order_id = ?",
                [$payment['order_id']]
            );
            $this->db->execute(
                "DELETE c FROM cart c
                 JOIN order_detail od
                   ON od.product_id = c.product_id AND od.order_id = ?
                 WHERE c.user_id = ?",
                [$payment['order_id'], $payment['user_id']]
            );
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Chỉ Pending -> Failed; lock payment đảm bảo kho được hoàn đúng một lần.
     */
    public function finalizePaymentFailure(string $providerOrderId, array $payload): bool
    {
        $this->db->beginTransaction();
        try {
            $payment = $this->db->fetchOne(
                "SELECT * FROM payments WHERE provider_order_id = ? FOR UPDATE",
                [$providerOrderId]
            );
            if (!$payment) {
                $this->db->rollBack();
                return false;
            }
            if ($payment['status'] === 'Failed') {
                $this->db->commit();
                return true;
            }
            if ($payment['status'] !== 'Pending') {
                $this->db->commit();
                return false;
            }

            $this->db->execute(
                "UPDATE payments
                 SET status = 'Failed', result_code = ?, payload = ?, updated_at = NOW(6)
                 WHERE id = ? AND status = 'Pending'",
                [
                    isset($payload['resultCode']) ? (int) $payload['resultCode'] : null,
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $payment['id'],
                ]
            );
            $this->db->execute(
                "UPDATE product p
                 JOIN order_detail od ON od.product_id = p.id
                 SET p.quantity = p.quantity + od.quantity,
                     p.update_date = NOW(6)
                 WHERE od.order_id = ?",
                [$payment['order_id']]
            );
            $this->db->execute(
                "UPDATE orders SET status_order = 'Da_Huy'
                 WHERE id = ? AND status_order = 'Cho_Thanh_Toan'",
                [$payment['order_id']]
            );
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng (dùng cho admin)
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $validStatuses = [
            'Cho_Thanh_Toan', 'Dang_Xu_Ly', 'Dang_Giao', 'Da_Giao',
            'Da_Nhan_Hang', 'Yeu_Cau_Tra_Hang', 'Da_Tra_Hang',
            'Yeu_Cau_Huy', 'Da_Huy',
        ];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $affected = $this->db->execute(
            "UPDATE orders SET status_order = ? WHERE id = ?",
            [$status, $orderId]
        );
        return $affected > 0;
    }

    /**
     * User yêu cầu hủy đơn hàng
     * Chỉ hủy được khi đang ở trạng thái Dang_Xu_Ly
     */
    public function requestCancel(int $orderId, int $userId): bool
    {
        $affected = $this->db->execute(
            "UPDATE orders SET status_order = 'Yeu_Cau_Huy'
             WHERE id = ? AND user_id = ? AND status_order = 'Dang_Xu_Ly'",
            [$orderId, $userId]
        );
        return $affected > 0;
    }

    public function confirmReceived(int $orderId, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $updated = $this->db->execute(
                "UPDATE orders SET status_order = 'Da_Nhan_Hang'
                 WHERE id = ? AND user_id = ? AND status_order = 'Da_Giao'",
                [$orderId, $userId]
            );
            if ($updated !== 1) {
                $this->db->rollBack();
                return false;
            }
            $this->db->execute(
                "UPDATE payments
                 SET status = 'Paid', result_code = 0, paid_at = NOW(6), updated_at = NOW(6)
                 WHERE order_id = ? AND provider = 'COD' AND status = 'Pending'",
                [$orderId]
            );
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function requestReturn(int $orderId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE orders SET status_order = 'Yeu_Cau_Tra_Hang'
             WHERE id = ? AND user_id = ? AND status_order = 'Da_Giao'",
            [$orderId, $userId]
        ) > 0;
    }

    /**
     * Lấy tất cả đơn hàng (Admin)
     */
    public function getAllOrders(string $status = ''): array
    {
        $sql    = "SELECT o.*, u.username, u.full_name
                   FROM orders o
                   JOIN `user` u ON o.user_id = u.id";
        $params = [];

        if ($status) {
            $sql   .= " WHERE o.status_order = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.date DESC";
        return $this->db->fetchAll($sql, $params);
    }
}
