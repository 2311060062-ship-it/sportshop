<?php
/**
 * ============================================================
 *  SPORT SHOP – Cart Model
 *  File: app/models/CartModel.php
 *  Bảng: cart
 * ============================================================
 */

class CartModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy giỏ hàng của user (kèm thông tin sản phẩm)
     */
    public function getCartByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.quantity, c.size, c.product_id, c.user_id,
                    p.name, p.price, p.discount, p.color, p.sizes,
                    p.quantity AS stock_quantity, p.status_product,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id LIMIT 1) AS thumbnail
             FROM cart c
             JOIN product p ON c.product_id = p.id
             WHERE c.user_id = ?
             ORDER BY c.id DESC",
            [$userId]
        );
    }

    public function getCartByIdsForUser(int $userId, array $cartIds): array
    {
        $cartIds = array_values(array_unique(array_filter(
            array_map('intval', $cartIds),
            static fn (int $id): bool => $id > 0
        )));
        if (!$cartIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        return $this->db->fetchAll(
            "SELECT c.id, c.quantity, c.size, c.product_id, c.user_id,
                    p.name, p.price, p.discount, p.color, p.sizes,
                    p.quantity AS stock_quantity, p.status_product,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id LIMIT 1) AS thumbnail
             FROM cart c
             JOIN product p ON c.product_id = p.id
             WHERE c.user_id = ? AND c.id IN ({$placeholders})
             ORDER BY c.id DESC",
            array_merge([$userId], $cartIds)
        );
    }

    /**
     * Đếm số item trong giỏ hàng
     */
    public function countItems(int $userId): int
    {
        $result = $this->db->fetchOne(
            "SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?",
            [$userId]
        );
        return (int) ($result['total'] ?? 0);
    }

    public function getProductQuantity(int $userId, int $productId): int
    {
        $item = $this->db->fetchOne(
            "SELECT SUM(quantity) AS quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        );

        return (int) ($item['quantity'] ?? 0);
    }

    /**
     * Chỉ trả item thuộc đúng user, kèm tồn kho hiện tại.
     */
    public function findOwnedItem(int $cartId, int $userId): array|false
    {
        return $this->db->fetchOne(
            "SELECT c.id, c.quantity, c.size, c.product_id, c.user_id,
                    p.quantity AS stock_quantity, p.status_product
             FROM cart c
             JOIN product p ON p.id = c.product_id
             WHERE c.id = ? AND c.user_id = ?
             LIMIT 1",
            [$cartId, $userId]
        );
    }

    /**
     * Thêm sản phẩm vào giỏ hàng (kèm Size giày)
     * Nếu sản phẩm cùng size đã có → tăng số lượng
     */
    public function addToCart(int $userId, int $productId, int $qty = 1, string $size = '40'): bool
    {
        if ($qty <= 0) {
            return false;
        }
        $size = trim($size) !== '' ? trim($size) : '40';

        $this->db->beginTransaction();
        try {
            $product = $this->db->fetchOne(
                "SELECT quantity, status_product FROM product WHERE id = ? FOR UPDATE",
                [$productId]
            );
            if (!$product || $product['status_product'] !== 'Active') {
                $this->db->rollBack();
                return false;
            }

            $existing = $this->db->fetchOne(
                "SELECT id, quantity FROM cart
                 WHERE user_id = ? AND product_id = ? AND size = ? LIMIT 1 FOR UPDATE",
                [$userId, $productId, $size]
            );
            $newQty = (int) ($existing['quantity'] ?? 0) + $qty;
            if ($newQty > (int) $product['quantity']) {
                $this->db->rollBack();
                return false;
            }

            if ($existing) {
                $this->db->execute(
                    "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?",
                    [$newQty, $existing['id'], $userId]
                );
            } else {
                $this->db->execute(
                    "INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)",
                    [$userId, $productId, $qty, $size]
                );
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Đổi size của một dòng giỏ. Nếu cùng sản phẩm đã có size mới thì gộp số lượng.
     *
     * @return array{status: string, merged: bool, cart_id: int, removed_cart_id: int, quantity: int}
     */
    public function updateSize(int $cartId, int $userId, string $size): array
    {
        $empty = ['status' => 'invalid', 'merged' => false, 'cart_id' => 0, 'removed_cart_id' => 0, 'quantity' => 0];
        $size = trim($size);
        if ($cartId <= 0 || $size === '') {
            return $empty;
        }

        $this->db->beginTransaction();
        try {
            $item = $this->db->fetchOne(
                "SELECT c.id, c.quantity, c.size, c.product_id,
                        p.sizes, p.quantity AS stock_quantity, p.status_product
                 FROM cart c
                 JOIN product p ON p.id = c.product_id
                 WHERE c.id = ? AND c.user_id = ?
                 LIMIT 1 FOR UPDATE",
                [$cartId, $userId]
            );
            if (!$item || $item['status_product'] !== 'Active') {
                $this->db->rollBack();
                return ['status' => 'missing', 'merged' => false, 'cart_id' => 0, 'removed_cart_id' => 0, 'quantity' => 0];
            }

            $allowed = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) ($item['sizes'] ?? ''))
            )));
            if (!in_array($size, $allowed, true)) {
                $this->db->rollBack();
                return $empty;
            }
            if ($size === trim((string) $item['size'])) {
                $this->db->commit();
                return [
                    'status' => 'ok',
                    'merged' => false,
                    'cart_id' => $cartId,
                    'removed_cart_id' => 0,
                    'quantity' => (int) $item['quantity'],
                ];
            }

            $other = $this->db->fetchOne(
                "SELECT id, quantity FROM cart
                 WHERE user_id = ? AND product_id = ? AND size = ? AND id <> ?
                 LIMIT 1 FOR UPDATE",
                [$userId, $item['product_id'], $size, $cartId]
            );
            if ($other) {
                $merged = (int) $other['quantity'] + (int) $item['quantity'];
                if ($merged > (int) $item['stock_quantity']) {
                    $this->db->rollBack();
                    return ['status' => 'stock', 'merged' => false, 'cart_id' => 0, 'removed_cart_id' => 0, 'quantity' => 0];
                }
                $this->db->execute(
                    "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?",
                    [$merged, $other['id'], $userId]
                );
                $this->db->execute(
                    "DELETE FROM cart WHERE id = ? AND user_id = ?",
                    [$cartId, $userId]
                );
                $this->db->commit();
                return [
                    'status' => 'ok',
                    'merged' => true,
                    'cart_id' => (int) $other['id'],
                    'removed_cart_id' => $cartId,
                    'quantity' => $merged,
                ];
            }

            $this->db->execute(
                "UPDATE cart SET size = ? WHERE id = ? AND user_id = ?",
                [$size, $cartId, $userId]
            );
            $this->db->commit();
            return [
                'status' => 'ok',
                'merged' => false,
                'cart_id' => $cartId,
                'removed_cart_id' => 0,
                'quantity' => (int) $item['quantity'],
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ
     */
    public function updateQuantity(int $cartId, int $userId, int $qty): bool
    {
        if ($qty <= 0) {
            return $this->removeItem($cartId, $userId);
        }

        $affected = $this->db->execute(
            "UPDATE cart c
             JOIN product p ON p.id = c.product_id
             SET c.quantity = ?
             WHERE c.id = ? AND c.user_id = ?
               AND p.status_product = 'Active'
               AND p.quantity >= ?",
            [$qty, $cartId, $userId, $qty]
        );
        return $affected > 0;
    }

    /**
     * Xóa 1 sản phẩm khỏi giỏ
     */
    public function removeItem(int $cartId, int $userId): bool
    {
        $affected = $this->db->execute(
            "DELETE FROM cart WHERE id = ? AND user_id = ?",
            [$cartId, $userId]
        );
        return $affected > 0;
    }

    /**
     * Xóa toàn bộ giỏ hàng của user
     */
    public function clearCart(int $userId): bool
    {
        $affected = $this->db->execute(
            "DELETE FROM cart WHERE user_id = ?",
            [$userId]
        );
        return $affected > 0;
    }

    /**
     * Tính tổng tiền giỏ hàng
     */
    public function calcTotal(array $cartItems): int
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $finalPrice = (int) round($item['price'] * (1 - $item['discount'] / 100));
            $total += $finalPrice * $item['quantity'];
        }
        return $total;
    }
}
