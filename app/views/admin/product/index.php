<?php
/**
 * Contract: products, total, page, totalPages, keyword, status, flash,
 * csrfToken, authUser.
 */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$products = is_array($products ?? null) ? $products : [];
$total = max(0, (int) ($total ?? count($products)));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$page = min($page, $totalPages);
$keyword = trim((string) ($keyword ?? ''));
$status = in_array(($status ?? ''), ['Active', 'Closed'], true) ? (string) $status : '';
$csrfToken = (string) ($csrfToken ?? '');
$pageTitle = 'Quản trị sản phẩm';
$adminActive = 'products';
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';

$paginationUrl = static function (int $targetPage) use ($keyword, $status, $adminEscape): string {
    $query = array_filter([
        'q' => $keyword,
        'status' => $status,
        'page' => max(1, $targetPage),
    ], static fn ($value): bool => $value !== '');

    return $adminEscape(BASE_URL . '/admin-product/index?' . http_build_query($query));
};

require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow">Danh mục vận hành</span>
    <h1>Sản phẩm</h1>
    <p>Theo dõi tồn kho, trạng thái và cập nhật thông tin sản phẩm.</p>
  </div>
  <a class="admin-btn admin-btn-primary" href="<?= $adminBaseUrl ?>/admin-product/create">
    <i class="fa-solid fa-plus" aria-hidden="true"></i>
    <span>Thêm sản phẩm</span>
  </a>
</section>

<?php if (!empty($flash)):
    $flashData = is_array($flash) ? $flash : ['type' => 'success', 'message' => $flash];
    $flashType = ($flashData['type'] ?? '') === 'error' ? 'error' : 'success';
    $flashMessage = (string) ($flashData['message'] ?? $flashData['msg'] ?? '');
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>" role="status">
    <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
    <span><?= $adminEscape($flashMessage) ?></span>
  </div>
<?php endif; ?>

