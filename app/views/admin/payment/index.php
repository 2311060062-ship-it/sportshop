<?php
/** Contract: settings,transactions,keyword,status,total,page,totalPages,flash,csrfToken,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$settings = is_array($settings ?? null) ? $settings : [];
$transactions = is_array($transactions ?? null) ? $transactions : [];
$keyword = trim((string) ($keyword ?? ''));
$status = in_array(($status ?? ''), ['Pending', 'Paid', 'Failed'], true) ? (string) $status : '';
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$pageTitle = 'Thanh toán QR';
$adminActive = 'payments';
$providers = ['VNPayQR' => ['VNPay QR', 'vnpay']];
$paginationUrl = static function (int $target) use ($keyword, $status, $adminEscape): string {
    return $adminEscape(BASE_URL . '/admin-payment/index?' . http_build_query(array_filter([
        'q' => $keyword, 'status' => $status, 'page' => $target,
    ], static fn ($value): bool => $value !== '')));
};
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div><span class="admin-eyebrow">Đối soát thủ công</span><h1>Thanh toán QR</h1><p>Cấu hình QR nhận tiền và xác nhận biên lai theo từng đơn hàng.</p></div>
</section>

<?php if (!empty($flash)):
    $flashType = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>"><i class="fa-solid <?= $flashType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i><span><?= $adminEscape($flash['msg'] ?? '') ?></span></div>
<?php endif; ?>

<div class="admin-alert admin-alert-info">
  <i class="fa-solid fa-circle-info"></i>
  <span>QR tải lên bên dưới chỉ dùng cho chuyển khoản VNPAY thủ công và không thay thế TMN Code/Hash Secret của cổng VNPAY chính thức.</span>
</div>

<section class="admin-payment-settings">
  <?php foreach ($providers as $provider => [$label, $theme]):
      $setting = is_array($settings[$provider] ?? null) ? $settings[$provider] : [];
      $qrImage = basename((string) ($setting['qr_image'] ?? ''));
  ?>
    <form class="admin-card admin-payment-setting-card" method="POST" action="<?= $adminBaseUrl ?>/admin-payment/save/<?= $provider ?>" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
      <header><span class="admin-payment-provider <?= $theme ?>">VN</span><div><h2><?= $label ?></h2><p>QR do cửa hàng sở hữu</p></div><label class="admin-switch"><input type="checkbox" name="is_active" value="1" <?= !empty($setting['is_active']) ? 'checked' : '' ?>><span></span></label></header>
      <div class="admin-payment-setting-body">
        <label class="admin-qr-upload">
          <?php if ($qrImage): ?><img src="<?= $adminBaseUrl ?>/public/images/payment-qr/<?= rawurlencode($qrImage) ?>" alt="QR <?= $label ?>"><?php else: ?><span><i class="fa-solid fa-qrcode"></i> Tải ảnh QR</span><?php endif; ?>
          <input type="file" name="qr_image" accept="image/jpeg,image/png,image/webp" <?= $qrImage ? '' : 'required' ?>>
        </label>
        <div class="admin-payment-fields">
          <label><span>Tên người nhận *</span><input name="account_name" maxlength="150" value="<?= $adminEscape($setting['account_name'] ?? '') ?>" required></label>
          <label><span>SĐT/Tài khoản</span><input name="account_number" maxlength="100" value="<?= $adminEscape($setting['account_number'] ?? '') ?>"></label>
          <label><span>Tiền tố nội dung *</span><input name="transfer_prefix" maxlength="20" value="<?= $adminEscape($setting['transfer_prefix'] ?? 'SPORTSHOP') ?>" pattern="[A-Za-z0-9]{2,20}" required></label>
          <label><span>Hướng dẫn</span><textarea name="instructions" maxlength="500" rows="2"><?= $adminEscape($setting['instructions'] ?? '') ?></textarea></label>
        </div>
      </div>
      <button class="admin-btn admin-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Lưu <?= $label ?></button>
    </form>
  <?php endforeach; ?>
</section>

<section class="admin-card">
  <div class="admin-card-header">
    <form class="admin-filters" method="GET" action="<?= $adminBaseUrl ?>/admin-payment/index">
      <label class="admin-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= $adminEscape($keyword) ?>" placeholder="Mã chuyển khoản, khách hàng..."></label>
      <label class="admin-filter-select"><select name="status"><option value="">Tất cả trạng thái</option><option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Chờ xác nhận</option><option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Đã thanh toán</option><option value="Failed" <?= $status === 'Failed' ? 'selected' : '' ?>>Từ chối</option></select></label>
      <button class="admin-btn admin-btn-secondary" type="submit">Lọc</button>
    </form>
    <span class="admin-result-count"><?= (int) ($total ?? 0) ?> giao dịch</span>
  </div>
  <?php if (!$transactions): ?><div class="admin-empty"><span><i class="fa-solid fa-receipt"></i></span><h2>Chưa có giao dịch QR</h2><p>Giao dịch sẽ xuất hiện sau khi khách chọn QR tại checkout.</p></div><?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table admin-payment-table"><thead><tr><th>Giao dịch</th><th>Khách hàng</th><th>Số tiền</th><th>Biên lai</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($transactions as $transaction):
        $pending = $transaction['status'] === 'Pending';
        $manualQr = $transaction['provider'] === 'VNPayQR';
        $receipt = basename((string) ($transaction['receipt_image'] ?? ''));
    ?>
      <tr>
        <td><strong><?= $adminEscape($transaction['provider_order_id']) ?></strong><small>Đơn #<?= (int) $transaction['order_id'] ?> · <?= $adminEscape($transaction['provider']) ?></small></td>
        <td><strong><?= $adminEscape($transaction['full_name'] ?: $transaction['username']) ?></strong><small><?= $adminEscape($transaction['phone']) ?></small></td>
        <td><strong class="admin-price"><?= number_format((int) $transaction['amount'], 0, ',', '.') ?> đ</strong></td>
        <td><?php if ($receipt): ?><a class="admin-receipt-link" href="<?= $adminBaseUrl ?>/public/images/payment-proofs/<?= rawurlencode($receipt) ?>" target="_blank"><i class="fa-solid fa-image"></i> Xem ảnh</a><?php else: ?><span class="admin-muted-text">Chưa gửi</span><?php endif; ?></td>
        <td><span class="admin-payment-status admin-payment-<?= strtolower($transaction['status']) ?>"><?= $transaction['status'] === 'Pending' ? 'Chờ xác nhận' : ($transaction['status'] === 'Paid' ? 'Đã thanh toán' : 'Đã từ chối') ?></span></td>
        <td><div class="admin-row-actions">
          <?php if ($pending && $manualQr): ?>
            <form method="POST" action="<?= $adminBaseUrl ?>/admin-payment/approve/<?= (int) $transaction['id'] ?>" data-admin-confirm="Bạn đã kiểm tra tiền thực nhận và muốn xác nhận giao dịch?"><input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>"><button class="admin-icon-btn admin-icon-success" title="Xác nhận"><i class="fa-solid fa-check"></i></button></form>
            <form method="POST" action="<?= $adminBaseUrl ?>/admin-payment/reject/<?= (int) $transaction['id'] ?>" data-admin-confirm="Từ chối sẽ hủy đơn và hoàn tồn kho. Tiếp tục?"><input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>"><button class="admin-icon-btn admin-icon-danger" title="Từ chối"><i class="fa-solid fa-xmark"></i></button></form>
          <?php endif; ?>
          <a class="admin-icon-btn" href="<?= $adminBaseUrl ?>/admin-order/detail/<?= (int) $transaction['order_id'] ?>"><i class="fa-solid fa-eye"></i></a>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
  <?php if ($totalPages > 1): ?><nav class="admin-pagination"><?php for ($number = 1; $number <= $totalPages; $number++): ?><a class="admin-page-link <?= $number === $page ? 'is-active' : '' ?>" href="<?= $paginationUrl($number) ?>"><?= $number ?></a><?php endfor; ?></nav><?php endif; ?>
</section>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
