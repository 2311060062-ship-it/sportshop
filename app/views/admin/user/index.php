<?php
/** Contract: users,total,page,totalPages,keyword,status,flash,csrfToken,authUser. */
$adminEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$users = is_array($users ?? null) ? $users : [];
$total = max(0, (int) ($total ?? count($users)));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$status = in_array(($status ?? ''), ['Active', 'Closed'], true) ? (string) $status : '';
$pageTitle = 'Quản lý người dùng';
$adminActive = 'users';
$paginationUrl = static function (int $target) use ($keyword, $status, $adminEscape): string {
    return $adminEscape(BASE_URL . '/admin-user/index?' . http_build_query(array_filter([
        'q' => $keyword, 'status' => $status, 'page' => $target,
    ], static fn ($value): bool => $value !== '')));
};
require APP_PATH . '/views/admin/layouts/header.php';
?>

<section class="admin-page-heading">
  <div><span class="admin-eyebrow">Tài khoản khách hàng</span><h1>Người dùng</h1><p>Tìm kiếm, quản lý thông tin, khóa hoặc xóa tài khoản khách hàng.</p></div>
</section>

<?php if (!empty($flash)):
    $flashType = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
?>
  <div class="admin-alert admin-alert-<?= $adminEscape($flashType) ?>"><i class="fa-solid <?= $flashType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i><span><?= $adminEscape($flash['msg'] ?? '') ?></span></div>
<?php endif; ?>

<section class="admin-card">
  <div class="admin-card-header">
    <form class="admin-filters" method="GET" action="<?= $adminBaseUrl ?>/admin-user/index">
      <label class="admin-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= $adminEscape($keyword) ?>" placeholder="Tên, email hoặc số điện thoại..."></label>
      <label class="admin-filter-select"><span class="admin-sr-only">Trạng thái</span><select name="status">
        <option value="">Tất cả trạng thái</option>
        <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Đang hoạt động</option>
        <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Đã khóa</option>
      </select></label>
      <button class="admin-btn admin-btn-secondary" type="submit">Lọc</button>
      <?php if ($keyword !== '' || $status !== ''): ?><a class="admin-clear-filter" href="<?= $adminBaseUrl ?>/admin-user/index">Xóa lọc</a><?php endif; ?>
    </form>
    <span class="admin-result-count"><?= number_format($total, 0, ',', '.') ?> khách hàng</span>
  </div>

  <?php if (!$users): ?>
    <div class="admin-empty"><span><i class="fa-solid fa-user-slash"></i></span><h2>Không tìm thấy người dùng</h2><p>Hãy thử thay đổi từ khóa hoặc trạng thái lọc.</p></div>
  <?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table admin-user-table">
      <thead><tr><th>Người dùng</th><th>Liên hệ</th><th>Ngày đăng ký</th><th>Đơn hàng</th><th>Đã chi tiêu</th><th>Trạng thái</th><th style="text-align:right">Thao tác</th></tr></thead>
      <tbody>
      <?php foreach ($users as $user):
          $name = (string) ($user['full_name'] ?: $user['username']);
          $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($name, 0, 1));
          $active = $user['status_user'] === 'Active';
      ?>
        <tr>
          <td><div class="admin-user-cell"><span><?= $adminEscape($initial) ?></span><div><strong><?= $adminEscape($name) ?></strong><small>@<?= $adminEscape($user['username']) ?> · #<?= (int) $user['id'] ?></small></div></div></td>
          <td><strong><?= $adminEscape($user['email'] ?: 'Chưa cập nhật') ?></strong><small><?= $adminEscape($user['phone'] ?: 'Chưa có SĐT') ?></small></td>
          <td><?= !empty($user['create_date']) ? date('d/m/Y', strtotime((string) $user['create_date'])) : '—' ?></td>
          <td><?= number_format((int) $user['order_count'], 0, ',', '.') ?></td>
          <td><strong class="admin-price"><?= number_format((int) $user['total_spent'], 0, ',', '.') ?> đ</strong></td>
          <td><span class="admin-status <?= $active ? 'admin-status-active' : 'admin-status-closed' ?>"><i></i><?= $active ? 'Hoạt động' : 'Đã khóa' ?></span></td>
          <td>
            <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center;">
              <!-- Nút Chỉnh sửa -->
              <a class="admin-icon-btn" href="<?= $adminBaseUrl ?>/admin-user/edit/<?= (int) $user['id'] ?>" title="Xem & Chỉnh sửa thông tin">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>

              <!-- Nút Khóa / Mở khóa -->
              <form method="POST" action="<?= $adminBaseUrl ?>/admin-user/toggle/<?= (int) $user['id'] ?>" data-admin-confirm="<?= $active ? 'Khóa tài khoản này? Khách hàng sẽ không thể đăng nhập.' : 'Mở khóa tài khoản này?' ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
                <button class="admin-icon-btn <?= $active ? 'admin-icon-warning' : '' ?>" type="submit" title="<?= $active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>">
                  <i class="fa-solid <?= $active ? 'fa-lock' : 'fa-lock-open' ?>"></i>
                </button>
              </form>

              <!-- Nút Xóa vĩnh viễn -->
              <form method="POST" action="<?= $adminBaseUrl ?>/admin-user/delete/<?= (int) $user['id'] ?>" data-admin-confirm="Xóa vĩnh viễn tài khoản @<?= $adminEscape($user['username']) ?>? Thao tác này không thể hoàn tác!" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $adminEscape($csrfToken ?? '') ?>">
                <button class="admin-icon-btn admin-icon-danger" type="submit" title="Xóa tài khoản">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?><nav class="admin-pagination">
    <a class="admin-page-link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $paginationUrl($page - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a>
    <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?><a class="admin-page-link <?= $number === $page ? 'is-active' : '' ?>" href="<?= $paginationUrl($number) ?>"><?= $number ?></a><?php endfor; ?>
    <a class="admin-page-link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : $paginationUrl($page + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a>
  </nav><?php endif; ?>
</section>

<?php require APP_PATH . '/views/admin/layouts/footer.php'; ?>
