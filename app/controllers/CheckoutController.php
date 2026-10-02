<?php

require_once APP_PATH . '/models/CartModel.php';
require_once APP_PATH . '/models/ProductModel.php';
require_once APP_PATH . '/models/OrderModel.php';
require_once APP_PATH . '/models/UserModel.php';
require_once APP_PATH . '/models/PaymentQrModel.php';
require_once APP_PATH . '/services/MomoPaymentService.php';
require_once APP_PATH . '/services/VnpayPaymentService.php';
require_once APP_PATH . '/services/FileUploadService.php';
require_once APP_PATH . '/services/GhnService.php';

class CheckoutController extends Controller
{
    private CartModel $cartModel;
    private OrderModel $orderModel;
    private UserModel $userModel;
    private MomoPaymentService $momo;
    private VnpayPaymentService $vnpay;
    private PaymentQrModel $paymentQrModel;
    private FileUploadService $proofUpload;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        $this->momo = new MomoPaymentService();
        $this->vnpay = new VnpayPaymentService();
        $this->paymentQrModel = new PaymentQrModel();
        $this->proofUpload = new FileUploadService('payment-proofs');
    }

    public function index(): void
    {
        $this->ensureEmailVerified();
        $this->renderCheckout();
    }

    public function process(): void
    {
        $this->ensureEmailVerified();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->renderCheckout('Phương thức không hợp lệ.');
            return;
        }
        if (!$this->verifyRequestCsrf()) {
            http_response_code(403);
            $this->renderCheckout('CSRF token không hợp lệ.');
            return;
        }
        $paymentMethod = strtolower((string) ($_POST['payment_method'] ?? ''));
        $allowedMethods = ['cod', 'momo'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            $this->renderCheckout('Phương thức thanh toán không hợp lệ.');
            return;
        }
        if ($paymentMethod === 'momo' && !$this->momo->isConfigured()) {
            http_response_code(503);
            $this->renderCheckout('MoMo sandbox chưa được cấu hình.');
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $cartItems = $this->selectedCartItems($userId);
        if (!$cartItems) {
            $this->renderCheckout('Vui lòng quay lại giỏ hàng và chọn sản phẩm cần thanh toán.');
            return;
        }
        foreach ($cartItems as $item) {
            if ($item['status_product'] !== 'Active'
                || (int) $item['quantity'] > (int) $item['stock_quantity']) {
                $this->renderCheckout(
                    'Sản phẩm "' . $item['name'] . '" không còn đủ tồn kho.'
                );
                return;
            }
        }

        $address = trim((string) ($_POST['address'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $districtId = (int) ($_POST['to_district_id'] ?? 0);
        $wardCode = trim((string) ($_POST['to_ward_code'] ?? ''));
        $oldInput = [
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
        ];
        if (function_exists('mb_strlen') ? mb_strlen($fullName) < 2 : strlen($fullName) < 2) {
            $this->renderCheckout('Vui lòng nhập họ và tên người nhận.', $oldInput);
            return;
        }
        if ($address === '' || (function_exists('mb_strlen') ? mb_strlen($address) < 5 : strlen($address) < 5)) {
            $this->renderCheckout('Vui lòng nhập số nhà, tên đường.', $oldInput);
            return;
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->renderCheckout('Email không đúng định dạng.', $oldInput);
            return;
        }
        if ($districtId <= 0 || $wardCode === '') {
            $this->renderCheckout('Vui lòng chọn tỉnh, quận/huyện và phường/xã để tính phí vận chuyển.', $oldInput);
            return;
        }
        $normalizedPhone = preg_replace('/[\s.-]+/', '', $phone) ?? '';
        if (str_starts_with($normalizedPhone, '84')) {
            $normalizedPhone = '0' . substr($normalizedPhone, 2);
        }
        if (!preg_match('/^0(?:3|5|7|8|9)\d{8}$/', $normalizedPhone)) {
            $this->renderCheckout('Số điện thoại phải đủ 10 số và bắt đầu bằng 03, 05, 07, 08 hoặc 09.', $oldInput);
            return;
        }

        $ghn = new GhnService();
        $goodsTotal = $this->cartModel->calcTotal($cartItems);
        $feeResponse = $ghn->calculateFee($districtId, $wardCode, $ghn->cartWeight($cartItems));
        if (($feeResponse['code'] ?? 0) !== 200 || !isset($feeResponse['data']['total'])) {
            $this->renderCheckout('GHN chưa tính được phí vận chuyển cho địa chỉ này. Hãy chọn lại phường/xã.', $oldInput);
            return;
        }
        $shippingFee = (int) $feeResponse['data']['total'];
        $total = $goodsTotal + $shippingFee;
        $shipping = [
            'ghn_total_fee' => $shippingFee,
            'to_district_id' => $districtId,
            'to_ward_code' => $wardCode,
            'shipping_status' => 'pending',
        ];
        $quantity = array_sum(array_map(
            static fn(array $item): int => (int) $item['quantity'],
            $cartItems
        ));

        if ($paymentMethod === 'cod') {
            $clientCode = 'SS' . $userId . date('ymdHis');
            $waybill = $this->createGhnWaybill($ghn, 0, $cartItems, [
                'name' => $fullName,
                'phone' => $normalizedPhone,
                'address' => $address,
                'district_id' => $districtId,
                'ward_code' => $wardCode,
                'cod_amount' => $total,
            ], $clientCode);
            if ($waybill === '') {
                $oldInput['province_id'] = (int) ($_POST['province_id'] ?? 0);
                $oldInput['to_district_id'] = $districtId;
                $oldInput['to_ward_code'] = $wardCode;
                $this->renderCheckout(
                    'GHN chưa tạo được vận đơn: ' . $this->ghnWaybillError
                    . ' Đơn chưa được ghi và giỏ hàng vẫn còn.',
                    $oldInput
                );
                return;
            }
            try {
                $orderId = $this->orderModel->createOrder(
                    array_merge([
                        'user_id' => $userId,
                        'address' => $address,
                        'phone' => $normalizedPhone,
                        'total' => $total,
                        'quantity_product' => $quantity,
                    ], $shipping),
                    $cartItems
                );
                $this->orderModel->attachGhnShipment($orderId, $waybill, 'ready_to_pick');
                unset($_SESSION['checkout_cart_ids']);
                $this->setFlash(
                    'success',
                    'Đặt hàng COD thành công. Đơn hàng #' . $orderId
                    . ' — phí vận chuyển ' . number_format($shippingFee, 0, ',', '.') . ' đ'
                    . '. Mã vận đơn GHN: ' . $waybill . '.'
                );
                $this->redirect('user/orders?status=processing');
            } catch (Throwable $exception) {
                $ghn->cancelOrder([$waybill]);
                $this->renderCheckout('Không thể tạo đơn hàng COD. Vui lòng kiểm tra tồn kho và thử lại.', $oldInput);
                return;
            }
        }

        $suffix = date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        $providerOrderId = 'MOMO' . $suffix;
        $requestId = 'REQ' . $suffix;

        try {
            $pending = $this->orderModel->createPendingOrder(
                array_merge([
                    'user_id' => $userId,
                    'address' => $address,
                    'phone' => $normalizedPhone,
                    'total' => $total,
                    'quantity_product' => $quantity,
                ], $shipping),
                $cartItems,
                $requestId,
                $providerOrderId,
                'MoMo'
            );
            unset($_SESSION['checkout_cart_ids']);
        } catch (Throwable $e) {
            $this->renderCheckout(
                'Chưa thể tạo đơn chờ thanh toán. ' . $e->getMessage(),
                $oldInput
            );
            return;
        }

        try {
            $this->sendToMomo($pending, $fullName);
        } catch (Throwable $e) {
            $this->setFlash(
                'error',
                'MoMo chưa mở được trang thanh toán. ' . $e->getMessage()
                . ' Đơn #' . (int) $pending['order_id'] . ' vẫn giữ, hãy bấm Thanh toán lại.'
            );
            $this->redirect('user/orders?status=processing');
        }
    }

    public function payMomo($orderId): void
    {
        $this->ensureEmailVerified('user/orders?status=processing');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->verifyRequestCsrf()) {
            $this->setFlash('error', 'Phiên thanh toán đã hết hạn. Vui lòng thử lại.');
            $this->redirect('user/orders?status=processing');
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = (int) $_SESSION['user_id'];
        $order = $id ? $this->orderModel->findOrderForUser((int) $id, $userId) : false;
        if (!$order || (string) $order['status_order'] !== 'Cho_Thanh_Toan') {
            $this->setFlash('error', 'Đơn này không còn chờ thanh toán MoMo.');
            $this->redirect('user/orders?status=processing');
        }
        $suffix = date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        $pending = $this->orderModel->reopenMomoPayment((int) $order['id'], 'REQ' . $suffix, 'MOMO' . $suffix);
        if ($pending === null) {
            $this->setFlash('error', 'Không thể tạo lại giao dịch MoMo cho đơn này.');
            $this->redirect('user/orders?status=processing');
        }
        $user = $this->userModel->findById($userId);
        $name = trim((string) ($user['full_name'] ?? $user['fullname'] ?? ''));
        try {
            $this->sendToMomo($pending, $name);
        } catch (Throwable $e) {
            $this->setFlash('error', 'MoMo chưa mở được trang thanh toán. ' . $e->getMessage());
            $this->redirect('user/orders?status=processing');
        }
    }

    /**
     * Redirect trình duyệt chỉ hiển thị; không thay đổi trạng thái thanh toán.
     */
    public function return(): void
    {
        $this->requireLogin();
        $providerOrderId = trim((string) ($_GET['orderId'] ?? ''));
        $payment = $providerOrderId !== ''
            ? $this->orderModel->getPaymentByProviderOrderId($providerOrderId)
            : false;
        $order = $payment
            ? $this->orderModel->findOrderForUser(
                (int) $payment['order_id'],
                (int) $_SESSION['user_id']
            )
            : false;

        $payload = $_GET;
        $signatureValid = $payload !== [] && $this->momo->verifyCallback($payload);
        if ($signatureValid && $payment && $order && (int) $payment['amount'] === (int) ($payload['amount'] ?? -1)) {
            $paid = (int) ($payload['resultCode'] ?? -1) === 0;
            if ($paid) {
                $saved = $this->orderModel->finalizePaymentSuccess($providerOrderId, $payload);
                if ($saved) {
                    $waybill = $this->createWaybillForPaidOrder((int) $order['id']);
                    $this->setFlash(
                        'success',
                        'Thanh toán MoMo thành công. Đơn hàng #' . (int) $order['id']
                        . ($waybill !== '' ? '. Mã vận đơn GHN: ' . $waybill . '.' : '. GHN chưa tạo được vận đơn, bạn có thể tạo lại trong lịch sử đơn.')
                    );
                } else {
                    $this->setFlash('error', 'Giao dịch MoMo không còn ở trạng thái chờ thanh toán.');
                }
            } else {
                $this->orderModel->markGatewayAttemptFailed($providerOrderId, $payload);
                $this->setFlash('error', 'Thanh toán MoMo không thành công. Đơn #' . (int) $order['id'] . ' vẫn giữ, bạn có thể thanh toán lại.');
            }
            $this->redirect('user/orders?status=processing');
        }

        if (!$payment || !$order) {
            http_response_code(404);
            $message = 'Không tìm thấy giao dịch thanh toán.';
            $payment = false;
            $order = false;
        } elseif ($payment['status'] === 'Paid') {
            $message = 'Thanh toán đã được MoMo xác nhận thành công.';
        } elseif ($payment['status'] === 'Failed') {
            $message = 'Thanh toán không thành công hoặc đã bị hủy.';
        } else {
            $message = 'Đang chờ MoMo xác nhận thanh toán.';
        }

        $userId = (int) $_SESSION['user_id'];
        $this->loadView('checkout/result', [
            'pageTitle' => 'Kết Quả Thanh Toán – Sport Shop',
            'order' => $order,
            'payment' => $payment,
            'message' => $message,
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
        ]);
    }

    public function ipn(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Method Not Allowed'], 405);
        }

        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload) || !$this->momo->verifyCallback($payload)) {
            $this->jsonResponse(['message' => 'Invalid signature or payload'], 400);
        }

        $payment = $this->orderModel->getPaymentByProviderOrderId((string) $payload['orderId']);
        if (!$payment) {
            $this->jsonResponse(['message' => 'Payment not found'], 404);
        }
        if ((string) $payment['request_id'] !== (string) $payload['requestId']) {
            $this->jsonResponse(['message' => 'Request ID mismatch'], 400);
        }
        if ((int) $payment['amount'] !== (int) $payload['amount']) {
            $this->jsonResponse(['message' => 'Amount mismatch'], 400);
        }

        try {
            $paid = (int) $payload['resultCode'] === 0;
            $ok = $paid
                ? $this->orderModel->finalizePaymentSuccess((string) $payload['orderId'], $payload)
                : $this->orderModel->markGatewayAttemptFailed((string) $payload['orderId'], $payload);
            if (!$ok) {
                $this->jsonResponse(['message' => 'Payment state conflict'], 409);
            }
            if ($paid) {
                $this->createWaybillForPaidOrder((int) $payment['order_id']);
            }
            http_response_code(204);
            exit;
        } catch (Throwable $e) {
            error_log('MoMo IPN error: ' . $e->getMessage());
            $this->jsonResponse(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Return URL VNPay chỉ hiển thị trạng thái đã lưu bởi IPN.
     */
    public function vnpayReturn(): void
    {
        $this->requireLogin();
        $providerOrderId = trim((string) ($_GET['vnp_TxnRef'] ?? ''));
        $signatureValid = $this->vnpay->verifyCallback($_GET);
        $payment = $providerOrderId !== ''
            ? $this->orderModel->getPaymentByProviderOrderId($providerOrderId)
            : false;
        $order = $payment && $payment['provider'] === 'VNPay'
            ? $this->orderModel->findOrderForUser(
                (int) $payment['order_id'],
                (int) $_SESSION['user_id']
            )
            : false;

        if (!$signatureValid) {
            http_response_code(400);
            $message = 'Chữ ký phản hồi VNPay không hợp lệ.';
        } elseif (!$payment || !$order) {
            http_response_code(404);
            $message = 'Không tìm thấy giao dịch VNPay.';
            $payment = false;
            $order = false;
        } elseif ($payment['status'] === 'Paid') {
            $message = 'Thanh toán đã được VNPay xác nhận thành công.';
        } elseif ($payment['status'] === 'Failed') {
            $message = 'Thanh toán VNPay không thành công hoặc đã bị hủy.';
        } else {
            $message = 'Đang chờ VNPay xác nhận thanh toán qua IPN.';
        }

        $userId = (int) $_SESSION['user_id'];
        $this->loadView('checkout/result', [
            'pageTitle' => 'Kết Quả VNPay – Sport Shop',
            'order' => $order,
            'payment' => $payment,
            'message' => $message,
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
        ]);
    }

    /**
     * IPN VNPay: xác minh checksum, mã giao dịch và số tiền trước khi finalize.
     */
    public function vnpayIpn(): void
    {
        if (!$this->vnpay->verifyCallback($_GET)) {
            $this->vnpayResponse('97', 'Invalid signature');
        }

        $providerOrderId = trim((string) ($_GET['vnp_TxnRef'] ?? ''));
        $payment = $providerOrderId !== ''
            ? $this->orderModel->getPaymentByProviderOrderId($providerOrderId)
            : false;
        if (!$payment || $payment['provider'] !== 'VNPay') {
            $this->vnpayResponse('01', 'Order not found');
        }
        if ((int) $payment['amount'] * 100 !== (int) ($_GET['vnp_Amount'] ?? 0)) {
            $this->vnpayResponse('04', 'Invalid amount');
        }

        $success = $this->vnpay->isSuccessful($_GET);
        $payload = $_GET;
        $payload['resultCode'] = $success ? 0 : (int) ($_GET['vnp_ResponseCode'] ?? -1);
        $payload['transId'] = (string) ($_GET['vnp_TransactionNo'] ?? '');

        try {
            $ok = $success
                ? $this->orderModel->finalizePaymentSuccess($providerOrderId, $payload)
                : $this->orderModel->finalizePaymentFailure($providerOrderId, $payload);
            $this->vnpayResponse($ok ? '00' : '02', $ok ? 'Confirm Success' : 'Order already confirmed');
        } catch (Throwable $e) {
            error_log('VNPay IPN error: ' . $e->getMessage());
            $this->vnpayResponse('99', 'Unknown error');
        }
    }

    public function qr($orderId): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $payment = $id === false
            ? false
            : $this->paymentQrModel->findForCustomer((int) $id, (int) $_SESSION['user_id']);
        if (!$payment) {
            http_response_code(404);
            $this->redirect('checkout/index');
        }
        $payload = json_decode((string) ($payment['payload'] ?? ''), true);
        $transferCode = is_array($payload) && !empty($payload['transfer_code'])
            ? (string) $payload['transfer_code']
            : (string) $payment['provider_order_id'];

        $this->loadView('checkout/qr', [
            'pageTitle' => 'Thanh toán QR – Sport Shop',
            'payment' => $payment,
            'transferCode' => $transferCode,
            'cartCount' => $this->cartModel->countItems((int) $_SESSION['user_id']),
            'authUser' => $this->getAuthUser(),
            'csrfToken' => $this->csrfToken(),
            'message' => (string) ($_SESSION['qr_message'] ?? ''),
        ]);
        unset($_SESSION['qr_message']);
    }

    public function submitQr($orderId): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $payment = $id === false
            ? false
            : $this->paymentQrModel->findForCustomer((int) $id, (int) $_SESSION['user_id']);
        if (!$this->verifyRequestCsrf() || !$payment || $payment['status'] !== 'Pending') {
            $_SESSION['qr_message'] = 'Giao dịch không hợp lệ hoặc đã được xử lý.';
            $this->redirect('checkout/qr/' . max(1, (int) $id));
        }

        $filename = null;
        try {
            $filename = $this->proofUpload->upload('receipt_image');
            if (!$filename) {
                throw new RuntimeException('Vui lòng tải ảnh biên lai thanh toán.');
            }
            if (!$this->paymentQrModel->submitReceipt(
                (int) $payment['id'],
                (int) $_SESSION['user_id'],
                $filename
            )) {
                throw new RuntimeException('Không thể gửi biên lai. Vui lòng thử lại.');
            }
            if (!empty($payment['receipt_image'])) {
                $this->proofUpload->remove((string) $payment['receipt_image']);
            }
            $_SESSION['qr_message'] = 'Đã gửi biên lai. Đơn hàng đang chờ admin xác nhận.';
        } catch (Throwable $exception) {
            if ($filename) {
                $this->proofUpload->remove($filename);
            }
            $_SESSION['qr_message'] = $exception->getMessage();
        }
        $this->redirect('checkout/qr/' . (int) $id);
    }

    public function dynamicQr($orderId): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $order = $id === false
            ? false
            : $this->orderModel->findOrderForUser((int) $id, (int) $_SESSION['user_id']);
        $payment = $order ? $this->orderModel->getPaymentByOrderId((int) $id) : false;
        if (!$order || !$payment || !in_array($payment['provider'], ['MoMo', 'VNPay', 'DemoQR'], true)) {
            http_response_code(404);
            $this->redirect('checkout/index');
        }

        $payload = json_decode((string) ($payment['payload'] ?? ''), true);
        $qrData = '';
        $fallbackUrl = '';
        if (is_array($payload)) {
            $fallbackUrl = (string) (
                $payload['paymentUrl']
                ?? $payload['response']['payUrl']
                ?? ''
            );
            $qrData = (string) (
                $payload['displayQrData']
                ?? $payload['response']['qrCodeUrl']
                ?? $fallbackUrl
            );
        }
        if ($qrData === '') {
            $this->redirect('checkout/index');
        }

        // Check if user wants VNPAY style (you can add logic to detect this)
        $useVnpayStyle = $payment['provider'] === 'VNPay' 
            || (isset($_GET['style']) && $_GET['style'] === 'vnpay');

        $viewName = $useVnpayStyle ? 'checkout/vnpay-qr-payment' : 'checkout/dynamic-qr';

        $this->loadView($viewName, [
            'pageTitle' => 'Quét QR thanh toán – Sport Shop',
            'order' => $order,
            'payment' => $payment,
            'qrData' => $qrData,
            'fallbackUrl' => $fallbackUrl,
            'cartCount' => $this->cartModel->countItems((int) $_SESSION['user_id']),
            'authUser' => $this->getAuthUser(),
        ], !$useVnpayStyle);
    }

    public function vnpayStylePayment($orderId): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $order = $id === false
            ? false
            : $this->orderModel->findOrderForUser((int) $id, (int) $_SESSION['user_id']);
        $payment = $order ? $this->orderModel->getPaymentByOrderId((int) $id) : false;
        if (!$order || !$payment) {
            http_response_code(404);
            $this->redirect('checkout/index');
        }

        $payload = json_decode((string) ($payment['payload'] ?? ''), true);
        $qrData = '';
        if (is_array($payload)) {
            $qrData = (string) (
                $payload['displayQrData']
                ?? $payload['response']['qrCodeUrl']
                ?? $payload['paymentUrl']
                ?? $payload['response']['payUrl']
                ?? ''
            );
        }
        if ($qrData === '') {
            $this->redirect('checkout/index');
        }

        $this->loadView('checkout/vnpay-qr-payment', [
            'pageTitle' => 'Thanh toán VNPAY – Sport Shop',
            'order' => $order,
            'payment' => $payment,
            'qrData' => $qrData,
            'cartCount' => $this->cartModel->countItems((int) $_SESSION['user_id']),
            'authUser' => $this->getAuthUser(),
        ], false);
    }

    public function confirmVnpayStyle($orderId): void
    {
        $this->requireLogin();
        if (APP_ENV !== 'development' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(APP_ENV !== 'development' ? 404 : 405);
            return;
        }
        if (!$this->verifyRequestCsrf()) {
            http_response_code(403);
            $_SESSION['qr_message'] = 'Phiên xác nhận đã hết hạn. Vui lòng thử lại.';
            $this->redirect('checkout/index');
        }

        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $order = $id === false
            ? false
            : $this->orderModel->findOrderForUser((int) $id, (int) $_SESSION['user_id']);
        $payment = $order ? $this->orderModel->getPaymentByOrderId((int) $id) : false;

        if (!$order || !$payment || $payment['provider'] !== 'VNPayStyleDemo') {
            http_response_code(404);
            $_SESSION['qr_message'] = 'Không tìm thấy giao dịch mô phỏng hợp lệ.';
            $this->redirect('checkout/index');
        }

        if ($payment['status'] === 'Pending' && $order['status_order'] === 'Cho_Thanh_Toan') {
            try {
                $confirmed = $this->orderModel->finalizePaymentSuccess(
                    (string) $payment['provider_order_id'],
                    [
                        'resultCode' => 0,
                        'transId' => 'VNPAY-DEMO-' . date('YmdHis'),
                        'source' => 'customer_demo_confirmation',
                    ]
                );
                if (!$confirmed) {
                    throw new RuntimeException('Không thể xác nhận giao dịch.');
                }
            } catch (Throwable $exception) {
                $_SESSION['qr_message'] = 'Không thể xác nhận thanh toán. Vui lòng thử lại.';
                $this->redirect('checkout/vnpay-style-payment/' . (int) $id);
            }
        } elseif ($payment['status'] !== 'Paid') {
            $_SESSION['qr_message'] = 'Giao dịch này đã bị hủy hoặc thất bại.';
            $this->redirect('checkout/index');
        }

        $this->setFlash('success', 'Thanh toán thành công. Đơn hàng đang được xử lý.');
        $this->redirect('user/orders?status=processing');
    }

    public function paymentStatus($orderId): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        $id = filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $order = $id === false
            ? false
            : $this->orderModel->findOrderForUser((int) $id, (int) $_SESSION['user_id']);
        $payment = $order ? $this->orderModel->getPaymentByOrderId((int) $id) : false;
        if (!$order || !$payment) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy giao dịch.'], 404);
        }
        $this->jsonResponse([
            'success' => true,
            'payment_status' => (string) $payment['status'],
            'order_status' => (string) $order['status_order'],
            'paid' => $payment['status'] === 'Paid',
            'failed' => $payment['status'] === 'Failed',
        ]);
    }

    public function demoPay($providerOrderId): void
    {
        if (APP_ENV !== 'development') {
            http_response_code(404);
            return;
        }
        $providerOrderId = trim((string) $providerOrderId);
        if (!preg_match('/^(DEMO|VNSD)[A-Z0-9]+$/', $providerOrderId)) {
            http_response_code(404);
            return;
        }
        $payment = $this->orderModel->getPaymentByProviderOrderId($providerOrderId);
        if (!$payment || !in_array($payment['provider'], ['DemoQR', 'VNPayStyleDemo'], true)) {
            http_response_code(404);
            return;
        }

        $payload = json_decode((string) ($payment['payload'] ?? ''), true);
        $expectedToken = is_array($payload) ? (string) ($payload['demo_token'] ?? '') : '';
        $receivedToken = (string) (
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? ($_POST['token'] ?? '')
                : ($_GET['token'] ?? '')
        );
        $success = $payment['status'] === 'Paid';
        $error = '';

        if (!$success && ($expectedToken === '' || !hash_equals($expectedToken, $receivedToken))) {
            http_response_code(403);
            $error = 'Liên kết thanh toán mô phỏng không hợp lệ.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
            try {
                $success = $this->orderModel->finalizePaymentSuccess($providerOrderId, [
                    'resultCode' => 0,
                    'transId' => 'DEMO-' . date('YmdHis'),
                    'source' => 'local_demo_gateway',
                ]);
                if ($success) {
                    $payment = $this->orderModel->getPaymentByProviderOrderId($providerOrderId);
                }
            } catch (Throwable $exception) {
                $error = 'Không thể xác nhận giao dịch mô phỏng.';
            }
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $this->loadView('checkout/demo-pay', [
            'pageTitle' => 'Cổng thanh toán mô phỏng',
            'payment' => $payment,
            'token' => $receivedToken,
            'success' => $success,
            'error' => $error,
            'cartCount' => $this->isLoggedIn()
                ? $this->cartModel->countItems((int) $_SESSION['user_id'])
                : 0,
            'authUser' => $this->getAuthUser(),
        ]);
    }

    private function ensureEmailVerified(string $returnPath = 'checkout/index'): void
    {
        $this->requireLogin();
        $user = $this->userModel->verificationState((int) $_SESSION['user_id']);
        if ($user && !empty($user['email_verified_at'])) {
            return;
        }

        $_SESSION['after_verify_redirect'] = $returnPath;
        $this->redirect('auth/require-verify');
    }

    private function sendToMomo(array $pending, string $fullName): void
    {
        $created = $this->momo->createPayment([
            'request_id' => $pending['request_id'],
            'provider_order_id' => $pending['provider_order_id'],
            'amount' => $pending['amount'],
            'order_info' => 'Thanh toan don hang #' . $pending['order_id'],
            'extra_data' => (string) $pending['order_id'],
        ]);
        $this->orderModel->saveGatewayPayload((int) $pending['payment_id'], [
            'customer' => $fullName,
            'request' => $created['request'],
            'response' => $created['response'],
        ]);
        header('Location: ' . $created['response']['payUrl']);
        exit;
    }

    private function createWaybillForPaidOrder(int $orderId): string
    {
        $order = $this->orderModel->findById($orderId);
        if (!$order || !empty($order['ghn_order_code'])) {
            return (string) ($order['ghn_order_code'] ?? '');
        }
        $user = $this->userModel->findById((int) $order['user_id']);
        $ghn = new GhnService();
        return $this->createGhnWaybill(
            $ghn,
            $orderId,
            $this->orderModel->getOrderDetail($orderId),
            [
                'name' => is_array($user) ? (string) ($user['full_name'] ?? '') : '',
                'phone' => (string) ($order['phone'] ?? ''),
                'address' => (string) ($order['address'] ?? ''),
                'district_id' => (int) ($order['to_district_id'] ?? 0),
                'ward_code' => (string) ($order['to_ward_code'] ?? ''),
                'cod_amount' => 0,
            ],
            'SSMOMO' . $orderId
        );
    }

    private string $ghnWaybillError = '';

    private function createGhnWaybill(
        GhnService $ghn,
        int $orderId,
        array $cartItems,
        array $receiver,
        string $clientCode = ''
    ): string {
        $items = [];
        foreach ($cartItems as $item) {
            $discount = (int) ($item['discount'] ?? 0);
            $price = (int) round(((int) $item['price']) * (1 - $discount / 100));
            $items[] = [
                'name' => (string) ($item['name'] ?? 'Sản phẩm'),
                'quantity' => (int) $item['quantity'],
                'price' => max($price, 0),
                'weight' => GHN_DEFAULT_WEIGHT,
            ];
        }
        $response = $ghn->createCodShipment(
            $clientCode !== '' ? $clientCode : 'SS' . $orderId,
            $receiver,
            $items,
            $ghn->cartWeight($cartItems)
        );
        $code = (string) ($response['data']['order_code'] ?? '');
        if (($response['code'] ?? 0) === 200 && $code !== '') {
            if ($orderId > 0) {
                $this->orderModel->attachGhnShipment($orderId, $code, 'ready_to_pick');
            }
            return $code;
        }

        $this->ghnWaybillError = GhnService::waybillError($response);
        return '';
    }

    private function renderCheckout(string $error = '', array $old = []): void
    {
        $userId = (int) $_SESSION['user_id'];
        $cartItems = $this->selectedCartItems($userId);
        $this->loadView('checkout/index', [
            'pageTitle' => 'Thanh Toán – Sport Shop',
            'cartItems' => $cartItems,
            'total' => $this->cartModel->calcTotal($cartItems),
            'user' => $this->userModel->findById($userId),
            'old' => $old,
            'error' => $error,
            'cartCount' => $this->cartModel->countItems($userId),
            'authUser' => $this->getAuthUser(),
            'csrfToken' => $this->csrfToken(),
            'qrSettings' => $this->paymentQrModel->getActiveSettings(),
            'gatewayAvailability' => [
                'momo' => $this->momo->isConfigured(),
                'vnpay' => $this->vnpay->isConfigured(),
            ],
        ]);
    }

    private function selectedCartItems(int $userId): array
    {
        $selectedIds = is_array($_SESSION['checkout_cart_ids'] ?? null)
            ? array_values(array_filter(array_map('intval', $_SESSION['checkout_cart_ids'])))
            : [];
        if (!empty($selectedIds)) {
            $items = $this->cartModel->getCartByIdsForUser($userId, $selectedIds);
            if (!empty($items)) {
                return $items;
            }
        }
        return $this->cartModel->getCartByUser($userId);
    }

    private function verifyRequestCsrf(): bool
    {
        if (!method_exists($this, 'verifyCsrfToken')) {
            return true;
        }
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return $this->verifyCsrfToken($token) !== false;
    }

    private function csrfToken(): string
    {
        return method_exists($this, 'generateCsrfToken')
            ? (string) $this->generateCsrfToken()
            : '';
    }

    private function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    private function vnpayResponse(string $code, string $message): void
    {
        $this->jsonResponse(['RspCode' => $code, 'Message' => $message]);
    }
}