<section class="admin-card">
  <div class="admin-card-header">
    <form class="admin-filters" method="GET" action="<?= $adminBaseUrl ?>/admin-product/index">
      <label class="admin-search" for="adminProductSearch">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input id="adminProductSearch" type="search" name="q" value="<?= $adminEscape($keyword) ?>"
               placeholder="Tìm theo tên sản phẩm..." aria-label="Tìm sản phẩm">
      </label>
      <label class="admin-filter-select" for="adminStatusFilter">
        <span class="admin-sr-only">Trạng thái</span>
        <select id="adminStatusFilter" name="status">
          <option value="">Tất cả trạng thái</option>
          <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Đang bán</option>
          <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Đang ẩn</option>
        </select>
      </label>
      <button class="admin-btn admin-btn-secondary" type="submit">Lọc</button>
      <?php if ($keyword !== '' || $status !== ''): ?>
        <a class="admin-clear-filter" href="<?= $adminBaseUrl ?>/admin-product/index">Xóa lọc</a>
      <?php endif; ?>
    </form>
    <span class="admin-result-count"><?= $adminEscape(number_format($total, 0, ',', '.')) ?> sản phẩm</span>
  </div>

  <?php if (!$products): ?>
    <div class="admin-empty">
      <span><i class="fa-solid fa-box-open" aria-hidden="true"></i></span>
      <h2>Chưa có sản phẩm phù hợp</h2>
      <p>Thử đổi bộ lọc hoặc tạo sản phẩm mới.</p>
      <a class="admin-btn admin-btn-primary" href="<?= $adminBaseUrl ?>/admin-product/create">Thêm sản phẩm</a>
    </div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th scope="col">Sản phẩm</th>
            <th scope="col">Phân loại</th>
            <th scope="col">Giá bán</th>
            <th scope="col">Tồn kho</th>
            <th scope="col">Trạng thái</th>
            <th scope="col" class="admin-table-actions-heading">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product):
              $productId = (int) ($product['id'] ?? 0);
              $productStatus = ($product['status_product'] ?? '') === 'Active' ? 'Active' : 'Closed';
              $thumbnail = (string) ($product['thumbnail'] ?? $product['image_link'] ?? '');
              $imageUrl = $thumbnail !== ''
                  ? BASE_URL . '/public/images/products/' . rawurlencode(basename($thumbnail))
                  : $placeholder;
              $price = max(0, (int) ($product['price'] ?? 0));
              $discount = min(100, max(0, (int) ($product['discount'] ?? 0)));
              $finalPrice = (int) round($price * (1 - $discount / 100));
          ?>
            <tr>
              <td data-label="Sản phẩm">
                <div class="admin-product-cell">
                  <img src="<?= $adminEscape($imageUrl) ?>"
                       onerror="this.onerror=null;this.src='<?= $adminEscape($placeholder) ?>'"
                       alt="<?= $adminEscape($product['name'] ?? 'Sản phẩm') ?>" loading="lazy">
                  <div>
                    <strong><?= $adminEscape($product['name'] ?? 'Chưa đặt tên') ?></strong>
                    <small>#<?= $adminEscape($productId) ?> · <?= $adminEscape($product['color'] ?? 'Chưa có màu') ?></small>
                  </div>
                </div>
              </td>
              <td data-label="Phân loại">
                <strong class="admin-table-primary"><?= $adminEscape($product['name_brand'] ?? 'Chưa gán') ?></strong>
                <small class="admin-table-secondary"><?= $adminEscape($product['name_category'] ?? 'Chưa gán') ?></small>
              </td>
              <td data-label="Giá bán">
                <strong class="admin-price"><?= $adminEscape(number_format($finalPrice, 0, ',', '.')) ?> đ</strong>
                <?php if ($discount > 0): ?>
                  <small class="admin-old-price"><?= $adminEscape(number_format($price, 0, ',', '.')) ?> đ · -<?= $adminEscape($discount) ?>%</small>
                <?php endif; ?>
              </td>
              <td data-label="Tồn kho">
                <strong class="<?= (int) ($product['quantity'] ?? 0) <= 5 ? 'admin-stock-low' : '' ?>">
                  <?= $adminEscape(number_format(max(0, (int) ($product['quantity'] ?? 0)), 0, ',', '.')) ?>
                </strong>
              </td>
              <td data-label="Trạng thái">
                <span class="admin-status admin-status-<?= $productStatus === 'Active' ? 'active' : 'closed' ?>">
                  <i aria-hidden="true"></i>
                  <?= $productStatus === 'Active' ? 'Đang bán' : 'Đang ẩn' ?>
                </span>
              </td>
              <td data-label="Thao tác">
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="<?= $adminBaseUrl ?>/product/detail/<?= $adminEscape($productId) ?>"
                     target="_blank" rel="noopener" title="Xem tại cửa hàng">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    <span class="admin-sr-only">Xem tại cửa hàng</span>
                  </a>
                  <a class="admin-icon-btn" href="<?= $adminBaseUrl ?>/admin-product/edit/<?= $adminEscape($productId) ?>"
                     title="Sửa sản phẩm">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    <span class="admin-sr-only">Sửa sản phẩm</span>
                  </a>
                  <form method="POST" action="<?= $adminBaseUrl ?>/admin-product/toggle/<?= $adminEscape($productId) ?>"
                        data-admin-confirm="<?= $adminEscape($productStatus === 'Active' ? 'Ẩn sản phẩm này khỏi cửa hàng?' : 'Hiển thị lại sản phẩm này?') ?>">
                    <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken) ?>">
                    <button class="admin-icon-btn" type="submit" title="<?= $productStatus === 'Active' ? 'Ẩn sản phẩm' : 'Hiện sản phẩm' ?>">
                      <i class="fa-solid <?= $productStatus === 'Active' ? 'fa-eye-slash' : 'fa-eye' ?>" aria-hidden="true"></i>
                      <span class="admin-sr-only"><?= $productStatus === 'Active' ? 'Ẩn sản phẩm' : 'Hiện sản phẩm' ?></span>
                    </button>
                  </form>
                  <form method="POST" action="<?= $adminBaseUrl ?>/admin-product/delete/<?= $adminEscape($productId) ?>"
                        data-admin-confirm="Đóng sản phẩm này? Sản phẩm sẽ được ẩn nhưng lịch sử đơn hàng vẫn được giữ.">
                    <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken) ?>">
                    <button class="admin-icon-btn admin-icon-btn-danger" type="submit" title="Đóng sản phẩm">
                      <i class="fa-solid fa-trash" aria-hidden="true"></i>
                      <span class="admin-sr-only">Đóng sản phẩm</span>
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
    <nav class="admin-pagination" aria-label="Phân trang sản phẩm">
      <a class="admin-page-link <?= $page <= 1 ? 'is-disabled' : '' ?>"
         href="<?= $page <= 1 ? '#' : $paginationUrl($page - 1) ?>"
         <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        <span class="admin-sr-only">Trang trước</span>
      </a>
      <?php
      $firstPage = max(1, $page - 2);
      $lastPage = min($totalPages, $page + 2);
      for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++):
      ?>
        <a class="admin-page-link <?= $pageNumber === $page ? 'is-active' : '' ?>"
           href="<?= $paginationUrl($pageNumber) ?>"
           <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>>
          <?= $adminEscape($pageNumber) ?>
        </a>
      <?php endfor; ?>
      <a class="admin-page-link <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
         href="<?= $page >= $totalPages ? '#' : $paginationUrl($page + 1) ?>"
         <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span class="admin-sr-only">Trang sau</span>
      </a>
    </nav>
  <?php endif; ?>
</section>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
