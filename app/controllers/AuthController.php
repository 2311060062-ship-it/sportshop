<?php
/**
 * ============================================================
 *  SPORT SHOP – Auth Controller
 *  File: app/controllers/AuthController.php
 *  URL:  /auth/login  |  /auth/register  |  /auth/logout
 * ============================================================
 */

require_once APP_PATH . '/models/UserModel.php';
require_once APP_PATH . '/services/SmtpMailer.php';

class AuthController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    // ────────────────────────────────────────────────────────
    //  ĐĂNG NHẬP
    // ────────────────────────────────────────────────────────

    /**
     * GET  /auth/login  → Hiển thị form đăng nhập
     * POST /auth/login  → Xử lý đăng nhập
     */
    public function login(): void
    {
        // Nếu đã đăng nhập → về đúng khu vực theo vai trò
        if ($this->isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }

        $flash = $this->getFlash();
        $this->loadViewOnly('auth/login', ['flash' => $flash]);
    }

    private function handleLogin(): void
    {
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            $this->loadViewOnly('auth/login', [
                'errors' => ['general' => 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại!'],
            ]);
            return;
        }

        $username = $this->post('username');
        $password = $_POST['password'] ?? ''; // Không escape password

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Vui lòng nhập tên tài khoản!';
        }
        if (empty($password)) {
            $errors['password'] = 'Vui lòng nhập mật khẩu!';
        }

        if (!empty($errors)) {
            $this->loadViewOnly('auth/login', [
                'errors'   => $errors,
                'old'      => ['username' => $username],
            ]);
            return;
        }

        $user = $this->userModel->login($username, $password);

        if (!$user) {
            $this->loadViewOnly('auth/login', [
                'errors'   => ['general' => 'Tài khoản hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa!'],
                'old'      => ['username' => $username],
            ]);
            return;
        }

        $this->startUserSession($user);
        $this->redirectAfterLogin();
    }

    private function pendingUser(): array|false
    {
        $userId = (int) ($_SESSION['pending_verify_user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $user = $this->userModel->verificationState($userId);
        if (!$user || ($user['status_user'] ?? '') === 'Closed') {
            unset($_SESSION['pending_verify_user_id']);
            return false;
        }

        if (!empty($user['email_verified_at'])) {
            unset($_SESSION['pending_verify_user_id']);
            return false;
        }

        return $user;
    }

    private function sendVerificationCode(int $userId): bool
    {
        $user = $this->userModel->verificationState($userId);
        if (!$user || !filter_var((string) $user['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->userModel->saveEmailOtp($userId, $code);
        $_SESSION['otp_last_sent'] = time();

        $name = trim((string) ($user['full_name'] ?: $user['username']));
        $text = "Xin chào {$name},\r\n\r\n"
            . "Mã xác thực TrendStyle Fashion của bạn là: {$code}\r\n"
            . "Mã có hiệu lực trong 10 phút. Không chia sẻ mã này với người khác.\r\n";

        try {
            (new SmtpMailer())->send((string) $user['email'], 'Mã xác thực 6 số - TrendStyle', $text);
            unset($_SESSION['otp_mail_error']);
            return true;
        } catch (Throwable $e) {
            $_SESSION['otp_mail_error'] = 'Gmail từ chối tài khoản gửi thư, nên mã chưa được gửi. Hãy cập nhật mật khẩu ứng dụng rồi bấm gửi lại.';
            return false;
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            return $email;
        }

        $name = $parts[0];
        $visible = substr($name, 0, 1);

        return $visible . str_repeat('*', max(2, strlen($name) - 1)) . '@' . $parts[1];
    }

    // ────────────────────────────────────────────────────────
    //  ĐĂNG KÝ
    // ────────────────────────────────────────────────────────

    /**
     * GET  /auth/register  → Hiển thị form đăng ký
     * POST /auth/register  → Xử lý đăng ký
     */
    public function register(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
            return;
        }

        $this->loadViewOnly('auth/register');
    }

    private function handleRegister(): void
    {
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            $this->loadViewOnly('auth/register', [
                'errors' => ['general' => 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại!'],
            ]);
            return;
        }

        $username        = $this->post('username');
        $email           = $this->post('email');
        $fullName        = $this->post('full_name');
        $phone           = $this->post('phone');
        $password        = $_POST['password']         ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validate
        if (strlen($username) < 4) {
            $errors['username'] = 'Tên tài khoản phải ít nhất 4 ký tự!';
        } elseif ($this->userModel->usernameExists($username)) {
            $errors['username'] = 'Tên tài khoản đã tồn tại!';
        }

        if ($email === '') {
            $errors['email'] = 'Vui lòng nhập email!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ!';
        } elseif ($this->userModel->emailExists($email)) {
            $errors['email'] = 'Email đã được đăng ký!';
        }

        if (strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu phải ít nhất 6 ký tự!';
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Mật khẩu nhập lại không khớp!';
        }

        if (!empty($errors)) {
            $this->loadViewOnly('auth/register', [
                'errors' => $errors,
                'old'    => compact('username', 'email', 'fullName', 'phone'),
            ]);
            return;
        }

        // Tạo tài khoản
        $userId = $this->userModel->register([
            'username'  => $username,
            'password'  => $password,
            'email'     => $email,
            'full_name' => $fullName,
            'phone'     => $phone,
        ]);

        $created = $this->userModel->verificationState($userId);
        if (!$created) {
            $this->setFlash('error', 'Tài khoản đã tạo nhưng chưa đăng nhập được. Hãy đăng nhập lại.');
            $this->redirect('auth/login');
        }

        $this->startUserSession($created);
        $this->setFlash('success', 'Đăng ký thành công. Bạn có thể xem sản phẩm và thêm vào giỏ. Email sẽ được xác minh khi thanh toán.');
        $this->redirect();
    }

    public function requireVerify(): void
    {
        $this->requireLogin();
        $user = $this->userModel->verificationState((int) $_SESSION['user_id']);
        if (!$user || ($user['status_user'] ?? '') === 'Closed') {
            $this->clearAuthSession();
            $this->redirect('auth/login');
        }
        if (!empty($user['email_verified_at'])) {
            $this->redirectAfterVerification();
        }

        $_SESSION['pending_verify_user_id'] = (int) $user['id'];
        $_SESSION['otp_attempts'] = (int) ($_SESSION['otp_attempts'] ?? 0);
        if ($this->userModel->activeEmailOtpHash((int) $user['id']) === null) {
            $sent = $this->sendVerificationCode((int) $user['id']);
            $this->setFlash(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Cần xác minh email trước khi thanh toán. Mã 6 số đã được gửi tới email của bạn.'
                    : ($_SESSION['otp_mail_error'] ?? 'Chưa gửi được mã xác minh. Hãy bấm gửi lại.')
            );
        } else {
            $this->setFlash('error', 'Cần xác minh email trước khi thanh toán. Nhập mã 6 số trong thư mới nhất.');
        }
        $this->redirect('auth/verify');
    }

    public function verify(): void
    {
        $user = $this->pendingUser();
        if (!$user) {
            $this->redirect('auth/login');
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
                http_response_code(403);
                $errors['general'] = 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại!';
            } else {
                $code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? '')) ?? '';
                $hash = $this->userModel->activeEmailOtpHash((int) $user['id']);
                $attempts = (int) ($_SESSION['otp_attempts'] ?? 0);
                $matched = $hash !== null && strlen($code) === 6 && password_verify($code, $hash);

                if ($matched) {
                    $_SESSION['otp_attempts'] = 0;
                } elseif ($hash === null) {
                    $errors['code'] = 'Mã đã hết hạn hoặc chưa được gửi. Hãy gửi lại mã.';
                } elseif ($attempts >= 4) {
                    $this->userModel->clearEmailOtp((int) $user['id']);
                    $_SESSION['otp_attempts'] = 0;
                    $errors['code'] = 'Bạn đã nhập sai quá 5 lần. Hãy gửi lại mã mới.';
                } else {
                    $_SESSION['otp_attempts'] = $attempts + 1;
                    $errors['code'] = 'Mã xác thực không đúng. Hãy dùng mã trong thư mới nhất.';
                }

                if ($matched) {
                    $this->userModel->markEmailVerified((int) $user['id']);
                    $verified = $this->userModel->verificationState((int) $user['id']);
                    unset($_SESSION['pending_verify_user_id'], $_SESSION['otp_attempts'], $_SESSION['otp_last_sent']);
                    $this->startUserSession($verified);
                    $this->setFlash('success', 'Email đã được xác minh. Bạn có thể tiếp tục thanh toán.');
                    $this->redirectAfterVerification();
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $this->loadViewOnly('auth/verify', [
            'errors' => $errors,
            'flash' => $this->getFlash(),
            'emailMask' => $this->maskEmail((string) $user['email']),
            'csrfToken' => $this->generateCsrfToken(),
        ]);
    }

    public function resend(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $user = $this->pendingUser();
        if (!$user) {
            $this->redirect('auth/login');
        }

        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại!');
            $this->redirect('auth/verify');
        }

        $elapsed = time() - (int) ($_SESSION['otp_last_sent'] ?? 0);
        if ($elapsed < 60) {
            $this->setFlash('error', 'Vui lòng đợi ' . (60 - $elapsed) . ' giây trước khi gửi lại mã.');
            $this->redirect('auth/verify');
        }

        $_SESSION['otp_attempts'] = 0;
        $sent = $this->sendVerificationCode((int) $user['id']);
        $this->setFlash(
            $sent ? 'success' : 'error',
            $sent ? 'Đã gửi mã 6 số mới. Mã có hiệu lực trong 10 phút.' : ($_SESSION['otp_mail_error'] ?? 'Chưa gửi được email. Thử lại sau ít phút.')
        );
        $this->redirect('auth/verify');
    }

    // ────────────────────────────────────────────────────────
    //  ĐĂNG XUẤT
    // ────────────────────────────────────────────────────────

    /**
     * GET /auth/logout
     */
    public function logout(): void
    {
        $this->clearAuthSession();
        session_regenerate_id(true);
        $this->redirect('auth/login');
    }

    private function redirectAfterVerification(): void
    {
        $path = (string) ($_SESSION['after_verify_redirect'] ?? '');
        unset($_SESSION['after_verify_redirect']);
        if (preg_match('#^(checkout/index|user/orders)(\\?[A-Za-z0-9_=&%.-]*)?$#', $path) === 1) {
            $this->redirect($path);
        }

        $this->redirectAfterLogin();
    }
}
