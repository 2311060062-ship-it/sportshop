<?php

require_once APP_PATH . '/models/CartModel.php';
require_once APP_PATH . '/models/ProductModel.php';

class CartController extends Controller
{
    private CartModel $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $userId = (int) $_SESSION['user_id'];
        $cartItems = $this->cartModel->getCartByUser($userId);
        $availableIds = array_map(static fn (array $item): int => (int) $item['id'], $cartItems);
        $savedIds = is_array($_SESSION['checkout_cart_ids'] ?? null)
            ? array_map('intval', $_SESSION['checkout_cart_ids'])
            : $availableIds;
        $selectedCartIds = array_values(array_intersect($availableIds, $savedIds));

        $this->loadView('cart/index', [
            'pageTitle' => 'Giỏ Hàng – Sport Shop',
            'cartItems' => $cartItems,
            'total' => $this->cartModel->calcTotal($cartItems),
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
            'csrfToken' => $this->csrfToken(),
            'selectedCartIds' => $selectedCartIds,
            'flash' => $this->getFlash(),
        ]);
    }

    public function checkoutSelected(): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên lựa chọn đã hết hạn. Vui lòng thử lại.');
            $this->redirect('cart/index');
        }

        $cartIds = is_array($_POST['cart_ids'] ?? null) ? $_POST['cart_ids'] : [];
        $cartIds = array_values(array_unique(array_filter(
            array_map('intval', $cartIds),
            static fn (int $id): bool => $id > 0
        )));
        $userId = (int) $_SESSION['user_id'];
        $selectedItems = $this->cartModel->getCartByIdsForUser($userId, $cartIds);
        if (!$cartIds || count($selectedItems) !== count($cartIds)) {
            $this->setFlash('error', 'Vui lòng chọn ít nhất một sản phẩm hợp lệ để thanh toán.');
            $this->redirect('cart/index');
        }

        $requestedQuantities = is_array($_POST['quantities'] ?? null) ? $_POST['quantities'] : [];
        foreach ($selectedItems as $item) {
            $cartId = (int) $item['id'];
            $quantity = filter_var(
                $requestedQuantities[$cartId] ?? $item['quantity'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($quantity === false
                || $item['status_product'] !== 'Active'
                || $quantity > (int) $item['stock_quantity']) {
                $this->setFlash('error', 'Số lượng của "' . $item['name'] . '" không hợp lệ hoặc vượt quá tồn kho.');
                $this->redirect('cart/index');
            }
        }
        foreach ($selectedItems as $item) {
            $cartId = (int) $item['id'];
            $quantity = (int) ($requestedQuantities[$cartId] ?? $item['quantity']);
            if ($quantity !== (int) $item['quantity']) {
                $this->cartModel->updateQuantity($cartId, $userId, $quantity);
            }
        }

        $_SESSION['checkout_cart_ids'] = $cartIds;
        $this->redirect('checkout/index');
    }

    public function update(): void
    {
        $data = $this->requireJsonPost();
        $userId = (int) $_SESSION['user_id'];
        $cartId = (int) ($data['cart_id'] ?? $data['id'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);

        if ($cartId <= 0 || $quantity <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu giỏ hàng không hợp lệ!'], 422);
        }

        $item = $this->cartModel->findOwnedItem($cartId, $userId);
        if (!$item) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ!'], 404);
        }
        if ($item['status_product'] !== 'Active') {
            $this->jsonResponse(['success' => false, 'message' => 'Sản phẩm đã ngừng bán!'], 409);
        }
        if ($quantity > (int) $item['stock_quantity']) {
            $this->jsonResponse(['success' => false, 'message' => 'Số lượng vượt quá tồn kho!'], 409);
        }

        $this->cartModel->updateQuantity($cartId, $userId, $quantity);
        $items = $this->cartModel->getCartByUser($userId);
        $this->jsonResponse([
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng!',
            'total' => $this->cartModel->calcTotal($items),
            'cartCount' => $this->cartModel->countItems($userId),
        ]);
    }

    public function updateSize(): void
    {
        $data = $this->requireJsonPost();
        $userId = (int) $_SESSION['user_id'];
        $cartId = (int) ($data['cart_id'] ?? $data['id'] ?? 0);
        $size = trim((string) ($data['size'] ?? ''));
        $result = $this->cartModel->updateSize($cartId, $userId, $size);
        $status = (string) ($result['status'] ?? 'invalid');

        if ($status === 'missing') {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ!'], 404);
        }
        if ($status === 'invalid') {
            $this->jsonResponse(['success' => false, 'message' => 'Size này không có trên sản phẩm.'], 422);
        }
        if ($status === 'stock') {
            $this->jsonResponse(['success' => false, 'message' => 'Gộp size vượt quá tồn kho của sản phẩm.'], 409);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Đã đổi size thành ' . $size . '.',
            'merged' => !empty($result['merged']),
            'cart_id' => (int) $result['cart_id'],
            'removed_cart_id' => (int) $result['removed_cart_id'],
            'quantity' => (int) $result['quantity'],
            'size' => $size,
        ]);
    }

    public function remove(): void
    {
        $data = $this->requireJsonPost();
        $userId = (int) $_SESSION['user_id'];
        $cartId = (int) ($data['cart_id'] ?? $data['id'] ?? 0);

        if ($cartId <= 0 || !$this->cartModel->findOwnedItem($cartId, $userId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ!'], 404);
        }

        $this->cartModel->removeItem($cartId, $userId);
        $items = $this->cartModel->getCartByUser($userId);
        $this->jsonResponse([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ!',
            'total' => $this->cartModel->calcTotal($items),
            'cartCount' => $this->cartModel->countItems($userId),
        ]);
    }

    private function requireJsonPost(): array
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập!'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ!'], 405);
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        if (method_exists($this, 'verifyCsrfToken')) {
            $token = (string) ($data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if ($this->verifyCsrfToken($token) === false) {
                $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ!'], 403);
            }
        }

        return $data;
    }

    private function csrfToken(): string
    {
        return method_exists($this, 'generateCsrfToken')
            ? (string) $this->generateCsrfToken()
            : '';
    }
}
