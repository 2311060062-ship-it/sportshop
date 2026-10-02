<?php
/** Biến nhận vào: $cartItems, $total, $user, $error, $cartCount, $authUser, $csrfToken, $qrSettings */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
$old = is_array($old ?? null) ? $old : [];
$fullName = (string) ($old['full_name'] ?? $user['full_name'] ?? $user['fullname'] ?? '');
$phone = (string) ($old['phone'] ?? $user['phone'] ?? '');
$email = (string) ($old['email'] ?? $user['email'] ?? '');
$address = (string) ($old['address'] ?? $user['address'] ?? '');
$qrSettings = is_array($qrSettings ?? null) ? $qrSettings : [];
$gatewayAvailability = is_array($gatewayAvailability ?? null) ? $gatewayAvailability : [];
$availableMethods = ['cod'];
if (!empty($gatewayAvailability['momo'])) $availableMethods[] = 'momo';
$firstPaymentMethod = $availableMethods[0] ?? '';
?>

<section class="page-hero compact-hero reveal">
  <div class="section-container">
    <nav class="breadcrumb reveal-left" aria-label="Đường dẫn"><a href="<?= BASE_URL ?>/">Trang chủ</a><i class="fas fa-chevron-right"></i><a href="<?= BASE_URL ?>/cart/index">Giỏ hàng</a><i class="fas fa-chevron-right"></i><span>Thanh toán</span></nav>
    <h1 class="reveal">Thanh toán</h1>
    <p class="reveal">Hoàn tất thông tin để đặt hàng qua cổng thanh toán an toàn.</p>
  </div>
</section>

