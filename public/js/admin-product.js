(function () {
  const body = document.body;
  const toggle = document.querySelector('[data-admin-sidebar-toggle]');
  const closeTargets = document.querySelectorAll('[data-admin-sidebar-close]');

  function setSidebar(open) {
    body.classList.toggle('admin-sidebar-open', open);
    if (toggle) toggle.setAttribute('aria-expanded', String(open));
  }

  if (toggle) {
    toggle.addEventListener('click', () => {
      setSidebar(!body.classList.contains('admin-sidebar-open'));
    });
  }
  closeTargets.forEach((target) => target.addEventListener('click', () => setSidebar(false)));

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
      if (/xóa|hủy|khóa|từ chối|dừng|ẩn/i.test(message)) {
        type = 'danger';
      } else if (/xác nhận|mở khóa|hiển thị|nhận|thành công/i.test(message)) {
        type = 'success';
      } else if (/cảnh báo|lưu ý|yêu cầu/i.test(message)) {
        type = 'warning';
      } else {
        type = 'primary';
      }
    }

    const title = config.title || (type === 'danger' ? 'Xác nhận xóa / hủy' : (type === 'success' ? 'Xác nhận hoàn tất' : 'Xác nhận thao tác'));
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

  window.showAdminConfirm = showCustomConfirm;

  document.querySelectorAll('form[data-admin-confirm]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      if (form.dataset.confirmed === 'true') {
        form.dataset.confirmed = 'false';
        return;
      }
      event.preventDefault();
      const message = form.dataset.adminConfirm || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
      const confirmed = await showCustomConfirm({ message });
      if (confirmed) {
        form.dataset.confirmed = 'true';
        form.submit();
      }
    });
  });

  const imageInput = document.getElementById('productImage');
  const imagePreview = document.getElementById('adminImagePreview');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', () => {
      const file = imageInput.files && imageInput.files[0];
      if (!file || !file.type.startsWith('image/')) return;
      const objectUrl = URL.createObjectURL(file);
      imagePreview.src = objectUrl;
      imagePreview.onload = () => URL.revokeObjectURL(objectUrl);
    });
  }

  const categorySelect = document.getElementById('categoryId');
  const newCategoryFields = document.getElementById('newCategoryFields');
  const newCategoryName = document.getElementById('newCategoryName');
  function toggleNewCategory() {
    if (!categorySelect || !newCategoryFields || !newCategoryName) return;
    const creating = categorySelect.value === '__new__';
    newCategoryFields.hidden = !creating;
    newCategoryName.required = creating;
    if (creating) newCategoryName.focus();
  }
  if (categorySelect) {
    categorySelect.addEventListener('change', toggleNewCategory);
    toggleNewCategory();
  }

  const replyModal = document.getElementById('reviewReplyModal');
  const replyForm = document.getElementById('reviewReplyForm');
  const replyMessage = document.getElementById('reviewReplyMessage');
  const replyUser = document.getElementById('reviewReplyUser');
  const reviewBaseUrl = window.location.pathname.split('/admin-review/')[0];

  function setReplyModal(open) {
    if (!replyModal) return;
    replyModal.classList.toggle('is-open', open);
    replyModal.setAttribute('aria-hidden', String(!open));
    body.classList.toggle('admin-modal-open', open);
    if (open && replyMessage) replyMessage.focus();
  }

  document.querySelectorAll('.admin-review-reply').forEach((button) => {
    button.addEventListener('click', () => {
      if (!replyForm) return;
      replyForm.action = reviewBaseUrl + '/admin-review/reply/' + button.dataset.reviewId;
      if (replyUser) replyUser.textContent = button.dataset.reviewUser || 'khách hàng';
      if (replyMessage) replyMessage.value = button.dataset.reviewReply || '';
      setReplyModal(true);
    });
  });
  document.querySelectorAll('[data-review-modal-close]').forEach((button) => {
    button.addEventListener('click', () => setReplyModal(false));
  });
  if (replyModal) {
    replyModal.addEventListener('click', (event) => {
      if (event.target === replyModal) setReplyModal(false);
    });
  }
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setReplyModal(false);
  });

  const orderModal = document.getElementById('orderStatusModal');
  const orderForm = document.getElementById('orderStatusForm');
  const orderSelect = document.getElementById('orderStatusSelect');
  const orderCode = document.getElementById('orderStatusCode');
  const orderWarning = document.getElementById('orderStatusWarning');
  const orderBaseUrl = window.location.pathname.split('/admin-order/')[0];
  let orderLabels = {};
  if (orderModal) {
    try {
      orderLabels = JSON.parse(orderModal.dataset.statusLabels || '{}');
    } catch (_) {
      orderLabels = {};
    }
  }

  function setOrderModal(open) {
    if (!orderModal) return;
    orderModal.classList.toggle('is-open', open);
    orderModal.setAttribute('aria-hidden', String(!open));
    body.classList.toggle('admin-modal-open', open);
    if (open && orderSelect) orderSelect.focus();
  }

  document.querySelectorAll('.admin-order-status-button').forEach((button) => {
    button.addEventListener('click', () => {
      if (!orderForm || !orderSelect) return;
      let options = [];
      try {
        options = JSON.parse(button.dataset.orderOptions || '[]');
      } catch (_) {
        options = [];
      }
      orderSelect.innerHTML = '';
      options.forEach((status) => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = orderLabels[status] || status;
        orderSelect.appendChild(option);
      });
      orderForm.action = orderBaseUrl + '/admin-order/status/' + button.dataset.orderId;
      if (orderCode) orderCode.textContent = '#' + button.dataset.orderId;
      if (orderWarning) orderWarning.hidden = !['Da_Huy', 'Da_Tra_Hang'].includes(orderSelect.value);
      setOrderModal(true);
    });
  });
  if (orderSelect && orderWarning) {
    orderSelect.addEventListener('change', () => {
      orderWarning.hidden = !['Da_Huy', 'Da_Tra_Hang'].includes(orderSelect.value);
    });
  }
  document.querySelectorAll('[data-order-modal-close]').forEach((button) => {
    button.addEventListener('click', () => setOrderModal(false));
  });
  if (orderModal) {
    orderModal.addEventListener('click', (event) => {
      if (event.target === orderModal) setOrderModal(false);
    });
  }
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOrderModal(false);
  });

  const revenueDataElement = document.getElementById('adminRevenueData');
  if (revenueDataElement && window.Chart) {
    let revenueData = {};
    try {
      revenueData = JSON.parse(revenueDataElement.textContent || '{}');
    } catch (_) {
      revenueData = {};
    }
    const currency = new Intl.NumberFormat('vi-VN');
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label(context) {
              return ' Doanh thu: ' + currency.format(context.raw || 0) + ' đ';
            },
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#77849a', font: { size: 9 }, maxTicksLimit: 12 },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(136, 153, 181, .16)' },
          ticks: {
            color: '#77849a',
            font: { size: 9 },
            callback(value) {
              if (value >= 1000000000) return (value / 1000000000) + ' tỷ';
              if (value >= 1000000) return (value / 1000000) + ' tr';
              if (value >= 1000) return (value / 1000) + ' k';
              return value;
            },
          },
        },
      },
    };

    function renderRevenueChart(id, series, type, color, hoverColor) {
      const canvas = document.getElementById(id);
      if (!canvas || !series) return;
      new window.Chart(canvas, {
        type,
        data: {
          labels: series.labels || [],
          datasets: [{
            data: series.revenue || [],
            borderColor: color,
            backgroundColor: type === 'line' ? 'rgba(0, 0, 0, 0.07)' : color,
            hoverBackgroundColor: hoverColor || color,
            borderWidth: type === 'line' ? 2.5 : 0,
            pointRadius: type === 'line' && (series.labels || []).length < 45 ? 3.5 : 0,
            pointBackgroundColor: '#000000',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 1.5,
            pointHoverRadius: 5.5,
            fill: type === 'line',
            tension: 0.35,
            borderRadius: type === 'bar' ? 6 : 0,
            maxBarThickness: 34,
          }],
        },
        options: chartOptions,
      });
    }

    renderRevenueChart('revenueDailyChart', revenueData.daily, 'line', '#000000');
    renderRevenueChart('revenueMonthlyChart', revenueData.monthly, 'bar', '#18181b', '#000000');
    renderRevenueChart('revenueYearlyChart', revenueData.yearly, 'bar', '#3f3f46', '#18181b');
  }

  /* ── Smooth Animated Number Counter ─────────────────────── */
  function animateCounters() {
    const statElements = document.querySelectorAll(
      '.admin-dashboard-stat strong, .admin-revenue-stat strong'
    );
    statElements.forEach((el) => {
      const text = el.textContent.trim();
      const isCurrency = text.endsWith('đ') || text.endsWith(' đ');
      const numericString = text.replace(/[^\d]/g, '');
      const targetValue = parseInt(numericString, 10);
      if (isNaN(targetValue) || targetValue === 0) return;

      const duration = 1200;
      const startTime = performance.now();

      function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Easing out expo
        const easeOut = 1 - Math.pow(2, -10 * progress);
        const currentVal = Math.floor(targetValue * easeOut);
        const formatted = currentVal.toLocaleString('vi-VN');
        el.textContent = isCurrency ? formatted + ' đ' : formatted;
        if (progress < 1) {
          requestAnimationFrame(update);
        } else {
          el.textContent = isCurrency ? targetValue.toLocaleString('vi-VN') + ' đ' : targetValue.toLocaleString('vi-VN');
        }
      }
      requestAnimationFrame(update);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', animateCounters);
  } else {
    animateCounters();
  }

  /* ── Table Row Stagger Animations ───────────────────────── */
  const tableRows = document.querySelectorAll('.admin-table tbody tr');
  tableRows.forEach((row, i) => {
    row.style.animation = `adminFadeUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) both ${0.04 * (i + 1)}s`;
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) setSidebar(false);
  });
})();
