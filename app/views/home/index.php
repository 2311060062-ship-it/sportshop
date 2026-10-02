<?php
/**
 * View: Trang chủ
 * Biến nhận vào: $newProducts, $hotProducts, $saleProducts, $brands
 */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$placeholder = BASE_URL . '/public/images/product-placeholder.svg';

$renderProductSection = static function (
    string $id,
    string $title,
    string $subtitle,
    array $products,
    string $emptyMessage
) use ($escape, $placeholder): void {
?>
<section class="section product-showcase" aria-labelledby="<?= $escape($id) ?>Title">
  <div class="section-container">
    <div class="section-heading reveal-left">
      <div>
        <span class="section-kicker reveal"><?= $escape($subtitle) ?></span>
        <h2 id="<?= $escape($id) ?>Title"><?= $escape($title) ?></h2>
      </div>
      <a class="section-link" href="<?= BASE_URL ?>/product/index">Xem tất cả <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (empty($products)): ?>
      <div class="empty-state compact"><i class="fas fa-box-open"></i><p><?= $escape($emptyMessage) ?></p></div>
    <?php else: ?>
      <div class="products-scroll-wrap reveal">
        <button class="scroll-btn left" type="button" onclick="scrollRow('<?= $escape($id) ?>Scroll',-260)" aria-label="Cuộn trái">
          <i class="fas fa-chevron-left"></i>
        </button>
        <div class="products-scroll" id="<?= $escape($id) ?>Scroll">
          <?php foreach ($products as $product):
              $discount = (int) ($product['discount'] ?? 0);
              $finalPrice = ProductModel::calcFinalPrice((int) $product['price'], $discount);
              $image = (string) ($product['thumbnail'] ?? '');
          ?>
            <article class="product-card">
              <a class="product-img-wrap" href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>">
                <img src="<?= $image ? BASE_URL . '/public/images/products/' . $escape($image) . '?v=' . (is_file(PUBLIC_PATH . '/images/products/' . $image) ? filemtime(PUBLIC_PATH . '/images/products/' . $image) : APP_VERSION) : $placeholder ?>"
                     onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
                     alt="<?= $escape($product['name']) ?>" loading="lazy" decoding="async">
                <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
              </a>
              <div class="product-info">
                <p class="product-meta"><?= $escape($product['name_brand'] ?? 'TrendStyle') ?></p>
                <h3 class="product-name"><a href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>"><?= $escape($product['name']) ?></a></h3>
                <div class="product-pricing">
                  <span class="price-val"><?= ProductModel::formatPrice($finalPrice) ?></span>
                  <?php if ($discount > 0): ?><del><?= ProductModel::formatPrice((int) $product['price']) ?></del><?php endif; ?>
                </div>
                <a href="<?= BASE_URL ?>/product/detail/<?= (int) $product['id'] ?>" class="btn-detail">Xem chi tiết</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <button class="scroll-btn right" type="button" onclick="scrollRow('<?= $escape($id) ?>Scroll',260)" aria-label="Cuộn phải">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php
};
?>

