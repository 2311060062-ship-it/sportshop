<?php
/** Contract: order,payment,qrData,fallbackUrl,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$order = is_array($order ?? null) ? $order : [];
$payment = is_array($payment ?? null) ? $payment : [];
$provider = (string) ($payment['provider'] ?? '');
$paid = ($payment['status'] ?? '') === 'Paid';
$providerLabel = $provider === 'DemoQR' ? 'Sandbox Demo' : $provider;
?>

<section class="page-hero compact-hero reveal">
  <div class="section-container">
    <nav class="breadcrumb reveal-left"><a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i><span>QR động</span></nav>
    <h1 class="reveal">Quét QR để thanh toán</h1>
    <p class="reveal">Số tiền và mã đơn đã được điền sẵn, bạn chỉ cần quét và xác nhận.</p>
  </div>
</section>

<section class="dynamic-qr-page">
  <div class="section-container">
    <article class="dynamic-qr-card <?= $paid ? 'is-paid' : '' ?> reveal-scale"
             id="dynamicQrPayment"
             data-order-id="<?= (int) $order['id'] ?>"
             data-status-url="<?= BASE_URL ?>/checkout/payment-status/<?= (int) $order['id'] ?>">
      <header>
        <span class="<?= $provider === 'MoMo' ? 'momo-logo' : ($provider === 'DemoQR' ? 'demo-logo' : 'vnpay-logo') ?>"><?= $provider === 'MoMo' ? 'M' : ($provider === 'DemoQR' ? 'TEST' : 'VN') ?></span>
        <div><h1><?= $escape($providerLabel) ?> QR</h1><p>Đơn hàng #<?= (int) $order['id'] ?></p></div>
      </header>

      <div class="dynamic-qr-content">
        <div class="dynamic-qr-code" id="dynamicQrCode" data-qr="<?= $escape($qrData ?? '') ?>">
          <span class="dynamic-qr-loading"><i class="fas fa-spinner fa-spin"></i> Đang tạo QR...</span>
        </div>
        <div class="dynamic-qr-info">
          <span>Số tiền thanh toán</span>
          <strong><?= number_format((int) ($payment['amount'] ?? $order['total'] ?? 0), 0, ',', '.') ?> đ</strong>
          <dl>
            <div><dt>Mã giao dịch</dt><dd><?= $escape($payment['provider_order_id'] ?? '') ?></dd></div>
            <div><dt>Trạng thái</dt><dd id="dynamicQrStatus"><?= $paid ? 'Thanh toán thành công' : 'Đang chờ thanh toán' ?></dd></div>
          </dl>
          <p><i class="fas fa-mobile-screen"></i> <?= $provider === 'DemoQR' ? 'Quét QR hoặc mở trang mô phỏng, sau đó bấm xác nhận thanh toán.' : 'Mở ứng dụng ' . $escape($provider) . ' hoặc ứng dụng hỗ trợ, quét mã và kiểm tra đúng số tiền trước khi xác nhận.' ?></p>
          <?php if (!empty($fallbackUrl)): ?><a class="btn btn-outline btn-block" href="<?= $escape($fallbackUrl) ?>" target="_blank" <?= $provider === 'DemoQR' ? '' : 'rel="noopener"' ?>><i class="fas fa-arrow-up-right-from-square"></i> Mở trang <?= $escape($providerLabel) ?></a><?php endif; ?>
        </div>
      </div>

      <div class="dynamic-qr-waiting" id="dynamicQrWaiting" <?= $paid ? 'hidden' : '' ?>><i class="fas fa-shield-halved"></i><span>Hệ thống đang tự động chờ xác nhận từ <?= $escape($providerLabel) ?>...</span></div>
      <div class="dynamic-qr-success" id="dynamicQrSuccess" <?= $paid ? '' : 'hidden' ?>><i class="fas fa-circle-check"></i><div><strong>Thanh toán thành công</strong><span>Đơn hàng của bạn đã được xác nhận tự động.</span></div></div>
      <div class="dynamic-qr-failed" id="dynamicQrFailed" hidden><i class="fas fa-circle-xmark"></i><div><strong>Thanh toán không thành công</strong><span>Vui lòng quay lại giỏ hàng và thử lại.</span></div></div>
    </article>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
