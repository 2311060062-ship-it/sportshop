<?php
/** Biến nhận vào: $order, $payment, $message, $cartCount, $authUser */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$order = is_array($order ?? null) ? $order : [];
$payment = is_array($payment ?? null) ? $payment : ['status' => (string) ($payment ?? '')];
$paymentStatus = strtolower((string) ($payment['status'] ?? $payment['resultCode'] ?? ''));
$provider = (string) ($payment['provider'] ?? 'Cổng thanh toán');
$isSuccess = !empty($order['id']) && $paymentStatus === 'paid';
$isFailed = in_array($paymentStatus, ['failed', 'failure', 'cancelled', 'error'], true);
$statusLabel = [
    'Cho_Thanh_Toan' => 'Chờ thanh toán',
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Dang_Giao' => 'Đang giao',
    'Da_Giao' => 'Đã giao - chờ xác nhận',
    'Da_Nhan_Hang' => 'Đã nhận hàng',
    'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
    'Da_Tra_Hang' => 'Đã trả hàng',
    'Yeu_Cau_Huy' => 'Yêu cầu hủy',
    'Da_Huy' => 'Đã hủy',
][$order['status_order'] ?? 'Dang_Xu_Ly'] ?? 'Đang xử lý';
?>

<section class="result-page">
  <div class="section-container">
    <div class="result-card <?= $isSuccess ? 'success' : ($isFailed ? 'failed' : 'pending') ?> reveal-scale">
      <div class="result-icon"><i class="fas <?= $isSuccess ? 'fa-check' : ($isFailed ? 'fa-xmark' : 'fa-clock') ?>"></i></div>
      <span class="section-kicker reveal"><?= $isSuccess ? 'Đặt hàng thành công' : 'Trạng thái thanh toán' ?></span>
      <h1 class="reveal"><?= $isSuccess ? 'Cảm ơn bạn đã mua hàng!' : ($isFailed ? 'Thanh toán chưa thành công' : 'Đơn hàng đang được xác nhận') ?></h1>
      <p class="result-message reveal"><?= $escape($message ?? ($isSuccess ? 'Đơn hàng đã được tiếp nhận và sẽ sớm được xử lý.' : 'Vui lòng kiểm tra trạng thái thanh toán hoặc thử lại.')) ?></p>

      <?php if (!empty($order)): ?>
        <div class="order-code"><span>Mã đơn hàng</span><strong>#<?= $escape($order['id'] ?? $order['order_id'] ?? '—') ?></strong></div>
        <div class="result-details">
          <div><span>Trạng thái đơn hàng</span><strong><?= $escape($statusLabel) ?></strong></div>
          <div><span>Phương thức</span><strong><?= $escape($provider) ?></strong></div>
          <?php if (!empty($order['date'])): ?><div><span>Thời gian đặt</span><strong><?= $escape($order['date']) ?></strong></div><?php endif; ?>
          <?php if (isset($order['total'])): ?><div><span>Tổng thanh toán</span><strong class="result-total"><?= ProductModel::formatPrice((int) $order['total']) ?></strong></div><?php endif; ?>
          <?php if (!empty($order['address'])): ?><div class="full"><span>Địa chỉ nhận hàng</span><strong><?= $escape($order['address']) ?></strong></div><?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($payment['transId']) || !empty($payment['trans_id'])): ?>
        <p class="transaction-code">Mã giao dịch <?= $escape($provider) ?>: <strong><?= $escape($payment['transId'] ?? $payment['trans_id']) ?></strong></p>
      <?php endif; ?>

      <div class="result-actions">
        <?php if ($isSuccess): ?>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/user/orders">
            <i class="fas fa-box"></i> Theo dõi đơn hàng
          </a>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/product/index"><i class="fas fa-bag-shopping"></i> Tiếp tục mua sắm</a>
        <a class="btn btn-outline" href="<?= BASE_URL ?>/"><i class="fas fa-house"></i> Về trang chủ</a>
      </div>
      <div class="result-support"><i class="fas fa-headset"></i><span>Cần hỗ trợ? Liên hệ <strong>0909 123 456</strong> hoặc contact@sportshop.vn</span></div>
    </div>
  </div>
</section>
