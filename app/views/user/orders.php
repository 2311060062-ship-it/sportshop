<?php
/** Contract: orders,activeGroup,counts,flash,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$orders = is_array($orders ?? null) ? $orders : [];
$activeGroup = (string) ($activeGroup ?? 'processing');
$counts = is_array($counts ?? null) ? $counts : [];
$tabs = [
    'processing' => ['Đang xử lý', 'fa-box'],
    'shipping' => ['Đang giao', 'fa-truck-fast'],
    'delivered' => ['Đã giao', 'fa-circle-check'],
    'cancelled' => ['Đã hủy/Trả', 'fa-circle-xmark'],
];
$statusLabels = [
    'Cho_Thanh_Toan' => 'Chờ thanh toán',
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Yeu_Cau_Huy' => 'Yêu cầu hủy',
    'Dang_Giao' => 'Đang giao',
    'Da_Giao' => 'Đã giao - chờ xác nhận',
    'Da_Nhan_Hang' => 'Đã nhận hàng',
    'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
    'Da_Tra_Hang' => 'Đã trả hàng',
    'Da_Huy' => 'Đã hủy',
];
$activeLabel = (string) ($tabs[$activeGroup][0] ?? '');
$activeLabelLower = function_exists('mb_strtolower')
    ? mb_strtolower($activeLabel, 'UTF-8')
    : strtolower($activeLabel);
?>

<section class="customer-orders-page">
  <div class="section-container">
    <header class="customer-orders-heading reveal">
      <span><i class="fas fa-clock-rotate-left"></i></span>
      <div><h1>Lịch sử mua hàng</h1><p>Theo dõi tình trạng các đơn hàng của bạn.</p></div>
    </header>

    <?php if (!empty($flash)): ?>
      <div class="alert <?= ($flash['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?> reveal">
        <i class="fas <?= ($flash['type'] ?? '') === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <span><?= $escape($flash['msg'] ?? '') ?></span>
      </div>
    <?php endif; ?>
    <?php foreach ((array) ($ghnNotices ?? []) as $ghnNotice): ?>
      <div class="alert alert-success reveal">
        <i class="fas fa-truck"></i>
        <span><?= $escape($ghnNotice) ?></span>
      </div>
    <?php endforeach; ?>

    <nav class="order-status-tabs reveal" aria-label="Lọc đơn hàng">
      <?php foreach ($tabs as $key => [$label, $icon]): ?>
        <a class="<?= $activeGroup === $key ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/user/orders?status=<?= $escape($key) ?>">
          <i class="fas <?= $escape($icon) ?>"></i>
          <span><?= $escape($label) ?></span>
          <b><?= (int) ($counts[$key] ?? 0) ?></b>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if (!$orders): ?>
      <div class="empty-state customer-orders-empty reveal">
        <i class="fas fa-box-open"></i>
        <h2>Chưa có đơn hàng <?= $escape($activeLabelLower) ?></h2>
        <p>Các đơn hàng phù hợp sẽ được hiển thị tại đây.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/product/index">Tiếp tục mua sắm</a>
      </div>
    <?php else: ?>
      <div class="customer-orders-table-wrap reveal-scale">
        <table class="customer-orders-table">
          <thead>
            <tr>
              <th>Mã đơn</th>
              <th>Ngày đặt</th>
              <th>Tên sản phẩm</th>
              <th>Số lượng</th>
              <th>Giá</th>
              <th>Thành tiền</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <?php foreach ($orders as $order):
              $orderId = (int) $order['id'];
              $status = (string) $order['status_order'];
              $date = strtotime((string) ($order['date'] ?? ''));
              $items = is_array($order['items'] ?? null) && $order['items']
                  ? $order['items']
                  : [['product_id' => 0, 'name' => 'Sản phẩm không còn tồn tại', 'quantity' => 0, 'total' => 0]];
              $rowspan = count($items);
          ?>
            <tbody class="customer-order-table-group">
              <?php foreach ($items as $index => $item):
                  $quantity = max(1, (int) ($item['quantity'] ?? 1));
                  $itemTotal = (int) ($item['total'] ?? 0);
                  $unitPrice = (int) round($itemTotal / $quantity);
                  $productId = (int) ($item['product_id'] ?? 0);
              ?>
                <tr>
                  <?php if ($index === 0): ?>
                    <td rowspan="<?= $rowspan ?>"><strong><a href="<?= BASE_URL ?>/user/order-detail/<?= $orderId ?>">#<?= $orderId ?></a></strong></td>
                    <td rowspan="<?= $rowspan ?>"><?= $date ? date('d/m/Y H:i', $date) : '—' ?></td>
                  <?php endif; ?>
                  <td>
                    <strong class="order-table-product-name"><?= $escape($item['name'] ?? 'Sản phẩm') ?></strong>
                    <?php if (!empty($item['color'])): ?><small><?= $escape($item['color']) ?></small><?php endif; ?>
                  </td>
                  <td><?= (int) ($item['quantity'] ?? 0) ?></td>
                  <td><?= ProductModel::formatPrice($unitPrice) ?></td>
                  <td><strong><?= ProductModel::formatPrice($itemTotal) ?></strong></td>
                  <td>
                    <div class="order-table-actions">
                      <span class="customer-order-status status-<?= strtolower($escape($status)) ?>">
                        <?= $escape($statusLabels[$status] ?? $status) ?>
                      </span>
                      <a href="<?= BASE_URL ?>/user/order-detail/<?= $orderId ?>">Xem chi tiết</a>
                      <?php if ($status === 'Da_Nhan_Hang'): ?>
                        <a class="review" href="<?= BASE_URL ?>/user/order-detail/<?= $orderId ?>#product-<?= $productId ?>">Đánh giá</a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <tr class="order-table-notice-row">
                <td colspan="7">
                  <div class="order-table-notice">
                    <?php if ((int) ($order['ghn_total_fee'] ?? 0) > 0 || !empty($order['ghn_order_code'])): ?>
                      <span><i class="fas fa-truck"></i>
                        Phí vận chuyển <?= ProductModel::formatPrice((int) ($order['ghn_total_fee'] ?? 0)) ?>
                        <?php if (!empty($order['ghn_order_code'])): ?>
                          · Mã GHN <?= $escape($order['ghn_order_code']) ?>
                          · <?= $escape(GhnService::statusLabel((string) ($order['shipping_status'] ?? ''))) ?>
                        <?php endif; ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($status === 'Da_Giao'): ?>
                      <span><i class="fas fa-box-open"></i> Đơn vị vận chuyển báo đã giao. Bạn đã nhận được hàng chưa?</span>
                      <form class="delivery-response-form" method="POST" action="<?= BASE_URL ?>/user/delivery-response/<?= $orderId ?>">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                        <button class="btn customer-confirm-received" type="submit" name="delivery_action" value="received"
                                data-confirm-click="Xác nhận bạn đã nhận được đơn hàng #<?= $orderId ?>?">
                          <i class="fas fa-circle-check"></i> Đã nhận được hàng
                        </button>
                        <button class="btn customer-request-return" type="submit" name="delivery_action" value="return"
                                data-confirm-click="Gửi yêu cầu chưa nhận được hàng hoặc trả đơn #<?= $orderId ?>?">
                          <i class="fas fa-rotate-left"></i> Chưa nhận/Trả hàng
                        </button>
                      </form>
                    <?php elseif ($status === 'Cho_Thanh_Toan'): ?>
                      <span><i class="fas fa-wallet"></i> Đơn đang chờ thanh toán MoMo.</span>
                      <form method="POST" action="<?= BASE_URL ?>/checkout/pay-momo/<?= $orderId ?>">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-rotate"></i> Thanh toán lại</button>
                      </form>
                    <?php elseif ($status === 'Dang_Xu_Ly'): ?>
                      <span><i class="fas fa-circle-info"></i> Cửa hàng đang chuẩn bị đơn hàng của bạn.</span>
                      <?php if (empty($order['ghn_order_code']) && !empty($order['to_ward_code'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/user/retry-ghn/<?= $orderId ?>">
                          <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                          <button class="btn btn-outline" type="submit"><i class="fas fa-rotate"></i> Tạo lại vận đơn GHN</button>
                        </form>
                      <?php endif; ?>
                      <form method="POST" action="<?= BASE_URL ?>/user/cancel-order/<?= $orderId ?>"
                            data-confirm="Bạn có chắc muốn gửi yêu cầu hủy đơn hàng #<?= $orderId ?>?">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                        <button class="btn customer-cancel-order" type="submit"><i class="fas fa-ban"></i> Yêu cầu hủy</button>
                      </form>
                    <?php elseif (in_array($status, ['Yeu_Cau_Huy', 'Yeu_Cau_Tra_Hang'], true)): ?>
                      <span class="cancel-request-pending"><i class="fas fa-clock"></i>
                        <?= $status === 'Yeu_Cau_Huy'
                            ? 'Yêu cầu hủy đang chờ admin xác nhận.'
                            : 'Yêu cầu chưa nhận được hàng/trả hàng đang chờ admin xử lý.' ?>
                      </span>
                    <?php elseif (in_array($status, ['Da_Huy', 'Da_Tra_Hang'], true) && ($order['payment_status'] ?? '') === 'Paid'): ?>
                      <div class="order-refund-notice">
                        <i class="fas fa-money-bill-transfer"></i>
                        <span><strong>Đang hoàn tiền <?= ProductModel::formatPrice((int) ($order['total'] ?? 0)) ?></strong>
                          Số tiền sẽ được hoàn trả cho quý khách trong vòng 24 giờ tới.</span>
                      </div>
                    <?php elseif (in_array($status, ['Da_Huy', 'Da_Tra_Hang'], true)): ?>
                      <span><i class="fas fa-circle-info"></i> Đơn hàng đã hủy/trả và không phát sinh khoản thanh toán cần hoàn lại.</span>
                    <?php elseif ($status === 'Dang_Giao'): ?>
                      <span><i class="fas fa-truck-fast"></i> Đơn hàng đang trên đường giao đến bạn.</span>
                    <?php elseif ($status === 'Da_Nhan_Hang'): ?>
                      <span><i class="fas fa-circle-check"></i> Đơn hàng đã hoàn tất. Bạn có thể đánh giá từng sản phẩm.</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            </tbody>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
