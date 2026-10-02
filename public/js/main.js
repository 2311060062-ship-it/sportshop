/**
 * Sport Shop – main.js
 * Các function xử lý sự kiện site
 */
const sportShopConfig = window.SPORTSHOP_CONFIG || {};
const BASE_URL = String(sportShopConfig.baseUrl || '').replace(/\/$/, '');
const CSRF_TOKEN = String(sportShopConfig.csrfToken || '');



/* ── Slider ────────────────────────────────────────────── */
(function () {
  const slides = document.querySelectorAll('.slide');
  const dotsEl = document.getElementById('sliderDots');
  if (!slides.length || !dotsEl) return;

  let current = 0;

  slides.forEach((_, i) => {
    const btn = document.createElement('button');
    btn.className = 'dot' + (i === 0 ? ' active' : '');
    btn.setAttribute('aria-label', 'Slide ' + (i + 1));
    btn.onclick = () => goTo(i);
    dotsEl.appendChild(btn);
  });

  function goTo(n) {
    slides[current].classList.remove('active');
    dotsEl.children[current].classList.remove('active');
    current = (n + slides.length) % slides.length;
    slides[current].classList.add('active');
    dotsEl.children[current].classList.add('active');
  }

  window.moveSlide = (dir) => goTo(current + dir);
  let sliderTimer = window.setInterval(() => goTo(current + 1), 4500);
  document.addEventListener('visibilitychange', () => {
    window.clearInterval(sliderTimer);
    if (!document.hidden) sliderTimer = window.setInterval(() => goTo(current + 1), 4500);
  });
})();

/* ── Scroll Products ───────────────────────────────────── */
window.scrollRow = function (id, amount) {
  const el = document.getElementById(id);
  if (el) el.scrollBy({ left: amount, behavior: 'smooth' });
};

/* ── User Dropdown ─────────────────────────────────────── */
window.toggleDropdown = function () {
  const menu = document.getElementById('dropdownMenu');
  const btn  = document.getElementById('userBtn');
  if (!menu) return;
  const open = menu.classList.toggle('open');
  btn && btn.setAttribute('aria-expanded', String(open));
};

document.addEventListener('click', function (e) {
  const dd = document.getElementById('userDropdown');
  const menu = document.getElementById('dropdownMenu');
  if (dd && menu && !dd.contains(e.target)) {
    menu.classList.remove('open');
    const btn = document.getElementById('userBtn');
    btn && btn.setAttribute('aria-expanded', 'false');
  }
});

/* ── Toast Mượt Mà ─────────────────────────────────────── */
let toastTimer;
window.showToast = function (msg, type = 'success') {
  const toast = document.getElementById('toast');
  const span  = document.getElementById('toastMsg');
  if (!toast || !span) return;

  span.textContent = msg;
  toast.style.background = type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #10b981, #059669)';
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
};

/* ── Add to Cart (AJAX) ────────────────────────────────── */
window.addToCart = function (productId, qty = 1, size = '') {
  const quantity = Math.max(1, Number.parseInt(qty, 10) || 1);
  if (!size) {
    const checkedRadio = document.querySelector('input[name="selected_shoe_size"]:checked');
    size = checkedRadio ? checkedRadio.value : 'L';
  }
  const body = new URLSearchParams({
    product_id: String(productId),
    quantity: String(quantity),
    size: String(size),
    csrf_token: CSRF_TOKEN,
  });

  // Kích hoạt hiệu ứng loading
  if (window.startProgressBar) window.startProgressBar();

  fetch(BASE_URL + '/product/addToCart', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  })
    .then(async (response) => {
      if (window.finishProgressBar) window.finishProgressBar();
      const data = await response.json();
      if (!response.ok && response.status === 401) {
        window.location.href = BASE_URL + '/auth/login';
        return null;
      }
      return data;
    })
    .then(data => {
      if (!data) return;
      if (data.success) {
        showToast(data.message);
        const badge = document.getElementById('cartBadge');
        if (badge) {
          badge.textContent = data.cartCount;
          // Web Animations chạy trên compositor, không ép trình duyệt reflow.
          if (typeof badge.animate === 'function') {
            badge.getAnimations().forEach(animation => animation.cancel());
            badge.animate(
              [
                { transform: 'scale(1) rotate(0)' },
                { transform: 'scale(1.4) rotate(-8deg)', offset: .5 },
                { transform: 'scale(1) rotate(0)' },
              ],
              { duration: 400, easing: 'ease-out' }
            );
          }
        }
      } else {
        if (data.message && data.message.includes('đăng nhập')) {
          window.location.href = BASE_URL + '/auth/login';
        } else {
          showToast(data.message || 'Có lỗi xảy ra!', 'error');
        }
      }
    })
    .catch(() => {
      if (window.finishProgressBar) window.finishProgressBar();
      showToast('Lỗi kết nối!', 'error');
    });
};

