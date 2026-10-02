<?php
/**
 * Layout Header – dùng chung cho tất cả trang có layout
 * Biến nhận vào: $pageTitle, $authUser, $cartCount
 */
$pageTitle = $pageTitle ?? 'TrendStyle Fashion';
$authUser  = $authUser  ?? ['id' => null, 'username' => '', 'role' => 'USER'];
$cartCount = $cartCount ?? 0;

// ── Xác định active nav item chính xác ──────────────────────
$currentUrl  = $_SERVER['REQUEST_URI'] ?? '';
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Tách path sạch (bỏ query string)
$currentPath = parse_url($currentUrl, PHP_URL_PATH) ?? '';

// Kiểm tra từng mục nav
$isHome      = (str_ends_with($currentPath, '/SportShop/') || str_ends_with($currentPath, '/SportShop'));
$isBrand     = (str_contains($currentUrl, '/product') && str_contains($queryString, 'brand'));
$isNew       = (str_contains($currentPath, '/product') && !str_contains($queryString, 'brand') && !str_contains($queryString, 'sale'));
$isSale      = (str_contains($currentUrl, '/product') && str_contains($queryString, 'sale'));
$stylePath   = PUBLIC_PATH . '/css/style.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : APP_VERSION;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="TrendStyle Fashion – Thiên đường thời trang phong cách, cao cấp và dẫn đầu xu hướng." />
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= htmlspecialchars($styleVersion, ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* Bọc cả Header dính 1 khối duy nhất - Phong cách Monochrome Thể Thao / Thời Trang Cao Cấp */
    .site-header-sticky {
      position: sticky !important;
      top: 0 !important;
      z-index: 1000 !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08) !important;
    }

    .top-announcement-bar {
      background: #000000;
      color: #ffffff;
      text-align: center;
      padding: 7px 16px;
      font-size: 11px;
      font-weight: 750;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .top-announcement-bar span {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .topbar {
      position: relative !important;
      top: 0 !important;
      background: #ffffff !important;
      border-bottom: 1px solid #e5e5e5 !important;
    }

    .navbar {
      position: relative !important;
      top: 0 !important;
      z-index: 1 !important;
      background: #ffffff !important;
      border-bottom: 1px solid #e5e5e5 !important;
    }

    /* Đệm khoảng cách khi click/cuộn đến section (Giới thiệu, Liên hệ...) */
    section[id],
    .story-section,
    .contact-section {
      scroll-margin-top: 140px !important;
    }

    /* Highlight active khi cuộn */
    .nav-link.js-active {
      border-bottom-color: #000000 !important;
      color: #000000 !important;
      background: transparent !important;
      box-shadow: inset 0 -3px 0 #000000 !important;
    }
  </style>
</head>
<body class="site-body">

<!-- ══ STICKY HEADER WRAPPER ═════════════════════════════════ -->
<header class="site-header-sticky">

  <!-- Top Announcement Bar -->
  <div class="top-announcement-bar">
    <span><i class="fa-solid fa-truck-fast"></i> GIAO HÀNG MIỄN PHÍ TOÀN QUỐC CHO ĐƠN TỪ 499.000Đ • ĐỔI TRẢ 30 NGÀY</span>
  </div>

  <!-- Topbar -->
  <div class="topbar" role="banner">
    <div class="topbar-inner">

    <!-- Logo with Animated Mascot -->
    <a href="<?= BASE_URL ?>/" class="logo" aria-label="TrendStyle Fashion – Trang chủ">
      <div class="logo-pet-mascot" aria-hidden="true">
        <svg class="logo-pet-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="50" cy="50" r="46" fill="#000000" stroke="#ffffff" stroke-width="2"/>
          <!-- Tai Pet -->
          <path d="M26 32C22 18 32 10 40 20L36 34" fill="#ffffff"/>
          <path d="M74 32C78 18 68 10 60 20L64 34" fill="#ffffff"/>
          <path d="M29 30C26 21 32 17 37 23" fill="#f87171"/>
          <path d="M71 30C74 21 68 17 63 23" fill="#f87171"/>
          <!-- Đầu & Má -->
          <circle cx="50" cy="52" r="28" fill="#ffffff"/>
          <!-- Mũ beret thời trang -->
          <path d="M24 38C32 30 68 30 76 38L74 44C66 40 34 40 26 44Z" fill="#000000"/>
          <circle cx="50" cy="31" r="3" fill="#f87171"/>
          <!-- Mắt -->
          <circle cx="40" cy="52" r="4.5" fill="#000000"/>
          <circle cx="41.5" cy="50.5" r="1.5" fill="#ffffff"/>
          <circle cx="60" cy="52" r="4.5" fill="#000000"/>
          <circle cx="61.5" cy="50.5" r="1.5" fill="#ffffff"/>
          <!-- Mũi & Miệng -->
          <polygon points="50,57 47,54 53,54" fill="#000000"/>
          <path d="M47 58Q50 61 53 58" stroke="#000000" stroke-width="1.5" stroke-linecap="round" fill="none"/>
          <ellipse cx="35" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
          <ellipse cx="65" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
          <!-- Nơ cổ thời trang -->
          <polygon points="50,68 42,63 42,73" fill="#f87171"/>
          <polygon points="50,68 58,63 58,73" fill="#f87171"/>
          <circle cx="50" cy="68" r="2.5" fill="#ffffff"/>
        </svg>
      </div>
      <span class="logo-text">TrendStyle</span>
    </a>

    <!-- Search -->
    <form class="search-bar" method="GET" action="<?= BASE_URL ?>/product/index" role="search">
      <input type="text" name="q"
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             placeholder="Tìm áo polo, sơ mi, đầm váy, quần jean, blazer..."
             aria-label="Tìm kiếm sản phẩm thời trang" />
      <button type="submit" aria-label="Tìm">
        <i class="fas fa-search"></i> Tìm
      </button>
    </form>

    <!-- Actions -->
    <div class="topbar-actions">

      <!-- User dropdown -->
      <div class="user-dropdown" id="userDropdown">
        <button class="topbar-btn" id="userBtn"
                onclick="toggleDropdown()"
                aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-user"></i>
          <span><?= $authUser['id'] ? htmlspecialchars($authUser['username']) : 'Tài Khoản' ?></span>
          <i class="fas fa-caret-down" style="font-size:11px"></i>
        </button>
        <div class="dropdown-menu" id="dropdownMenu" role="menu">
          <?php if ($authUser['id']): ?>
            <a href="<?= BASE_URL ?>/user/profile" class="dropdown-item" role="menuitem">
              <i class="fas fa-id-card"></i> Tài Khoản
            </a>
            <a href="<?= BASE_URL ?>/user/orders" class="dropdown-item" role="menuitem">
              <i class="fas fa-box"></i> Đơn Hàng
            </a>
            <?php if (($authUser['role'] ?? '') === 'ADMIN'): ?>
            <div class="dropdown-divider"></div>
            <a href="<?= BASE_URL ?>/admin-dashboard/index" class="dropdown-item" role="menuitem">
              <i class="fas fa-tachometer-alt"></i> Quản Trị
            </a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a href="<?= BASE_URL ?>/auth/logout" class="dropdown-item" role="menuitem">
              <i class="fas fa-sign-out-alt"></i> Đăng Xuất
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login" class="dropdown-item" role="menuitem">
              <i class="fas fa-sign-in-alt"></i> Đăng Nhập
            </a>
            <a href="<?= BASE_URL ?>/auth/register" class="dropdown-item" role="menuitem">
              <i class="fas fa-user-plus"></i> Đăng Ký
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cart -->
      <a href="<?= BASE_URL ?>/cart/index" class="topbar-btn" aria-label="Giỏ hàng">
        <i class="fas fa-shopping-cart"></i> Giỏ hàng
        <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
      </a>

    </div>
  </div>
</div>


<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar" id="mainNav" aria-label="Điều hướng chính">
  <div class="navbar-inner">

    <a href="<?= BASE_URL ?>/"
       class="nav-link <?= $isHome ? 'active' : '' ?>"
       data-section="top"
       id="nav-home">
      <i class="fas fa-home"></i> Trang Chủ
    </a>

    <a href="<?= BASE_URL ?>/product/index?brand=1"
       class="nav-link <?= $isBrand ? 'active' : '' ?>"
       id="nav-brand">
      <i class="fas fa-store"></i> Thương Hiệu
    </a>

    <a href="<?= BASE_URL ?>/product/index"
       class="nav-link <?= $isNew ? 'active' : '' ?>"
       id="nav-new">
      <i class="fas fa-star"></i> Mới Nhất
    </a>

    <a href="<?= BASE_URL ?>/product/index?sale=1"
       class="nav-link <?= $isSale ? 'active' : '' ?>"
       id="nav-sale">
      <i class="fas fa-percent"></i> Khuyến Mãi
    </a>

    <!-- Cuộn đến section trong trang chủ -->
    <a href="<?= BASE_URL ?>/#about"
       class="nav-link"
       data-scroll="about"
       id="nav-about">
      <i class="fas fa-info-circle"></i> Giới Thiệu
    </a>

    <a href="<?= BASE_URL ?>/#contact"
       class="nav-link"
       data-scroll="contact"
       id="nav-contact">
      <i class="fas fa-phone"></i> Liên Hệ
    </a>

  </div>
</nav>
</header> <!-- /.site-header-sticky -->


<!-- Main content starts here -->
<main id="mainContent">

<script>
/* ── Smooth scroll + active highlight khi lướt ──────────── */
(function () {
  // Xử lý click vào link anchor (Giới Thiệu, Liên Hệ)
  document.querySelectorAll('[data-scroll]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var sectionId = this.getAttribute('data-scroll');
      var target = document.getElementById(sectionId);

      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Cập nhật URL mà không reload
        history.pushState(null, '', '#' + sectionId);
      }
    });
  });

  // Nếu URL có hash khi load trang → cuộn đúng vị trí sau 300ms
  if (window.location.hash) {
    var hash = window.location.hash.slice(1);
    setTimeout(function () {
      var el = document.getElementById(hash);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 300);
  }

  // IntersectionObserver: highlight nav khi section vào viewport
  var sections = document.querySelectorAll('section[id]');
  if (!sections.length) return;

  var navMap = {
    'newProducts': 'nav-new',
    'hotProducts': 'nav-new',
    'about':       'nav-about',
    'contact':     'nav-contact',
  };

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        // Bỏ js-active khỏi tất cả
        document.querySelectorAll('.nav-link').forEach(function (n) {
          n.classList.remove('js-active');
        });
        // Thêm vào nav tương ứng
        var navId = navMap[entry.target.id];
        if (navId) {
          var navEl = document.getElementById(navId);
          if (navEl) navEl.classList.add('js-active');
        }
      }
    });
  }, {
    rootMargin: '-30% 0px -60% 0px',
    threshold: 0
  });

  sections.forEach(function (s) { observer.observe(s); });
})();
</script>

