<?php
/** Contract: summary,recentOrders,lowStockProducts,recentReviews,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$summary = is_array($summary ?? null) ? $summary : [];
$recentOrders = is_array($recentOrders ?? null) ? $recentOrders : [];
$lowStockProducts = is_array($lowStockProducts ?? null) ? $lowStockProducts : [];
$recentReviews = is_array($recentReviews ?? null) ? $recentReviews : [];
$pageTitle = 'Trang quản trị';
$adminActive = 'dashboard';
$statusLabels = [
    'Cho_Thanh_Toan' => 'Chờ thanh toán', 'Dang_Xu_Ly' => 'Đang xử lý',
    'Dang_Giao' => 'Đang giao', 'Da_Giao' => 'Chờ khách xác nhận',
    'Da_Nhan_Hang' => 'Khách đã nhận', 'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
    'Da_Tra_Hang' => 'Đã trả hàng',
    'Yeu_Cau_Huy' => 'Yêu cầu hủy', 'Da_Huy' => 'Đã hủy',
];
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div>
    <span class="admin-eyebrow">Tổng quan hệ thống</span>
    <h1>Xin chào, <?= $adminEscape($authUser['username'] ?? 'Admin') ?></h1>
    <p>Theo dõi nhanh hoạt động của TrendStyle Fashion từ một màn hình.</p>
  </div>
  <span class="admin-dashboard-date"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y') ?></span>
</section>

<section class="admin-dashboard-stats">
  <a class="admin-dashboard-stat stat-blue" href="<?= $adminBaseUrl ?>/admin-user/index"><span><i class="fa-solid fa-users"></i></span><div><small>Khách hàng</small><strong><?= number_format((int) ($summary['users'] ?? 0), 0, ',', '.') ?></strong><em>Xem người dùng <i class="fa-solid fa-arrow-right"></i></em></div></a>
  <a class="admin-dashboard-stat stat-green" href="<?= $adminBaseUrl ?>/admin-product/index"><span><i class="fa-solid fa-shirt"></i></span><div><small>Sản phẩm đang bán</small><strong><?= number_format((int) ($summary['products'] ?? 0), 0, ',', '.') ?></strong><em><?= (int) ($summary['low_stock'] ?? 0) ?> sắp hết hàng</em></div></a>
  <a class="admin-dashboard-stat stat-orange" href="<?= $adminBaseUrl ?>/admin-order/index"><span><i class="fa-solid fa-cart-shopping"></i></span><div><small>Tổng đơn hàng</small><strong><?= number_format((int) ($summary['orders'] ?? 0), 0, ',', '.') ?></strong><em><?= (int) ($summary['pending_orders'] ?? 0) ?> cần xử lý</em></div></a>
  <a class="admin-dashboard-stat stat-purple" href="<?= $adminBaseUrl ?>/admin-revenue/index"><span><i class="fa-solid fa-chart-line"></i></span><div><small>Doanh thu đã thanh toán</small><strong><?= number_format((int) ($summary['revenue'] ?? 0), 0, ',', '.') ?> đ</strong><em>Xem báo cáo <i class="fa-solid fa-arrow-right"></i></em></div></a>
</section>

<div class="admin-dashboard-grid">
  <section class="admin-card admin-dashboard-panel dashboard-orders">
    <header><div><h2>Đơn hàng mới</h2><p>Các đơn được tạo gần đây</p></div><a href="<?= $adminBaseUrl ?>/admin-order/index">Xem tất cả</a></header>
    <?php if (!$recentOrders): ?>
      <div class="admin-mini-empty">Chưa có đơn hàng.</div>
    <?php else: ?>
      <div class="admin-table-wrap"><table class="admin-table admin-dashboard-table"><thead><tr><th>Đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>
      <?php foreach ($recentOrders as $order): ?>
        <tr>
          <td><a href="<?= $adminBaseUrl ?>/admin-order/detail/<?= (int) $order['id'] ?>"><strong>#<?= (int) $order['id'] ?></strong></a><small><?= date('d/m H:i', strtotime((string) $order['date'])) ?></small></td>
          <td><?= $adminEscape($order['full_name'] ?: $order['username']) ?></td>
          <td><strong><?= number_format((int) $order['total'], 0, ',', '.') ?> đ</strong></td>
          <td><span class="admin-order-status"><?= $adminEscape($statusLabels[$order['status_order']] ?? $order['status_order']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </section>

  <section class="admin-card admin-dashboard-panel">
    <header><div><h2>Cảnh báo tồn kho</h2><p>Sản phẩm còn không quá 5</p></div><a href="<?= $adminBaseUrl ?>/admin-product/index">Quản lý</a></header>
    <div class="admin-dashboard-list">
      <?php if (!$lowStockProducts): ?><div class="admin-mini-empty">Tồn kho đang ổn định.</div><?php endif; ?>
      <?php foreach ($lowStockProducts as $product):
          $image = basename((string) ($product['thumbnail'] ?? ''));
          $imageUrl = $image ? BASE_URL . '/public/images/products/' . rawurlencode($image) : $placeholder;
      ?>
        <a href="<?= $adminBaseUrl ?>/admin-product/edit/<?= (int) $product['id'] ?>">
          <img src="<?= $adminEscape($imageUrl) ?>" onerror="this.src='<?= $adminEscape($placeholder) ?>'" alt="">
          <span><strong><?= $adminEscape($product['name']) ?></strong><small>Mã SP #<?= (int) $product['id'] ?></small></span>
          <em class="<?= (int) $product['quantity'] === 0 ? 'is-empty' : '' ?>"><?= (int) $product['quantity'] ?> còn lại</em>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="admin-card admin-dashboard-panel dashboard-reviews">
    <header><div><h2>Đánh giá gần đây</h2><p>Phản hồi mới nhất từ khách hàng</p></div><a href="<?= $adminBaseUrl ?>/admin-review/index">Xem tất cả</a></header>
    <div class="admin-dashboard-list">
      <?php if (!$recentReviews): ?><div class="admin-mini-empty">Chưa có đánh giá.</div><?php endif; ?>
      <?php foreach ($recentReviews as $review): ?>
        <div class="admin-dashboard-review">
          <span class="admin-review-avatar"><?= $adminEscape(strtoupper(substr((string) $review['username'], 0, 1))) ?></span>
          <div><strong><?= $adminEscape($review['full_name'] ?: $review['username']) ?></strong><small><?= $adminEscape($review['product_name']) ?></small><p><?= $adminEscape($review['messages']) ?></p></div>
          <em><?= str_repeat('★', max(0, min(5, (int) $review['rate']))) ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
