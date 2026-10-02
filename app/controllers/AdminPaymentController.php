<?php

require_once APP_PATH . '/models/PaymentQrModel.php';
require_once APP_PATH . '/models/OrderModel.php';
require_once APP_PATH . '/services/FileUploadService.php';

class AdminPaymentController extends Controller
{
    private const PER_PAGE = 10;
    private const PROVIDERS = ['VNPayQR'];

    private PaymentQrModel $paymentModel;
    private OrderModel $orderModel;
    private FileUploadService $qrUpload;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->paymentModel = new PaymentQrModel();
        $this->orderModel = new OrderModel();
        $this->qrUpload = new FileUploadService('payment-qr');
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['Pending', 'Paid', 'Failed'], true)
            ? (string) $_GET['status']
            : 'all';
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $page = $page === false ? 1 : (int) $page;
        $total = $this->paymentModel->countTransactions($keyword, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        $settings = [];
        foreach (self::PROVIDERS as $provider) {
            $settings[$provider] = $this->paymentModel->getSetting($provider);
        }
        $this->loadView('admin/payment/index', [
            'pageTitle' => 'Thanh toán QR',
            'settings' => $settings,
            'transactions' => $this->paymentModel->getTransactions(
                $keyword,
                $status,
                self::PER_PAGE,
                ($page - 1) * self::PER_PAGE
            ),
            'keyword' => $keyword,
            'status' => $status,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    public function save($provider): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->validCsrf()) {
            $this->redirect('admin-payment/index');
        }
        $provider = (string) $provider;
        if (!in_array($provider, self::PROVIDERS, true)) {
            $this->setFlash('error', 'Nhà cung cấp QR không hợp lệ.');
            $this->redirect('admin-payment/index');
        }

        $existing = $this->paymentModel->getSetting($provider);
        $newImage = null;
        try {
            $newImage = $this->qrUpload->upload('qr_image');
            $image = $newImage ?: (string) ($existing['qr_image'] ?? '');
            $accountName = trim((string) ($_POST['account_name'] ?? ''));
            $prefix = strtoupper(trim((string) ($_POST['transfer_prefix'] ?? 'SPORTSHOP')));
            if ($image === '' || $accountName === '') {
                throw new RuntimeException('Vui lòng nhập tên người nhận và tải ảnh QR.');
            }
            if (!preg_match('/^[A-Z0-9]{2,20}$/', $prefix)) {
                throw new RuntimeException('Tiền tố mã chuyển khoản chỉ gồm 2-20 chữ cái hoặc số.');
            }
            $this->paymentModel->saveSetting($provider, [
                'display_name' => 'VNPay QR',
                'account_name' => mb_substr($accountName, 0, 150),
                'account_number' => mb_substr(trim((string) ($_POST['account_number'] ?? '')), 0, 100),
                'qr_image' => $image,
                'transfer_prefix' => $prefix,
                'instructions' => mb_substr(trim((string) ($_POST['instructions'] ?? '')), 0, 500),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], (int) $_SESSION['admin_user_id']);
            if ($newImage && !empty($existing['qr_image'])) {
                $this->qrUpload->remove((string) $existing['qr_image']);
            }
            $this->setFlash('success', 'Đã lưu cấu hình VNPay QR.');
        } catch (Throwable $exception) {
            if ($newImage) {
                $this->qrUpload->remove($newImage);
            }
            $this->setFlash('error', $exception->getMessage());
        }
        $this->redirect('admin-payment/index');
    }

    public function approve($id): void
    {
        $this->review($id, true);
    }

    public function reject($id): void
    {
        $this->review($id, false);
    }

    private function review($id, bool $approve): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->validCsrf()) {
            $this->redirect('admin-payment/index');
        }
        $paymentId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $payment = $paymentId === false ? false : $this->paymentModel->findTransaction((int) $paymentId);
        if (!$payment || $payment['status'] !== 'Pending') {
            $this->setFlash('error', 'Giao dịch không tồn tại hoặc đã được xử lý.');
            $this->redirect('admin-payment/index');
        }
        try {
            $payload = [
                'resultCode' => $approve ? 0 : -3,
                'transId' => 'ADMIN-' . date('YmdHis'),
                'source' => 'admin_qr_review',
                'admin_id' => (int) $_SESSION['admin_user_id'],
            ];
            $ok = $approve
                ? $this->orderModel->finalizePaymentSuccess((string) $payment['provider_order_id'], $payload)
                : $this->orderModel->finalizePaymentFailure((string) $payment['provider_order_id'], $payload);
            if (!$ok) {
                throw new RuntimeException('Giao dịch đã được xử lý trước đó.');
            }
            $this->paymentModel->markReviewed((int) $paymentId, (int) $_SESSION['admin_user_id']);
            $this->setFlash('success', $approve ? 'Đã xác nhận khách hàng thanh toán.' : 'Đã từ chối giao dịch và hoàn tồn kho.');
        } catch (Throwable $exception) {
            $this->setFlash('error', $exception->getMessage());
        }
        $this->redirect('admin-payment/index');
    }

    private function validCsrf(): bool
    {
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
            return false;
        }
        return true;
    }
}
