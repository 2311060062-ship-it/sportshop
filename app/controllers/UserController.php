<?php

require_once APP_PATH . '/models/CartModel.php';
require_once APP_PATH . '/models/OrderModel.php';
require_once APP_PATH . '/models/ProductModel.php';
require_once APP_PATH . '/models/CommentModel.php';
require_once APP_PATH . '/models/UserModel.php';

class UserController extends Controller
{
    private CartModel $cartModel;
    private OrderModel $orderModel;
    private CommentModel $commentModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->cartModel = new CartModel();
        $this->orderModel = new OrderModel();
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
    }

    public function profile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->setFlash('error', 'Không tìm thấy thông tin tài khoản.');
            $this->redirect('auth/logout');
        }

        $this->loadView('user/profile', [
            'pageTitle' => 'Tài khoản của tôi – Sport Shop',
            'user' => $user,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
        ]);
    }

    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $gender = trim((string) ($_POST['gender'] ?? ''));

        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên cập nhật đã hết hạn. Vui lòng thử lại.');
        } elseif ($fullName === '' || $this->textLength($fullName) > 255) {
            $this->setFlash('error', 'Họ và tên phải từ 1 đến 255 ký tự.');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Địa chỉ email không hợp lệ.');
        } elseif ($email !== '' && $this->userModel->emailExistsForOtherUser($email, $userId)) {
            $this->setFlash('error', 'Email này đã được sử dụng bởi tài khoản khác.');
        } elseif ($phone !== '' && !preg_match('/^(?:\+84|0)[0-9]{9,10}$/', preg_replace('/[\s.-]+/', '', $phone))) {
            $this->setFlash('error', 'Số điện thoại không hợp lệ.');
        } elseif (!in_array($gender, ['', 'Nam', 'Nữ', 'Khác'], true)) {
            $this->setFlash('error', 'Giới tính không hợp lệ.');
        } else {
            try {
                $this->userModel->updateProfile($userId, [
                    'full_name' => $fullName,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'gender' => $gender !== '' ? $gender : null,
                ]);
                $_SESSION['full_name'] = $fullName;
                $this->setFlash('success', 'Thông tin tài khoản đã được cập nhật.');
            } catch (Throwable $exception) {
                $this->setFlash('error', 'Không thể cập nhật thông tin. Vui lòng thử lại.');
            }
        }

        $this->redirect('user/profile');
    }

    public function changePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên đổi mật khẩu đã hết hạn. Vui lòng thử lại.');
        } elseif (!$this->userModel->verifyPassword($userId, $currentPassword)) {
            $this->setFlash('error', 'Mật khẩu hiện tại không chính xác.');
        } elseif (strlen($newPassword) < 6 || strlen($newPassword) > 72) {
            $this->setFlash('error', 'Mật khẩu mới phải từ 6 đến 72 ký tự.');
        } elseif ($newPassword !== $confirmPassword) {
            $this->setFlash('error', 'Mật khẩu xác nhận không khớp.');
        } elseif ($this->userModel->verifyPassword($userId, $newPassword)) {
            $this->setFlash('error', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
        } else {
            try {
                $this->userModel->changePassword($userId, $newPassword);
                $this->setFlash('success', 'Mật khẩu đã được thay đổi thành công.');
            } catch (Throwable $exception) {
                $this->setFlash('error', 'Không thể đổi mật khẩu. Vui lòng thử lại.');
            }
        }

        $this->redirect('user/profile#change-password');
    }

    public function orders(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $groups = [
            'processing' => ['Cho_Thanh_Toan', 'Dang_Xu_Ly', 'Yeu_Cau_Huy'],
            'shipping' => ['Dang_Giao'],
            'delivered' => ['Da_Giao', 'Da_Nhan_Hang', 'Yeu_Cau_Tra_Hang'],
            'cancelled' => ['Da_Huy', 'Da_Tra_Hang'],
        ];
        $activeGroup = (string) ($_GET['status'] ?? 'processing');
        if (!isset($groups[$activeGroup])) {
            $activeGroup = 'processing';
        }

        $userId = (int) $_SESSION['user_id'];
        $allOrders = $this->orderModel->getOrdersByUser($userId);
        $synced = $this->orderModel->syncWithGhn($allOrders);
        $allOrders = $synced['orders'];
        $ghnNotices = $synced['notices'];
        $counts = array_fill_keys(array_keys($groups), 0);
        $ordersByGroup = array_fill_keys(array_keys($groups), []);

        foreach ($allOrders as $order) {
            foreach ($groups as $group => $statuses) {
                if (in_array((string) $order['status_order'], $statuses, true)) {
                    $counts[$group]++;
                    $ordersByGroup[$group][] = $order;
                    break;
                }
            }
        }

        $visibleOrders = $ordersByGroup[$activeGroup];
        foreach ($visibleOrders as &$visibleOrder) {
            $visibleOrder['items'] = $this->orderModel->getOrderDetail((int) $visibleOrder['id']);
        }
        unset($visibleOrder);

        $this->loadView('user/orders', [
            'pageTitle' => 'Lịch sử mua hàng – Sport Shop',
            'orders' => $visibleOrders,
            'activeGroup' => $activeGroup,
            'counts' => $counts,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
            'ghnNotices' => $ghnNotices,
        ]);
    }

    public function orderDetail($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $orderId = $this->validId($id);
        $userId = (int) $_SESSION['user_id'];
        $order = $orderId ? $this->orderModel->findOrderForUser($orderId, $userId) : false;
        if (!$order) {
            $this->setFlash('error', 'Không tìm thấy đơn hàng.');
            $this->redirect('user/orders');
        }

        $synced = $this->orderModel->syncWithGhn([$order]);
        $order = $synced['orders'][0];
        $items = $this->orderModel->getOrderDetail($orderId);
        $reviews = $this->commentModel->getUserReviewsForProducts(
            $userId,
            array_column($items, 'product_id')
        );

        $this->loadView('user/order-detail', [
            'pageTitle' => 'Chi tiết đơn hàng #' . $orderId . ' – Sport Shop',
            'order' => $order,
            'items' => $items,
            'reviews' => $reviews,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
            'ghnNotices' => $synced['notices'],
        ]);
    }

    public function submitReview($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $orderId = $this->validId($id);
        $productId = $this->validId($_POST['product_id'] ?? null);
        $rate = filter_var(
            $_POST['rate'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 5]]
        );
        $message = trim((string) ($_POST['messages'] ?? ''));
        $userId = (int) $_SESSION['user_id'];

        if (!$orderId) {
            $this->setFlash('error', 'Mã đơn hàng không hợp lệ.');
            $this->redirect('user/orders?status=delivered');
        } elseif (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên đánh giá đã hết hạn. Vui lòng thử lại.');
        } elseif (!$productId || $rate === false) {
            $this->setFlash('error', 'Vui lòng chọn sản phẩm và số sao hợp lệ.');
        } elseif ($message === '' || $this->textLength($message) > 2000) {
            $this->setFlash('error', 'Nội dung đánh giá phải từ 1 đến 2.000 ký tự.');
        } elseif (!$this->commentModel->canReviewProduct($userId, $orderId, $productId)) {
            $this->setFlash('error', 'Bạn chỉ có thể đánh giá sản phẩm sau khi xác nhận đã nhận hàng.');
        } elseif ($this->commentModel->hasReviewed($userId, $productId)) {
            $this->setFlash('error', 'Bạn đã đánh giá sản phẩm này.');
        } else {
            try {
                $created = $this->commentModel->create($userId, $productId, (int) $rate, $message);
                $this->setFlash(
                    $created ? 'success' : 'error',
                    $created ? 'Cảm ơn bạn đã đánh giá sản phẩm.' : 'Không thể lưu đánh giá.'
                );
            } catch (Throwable $exception) {
                $this->setFlash('error', 'Không thể lưu đánh giá. Vui lòng thử lại.');
            }
        }

        $this->redirect('user/order-detail/' . $orderId);
    }

    public function cancelOrder($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $orderId = $this->validId($id);
        $userId = (int) $_SESSION['user_id'];
        if (!$orderId || !$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên yêu cầu đã hết hạn. Vui lòng thử lại.');
        } else {
            $order = $this->orderModel->findOrderForUser($orderId, $userId);
            if (!$order) {
                $this->setFlash('error', 'Không tìm thấy đơn hàng.');
            } elseif ($order['status_order'] !== 'Dang_Xu_Ly') {
                $this->setFlash('error', 'Chỉ đơn hàng đang xử lý mới có thể yêu cầu hủy.');
            } elseif ($this->orderModel->requestCancel($orderId, $userId)) {
                $this->setFlash('success', 'Đã gửi yêu cầu hủy. Vui lòng chờ admin xác nhận.');
            } else {
                $this->setFlash('error', 'Không thể gửi yêu cầu hủy đơn hàng.');
            }
        }

        $this->redirect('user/orders?status=processing');
    }

    public function retryGhn($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $orderId = $this->validId($id);
        $userId = (int) $_SESSION['user_id'];
        if (!$orderId || !$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên yêu cầu đã hết hạn. Vui lòng thử lại.');
            $this->redirect('user/orders?status=processing');
        }

        $order = $this->orderModel->findOrderForUser($orderId, $userId);
        if (!$order || (string) $order['status_order'] !== 'Dang_Xu_Ly' || !empty($order['ghn_order_code'])) {
            $this->setFlash('error', 'Đơn này không thể tạo lại vận đơn.');
            $this->redirect('user/orders?status=processing');
        }

        require_once APP_PATH . '/services/GhnService.php';
        $ghn = new GhnService();
        $items = [];
        foreach ($this->orderModel->getOrderDetail($orderId) as $item) {
            $items[] = [
                'name' => (string) ($item['name'] ?? 'Sản phẩm'),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'price' => (int) ($item['price'] ?? 0),
                'weight' => defined('GHN_DEFAULT_WEIGHT') ? GHN_DEFAULT_WEIGHT : 200,
            ];
        }
        $user = $this->userModel->findById($userId);
        $response = $ghn->createCodShipment(
            'SS' . $orderId,
            [
                'name' => (string) ($user['full_name'] ?? $user['fullname'] ?? ''),
                'phone' => (string) ($order['phone'] ?? ''),
                'address' => (string) ($order['address'] ?? ''),
                'district_id' => (int) ($order['to_district_id'] ?? 0),
                'ward_code' => (string) ($order['to_ward_code'] ?? ''),
                'cod_amount' => (int) ($order['total'] ?? 0),
            ],
            $items,
            max(200, count($items) * (defined('GHN_DEFAULT_WEIGHT') ? GHN_DEFAULT_WEIGHT : 200))
        );
        $code = (string) ($response['data']['order_code'] ?? '');
        if (($response['code'] ?? 0) === 200 && $code !== '') {
            $this->orderModel->attachGhnShipment($orderId, $code, 'ready_to_pick');
            $this->setFlash('success', 'Đã tạo vận đơn GHN ' . $code . ' cho đơn #' . $orderId . '.');
        } else {
            $this->setFlash('error', 'GHN chưa tạo được vận đơn cho đơn #' . $orderId . ': ' . GhnService::waybillError($response));
        }
        $this->redirect('user/orders?status=processing');
    }

    public function deliveryResponse($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $orderId = $this->validId($id);
        $userId = (int) $_SESSION['user_id'];
        $action = (string) ($_POST['delivery_action'] ?? '');
        if (!$orderId || !$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên xác nhận đã hết hạn. Vui lòng thử lại.');
        } else {
            $order = $this->orderModel->findOrderForUser($orderId, $userId);
            if (!$order || $order['status_order'] !== 'Da_Giao') {
                $this->setFlash('error', 'Đơn hàng không còn ở trạng thái chờ xác nhận.');
            } elseif ($action === 'received' && $this->orderModel->confirmReceived($orderId, $userId)) {
                $this->setFlash('success', 'Cảm ơn bạn đã xác nhận nhận hàng. Bạn có thể đánh giá sản phẩm.');
            } elseif ($action === 'return' && $this->orderModel->requestReturn($orderId, $userId)) {
                $this->setFlash('success', 'Đã gửi yêu cầu chưa nhận được hàng/trả hàng. Vui lòng chờ admin xử lý.');
            } else {
                $this->setFlash('error', 'Không thể cập nhật xác nhận giao hàng.');
            }
        }

        $this->redirect('user/orders?status=delivered');
    }

    private function validId($value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? 0 : (int) $id;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

}
