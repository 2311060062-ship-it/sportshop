    </main>

    <footer class="admin-footer">
      <span>© <?= $adminEscape(date('Y')) ?> TrendStyle Fashion</span>
      <a href="<?= $adminBaseUrl ?>/" target="_blank" rel="noopener">Mở cửa hàng</a>
    </footer>
  </div>
</div>

<?php if (!empty($adminChart)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= $adminBaseUrl ?>/public/js/admin-product.js?v=<?= is_file(PUBLIC_PATH . '/js/admin-product.js') ? filemtime(PUBLIC_PATH . '/js/admin-product.js') : APP_VERSION ?>"></script>
</body>
</html>
