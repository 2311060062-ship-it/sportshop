<?php
/** Biến nhận vào: $cartItems, $total, $cartCount, $authUser, $csrfToken, $selectedCartIds, $flash */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
$selectedCartIds = is_array($selectedCartIds ?? null) ? array_map('intval', $selectedCartIds) : [];
?>

<section class="page-hero compact-hero reveal">
  <div class="section-container">
    <nav class="breadcrumb reveal-left" aria-label="Đường dẫn"><a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i><span>Giỏ hàng</span></nav>
    <h1 class="reveal">Giỏ hàng của bạn</h1>
    <p class="reveal">Kiểm tra sản phẩm trước khi tiến hành thanh toán.</p>
  </div>
</section>

<section class="cart-page" data-csrf-token="<?= $escape($csrfToken ?? '') ?>">
  <div class="section-container">
    <?php if (!empty($flash)): ?>
      <div class="alert <?= ($flash['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?> reveal">
        <i class="fas <?= ($flash['type'] ?? '') === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <span><?= $escape($flash['msg'] ?? '') ?></span>
      </div>
    <?php endif; ?>
    <?php if (empty($cartItems)): ?>
      <div class="empty-state empty-cart reveal">
        <span class="empty-icon"><i class="fas fa-cart-shopping"></i></span>
        <h2>Giỏ hàng đang trống</h2>
        <p>Hãy khám phá bộ sưu tập và chọn sản phẩm bạn yêu thích.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/product/index"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a>
      </div>
    <?php else: ?>
      <form id="selectedCheckoutForm" class="cart-layout" method="POST" action="<?= BASE_URL ?>/cart/checkout-selected">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <div class="cart-list reveal-left">
          <div class="cart-list-header">
            <label class="cart-select-all"><input id="selectAllCartItems" type="checkbox"> Chọn tất cả</label>
            <span><?= (int) ($cartCount ?? count($cartItems)) ?> sản phẩm</span>
          </div>
          <?php $cIdx = 0; foreach ($cartItems as $item):
              $cIdx++;
              $discount = (int) ($item['discount'] ?? 0);
              $finalPrice = ProductModel::calcFinalPrice((int) $item['price'], $discount);
              $quantity = max(1, (int) $item['quantity']);
              $image = (string) ($item['thumbnail'] ?? '');
          ?>
            <article class="cart-item reveal stagger-<?= min($cIdx, 8) ?>">
              <label class="cart-item-selector" aria-label="Chọn <?= $escape($item['name']) ?>">
                <input class="js-cart-select" type="checkbox" name="cart_ids[]"
                       value="<?= (int) $item['id'] ?>"
                       data-line-total="<?= $finalPrice * $quantity ?>"
                       <?= in_array((int) $item['id'], $selectedCartIds, true) ? 'checked' : '' ?>>
                <span><i class="fas fa-check"></i></span>
              </label>
              <a class="cart-item-image" href="<?= BASE_URL ?>/product/detail/<?= (int) $item['product_id'] ?>">
                <img src="<?= $image ? BASE_URL . '/public/images/products/' . $escape($image) : $placeholder ?>"
                     onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
                     alt="<?= $escape($item['name']) ?>" decoding="async">
              </a>
              <div class="cart-item-info">
                <h3><a href="<?= BASE_URL ?>/product/detail/<?= (int) $item['product_id'] ?>"><?= $escape($item['name']) ?></a></h3>
                <?php
                  $sizeOptions = array_values(array_filter(array_map('trim', explode(',', (string) ($item['sizes'] ?? '')))));
                  $currentSize = trim((string) ($item['size'] ?? ''));
                  if ($currentSize !== '' && !in_array($currentSize, $sizeOptions, true)) {
                      array_unshift($sizeOptions, $currentSize);
                  }
                ?>
                <p class="cart-item-variants">
                  <?php if (!empty($item['color'])): ?><span>Màu: <?= $escape($item['color']) ?></span><?php endif; ?>
                  <label class="cart-size">
                    <span><i class="fas fa-shirt"></i> Size</span>
                    <select class="js-cart-size" data-cart-id="<?= (int) $item['id'] ?>"
                            data-current-size="<?= $escape($currentSize) ?>"
                            aria-label="Size <?= $escape($item['name']) ?>">
                      <?php foreach ($sizeOptions as $sizeOption): ?>
                        <option value="<?= $escape($sizeOption) ?>" <?= $sizeOption === $currentSize ? 'selected' : '' ?>><?= $escape($sizeOption) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                </p>
                <div class="cart-unit-price">
                  <strong><?= ProductModel::formatPrice($finalPrice) ?></strong>
                  <?php if ($discount > 0): ?><del><?= ProductModel::formatPrice((int) $item['price']) ?></del><?php endif; ?>
                </div>
              </div>
              <div class="cart-item-actions">
                <label for="cartQty<?= (int) $item['id'] ?>">Số lượng</label>
                <div class="cart-quantity">
                  <input id="cartQty<?= (int) $item['id'] ?>" type="number"
                         name="quantities[<?= (int) $item['id'] ?>]"
                         min="1" max="<?= max(1, (int) ($item['stock_quantity'] ?? 1)) ?>"
                         value="<?= $quantity ?>" data-unit-price="<?= $finalPrice ?>"
                         aria-label="Số lượng <?= $escape($item['name']) ?>">
                  <button class="icon-btn js-cart-update" type="button"
                          data-cart-id="<?= (int) $item['id'] ?>" data-quantity="<?= $quantity ?>"
                          aria-label="Cập nhật <?= $escape($item['name']) ?>"><i class="fas fa-rotate"></i></button>
                </div>
                <strong class="cart-line-total"><?= ProductModel::formatPrice($finalPrice * $quantity) ?></strong>
                <button class="remove-btn js-cart-remove" type="button"
                        data-cart-id="<?= (int) $item['id'] ?>" data-quantity="<?= $quantity ?>">
                  <i class="fa-solid fa-trash-can"></i> Xóa
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <aside class="summary-card reveal-right">
          <h2>Tóm tắt đơn hàng</h2>
          <div class="summary-row"><span>Đã chọn</span><strong><b id="selectedCartCount">0</b> sản phẩm</strong></div>
          <div class="summary-row"><span>Tạm tính</span><strong id="selectedCartSubtotal"><?= ProductModel::formatPrice((int) $total) ?></strong></div>
          <div class="summary-row"><span>Phí vận chuyển</span><strong class="free-text">Miễn phí</strong></div>
          <div class="summary-divider"></div>
          <div class="summary-total"><span>Tổng cộng</span><strong id="selectedCartTotal"><?= ProductModel::formatPrice((int) $total) ?></strong></div>
          <small>Đã bao gồm thuế (nếu có)</small>
          <button id="checkoutSelectedButton" class="btn btn-primary btn-block" type="submit">
            Thanh toán sản phẩm đã chọn <i class="fas fa-arrow-right"></i>
          </button>
          <a class="continue-link" href="<?= BASE_URL ?>/product/index"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a>
          <div class="secure-note"><i class="fas fa-lock"></i><span><strong>Thanh toán an toàn</strong>Thông tin của bạn luôn được bảo mật.</span></div>
        </aside>
      </form>
    <?php endif; ?>
  </div>
</section>
