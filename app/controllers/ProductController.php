<?php
/**
 * ============================================================
 *  SPORT SHOP – Product Controller
 *  File: app/controllers/ProductController.php
 *  URL:  /product/index  |  /product/detail/{id}  |  /product/search
 * ============================================================
 */

require_once APP_PATH . '/models/ProductModel.php';
require_once APP_PATH . '/models/CartModel.php';

class ProductController extends Controller
{
    private ProductModel $productModel;
    private CartModel    $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
        $this->cartModel    = new CartModel();
    }

    /**
     * GET /product/index  →  Danh sách tất cả sản phẩm
     */
    public function index(): void
    {
        $keyword    = trim($this->get('q'));
        $brandId    = (int) $this->get('brand');
        $categoryId = (int) $this->get('category');
        $saleValue  = strtolower($this->get('sale'));
        $saleOnly   = in_array($saleValue, ['1', 'true', 'yes', 'on'], true);
        $products   = $this->productModel->filter(
            $keyword,
            $brandId,
            $categoryId,
            $saleOnly
        );

        $brands     = $this->productModel->getAllBrands();
        $categories = $this->productModel->getAllCategories();
        $cartCount  = $this->isLoggedIn()
                      ? $this->cartModel->countItems($_SESSION['user_id'])
                      : 0;

        $this->loadView('product/index', [
            'pageTitle'  => 'Sản Phẩm – TrendStyle Fashion',
            'products'   => $products,
            'brands'     => $brands,
            'categories' => $categories,
            'keyword'    => $keyword,
            'brandId'    => $brandId,
            'categoryId' => $categoryId,
            'saleOnly'   => $saleOnly,
            'cartCount'  => $cartCount,
            'authUser'   => $this->getAuthUser(),
        ]);
    }

    /**
     * GET /product/detail/{id}  →  Chi tiết sản phẩm
     */
    public function detail(int $id = 0): void
    {
        if (!$id) {
            $this->redirect('product/index');
            return;
        }

        $product = $this->productModel->findById($id);

        if (!$product) {
            http_response_code(404);
            echo '<h3 style="text-align:center;padding:60px;color:#64748b">Sản phẩm không tồn tại!</h3>';
            return;
        }

        $cartCount = $this->isLoggedIn()
                     ? $this->cartModel->countItems($_SESSION['user_id'])
                     : 0;

        // Lấy danh sách ảnh (findById đã gắn vào $product['images'])
        $images = $product['images'] ?? [];

        // Tạo CSRF token cho form thêm giỏ hàng
        $csrfToken = method_exists($this, 'generateCsrfToken')
            ? (string) $this->generateCsrfToken()
            : '';

        $this->loadView('product/detail', [
            'pageTitle'  => $product['name'] . ' – TrendStyle Fashion',
            'product'    => $product,
            'images'     => $images,
            'cartCount'  => $cartCount,
            'authUser'   => $this->getAuthUser(),
            'csrfToken'  => $csrfToken,
        ]);
    }

    /**
     * POST /product/addToCart  →  Thêm vào giỏ hàng (AJAX)
     */
    public function addToCart(): void
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập!'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ!'], 405);
            return;
        }

        if (!$this->verifyRequestCsrf()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ!'], 403);
            return;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty       = max(1, (int) ($_POST['quantity'] ?? 1));
        $size      = trim((string) ($_POST['size'] ?? '40'));

        if (!$productId) {
            $this->jsonResponse(['success' => false, 'message' => 'Sản phẩm không hợp lệ!'], 400);
            return;
        }

        $product = $this->productModel->findPurchasableById($productId);
        if (!$product) {
            $this->jsonResponse(['success' => false, 'message' => 'Sản phẩm không còn được bán!'], 404);
            return;
        }

        $currentQty = $this->cartModel->getProductQuantity(
            (int) $_SESSION['user_id'],
            $productId
        );
        if ($qty > (int) $product['quantity'] - $currentQty) {
            $this->jsonResponse(['success' => false, 'message' => 'Số lượng vượt quá tồn kho!'], 409);
            return;
        }

        try {
            $result = $this->cartModel->addToCart($_SESSION['user_id'], $productId, $qty, $size);
            $count  = $this->cartModel->countItems($_SESSION['user_id']);

            $this->jsonResponse([
                'success'   => $result,
                'message'   => $result ? 'Đã thêm vào giỏ hàng (Size: ' . htmlspecialchars($size) . ')!' : 'Sản phẩm không còn đủ tồn kho!',
                'cartCount' => $count,
            ], $result ? 200 : 409);
        } catch (\Throwable $e) {
            // Nếu lỗi do user_id trong session không còn tồn tại trong DB
            unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role']);
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phiên đăng nhập không hợp lệ, vui lòng đăng nhập lại!'
            ], 401);
        }
    }

    private function verifyRequestCsrf(): bool
    {
        // Nếu controller không có CSRF method → bỏ qua kiểm tra
        if (!method_exists($this, 'verifyCsrfToken')) {
            return true;
        }

        $token = (string) ($_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '');

        return $this->verifyCsrfToken($token) !== false;
    }
}
