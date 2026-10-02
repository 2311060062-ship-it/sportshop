<?php
/** Biến nhận vào: $product, $cartCount, $authUser */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$discount = (int) ($product['discount'] ?? 0);
$price = (int) ($product['price'] ?? 0);
$finalPrice = ProductModel::calcFinalPrice($price, $discount);
$stock = max(0, (int) ($product['quantity'] ?? 0));
$images = $product['images'] ?? [];
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
$mainImage = !empty($images[0]['image_link']) ? BASE_URL . '/public/images/products/' . $escape($images[0]['image_link']) . '?v=' . (is_file(PUBLIC_PATH . '/images/products/' . $images[0]['image_link']) ? filemtime(PUBLIC_PATH . '/images/products/' . $images[0]['image_link']) : APP_VERSION) : $placeholder;

// Xử lý danh sách size thời trang
$rawSizes = !empty($product['sizes']) ? $product['sizes'] : 'S, M, L, XL';
$sizes = array_values(array_filter(array_map('trim', explode(',', $rawSizes))));
if (empty($sizes)) {
    $sizes = ['S', 'M', 'L', 'XL'];
}
$defaultSize = $sizes[0] ?? 'M';
?>

<section class="detail-page">
  <div class="section-container">
    <nav class="breadcrumb reveal-left" aria-label="Đường dẫn">
      <a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i>
      <a href="<?= BASE_URL ?>/product/index">Sản phẩm</a><i class="fas fa-chevron-right"></i>
      <span><?= $escape($product['name']) ?></span>
    </nav>

    <div class="detail-card reveal">
      <div class="product-gallery reveal-left">
        <div class="gallery-main">
          <img src="<?= $mainImage ?>" onerror="this.onerror=null;this.src='<?= $placeholder ?>'" alt="<?= $escape($product['name']) ?>">
          <?php if ($discount > 0): ?><span class="discount-badge">Giảm <?= $discount ?>%</span><?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($images as $index => $image): ?>
              <?php $imgLink = $image['image_link'] ?? ''; ?>
              <img class="<?= $index === 0 ? 'active' : '' ?>"
                   src="<?= BASE_URL ?>/public/images/products/<?= $escape($imgLink) ?>?v=<?= is_file(PUBLIC_PATH . '/images/products/' . $imgLink) ? filemtime(PUBLIC_PATH . '/images/products/' . $imgLink) : APP_VERSION ?>"
                   onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
                   alt="Ảnh <?= $index + 1 ?> của <?= $escape($product['name']) ?>">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="detail-info reveal-right">
        <span class="detail-brand"><?= $escape($product['name_brand'] ?? 'TrendStyle') ?></span>
        <h1><?= $escape($product['name']) ?></h1>
        <div class="rating-row"><span class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span><span>Đã bán <?= (int) ($product['quantity_sell'] ?? 0) ?></span></div>
        <div class="detail-price">
          <strong><?= ProductModel::formatPrice($finalPrice) ?></strong>
          <?php if ($discount > 0): ?><del><?= ProductModel::formatPrice($price) ?></del><span>Tiết kiệm <?= ProductModel::formatPrice($price - $finalPrice) ?></span><?php endif; ?>
        </div>
        <p class="detail-description"><?= nl2br($escape($product['description'] ?? 'Sản phẩm thời trang cao cấp, thiết kế hiện đại, chất liệu thoáng mát và tôn dáng hoàn hảo.')) ?></p>

        <dl class="product-specs">
          <div><dt>Thương hiệu</dt><dd><?= $escape($product['name_brand'] ?? 'Đang cập nhật') ?></dd></div>
          <div><dt>Danh mục</dt><dd><?= $escape($product['name_category'] ?? 'Thời trang cao cấp') ?></dd></div>
          <div><dt>Màu sắc</dt><dd><?= $escape($product['color'] ?? 'Đang cập nhật') ?></dd></div>
          <div><dt>Tình trạng</dt><dd class="<?= $stock > 0 ? 'in-stock' : 'out-stock' ?>"><?= $stock > 0 ? 'Còn hàng (' . $stock . ')' : 'Tạm hết hàng' ?></dd></div>
        </dl>

        <!-- ══ CHỌN SIZE THỜI TRANG ═════════════════════════════════ -->
        <div class="product-size-wrapper">
          <div class="product-size-header">
            <label class="product-size-label">
              <i class="fas fa-shirt"></i> Chọn Size: <strong id="currentSelectedSize"><?= $escape($defaultSize) ?></strong>
            </label>
            <button type="button" class="btn-size-guide-toggle" id="openSizeGuideBtn">
              <i class="fas fa-ruler-horizontal"></i> Bảng đo size
            </button>
          </div>
          <div class="size-chips-grid" id="sizeChipsContainer">
            <?php foreach ($sizes as $idx => $s): ?>
              <label class="size-chip <?= $s === $defaultSize ? 'active' : '' ?>">
                <input type="radio" name="selected_shoe_size" value="<?= $escape($s) ?>" <?= $s === $defaultSize ? 'checked' : '' ?>>
                <span><?= $escape($s) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="purchase-box">
          <label for="productQuantity">Số lượng</label>
          <input id="productQuantity" class="quantity-input" type="number" min="1" max="<?= max(1, $stock) ?>" value="1" <?= $stock < 1 ? 'disabled' : '' ?>>
          <?php if ($stock < 1): ?>
            <button class="btn btn-primary add-cart-btn" type="button" disabled>
              <i class="fas fa-cart-plus"></i> Tạm hết hàng
            </button>
          <?php elseif (empty($authUser['id'])): ?>
            <a class="btn btn-primary add-cart-btn" href="<?= BASE_URL ?>/auth/login">
              <i class="fas fa-right-to-bracket"></i> Đăng nhập để thêm vào giỏ
            </a>
          <?php else: ?>
            <button class="btn btn-primary add-cart-btn" type="button"
                    id="detailAddToCartBtn"
                    data-product-id="<?= (int) $product['id'] ?>">
              <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
          <?php endif; ?>
        </div>

        <div class="detail-assurances">
          <span><i class="fas fa-shield-halved"></i> 100% Chính hãng</span>
          <span><i class="fas fa-rotate-left"></i> Đổi trả 30 ngày</span>
          <span><i class="fas fa-truck-fast"></i> Giao hàng hỏa tốc</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ POPUP HƯỚNG DẪN ĐO SIZE QUẦN ÁO THỜI TRANG ═══════════════ -->