/* ── Shoe Size Selection ───────────────────────────────── */
const sizeChips = document.querySelectorAll('.size-chip');
const selectedSizeDisplay = document.getElementById('currentSelectedSize');
sizeChips.forEach((chip) => {
  chip.addEventListener('click', () => {
    sizeChips.forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    const radio = chip.querySelector('input[type="radio"]');
    if (radio) {
      radio.checked = true;
      if (selectedSizeDisplay) selectedSizeDisplay.textContent = radio.value;
    }
  });
});

/* ── Add to Cart Button on Detail Page ─────────────────── */
const detailAddCartBtn = document.getElementById('detailAddToCartBtn');
if (detailAddCartBtn) {
  detailAddCartBtn.addEventListener('click', () => {
    const productId = detailAddCartBtn.dataset.productId;
    const qtyInput = document.getElementById('productQuantity');
    const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
    const checkedRadio = document.querySelector('input[name="selected_shoe_size"]:checked');
    const size = checkedRadio ? checkedRadio.value : '40';
    addToCart(productId, qty, size);
  });
}

/* ── Size Guide Modal ──────────────────────────────────── */
const openSizeGuideBtn = document.getElementById('openSizeGuideBtn');
const closeSizeGuideBtn = document.getElementById('closeSizeGuideBtn');
const sizeGuideModal = document.getElementById('sizeGuideModal');
const sizeGuideBackdrop = document.getElementById('sizeGuideBackdrop');

function toggleSizeGuide(show) {
  if (!sizeGuideModal) return;
  sizeGuideModal.classList.toggle('is-open', show);
  sizeGuideModal.setAttribute('aria-hidden', String(!show));
}
if (openSizeGuideBtn) openSizeGuideBtn.addEventListener('click', () => toggleSizeGuide(true));
if (closeSizeGuideBtn) closeSizeGuideBtn.addEventListener('click', () => toggleSizeGuide(false));
if (sizeGuideBackdrop) sizeGuideBackdrop.addEventListener('click', () => toggleSizeGuide(false));

/* ── Product Gallery ───────────────────────────────────── */
document.querySelectorAll('.gallery-thumbs img').forEach((thumbnail) => {
  thumbnail.addEventListener('click', () => {
    const mainImage = document.querySelector('.gallery-main img');
    if (!mainImage) return;
    mainImage.style.opacity = '0.3';
    setTimeout(() => {
      mainImage.src = thumbnail.src;
      mainImage.style.opacity = '1';
    }, 150);
    document.querySelectorAll('.gallery-thumbs img').forEach((item) => {
      item.classList.toggle('active', item === thumbnail);
    });
  });
});

/* ── Payment Method ────────────────────────────────────── */
document.querySelectorAll('.payment-option').forEach((option) => {
  option.addEventListener('click', () => {
    const radio = option.querySelector('input[type="radio"]');
    if (radio) {
      radio.checked = true;
      document.querySelectorAll('.payment-option').forEach((opt) => {
        const r = opt.querySelector('input[type="radio"]');
        opt.classList.toggle('selected', Boolean(r && r.checked));
      });
    }
  });
});

document.querySelectorAll('[data-copy-value]').forEach((button) => {
  button.addEventListener('click', async () => {
    const value = button.dataset.copyValue || '';
    if (!value) return;
    try {
      await navigator.clipboard.writeText(value);
      const original = button.innerHTML;
      button.innerHTML = '<i class="fas fa-check"></i> Đã sao chép';
      window.setTimeout(() => { button.innerHTML = original; }, 1600);
    } catch (_) {
      window.prompt('Sao chép nội dung chuyển khoản:', value);
    }
  });
});

