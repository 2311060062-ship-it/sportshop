<?php /** Layout Footer – dùng chung */ ?>
</main><!-- /#mainContent -->

<!-- ══ FOOTER ══════════════════════════════════════════════ -->
<footer class="footer" role="contentinfo">
  <div class="footer-inner">
    <div class="reveal stagger-1">
      <h4><i class="fas fa-crown"></i> TrendStyle Fashion</h4>
      <p>Thương hiệu thời trang định hình phong cách sống hiện đại.<br>
         Chất lượng cao cấp – Thiết kế dẫn đầu xu hướng – Dịch vụ tận tâm.</p>
      <div style="display:flex;gap:14px;margin-top:16px">
        <a href="#" style="color:rgba(255,255,255,.8);font-size:20px" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
        <a href="#" style="color:rgba(255,255,255,.8);font-size:20px" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" style="color:rgba(255,255,255,.8);font-size:20px" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="#" style="color:rgba(255,255,255,.8);font-size:20px" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
      </div>
    </div>
    <div class="reveal stagger-2">
      <h4>Thương Hiệu Nổi Bật</h4>
      <a href="<?= BASE_URL ?>/product/index?brand=1">Uniqlo</a><br>
      <a href="<?= BASE_URL ?>/product/index?brand=2">Zara</a><br>
      <a href="<?= BASE_URL ?>/product/index?brand=3">H&M</a><br>
      <a href="<?= BASE_URL ?>/product/index?brand=4">Coolmate</a><br>
      <a href="<?= BASE_URL ?>/product/index?brand=5">Mango</a>
    </div>
    <div class="reveal stagger-3">
      <h4>Hỗ Trợ Khách Hàng</h4>
      <a href="#">Chính sách đổi trả 30 ngày</a><br>
      <a href="#">Hướng dẫn chọn size chuẩn</a><br>
      <a href="#">Bảo quản quần áo bền đẹp</a><br>
      <a href="<?= BASE_URL ?>/#contact">Liên hệ chúng tôi</a>
    </div>
  </div>
  <div class="footer-bottom reveal stagger-4">
    <p>© <?= date('Y') ?> TrendStyle Fashion. Tất cả quyền được bảo lưu.</p>
  </div>
</footer>

<!-- Toast notification -->
<div class="toast" id="toast" role="status" aria-live="polite">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg"></span>
</div>

<!-- ══ AI STYLIST CHATBOT WIDGET ═════════════════════════════════ -->
<div class="sport-chatbot-widget" id="sportChatbotWidget">
  <!-- Nút tròn nổi (Floating Button) -->
  <button type="button" class="chatbot-trigger-btn" id="chatbotTriggerBtn" aria-label="Mở Trợ lý AI Stylist">
    <span class="chatbot-trigger-icon">
      <i class="fa-solid fa-wand-magic-sparkles"></i>
    </span>
    <span class="chatbot-trigger-badge">Stylist</span>
    <span class="chatbot-pulse-ring"></span>
  </button>

  <!-- Cửa sổ Chat (Chat Window) -->
  <div class="chatbot-window" id="chatbotWindow" aria-hidden="true">
    <!-- Header -->
    <div class="chatbot-header">
      <div class="chatbot-header-info">
        <div class="chatbot-avatar">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
          <span class="chatbot-online-dot"></span>
        </div>
        <div>
          <h4 class="chatbot-title">AI Stylist – TrendStyle</h4>
          <span class="chatbot-status">Chuyên gia tư vấn thời trang 24/7</span>
        </div>
      </div>
      <div class="chatbot-header-actions">
        <button type="button" class="chatbot-action-btn" id="chatbotResetBtn" title="Làm mới cuộc trò chuyện"><i class="fa-solid fa-rotate-right"></i></button>
        <button type="button" class="chatbot-action-btn" id="chatbotCloseBtn" title="Đóng cửa sổ"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>

    <!-- Message Body -->
    <div class="chatbot-body" id="chatbotBody">
      <!-- Tin nhắn chào mừng -->
      <div class="chat-bubble chat-bubble-bot">
        <div class="chat-bubble-avatar"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
        <div class="chat-bubble-content">
          <p>Xin chào bạn! 👋 Tôi là <strong>AI Stylist – TrendStyle</strong>. Tôi có thể giúp bạn:</p>
          <ul>
            <li>✨ Gợi ý phối đồ (Mix & Match) cho từng dịp</li>
            <li>📏 Tư vấn chọn size quần áo theo chiều cao, cân nặng</li>
            <li>👗 Khám phá các mẫu thời trang hot trend</li>
            <li>📦 Tra cứu tình trạng đơn hàng nhanh chóng</li>
          </ul>
          <p>Hôm nay bạn muốn tìm phong cách hoặc trang phục nào?</p>
        </div>
      </div>
    </div>

    <!-- Quick Suggestions (Gợi ý nhanh) -->
    <div class="chatbot-suggestions" id="chatbotSuggestions">
      <button type="button" class="suggestion-chip" data-prompt="Gợi ý set đồ công sở lịch sự thanh lịch"><i class="fa-solid fa-briefcase"></i> Phối đồ công sở</button>
      <button type="button" class="suggestion-chip" data-prompt="Tôi cao 1m72 nặng 65kg nên chọn size áo và quần nào?"><i class="fa-solid fa-ruler"></i> Tư vấn size chuẩn</button>
      <button type="button" class="suggestion-chip" data-prompt="Các mẫu đầm váy dự tiệc đẹp hot trend"><i class="fa-solid fa-vest-patches"></i> Đầm váy hot trend</button>
      <button type="button" class="suggestion-chip" data-prompt="Kiểm tra trạng thái đơn hàng của tôi"><i class="fa-solid fa-box-open"></i> Đơn hàng của tôi</button>
    </div>

    <!-- Input Bar -->
    <form class="chatbot-input-bar" id="chatbotForm">
      <input type="text" id="chatbotInput" placeholder="Nhập câu hỏi cho Trợ lý AI..." autocomplete="off" maxlength="500">
      <button type="submit" id="chatbotSendBtn" aria-label="Gửi tin nhắn">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </form>
  </div>
</div>

<script>
window.SPORTSHOP_CONFIG = Object.freeze({
  baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
  csrfToken: <?= json_encode($csrfToken ?? '', JSON_UNESCAPED_SLASHES) ?>
});
</script>
<script src="<?= BASE_URL ?>/public/js/main.js?v=<?= is_file(PUBLIC_PATH . '/js/main.js') ? filemtime(PUBLIC_PATH . '/js/main.js') : APP_VERSION ?>"></script>
<script src="<?= BASE_URL ?>/public/js/chatbot.js?v=<?= is_file(PUBLIC_PATH . '/js/chatbot.js') ? filemtime(PUBLIC_PATH . '/js/chatbot.js') : APP_VERSION ?>"></script>
</body>
</html>
