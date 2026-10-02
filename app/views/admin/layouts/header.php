<?php
/**
 * Layout độc lập cho khu vực quản trị.
 * Biến nhận vào: $pageTitle, $authUser, $adminActive.
 */
$adminEscape = $adminEscape ?? static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = (string) ($pageTitle ?? 'Quản trị sản phẩm – TrendStyle Fashion');
$authUser = is_array($authUser ?? null) ? $authUser : [];
$adminActive = (string) ($adminActive ?? 'products');
$adminBaseUrl = $adminEscape(BASE_URL);
$adminUsername = (string) ($authUser['username'] ?? 'A');
$adminInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($adminUsername, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($adminUsername, 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $adminEscape($pageTitle) ?></title>
  <meta name="description" content="Khu vực quản trị TrendStyle Fashion.">
  <link rel="stylesheet" href="<?= $adminBaseUrl ?>/public/css/style.css?v=<?= is_file(PUBLIC_PATH . '/css/style.css') ? filemtime(PUBLIC_PATH . '/css/style.css') : APP_VERSION ?>">
  <link rel="stylesheet" href="<?= $adminBaseUrl ?>/public/css/admin.css?v=<?= is_file(PUBLIC_PATH . '/css/admin.css') ? filemtime(PUBLIC_PATH . '/css/admin.css') : APP_VERSION ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<div class="admin-shell">
  <div class="admin-sidebar-overlay" data-admin-sidebar-close></div>

  <aside class="admin-sidebar" id="adminSidebar" aria-label="Điều hướng quản trị">
    <a class="admin-brand" href="<?= $adminBaseUrl ?>/admin-dashboard/index">
      <div class="logo-pet-mascot" aria-hidden="true" style="width:36px;height:36px;background:#000;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg class="logo-pet-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;">
          <circle cx="50" cy="50" r="46" fill="#000000" stroke="#ffffff" stroke-width="2"/>
          <path d="M26 32C22 18 32 10 40 20L36 34" fill="#ffffff"/>
          <path d="M74 32C78 18 68 10 60 20L64 34" fill="#ffffff"/>
          <path d="M29 30C26 21 32 17 37 23" fill="#f87171"/>
          <path d="M71 30C74 21 68 17 63 23" fill="#f87171"/>
          <circle cx="50" cy="52" r="28" fill="#ffffff"/>
          <path d="M24 38C32 30 68 30 76 38L74 44C66 40 34 40 26 44Z" fill="#000000"/>
          <circle cx="50" cy="31" r="3" fill="#f87171"/>
          <circle cx="40" cy="52" r="4.5" fill="#000000"/>
          <circle cx="41.5" cy="50.5" r="1.5" fill="#ffffff"/>
          <circle cx="60" cy="52" r="4.5" fill="#000000"/>
          <circle cx="61.5" cy="50.5" r="1.5" fill="#ffffff"/>
          <polygon points="50,57 47,54 53,54" fill="#000000"/>
          <path d="M47 58Q50 61 53 58" stroke="#000000" stroke-width="1.5" stroke-linecap="round" fill="none"/>
          <ellipse cx="35" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
          <ellipse cx="65" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
        </svg>
      </div>
      <span><strong>TrendStyle</strong><small>Admin workspace</small></span>
    </a>

    <nav class="admin-nav">
      <p class="admin-nav-label">Quản lý</p>
      <a class="admin-nav-link <?= $adminActive === 'dashboard' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-dashboard/index"
         <?= $adminActive === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        <span>Dashboard</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'users' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-user/index"
         <?= $adminActive === 'users' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <span>Người dùng</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'products' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-product/index"
         <?= $adminActive === 'products' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>
        <span>Sản phẩm</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'reviews' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-review/index"
         <?= $adminActive === 'reviews' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-comments" aria-hidden="true"></i>
        <span>Đánh giá</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'orders' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-order/index"
         <?= $adminActive === 'orders' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
        <span>Đơn hàng</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'payments' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-payment/index"
         <?= $adminActive === 'payments' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
        <span>Thanh toán QR</span>
      </a>
      <a class="admin-nav-link <?= $adminActive === 'revenue' ? 'is-active' : '' ?>"
         href="<?= $adminBaseUrl ?>/admin-revenue/index"
         <?= $adminActive === 'revenue' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
        <span>Doanh thu</span>
      </a>
    </nav>

    <div class="admin-sidebar-footer">
      <a class="admin-nav-link" href="<?= $adminBaseUrl ?>/">
        <i class="fa-solid fa-store" aria-hidden="true"></i>
        <span>Về cửa hàng</span>
      </a>
      <a class="admin-nav-link admin-nav-link-danger" href="<?= $adminBaseUrl ?>/admin-auth/logout">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
        <span>Đăng xuất</span>
      </a>
    </div>
  </aside>

  <div class="admin-workspace">
    <header class="admin-topbar">
      <button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle
              aria-controls="adminSidebar" aria-expanded="false" aria-label="Mở menu quản trị">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
      <div class="admin-topbar-title">
        <span>Trang quản trị</span>
        <strong><?= $adminEscape($pageTitle) ?></strong>
      </div>
      <div class="admin-user">
        <span class="admin-user-avatar" aria-hidden="true">
          <?= $adminEscape($adminInitial) ?>
        </span>
        <span>
          <strong><?= $adminEscape($authUser['username'] ?? 'Quản trị viên') ?></strong>
          <small><?= $adminEscape($authUser['role'] ?? 'ADMIN') ?></small>
        </span>
      </div>
    </header>

    <main class="admin-main" id="adminMain">