<section class="home-hero" id="heroSection" aria-label="Banner">
<?php
$heroFashionItems = [
  ['src'=>BASE_URL.'/public/images/products/fashion_blazer_unisex.jpg', 'brand'=>'Zara',     'tag'=>'Xu Hướng 2026', 'name'=>'Áo Blazer Unisex Form Rộng',    'desc'=>'Phong cách Minimalism thanh lịch – Đứng form sang trọng', 'price'=>'1.000.000đ','old'=>'1.250.000đ','save'=>'20%','color'=>'#8b5cf6'],
  ['src'=>BASE_URL.'/public/images/products/fashion_dress_satin.jpg',   'brand'=>'Mango',    'tag'=>'Best Seller',   'name'=>'Đầm Dạ Hội Lụa Satin Cao Cấp',   'desc'=>'Chất liệu lụa Satin thượng hạng – Tôn vinh vẻ đẹp kiêu sa', 'price'=>'1.189.000đ','old'=>'1.450.000đ','save'=>'18%','color'=>'#e11d48'],
  ['src'=>BASE_URL.'/public/images/products/fashion_polo_pima.jpg',     'brand'=>'Coolmate', 'tag'=>'Bán Chạy #1',   'name'=>'Áo Polo Nam Pima Cotton',        'desc'=>'Sợi bông Pima cao cấp – Thoáng mát và chống nhăn tối đa',   'price'=>'382.500đ',  'old'=>'450.000đ',  'save'=>'15%','color'=>'#0284c7'],
  ['src'=>BASE_URL.'/public/images/products/fashion_bomber_jacket.jpg', 'brand'=>'Uniqlo',   'tag'=>'Hot Deal',      'name'=>'Áo Khoác Bomber Gió 2 Lớp',      'desc'=>'Chống trượt nước cản gió – Phong cách street style cá tính', 'price'=>'637.500đ',  'old'=>'850.000đ',  'save'=>'25%','color'=>'#10b981'],
];
$h0 = $heroFashionItems[0];
?>
  <!-- Nền động cinematic -->
  <div class="hv2-bg" aria-hidden="true">
    <div class="hv2-grid"></div>
    <div class="hv2-blob hv2-blob1"></div>
    <div class="hv2-blob hv2-blob2"></div>
    <div class="hv2-blob hv2-blob3"></div>
    <div class="hv2-shine"></div>
    <div class="hv2-scanline"></div>
    <div class="hv2-particle hv2-p1"></div>
    <div class="hv2-particle hv2-p2"></div>
    <div class="hv2-particle hv2-p3"></div>
    <div class="hv2-particle hv2-p4"></div>
    <div class="hv2-particle hv2-p5"></div>
    <div class="hv2-particle hv2-p6"></div>
  </div>

  <div class="hv2-inner section-container">

    <!-- CỘT TRÁI -->
    <div class="hv2-copy">
      <div class="hv2-reveal" style="--d:.05s"><div class="hv2-eyebrow" id="hv2Tag"><?= htmlspecialchars($h0['tag']) ?></div></div>
      <div class="hv2-reveal" style="--d:.12s">
        <div class="hv2-brand-row">
          <span class="hv2-brand-dot" id="hv2Dot" style="background:<?= $h0['color'] ?>"></span>
          <span id="hv2Brand"><?= htmlspecialchars($h0['brand']) ?></span>
        </div>
      </div>
      <div class="hv2-reveal" style="--d:.2s"><h1 class="hv2-title" id="hv2Title"><?= htmlspecialchars($h0['name']) ?></h1></div>
      <div class="hv2-reveal" style="--d:.28s"><p class="hv2-desc" id="hv2Desc"><?= htmlspecialchars($h0['desc']) ?></p></div>
      <div class="hv2-reveal" style="--d:.36s">
        <div class="hv2-pricing">
          <span class="hv2-price" id="hv2Price"><?= $h0['price'] ?></span>
          <span class="hv2-old"   id="hv2Old"><?= $h0['old'] ?></span>
          <span class="hv2-save"  id="hv2Save">Tiết kiệm <?= $h0['save'] ?></span>
        </div>
      </div>
      <div class="hv2-reveal" style="--d:.44s">
        <div class="hv2-actions">
          <a class="hv2-btn-main" href="<?= BASE_URL ?>/product/index">Khám phá ngay <i class="fas fa-arrow-right"></i></a>
          <a class="hv2-btn-ghost" href="#newProducts">Bộ sưu tập mới</a>
        </div>
      </div>
      <div class="hv2-reveal" style="--d:.52s">
        <div class="hv2-trust">
          <span><i class="fas fa-shield-check"></i>100% Chính hãng</span>
          <span><i class="fas fa-rotate-left"></i>Đổi trả 30 ngày</span>
          <span><i class="fas fa-truck-fast"></i>Freeship từ 499k</span>
        </div>
      </div>
    </div>

    <!-- CỘT GIỮA: Ảnh lớn -->
    <div class="hv2-stage hv2-reveal" id="hv2Stage" style="--d:.18s">
      <div class="hv2-glow" id="hv2Glow" style="background:radial-gradient(circle,<?= $h0['color'] ?>55 0%,transparent 70%)"></div>
      <div class="hv2-orbit" aria-hidden="true"></div>
      <div class="hv2-orbit hv2-orbit2" aria-hidden="true"></div>
      <div class="hv2-floor"></div>
      <div class="hv2-spark hv2-spark1"></div>
      <div class="hv2-spark hv2-spark2"></div>
      <div class="hv2-spark hv2-spark3"></div>
      <div class="hv2-spark hv2-spark4"></div>
      <div class="hv2-shoe-layer" id="hv2ShoeLayer">
        <?php foreach ($heroFashionItems as $i => $item): ?>
        <img
          src="<?= $item['src'] ?>"
          onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
          alt="<?= htmlspecialchars($item['name']) ?>"
          class="hv2-shoe<?= $i===0?' active':'' ?>"
          id="hv2Shoe<?= $i ?>"
          loading="<?= $i===0?'eager':'lazy' ?>"
          decoding="async"
          <?= $i===0?'fetchpriority="high"':'' ?>
        />
        <?php endforeach; ?>
      </div>
      <div class="hv2-badge-sale">
        <span>HOT</span>
        <strong id="hv2Pct"><?= $h0['save'] ?></strong>
        <small>OFF</small>
      </div>

      <!-- ══ LINH VẬT ĐẠI SỨ THỜI TRANG (HERO STYLIST MASCOT) ══ -->
      <div class="hv2-mascot-companion" id="hv2HeroMascot" title="Trợ lý AI Stylist TrendStyle">
        <div class="hv2-mascot-speech" id="hv2MascotSpeech">
          <i class="fa-solid fa-wand-magic-sparkles"></i> <span id="hv2MascotQuote">Áo Blazer form Hàn Quốc siêu tôn dáng!</span>
        </div>
        <div class="hv2-mascot-avatar">
          <svg class="hv2-mascot-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g class="hv2-mascot-head">
              <!-- Tai Pet -->
              <path d="M26 32C22 18 32 10 40 20L36 34" fill="#111111" stroke="#222222" stroke-width="2"/>
              <path d="M74 32C78 18 68 10 60 20L64 34" fill="#111111" stroke="#222222" stroke-width="2"/>
              <path d="M29 30C26 21 32 17 37 23" fill="#f87171"/>
              <path d="M71 30C74 21 68 17 63 23" fill="#f87171"/>
              <!-- Khuôn mặt thời trang -->
              <circle cx="50" cy="52" r="28" fill="#111111"/>
              <path d="M34 60C34 50 66 50 66 60C66 70 34 70 34 60Z" fill="#ffffff"/>
              <!-- Mũ Beret phong cách Pháp -->
              <path d="M24 38C32 28 68 28 76 38L74 44C66 38 34 38 26 44Z" fill="#000000" stroke="#ffffff" stroke-width="1.5"/>
              <circle cx="50" cy="30" r="3.5" fill="#f87171"/>
              <!-- Mắt -->
              <circle cx="40" cy="52" r="4.5" fill="#000000"/>
              <circle cx="41.5" cy="50.5" r="1.5" fill="#ffffff"/>
              <circle cx="60" cy="52" r="4.5" fill="#000000"/>
              <circle cx="61.5" cy="50.5" r="1.5" fill="#ffffff"/>
              <!-- Mũi & Miệng cười -->
              <polygon points="50,57 47,54 53,54" fill="#000000"/>
              <path d="M47 58Q50 61 53 58" stroke="#000000" stroke-width="1.5" stroke-linecap="round" fill="none"/>
              <ellipse cx="35" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
              <ellipse cx="65" cy="57" rx="3" ry="1.8" fill="#fca5a5"/>
              <!-- Bàn tay bám mép khung ảnh -->
              <ellipse cx="32" cy="74" rx="7" ry="5.5" fill="#111111" stroke="#ffffff" stroke-width="1.5"/>
              <ellipse cx="68" cy="74" rx="7" ry="5.5" fill="#111111" stroke="#ffffff" stroke-width="1.5"/>
            </g>
          </svg>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI: Thumbnails -->
    <div class="hv2-thumbs" role="tablist">
      <?php foreach ($heroFashionItems as $i => $item): ?>
      <button
        class="hv2-thumb hv2-reveal<?= $i===0?' active':'' ?>"
        id="hv2Thumb<?= $i ?>"
        onclick="hv2Go(<?= $i ?>)"
        role="tab" aria-selected="<?= $i===0?'true':'false' ?>"
        style="--c:<?= $item['color'] ?>;--d:<?= number_format(0.22 + $i * 0.08, 2, '.', '') ?>s"
      >
        <div class="hv2-th-img">
          <img src="<?= $item['src'] ?>" onerror="this.onerror=null;this.src='<?= $placeholder ?>'" alt="" loading="lazy" decoding="async"/>
        </div>
        <div class="hv2-th-text">
          <b><?= htmlspecialchars($item['brand']) ?></b>
          <span><?= htmlspecialchars($item['name']) ?></span>
          <em><?= $item['price'] ?></em>
        </div>
        <div class="hv2-th-bar"></div>
        <div class="hv2-th-progress"></div>
      </button>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<style>
