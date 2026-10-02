<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập quản trị – TrendStyle Fashion</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-login-page">
  <main class="admin-login-shell">
    <section class="admin-login-intro">
      <a class="admin-login-brand" href="<?= BASE_URL ?>/">
        <img src="<?= BASE_URL ?>/public/images/sport_logo_2026.jpg" alt="TrendStyle Fashion Logo">
        <span><strong>TrendStyle</strong><small>Administration Portal</small></span>
      </a>
      <div>
        <span class="admin-eyebrow">Khu vực nội bộ</span>
        <h1>Quản lý cửa hàng<br>trên một giao diện riêng.</h1>
        <p>Cổng này chỉ dành cho quản trị viên. Khách hàng đăng nhập tại trang cửa hàng và không vào được khu vực này.</p>
      </div>
      <a class="admin-login-store-link" href="<?= BASE_URL ?>/">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Về trang khách hàng
      </a>
    </section>

    <section class="admin-login-panel">
      <form class="admin-login-card" method="POST" action="<?= BASE_URL ?>/admin-auth/login">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <div class="admin-login-heading">
          <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
          <div><h2>Đăng nhập ADMIN</h2><p>Sử dụng tài khoản có quyền quản trị.</p></div>
        </div>

        <?php if (!empty($errors['general'])): ?>
          <div class="admin-alert admin-alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span><?= $escape($errors['general']) ?></span>
          </div>
        <?php endif; ?>

        <label class="admin-login-field" for="adminUsername">
          <span>Tài khoản quản trị</span>
          <div><i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            <input id="adminUsername" name="username" type="text"
                   value="<?= $escape($old['username'] ?? '') ?>" autocomplete="username" required autofocus>
          </div>
        </label>

        <label class="admin-login-field" for="adminPassword">
          <span>Mật khẩu</span>
          <div><i class="fa-solid fa-lock" aria-hidden="true"></i>
            <input id="adminPassword" name="password" type="password"
                   autocomplete="current-password" required>
          </div>
        </label>

        <button class="admin-btn admin-btn-primary admin-login-submit" type="submit">
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Vào trang quản trị
        </button>
        <p class="admin-login-note"><i class="fa-solid fa-circle-info"></i> Tài khoản khách hàng không thể đăng nhập tại đây.</p>
      </form>
    </section>
  </main>
</body>
</html>
