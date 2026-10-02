<?php
/** Contract: reviews,total,page,totalPages,keyword,rating,flash,csrfToken,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$reviews = is_array($reviews ?? null) ? $reviews : [];
$total = max(0, (int) ($total ?? count($reviews)));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$rating = min(5, max(0, (int) ($rating ?? 0)));
$pageTitle = 'Quản lý đánh giá';
$adminActive = 'reviews';

$paginationUrl = static function (int $targetPage) use ($keyword, $rating, $adminEscape): string {
    $query = array_filter([
        'q' => $keyword,
        'rating' => $rating,
        'page' => max(1, $targetPage),
    ], static fn ($value): bool => $value !== '' && $value !== 0);
    return $adminEscape(BASE_URL . '/admin-review/index?' . http_build_query($query));
};

$mediaUrl = static function (string $value): string {
    if (filter_var($value, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
    ) {
        return $value;
    }
    return BASE_URL . '/public/images/reviews/' . rawurlencode(basename($value));
};

require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow">Chăm sóc khách hàng</span>
    <h1>Đánh giá sản phẩm</h1>
    <p>Đọc phản hồi, trả lời khách hàng và xử lý nội dung không phù hợp.</p>
  </div>
</section>

<?php if (!empty($flash)):
    $flashType = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
    $flashMessage = (string) ($flash['msg'] ?? '');
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>" role="status">
    <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <span><?= $adminEscape($flashMessage) ?></span>
  </div>
<?php endif; ?>

<section class="admin-card">
  <div class="admin-card-header">
    <form class="admin-filters" method="GET" action="<?= $adminBaseUrl ?>/admin-review/index">
      <label class="admin-search" for="reviewSearch">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input id="reviewSearch" type="search" name="q" value="<?= $adminEscape($keyword) ?>"
               placeholder="Tài khoản, sản phẩm hoặc nội dung...">
      </label>
      <label class="admin-filter-select" for="ratingFilter">
        <span class="admin-sr-only">Số sao</span>
        <select id="ratingFilter" name="rating">
          <option value="0">Tất cả số sao</option>
          <?php for ($star = 5; $star >= 1; $star--): ?>
            <option value="<?= $star ?>" <?= $rating === $star ? 'selected' : '' ?>><?= $star ?> sao</option>
          <?php endfor; ?>
        </select>
      </label>
      <button class="admin-btn admin-btn-secondary" type="submit">Lọc</button>
      <?php if ($keyword !== '' || $rating > 0): ?>
        <a class="admin-clear-filter" href="<?= $adminBaseUrl ?>/admin-review/index">Xóa lọc</a>
      <?php endif; ?>
    </form>
    <span class="admin-result-count"><?= number_format($total, 0, ',', '.') ?> đánh giá</span>
  </div>

  <?php if (!$reviews): ?>
    <div class="admin-empty">
      <span><i class="fa-regular fa-comments" aria-hidden="true"></i></span>
      <h2>Chưa có đánh giá phù hợp</h2>
      <p>Đánh giá của khách hàng sẽ xuất hiện tại đây.</p>
    </div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table admin-review-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Khách hàng / Sản phẩm</th>
            <th>Số sao</th>
            <th>Nội dung</th>
            <th>Tệp đính kèm</th>
            <th>Ngày đánh giá</th>
            <th>Phản hồi</th>
            <th class="admin-table-actions-heading">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $review):
              $reviewId = (int) $review['id'];
              $stars = min(5, max(0, (int) ($review['rate'] ?? 0)));
              $attachment = trim((string) ($review['media_url'] ?? ''));
              $dateValue = strtotime((string) ($review['date'] ?? ''));
              $reply = trim((string) ($review['admin_reply'] ?? ''));
          ?>
            <tr>
              <td data-label="ID">#<?= $reviewId ?></td>
              <td data-label="Khách hàng / Sản phẩm">
                <strong class="admin-table-primary"><?= $adminEscape($review['username'] ?? 'Khách hàng') ?></strong>
                <small class="admin-table-secondary"><?= $adminEscape($review['product_name'] ?? 'Sản phẩm') ?></small>
              </td>
              <td data-label="Số sao">
                <span class="admin-stars" aria-label="<?= $stars ?> trên 5 sao">
                  <?php for ($star = 1; $star <= 5; $star++): ?>
                    <i class="<?= $star <= $stars ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                  <?php endfor; ?>
                </span>
              </td>
              <td data-label="Nội dung">
                <p class="admin-review-message"><?= $adminEscape($review['messages'] ?? '') ?></p>
              </td>
              <td data-label="Tệp đính kèm">
                <?php if ($attachment !== ''): ?>
                  <a class="admin-attachment" href="<?= $adminEscape($mediaUrl($attachment)) ?>"
                     target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-paperclip"></i> Xem tệp
                  </a>
                <?php else: ?><span class="admin-muted-text">Không có</span><?php endif; ?>
              </td>
              <td data-label="Ngày đánh giá">
                <?= $dateValue ? date('d/m/Y H:i', $dateValue) : '—' ?>
              </td>
              <td data-label="Phản hồi">
                <?php if ($reply !== ''): ?>
                  <span class="admin-replied"><i class="fa-solid fa-check"></i> Đã phản hồi</span>
                  <small title="<?= $adminEscape($reply) ?>"><?= $adminEscape($reply) ?></small>
                <?php else: ?><span class="admin-muted-text">Chưa phản hồi</span><?php endif; ?>
              </td>
              <td data-label="Hành động">
                <div class="admin-row-actions">
                  <button class="admin-icon-btn admin-review-reply" type="button" title="Phản hồi"
                          data-review-id="<?= $reviewId ?>"
                          data-review-user="<?= $adminEscape($review['username'] ?? 'Khách hàng') ?>"
                          data-review-reply="<?= $adminEscape($reply) ?>">
                    <i class="fa-solid fa-reply"></i><span class="admin-sr-only">Phản hồi</span>
                  </button>
                  <form method="POST" action="<?= $adminBaseUrl ?>/admin-review/delete/<?= $reviewId ?>"
                        data-admin-confirm="Xóa đánh giá này? Thao tác không thể hoàn tác.">
                    <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
                    <button class="admin-icon-btn admin-icon-btn-danger" type="submit" title="Xóa đánh giá">
                      <i class="fa-solid fa-trash"></i><span class="admin-sr-only">Xóa</span>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination" aria-label="Phân trang đánh giá">
      <a class="admin-page-link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $paginationUrl($page - 1) ?>">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?>
        <a class="admin-page-link <?= $number === $page ? 'is-active' : '' ?>" href="<?= $paginationUrl($number) ?>"><?= $number ?></a>
      <?php endfor; ?>
      <a class="admin-page-link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : $paginationUrl($page + 1) ?>">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </nav>
  <?php endif; ?>
</section>

<div class="admin-modal-backdrop" id="reviewReplyModal" aria-hidden="true">
  <section class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="reviewReplyTitle">
    <header class="admin-modal-header">
      <div><h2 id="reviewReplyTitle">Phản hồi đánh giá</h2><p>Trả lời: <strong id="reviewReplyUser">khách hàng</strong></p></div>
      <button type="button" class="admin-modal-close" data-review-modal-close aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
    </header>
    <form id="reviewReplyForm" method="POST">
      <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
      <label class="admin-field">
        <span>Nội dung phản hồi</span>
        <textarea id="reviewReplyMessage" name="admin_reply" maxlength="2000" rows="6"
                  placeholder="Nhập lời cảm ơn hoặc giải đáp cho khách hàng..." required></textarea>
      </label>
      <footer class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" data-review-modal-close>Hủy</button>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="fa-solid fa-paper-plane"></i> Gửi phản hồi</button>
      </footer>
    </form>
  </section>
</div>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