/* ── Cart Actions ──────────────────────────────────────── */
async function postCartAction(action, cartId, quantity, size = '') {
  const response = await fetch(BASE_URL + '/cart/' + action, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': CSRF_TOKEN,
    },
    body: JSON.stringify({
      cart_id: cartId,
      quantity,
      size,
      csrf_token: CSRF_TOKEN,
    }),
  });
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.message || 'Không thể cập nhật giỏ hàng.');
  }
  return data;
}

/* ══ PREMIUM CUSTOM CONFIRMATION MODAL ENGINE ══════════════ */
let modalBackdrop = null;
let modalDialog = null;
let modalIconWrap = null;
let modalIcon = null;
let modalTitle = null;
let modalMsg = null;
let modalCancelBtn = null;
let modalOkBtn = null;
let currentResolve = null;

function ensureModalDOMElements() {
  if (modalBackdrop) return;

  modalBackdrop = document.createElement('div');
  modalBackdrop.className = 'custom-modal-backdrop';
  modalBackdrop.id = 'customModalBackdrop';

  modalDialog = document.createElement('div');
  modalDialog.className = 'custom-modal-dialog';
  modalDialog.id = 'customModalDialog';
  modalDialog.setAttribute('role', 'dialog');
  modalDialog.setAttribute('aria-modal', 'true');

  modalDialog.innerHTML = `
    <div class="custom-modal-icon-wrap type-primary" id="customModalIconWrap">
      <i class="fa-solid fa-circle-question" id="customModalIcon"></i>
    </div>
    <h3 class="custom-modal-title" id="customModalTitle">Xác nhận thao tác</h3>
    <p class="custom-modal-message" id="customModalMsg"></p>
    <div class="custom-modal-actions">
      <button type="button" class="custom-modal-btn custom-modal-cancel" id="customModalCancel">Hủy bỏ</button>
      <button type="button" class="custom-modal-btn custom-modal-ok type-primary" id="customModalOk">Xác nhận</button>
    </div>
  `;

  document.body.appendChild(modalBackdrop);
  document.body.appendChild(modalDialog);

  modalIconWrap = document.getElementById('customModalIconWrap');
  modalIcon = document.getElementById('customModalIcon');
  modalTitle = document.getElementById('customModalTitle');
  modalMsg = document.getElementById('customModalMsg');
  modalCancelBtn = document.getElementById('customModalCancel');
  modalOkBtn = document.getElementById('customModalOk');

  function closeModal(result) {
    modalBackdrop.classList.remove('is-open');
    modalDialog.classList.remove('is-open');
    if (currentResolve) {
      currentResolve(result);
      currentResolve = null;
    }
  }

  modalCancelBtn.addEventListener('click', () => closeModal(false));
  modalOkBtn.addEventListener('click', () => closeModal(true));
  modalBackdrop.addEventListener('click', () => closeModal(false));

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalDialog.classList.contains('is-open')) {
      closeModal(false);
    }
  });
}

function showCustomConfirm(options) {
  ensureModalDOMElements();
  const config = typeof options === 'string' ? { message: options } : (options || {});
  const message = config.message || 'Bạn có chắc chắn muốn thực hiện thao tác này?';

  let type = config.type;
  if (!type) {
    if (/xóa|hủy|khóa|từ chối|bỏ|trả/i.test(message)) {
      type = 'danger';
    } else if (/xác nhận|nhận|thành công|hoàn tất/i.test(message)) {
      type = 'success';
    } else if (/cảnh báo|lưu ý|yêu cầu/i.test(message)) {
      type = 'warning';
    } else {
      type = 'primary';
    }
  }

  const title = config.title || (type === 'danger' ? 'Xác nhận xóa / hủy' : (type === 'success' ? 'Xác nhận thành công' : 'Thông báo xác nhận'));
  const confirmText = config.confirmText || (type === 'danger' ? 'Đồng ý' : (type === 'success' ? 'Xác nhận' : 'Tiếp tục'));
  const cancelText = config.cancelText || 'Hủy bỏ';
  const isAlertOnly = config.isAlert === true;

  modalIconWrap.className = 'custom-modal-icon-wrap type-' + type;
  if (type === 'danger') {
    modalIcon.className = 'fa-solid fa-triangle-exclamation';
  } else if (type === 'success') {
    modalIcon.className = 'fa-solid fa-circle-check';
  } else if (type === 'warning') {
    modalIcon.className = 'fa-solid fa-circle-exclamation';
  } else {
    modalIcon.className = 'fa-solid fa-circle-question';
  }

  modalTitle.textContent = title;
  modalMsg.textContent = message;
  modalOkBtn.className = 'custom-modal-btn custom-modal-ok type-' + type;
  modalOkBtn.innerHTML = (type === 'danger' ? '<i class="fa-solid fa-trash-can"></i> ' : (type === 'success' ? '<i class="fa-solid fa-check"></i> ' : '<i class="fa-solid fa-arrow-right"></i> ')) + confirmText;
  modalCancelBtn.textContent = cancelText;
  modalCancelBtn.style.display = isAlertOnly ? 'none' : '';

  modalBackdrop.classList.add('is-open');
  modalDialog.classList.add('is-open');

  return new Promise((resolve) => {
    currentResolve = resolve;
  });
}

