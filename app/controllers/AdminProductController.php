<?php

require_once APP_PATH . '/models/AdminProductModel.php';
require_once APP_PATH . '/services/FileUploadService.php';

class AdminProductController extends Controller
{
    private const PER_PAGE = 10;

    private AdminProductModel $productModel;
    private FileUploadService $uploadService;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->productModel = new AdminProductModel();
        $this->uploadService = new FileUploadService();
    }

    public function index(): void
    {
        if (!$this->requireRequestMethod('GET')) {
            return;
        }

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? 'all');
        if (!in_array($status, ['Active', 'Closed', 'all'], true)) {
            $status = 'all';
        }

        $requestedPage = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $page = $requestedPage === false ? 1 : (int) $requestedPage;
        $total = $this->productModel->countProducts($keyword, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        $this->loadView('admin/product/index', [
            'pageTitle' => 'Quản lý sản phẩm',
            'products' => $this->productModel->getProducts(
                $keyword,
                $status,
                self::PER_PAGE,
                ($page - 1) * self::PER_PAGE
            ),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    public function create(): void
    {
        if (!$this->requireRequestMethod('GET')) {
            return;
        }

        $this->renderForm(false);
    }

    public function edit($id): void
    {
        if (!$this->requireRequestMethod('GET')) {
            return;
        }

        $productId = $this->validId($id);
        $product = $productId > 0 ? $this->productModel->findById($productId) : false;
        if (!$product) {
            $this->setFlash('error', 'Không tìm thấy sản phẩm.');
            $this->redirect('admin-product/index');
        }

        $this->renderForm(true, $product);
    }

    public function store(): void
    {
        if (!$this->requireRequestMethod('POST')) {
            return;
        }
        if (!$this->verifyPostCsrf('admin-product/create')) {
            return;
        }

        [$data, $old, $errors] = $this->validateProductInput();
        if ($errors) {
            $this->renderForm(false, [], $errors, $old);
            return;
        }
        if (!$this->resolveNewCategory($data, $old, false)) {
            return;
        }

        $uploadedImage = null;
        try {
            $uploadedImage = $this->uploadService->upload('image');
        } catch (RuntimeException $exception) {
            $this->renderForm(false, [], ['image' => $exception->getMessage()], $old);
            return;
        }

        try {
            $this->productModel->createWithImage($data, $uploadedImage);
        } catch (Throwable $exception) {
            $this->uploadService->remove($uploadedImage);
            $this->renderForm(
                false,
                [],
                ['general' => 'Không thể tạo sản phẩm. Vui lòng thử lại.'],
                $old
            );
            return;
        }

        $this->setFlash('success', 'Tạo sản phẩm thành công.');
        $this->redirect('admin-product/index');
    }

    public function update($id): void
    {
        if (!$this->requireRequestMethod('POST')) {
            return;
        }

        $productId = $this->validId($id);
        if (!$this->verifyPostCsrf($productId > 0 ? 'admin-product/edit/' . $productId : 'admin-product/index')) {
            return;
        }

        $product = $productId > 0 ? $this->productModel->findById($productId) : false;
        if (!$product) {
            $this->setFlash('error', 'Không tìm thấy sản phẩm.');
            $this->redirect('admin-product/index');
        }

        [$data, $old, $errors] = $this->validateProductInput();
        if ($errors) {
            $this->renderForm(true, $product, $errors, $old);
            return;
        }
        if (!$this->resolveNewCategory($data, $old, true, $product)) {
            return;
        }

        $uploadedImage = null;
        try {
            $uploadedImage = $this->uploadService->upload('image');
        } catch (RuntimeException $exception) {
            $this->renderForm(true, $product, ['image' => $exception->getMessage()], $old);
            return;
        }

        try {
            $this->productModel->updateWithImage($productId, $data, $uploadedImage);
        } catch (Throwable $exception) {
            $this->uploadService->remove($uploadedImage);
            $this->renderForm(
                true,
                $product,
                ['general' => 'Không thể cập nhật sản phẩm. Vui lòng thử lại.'],
                $old
            );
            return;
        }

        $this->setFlash('success', 'Cập nhật sản phẩm thành công.');
        $this->redirect('admin-product/index');
    }

    public function toggle($id): void
    {
        if (!$this->requireRequestMethod('POST')) {
            return;
        }
        if (!$this->verifyPostCsrf('admin-product/index')) {
            return;
        }

        $productId = $this->validId($id);
        if ($productId < 1 || !$this->productModel->toggleStatus($productId)) {
            $this->setFlash('error', 'Không tìm thấy sản phẩm cần đổi trạng thái.');
        } else {
            $this->setFlash('success', 'Đã cập nhật trạng thái sản phẩm.');
        }

        $this->redirect('admin-product/index');
    }

    public function delete($id): void
    {
        if (!$this->requireRequestMethod('POST')) {
            return;
        }
        if (!$this->verifyPostCsrf('admin-product/index')) {
            return;
        }

        $productId = $this->validId($id);
        if ($productId < 1 || !$this->productModel->softDelete($productId)) {
            $this->setFlash('error', 'Không tìm thấy sản phẩm cần đóng.');
        } else {
            $this->setFlash('success', 'Đã đóng sản phẩm. Dữ liệu đơn hàng và ảnh được giữ nguyên.');
        }

        $this->redirect('admin-product/index');
    }

    private function renderForm(
        bool $isEdit,
        array $product = [],
        array $errors = [],
        array $old = []
    ): void {
        $this->loadView('admin/product/form', [
            'pageTitle' => $isEdit ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm',
            'product' => $product,
            'brands' => $this->productModel->getBrands(),
            'categories' => $this->productModel->getCategories(),
            'errors' => $errors,
            'old' => $old,
            'csrfToken' => $this->generateCsrfToken(),
            'isEdit' => $isEdit,
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    private function validateProductInput(): array
    {
        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'price' => trim((string) ($_POST['price'] ?? '')),
            'discount' => trim((string) ($_POST['discount'] ?? '')),
            'quantity' => trim((string) ($_POST['quantity'] ?? '')),
            'color' => trim((string) ($_POST['color'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'status_product' => trim((string) ($_POST['status_product'] ?? ($_POST['status'] ?? ''))),
            'brand_id' => trim((string) ($_POST['brand_id'] ?? '')),
            'category_id' => trim((string) ($_POST['category_id'] ?? '')),
            'sizes' => trim((string) ($_POST['sizes'] ?? '38,39,40,41,42,43')),
            'new_category_name' => trim((string) ($_POST['new_category_name'] ?? '')),
        ];
        $errors = [];

        if ($old['name'] === '') {
            $errors['name'] = 'Tên sản phẩm là bắt buộc.';
        } elseif ($this->textLength($old['name']) > 255) {
            $errors['name'] = 'Tên sản phẩm không được vượt quá 255 ký tự.';
        }

        $price = $this->parseUnsignedInteger($old['price']);
        if ($price === null || $price <= 0) {
            $errors['price'] = 'Giá sản phẩm phải lớn hơn 0.';
        }

        $discount = $this->parseUnsignedInteger($old['discount']);
        if ($discount === null || $discount > 100) {
            $errors['discount'] = 'Giảm giá phải nằm trong khoảng 0 đến 100.';
        }

        $quantity = $this->parseUnsignedInteger($old['quantity']);
        if ($quantity === null) {
            $errors['quantity'] = 'Số lượng phải là số nguyên không âm.';
        }

        if ($this->textLength($old['color']) > 255) {
            $errors['color'] = 'Màu sắc không được vượt quá 255 ký tự.';
        }

        if (!in_array($old['status_product'], ['Active', 'Closed'], true)) {
            $errors['status_product'] = 'Trạng thái sản phẩm không hợp lệ.';
        }

        $brandId = $this->parsePositiveInteger($old['brand_id']);
        if ($brandId === null || !$this->productModel->brandExists($brandId)) {
            $errors['brand_id'] = 'Thương hiệu không hợp lệ.';
        }

        $categoryId = null;
        if ($old['category_id'] === '__new__') {
            if ($old['new_category_name'] === '') {
                $errors['new_category_name'] = 'Vui lòng nhập tên danh mục mới.';
            } elseif ($this->textLength($old['new_category_name']) > 255) {
                $errors['new_category_name'] = 'Tên danh mục không được vượt quá 255 ký tự.';
            }
        } else {
            $categoryId = $this->parsePositiveInteger($old['category_id']);
            if ($categoryId === null || !$this->productModel->categoryExists($categoryId)) {
                $errors['category_id'] = 'Danh mục không hợp lệ.';
            }
        }

        $data = [
            'name' => $old['name'],
            'price' => $price ?? 0,
            'discount' => $discount ?? 0,
            'quantity' => $quantity ?? 0,
            'color' => $old['color'],
            'sizes' => !empty($old['sizes']) ? $old['sizes'] : '38,39,40,41,42,43',
            'description' => $old['description'],
            'status_product' => $old['status_product'],
            'brand_id' => $brandId ?? 0,
            'category_id' => $categoryId ?? 0,
        ];

        return [$data, $old, $errors];
    }

    private function resolveNewCategory(
        array &$data,
        array $old,
        bool $isEdit,
        array $product = []
    ): bool {
        if ($old['category_id'] !== '__new__') {
            return true;
        }
        try {
            $data['category_id'] = $this->productModel->findOrCreateCategory(
                $old['new_category_name']
            );
            return true;
        } catch (Throwable $exception) {
            $this->renderForm(
                $isEdit,
                $product,
                ['new_category_name' => 'Không thể tạo danh mục mới. Vui lòng thử lại.'],
                $old
            );
            return false;
        }
    }

    private function verifyPostCsrf(string $redirectPath): bool
    {
        if ($this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            return true;
        }

        $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
        $this->redirect($redirectPath);
        return false;
    }

    private function requireRequestMethod(string $method): bool
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === $method) {
            return true;
        }

        http_response_code(405);
        header('Allow: ' . $method);
        echo 'Method Not Allowed';
        return false;
    }

    private function validId($value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? 0 : (int) $id;
    }

    private function parseUnsignedInteger(string $value): ?int
    {
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        return $number === false ? null : (int) $number;
    }

    private function parsePositiveInteger(string $value): ?int
    {
        $number = $this->parseUnsignedInteger($value);
        return $number !== null && $number > 0 ? $number : null;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
