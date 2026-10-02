<?php

require_once APP_PATH . '/models/AdminUserModel.php';

class AdminUserController extends Controller
{
    private const PER_PAGE = 10;

    private AdminUserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->userModel = new AdminUserModel();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['Active', 'Closed'], true)
            ? (string) $_GET['status']
            : 'all';
        $page = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $page = $page === false ? 1 : (int) $page;
        $total = $this->userModel->countUsers($keyword, $status);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        $this->loadView('admin/user/index', [
            'pageTitle' => 'Quản lý người dùng',
            'users' => $this->userModel->getUsers(
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

    public function edit($id = null): void
    {
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false) {
            $this->setFlash('error', 'Mã tài khoản không hợp lệ.');
            $this->redirect('admin-user/index');
            return;
        }

        $user = $this->userModel->findByIdFull((int) $userId);
        if (!$user) {
            $this->setFlash('error', 'Không tìm thấy tài khoản người dùng.');
            $this->redirect('admin-user/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
                $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
                $this->redirect('admin-user/edit/' . $userId);
                return;
            }

            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email    = trim((string) ($_POST['email'] ?? ''));
            $phone    = trim((string) ($_POST['phone'] ?? ''));
            $gender   = trim((string) ($_POST['gender'] ?? ''));
            $status   = in_array($_POST['status_user'] ?? '', ['Active', 'Closed'], true) ? $_POST['status_user'] : 'Active';
            $password = (string) ($_POST['password'] ?? '');

            $updateData = [
                'full_name'   => $fullName,
                'email'       => $email,
                'phone'       => $phone,
                'gender'      => $gender,
                'status_user' => $status,
            ];

            if ($password !== '') {
                $updateData['password'] = $password;
            }

            if ($this->userModel->updateUser((int) $userId, $updateData)) {
                $this->setFlash('success', 'Đã cập nhật thông tin tài khoản ' . $user['username'] . ' thành công.');
                $this->redirect('admin-user/index');
                return;
            } else {
                $this->setFlash('error', 'Không có thay đổi nào hoặc không thể cập nhật.');
            }
        }

        $this->loadView('admin/user/edit', [
            'pageTitle' => 'Chỉnh sửa tài khoản – ' . $user['username'],
            'user'      => $user,
            'flash'     => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser'  => $this->getAdminUser(),
        ], false);
    }

    public function delete($id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
            $this->redirect('admin-user/index');
            return;
        }

        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $userId === false ? false : $this->userModel->findById((int) $userId);
        if (!$user) {
            $this->setFlash('error', 'Không tìm thấy tài khoản người dùng.');
        } elseif ($this->userModel->deleteUser((int) $userId)) {
            $this->setFlash('success', 'Đã xóa tài khoản ' . $user['username'] . ' thành công.');
        } else {
            $this->setFlash('error', 'Không thể xóa tài khoản này.');
        }
        $this->redirect('admin-user/index');
    }

    public function toggle($id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
            $this->redirect('admin-user/index');
            return;
        }
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $userId === false ? false : $this->userModel->findById((int) $userId);
        if (!$user) {
            $this->setFlash('error', 'Không tìm thấy tài khoản khách hàng.');
        } elseif ($this->userModel->toggleStatus((int) $userId)) {
            $action = $user['status_user'] === 'Active' ? 'khóa' : 'mở khóa';
            $this->setFlash('success', 'Đã ' . $action . ' tài khoản ' . $user['username'] . '.');
        } else {
            $this->setFlash('error', 'Không thể cập nhật trạng thái tài khoản.');
        }
        $this->redirect('admin-user/index');
    }
}
