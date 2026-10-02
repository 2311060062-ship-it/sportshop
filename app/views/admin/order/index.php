<?php
/** Contract: orders,total,page,totalPages,keyword,status,statuses,flash,csrfToken,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$orders = is_array($orders ?? null) ? $orders : [];
$total = max(0, (int) ($total ?? count($orders)));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$status = (string) ($status ?? 'all');
$pageTitle = 'Quản lý đơn hàng';
$adminActive = 'orders';
$statusLabels = [
    'Cho_Thanh_Toan' => 'Chờ thanh toán',
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Dang_Giao' => 'Đang giao',
    'Da_Giao' => 'Đã giao - chờ khách xác nhận',
    'Da_Nhan_Hang' => 'Khách đã nhận hàng',
    'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
    'Da_Tra_Hang' => 'Đã trả hàng',
    'Yeu_Cau_Huy' => 'Yêu cầu hủy',
    'Da_Huy' => 'Đã hủy',
];
$statusClass = [
    'Cho_Thanh_Toan' => 'pending',
    'Dang_Xu_Ly' => 'processing',
    'Dang_Giao' => 'shipping',
    'Da_Giao' => 'completed',
    'Da_Nhan_Hang' => 'completed',
    'Yeu_Cau_Tra_Hang' => 'warning',
    'Da_Tra_Hang' => 'cancelled',
    'Yeu_Cau_Huy' => 'warning',
    'Da_Huy' => 'cancelled',
];
$paginationUrl = static function (int $targetPage) use ($keyword, $status, $adminEscape): string {
    $query = array_filter([
        'q' => $keyword,
        'status' => $status === 'all' ? '' : $status,
        'page' => max(1, $targetPage),
    ], static fn ($value): bool => $value !== '');
    return $adminEscape(BASE_URL . '/admin-order/index?' . http_build_query($query));
};
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow">Vận hành bán hàng</span>
    <h1>Đơn hàng</h1>
    <p>Theo dõi thanh toán, giao hàng và cập nhật trạng thái theo đúng quy trình.</p>
  </div>
</section>

<?php if (!empty($flash)):
    $flashType = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>" role="status">
    <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <span><?= $adminEscape($flash['msg'] ?? '') ?></span>
  </div>
<?php endif; ?>

<section class="admin-card">
  <div class="admin-card-header">
    <form class="admin-filters" method="GET" action="<?= $adminBaseUrl ?>/admin-order/index">
      <label class="admin-search" for="orderSearch">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="orderSearch" type="search" name="q" value="<?= $adminEscape($keyword) ?>"
               placeholder="Mã đơn, khách hàng, SĐT...">
      </label>
      <label class="admin-filter-select" for="orderStatus">
        <span class="admin-sr-only">Trạng thái</span>
        <select id="orderStatus" name="status">
          <option value="all">Tất cả trạng thái</option>
          <?php foreach (($statuses ?? []) as $statusValue): ?>
            <option value="<?= $adminEscape($statusValue) ?>" <?= $status === $statusValue ? 'selected' : '' ?>>
              <?= $adminEscape($statusLabels[$statusValue] ?? $statusValue) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="admin-btn admin-btn-secondary" type="submit">Lọc</button>
      <?php if ($keyword !== '' || $status !== 'all'): ?>
        <a class="admin-clear-filter" href="<?= $adminBaseUrl ?>/admin-order/index">Xóa lọc</a>
      <?php endif; ?>
    </form>
    <span class="admin-result-count"><?= number_format($total, 0, ',', '.') ?> đơn hàng</span>
  </div>

  <?php if (!$orders): ?>
    <div class="admin-empty">
      <span><i class="fa-solid fa-box-open"></i></span>
      <h2>Chưa có đơn hàng phù hợp</h2>
      <p>Thử thay đổi từ khóa hoặc trạng thái lọc.</p>
    </div>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table admin-order-table">
        <thead><tr>
          <th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th>
          <th>Sản phẩm</th><th>Thanh toán</th><th>Trạng thái</th>
          <th class="admin-table-actions-heading">Thao tác</th>
        </tr></thead>
        <tbody>
          <?php foreach ($orders as $order):
              $orderId = (int) $order['id'];
              $currentStatus = (string) $order['status_order'];
              $allowed = is_array($order['allowed_statuses'] ?? null) ? $order['allowed_statuses'] : [];
              $dateValue = strtotime((string) ($order['date'] ?? ''));
          ?>
            <tr>
              <td data-label="Mã đơn"><strong class="admin-order-code">#<?= $orderId ?></strong></td>
              <td data-label="Khách hàng">
                <strong class="admin-table-primary"><?= $adminEscape($order['full_name'] ?: $order['username']) ?></strong>
                <small><?= $adminEscape($order['phone'] ?? '') ?></small>
              </td>
              <td data-label="Ngày đặt"><?= $dateValue ? date('d/m/Y H:i', $dateValue) : '—' ?></td>
              <td data-label="Tổng tiền"><strong class="admin-price"><?= number_format((int) $order['total'], 0, ',', '.') ?> đ</strong></td>
              <td data-label="Sản phẩm"><?= number_format((int) $order['quantity_product'], 0, ',', '.') ?></td>
              <td data-label="Thanh toán">
                <span class="admin-payment-status admin-payment-<?= strtolower((string) ($order['payment_status'] ?? 'none')) ?>">
                  <?= $adminEscape($order['payment_status'] ?? 'Chưa có') ?>
                </span>
                <small><?= $adminEscape($order['payment_provider'] ?? '') ?></small>
              </td>
              <td data-label="Trạng thái">
                <span class="admin-order-status admin-order-status-<?= $adminEscape($statusClass[$currentStatus] ?? 'pending') ?>">
                  <?= $adminEscape($statusLabels[$currentStatus] ?? $currentStatus) ?>
                </span>
              </td>
              <td data-label="Thao tác">
                <div class="admin-row-actions">
                  <a class="admin-icon-btn" href="<?= $adminBaseUrl ?>/admin-order/detail/<?= $orderId ?>" title="Xem chi tiết">
                    <i class="fa-solid fa-eye"></i><span class="admin-sr-only">Xem</span>
                  </a>
                  <?php if ($allowed): ?>
                    <button class="admin-icon-btn admin-order-status-button" type="button" title="Đổi trạng thái"
                            data-order-id="<?= $orderId ?>"
                            data-order-current="<?= $adminEscape($currentStatus) ?>"
                            data-order-options="<?= $adminEscape(json_encode($allowed, JSON_UNESCAPED_UNICODE)) ?>">
                      <i class="fa-solid fa-pen-to-square"></i><span class="admin-sr-only">Đổi trạng thái</span>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination" aria-label="Phân trang đơn hàng">
      <a class="admin-page-link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $paginationUrl($page - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a>
      <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?>
        <a class="admin-page-link <?= $number === $page ? 'is-active' : '' ?>" href="<?= $paginationUrl($number) ?>"><?= $number ?></a>
      <?php endfor; ?>
      <a class="admin-page-link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : $paginationUrl($page + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </nav>
  <?php endif; ?>
</section>

<div class="admin-modal-backdrop" id="orderStatusModal" aria-hidden="true"
     data-status-labels="<?= $adminEscape(json_encode($statusLabels, JSON_UNESCAPED_UNICODE)) ?>">
  <section class="admin-modal admin-modal-small" role="dialog" aria-modal="true" aria-labelledby="orderStatusTitle">
    <header class="admin-modal-header">
      <div><h2 id="orderStatusTitle">Cập nhật trạng thái</h2><p>Đơn hàng <strong id="orderStatusCode"></strong></p></div>
      <button class="admin-modal-close" type="button" data-order-modal-close><i class="fa-solid fa-xmark"></i></button>
    </header>
    <form id="orderStatusForm" method="POST">
      <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
      <label class="admin-field">
        <span>Trạng thái tiếp theo</span>
        <select id="orderStatusSelect" name="status_order" required></select>
      </label>
      <p class="admin-status-warning" id="orderStatusWarning" hidden>
        <i class="fa-solid fa-triangle-exclamation"></i> Hủy/trả đơn sẽ hoàn lại tồn kho.
      </p>
      <footer class="admin-modal-footer">
        <button class="admin-btn admin-btn-secondary" type="button" data-order-modal-close>Hủy</button>
        <button class="admin-btn admin-btn-primary" type="submit">Cập nhật</button>
      </footer>
    </form>
  </section>
</div>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
