<?php
/**
 * ============================================================
 *  SPORT SHOP – Home Controller
 *  File: app/controllers/HomeController.php
 *  URL:  /  |  /home/index
 * ============================================================
 */

require_once APP_PATH . '/models/ProductModel.php';
require_once APP_PATH . '/models/CartModel.php';

class HomeController extends Controller
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
     * GET /  →  Trang chủ
     */
    public function index(): void
    {
        $newProducts  = $this->productModel->getNewProducts(8);
        $hotProducts  = $this->productModel->getHotProducts(8);
        $saleProducts = $this->productModel->getSaleProducts(6);
        $brands       = $this->productModel->getAllBrands();
        $cartCount    = $this->isLoggedIn()
                        ? $this->cartModel->countItems($_SESSION['user_id'])
                        : 0;

        $this->loadView('home/index', [
            'pageTitle'    => 'TrendStyle Fashion – Thời Trang Phong Cách & Dẫn Đầu Xu Hướng',
            'newProducts'  => $newProducts,
            'hotProducts'  => $hotProducts,
            'saleProducts' => $saleProducts,
            'brands'       => $brands,
            'cartCount'    => $cartCount,
            'authUser'     => $this->getAuthUser(),
        ]);
    }
}
