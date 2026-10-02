<?php
/** View: Chỉnh sửa tài khoản người dùng */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$user = is_array($user ?? null) ? $user : [];
$pageTitle = 'Chỉnh sửa tài khoản – ' . ($user['username'] ?? '');
$adminActive = 'users';
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow"><a href="<?= $adminBaseUrl ?>/admin-user/index" style="color:inherit">Tài khoản khách hàng</a> / Chỉnh sửa</span>
    <h1>Chỉnh sửa tài khoản: <?= $adminEscape($user['username'] ?? '') ?></h1>
    <p>Cập nhật thông tin cá nhân, trạng thái hoặc đặt lại mật khẩu cho khách hàng.</p>
  </div>
  <div>
    <a class="admin-btn admin-btn-secondary" href="<?= $adminBaseUrl ?>/admin-user/index">
      <i class="fa-solid fa-arrow-left"></i> Quay lại
    </a>
  </div>
</section>

<?php if (!empty($flash)):
    $flashType = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>">
    <i class="fa-solid <?= $flashType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
    <span><?= $adminEscape($flash['msg'] ?? '') ?></span>
  </div>
<?php endif; ?>

<section class="admin-card">
  <form method="POST" action="<?= $adminBaseUrl ?>/admin-user/edit/<?= (int) ($user['id'] ?? 0) ?>" style="padding: 24px;">
    <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
      <div class="form-group">
        <label>Tên đăng nhập (Username)</label>
        <input type="text" value="<?= $adminEscape($user['username'] ?? '') ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
      </div>

      <div class="form-group">
        <label>Họ và tên</label>
        <input type="text" name="full_name" value="<?= $adminEscape($user['full_name'] ?? '') ?>" placeholder="Nhập họ và tên...">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= $adminEscape($user['email'] ?? '') ?>" placeholder="Nhập địa chỉ email...">
      </div>

      <div class="form-group">
        <label>Số điện thoại</label>
        <input type="text" name="phone" value="<?= $adminEscape($user['phone'] ?? '') ?>" placeholder="Nhập số điện thoại...">
      </div>

      <div class="form-group">
        <label>Giới tính</label>
        <select name="gender">
          <option value="">Chưa chọn</option>
          <option value="Nam" <?= ($user['gender'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
          <option value="Nữ" <?= ($user['gender'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
          <option value="Khác" <?= ($user['gender'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
        </select>
      </div>

      <div class="form-group">
        <label>Trạng thái tài khoản</label>
        <select name="status_user">
          <option value="Active" <?= ($user['status_user'] ?? '') === 'Active' ? 'selected' : '' ?>>Hoạt động (Active)</option>
          <option value="Closed" <?= ($user['status_user'] ?? '') === 'Closed' ? 'selected' : '' ?>>Khóa tài khoản (Closed)</option>
        </select>
      </div>

      <div class="form-group" style="grid-column: 1 / -1;">
        <label>Đặt lại mật khẩu mới (Bỏ trống nếu không muốn đổi)</label>
        <input type="password" name="password" placeholder="Nhập mật khẩu mới cho tài khoản này (nếu cần)...">
      </div>
    </div>

    <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid var(--border, #e2e8f0); padding-top: 20px;">
      <a class="admin-btn admin-btn-secondary" href="<?= $adminBaseUrl ?>/admin-user/index">Hủy bỏ</a>
      <button class="admin-btn admin-btn-primary" type="submit">
        <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
      </button>
    </div>
  </form>
</section>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
