<?php

require_once APP_PATH . '/models/UserModel.php';

class AdminAuthController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function login(): void
    {
        if (!empty($_SESSION['admin_user_id']) && ($_SESSION['admin_role'] ?? '') === 'ADMIN') {
            $this->redirect('admin-dashboard/index');
        }

        $errors = [];
        $old = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
                http_response_code(403);
                $errors['general'] = 'Phiên đăng nhập đã hết hạn. Vui lòng thử lại.';
            } else {
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $old['username'] = $username;

                if ($username === '' || $password === '') {
                    $errors['general'] = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu quản trị.';
                } else {
                    $admin = $this->userModel->login($username, $password);
                    if (!$admin || ($admin['roles'] ?? '') !== 'ADMIN') {
                        $errors['general'] = 'Thông tin quản trị không chính xác hoặc tài khoản không có quyền ADMIN.';
                    } else {
                        $this->startUserSession($admin);
                        $this->redirect('admin-dashboard/index');
                    }
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $this->loadViewOnly('admin/auth/login', [
            'errors' => $errors,
            'old' => $old,
            'csrfToken' => $this->generateCsrfToken(),
        ]);
    }

    public function logout(): void
    {
        $this->clearAuthSession();
        session_regenerate_id(true);
        $this->redirect('admin-auth/login');
    }
}
