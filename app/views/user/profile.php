<?php
/** Contract: user,flash,csrfToken,cartCount,authUser. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$user = is_array($user ?? null) ? $user : [];
$flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
$displayName = trim((string) ($user['full_name'] ?? '')) ?: (string) ($user['username'] ?? '');
$initial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($displayName, 0, 1));
?>

<section class="customer-profile-page">
  <div class="section-container">
    <header class="customer-profile-heading reveal">
      <div class="customer-profile-avatar" aria-hidden="true"><?= $escape($initial) ?></div>
      <div>
        <span>Tài khoản của tôi</span>
        <h1><?= $escape($displayName) ?></h1>
        <p>Quản lý thông tin cá nhân và bảo mật tài khoản.</p>
      </div>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/user/orders">
        <i class="fas fa-box"></i> Đơn hàng của tôi
      </a>
    </header>

    <?php if (!empty($flash['msg'])): ?>
      <div class="profile-alert profile-alert-<?= $flashType ?> reveal" role="alert">
        <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <span><?= $escape($flash['msg']) ?></span>
      </div>
    <?php endif; ?>

    <div class="customer-profile-layout">
      <form class="profile-card reveal-left" method="POST" action="<?= BASE_URL ?>/user/update-profile">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <header>
          <span><i class="fas fa-user-pen"></i></span>
          <div><h2>Thông tin cá nhân</h2><p>Cập nhật thông tin liên hệ của bạn.</p></div>
        </header>

        <div class="profile-form-grid">
          <label class="profile-field">
            <span>Tên đăng nhập</span>
            <div class="profile-input is-readonly"><i class="fas fa-at"></i><input value="<?= $escape($user['username'] ?? '') ?>" readonly></div>
            <small>Tên đăng nhập không thể thay đổi.</small>
          </label>

          <label class="profile-field">
            <span>Họ và tên *</span>
            <div class="profile-input"><i class="fas fa-id-card"></i><input name="full_name" maxlength="255" value="<?= $escape($user['full_name'] ?? '') ?>" autocomplete="name" required></div>
          </label>

          <label class="profile-field">
            <span>Email</span>
            <div class="profile-input"><i class="fas fa-envelope"></i><input type="email" name="email" maxlength="255" value="<?= $escape($user['email'] ?? '') ?>" autocomplete="email" placeholder="email@example.com"></div>
          </label>

          <label class="profile-field">
            <span>Số điện thoại</span>
            <div class="profile-input"><i class="fas fa-phone"></i><input type="tel" name="phone" maxlength="20" value="<?= $escape($user['phone'] ?? '') ?>" autocomplete="tel" placeholder="0901234567"></div>
          </label>

          <label class="profile-field profile-field-full">
            <span>Giới tính</span>
            <div class="profile-input"><i class="fas fa-venus-mars"></i>
              <select name="gender">
                <option value="">Chưa chọn</option>
                <?php foreach (['Nam', 'Nữ', 'Khác'] as $gender): ?>
                  <option value="<?= $gender ?>" <?= ($user['gender'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </label>
        </div>

        <footer><button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Lưu thay đổi</button></footer>
      </form>

      <form class="profile-card profile-password-card" id="change-password" method="POST" action="<?= BASE_URL ?>/user/change-password">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <header>
          <span><i class="fas fa-shield-halved"></i></span>
          <div><h2>Đổi mật khẩu</h2><p>Nên sử dụng mật khẩu khó đoán và không dùng lại.</p></div>
        </header>

        <div class="profile-password-fields">
          <label class="profile-field">
            <span>Mật khẩu hiện tại *</span>
            <div class="profile-input"><i class="fas fa-lock"></i><input type="password" name="current_password" autocomplete="current-password" required></div>
          </label>
          <label class="profile-field">
            <span>Mật khẩu mới *</span>
            <div class="profile-input"><i class="fas fa-key"></i><input type="password" name="new_password" minlength="6" maxlength="72" autocomplete="new-password" required></div>
            <small>Từ 6 đến 72 ký tự.</small>
          </label>
          <label class="profile-field">
            <span>Xác nhận mật khẩu mới *</span>
            <div class="profile-input"><i class="fas fa-check"></i><input type="password" name="confirm_password" minlength="6" maxlength="72" autocomplete="new-password" required></div>
          </label>
        </div>

        <footer><button class="btn btn-primary" type="submit"><i class="fas fa-shield-halved"></i> Đổi mật khẩu</button></footer>
      </form>
    </div>
  </div>
</section>