<div class="size-guide-modal" id="sizeGuideModal" aria-hidden="true">
  <div class="size-guide-backdrop" id="sizeGuideBackdrop"></div>
  <div class="size-guide-dialog">
    <div class="size-guide-head">
      <div style="display:flex;align-items:center;gap:10px;">
        <i class="fas fa-ruler-combined" style="color:#1677ff;font-size:20px;"></i>
        <h3 style="margin:0;font-size:18px;font-weight:800;color:#0f2b48;">Bảng Quy Đổi Size Quần Áo Chuẩn</h3>
      </div>
      <button type="button" class="size-guide-close" id="closeSizeGuideBtn"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="size-guide-content">
      <p style="margin-top:0;font-size:14px;color:#475569;line-height:1.6;">
        Để chọn được bộ trang phục vừa vặn và tôn dáng nhất, bạn hãy đối chiếu chiều cao & cân nặng theo bảng chuẩn sau:
      </p>
      <div class="table-responsive">
        <table class="size-table">
          <thead>
            <tr>
              <th>Size</th>
              <th>Chiều cao (cm)</th>
              <th>Cân nặng (kg)</th>
              <th>Vòng ngực (cm)</th>
              <th>Vòng eo (cm)</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><strong>S</strong></td><td>1m50 - 1m60</td><td>45 - 53 kg</td><td>82 - 86 cm</td><td>64 - 68 cm</td></tr>
            <tr><td><strong>M</strong></td><td>1m60 - 1m68</td><td>54 - 62 kg</td><td>86 - 90 cm</td><td>68 - 72 cm</td></tr>
            <tr><td><strong>L</strong></td><td>1m68 - 1m75</td><td>63 - 70 kg</td><td>90 - 95 cm</td><td>72 - 77 cm</td></tr>
            <tr><td><strong>XL</strong></td><td>1m75 - 1m82</td><td>71 - 78 kg</td><td>95 - 100 cm</td><td>78 - 84 cm</td></tr>
            <tr><td><strong>XXL</strong></td><td>1m80 - 1m88</td><td>79 - 88 kg</td><td>100 - 106 cm</td><td>85 - 92 cm</td></tr>
          </tbody>
        </table>
      </div>
      <div class="size-guide-tip">
        <i class="fas fa-lightbulb" style="color:#f59e0b;font-size:16px;"></i>
        <span><strong>Gợi ý chọn size:</strong> Nếu số đo của bạn nằm ở khoảng giữa 2 size, hãy chọn <strong>size lớn hơn (+1 size)</strong> nếu thích mặc thoải mái hoặc Oversize, hoặc chọn <strong>size nhỏ hơn</strong> nếu muốn mặc ôm dáng Slimfit! Hoặc hỏi ngay <strong>Trợ lý AI Stylist</strong> ở góc phải màn hình nhé!</span>
      </div>
    </div>
  </div>
</div>