window.showConfirmDialog = showCustomConfirm;

// Auto-handle forms and buttons with data-confirm
document.addEventListener('submit', async (event) => {
  const form = event.target.closest('form[data-confirm]');
  if (!form) return;
  if (form.dataset.confirmed === 'true') {
    form.dataset.confirmed = 'false';
    return;
  }
  event.preventDefault();
  const message = form.dataset.confirm || 'Bạn có chắc chắn muốn thực hiện?';
  const confirmed = await showCustomConfirm({ message });
  if (confirmed) {
    form.dataset.confirmed = 'true';
    form.submit();
  }
});

document.addEventListener('change', async (event) => {
  const sizeSelect = event.target.closest('.js-cart-size');
  if (!sizeSelect) return;
  const cartId = Number.parseInt(sizeSelect.dataset.cartId, 10);
  const previousSize = sizeSelect.dataset.currentSize || '';
  if (!cartId || sizeSelect.value === previousSize) return;
  sizeSelect.disabled = true;
  try {
    const data = await postCartAction('update-size', cartId, 1, sizeSelect.value);
    if (data.merged && data.removed_cart_id) {
      const removed = document.querySelector('.js-cart-size[data-cart-id="' + data.removed_cart_id + '"]');
      const removedItem = removed && removed.closest('.cart-item');
      const target = document.querySelector('.js-cart-size[data-cart-id="' + data.cart_id + '"]');
      const targetItem = target && target.closest('.cart-item');
      const quantityInput = targetItem && targetItem.querySelector('.cart-quantity input[type="number"]');
      if (quantityInput) {
        quantityInput.value = String(data.quantity);
        quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (removedItem) removedItem.remove();
      document.dispatchEvent(new Event('cart-summary-refresh'));
    }
    sizeSelect.dataset.currentSize = sizeSelect.value;
    sizeSelect.disabled = false;
    showToast(data.message);
  } catch (error) {
    showToast(error.message || 'Không thể đổi size.', 'error');
    sizeSelect.value = previousSize;
    sizeSelect.disabled = false;
  }
});

document.addEventListener('click', async (event) => {
  const btn = event.target.closest('[data-confirm-click]');
  if (btn) {
    event.preventDefault();
    const message = btn.dataset.confirmClick || 'Bạn có chắc chắn muốn thực hiện?';
    const confirmed = await showCustomConfirm({ message });
    if (confirmed) {
      const form = btn.closest('form');
      if (form) {
        // If button has a name and value, append hidden input or click it
        if (btn.name && btn.value) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = btn.name;
          hidden.value = btn.value;
          form.appendChild(hidden);
        }
        form.submit();
      } else if (btn.href) {
        window.location.href = btn.href;
      }
    }
    return;
  }

  const updateButton = event.target.closest('.js-cart-update');
  const removeButton = event.target.closest('.js-cart-remove');
  const button = updateButton || removeButton;
  if (!button) return;

  const cartId = Number.parseInt(button.dataset.cartId, 10);
  if (!cartId) return;

  if (removeButton) {
    const confirmed = await showCustomConfirm({
      title: 'Xóa khỏi giỏ hàng?',
      message: 'Bạn có chắc chắn muốn bỏ sản phẩm này ra khỏi giỏ hàng không?',
      type: 'danger',
      confirmText: 'Xóa sản phẩm',
      cancelText: 'Giữ lại'
    });
    if (!confirmed) return;
  }

  const item = button.closest('.cart-item');
  const quantityInput = item && item.querySelector('input[type="number"]');
  const quantity = Math.max(1, Number.parseInt(quantityInput && quantityInput.value, 10) || 1);
  button.disabled = true;

  try {
    const data = await postCartAction(removeButton ? 'remove' : 'update', cartId, quantity);
    showToast(data.message);
    window.setTimeout(() => window.location.reload(), 350);
  } catch (error) {
    showToast(error.message || 'Không thể cập nhật giỏ hàng.', 'error');
    button.disabled = false;
  }
});

