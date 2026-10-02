<?php
/** Contract: payment,transferCode,message,csrfToken,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$payment = is_array($payment ?? null) ? $payment : [];
$qrFile = basename((string) ($payment['qr_image'] ?? ''));
$qrUrl = BASE_URL . '/public/images/payment-qr/' . rawurlencode($qrFile);
$receiptFile = basename((string) ($payment['receipt_image'] ?? ''));
$paymentStatus = (string) ($payment['status'] ?? 'Pending');
$isPending = $paymentStatus === 'Pending';
$statusLabel = $paymentStatus === 'Paid'
    ? 'Đã xác nhận thanh toán'
    : ($paymentStatus === 'Failed' ? 'Giao dịch bị từ chối' : 'Đang chờ xác nhận');
?>

<section class="page-hero compact-hero reveal">
  <div class="section-container">
    <nav class="breadcrumb reveal-left"><a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i><span>Thanh toán QR</span></nav>
    <h1 class="reveal">Thanh toán đơn hàng #<?= (int) ($payment['order_id'] ?? 0) ?></h1>
    <p class="reveal">Quét QR, chuyển đúng số tiền và nội dung để đơn được đối soát.</p>
  </div>
</section>

<section class="qr-payment-page">
  <div class="section-container qr-payment-layout">
    <section class="qr-payment-card reveal-left">
      <div class="qr-provider-heading">
        <span class="vnpay-logo">QR</span>
        <div><h2><?= $escape($payment['display_name'] ?? 'Thanh toán QR') ?></h2><p>Người nhận: <strong><?= $escape($payment['account_name'] ?? '') ?></strong></p></div>
      </div>
      <div class="qr-image-frame"><img src="<?= $escape($qrUrl) ?>" alt="Mã QR thanh toán"></div>
      <?php if (!empty($payment['account_number'])): ?><p class="qr-account-number">Tài khoản/SĐT: <strong><?= $escape($payment['account_number']) ?></strong></p><?php endif; ?>
      <?php if (!empty($payment['instructions'])): ?><p class="qr-instructions"><?= nl2br($escape($payment['instructions'])) ?></p><?php endif; ?>
    </section>

    <aside class="qr-payment-details reveal-right">
      <?php if (!empty($message)): ?><div class="alert <?= str_contains($message, 'Đã gửi') ? 'alert-success' : 'alert-error' ?>"><?= $escape($message) ?></div><?php endif; ?>
      <div class="checkout-card">
        <div class="checkout-card-title"><span>1</span><div><h2>Thông tin chuyển khoản</h2><p>Không tự ý thay đổi nội dung.</p></div></div>
        <div class="qr-transfer-row"><span>Số tiền</span><strong><?= number_format((int) ($payment['amount'] ?? $payment['total'] ?? 0), 0, ',', '.') ?> đ</strong></div>
        <div class="qr-transfer-row"><span>Nội dung</span><strong class="qr-transfer-code"><?= $escape($transferCode ?? '') ?></strong></div>
        <button class="btn btn-outline btn-block" type="button" data-copy-value="<?= $escape($transferCode ?? '') ?>"><i class="fas fa-copy"></i> Sao chép nội dung</button>
      </div>

      <div class="checkout-card">
        <div class="checkout-card-title"><span>2</span><div><h2>Gửi biên lai</h2><p>Admin sẽ kiểm tra giao dịch trước khi xử lý đơn.</p></div></div>
        <?php if ($receiptFile): ?>
          <div class="qr-receipt-preview qr-receipt-<?= strtolower($escape($paymentStatus)) ?>">
            <img src="<?= BASE_URL ?>/public/images/payment-proofs/<?= rawurlencode($receiptFile) ?>" alt="Biên lai đã gửi">
            <span><i class="fas <?= $paymentStatus === 'Paid' ? 'fa-circle-check' : ($paymentStatus === 'Failed' ? 'fa-circle-xmark' : 'fa-clock') ?>"></i> <?= $escape($statusLabel) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($isPending): ?>
          <form method="POST" action="<?= BASE_URL ?>/checkout/submit-qr/<?= (int) $payment['order_id'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
            <label class="qr-proof-upload"><i class="fas fa-cloud-arrow-up"></i><span><?= $receiptFile ? 'Thay ảnh biên lai' : 'Chọn ảnh biên lai' ?></span><input type="file" name="receipt_image" accept="image/jpeg,image/png,image/webp" required></label>
            <button class="btn btn-primary btn-block" type="submit">Tôi đã thanh toán</button>
          </form>
        <?php else: ?>
          <div class="payment-security"><i class="fas <?= $paymentStatus === 'Paid' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= $escape($statusLabel) ?></div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>