.home-hero{position:relative;overflow:hidden;background:linear-gradient(140deg,#030a1a 0%,#071030 45%,#0d1d45 100%);padding:56px 0 90px;min-height:560px;--hero-accent:<?= $h0['color'] ?>;}
.hv2-bg{position:absolute;inset:0;pointer-events:none;}
.hv2-grid{position:absolute;inset:-20%;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:56px 56px;opacity:.35;transform:perspective(900px) rotateX(62deg) translateY(-18%) scale(1.4);animation:hv2grid 18s linear infinite;}
@keyframes hv2grid{from{background-position:0 0,0 0}to{background-position:0 56px,56px 0}}
.hv2-blob{position:absolute;border-radius:50%;opacity:.6;will-change:transform;animation:hv2blob 11s ease-in-out infinite;}
.hv2-blob1{width:560px;height:560px;top:-190px;left:-90px;background:radial-gradient(circle,#1e40af 0%,#1677ff44 42%,transparent 72%);}
.hv2-blob2{width:500px;height:500px;bottom:-160px;right:14%;background:radial-gradient(circle,#6d28d9 0%,#a855f733 42%,transparent 72%);animation-delay:-5s;}
.hv2-blob3{width:280px;height:280px;top:18%;right:34%;background:radial-gradient(circle,var(--hero-accent,#f97316) 0%,transparent 70%);opacity:.28;animation:hv2blob 8s ease-in-out infinite reverse;}
.hv2-shine{position:absolute;inset:0;background:linear-gradient(115deg,transparent 30%,rgba(255,255,255,.06) 48%,transparent 62%);transform:translateX(-40%);animation:hv2shine 7.5s ease-in-out infinite;}
@keyframes hv2blob{0%,100%{transform:translate3d(0,0,0) scale(1) rotate(0deg);}33%{transform:translate3d(30px,-22px,0) scale(1.15) rotate(3deg);}66%{transform:translate3d(-18px,12px,0) scale(.92) rotate(-2deg);}}
@keyframes hv2shine{0%,100%{transform:translateX(-45%);}50%{transform:translateX(35%);}}
/* Scanning light beam */
.hv2-scanline{position:absolute;inset:0;background:linear-gradient(180deg,transparent 0%,rgba(22,119,255,.08) 48%,transparent 100%);height:120px;width:100%;animation:hv2scan 6s ease-in-out infinite;}
@keyframes hv2scan{0%{top:-120px;opacity:.5;}50%{top:100%;opacity:.8;}100%{top:-120px;opacity:.5;}}
/* Floating particles – larger & brighter */
.hv2-particle{position:absolute;border-radius:50%;pointer-events:none;will-change:transform;}
.hv2-p1{width:6px;height:6px;background:#3b82f6;top:22%;left:12%;animation:hv2drift 9s ease-in-out infinite;box-shadow:0 0 18px 4px #3b82f6;}
.hv2-p2{width:5px;height:5px;background:#a855f7;top:68%;left:24%;animation:hv2drift 7s ease-in-out infinite 1.5s;box-shadow:0 0 16px 3px #a855f7;}
.hv2-p3{width:7px;height:7px;background:#f97316;top:36%;right:18%;animation:hv2drift 11s ease-in-out infinite 3s;box-shadow:0 0 22px 5px #f97316;}
.hv2-p4{width:5px;height:5px;background:#10b981;bottom:28%;right:32%;animation:hv2drift 8s ease-in-out infinite 2s;box-shadow:0 0 16px 3px #10b981;}
.hv2-p5{width:6px;height:6px;background:#1677ff;top:48%;left:42%;animation:hv2drift 10s ease-in-out infinite 4s;box-shadow:0 0 18px 4px #1677ff;}
.hv2-p6{width:5px;height:5px;background:#f472b6;bottom:18%;left:56%;animation:hv2drift 8.5s ease-in-out infinite 1s;box-shadow:0 0 16px 3px #f472b6;}
@keyframes hv2drift{0%{transform:translate3d(0,0,0) scale(1);opacity:.7;}25%{transform:translate3d(30px,-38px,0) scale(1.8);opacity:1;}50%{transform:translate3d(-22px,-60px,0) scale(.6);opacity:.3;}75%{transform:translate3d(26px,-24px,0) scale(1.5);opacity:1;}100%{transform:translate3d(0,0,0) scale(1);opacity:.7;}}
.hv2-inner{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1.05fr 256px;align-items:center;gap:28px;min-height:440px;}
.hv2-copy{display:flex;flex-direction:column;}
.hv2-reveal{opacity:0;transform:translate3d(0,28px,0);transition:opacity .7s cubic-bezier(.22,1,.36,1),transform .7s cubic-bezier(.22,1,.36,1);transition-delay:var(--d,0s);}
.home-hero.is-ready .hv2-reveal{opacity:1;transform:translate3d(0,0,0);}
.hv2-eyebrow{display:inline-flex;align-items:center;padding:5px 14px;border-radius:50px;margin-bottom:14px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.16);font-size:11px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase;color:#93c5fd;animation:hv2eyePulse 3s ease-in-out infinite;}
@keyframes hv2eyePulse{0%,100%{background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.16);box-shadow:none;}50%{background:rgba(59,130,246,.15);border-color:rgba(59,130,246,.4);box-shadow:0 0 18px rgba(59,130,246,.25);}}
.hv2-brand-row{display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:13px;font-weight:700;color:rgba(255,255,255,.55);}
.hv2-brand-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 4px color-mix(in srgb,var(--hero-accent,#f97316) 28%,transparent);transition:background .5s ease,box-shadow .5s ease;}
.hv2-title{font-size:clamp(24px,3vw,40px);font-weight:900;line-height:1.15;letter-spacing:-1px;color:#fff;margin-bottom:10px;background:linear-gradient(90deg,#fff 0%,#fff 40%,#93c5fd 50%,#fff 60%,#fff 100%);background-size:200% 100%;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;animation:hv2titleShimmer 4s ease-in-out infinite 1.5s;}
@keyframes hv2titleShimmer{0%,100%{background-position:200% center;}50%{background-position:-200% center;}}
.hv2-desc{font-size:14px;color:rgba(255,255,255,.58);line-height:1.75;margin-bottom:22px;max-width:330px;}
.hv2-pricing{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:28px;}
.hv2-price{font-size:28px;font-weight:900;color:#fff;}
.hv2-old{font-size:15px;color:rgba(255,255,255,.38);text-decoration:line-through;}
.hv2-save{font-size:11px;font-weight:800;padding:3px 10px;border-radius:50px;background:rgba(249,115,22,.18);color:#fdba74;border:1px solid rgba(249,115,22,.3);}
.hv2-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px;}
.hv2-btn-main{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:50px;background:linear-gradient(120deg,#1677ff,#0963d8);color:#fff;font-weight:800;font-size:14px;box-shadow:0 8px 28px rgba(22,119,255,.38);transition:transform .35s cubic-bezier(.34,1.4,.64,1),box-shadow .35s ease;animation:hv2btnGlow 2.5s ease-in-out infinite;}
@keyframes hv2btnGlow{0%,100%{box-shadow:0 8px 28px rgba(22,119,255,.38);}50%{box-shadow:0 8px 42px rgba(22,119,255,.7),0 0 20px rgba(22,119,255,.3);}}
.hv2-btn-main::after{content:"";position:absolute;inset:0;background:linear-gradient(110deg,transparent 20%,rgba(255,255,255,.35) 45%,transparent 70%);transform:translateX(-120%);transition:transform .55s ease;}
.hv2-btn-main:hover{transform:translateY(-4px) scale(1.04);box-shadow:0 18px 40px rgba(22,119,255,.55);}
.hv2-btn-main:hover::after{transform:translateX(120%);}
.hv2-btn-main i{transition:transform .35s cubic-bezier(.34,1.4,.64,1);}
.hv2-btn-main:hover i{transform:translateX(6px);}
.hv2-btn-ghost{display:inline-flex;align-items:center;padding:13px 22px;border-radius:50px;border:1.5px solid rgba(255,255,255,.18);color:rgba(255,255,255,.75);font-weight:700;font-size:14px;transition:transform .3s ease,background-color .3s ease,border-color .3s ease,color .3s ease,box-shadow .3s ease;}
.hv2-btn-ghost:hover{background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.4);color:#fff;transform:translateY(-3px);box-shadow:0 10px 24px rgba(0,0,0,.22);}
.hv2-trust{display:flex;gap:16px;flex-wrap:wrap;}
.hv2-trust span{display:flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.42);font-weight:600;}
.hv2-trust i{font-size:12px;color:rgba(255,255,255,.3);}
.hv2-stage{position:relative;display:flex;align-items:center;justify-content:center;height:450px;perspective:1000px;}
/* Animated neon border ring around shoe stage */
.hv2-stage::before{content:"";position:absolute;width:380px;height:380px;border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);border:2px solid transparent;background:conic-gradient(from 0deg,var(--hero-accent,#1677ff),#a855f7,#3b82f6,#10b981,var(--hero-accent,#1677ff)) border-box;-webkit-mask:linear-gradient(#fff 0 0) padding-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;animation:hv2ringRotate 4s linear infinite;opacity:.6;pointer-events:none;z-index:1;}
@keyframes hv2ringRotate{to{transform:translate(-50%,-50%) rotate(360deg);}}
.hv2-stage::after{content:"";position:absolute;width:380px;height:380px;border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);box-shadow:0 0 40px 8px rgba(22,119,255,.15),inset 0 0 40px 8px rgba(22,119,255,.08);animation:hv2ringPulse 3s ease-in-out infinite;pointer-events:none;z-index:1;}
@keyframes hv2ringPulse{0%,100%{box-shadow:0 0 40px 8px rgba(22,119,255,.15),inset 0 0 40px 8px rgba(22,119,255,.08);}50%{box-shadow:0 0 60px 15px rgba(22,119,255,.3),inset 0 0 60px 15px rgba(22,119,255,.15);}}
.hv2-glow{position:absolute;width:420px;height:420px;border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;transition:background 1s ease;animation:glowPulse 4s ease-in-out infinite;}
@keyframes glowPulse{0%,100%{opacity:.55;transform:translate(-50%,-50%) scale(1);}50%{opacity:1;transform:translate(-50%,-50%) scale(1.22);}}
.hv2-orbit{position:absolute;width:320px;height:320px;border:1.5px solid rgba(255,255,255,.2);border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);animation:hv2orbit 10s linear infinite;}
.hv2-orbit::before,.hv2-orbit::after{content:"";position:absolute;border-radius:50%;background:var(--hero-accent,#1677ff);box-shadow:0 0 22px 4px var(--hero-accent,#1677ff);}
.hv2-orbit::before{width:10px;height:10px;top:-5px;left:50%;margin-left:-5px;}
.hv2-orbit::after{width:8px;height:8px;bottom:18%;right:8%;}
@keyframes hv2orbit{to{transform:translate(-50%,-50%) rotate(360deg)}}
/* Second orbit ring – counter-rotate for depth */
.hv2-orbit2{width:440px;height:440px;border:1px dashed rgba(255,255,255,.12);animation:hv2orbit 18s linear infinite reverse;}
.hv2-orbit2::before{width:7px;height:7px;top:10%;right:-4px;background:rgba(255,255,255,.6);box-shadow:0 0 16px 3px rgba(255,255,255,.4);}
.hv2-orbit2::after{width:6px;height:6px;bottom:5%;left:12%;background:rgba(168,85,247,.8);box-shadow:0 0 16px 3px rgba(168,85,247,.5);}
/* Sparks around the shoe */
.hv2-spark{position:absolute;width:6px;height:6px;border-radius:50%;pointer-events:none;z-index:5;}
.hv2-spark1{top:16%;left:18%;background:#fff;box-shadow:0 0 10px #fff,0 0 30px var(--hero-accent,#1677ff);animation:hv2sparkle 3s ease-in-out infinite;}
.hv2-spark2{bottom:28%;right:14%;background:#fff;box-shadow:0 0 10px #fff,0 0 30px var(--hero-accent,#1677ff);animation:hv2sparkle 3.5s ease-in-out infinite .8s;}
.hv2-spark3{top:42%;right:8%;background:#fff;box-shadow:0 0 8px #fff,0 0 22px var(--hero-accent,#1677ff);animation:hv2sparkle 2.8s ease-in-out infinite 1.5s;width:4px;height:4px;}
.hv2-spark4{bottom:38%;left:10%;background:#fff;box-shadow:0 0 8px #fff,0 0 22px var(--hero-accent,#1677ff);animation:hv2sparkle 4s ease-in-out infinite 2.2s;width:4px;height:4px;}
@keyframes hv2sparkle{0%,100%{opacity:0;transform:scale(0) translate3d(0,0,0);}20%{opacity:1;transform:scale(1.2) translate3d(4px,-6px,0);}50%{opacity:.6;transform:scale(.7) translate3d(-3px,4px,0);}80%{opacity:1;transform:scale(1) translate3d(2px,-2px,0);}}
.hv2-floor{position:absolute;bottom:24px;left:50%;transform:translateX(-50%);width:240px;height:36px;background:radial-gradient(ellipse,rgba(0,0,0,.6) 0%,transparent 72%);border-radius:50%;pointer-events:none;transition:transform .35s ease,opacity .35s ease;}
.hv2-shoe-layer{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;transform-style:preserve-3d;transition:transform .18s ease-out;}
.hv2-shoe{position:absolute;width:90%;height:92%;max-width:340px;max-height:340px;object-fit:cover;opacity:0;pointer-events:none;transform:translate3d(60px,14px,0) scale(.88);transition:opacity .6s cubic-bezier(.22,1,.36,1),transform .7s cubic-bezier(.22,1,.36,1);filter:drop-shadow(0 25px 50px rgba(0,0,0,.5));border-radius:20px;overflow:hidden;background:#fff;box-shadow:0 12px 36px rgba(0,0,0,.35);}
.hv2-shoe.active{opacity:1;pointer-events:auto;transform:translate3d(0,0,0) scale(1);animation:shoeHover 4.5s ease-in-out infinite .6s;}
.hv2-shoe.exit-left{opacity:0;transform:translate3d(-70px,-10px,0) scale(.88);animation:none;}
.hv2-shoe.exit-right{opacity:0;transform:translate3d(70px,-10px,0) scale(.88);animation:none;}
.hv2-shoe.enter-from-left{transform:translate3d(-70px,14px,0) scale(.88);}
.hv2-shoe.enter-from-right{transform:translate3d(70px,14px,0) scale(.88);}
@keyframes shoeHover{0%,100%{transform:translate3d(0,0,0) rotate(0deg) scale(1);}25%{transform:translate3d(0,-20px,0) rotate(2deg) scale(1.02);}50%{transform:translate3d(0,-6px,0) rotate(-1deg) scale(.99);}75%{transform:translate3d(0,-16px,0) rotate(1deg) scale(1.01);}}
.hv2-badge-sale{position:absolute;top:10px;right:-4px;background:linear-gradient(135deg,#ff5d31,#ff9100);color:#fff;border-radius:18px;padding:10px 16px;text-align:center;box-shadow:0 10px 30px rgba(255,93,49,.5);animation:badgeFloat 3.5s ease-in-out infinite;z-index:6;transition:transform .4s cubic-bezier(.34,1.4,.64,1);}
.hv2-badge-sale.is-pop{transform:scale(1.22) rotate(6deg);}
.hv2-badge-sale span{display:block;font-size:9px;font-weight:800;letter-spacing:1.8px;opacity:.8;}
.hv2-badge-sale strong{display:block;font-size:22px;font-weight:900;line-height:1;}
.hv2-badge-sale small{font-size:9px;font-weight:700;opacity:.75;}
@keyframes badgeFloat{0%,100%{transform:translateY(0) rotate(-1deg);}50%{transform:translateY(-12px) rotate(2deg);}}
.hv2-thumbs{display:flex;flex-direction:column;gap:10px;}
.hv2-thumb{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:16px;cursor:pointer;text-align:left;background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.07);transition:transform .4s cubic-bezier(.34,1.4,.64,1),background-color .35s ease,border-color .35s ease,box-shadow .35s ease;position:relative;overflow:hidden;width:100%;}
.hv2-thumb::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 20% 50%,color-mix(in srgb,var(--c,#1677ff) 28%,transparent),transparent 65%);opacity:0;transition:opacity .35s ease;}
.hv2-thumb:hover,.hv2-thumb.active{background:rgba(255,255,255,.1);border-color:var(--c,#1677ff);transform:translateX(8px) scale(1.02);}
.hv2-thumb:hover::before,.hv2-thumb.active::before{opacity:1;}
.hv2-thumb.active{box-shadow:0 8px 28px rgba(0,0,0,.28),0 0 0 1px color-mix(in srgb,var(--c,#1677ff) 45%,transparent);}
.hv2-th-img{flex-shrink:0;width:54px;height:46px;border-radius:10px;overflow:hidden;background:#fff;display:flex;align-items:center;justify-content:center;transition:transform .4s cubic-bezier(.34,1.4,.64,1);}
.hv2-thumb:hover .hv2-th-img,.hv2-thumb.active .hv2-th-img{transform:scale(1.12) rotate(-3deg);}
.hv2-th-img img{width:100%;height:100%;object-fit:contain;padding:2px;}
.hv2-th-text{flex:1;min-width:0;}
.hv2-th-text b{display:block;font-size:9px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase;color:var(--c,#93c5fd);margin-bottom:2px;}
.hv2-th-text span{display:block;font-size:12px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px;}
.hv2-th-text em{display:block;font-size:11px;font-weight:800;color:rgba(255,255,255,.5);font-style:normal;}
.hv2-th-bar{position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--c,#1677ff);border-radius:3px 0 0 3px;transform:scaleY(0);transform-origin:center;transition:transform .35s cubic-bezier(.34,1.4,.64,1);}
.hv2-thumb.active .hv2-th-bar{transform:scaleY(1);}
.hv2-th-progress{position:absolute;bottom:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,var(--c,#1677ff),transparent);border-radius:0 0 16px 16px;transform:scaleX(0);transform-origin:left center;}
.hv2-thumb.active .hv2-th-progress{animation:thProg 3.8s linear infinite;}
@keyframes thProg{from{transform:scaleX(0)}to{transform:scaleX(1)}}
.hv2-copy-transition{display:inline-block;transition:opacity .28s cubic-bezier(.22,1,.36,1),transform .42s cubic-bezier(.22,1,.36,1);}
.hv2-copy-transition.is-changing{opacity:0;transform:translate3d(0,14px,0) scale(.98);}
.hv2-title.hv2-copy-transition,.hv2-desc.hv2-copy-transition,.hv2-price.hv2-copy-transition{display:block;}
.home-hero.is-paused .hv2-blob,.home-hero.is-paused .hv2-glow,.home-hero.is-paused .hv2-orbit,.home-hero.is-paused .hv2-shine,.home-hero.is-paused .hv2-grid,.home-hero.is-paused .hv2-shoe.active,.home-hero.is-paused .hv2-badge-sale,.home-hero.is-paused .hv2-thumb.active .hv2-th-progress,.home-hero.is-paused .hv2-particle,.home-hero.is-paused .hv2-scanline,.home-hero.is-paused .hv2-spark,.home-hero.is-paused .hv2-orbit2,.home-hero.is-paused .hv2-eyebrow,.home-hero.is-paused .hv2-title,.home-hero.is-paused .hv2-btn-main,.home-hero.is-paused .hv2-stage::before,.home-hero.is-paused .hv2-stage::after{animation-play-state:paused!important;}

/* ══ HERO MASCOT COMPANION STYLES ══ */
.hv2-mascot-companion {
  position: absolute;
  bottom: -6px;
  left: 2px;
  z-index: 12;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transform-origin: bottom center;
  transition: transform .3s cubic-bezier(.34, 1.56, .64, 1);
}
.hv2-mascot-companion:hover {
  transform: translateY(-6px) scale(1.08);
}
.hv2-mascot-avatar {
  width: 72px;
  height: 72px;
  position: relative;
  filter: drop-shadow(0 8px 20px rgba(0,0,0,.45));
}
.hv2-mascot-head {
  transform-origin: center bottom;
  animation: heroMascotNod 3.2s infinite ease-in-out;
}
@keyframes heroMascotNod {
  0%, 100% { transform: rotate(0deg) translateY(0); }
  50% { transform: rotate(-4deg) translateY(-2px); }
}
.hv2-mascot-speech {
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(8px);
  color: #ffffff;
  border: 1.5px solid rgba(255, 255, 255, 0.25);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
  margin-bottom: 4px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  gap: 5px;
  transition: transform .3s cubic-bezier(.34, 1.4, .64, 1), opacity .2s;
  pointer-events: none;
}
.hv2-mascot-speech i { color: #facc15; }
.hv2-mascot-speech.is-pop {
  transform: scale(1.18) translateY(-4px);
}
@media(max-width:1060px){.hv2-inner{grid-template-columns:1fr 1fr;}.hv2-thumbs{flex-direction:row;flex-wrap:wrap;grid-column:1/-1;}.hv2-thumb{width:calc(50% - 5px);}.hv2-stage::before,.hv2-stage::after,.hv2-orbit2{display:none;}}
@media(max-width:680px){.home-hero{padding:36px 0 64px;}.hv2-inner{grid-template-columns:1fr;}.hv2-stage{height:280px;}.hv2-shoe{max-width:240px;max-height:200px;}.hv2-glow,.hv2-orbit{width:220px;height:220px;}.hv2-thumbs{flex-direction:row;overflow-x:auto;flex-wrap:nowrap;scrollbar-width:none;}.hv2-thumb{min-width:150px;flex-shrink:0;}.hv2-thumbs::-webkit-scrollbar{display:none;}.hv2-stage::before,.hv2-stage::after{width:260px;height:260px;} .hv2-mascot-companion{bottom:-10px;left:-6px;} .hv2-mascot-avatar{width:54px;height:54px;} .hv2-mascot-speech{font-size:9.5px;padding:4px 8px;}}
</style>

<script>
(function(){
  var DATA=<?= json_encode(array_map(fn($s)=>['color'=>$s['color'],'tag'=>$s['tag'],'brand'=>$s['brand'],'name'=>$s['name'],'desc'=>$s['desc'],'price'=>$s['price'],'old'=>$s['old'],'save'=>$s['save']],$heroFashionItems),JSON_UNESCAPED_UNICODE) ?>;
  var mascotQuotes = [
    'Áo Blazer form Hàn Quốc siêu tôn dáng!',
    'Lụa Satin sang trọng quý phái!',
    'Pima Cotton thoáng mát cả ngày dài!',
    'Bomber jacket chống nước cản gió cực đỉnh!'
  ];
  var shoes=document.querySelectorAll('.hv2-shoe'),thumbs=document.querySelectorAll('.hv2-thumb'),cur=0,timer,busy=false;
  var hero=document.getElementById('heroSection'),heroVisible=true;
  var stage=document.getElementById('hv2Stage');
  var shoeLayer=document.getElementById('hv2ShoeLayer');
  var badge=document.querySelector('.hv2-badge-sale');
  var mascotQuoteEl = document.getElementById('hv2MascotQuote');
  var mascotSpeechEl = document.getElementById('hv2MascotSpeech');
  var mascotCompanion = document.getElementById('hv2HeroMascot');
  var E={tag:document.getElementById('hv2Tag'),brand:document.getElementById('hv2Brand'),dot:document.getElementById('hv2Dot'),title:document.getElementById('hv2Title'),desc:document.getElementById('hv2Desc'),price:document.getElementById('hv2Price'),old:document.getElementById('hv2Old'),save:document.getElementById('hv2Save'),pct:document.getElementById('hv2Pct'),glow:document.getElementById('hv2Glow')};
  var fadeTimers=new WeakMap();
  Object.keys(E).forEach(function(key){if(E[key]&&key!=='dot'&&key!=='glow')E[key].classList.add('hv2-copy-transition');});

  requestAnimationFrame(function(){if(hero)hero.classList.add('is-ready');});

  function fade(el,val,delay){
    if(!el)return;
    clearTimeout(fadeTimers.get(el));
    var run=function(){
      el.classList.add('is-changing');
      fadeTimers.set(el,setTimeout(function(){
        el.textContent=val;
        requestAnimationFrame(function(){el.classList.remove('is-changing');});
      }, 160));
    };
    if(delay) fadeTimers.set(el,setTimeout(run,delay)); else run();
  }

  function setAccent(color){
    if(hero) hero.style.setProperty('--hero-accent', color);
    if(E.glow) E.glow.style.background='radial-gradient(circle,'+color+'55 0%,transparent 70%)';
    if(E.dot) E.dot.style.background=color;
  }

  window.hv2Go=function(n){
    if(n===cur||busy)return;
    busy=true;
    var p=cur;
    var next=(n+shoes.length)%shoes.length;
    var forward=next>p || (p===shoes.length-1 && next===0);
    if(p===0 && next===shoes.length-1) forward=false;
    cur=next;
    var d=DATA[cur];

    shoes[p].classList.remove('active');
    shoes[p].classList.add(forward?'exit-left':'exit-right');
    shoes[cur].classList.remove('exit-left','exit-right');
    shoes[cur].classList.add(forward?'enter-from-right':'enter-from-left');
    void shoes[cur].offsetWidth;
    shoes[cur].classList.remove('enter-from-right','enter-from-left');
    shoes[cur].classList.add('active');
    setTimeout(function(){shoes[p].classList.remove('exit-left','exit-right');busy=false;},700);

    thumbs[p].classList.remove('active');thumbs[p].setAttribute('aria-selected','false');
    thumbs[cur].classList.add('active');thumbs[cur].setAttribute('aria-selected','true');

    setAccent(d.color);
    fade(E.tag,d.tag,0);
    fade(E.brand,d.brand,40);
    fade(E.title,d.name,80);
    fade(E.desc,d.desc,120);
    fade(E.price,d.price,160);
    fade(E.old,d.old,180);
    fade(E.save,'Tiết kiệm '+d.save,200);
    fade(E.pct,d.save,120);

    if(mascotQuoteEl && mascotSpeechEl){
      mascotSpeechEl.classList.remove('is-pop');
      void mascotSpeechEl.offsetWidth;
      mascotQuoteEl.textContent = mascotQuotes[cur] || '✨ Thời trang xu hướng!';
      mascotSpeechEl.classList.add('is-pop');
      setTimeout(function(){mascotSpeechEl.classList.remove('is-pop');},450);
    }

    if(badge){
      badge.classList.remove('is-pop');
      void badge.offsetWidth;
      badge.classList.add('is-pop');
      setTimeout(function(){badge.classList.remove('is-pop');},450);
    }
    startAuto();
  };

  if(mascotCompanion){
    var cheerIndex = 0;
    var cheerQuotes = [
      '✨ Gu thời trang của bạn cực kỳ cuốn hút!',
      '👗 Cần tư vấn phối đồ cứ hỏi Stylist AI nhé!',
      '💎 100% Sản phẩm chính hãng cao cấp!',
      '🎉 Đặt hàng ngay hôm nay freeship toàn quốc!'
    ];
    mascotCompanion.addEventListener('click', function(){
      cheerIndex = (cheerIndex + 1) % cheerQuotes.length;
      if(mascotQuoteEl && mascotSpeechEl){
        mascotSpeechEl.classList.remove('is-pop');
        void mascotSpeechEl.offsetWidth;
        mascotQuoteEl.textContent = cheerQuotes[cheerIndex];
        mascotSpeechEl.classList.add('is-pop');
        setTimeout(function(){mascotSpeechEl.classList.remove('is-pop');},450);
      }
    });
  }

  function startAuto(){
    clearInterval(timer);
    if(!document.hidden&&heroVisible)timer=setInterval(function(){hv2Go(cur+1);},3800);
  }

  if(stage){
    stage.addEventListener('mouseenter',function(){clearInterval(timer);});
    stage.addEventListener('mouseleave',function(){
      if(shoeLayer) shoeLayer.style.transform='';
      startAuto();
    });
    stage.addEventListener('mousemove',function(e){
      if(!shoeLayer) return;
      var rect=stage.getBoundingClientRect();
      var x=((e.clientX-rect.left)/rect.width-.5)*14;
      var y=((e.clientY-rect.top)/rect.height-.5)*10;
      shoeLayer.style.transform='rotateY('+(-x)+'deg) rotateX('+y+'deg) translateZ(12px)';
    });
    var sx=0;
    stage.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});
    stage.addEventListener('touchend',function(e){var dx=e.changedTouches[0].clientX-sx;if(Math.abs(dx)>45)hv2Go(dx<0?cur+1:cur-1);});
  }

  if(hero&&'IntersectionObserver' in window){
    new IntersectionObserver(function(entries){
      heroVisible=entries[0].isIntersecting;
      hero.classList.toggle('is-paused',!heroVisible||document.hidden);
      startAuto();
    },{rootMargin:'100px'}).observe(hero);
  }
  document.addEventListener('visibilitychange',function(){
    if(hero)hero.classList.toggle('is-paused',document.hidden||!heroVisible);
    startAuto();
  });
  startAuto();
})();
</script>



<section class="benefit-strip" aria-label="Quyền lợi mua sắm">
  <div class="section-container benefit-grid">
    <div class="reveal stagger-1"><i class="fas fa-truck-fast"></i><span><strong>Giao hàng hỏa tốc</strong><small>Miễn phí từ 499.000đ</small></span></div>
    <div class="reveal stagger-2"><i class="fas fa-shield-halved"></i><span><strong>Cam kết chính hãng</strong><small>Chất liệu cao cấp chuẩn xịn</small></span></div>
    <div class="reveal stagger-3"><i class="fas fa-rotate-left"></i><span><strong>Đổi trả dễ dàng</strong><small>Trong vòng 30 ngày</small></span></div>
    <div class="reveal stagger-4"><i class="fas fa-wand-magic-sparkles"></i><span><strong>AI Stylist 24/7</strong><small>Tư vấn phối đồ & chọn size</small></span></div>
  </div>
</section>

<?php $renderProductSection('newProducts', 'Bộ sưu tập mới', 'Vừa cập bến', $newProducts ?? [], 'Sản phẩm mới đang được cập nhật.'); ?>
<?php $renderProductSection('hotProducts', 'Sản phẩm bán chạy', 'Xu hướng thịnh hành', $hotProducts ?? [], 'Chưa có sản phẩm bán chạy.'); ?>
<?php $renderProductSection('saleProducts', 'Ưu đãi nổi bật', 'Giá tốt hôm nay', $saleProducts ?? [], 'Chương trình ưu đãi đang được cập nhật.'); ?>

<section class="section brand-section" aria-labelledby="brandTitle">
  <div class="section-container">
    <div class="section-heading centered reveal">
      <div><span class="section-kicker reveal">Thương hiệu đối tác</span><h2 id="brandTitle">Thương hiệu nổi bật</h2></div>
    </div>
    <div class="brand-grid">
      <?php $brandIdx = 0; foreach (($brands ?? []) as $brand):
          $brandImage = (string) ($brand['image'] ?? '');
          $brandIdx++;
      ?>
        <a class="brand-card reveal-scale stagger-<?= min($brandIdx, 8) ?>" href="<?= BASE_URL ?>/product/index?brand=<?= (int) $brand['id'] ?>">
          <img src="<?= $brandImage ? BASE_URL . '/public/images/brands/' . $escape($brandImage) : $placeholder ?>"
               onerror="this.onerror=null;this.src='<?= $placeholder ?>'"
               alt="<?= $escape($brand['name_brand']) ?>" loading="lazy" decoding="async">
          <strong><?= $escape($brand['name_brand']) ?></strong>
          <span>Khám phá <i class="fas fa-arrow-right"></i></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section story-section" id="about">
  <div class="section-container story-card reveal">
    <div>
      <span class="section-kicker reveal">Về TrendStyle Fashion</span>
      <h2>Định hình phong cách thời trang hiện đại</h2>
      <p>Chúng tôi tuyển chọn những bộ sưu tập thời trang tinh tế, dẫn đầu xu hướng, đồng hành cùng bạn tạo dựng phong cách riêng đầy tự tin và cuốn hút mỗi ngày.</p>
    </div>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/product/index">Khám phá bộ sưu tập</a>
  </div>
</section>

<section class="section contact-section" id="contact">
  <div class="section-container contact-grid">
    <div class="reveal stagger-1"><i class="fas fa-location-dot"></i><span><strong>Ghé cửa hàng</strong><small>123 Đường Thời Trang, Q.1, TP.HCM</small></span></div>
    <div class="reveal stagger-2"><i class="fas fa-phone"></i><span><strong>Hotline hỗ trợ</strong><small>0909 123 456</small></span></div>
    <div class="reveal stagger-3"><i class="fas fa-envelope"></i><span><strong>Gửi email</strong><small>contact@trendstyle.vn</small></span></div>
  </div>
</section>
