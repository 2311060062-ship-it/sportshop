<?php

require_once APP_PATH . '/models/AdminOrderModel.php';

class AdminOrderController extends Controller
{
    private const PER_PAGE = 10;

    private AdminOrderModel $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->orderModel = new AdminOrderModel();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? 'all');
        if (!in_array($status, array_merge(['all'], $this->orderModel->statuses()), true)) {
            $status = 'all';
        }
        $page = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $page = $page === false ? 1 : (int) $page;
        $total = $this->orderModel->countOrders($keyword, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);
        $orders = $this->orderModel->getOrders(
            $keyword,
            $status,
            self::PER_PAGE,
            ($page - 1) * self::PER_PAGE
        );
        require_once APP_PATH . '/models/OrderModel.php';
        $synced = (new OrderModel())->syncWithGhn($orders);
        if ($synced['notices'] !== []) {
            $orders = $this->orderModel->getOrders(
                $keyword,
                $status,
                self::PER_PAGE,
                ($page - 1) * self::PER_PAGE
            );
            $total = $this->orderModel->countOrders($keyword, $status);
            $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        } else {
            $orders = $synced['orders'];
        }
        foreach ($orders as &$order) {
            $order['allowed_statuses'] = $this->orderModel->allowedNextStatuses(
                (string) $order['status_order']
            );
        }
        unset($order);

        $this->loadView('admin/order/index', [
            'pageTitle' => 'Quản lý đơn hàng',
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
            'statuses' => $this->orderModel->statuses(),
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    public function detail($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $orderId = $this->validId($id);
        $order = $orderId > 0 ? $this->orderModel->findById($orderId) : false;
        if (!$order) {
            $this->setFlash('error', 'Không tìm thấy đơn hàng.');
            $this->redirect('admin-order/index');
        }

        require_once APP_PATH . '/models/OrderModel.php';
        $synced = (new OrderModel())->syncWithGhn([$order]);
        $order = $this->orderModel->findById($orderId) ?: $synced['orders'][0];

        $this->loadView('admin/order/detail', [
            'pageTitle' => 'Chi tiết đơn hàng #' . $orderId,
            'order' => $order,
            'items' => $this->orderModel->getItems($orderId),
            'allowedStatuses' => $this->orderModel->allowedNextStatuses(
                (string) $order['status_order']
            ),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    public function status($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
            $this->redirect('admin-order/index');
        }

        $orderId = $this->validId($id);
        $newStatus = (string) ($_POST['status_order'] ?? '');
        $order = $orderId > 0 ? $this->orderModel->findById($orderId) : false;
        if (!$order) {
            $this->setFlash('error', 'Không tìm thấy đơn hàng.');
        } elseif (!in_array(
            $newStatus,
            $this->orderModel->allowedNextStatuses((string) $order['status_order']),
            true
        )) {
            $this->setFlash('error', 'Không thể chuyển sang trạng thái đã chọn.');
        } else {
            try {
                if ($this->orderModel->changeStatus($orderId, $newStatus)) {
                    if ($newStatus === 'Da_Huy' && !empty($order['ghn_order_code'])) {
                        require_once APP_PATH . '/services/GhnService.php';
                        (new GhnService())->cancelOrder([(string) $order['ghn_order_code']]);
                        require_once APP_PATH . '/models/OrderModel.php';
                        (new OrderModel())->applyGhnStatus($orderId, 'cancel', null);
                    }
                    $this->setFlash('success', 'Đã cập nhật trạng thái đơn hàng #' . $orderId . '.');
                } else {
                    $this->setFlash('error', 'Không thể cập nhật trạng thái đơn hàng.');
                }
            } catch (Throwable $exception) {
                $this->setFlash('error', 'Trạng thái đơn hàng vừa thay đổi. Vui lòng tải lại trang.');
            }
        }

        $returnPath = ($_POST['return_to'] ?? '') === 'detail'
            ? 'admin-order/detail/' . $orderId
            : 'admin-order/index';
        $this->redirect($returnPath);
    }

    private function validId($value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? 0 : (int) $id;
    }
}
