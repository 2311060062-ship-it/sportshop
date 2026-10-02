<?php
/**
 * ============================================================
 *  SPORT SHOP – Base Controller
 *  File: core/Controller.php
 * ============================================================
 */

class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Load một view file, truyền dữ liệu vào view
     *
     * @param string $view   Đường dẫn view: 'home/index', 'auth/login'
     * @param array  $data   Dữ liệu truyền vào view
     * @param bool   $layout Có dùng layout header/footer không
     */
    protected function loadView(string $view, array $data = [], bool $layout = true): void
    {
        // Giải nén mảng $data thành các biến
        extract($data);
        $csrfToken = $csrfToken ?? $this->generateCsrfToken();

        $viewFile = APP_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("❌ View không tìm thấy: <strong>{$view}</strong>");
        }

        if ($layout) {
            require_once APP_PATH . '/views/layouts/header.php';
        }

        require_once $viewFile;

        if ($layout) {
            require_once APP_PATH . '/views/layouts/footer.php';
        }
    }

    /**
     * Load view không dùng layout (dành cho trang auth)
     */
    protected function loadViewOnly(string $view, array $data = []): void
    {
        $this->loadView($view, $data, false);
    }

    /**
     * Redirect đến URL khác
     */
    protected function redirect(string $path = ''): void
    {
        $url = BASE_URL . '/' . ltrim($path, '/');
        header("Location: {$url}");
        exit;
    }

    /**
     * Kiểm tra đã đăng nhập chưa
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Yêu cầu đăng nhập, nếu chưa thì redirect
     */
    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('auth/login');
        }
    }

    /**
     * Ghi phiên sau khi xác thực. Admin được cấp cả phiên cửa hàng và phiên quản trị.
     */
    protected function startUserSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['roles'];
        $_SESSION['avatar'] = $user['avatar'];

        if (($user['roles'] ?? '') === 'ADMIN') {
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_full_name'] = $user['full_name'];
            $_SESSION['admin_role'] = 'ADMIN';
            $_SESSION['admin_avatar'] = $user['avatar'];
        }
    }

    /**
     * Admin vào trang quản trị, khách hàng về trang chủ.
     */
    protected function redirectAfterLogin(): void
    {
        if (($_SESSION['admin_role'] ?? '') === 'ADMIN' || ($_SESSION['user_role'] ?? '') === 'ADMIN') {
            $this->redirect('admin-dashboard/index');
        }

        $this->redirect();
    }

    /**
     * Xóa cả phiên khách hàng và phiên quản trị.
     */
    protected function clearAuthSession(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['full_name'],
            $_SESSION['user_role'],
            $_SESSION['avatar'],
            $_SESSION['admin_user_id'],
            $_SESSION['admin_username'],
            $_SESSION['admin_full_name'],
            $_SESSION['admin_role'],
            $_SESSION['admin_avatar'],
            $_SESSION['pending_verify_user_id'],
            $_SESSION['otp_attempts'],
            $_SESSION['otp_last_sent'],
            $_SESSION['after_verify_redirect']
        );
    }

    /**
     * Yêu cầu quyền ADMIN. Khách chưa đăng nhập hoặc không phải admin
     * không vào được trang quản trị bằng URL trực tiếp.
     */
    protected function requireAdmin(): void
    {
        if (empty($_SESSION['admin_user_id']) || ($_SESSION['admin_role'] ?? '') !== 'ADMIN') {
            $this->redirect('admin-auth/login');
        }
    }

    /**
     * Lấy thông tin user đang đăng nhập từ session
     */
    protected function getAuthUser(): array
    {
        return [
            'id'       => $_SESSION['user_id']   ?? null,
            'username' => $_SESSION['username']   ?? '',
            'fullname' => $_SESSION['full_name']  ?? '',
            'role'     => $_SESSION['user_role']  ?? 'USER',
            'avatar'   => $_SESSION['avatar']     ?? null,
        ];
    }

    /**
     * Thông tin phiên quản trị. Chỉ tài khoản ADMIN mới được gán các khóa này.
     */
    protected function getAdminUser(): array
    {
        return [
            'id' => $_SESSION['admin_user_id'] ?? null,
            'username' => $_SESSION['admin_username'] ?? '',
            'fullname' => $_SESSION['admin_full_name'] ?? '',
            'role' => $_SESSION['admin_role'] ?? '',
            'avatar' => $_SESSION['admin_avatar'] ?? null,
        ];
    }

    /**
     * Tạo/lấy CSRF token dùng chung cho form và AJAX.
     */
    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf_token'];
    }

    /**
     * Xác minh CSRF token bằng so sánh constant-time.
     */
    protected function verifyCsrfToken(string $token): bool
    {
        $expected = (string) ($_SESSION['_csrf_token'] ?? '');

        return $token !== ''
            && $expected !== ''
            && hash_equals($expected, $token);
    }

    /**
     * Trả về JSON response (dùng cho AJAX)
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Lấy input từ POST (đã sanitize)
     */
    protected function post(string $key, string $default = ''): string
    {
        return htmlspecialchars(trim($_POST[$key] ?? $default));
    }

    /**
     * Lấy input từ GET (đã sanitize)
     */
    protected function get(string $key, string $default = ''): string
    {
        return htmlspecialchars(trim($_GET[$key] ?? $default));
    }

    /**
     * Đặt flash message (thông báo 1 lần)
     */
    protected function setFlash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }

    /**
     * Lấy và xóa flash message
     */
    protected function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
