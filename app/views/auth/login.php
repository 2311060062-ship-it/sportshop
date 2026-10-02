<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng Nhập – TrendStyle Fashion</title>
  <meta name="description" content="Đăng nhập tài khoản TrendStyle Fashion chính hãng." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #0b0c10;
      --card-bg: rgba(255, 255, 255, 0.96);
      --primary: #000000;
      --primary-hover: #1f1f1f;
      --text: #111111;
      --muted: #6b7280;
      --border: #e5e7eb;
      --focus-ring: rgba(0, 0, 0, 0.12);
      --danger: #ef4444;
      --success: #10b981;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      background: #090a0f;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      position: relative;
      overflow-x: hidden;
      color: var(--text);
    }

    /* ══ LUNG LINH SỐNG ĐỘNG: NỀN AMBIENT GLOW & THỂ THAO ═════ */
    .ambient-glow-1 {
      position: absolute;
      width: 550px;
      height: 550px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 70%);
      top: -150px;
      left: -150px;
      filter: blur(50px);
      pointer-events: none;
      animation: ambientFloat 14s infinite alternate ease-in-out;
    }

    .ambient-glow-2 {
      position: absolute;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
      bottom: -150px;
      right: -150px;
      filter: blur(60px);
      pointer-events: none;
      animation: ambientFloat 18s infinite alternate-reverse ease-in-out;
    }

    .bg-grid-pattern {
      position: absolute;
      inset: 0;
      background-image: 
        radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
      background-size: 32px 32px;
      opacity: 0.25;
      pointer-events: none;
    }

    @keyframes ambientFloat {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(40px, 60px) scale(1.1); }
      100% { transform: translate(-30px, 30px) scale(0.95); }
    }

    /* ══ NÚT QUAY LẠI TRANG CHỦ ═════════════════════════════════ */
    .top-nav {
      position: absolute;
      top: 24px;
      left: 24px;
      z-index: 10;
    }

    .btn-home-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: 50px;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.16);
      color: #ffffff;
      font-size: 12.5px;
      font-weight: 750;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.25s ease;
    }

    .btn-home-back:hover {
      background: #ffffff;
      color: #000000;
      transform: translateX(-4px);
      box-shadow: 0 4px 16px rgba(255, 255, 255, 0.2);
    }

    /* ══ MAIN AUTH WRAPPER ══════════════════════════════════════ */
    .auth-wrapper {
      width: 100%;
      max-width: 440px;
      position: relative;
      z-index: 5;
      margin-top: 50px;
    }

    /* ══ ANIMATED BRAND MASCOT (CHÚ BÁO CHEETAH / PUMA THỂ THAO) ═ */
    .mascot-container {
      width: 150px;
      height: 130px;
      margin: 0 auto -20px;
      position: relative;
      z-index: 6;
      display: flex;
      justify-content: center;
      align-items: flex-end;
    }

    .mascot-svg {
      width: 140px;
      height: 140px;
      overflow: visible;
      filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.45));
    }

    /* Micro animations for Mascot */
    .mascot-head {
      transform-origin: center bottom;
      animation: mascotBreathe 3.5s infinite ease-in-out;
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .mascot-ear-l, .mascot-ear-r {
      transform-origin: center;
      transition: transform 0.25s ease;
    }

    .mascot-eye-pupil {
      transition: transform 0.15s ease-out;
    }

    .mascot-paw-l, .mascot-paw-r {
      transition: transform 0.38s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
      transform-origin: bottom center;
    }

    /* Trạng thái mặc định: 2 tay giấu phía dưới */
    .mascot-paw-l { transform: translateY(48px) scale(0.7); opacity: 0; }
    .mascot-paw-r { transform: translateY(48px) scale(0.7); opacity: 0; }

    /* Trạng thái Che Mắt khi gõ Mật khẩu (Peek-a-boo Mode) */
    .mascot-container.covering-eyes .mascot-paw-l {
      transform: translateY(0px) scale(1);
      opacity: 1;
    }
    .mascot-container.covering-eyes .mascot-paw-r {
      transform: translateY(0px) scale(1);
      opacity: 1;
    }

    /* Khi hé mắt nhìn qua khe tay */
    .mascot-container.peeking .mascot-paw-r {
      transform: translateY(8px) translateX(6px) rotate(15deg) scale(0.95);
    }

    /* Khi đang gõ tài khoản (Looking down at input) */
    .mascot-container.looking-down .mascot-eye-pupil {
      transform: translateY(3.5px);
    }

    @keyframes mascotBreathe {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-3px) rotate(1deg); }
    }

    /* ══ AUTH CARD ══════════════════════════════════════════════ */
    .auth-card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 36px 32px 32px;
      box-shadow: 
        0 24px 70px rgba(0, 0, 0, 0.45),
        0 0 0 1px rgba(255, 255, 255, 0.18),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(20px);
      position: relative;
      animation: cardPopIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes cardPopIn {
      from { opacity: 0; transform: translateY(28px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .brand-header {
      text-align: center;
      margin-bottom: 26px;
    }

    .brand-logo-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 18px;
      font-weight: 900;
      color: #000000;
      letter-spacing: -0.5px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .brand-header h1 {
      font-size: 24px;
      font-weight: 900;
      color: #000000;
      letter-spacing: -0.5px;
      text-transform: uppercase;
    }

    .brand-header p {
      font-size: 13px;
      color: var(--muted);
      margin-top: 4px;
      font-weight: 500;
    }

    /* ══ FORM CONTROLS ══════════════════════════════════════════ */
    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12.5px;
      font-weight: 800;
      color: #000000;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 8px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon-left {
      position: absolute;
      left: 16px;
      color: #9ca3af;
      font-size: 15px;
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .form-control {
      width: 100%;
      height: 48px;
      padding: 0 46px 0 44px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: #f8fafc;
      font-size: 14px;
      font-family: inherit;
      font-weight: 600;
      color: #111111;
      outline: none;
      transition: all 0.22s ease;
    }

    .form-control::placeholder {
      color: #9ca3af;
      font-weight: 500;
    }

    .form-control:focus {
      background: #ffffff;
      border-color: #000000;
      box-shadow: 0 0 0 4px var(--focus-ring);
    }

    .form-control:focus + .input-icon-left {
      color: #000000;
    }

    .btn-toggle-pwd {
      position: absolute;
      right: 14px;
      background: none;
      border: none;
      color: #9ca3af;
      cursor: pointer;
      font-size: 15px;
      padding: 6px;
      display: grid;
      place-items: center;
      transition: color 0.2s;
    }

    .btn-toggle-pwd:hover {
      color: #000000;
    }

    .invalid-feedback {
      color: var(--danger);
      font-size: 12px;
      font-weight: 600;
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* ══ ALERTS ═════════════════════════════════════════════════ */
    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      line-height: 1.4;
      animation: alertSlide 0.3s ease;
    }

    @keyframes alertSlide {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert-danger {
      background: #fef2f2;
      border: 1px solid #fee2e2;
      color: #b91c1c;
    }

    .alert-success {
      background: #f0fdf4;
      border: 1px solid #dcfce7;
      color: #15803d;
    }

    /* ══ SUBMIT BUTTON ══════════════════════════════════════════ */
    .btn-submit {
      width: 100%;
      height: 50px;
      margin-top: 10px;
      background: #000000;
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 850;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
      transition: all 0.24s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .btn-submit:hover {
      background: #222222;
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(0, 0, 0, 0.35);
    }

    .btn-submit:active {
      transform: scale(0.98);
    }

    /* ══ FOOTER LINK ════════════════════════════════════════════ */
    .auth-footer {
      text-align: center;
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
      font-size: 13.5px;
      color: var(--muted);
      font-weight: 600;
    }

    .auth-footer a {
      color: #000000;
      font-weight: 800;
      text-decoration: underline;
      text-underline-offset: 3px;
      transition: opacity 0.2s;
    }

    .auth-footer a:hover {
      opacity: 0.75;
    }

    .auth-security-badge {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 20px;
      font-size: 11.5px;
      color: #9ca3af;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <!-- Hiệu ứng Lung linh Nền -->
  <div class="ambient-glow-1"></div>
  <div class="ambient-glow-2"></div>
  <div class="bg-grid-pattern"></div>

  <!-- Nút Quay Lại Trang Chủ -->
  <div class="top-nav">
    <a href="<?= BASE_URL ?>/" class="btn-home-back">
      <i class="fa-solid fa-arrow-left"></i> Về Trang Chủ
    </a>
  </div>

  <div class="auth-wrapper">
    <!-- ══ LINH VẬT THỂ THAO SPORTY CHEETAH / PUMA CHUYỂN ĐỘNG ══ -->
    <div class="mascot-container" id="mascotBox" aria-hidden="true">
      <svg class="mascot-svg" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Đỉnh đầu & Thân Pet -->
        <g class="mascot-head" id="mascotHead">
          <!-- Tai Trái -->
          <g class="mascot-ear-l">
            <path d="M36 42C30 24 44 14 54 26L48 46" fill="#111111" stroke="#222222" stroke-width="3"/>
            <path d="M40 38C36 28 44 22 49 29L46 41" fill="#f87171"/>
          </g>
          <!-- Tai Phải -->
          <g class="mascot-ear-r">
            <path d="M104 42C110 24 96 14 86 26L92 46" fill="#111111" stroke="#222222" stroke-width="3"/>
            <path d="M100 38C104 28 96 22 91 29L94 41" fill="#f87171"/>
          </g>

          <!-- Khuôn mặt tròn thể thao đen bóng -->
          <circle cx="70" cy="68" r="38" fill="#111111"/>
          <!-- Vùng má / cằm trắng sáng tương phản -->
          <path d="M48 76C48 64 92 64 92 76C92 90 48 90 48 76Z" fill="#ffffff"/>

          <!-- Headband Thể Thao Cao Cấp Sport Shop -->
          <path d="M34 50C45 46 95 46 106 50L104 59C93 55 47 55 36 59Z" fill="#000000" stroke="#ffffff" stroke-width="2"/>
          <!-- 3 Sọc Thể Thao Trên Headband -->
          <line x1="64" y1="51" x2="63" y2="58" stroke="#ffffff" stroke-width="2"/>
          <line x1="70" y1="50" x2="69" y2="58" stroke="#ffffff" stroke-width="2.5"/>
          <line x1="76" y1="51" x2="75" y2="58" stroke="#ffffff" stroke-width="2"/>

          <!-- Mắt Trái -->
          <circle cx="56" cy="67" r="7.5" fill="#ffffff"/>
          <circle class="mascot-eye-pupil" id="pupilL" cx="56" cy="67" r="4.2" fill="#000000"/>
          <circle cx="58" cy="65" r="1.8" fill="#ffffff"/>

          <!-- Mắt Phải -->
          <circle cx="84" cy="67" r="7.5" fill="#ffffff"/>
          <circle class="mascot-eye-pupil" id="pupilR" cx="84" cy="67" r="4.2" fill="#000000"/>
          <circle cx="86" cy="65" r="1.8" fill="#ffffff"/>

          <!-- Mũi Tam Giác Đen -->
          <polygon points="70,74 66,70 74,70" fill="#000000"/>
          <!-- Miệng Cười Vui Tươi -->
          <path d="M66 76Q70 80 74 76" stroke="#000000" stroke-width="2" stroke-linecap="round" fill="none"/>
          <!-- Má Hồng Năng Động -->
          <ellipse cx="49" cy="74" rx="4" ry="2.5" fill="#fca5a5"/>
          <ellipse cx="91" cy="74" rx="4" ry="2.5" fill="#fca5a5"/>
        </g>

        <!-- ══ HAI BÀN TAY / BÀN CHÂN CHE MẮT KHI GÕ PASSWORD ══ -->
        <!-- Tay Trái -->
        <g class="mascot-paw-l" id="mascotPawL">
          <ellipse cx="56" cy="67" rx="13" ry="12" fill="#111111" stroke="#333333" stroke-width="2"/>
          <circle cx="51" cy="63" r="2.5" fill="#ffffff"/>
          <circle cx="56" cy="60" r="2.5" fill="#ffffff"/>
          <circle cx="61" cy="63" r="2.5" fill="#ffffff"/>
          <ellipse cx="56" cy="69" rx="5" ry="4" fill="#f87171"/>
        </g>
        <!-- Tay Phải -->
        <g class="mascot-paw-r" id="mascotPawR">
          <ellipse cx="84" cy="67" rx="13" ry="12" fill="#111111" stroke="#333333" stroke-width="2"/>
          <circle cx="79" cy="63" r="2.5" fill="#ffffff"/>
          <circle cx="84" cy="60" r="2.5" fill="#ffffff"/>
          <circle cx="89" cy="63" r="2.5" fill="#ffffff"/>
          <ellipse cx="84" cy="69" rx="5" ry="4" fill="#f87171"/>
        </g>
      </svg>
    </div>

    <!-- ══ CARD ĐĂNG NHẬP MONOCHROME LUXURY ═════════════════════ -->
    <div class="auth-card">
      <div class="brand-header">
        <div class="brand-logo-badge">
          <i class="fa-solid fa-crown"></i> TRENDSTYLE FASHION
        </div>
        <h1>ĐĂNG NHẬP</h1>
        <p>Chào mừng trở lại với thế giới thời trang đẳng cấp</p>
      </div>

      <!-- Flash message -->
      <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
          <i class="fa-solid fa-<?= $flash['type'] === 'success' ? 'circle-check' : 'triangle-exclamation' ?>"></i>
          <span><?= htmlspecialchars($flash['msg']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Lỗi chung -->
      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?= htmlspecialchars($errors['general']) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/auth/login" novalidate id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <!-- Tài khoản -->
        <div class="form-group">
          <label class="form-label" for="username">Tài Khoản</label>
          <div class="input-wrapper">
            <input type="text" id="username" name="username"
                   class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                   placeholder="Nhập tên tài khoản của bạn"
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                   autocomplete="username" required autofocus />
            <i class="fa-solid fa-user input-icon-left"></i>
          </div>
          <?php if (!empty($errors['username'])): ?>
            <div class="invalid-feedback">
              <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errors['username']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Mật khẩu -->
        <div class="form-group">
          <label class="form-label" for="password">Mật Khẩu</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password"
                   class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="Nhập mật khẩu"
                   autocomplete="current-password" required />
            <i class="fa-solid fa-lock input-icon-left"></i>
            <button type="button" class="btn-toggle-pwd" id="togglePwdBtn" aria-label="Hiện/ẩn mật khẩu">
              <i class="fa-regular fa-eye" id="togglePwdIcon"></i>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback">
              <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errors['password']) ?>
            </div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit" id="loginBtn">
          ĐĂNG NHẬP NGAY <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>

      <div class="auth-footer">
        Chưa có tài khoản? <a href="<?= BASE_URL ?>/auth/register">Đăng ký tài khoản mới</a>
      </div>

      <div class="auth-security-badge">
        <i class="fa-solid fa-shield-halved"></i> Bảo mật chuẩn SSL 256-bit
      </div>
    </div>
  </div>

  <!-- ══ INTERACTIVE MASCOT ENGINE SCRIPT ═══════════════════════ -->
  <script>
    const mascotBox = document.getElementById('mascotBox');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const togglePwdBtn = document.getElementById('togglePwdBtn');
    const togglePwdIcon = document.getElementById('togglePwdIcon');
    const pupilL = document.getElementById('pupilL');
    const pupilR = document.getElementById('pupilR');

    // 1. Khi focus vào Tài khoản: Pet nhìn xuống ô nhập liệu
    usernameInput.addEventListener('focus', () => {
      mascotBox.classList.remove('covering-eyes', 'peeking');
      mascotBox.classList.add('looking-down');
    });

    usernameInput.addEventListener('blur', () => {
      mascotBox.classList.remove('looking-down');
      pupilL.style.transform = 'translate(0, 0)';
      pupilR.style.transform = 'translate(0, 0)';
    });

    // Pet liếc mắt theo độ dài ký tự đang gõ
    usernameInput.addEventListener('input', (e) => {
      const len = e.target.value.length;
      const xOffset = Math.min(Math.max((len - 8) * 0.35, -3), 3);
      pupilL.style.transform = `translate(${xOffset}px, 3.5px)`;
      pupilR.style.transform = `translate(${xOffset}px, 3.5px)`;
    });

    // 2. Khi focus vào Mật khẩu: Pet lấy tay che mắt (Peek-a-boo)
    passwordInput.addEventListener('focus', () => {
      mascotBox.classList.remove('looking-down');
      if (passwordInput.type === 'password') {
        mascotBox.classList.add('covering-eyes');
        mascotBox.classList.remove('peeking');
      } else {
        mascotBox.classList.add('covering-eyes', 'peeking');
      }
    });

    passwordInput.addEventListener('blur', () => {
      mascotBox.classList.remove('covering-eyes', 'peeking');
    });

    // 3. Nút Toggle Ẩn / Hiện mật khẩu: Pet hé mắt nhìn trộm cực vui nhộn!
    togglePwdBtn.addEventListener('click', () => {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      togglePwdIcon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';

      if (isPassword) {
        // Hiện mật khẩu -> Pet hé 1 mắt nhìn qua khe tay
        mascotBox.classList.add('peeking');
      } else {
        // Ẩn mật khẩu -> Pet che kín mắt lại
        mascotBox.classList.remove('peeking');
      }
    });
  </script>
</body>
</html>
