<?php
/** Contract: order,items,reviews,flash,csrfToken,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$order = is_array($order ?? null) ? $order : [];
$items = is_array($items ?? null) ? $items : [];
$reviews = is_array($reviews ?? null) ? $reviews : [];
$orderId = (int) ($order['id'] ?? 0);
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
$date = strtotime((string) ($order['date'] ?? ''));
$status = (string) ($order['status_order'] ?? '');
$canReview = $status === 'Da_Nhan_Hang';
$statusLabels = [
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Yeu_Cau_Huy' => 'Yêu cầu hủy',
    'Dang_Giao' => 'Đang giao',
    'Da_Giao' => 'Đã giao - chờ xác nhận',
    'Da_Nhan_Hang' => 'Đã nhận hàng',
    'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
    'Da_Tra_Hang' => 'Đã trả hàng',
    'Da_Huy' => 'Đã hủy',
    'Cho_Thanh_Toan' => 'Chờ thanh toán',
];
$statusGroups = [
    'Dang_Xu_Ly' => 'processing',
    'Yeu_Cau_Huy' => 'processing',
    'Dang_Giao' => 'shipping',
    'Da_Giao' => 'delivered',
    'Da_Nhan_Hang' => 'delivered',
    'Yeu_Cau_Tra_Hang' => 'delivered',
    'Da_Huy' => 'cancelled',
    'Da_Tra_Hang' => 'cancelled',
];
$listUrl = BASE_URL . '/user/orders?status=' . ($statusGroups[$status] ?? 'processing');
$statusLabel = $statusLabels[$status] ?? 'Đơn hàng';
?>

<section class="customer-order-detail-page">
  <div class="section-container">
    <nav class="breadcrumb reveal-left" aria-label="Đường dẫn">
      <a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i>
      <a href="<?= $escape($listUrl) ?>">Lịch sử mua hàng</a><i class="fas fa-chevron-right"></i>
      <span>Đơn #<?= $orderId ?></span>
    </nav>

    <header class="order-detail-heading reveal">
      <div>
        <a class="store-back-link" href="<?= $escape($listUrl) ?>" style="margin-bottom:10px;">
          <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
        </a>
        <span class="section-kicker"><?= $escape($statusLabel) ?></span>
        <h1>Chi tiết đơn hàng #<?= $orderId ?></h1>
        <p>Đặt lúc <?= $date ? date('H:i, d/m/Y', $date) : '—' ?></p>
      </div>
      <span class="customer-order-status status-<?= strtolower($escape($status)) ?>"><?= $escape($statusLabel) ?></span>
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

    <div class="order-detail-layout">
      <main class="order-detail-products reveal-left">
        <?php foreach ($items as $item):
            $productId = (int) $item['product_id'];
            $review = $reviews[$productId] ?? null;
            $thumbnail = trim((string) ($item['thumbnail'] ?? ''));
            $imageUrl = $thumbnail
                ? BASE_URL . '/public/images/products/' . rawurlencode(basename($thumbnail))
                : $placeholder;
        ?>
          <article class="delivered-product-card reveal-scale" id="product-<?= $productId ?>">
            <div class="delivered-product-info">
              <img src="<?= $escape($imageUrl) ?>"
                   onerror="this.onerror=null;this.src='<?= $escape($placeholder) ?>'"
                   alt="<?= $escape($item['name'] ?? 'Sản phẩm') ?>">
              <div>
                <h2><?= $escape($item['name'] ?? 'Sản phẩm') ?></h2>
                <p style="margin:3px 0 6px;color:#64748b;font-size:13px;">
                  <?php if (!empty($item['color'])): ?><span>Màu: <?= $escape($item['color']) ?></span> • <?php endif; ?>
                  <span style="color:#1677ff;font-weight:700;">Size: <?= $escape($item['size'] ?? 'L') ?></span>
                </p>
                <span>Số lượng: <?= (int) ($item['quantity'] ?? 0) ?></span>
              </div>
              <strong><?= ProductModel::formatPrice((int) ($item['total'] ?? 0)) ?></strong>
            </div>

            <?php if ($canReview && $review): ?>
              <div class="submitted-review">
                <div>
                  <strong>Đánh giá của bạn</strong>
                  <span class="review-stars" aria-label="<?= (int) $review['rate'] ?> sao">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                      <i class="<?= $star <= (int) $review['rate'] ? 'fas' : 'far' ?> fa-star"></i>
                    <?php endfor; ?>
                  </span>
                </div>
                <p><?= nl2br($escape($review['messages'] ?? '')) ?></p>
                <?php if (!empty($review['admin_reply'])): ?>
                  <div class="shop-review-reply"><strong>TrendStyle phản hồi:</strong> <?= nl2br($escape($review['admin_reply'])) ?></div>
                <?php endif; ?>
              </div>
            <?php elseif ($canReview): ?>
              <form class="product-review-form" method="POST"
                    action="<?= BASE_URL ?>/user/submit-review/<?= $orderId ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                <input type="hidden" name="product_id" value="<?= $productId ?>">
                <div class="review-form-heading">
                  <div><strong>Đánh giá sản phẩm</strong><small>Chia sẻ trải nghiệm của bạn về sản phẩm này.</small></div>
                  <div class="star-rating" aria-label="Chọn số sao">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                      <input id="rating-<?= $productId ?>-<?= $star ?>" type="radio" name="rate" value="<?= $star ?>" required>
                      <label for="rating-<?= $productId ?>-<?= $star ?>" title="<?= $star ?> sao"><i class="fas fa-star"></i></label>
                    <?php endfor; ?>
                  </div>
                </div>
                <textarea name="messages" rows="3" maxlength="2000"
                          placeholder="Nhập nhận xét về chất lượng, kích cỡ, trải nghiệm sử dụng..." required></textarea>
                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Gửi đánh giá</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </main>

      <aside class="order-detail-summary">
        <h2>Thông tin đơn hàng</h2>
        <dl>
          <div><dt>Người nhận</dt><dd><?= $escape($authUser['fullname'] ?? $authUser['username'] ?? '') ?></dd></div>
          <div><dt>Số điện thoại</dt><dd><?= $escape($order['phone'] ?? '—') ?></dd></div>
          <div><dt>Địa chỉ giao hàng</dt><dd><?= $escape($order['address'] ?? '—') ?></dd></div>
          <div><dt>Phí vận chuyển</dt><dd><?= ProductModel::formatPrice((int) ($order['ghn_total_fee'] ?? 0)) ?></dd></div>
          <?php if (!empty($order['ghn_order_code'])): ?>
            <div><dt>Mã vận đơn GHN</dt><dd><?= $escape($order['ghn_order_code']) ?></dd></div>
            <div><dt>Trạng thái GHN</dt><dd><?= $escape(GhnService::statusLabel((string) ($order['shipping_status'] ?? ''))) ?></dd></div>
          <?php endif; ?>
          <div><dt>Số sản phẩm</dt><dd><?= (int) ($order['quantity_product'] ?? 0) ?></dd></div>
        </dl>
        <div class="order-detail-total"><span>Tổng thanh toán</span><strong><?= ProductModel::formatPrice((int) ($order['total'] ?? 0)) ?></strong></div>
        <?php if (($order['status_order'] ?? '') === 'Cho_Thanh_Toan'): ?>
          <form method="POST" action="<?= BASE_URL ?>/checkout/pay-momo/<?= $orderId ?>">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
            <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-wallet"></i> Thanh toán lại bằng MoMo</button>
          </form>
        <?php endif; ?>
        <a class="btn btn-outline btn-block" href="<?= $escape($listUrl) ?>">
          <i class="fas fa-arrow-left"></i> Quay lại lịch sử
        </a>
      </aside>
    </div>
  </div>
</section>
