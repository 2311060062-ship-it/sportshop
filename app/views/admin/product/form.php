<?php
/** Contract: product, brands, categories, errors, old, csrfToken, isEdit, authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$product = is_array($product ?? null) ? $product : [];
$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$brands = is_array($brands ?? null) ? $brands : [];
$categories = is_array($categories ?? null) ? $categories : [];
$isEdit = (bool) ($isEdit ?? false);
$productId = (int) ($product['id'] ?? 0);
$value = static function (string $key, $default = '') use ($old, $product) {
    return array_key_exists($key, $old) ? $old[$key] : ($product[$key] ?? $default);
};
$pageTitle = $isEdit ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm mới';
$adminActive = 'products';
$thumbnail = basename((string) ($product['thumbnail'] ?? ''));
$previewUrl = $thumbnail !== ''
    ? BASE_URL . '/public/images/products/' . rawurlencode($thumbnail)
    : BASE_URL . '/public/images/product-placeholder.svg';
$formAction = $isEdit
    ? BASE_URL . '/admin-product/update/' . $productId
    : BASE_URL . '/admin-product/store';
$isNewCategory = (string) $value('category_id') === '__new__';
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <a class="admin-back-link" href="<?= $adminBaseUrl ?>/admin-product/index">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Quay lại danh sách
    </a>
    <h1><?= $isEdit ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm mới' ?></h1>
    <p><?= $isEdit ? 'Điều chỉnh thông tin, tồn kho và hình ảnh sản phẩm.' : 'Điền đầy đủ thông tin để đưa sản phẩm lên cửa hàng.' ?></p>
  </div>
</section>

<?php if (!empty($errors['general'])): ?>
  <div class="admin-alert admin-alert-error" role="alert">
    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
    <span><?= $adminEscape($errors['general']) ?></span>
  </div>
<?php endif; ?>

<form class="admin-product-form" method="POST" action="<?= $adminEscape($formAction) ?>"
      enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">

  <aside class="admin-card admin-media-card">
    <div class="admin-section-title">
      <i class="fa-regular fa-image" aria-hidden="true"></i>
      <div><h2>Ảnh sản phẩm</h2><p>JPG, PNG hoặc WEBP, tối đa 5MB.</p></div>
    </div>
    <label class="admin-image-upload" for="productImage">
      <img id="adminImagePreview" src="<?= $adminEscape($previewUrl) ?>"
           onerror="this.onerror=null;this.src='<?= $adminBaseUrl ?>/public/images/product-placeholder.svg'"
           alt="Ảnh xem trước sản phẩm">
      <span><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Chọn ảnh tải lên</span>
      <input id="productImage" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php if (!empty($errors['image'])): ?><small class="admin-field-error"><?= $adminEscape($errors['image']) ?></small><?php endif; ?>
    <?php if ($isEdit): ?><p class="admin-form-hint">Nếu không chọn ảnh mới, ảnh hiện tại sẽ được giữ nguyên.</p><?php endif; ?>
  </aside>

  <section class="admin-card admin-details-card">
    <div class="admin-section-title">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      <div><h2>Thông tin sản phẩm</h2><p>Các trường có dấu * là bắt buộc.</p></div>
    </div>

    <div class="admin-form-grid">
      <div class="admin-field">
        <label for="brandId">Thương hiệu *</label>
        <select id="brandId" name="brand_id" class="<?= isset($errors['brand_id']) ? 'is-invalid' : '' ?>" required>
          <option value="">-- Chọn thương hiệu --</option>
          <?php foreach ($brands as $brand): ?>
            <option value="<?= (int) $brand['id'] ?>"
              <?= (int) $value('brand_id') === (int) $brand['id'] ? 'selected' : '' ?>>
              <?= $adminEscape($brand['name_brand']) ?><?= ($brand['status'] ?? '') === 'Closed' ? ' (đang ẩn)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['brand_id'])): ?><small class="admin-field-error"><?= $adminEscape($errors['brand_id']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="categoryId">Danh mục *</label>
        <select id="categoryId" name="category_id" class="<?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>"
              <?= (int) $value('category_id') === (int) $category['id'] ? 'selected' : '' ?>>
              <?= $adminEscape($category['name_category']) ?><?= ($category['status'] ?? '') === 'Closed' ? ' (đang ẩn)' : '' ?>
            </option>
          <?php endforeach; ?>
          <option value="__new__" <?= $isNewCategory ? 'selected' : '' ?>>＋ Thêm danh mục khác</option>
        </select>
        <?php if (isset($errors['category_id'])): ?><small class="admin-field-error"><?= $adminEscape($errors['category_id']) ?></small><?php endif; ?>
        <div class="admin-new-category" id="newCategoryFields" <?= $isNewCategory ? '' : 'hidden' ?>>
          <label for="newCategoryName">Tên danh mục mới *</label>
          <input id="newCategoryName" type="text" name="new_category_name"
                 value="<?= $adminEscape($value('new_category_name')) ?>"
                 maxlength="255" placeholder="Ví dụ: Dép thể thao"
                 <?= $isNewCategory ? 'required' : '' ?>>
          <small>Danh mục mới sẽ được bật và xuất hiện trong bộ lọc cửa hàng.</small>
          <?php if (isset($errors['new_category_name'])): ?><small class="admin-field-error"><?= $adminEscape($errors['new_category_name']) ?></small><?php endif; ?>
        </div>
      </div>

      <div class="admin-field">
        <label for="productName">Tên sản phẩm *</label>
        <input id="productName" type="text" name="name" value="<?= $adminEscape($value('name')) ?>"
               class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" maxlength="255" required>
        <?php if (isset($errors['name'])): ?><small class="admin-field-error"><?= $adminEscape($errors['name']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="productColor">Màu sắc</label>
        <input id="productColor" type="text" name="color" value="<?= $adminEscape($value('color')) ?>" maxlength="255">
        <?php if (isset($errors['color'])): ?><small class="admin-field-error"><?= $adminEscape($errors['color']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="productPrice">Giá bán (VND) *</label>
        <input id="productPrice" type="number" name="price" value="<?= $adminEscape($value('price')) ?>"
               min="1" step="1" class="<?= isset($errors['price']) ? 'is-invalid' : '' ?>" required>
        <?php if (isset($errors['price'])): ?><small class="admin-field-error"><?= $adminEscape($errors['price']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="productDiscount">Giảm giá (%)</label>
        <input id="productDiscount" type="number" name="discount" value="<?= $adminEscape($value('discount', 0)) ?>"
               min="0" max="100" step="1" class="<?= isset($errors['discount']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['discount'])): ?><small class="admin-field-error"><?= $adminEscape($errors['discount']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="productQuantity">Số lượng tồn kho *</label>
        <input id="productQuantity" type="number" name="quantity" value="<?= $adminEscape($value('quantity', 0)) ?>"
               min="0" step="1" class="<?= isset($errors['quantity']) ? 'is-invalid' : '' ?>" required>
        <?php if (isset($errors['quantity'])): ?><small class="admin-field-error"><?= $adminEscape($errors['quantity']) ?></small><?php endif; ?>
      </div>

      <div class="admin-field">
        <label for="productStatus">Trạng thái *</label>
        <select id="productStatus" name="status_product" required>
          <option value="Active" <?= $value('status_product', 'Active') === 'Active' ? 'selected' : '' ?>>Đang bán</option>
          <option value="Closed" <?= $value('status_product', 'Active') === 'Closed' ? 'selected' : '' ?>>Đang ẩn</option>
        </select>
      </div>

      <div class="admin-field admin-field-full">
        <label for="productSizes">Các Size sản phẩm có sẵn (cách nhau bằng dấu phẩy) *</label>
        <input id="productSizes" type="text" name="sizes" value="<?= $adminEscape($value('sizes', 'S, M, L, XL')) ?>"
               placeholder="Ví dụ: S, M, L, XL hoặc 29, 30, 31, 32 hoặc FreeSize" required>
        <small class="admin-form-hint">Nhập các size cách nhau bằng dấu phẩy để hiển thị cho khách chọn mua và hỗ trợ Trợ lý AI Stylist.</small>
      </div>

      <div class="admin-field admin-field-full">
        <label for="productDescription">Mô tả</label>
        <textarea id="productDescription" name="description" rows="5"
                  placeholder="Mô tả chất liệu vải, kiểu dáng, form mặc và hướng dẫn phối đồ..."><?= $adminEscape($value('description')) ?></textarea>
      </div>
    </div>
  </section>

  <div class="admin-form-actions">
    <a class="admin-btn admin-btn-secondary" href="<?= $adminBaseUrl ?>/admin-product/index">Hủy</a>
    <button class="admin-btn admin-btn-primary" type="submit">
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      <?= $isEdit ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?>
    </button>
  </div>
</form>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
