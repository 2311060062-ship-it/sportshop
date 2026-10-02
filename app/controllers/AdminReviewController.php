<?php

require_once APP_PATH . '/models/AdminReviewModel.php';

class AdminReviewController extends Controller
{
    private const PER_PAGE = 10;

    private AdminReviewModel $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->reviewModel = new AdminReviewModel();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $rating = (int) ($_GET['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            $rating = 0;
        }
        $page = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $page = $page === false ? 1 : (int) $page;
        $total = $this->reviewModel->countReviews($keyword, $rating);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);

        $this->loadView('admin/review/index', [
            'pageTitle' => 'Quản lý đánh giá',
            'reviews' => $this->reviewModel->getReviews(
                $keyword,
                $rating,
                self::PER_PAGE,
                ($page - 1) * self::PER_PAGE
            ),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'rating' => $rating,
            'flash' => $this->getFlash(),
            'csrfToken' => $this->generateCsrfToken(),
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    public function reply($id): void
    {
        $reviewId = $this->requireValidPost($id);
        $message = trim((string) ($_POST['admin_reply'] ?? ''));

        if ($message === '') {
            $this->setFlash('error', 'Vui lòng nhập nội dung phản hồi.');
        } elseif ($this->textLength($message) > 2000) {
            $this->setFlash('error', 'Phản hồi không được vượt quá 2.000 ký tự.');
        } elseif (!$this->reviewModel->findById($reviewId)) {
            $this->setFlash('error', 'Không tìm thấy đánh giá cần phản hồi.');
        } elseif ($this->reviewModel->reply($reviewId, $message)) {
            $this->setFlash('success', 'Đã gửi phản hồi cho khách hàng.');
        } else {
            $this->setFlash('error', 'Không thể lưu phản hồi.');
        }

        $this->redirect('admin-review/index');
    }

    public function delete($id): void
    {
        $reviewId = $this->requireValidPost($id);

        if (!$this->reviewModel->findById($reviewId)) {
            $this->setFlash('error', 'Đánh giá không tồn tại.');
        } elseif ($this->reviewModel->delete($reviewId)) {
            $this->setFlash('success', 'Đã xóa đánh giá.');
        } else {
            $this->setFlash('error', 'Không thể xóa đánh giá.');
        }

        $this->redirect('admin-review/index');
    }

    private function requireValidPost($id): int
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!$this->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->setFlash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
            $this->redirect('admin-review/index');
        }

        $reviewId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($reviewId === false) {
            $this->setFlash('error', 'Mã đánh giá không hợp lệ.');
            $this->redirect('admin-review/index');
        }

        return (int) $reviewId;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
