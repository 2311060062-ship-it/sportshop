<?php
/** Contract: order,payment,qrData. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$order = is_array($order ?? null) ? $order : [];
$payment = is_array($payment ?? null) ? $payment : [];
$orderId = (int) ($order['id'] ?? 0);
$amount = (int) ($payment['amount'] ?? $order['total'] ?? 0);
$provider = (string) ($payment['provider'] ?? '');
$isDemo = $provider === 'VNPayStyleDemo';
$stylePath = PUBLIC_PATH . '/css/style.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : APP_VERSION;
$banks = [
    ['Vietcombank', 'vcb'], ['BIDV', 'bidv'], ['VietinBank', 'vietin'],
    ['AGRIBANK', 'agribank'], ['VNPAY', 'vnpay'], ['ABBANK', 'abbank'],
    ['BAOVIET Bank', 'baoviet'], ['HDBank', 'hdbank'], ['MB', 'mb'],
    ['SCB', 'scb'], ['PVcomBank', 'pvcom'], ['VIET A BANK', 'vieta'],
    ['Techcombank', 'techcom'], ['Sacombank', 'sacombank'],
    ['VPBank', 'vpbank'], ['ACB', 'acb'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $escape($pageTitle ?? 'Thanh Toán VNPAY QR – Sport Shop') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= $escape($styleVersion) ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body.vnpay-gateway-body {
      background: #090a0f;
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      padding: 32px 16px 48px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
      color: #111111;
    }

    /* ══ NỀN AMBIENT LUNG LINH ══ */
    .vnpay-bg-ambient-1 {
      position: absolute;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.06) 0%, transparent 70%);
      top: -100px;
      left: -100px;
      pointer-events: none;
      filter: blur(50px);
      animation: vnpayFloat 16s infinite alternate ease-in-out;
    }

    .vnpay-bg-ambient-2 {
      position: absolute;
      width: 550px;
      height: 550px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.04) 0%, transparent 70%);
      bottom: -120px;
      right: -120px;
      pointer-events: none;
      filter: blur(60px);
      animation: vnpayFloat 20s infinite alternate-reverse ease-in-out;
    }

    @keyframes vnpayFloat {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(40px, 50px) scale(1.1); }
      100% { transform: translate(-30px, 20px) scale(0.95); }
    }

    .vnpay-gateway-shell {
      width: 100%;
      max-width: 860px;
      position: relative;
      z-index: 5;
    }

    /* ══ NÚT QUAY LẠI & LANGUAGE ══ */
    .vnpay-utility {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .vnpay-btn-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: 50px;
      background: rgba(255, 255, 255, 0.09);
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

    .vnpay-btn-back:hover {
      background: #ffffff;
      color: #000000;
      transform: translateX(-4px);
      box-shadow: 0 4px 16px rgba(255, 255, 255, 0.2);
    }

    .vnpay-lang-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 50px;
      background: rgba(255, 255, 255, 0.06);
      color: rgba(255, 255, 255, 0.8);
      font-size: 12px;
      font-weight: 700;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ══ MAIN GATEWAY CARD ══ */
    .vnpay-gateway-card {
      background: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.15);
      animation: vnpayPopIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes vnpayPopIn {
      from { opacity: 0; transform: translateY(24px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ══ HEADER ══ */
    .vnpay-gateway-header {
      padding: 20px 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #f1f5f9;
      background: #ffffff;
    }

    .vnpay-brand-group {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .vnpay-brand-group img {
      height: 32px;
      object-fit: contain;
    }

    .vnpay-brand-divider {
      width: 1px;
      height: 24px;
      background: #e2e8f0;
    }

    .vnpay-expiry {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12.5px;
      color: #64748b;
      font-weight: 600;
    }

    .vnpay-timer-badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      background: #000000;
      color: #ffffff;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.5px;
    }

    /* ══ NOTICE BANNER ══ */
    .vnpay-notice {
      margin: 20px 28px 0;
      padding: 12px 18px;
      border-radius: 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 12.5px;
      color: #475569;
      line-height: 1.5;
    }

    .vnpay-notice i {
      color: #000000;
      font-size: 16px;
      flex-shrink: 0;
    }

    .vnpay-notice a {
      color: #000000;
      font-weight: 800;
      text-decoration: underline;
    }

    /* ══ CONTENT GRID ══ */
    .vnpay-gateway-content {
      padding: 0 0 28px;
    }

    .vnpay-main-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.3fr);
      gap: 28px;
      padding: 24px 28px;
      align-items: start;
    }

    /* ══ CỘT TRÁI: THÔNG TIN ĐƠN HÀNG ══ */
    .vnpay-order-panel {
      background: #f8fafc;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      padding: 22px 20px;
    }

    .vnpay-order-panel h1 {
      font-size: 15px;
      font-weight: 850;
      color: #000000;
      text-transform: uppercase;
      letter-spacing: -0.2px;
      margin: 0 0 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .vnpay-order-panel dl {
      display: grid;
      gap: 14px;
      margin: 0;
    }

    .vnpay-order-panel dt {
      font-size: 11.5px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .vnpay-order-panel dd {
      font-size: 13.5px;
      font-weight: 800;
      color: #000000;
      margin: 2px 0 0;
    }

    .vnpay-primary-amount {
      padding: 14px 16px;
      background: #ffffff;
      border-radius: 10px;
      border: 1.5px solid #000000;
      margin-bottom: 6px;
    }

    .vnpay-primary-amount dt {
      color: #64748b;
      font-size: 11px;
    }

    .vnpay-primary-amount dd {
      font-size: 22px;
      font-weight: 900;
      color: #000000;
      margin-top: 4px;
    }

    .vnpay-primary-amount dd sup {
      font-size: 12px;
      margin-left: 2px;
    }

    /* ══ CỘT PHẢI: QUÉT MÃ QR & LINH THÚ CHUYỂN ĐỘNG ══ */
    .vnpay-scan-panel {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      position: relative;
    }

    .vnpay-scan-panel h1 {
      font-size: 16px;
      font-weight: 850;
      color: #000000;
      margin: 0 0 6px;
      line-height: 1.35;
    }

    .vnpay-help {
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-bottom: 16px;
      transition: color 0.2s;
    }

    .vnpay-help:hover {
      color: #000000;
    }

    /* ══ LINH THÚ TRỢ LÝ THANH TOÁN (GPU ACCELERATED 60FPS) ══ */
    .vnpay-mascot-helper {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 0 auto 16px auto;
      max-width: 340px;
      width: 100%;
      padding: 8px 14px;
      border-radius: 50px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      cursor: pointer;
      transform: translate3d(0, 0, 0);
      will-change: transform;
      transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    }

    .vnpay-mascot-helper:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }

    .vnpay-mascot-icon-box {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
      border-radius: 50%;
      background: #000000;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .vnpay-mascot-head-svg {
      width: 36px;
      height: 36px;
      transform-origin: center bottom;
      animation: petBreatheSmooth 3.2s infinite ease-in-out;
      will-change: transform;
    }

    @keyframes petBreatheSmooth {
      0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
      50% { transform: translate3d(0, -2px, 0) rotate(2deg); }
    }

    .vnpay-mascot-speech-bubble {
      font-size: 11.5px;
      font-weight: 750;
      color: #111111;
      text-align: left;
      line-height: 1.35;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .vnpay-mascot-speech-bubble i {
      color: #eab308;
      font-size: 13px;
      flex-shrink: 0;
    }

    /* ══ KHUNG MÃ QR CĂN GIỮA CHUẨN XÁC 100% ══ */
    .vnpay-qr-wrap {
      position: relative;
      padding: 20px 24px;
      border: 1.5px solid #000000;
      border-radius: 18px;
      background: #ffffff;
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px auto;
      width: fit-content;
      box-sizing: border-box;
    }

    .vnpay-qr-brand {
      height: 22px;
      margin-bottom: 12px;
      object-fit: contain;
      display: block;
    }

    #vnpayQrCode {
      width: 176px;
      height: 176px;
      display: flex !important;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      background: #ffffff;
      overflow: hidden;
      box-sizing: border-box;
    }

    #vnpayQrCode canvas {
      display: none !important;
    }

    #vnpayQrCode img {
      width: 176px !important;
      height: 176px !important;
      max-width: 176px !important;
      display: block !important;
      margin: 0 auto !important;
      object-fit: contain;
    }

    .vnpay-qr-wrap em {
      display: block;
      margin-top: 12px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: #64748b;
      font-style: normal;
      text-align: center;
    }

    /* ══ NÚT THANH TOÁN & HỦY ══ */
    .vnpay-payment-actions {
      max-width: 320px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .vnpay-confirm {
      width: 100%;
      height: 48px;
      border-radius: 10px;
      background: #000000;
      color: #ffffff;
      border: none;
      font-size: 13.5px;
      font-weight: 850;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
      transition: all 0.24s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .vnpay-confirm:hover {
      background: #222222;
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(0, 0, 0, 0.35);
    }

    .vnpay-cancel {
      width: 100%;
      height: 44px;
      border-radius: 10px;
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #e2e8f0;
      font-size: 13px;
      font-weight: 750;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.2s ease;
    }

    .vnpay-cancel:hover {
      background: #e2e8f0;
      color: #000000;
    }

    /* ══ DANH SÁCH NGÂN HÀNG ══ */
    .vnpay-supported-banks {
      margin: 10px 28px 0;
      padding-top: 22px;
      border-top: 1px solid #f1f5f9;
    }

    .vnpay-supported-banks h2 {
      font-size: 12px;
      font-weight: 800;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 14px;
      text-align: center;
    }

    .vnpay-bank-list {
      display: grid;
      grid-template-columns: repeat(8, 1fr);
      gap: 8px;
    }

    .vnpay-bank-logo {
      padding: 8px 6px;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      text-align: center;
      font-size: 10px;
      font-weight: 800;
      color: #000000;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      transition: all 0.2s ease;
    }

    .vnpay-bank-logo:hover {
      background: #ffffff;
      border-color: #000000;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    @media (max-width: 768px) {
      .vnpay-main-grid { grid-template-columns: 1fr; gap: 20px; }
      .vnpay-bank-list { grid-template-columns: repeat(4, 1fr); }
    }
  </style>
</head>
<body class="vnpay-gateway-body">
  <!-- Hiệu ứng Lung linh Nền -->
  <div class="vnpay-bg-ambient-1"></div>
  <div class="vnpay-bg-ambient-2"></div>

  <div class="vnpay-gateway-shell">
    <div class="vnpay-utility">
      <a href="<?= BASE_URL ?>/checkout/index" class="vnpay-btn-back">
        <i class="fa-solid fa-arrow-left"></i> Quay lại giỏ hàng
      </a>
      <span class="vnpay-lang-badge">🇻🇳 VIỆT NAM (VN)</span>
    </div>

    <main class="vnpay-gateway-card">
      <header class="vnpay-gateway-header">
        <div class="vnpay-brand-group">
          <img src="<?= BASE_URL ?>/public/images/ocb-logo.svg" alt="OCB">
          <span class="vnpay-brand-divider"></span>
          <img src="<?= BASE_URL ?>/public/images/vnpay-logo.svg" alt="VNPAY">
        </div>
        <div class="vnpay-expiry">
          <span>Hết hạn sau:</span>
          <div class="vnpay-timer-badge">
            <span id="vnpayMinutes">14</span>:<span id="vnpaySeconds">43</span>
          </div>
        </div>
      </header>

      <div class="vnpay-gateway-content">
        <div class="vnpay-notice">
          <i class="fa-solid fa-shield-halved"></i>
          <p>
            Giao dịch được mã hóa chuẩn bảo mật quốc tế. Vui lòng không tắt trình duyệt cho đến khi nhận được kết quả.
            Nếu đã thanh toán mà chưa thấy chuyển trang, bấm <a href="#" id="refreshPaymentStatus">Tại đây</a> để kiểm tra.
          </p>
        </div>

        <div class="vnpay-main-grid">
          <!-- CỘT TRÁI: THÔNG TIN ĐƠN HÀNG -->
          <aside class="vnpay-order-panel">
            <h1>
              <span>Thông tin đơn hàng</span>
              <small style="font-size:10.5px;color:#64748b;font-weight:700;">#<?= $orderId ?></small>
            </h1>
            <dl>
              <div class="vnpay-primary-amount">
                <dt>Số tiền cần thanh toán</dt>
                <dd><?= number_format($amount, 0, ',', '.') ?><sup>VND</sup></dd>
              </div>
              <div>
                <dt>Mã đơn hàng</dt>
                <dd>DH-<?= str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) ?></dd>
              </div>
              <div>
                <dt>Phí giao dịch</dt>
                <dd style="color:#10b981;">Miễn phí 0<sup>VND</sup></dd>
              </div>
              <div>
                <dt>Đơn vị thụ hưởng</dt>
                <dd>SPORT SHOP VIỆT NAM</dd>
              </div>
            </dl>
          </aside>

          <!-- CỘT PHẢI: QUÉT MÃ QR & LINH THÚ TRỢ LÝ THANH TOÁN -->
          <section class="vnpay-scan-panel">
            <h1>Quét mã qua App Ngân Hàng<br>hoặc Ví Điện Tử</h1>
            <a class="vnpay-help" href="#"><i class="fa-regular fa-circle-question"></i> Hướng dẫn thanh toán an toàn</a>

            <!-- ══ LINH THÚ TRỢ LÝ THANH TOÁN CHUYỂN ĐỘNG SIÊU MƯỢT ══ -->
            <div class="vnpay-mascot-helper" id="vnpayMascotHelper" title="Nhấp để nghe Linh thú tư vấn">
              <div class="vnpay-mascot-icon-box">
                <svg class="vnpay-mascot-head-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <!-- Tai Pet -->
                  <path d="M26 32C22 18 32 10 40 20L36 34" fill="#ffffff"/>
                  <path d="M74 32C78 18 68 10 60 20L64 34" fill="#ffffff"/>
                  <path d="M29 30C26 21 32 17 37 23" fill="#f87171"/>
                  <path d="M71 30C74 21 68 17 63 23" fill="#f87171"/>
                  <!-- Khuôn mặt tròn thể thao -->
                  <circle cx="50" cy="52" r="28" fill="#ffffff"/>
                  <!-- Headband 3 sọc -->
                  <path d="M24 38C32 34 68 34 76 38L74 45C66 42 34 42 26 45Z" fill="#000000"/>
                  <line x1="46" y1="39" x2="45" y2="44" stroke="#ffffff" stroke-width="1.8"/>
                  <line x1="50" y1="38.5" x2="49.5" y2="44" stroke="#ffffff" stroke-width="2"/>
                  <line x1="54" y1="39" x2="53.5" y2="44" stroke="#ffffff" stroke-width="1.8"/>
                  <!-- Mắt nhấp nháy chuyển động -->
                  <circle cx="40" cy="52" r="4.5" fill="#000000"/>
                  <circle cx="41.5" cy="50.5" r="1.5" fill="#ffffff"/>
                  <circle cx="60" cy="52" r="4.5" fill="#000000"/>
                  <circle cx="61.5" cy="50.5" r="1.5" fill="#ffffff"/>
                  <!-- Mũi & Miệng cười -->
                  <polygon points="50,57 47,54 53,54" fill="#000000"/>
                  <path d="M47 58Q50 61 53 58" stroke="#000000" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                  <ellipse cx="35" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
                  <ellipse cx="65" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
                </svg>
              </div>
              <div class="vnpay-mascot-speech-bubble">
                <i class="fa-solid fa-bolt"></i>
                <span id="vnpayMascotText">Quét mã QR bằng App ngân hàng bất kỳ để thanh toán nhanh nhé!</span>
              </div>
            </div>

            <!-- Khung QR Code Căn Giữa Hoàn Hảo -->
            <div class="vnpay-qr-wrap" id="vnpayQrCard">
              <img class="vnpay-qr-brand" src="<?= BASE_URL ?>/public/images/vnpay-qr-logo.svg" alt="VNPAY QR">
              <div id="vnpayQrCode" data-qr="<?= $escape($qrData ?? '') ?>">
                <i class="fa-solid fa-spinner fa-spin"></i>
              </div>
              <em>Scan to Pay</em>
            </div>

            <div class="vnpay-payment-actions">
              <?php if ($isDemo): ?>
                <form method="POST" action="<?= BASE_URL ?>/checkout/confirm-vnpay-style/<?= $orderId ?>" id="confirmPayForm">
                  <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                  <button class="vnpay-confirm" type="submit" id="btnConfirmPay">
                    <i class="fa-solid fa-circle-check"></i> Xác nhận đã thanh toán
                  </button>
                </form>
              <?php endif; ?>
              <button class="vnpay-cancel" id="cancelVnpayPayment" type="button">
                <i class="fa-solid fa-xmark"></i> Hủy giao dịch này
              </button>
            </div>
          </section>
        </div>

        <section class="vnpay-supported-banks">
          <h2>Hỗ trợ thanh toán qua 40+ Ngân Hàng & Ví Điện Tử</h2>
          <div class="vnpay-bank-list">
            <?php foreach ($banks as [$name, $className]): ?>
              <div class="vnpay-bank-logo <?= $escape($className) ?>"><?= $escape($name) ?></div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <script>
  window.addEventListener('load', () => {
    const orderId = <?= $orderId ?>;
    const statusUrl = <?= json_encode(BASE_URL . '/checkout/payment-status/' . $orderId) ?>;
    const resultUrl = <?= json_encode(BASE_URL . '/user/orders?status=processing') ?>;
    const cartUrl = <?= json_encode(BASE_URL . '/cart/index') ?>;
    const qrElement = document.getElementById('vnpayQrCode');
    const qrData = qrElement ? qrElement.dataset.qr : '';
    let remaining = 14 * 60 + 43;
    let statusTimer;

    // Render QRCode chuẩn kích thước và căn giữa
    if (qrData && typeof QRCode !== 'undefined' && qrElement) {
      qrElement.innerHTML = '';
      new QRCode(qrElement, {
        text: qrData,
        width: 176,
        height: 176,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    } else if (qrElement) {
      qrElement.innerHTML = '<small style="color:#64748b;font-weight:700;">Không thể tải mã QR</small>';
    }

    // Polling Status
    const checkStatus = () => fetch(statusUrl, { headers: { Accept: 'application/json' } })
      .then(response => response.json())
      .then(data => {
        if (data.paid) {
          clearInterval(statusTimer);
          window.location.href = resultUrl;
        } else if (data.failed) {
          clearInterval(statusTimer);
          alert('Thanh toán không thành công.');
        }
      })
      .catch(() => {});

    statusTimer = setInterval(checkStatus, 3000);

    const refreshBtn = document.getElementById('refreshPaymentStatus');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', event => {
        event.preventDefault();
        checkStatus();
      });
    }

    // Nút Hủy
    const cancelBtn = document.getElementById('cancelVnpayPayment');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', async () => {
        const confirmed = window.showConfirmDialog
          ? await window.showConfirmDialog({
              title: 'Hủy thanh toán?',
              message: 'Bạn có chắc chắn muốn hủy thanh toán và quay lại giỏ hàng?',
              type: 'danger',
              confirmText: 'Hủy thanh toán',
              cancelText: 'Tiếp tục thanh toán'
            })
          : confirm('Bạn có chắc muốn hủy thanh toán?');
        if (confirmed) window.location.href = cartUrl;
      });
    }

    // Đếm ngược thời gian
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const minEl = document.getElementById('vnpayMinutes');
      const secEl = document.getElementById('vnpaySeconds');
      if (minEl) minEl.textContent = String(Math.floor(remaining / 60)).padStart(2, '0');
      if (secEl) secEl.textContent = String(remaining % 60).padStart(2, '0');
    }, 1000);

    // ══ INTERACTIVE MASCOT ENGINE ══
    const mascotHelper = document.getElementById('vnpayMascotHelper');
    const mascotText = document.getElementById('vnpayMascotText');
    const qrCard = document.getElementById('vnpayQrCard');
    const confirmBtn = document.getElementById('btnConfirmPay');

    const tips = [
      'Quét mã QR bằng App ngân hàng bất kỳ để thanh toán nhanh nhé!',
      'Giao dịch được mã hóa chuẩn bảo mật SSL 256-bit an toàn 100%!',
      'Mở ứng dụng ngân hàng và chọn tính năng Quét QR nhé!',
      'Sau khi chuyển khoản thành công hệ thống sẽ tự động xác nhận đơn!'
    ];
    let tipIdx = 0;

    if (mascotHelper && mascotText) {
      mascotHelper.addEventListener('click', () => {
        tipIdx = (tipIdx + 1) % tips.length;
        mascotText.textContent = tips[tipIdx];
      });
    }

    if (qrCard && mascotText) {
      qrCard.addEventListener('mouseenter', () => {
        mascotText.textContent = '📸 Đưa camera quét mã QR chuẩn xác để thanh toán nhé!';
      });
      qrCard.addEventListener('mouseleave', () => {
        mascotText.textContent = tips[tipIdx];
      });
    }

    if (confirmBtn && mascotText) {
      confirmBtn.addEventListener('mouseenter', () => {
        mascotText.textContent = '⚡ Nhấn để hoàn tất đơn hàng và nhận giày sớm nhất nha!';
      });
    }
  });
  </script>
</body>
</html>