/* ── Select Cart Items for Checkout ────────────────────── */
(function () {
  const form = document.getElementById('selectedCheckoutForm');
  if (!form) return;

  const itemCheckboxes = () => Array.from(form.querySelectorAll('.js-cart-select'));
  const quantityInputs = Array.from(form.querySelectorAll('.cart-quantity input[type="number"]'));
  const selectAll = document.getElementById('selectAllCartItems');
  const countElement = document.getElementById('selectedCartCount');
  const subtotalElement = document.getElementById('selectedCartSubtotal');
  const totalElement = document.getElementById('selectedCartTotal');
  const checkoutButton = document.getElementById('checkoutSelectedButton');
  const formatPrice = value => new Intl.NumberFormat('vi-VN').format(value) + ' đ';

  function updateSelectedSummary() {
    const boxes = itemCheckboxes();
    const selected = boxes.filter(checkbox => checkbox.checked);
    const total = selected.reduce(
      (sum, checkbox) => sum + (Number.parseInt(checkbox.dataset.lineTotal, 10) || 0),
      0
    );
    const selectedQuantity = selected.reduce((sum, checkbox) => {
      const item = checkbox.closest('.cart-item');
      const quantityInput = item && item.querySelector('.cart-quantity input[type="number"]');
      return sum + Math.max(0, Number.parseInt(quantityInput && quantityInput.value, 10) || 0);
    }, 0);

    if (countElement) countElement.textContent = String(selectedQuantity);
    if (subtotalElement) subtotalElement.textContent = formatPrice(total);
    if (totalElement) totalElement.textContent = formatPrice(total);
    if (checkoutButton) checkoutButton.disabled = selected.length === 0;
    if (selectAll) {
      const boxes = itemCheckboxes();
      selectAll.checked = selected.length === boxes.length && boxes.length > 0;
      selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
    }
  }

  document.addEventListener('cart-summary-refresh', updateSelectedSummary);
  itemCheckboxes().forEach(checkbox => checkbox.addEventListener('change', updateSelectedSummary));
  quantityInputs.forEach(input => {
    const updateLineTotal = (normalize = false) => {
      const item = input.closest('.cart-item');
      const checkbox = item && item.querySelector('.js-cart-select');
      const lineTotalElement = item && item.querySelector('.cart-line-total');
      const unitPrice = Number.parseInt(input.dataset.unitPrice, 10) || 0;
      const max = Math.max(1, Number.parseInt(input.max, 10) || 1);
      let quantity = Number.parseInt(input.value, 10);
      if (!Number.isFinite(quantity)) quantity = 0;
      if (normalize) {
        quantity = Math.min(max, Math.max(1, quantity));
        input.value = String(quantity);
      }
      const lineTotal = unitPrice * Math.min(max, Math.max(0, quantity));
      if (lineTotalElement) lineTotalElement.textContent = formatPrice(lineTotal);
      if (checkbox) checkbox.dataset.lineTotal = String(lineTotal);
      updateSelectedSummary();
    };
    input.addEventListener('input', () => updateLineTotal(false));
    input.addEventListener('change', () => updateLineTotal(true));
  });
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      itemCheckboxes().forEach(checkbox => { checkbox.checked = selectAll.checked; });
      updateSelectedSummary();
    });
  }
  form.addEventListener('submit', event => {
    if (!itemCheckboxes().some(checkbox => checkbox.checked)) {
      event.preventDefault();
      showToast('Vui lòng chọn ít nhất một sản phẩm để thanh toán.', 'error');
    }
  });

  updateSelectedSummary();
})();

/* ── Scroll Reveal Animation Engine ───────────────────────── */
(function () {
  const SELECTOR = '.reveal, .reveal-left, .reveal-right, .reveal-scale';
  const elements = document.querySelectorAll(SELECTOR);
  if (!elements.length) return;

  const observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1, rootMargin: '0px 0px -20px 0px' }
  );

  elements.forEach(function (el) { observer.observe(el); });
})();
