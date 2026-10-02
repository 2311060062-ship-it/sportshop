<?php

require_once APP_PATH . '/models/CartModel.php';
require_once APP_PATH . '/services/GhnService.php';

class GhnController extends Controller
{
    private CartModel $cartModel;
    private GhnService $ghn;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->cartModel = new CartModel();
        $this->ghn = new GhnService();
    }

    public function provinces(): void
    {
        $this->jsonResponse($this->ghn->getProvinces());
    }

    public function districts(string $provinceId = '0'): void
    {
        $this->jsonResponse($this->ghn->getDistricts((int) $provinceId));
    }

    public function wards(string $districtId = '0'): void
    {
        $this->jsonResponse($this->ghn->getWards((int) $districtId));
    }

    public function fee(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['code' => 405, 'message' => 'Phương thức không hợp lệ.'], 405);
        }
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $raw = json_decode((string) file_get_contents('php://input'), true);
        if (is_array($raw)) {
            $token = $token !== '' ? $token : (string) ($raw['csrf_token'] ?? '');
            $_POST = array_merge($raw, $_POST);
        }
        if (!$this->verifyCsrfToken($token)) {
            $this->jsonResponse(['code' => 403, 'message' => 'Phiên biểu mẫu đã hết hạn.'], 403);
        }

        $districtId = (int) ($_POST['to_district_id'] ?? 0);
        $wardCode = trim((string) ($_POST['to_ward_code'] ?? ''));
        if ($districtId <= 0 || $wardCode === '') {
            $this->jsonResponse(['code' => 422, 'message' => 'Chưa chọn quận hoặc phường.'], 422);
        }

        $items = $this->selectedCartItems((int) $_SESSION['user_id']);
        if (!$items) {
            $this->jsonResponse(['code' => 422, 'message' => 'Giỏ hàng đang trống.'], 422);
        }

        $this->jsonResponse($this->ghn->calculateFee(
            $districtId,
            $wardCode,
            $this->ghn->cartWeight($items)
        ));
    }

    private function selectedCartItems(int $userId): array
    {
        $selectedIds = is_array($_SESSION['checkout_cart_ids'] ?? null)
            ? array_values(array_filter(array_map('intval', $_SESSION['checkout_cart_ids'])))
            : [];
        if ($selectedIds !== []) {
            $items = $this->cartModel->getCartByIdsForUser($userId, $selectedIds);
            if ($items !== []) {
                return $items;
            }
        }

        return $this->cartModel->getCartByUser($userId);
    }
}
