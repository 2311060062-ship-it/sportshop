<?php
/** Contract: order,items,allowedStatuses,csrfToken,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$order = is_array($order ?? null) ? $order : [];
$items = is_array($items ?? null) ? $items : [];
$allowedStatuses = is_array($allowedStatuses ?? null) ? $allowedStatuses : [];
$pageTitle = 'Chi tiết đơn hàng #' . (int) ($order['id'] ?? 0);
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
    'Cho_Thanh_Toan' => 'pending', 'Dang_Xu_Ly' => 'processing',
    'Dang_Giao' => 'shipping', 'Da_Giao' => 'completed',
    'Da_Nhan_Hang' => 'completed', 'Yeu_Cau_Tra_Hang' => 'warning',
    'Da_Tra_Hang' => 'cancelled',
    'Yeu_Cau_Huy' => 'warning', 'Da_Huy' => 'cancelled',
];
$currentStatus = (string) ($order['status_order'] ?? '');
$dateValue = strtotime((string) ($order['date'] ?? ''));
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading admin-order-detail-heading">
  <div>
    <a class="admin-back-link" href="<?= $adminBaseUrl ?>/admin-order/index"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
    <h1>Đơn hàng #<?= (int) $order['id'] ?></h1>
    <p>Thông tin khách hàng, thanh toán và các sản phẩm trong đơn.</p>
  </div>
  <?php if ($allowedStatuses): ?>
    <form class="admin-detail-status-form" method="POST" action="<?= $adminBaseUrl ?>/admin-order/status/<?= (int) $order['id'] ?>"
          data-admin-confirm="Xác nhận cập nhật trạng thái đơn hàng?">
      <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
      <input type="hidden" name="return_to" value="detail">
      <select name="status_order" required>
        <option value="">Chọn trạng thái tiếp theo</option>
        <?php foreach ($allowedStatuses as $nextStatus): ?>
          <option value="<?= $adminEscape($nextStatus) ?>"><?= $adminEscape($statusLabels[$nextStatus] ?? $nextStatus) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="admin-btn admin-btn-primary" type="submit"><i class="fa-solid fa-pen-to-square"></i> Cập nhật</button>
    </form>
  <?php endif; ?>
</section>

<?php if (($order['payment_status'] ?? '') === 'Paid' && in_array($currentStatus, ['Da_Huy', 'Da_Tra_Hang'], true)): ?>
  <div class="admin-alert admin-alert-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span>Đơn đã hủy/trả nhưng giao dịch đã thanh toán. Cần đối soát và hoàn tiền ngoài hệ thống.</span>
  </div>
<?php endif; ?>

<section class="admin-order-summary">
  <article class="admin-card admin-order-summary-card">
    <span><i class="fa-solid fa-hashtag"></i></span>
    <div><small>Mã đơn hàng</small><strong>#<?= (int) $order['id'] ?></strong></div>
  </article>
  <article class="admin-card admin-order-summary-card">
    <span><i class="fa-regular fa-calendar"></i></span>
    <div><small>Ngày đặt</small><strong><?= $dateValue ? date('d/m/Y H:i', $dateValue) : '—' ?></strong></div>
  </article>
  <article class="admin-card admin-order-summary-card">
    <span><i class="fa-solid fa-shoe-prints"></i></span>
    <div><small>Số lượng sản phẩm</small><strong><?= (int) ($order['quantity_product'] ?? 0) ?></strong></div>
  </article>
  <article class="admin-card admin-order-summary-card">
    <span><i class="fa-solid fa-wallet"></i></span>
    <div><small>Tổng thanh toán</small><strong><?= number_format((int) ($order['total'] ?? 0), 0, ',', '.') ?> đ</strong></div>
  </article>
</section>

<div class="admin-order-detail-grid">
  <section class="admin-card admin-order-info-card">
    <div class="admin-section-title"><i class="fa-solid fa-user"></i><div><h2>Khách hàng và giao hàng</h2><p>Thông tin dùng để liên hệ giao nhận.</p></div></div>
    <dl class="admin-info-list">
      <div><dt>Khách hàng</dt><dd><?= $adminEscape($order['full_name'] ?: $order['username']) ?></dd></div>
      <div><dt>Tài khoản</dt><dd><?= $adminEscape($order['username'] ?? '') ?></dd></div>
      <div><dt>Email</dt><dd><?= $adminEscape($order['email'] ?? 'Chưa cập nhật') ?></dd></div>
      <div><dt>Số điện thoại</dt><dd><?= $adminEscape($order['phone'] ?? '') ?></dd></div>
      <div class="full"><dt>Địa chỉ nhận hàng</dt><dd><?= $adminEscape($order['address'] ?? '') ?></dd></div>
    </dl>
  </section>

  <aside class="admin-card admin-order-info-card">
    <div class="admin-section-title"><i class="fa-solid fa-credit-card"></i><div><h2>Thanh toán và trạng thái</h2><p>Thông tin xác nhận từ cổng thanh toán.</p></div></div>
    <dl class="admin-info-list single">
      <div><dt>Trạng thái đơn</dt><dd><span class="admin-order-status admin-order-status-<?= $adminEscape($statusClass[$currentStatus] ?? 'pending') ?>"><?= $adminEscape($statusLabels[$currentStatus] ?? $currentStatus) ?></span></dd></div>
      <div><dt>Phương thức</dt><dd><?= $adminEscape($order['payment_provider'] ?? 'Chưa xác định') ?></dd></div>
      <div><dt>Thanh toán</dt><dd><?= $adminEscape($order['payment_status'] ?? 'Chưa có giao dịch') ?></dd></div>
      <div><dt>Mã giao dịch</dt><dd><?= $adminEscape($order['trans_id'] ?? '—') ?></dd></div>
    </dl>
  </aside>
</div>

<section class="admin-card admin-order-items-card">
  <div class="admin-section-title"><i class="fa-solid fa-box"></i><div><h2>Sản phẩm trong đơn</h2><p>Giá được lưu tại thời điểm khách hàng đặt hàng.</p></div></div>
  <div class="admin-table-wrap">
    <table class="admin-table admin-order-items-table">
      <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Giảm giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item):
            $thumbnail = basename((string) ($item['thumbnail'] ?? ''));
            $imageUrl = $thumbnail ? BASE_URL . '/public/images/products/' . rawurlencode($thumbnail) : $placeholder;
        ?>
            <td><div class="admin-product-cell">
              <img src="<?= $adminEscape($imageUrl) ?>" onerror="this.onerror=null;this.src='<?= $adminEscape($placeholder) ?>'" alt="">
              <div>
                <strong><?= $adminEscape($item['name'] ?? '') ?></strong>
                <small><?= !empty($item['color']) ? $adminEscape($item['color']) . ' • ' : '' ?><span style="color:#1677ff;font-weight:700;">Size: <?= $adminEscape($item['size'] ?? '40') ?></span></small>
              </div>
            </div></td>
            <td><?= number_format((int) $item['price'], 0, ',', '.') ?> đ</td>
            <td><?= (int) $item['discount'] ?>%</td>
            <td><?= (int) $item['quantity'] ?></td>
            <td><strong class="admin-price"><?= number_format((int) $item['total'], 0, ',', '.') ?> đ</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
