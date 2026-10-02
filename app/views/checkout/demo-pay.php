<?php
/** Contract: payment,token,success,error,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$payment = is_array($payment ?? null) ? $payment : [];
?>

<section class="demo-gateway-page">
  <article class="demo-gateway-card"
           data-demo-payment-success="<?= $success ? '1' : '0' ?>"
           data-order-id="<?= (int) ($payment['order_id'] ?? 0) ?>"
           data-return-url="<?= BASE_URL ?>/checkout/dynamic-qr/<?= (int) ($payment['order_id'] ?? 0) ?>">
    <header><span><i class="fas fa-flask"></i></span><div><small>SPORT SHOP SANDBOX</small><h1>Cổng thanh toán mô phỏng</h1></div></header>

    <?php if ($success): ?>
      <div class="demo-gateway-result success"><i class="fas fa-circle-check"></i><h2>Thanh toán thành công</h2><p>Đơn hàng đã được tiếp nhận và chuyển sang trạng thái đang xử lý.</p></div>
      <a class="btn btn-primary btn-block" href="<?= BASE_URL ?>/user/orders?status=processing">
        <i class="fas fa-box"></i> Theo dõi đơn hàng
      </a>
    <?php elseif ($error !== ''): ?>
      <div class="demo-gateway-result failed"><i class="fas fa-circle-xmark"></i><h2>Không thể thanh toán</h2><p><?= $escape($error) ?></p></div>
    <?php else: ?>
      <div class="demo-gateway-amount"><span>Số tiền cần thanh toán</span><strong><?= number_format((int) $payment['amount'], 0, ',', '.') ?> đ</strong></div>
      <dl class="demo-gateway-info">
        <div><dt>Mã giao dịch</dt><dd><?= $escape($payment['provider_order_id']) ?></dd></div>
        <div><dt>Đơn hàng</dt><dd>#<?= (int) $payment['order_id'] ?></dd></div>
        <div><dt>Môi trường</dt><dd>LOCAL SANDBOX</dd></div>
      </dl>
      <div class="demo-gateway-notice"><i class="fas fa-circle-info"></i> Đây là giả lập. Không có tiền thật được chuyển.</div>
      <form method="POST" action="<?= BASE_URL ?>/checkout/demo-pay/<?= rawurlencode((string) $payment['provider_order_id']) ?>">
        <input type="hidden" name="token" value="<?= $escape($token ?? '') ?>">
        <button class="btn btn-primary btn-block demo-confirm-payment" type="submit"><i class="fas fa-check"></i> Xác nhận thanh toán mô phỏng</button>
      </form>
    <?php endif; ?>
  </article>
</section>
