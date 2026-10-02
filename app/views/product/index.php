<?php
/**
 * Biến nhận vào: $products, $brands, $categories, $keyword, $brandId,
 * $categoryId, $saleOnly, $cartCount, $authUser
 */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$keyword = (string) ($keyword ?? '');
$brandId = (int) ($brandId ?? 0);
$categoryId = (int) ($categoryId ?? 0);
$saleOnly = (bool) ($saleOnly ?? false);
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
?>

<section class="page-hero compact-hero reveal">
  <div class="section-container">
    <nav class="breadcrumb reveal-left" aria-label="Đường dẫn"><a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i><span>Sản phẩm</span></nav>
    <h1 class="reveal"><?= $keyword !== '' ? 'Tìm kiếm sản phẩm' : ($saleOnly ? 'Sản phẩm khuyến mãi' : ($brandId > 0 ? 'Sản phẩm theo thương hiệu' : 'Khám phá sản phẩm')) ?></h1>
    <p class="reveal">Tìm đôi giày phù hợp với phong cách và mục tiêu của bạn.</p>
  </div>
</section>

<section class="catalog-section">
  <div class="section-container catalog-layout">
    <aside class="filter-card reveal-left">
      <div class="filter-heading"><h2>Bộ lọc</h2><a href="<?= BASE_URL ?>/product/index">Xóa lọc</a></div>
      <form method="GET" action="<?= BASE_URL ?>/product/index">
        <div class="form-group">
          <label for="catalogKeyword">Tìm kiếm</label>
          <div class="input-with-icon"><i class="fas fa-search"></i><input id="catalogKeyword" name="q" type="search" value="<?= $escape($keyword) ?>" placeholder="Tên sản phẩm..."></div>
        </div>
        <div class="form-group">
          <label for="brandFilter">Thương hiệu</label>
          <select id="brandFilter" name="brand">
            <option value="">Tất cả thương hiệu</option>
            <?php foreach (($brands ?? []) as $brand): ?>
              <option value="<?= (int) $brand['id'] ?>" <?= $brandId === (int) $brand['id'] ? 'selected' : '' ?>><?= $escape($brand['name_brand']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="categoryFilter">Danh mục</label>
          <select id="categoryFilter" name="category">
            <option value="">Tất cả danh mục</option>
            <?php foreach (($categories ?? []) as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= $escape($category['name_category']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="check-row"><input type="checkbox" name="sale" value="1" <?= $saleOnly ? 'checked' : '' ?>><span>Chỉ hiển thị sản phẩm giảm giá</span></label>
        <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-sliders"></i> Áp dụng bộ lọc</button>
      </form>
    </aside>

    <div class="catalog-content">
      <div class="catalog-toolbar reveal">
        <div><span class="section-kicker reveal">Bộ sưu tập</span><h2><?= $keyword !== '' ? 'Kết quả cho “' . $escape($keyword) . '”' : 'Tất cả sản phẩm' ?></h2></div>
        <span class="result-count"><?= count($products ?? []) ?> sản phẩm</span>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty-state reveal"><i class="fas fa-magnifying-glass"></i><h3>Không tìm thấy sản phẩm</h3><p>Hãy thử thay đổi từ khóa hoặc bộ lọc của bạn.</p><a class="btn btn-primary" href="<?= BASE_URL ?>/product/index">Xem tất cả sản phẩm</a></div>
      <?php else: ?>
        <div class="product-grid">
          <?php $pIdx = 0; foreach ($products as $product):
              $pIdx++;
              $discount = (int) ($product['discount'] ?? 0);
              $finalPrice = ProductModel::calcFinalPrice((int) $product['price'], $discount);
              $image = (string) ($product['thumbnail'] ?? '');
          ?>
            <article class="product-card reveal-scale stagger-<?= min($pIdx, 8) ?>">
              <a class="product-img-wrap" href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>">
                <img src="<?= $image ? BASE_URL . '/public/images/products/' . $escape($image) . '?v=' . (is_file(PUBLIC_PATH . '/images/products/' . $image) ? filemtime(PUBLIC_PATH . '/images/products/' . $image) : APP_VERSION) : $placeholder ?>"
                     onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
                     alt="<?= $escape($product['name']) ?>" loading="lazy" decoding="async">
                <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
              </a>
              <div class="product-info">
                <p class="product-meta"><?= $escape($product['name_brand'] ?? 'TrendStyle') ?> · <?= $escape($product['name_category'] ?? 'Thời trang') ?></p>
                <h3 class="product-name"><a href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>"><?= $escape($product['name']) ?></a></h3>
                <div class="product-pricing">
                  <span class="price-val"><?= ProductModel::formatPrice($finalPrice) ?></span>
                  <?php if ($discount > 0): ?><del><?= ProductModel::formatPrice((int) $product['price']) ?></del><?php endif; ?>
                </div>
                <a href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>" class="btn-detail">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
