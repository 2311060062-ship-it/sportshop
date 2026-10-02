<?php
/** Contract: summary,from,to,chartYear,chartData,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = 'Quản lý doanh thu';
$adminActive = 'revenue';
$adminChart = true;
$summary = is_array($summary ?? null) ? $summary : [];
$chartData = is_array($chartData ?? null) ? $chartData : [];
$from = (string) ($from ?? date('Y-m-01'));
$to = (string) ($to ?? date('Y-m-d'));
$chartYear = (int) ($chartYear ?? date('Y'));
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow">Báo cáo kinh doanh</span>
    <h1>Doanh thu</h1>
    <p>Doanh thu được ghi nhận từ giao dịch đã thanh toán và chưa bị hủy.</p>
  </div>
  <div class="admin-heading-actions" style="margin-left:auto;">
    <a href="<?= $adminBaseUrl ?>/admin-revenue/export?from=<?= $adminEscape($from) ?>&to=<?= $adminEscape($to) ?>" class="admin-btn admin-btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
      <i class="fa-solid fa-file-excel"></i> Xuất Báo Cáo Excel
    </a>
  </div>
</section>

<section class="admin-card admin-revenue-filter-card">
  <form class="admin-revenue-filter" method="GET" action="<?= $adminBaseUrl ?>/admin-revenue/index">
    <label><span>Từ ngày</span><input type="date" name="from" value="<?= $adminEscape($from) ?>" required></label>
    <label><span>Đến ngày</span><input type="date" name="to" value="<?= $adminEscape($to) ?>" required></label>
    <button class="admin-btn admin-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Xem báo cáo</button>
    <a href="<?= $adminBaseUrl ?>/admin-revenue/export?from=<?= $adminEscape($from) ?>&to=<?= $adminEscape($to) ?>" class="admin-btn admin-btn-secondary" style="display:inline-flex;align-items:center;gap:8px;background:#f5f5f5;color:#000000;border:1.5px solid #000000;">
      <i class="fa-solid fa-download"></i> Xuất Excel (.csv)
    </a>
  </form>
  <p><i class="fa-regular fa-calendar"></i> Khoảng báo cáo tối đa 366 ngày</p>
</section>

<section class="admin-revenue-summary" aria-label="Số liệu tổng quan">
  <article class="admin-revenue-stat stat-users">
    <span><i class="fa-solid fa-users"></i></span>
    <div><small>Khách hàng</small><strong><?= number_format((int) ($summary['users'] ?? 0), 0, ',', '.') ?></strong></div>
  </article>
  <article class="admin-revenue-stat stat-products">
    <span><i class="fa-solid fa-box"></i></span>
    <div><small>Sản phẩm đang bán</small><strong><?= number_format((int) ($summary['products'] ?? 0), 0, ',', '.') ?></strong></div>
  </article>
  <article class="admin-revenue-stat stat-orders">
    <span><i class="fa-solid fa-cart-shopping"></i></span>
    <div><small>Đơn đã thanh toán trong kỳ</small><strong><?= number_format((int) ($summary['orders'] ?? 0), 0, ',', '.') ?></strong></div>
  </article>
  <article class="admin-revenue-stat stat-revenue">
    <span><i class="fa-solid fa-wallet"></i></span>
    <div><small>Tổng doanh thu</small><strong><?= number_format((int) ($summary['revenue'] ?? 0), 0, ',', '.') ?> đ</strong></div>
  </article>
</section>

<section class="admin-revenue-charts">
  <article class="admin-card admin-chart-card admin-chart-wide">
    <header><div><h2>Doanh thu theo ngày</h2><p><?= date('d/m/Y', strtotime($from)) ?> – <?= date('d/m/Y', strtotime($to)) ?></p></div><span class="admin-chart-legend"><i></i> Doanh thu</span></header>
    <div class="admin-chart-area"><canvas id="revenueDailyChart"></canvas></div>
  </article>
  <article class="admin-card admin-chart-card">
    <header><div><h2>Doanh thu theo tháng</h2><p>Năm <?= $chartYear ?></p></div></header>
    <div class="admin-chart-area"><canvas id="revenueMonthlyChart"></canvas></div>
  </article>
  <article class="admin-card admin-chart-card admin-chart-yearly">
    <header>
      <div><h2>Doanh thu theo năm</h2><p>5 năm gần nhất tính đến <?= $chartYear ?></p></div>
      <div class="admin-average-order"><small>Giá trị đơn trung bình</small><strong><?= number_format((int) ($summary['average_order'] ?? 0), 0, ',', '.') ?> đ</strong></div>
    </header>
    <div class="admin-chart-area"><canvas id="revenueYearlyChart"></canvas></div>
  </article>
</section>

<script id="adminRevenueData" type="application/json"><?= json_encode(
    $chartData,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?></script>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
