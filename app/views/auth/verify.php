<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Xác thực email – TrendStyle Fashion</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #090a0f;
      color: #111;
    }
    .card {
      width: min(440px, 100%);
      background: #fff;
      border-radius: 20px;
      padding: 32px 28px;
    }
    h1 { margin: 8px 0; font-size: 26px; letter-spacing: -0.4px; }
    p { color: #6b7280; line-height: 1.5; }
    .badge { font-size: 12px; font-weight: 800; letter-spacing: 0.6px; }
    label { display: block; font-weight: 700; margin: 18px 0 8px; }
    input[type="text"] {
      width: 100%;
      padding: 14px 12px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      font-size: 28px;
      letter-spacing: 6px;
      text-align: center;
      font-weight: 800;
    }
    button, .link-btn {
      width: 100%;
      margin-top: 12px;
      border: 0;
      border-radius: 999px;
      padding: 13px 16px;
      font-weight: 800;
      cursor: pointer;
      background: #111;
      color: #fff;
    }
    .link-btn { background: #f3f4f6; color: #111; }
    .alert { padding: 12px 14px; border-radius: 12px; margin-top: 12px; }
    .alert-success { background: #ecfdf5; color: #047857; }
    .alert-danger { background: #fef2f2; color: #b91c1c; }
    a { color: #111; }
  </style>
</head>
<body>
  <main class="card">
    <div class="badge">TRENDSTYLE FASHION</div>
    <h1>Xác thực email</h1>
    <p>Nhập mã 6 số đã gửi tới <strong><?= htmlspecialchars($emailMask ?? '') ?></strong> để tiếp tục thanh toán. Mã có hiệu lực trong 10 phút.</p>

    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['msg'] ?? '') ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['code'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errors['code']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/auth/verify">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label for="code">Mã xác thực</label>
      <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
             maxlength="6" placeholder="000000" required>
      <button type="submit">Xác nhận</button>
    </form>

    <form method="POST" action="<?= BASE_URL ?>/auth/resend">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="link-btn" type="submit">Gửi lại mã</button>
    </form>
    <p><a href="<?= BASE_URL ?>/">Tiếp tục xem sản phẩm</a> · <a href="<?= BASE_URL ?>/auth/logout">Dùng tài khoản khác</a></p>
  </main>
</body>
</html>