<section class="checkout-page">
  <div class="section-container">
    <?php if (!empty($error)): ?>
      <div class="alert alert-error reveal" role="alert"><i class="fas fa-circle-exclamation"></i><span><?= $escape($error) ?></span></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
      <div class="empty-state reveal"><i class="fas fa-cart-shopping"></i><h2>Không có sản phẩm để thanh toán</h2><p>Vui lòng thêm sản phẩm vào giỏ hàng trước.</p><a class="btn btn-primary" href="<?= BASE_URL ?>/product/index">Mua sắm ngay</a></div>
    <?php else: ?>
      <form class="checkout-layout" id="checkoutForm" method="POST" action="<?= BASE_URL ?>/checkout/process" novalidate
            data-province="<?= (int) ($old['province_id'] ?? 0) ?>"
            data-district="<?= (int) ($old['to_district_id'] ?? 0) ?>"
            data-ward="<?= $escape($old['to_ward_code'] ?? '') ?>">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">

        <div class="checkout-main reveal-left">
          <section class="checkout-card">
            <div class="checkout-card-title"><span>1</span><div><h2>Thông tin giao hàng</h2><p>Nhập địa chỉ để chúng tôi giao hàng chính xác.</p></div></div>
            <div class="form-grid">
              <div class="form-group full"><label for="fullName">Họ và tên <em>*</em></label><input id="fullName" name="full_name" type="text" value="<?= $escape($fullName) ?>" autocomplete="name" required></div>
              <div class="form-group"><label for="phone">Số điện thoại <em>*</em></label><input id="phone" name="phone" type="tel" value="<?= $escape($phone) ?>" autocomplete="tel" required></div>
              <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= $escape($email) ?>" autocomplete="email"></div>
              <div class="form-group"><label for="province_select">Tỉnh/Thành <em>*</em></label><select id="province_select" name="province_id" required><option value="">Đang tải tỉnh/thành...</option></select></div>
              <div class="form-group"><label for="district_select">Quận/Huyện <em>*</em></label><select id="district_select" name="to_district_id" required disabled><option value="">Chọn quận/huyện</option></select></div>
              <div class="form-group full"><label for="ward_select">Phường/Xã <em>*</em></label><select id="ward_select" name="to_ward_code" required disabled><option value="">Chọn phường/xã</option></select></div>
              <div class="form-group full"><label for="address">Số nhà, tên đường <em>*</em></label><textarea id="address" name="address" rows="3" autocomplete="street-address" placeholder="Số nhà, tên đường" required><?= $escape($address) ?></textarea></div>
              <div class="form-group full"><label for="note">Ghi chú đơn hàng</label><textarea id="note" name="note" rows="2" placeholder="Lưu ý cho người giao hàng (không bắt buộc)"></textarea></div>
            </div>
          </section>

          <section class="checkout-card">
            <div class="checkout-card-title"><span>2</span><div><h2>Phương thức thanh toán</h2><p>Thanh toán nhanh chóng và bảo mật.</p></div></div>
            <label class="payment-option <?= $firstPaymentMethod === 'cod' ? 'selected' : '' ?>">
                <input type="radio" name="payment_method" value="cod" <?= $firstPaymentMethod === 'cod' ? 'checked' : '' ?> required>
                <span class="cod-logo"><i class="fas fa-truck-fast"></i></span>
                <span><strong>Thanh toán khi nhận hàng (COD)</strong><small>Thanh toán bằng tiền mặt sau khi kiểm tra và nhận hàng</small></span>
                <i class="fas fa-circle-check"></i>
            </label>
            <?php if (!empty($gatewayAvailability['momo'])): ?>
              <label class="payment-option <?= $firstPaymentMethod === 'momo' ? 'selected' : '' ?>">
                <input type="radio" name="payment_method" value="momo" <?= $firstPaymentMethod === 'momo' ? 'checked' : '' ?> required>
                <span class="momo-logo">M</span>
                <span><strong>Ví MoMo</strong><small>Thanh toán thử bằng thẻ ATM trên cổng MoMo sandbox</small></span>
                <i class="fas fa-circle-check"></i>
              </label>
            <?php endif; ?>
            <div class="payment-security"><i class="fas fa-shield-halved"></i> Giao dịch được mã hóa và bảo mật. Sport Shop không lưu thông tin thanh toán.</div>
          </section>
        </div>

        <aside class="summary-card checkout-summary reveal-right">
          <div class="summary-title"><h2>Đơn hàng của bạn</h2><a href="<?= BASE_URL ?>/cart/index">Chỉnh sửa</a></div>
          <div class="checkout-items">
            <?php $ckIdx = 0; foreach ($cartItems as $item):
                $ckIdx++;
                $discount = (int) ($item['discount'] ?? 0);
                $finalPrice = ProductModel::calcFinalPrice((int) $item['price'], $discount);
                $image = (string) ($item['thumbnail'] ?? '');
            ?>
              <div class="checkout-item reveal stagger-<?= min($ckIdx, 6) ?>">
                <div class="checkout-item-image">
                  <img src="<?= $image ? BASE_URL . '/public/images/products/' . $escape($image) : $placeholder ?>"
                       onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
                       alt="<?= $escape($item['name']) ?>">
                  <span><?= (int) $item['quantity'] ?></span>
                </div>
                <div>
                  <strong><?= $escape($item['name']) ?></strong>
                  <small style="display:block;margin-top:2px;color:#6b7280;">
                    <?= !empty($item['color']) ? $escape($item['color']) . ' • ' : '' ?>Size: <strong style="color:#000000;font-weight:800;"><?= $escape($item['size'] ?? '40') ?></strong>
                  </small>
                </div>
                <b><?= ProductModel::formatPrice($finalPrice * (int) $item['quantity']) ?></b>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="summary-divider"></div>
          <div class="summary-row"><span>Tạm tính</span><strong id="goods_total_text"><?= ProductModel::formatPrice((int) $total) ?></strong></div>
          <div class="summary-row"><span>Vận chuyển GHN</span><strong id="shipping_fee_text">Chọn địa chỉ</strong></div>
          <div class="summary-total"><span>Tổng thanh toán</span><strong id="final_total_text"><?= ProductModel::formatPrice((int) $total) ?></strong></div>
          <input type="hidden" id="goods_total_input" value="<?= (int) $total ?>">
          <button class="btn btn-primary btn-block checkout-submit" type="submit"><i class="fas fa-lock"></i> Tiếp tục thanh toán</button>
          <p class="terms-note">Bằng việc đặt hàng, bạn đồng ý với điều khoản mua hàng và chính sách bảo mật của Sport Shop.</p>
        </aside>
      </form>
      <script>
      document.addEventListener('DOMContentLoaded', function () {
        const provinceSelect = document.getElementById('province_select');
        const districtSelect = document.getElementById('district_select');
        const wardSelect = document.getElementById('ward_select');
        const shippingFeeText = document.getElementById('shipping_fee_text');
        const finalTotalText = document.getElementById('final_total_text');
        const goodsInput = document.getElementById('goods_total_input');
        const csrf = document.querySelector('input[name="csrf_token"]');
        if (!provinceSelect || !goodsInput) return;
        const subtotal = parseInt(goodsInput.value, 10) || 0;
        const money = (value) => new Intl.NumberFormat('vi-VN').format(value) + ' đ';
        let feeReady = false;
        const updateTotals = (fee, ready) => {
          feeReady = ready === true;
          shippingFeeText.textContent = feeReady ? money(fee) : 'Chọn địa chỉ';
          finalTotalText.textContent = money(subtotal + (feeReady ? fee : 0));
        };
        const setFieldError = (input, message) => {
          if (!input) return;
          let error = input.parentElement.querySelector('.field-error');
          if (!error) {
            error = document.createElement('small');
            error.className = 'field-error';
            input.insertAdjacentElement('afterend', error);
          }
          error.textContent = message;
          error.hidden = message === '';
          input.classList.toggle('is-invalid', message !== '');
        };
        const phoneValue = (value) => value.replace(/[\s.-]/g, '').replace(/^84/, '0').replace(/^\+84/, '0');
        const validPhone = (value) => /^0(?:3|5|7|8|9)\d{8}$/.test(phoneValue(value));
        fetch('<?= BASE_URL ?>/ghn/provinces')
          .then((res) => res.json())
          .then((res) => {
            if (!res.data) {
              provinceSelect.innerHTML = '<option value="">Không tải được tỉnh/thành</option>';
              return;
            }
            provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành --</option>'
              + res.data.map((p) => `<option value="${p.ProvinceID}">${p.ProvinceName}</option>`).join('');
            const savedProvince = form ? form.dataset.province : '';
            if (savedProvince && savedProvince !== '0') {
              provinceSelect.value = savedProvince;
              provinceSelect.dispatchEvent(new Event('change'));
            }
          })
          .catch(() => { provinceSelect.innerHTML = '<option value="">Lỗi kết nối GHN</option>'; });
        provinceSelect.addEventListener('change', function () {
          districtSelect.innerHTML = '<option value="">Đang tải...</option>';
          districtSelect.disabled = true;
          wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
          wardSelect.disabled = true;
          updateTotals(0);
          if (!this.value) return;
          fetch('<?= BASE_URL ?>/ghn/districts/' + encodeURIComponent(this.value))
            .then((res) => res.json())
            .then((res) => {
              if (!Array.isArray(res.data)) {
                districtSelect.innerHTML = '<option value="">Không tải được quận/huyện</option>';
                return;
              }
              if (res.data.length === 0) {
                districtSelect.innerHTML = '<option value="">Tỉnh này chưa có quận/huyện trên GHN</option>';
                return;
              }
              districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>'
                + res.data.map((d) => `<option value="${d.DistrictID}">${d.DistrictName}</option>`).join('');
              districtSelect.disabled = false;
              const savedDistrict = form ? form.dataset.district : '';
              if (savedDistrict && savedDistrict !== '0' && [...districtSelect.options].some((option) => option.value === savedDistrict)) {
                districtSelect.value = savedDistrict;
                form.dataset.district = '';
                districtSelect.dispatchEvent(new Event('change'));
              }
            })
            .catch(() => { districtSelect.innerHTML = '<option value="">Lỗi kết nối GHN</option>'; });
        });
        districtSelect.addEventListener('change', function () {
          wardSelect.innerHTML = '<option value="">Đang tải...</option>';
          wardSelect.disabled = true;
          updateTotals(0);
          if (!this.value) return;
          fetch('<?= BASE_URL ?>/ghn/wards/' + encodeURIComponent(this.value))
            .then((res) => res.json())
            .then((res) => {
              if (!Array.isArray(res.data)) {
                wardSelect.innerHTML = '<option value="">Không tải được phường/xã</option>';
                return;
              }
              if (res.data.length === 0) {
                wardSelect.innerHTML = '<option value="">Quận này chưa có phường/xã trên GHN</option>';
                return;
              }
              wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>'
                + res.data.map((w) => `<option value="${w.WardCode}">${w.WardName}</option>`).join('');
              wardSelect.disabled = false;
              const savedWard = form ? form.dataset.ward : '';
              if (savedWard && [...wardSelect.options].some((option) => option.value === savedWard)) {
                wardSelect.value = savedWard;
                form.dataset.ward = '';
                wardSelect.dispatchEvent(new Event('change'));
              }
            })
            .catch(() => { wardSelect.innerHTML = '<option value="">Lỗi kết nối GHN</option>'; });
        });
        wardSelect.addEventListener('change', function () {
          if (!this.value || !districtSelect.value) return;
          shippingFeeText.textContent = 'Đang tính cước...';
          fetch('<?= BASE_URL ?>/ghn/fee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.value : '' },
            body: JSON.stringify({ to_district_id: districtSelect.value, to_ward_code: this.value })
          })
            .then((res) => res.json())
            .then((res) => {
              if (res.code === 200 && res.data) {
                updateTotals(parseInt(res.data.total, 10) || 0, true);
                setFieldError(wardSelect, '');
              } else {
                feeReady = false;
                shippingFeeText.textContent = 'Chưa hỗ trợ';
                finalTotalText.textContent = money(subtotal);
              }
            })
            .catch(() => {
              feeReady = false;
              shippingFeeText.textContent = 'Lỗi tính phí';
              finalTotalText.textContent = money(subtotal);
            });
        });
        const form = document.getElementById('checkoutForm');
        form.addEventListener('submit', function (event) {
          const nameInput = document.getElementById('fullName');
          const phoneInput = document.getElementById('phone');
          const emailInput = document.getElementById('email');
          const addressInput = document.getElementById('address');
          const checks = [
            [nameInput, nameInput.value.trim().length >= 2 ? '' : 'Vui lòng nhập họ và tên người nhận.'],
            [phoneInput, validPhone(phoneInput.value) ? '' : 'Số điện thoại phải đủ 10 số và bắt đầu bằng 03, 05, 07, 08 hoặc 09.'],
            [emailInput, emailInput.value.trim() === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim()) ? '' : 'Email không đúng định dạng.'],
            [provinceSelect, provinceSelect.value ? '' : 'Vui lòng chọn tỉnh/thành.'],
            [districtSelect, districtSelect.value ? '' : 'Vui lòng chọn quận/huyện.'],
            [wardSelect, wardSelect.value ? '' : 'Vui lòng chọn phường/xã.'],
            [addressInput, addressInput.value.trim().length >= 5 ? '' : 'Vui lòng nhập số nhà, tên đường.'],
          ];
          let firstInvalid = null;
          checks.forEach(([input, message]) => {
            setFieldError(input, message);
            if (message && !firstInvalid) firstInvalid = input;
          });
          if (!firstInvalid && wardSelect.value && !feeReady) {
            setFieldError(wardSelect, 'Chưa tính được phí vận chuyển cho địa chỉ này.');
            firstInvalid = wardSelect;
          }
          if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
        form.querySelectorAll('input, select, textarea').forEach((input) => {
          input.addEventListener('input', () => setFieldError(input, ''));
        });
      });
      </script>
    <?php endif; ?>
  </div>
</section>
