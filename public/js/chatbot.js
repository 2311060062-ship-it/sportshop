/**
 * ============================================================
 *  SPORT SHOP – AI Chatbot Interactive Engine
 *  File: public/js/chatbot.js
 * ============================================================
 */
(function () {
  'use strict';

  const triggerBtn = document.getElementById('chatbotTriggerBtn');
  const chatWindow = document.getElementById('chatbotWindow');
  const closeBtn = document.getElementById('chatbotCloseBtn');
  const resetBtn = document.getElementById('chatbotResetBtn');
  const chatBody = document.getElementById('chatbotBody');
  const chatForm = document.getElementById('chatbotForm');
  const chatInput = document.getElementById('chatbotInput');
  const sendBtn = document.getElementById('chatbotSendBtn');
  const suggestions = document.getElementById('chatbotSuggestions');

  if (!triggerBtn || !chatWindow || !chatBody || !chatForm) return;

  const BASE_URL = (window.SPORTSHOP_CONFIG && window.SPORTSHOP_CONFIG.baseUrl) || '';
  const CHAT_ENDPOINT = BASE_URL + '/chatbot/send';
  const STORAGE_KEY = 'sportshop_chat_history';

  let chatHistory = [];
  let isSending = false;

  // Khôi phục lịch sử chat từ sessionStorage (nếu có)
  function loadStoredChat() {
    try {
      const stored = sessionStorage.getItem(STORAGE_KEY);
      if (stored) {
        chatHistory = JSON.parse(stored);
        if (Array.isArray(chatHistory) && chatHistory.length > 0) {
          chatBody.innerHTML = '';
          chatHistory.forEach(item => {
            appendMessageBubble(item.sender, item.text, item.products, false);
          });
          scrollToBottom();
        }
      }
    } catch (e) {
      chatHistory = [];
    }
  }

  function saveStoredChat() {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(chatHistory.slice(-15)));
    } catch (e) {}
  }

  function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function toggleChat(open) {
    const isOpen = open !== undefined ? open : !chatWindow.classList.contains('is-open');
    chatWindow.classList.toggle('is-open', isOpen);
    chatWindow.setAttribute('aria-hidden', String(!isOpen));
    if (isOpen) {
      setTimeout(() => chatInput && chatInput.focus(), 150);
      scrollToBottom();
    }
  }

  // Chuyển đổi Markdown cơ bản sang HTML an toàn
  function formatMarkdown(text) {
    if (!text) return '';
    let escaped = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    // In đậm **text**
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // In nghiêng *text*
    escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
    // Link [title](url)
    escaped = escaped.replace(/\[(.*?)\]\((https?:\/\/.*?)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

    // Chuyển dòng thành <p> hoặc <br>
    const lines = escaped.split('\n');
    let html = '';
    let inList = false;

    lines.forEach(line => {
      const trimmed = line.trim();
      if (trimmed.startsWith('- ') || trimmed.startsWith('+ ') || trimmed.startsWith('* ')) {
        if (!inList) {
          html += '<ul>';
          inList = true;
        }
        html += '<li>' + trimmed.substring(2) + '</li>';
      } else {
        if (inList) {
          html += '</ul>';
          inList = false;
        }
        if (trimmed !== '') {
          html += '<p>' + trimmed + '</p>';
        }
      }
    });

    if (inList) html += '</ul>';
    return html || '<p>' + escaped + '</p>';
  }

  // Thêm bong bóng chat vào giao diện
  function appendMessageBubble(sender, text, products = [], suggestions = [], save = true) {
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble chat-bubble-' + (sender === 'user' ? 'user' : 'bot');

    let innerHTML = '';
    if (sender === 'bot') {
      innerHTML += '<div class="chat-bubble-avatar"><i class="fa-solid fa-robot"></i></div>';
    }

    innerHTML += '<div class="chat-bubble-content">' + formatMarkdown(text);

    // Nếu có thẻ sản phẩm đi kèm
    if (Array.isArray(products) && products.length > 0) {
      innerHTML += '<div class="chat-product-list">';
      products.forEach(p => {
        innerHTML += `
          <a class="chat-product-card" href="${p.link || '#'}" target="_blank">
            <img class="chat-product-img" src="${p.thumbnail || ''}" alt="${p.name || 'Sản phẩm'}" onerror="this.src='${BASE_URL}/public/images/product-placeholder.svg'">
            <div class="chat-product-meta">
              <div class="chat-product-name">${p.name || ''}</div>
              <div class="chat-product-price">${p.final_price || p.price || ''}</div>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:#1677ff;font-size:11px"></i>
          </a>
        `;
      });
      innerHTML += '</div>';
    }

    // Nếu có các nút gợi ý câu hỏi tiếp theo (Follow-up suggestions)
    if (Array.isArray(suggestions) && suggestions.length > 0 && sender === 'bot') {
      innerHTML += '<div class="chat-followup-chips">';
      suggestions.forEach(s => {
        innerHTML += `<button type="button" class="chat-followup-chip" data-prompt="${s}">${s}</button>`;
      });
      innerHTML += '</div>';
    }

    innerHTML += '</div>';
    bubble.innerHTML = innerHTML;
    chatBody.appendChild(bubble);
    scrollToBottom();

    if (save) {
      chatHistory.push({ sender, text, products, suggestions });
      saveStoredChat();
    }
  }

  // Hiển thị Typing indicator khi chờ AI
  function showTypingIndicator() {
    const typingBubble = document.createElement('div');
    typingBubble.className = 'chat-bubble chat-bubble-bot';
    typingBubble.id = 'chatTypingIndicator';
    typingBubble.innerHTML = `
      <div class="chat-bubble-avatar"><i class="fa-solid fa-robot"></i></div>
      <div class="chat-bubble-content chat-typing">
        <span></span><span></span><span></span>
      </div>
    `;
    chatBody.appendChild(typingBubble);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    const el = document.getElementById('chatTypingIndicator');
    if (el) el.remove();
  }

  // Gửi tin nhắn tới backend
  async function handleSendMessage(messageText) {
    const text = (messageText || (chatInput && chatInput.value) || '').trim();
    if (!text || isSending) return;

    if (chatInput) chatInput.value = '';
    isSending = true;
    if (sendBtn) sendBtn.disabled = true;

    appendMessageBubble('user', text);
    showTypingIndicator();

    try {
      const response = await fetch(CHAT_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json; charset=utf-8'
        },
        body: JSON.stringify({
          message: text,
          history: chatHistory.slice(-8)
        })
      });

      const data = await response.json();
      removeTypingIndicator();

      if (data && data.success) {
        appendMessageBubble('bot', data.reply || 'Sport Shop rất vui được hỗ trợ bạn!', data.products || [], data.suggestions || []);
      } else {
        appendMessageBubble('bot', data.message || 'Xin lỗi bạn, kết nối của Trợ lý AI đang gián đoạn một chút. Bạn vui lòng thử lại nhé!');
      }
    } catch (error) {
      removeTypingIndicator();
      appendMessageBubble('bot', 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại đường truyền mạng của bạn.');
    } finally {
      isSending = false;
      if (sendBtn) sendBtn.disabled = false;
      if (chatInput) chatInput.focus();
    }
  }

  // Event Listeners
  triggerBtn.addEventListener('click', () => toggleChat());
  closeBtn.addEventListener('click', () => toggleChat(false));

  resetBtn.addEventListener('click', () => {
    chatHistory = [];
    sessionStorage.removeItem(STORAGE_KEY);
    chatBody.innerHTML = `
      <div class="chat-bubble chat-bubble-bot">
        <div class="chat-bubble-avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="chat-bubble-content">
          <p>Cuộc trò chuyện đã được làm mới! 👋 Tôi là <strong>Trợ lý AI Sport Shop</strong>. Tôi có thể giúp gì cho bạn hôm nay?</p>
        </div>
      </div>
    `;
    scrollToBottom();
  });

  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    handleSendMessage();
  });

  // Click chọn gợi ý nhanh bên trong bong bóng chat
  chatBody.addEventListener('click', (e) => {
    const chip = e.target.closest('.chat-followup-chip');
    if (chip && chip.dataset.prompt) {
      handleSendMessage(chip.dataset.prompt);
    }
  });

  // Click chọn gợi ý nhanh ở thanh cuộn dưới
  if (suggestions) {
    suggestions.addEventListener('click', (e) => {
      const chip = e.target.closest('.suggestion-chip');
      if (chip && chip.dataset.prompt) {
        handleSendMessage(chip.dataset.prompt);
      }
    });
  }

  // Tải lịch sử chat
  loadStoredChat();
})();
