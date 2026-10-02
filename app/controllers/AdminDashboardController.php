<?php

require_once APP_PATH . '/models/AdminDashboardModel.php';

class AdminDashboardController extends Controller
{
    private AdminDashboardModel $dashboardModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->dashboardModel = new AdminDashboardModel();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $this->loadView('admin/dashboard/index', [
            'pageTitle' => 'Trang quản trị',
            'summary' => $this->dashboardModel->summary(),
            'recentOrders' => $this->dashboardModel->recentOrders(),
            'lowStockProducts' => $this->dashboardModel->lowStockProducts(),
            'recentReviews' => $this->dashboardModel->recentReviews(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }
}
