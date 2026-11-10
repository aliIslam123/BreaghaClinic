<?php
header('Content-Type: text/html; charset=utf-8');
 require_once 'config/init.php'; ?>
<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#C39E31">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>عروض الليزر والتجميل المخفضة | Brèagha Clinic</title>
  <meta name="description" content="عروض حصرية على جلسات الليزر والتقشير والبوتوكس والفيلر في عيادة براجيا. أسعار مخفضة لفترة محدودة في العبور — احجزي الآن.">
  <meta name="keywords" content="عروض ليزر, عروض تجميل, خصومات بوتوكس, فيلر, تقشير, عيادة براجيا, عروض العبور">
  <meta name="author" content="Brèagha Clinic">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">

  <!-- Open Graph -->
  <meta property="og:title" content="عروض الليزر والتجميل المخفضة | Brèagha Clinic">
  <meta property="og:description" content="عروض حصرية على جلسات الليزر والتقشير والبوتوكس — أسعار مخفضة لفترة محدودة">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://breaghaclinic.com/offers">
  <meta property="og:image" content="https://breaghaclinic.com/uploads/services/hairRemove.webp">
  <meta property="og:site_name" content="Brèagha Clinic">
  <meta property="og:locale" content="ar_AR">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="عروض الليزر والتجميل المخفضة | Brèagha Clinic">
  <meta name="twitter:description" content="عروض حصرية على جلسات الليزر والتقشير والبوتوكس — أسعار مخفضة لفترة محدودة">
  <meta name="twitter:image" content="https://breaghaclinic.com/uploads/services/hairRemove.webp">

  <!-- Canonical -->
  <link rel="canonical" href="https://breaghaclinic.com/offers">

  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/navbar.css" />
  <link rel="stylesheet" href="assets/css/offers.css" />
  <link rel="stylesheet" href="assets/css/footer.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <?php require_once 'config/seo.php'; seo_head_meta('offers'); ?>
  <?php include 'seo-schema.php'; ?>
</head>

<body class="offers-page">
  <?php
  require_once 'config/connection.php';
  // Load offers from DB
  $offers_result = $conn->query("SELECT id, category, name, price, `starting`, original_price, details, gift_label FROM offers WHERE visible = 1 ORDER BY category, id ASC");
  $offers = [];
  while ($o = $offers_result->fetch_assoc()) {
      $offers[] = $o;
  }
  $categories = [];
  $offers_json = [];
  foreach ($offers as $o) {
      if (!in_array($o['category'], $categories)) $categories[] = $o['category'];
      $offers_json[] = [
          'cat' => $o['category'],
          'name' => $o['name'],
          'price' => (float)$o['price'],
          'starting' => (bool)$o['starting'],
          'originalPrice' => $o['original_price'] !== null ? (float)$o['original_price'] : null,
          'details' => $o['details'] ?? '',
          'giftLabel' => $o['gift_label'] ?? '',
      ];
    }
  $featured_offer = $offers[0] ?? null;
  ?>

  <nav class="navbar">
    <a href="<?= $base_url ?>/" class="logo" aria-label="الانتقال إلى الصفحة الرئيسية">Brèagha</a>
    <button class="nav-toggle" aria-label="فتح القائمة" aria-expanded="false"><span></span></button>
    <ul class="nav-links">
      <li><a href="<?= $base_url ?>/">الرئيسية</a></li>
      <li><a href="<?= $base_url ?>/services">خدماتنا</a></li>
      <li><a href="#filters">العروض</a></li>
      <li><a href="<?= $base_url ?>/about">من نحن</a></li>
      <li><a href="javascript:void(0)" onclick="smoothScrollToBooking()" class="nav-book-btn">احجز الآن</a></li>
    </ul>
  </nav>

  <header class="offers-hero">
    <div class="offers-hero-inner">
      <div class="offers-hero-copy">
        <p class="offers-kicker">Brèagha Clinic <span aria-hidden="true"></span> العروض</p>
        <h1>عنايتك، <span>بأسعار واضحة</span></h1>
        <p>باقات للعناية بالبشرة والشعر والليزر والتجميل بالحقن، مع تفاصيل كل جلسة وسعرها قبل الحجز.</p>
        <a class="offers-hero-link" href="#filters">استعرضي العروض <i class="fas fa-arrow-left" aria-hidden="true"></i></a>
      </div>
      <?php if ($featured_offer): ?>
        <article class="offers-featured-card" aria-label="عينة من العروض المتاحة">
          <div class="offers-featured-top">
            <span>عينة من العروض</span>
            <span><?= count($offers) ?> عروض</span>
          </div>
          <span class="offers-featured-rule" aria-hidden="true"></span>
          <span class="offers-featured-category"><?= htmlspecialchars($featured_offer['category'], ENT_QUOTES, 'UTF-8') ?></span>
          <h2><?= htmlspecialchars($featured_offer['name'], ENT_QUOTES, 'UTF-8') ?></h2>
          <?php if (!empty($featured_offer['details'])): ?>
            <p><?= htmlspecialchars($featured_offer['details'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <?php if (!empty($featured_offer['gift_label'])): ?>
            <span class="offers-featured-gift"><i class="fas fa-gift" aria-hidden="true"></i><?= htmlspecialchars($featured_offer['gift_label'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
          <div class="offers-featured-price">
            <span class="offers-featured-current"><?= number_format((float)$featured_offer['price'], 0) ?> <small>ج.م</small></span>
            <?php if ($featured_offer['original_price'] !== null && (float)$featured_offer['original_price'] > 0): ?>
              <span class="offers-featured-old"><?= number_format((float)$featured_offer['original_price'], 0) ?> ج.م</span>
            <?php endif; ?>
          </div>
        </article>
      <?php endif; ?>
    </div>
  </header>

  <main class="offers-container">
    <div class="offers-section-head">
      <div>
        <p class="offers-section-kicker">العناية التي تبحثين عنها</p>
        <h2>اختاري القسم</h2>
      </div>
      <p>تصفحي الباقات المتاحة واختاري ما يناسبك</p>
    </div>
    <div class="offers-filters" id="filters"></div>
    <div id="offers-grid" class="offers-grid" aria-live="polite"></div>
    <div id="empty" class="offers-empty" style="display:none">لا توجد عروض في الفئة المختارة.</div>
  </main>

  <script>
    // === بيانات العروض من قاعدة البيانات ===
    const services = <?= json_encode($offers_json, JSON_UNESCAPED_UNICODE) ?>;

    function formatPrice(p) {
      return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(p);
    }

    const categories = ['الكل', ...new Set(services.map(s => s.cat))];
    const filtersEl = document.getElementById('filters');
    const grid = document.getElementById('offers-grid');
    const empty = document.getElementById('empty');

    categories.forEach(cat => {
      const btn = document.createElement('button');
      btn.className = 'filter-btn' + (cat === 'الكل' ? ' active' : '');
      btn.textContent = cat;
      btn.setAttribute('aria-pressed', cat === 'الكل' ? 'true' : 'false');
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.filter-btn').forEach(b => b.setAttribute('aria-pressed', 'false'));
        btn.classList.add('active');
        btn.setAttribute('aria-pressed', 'true');
        renderGrid(cat === 'الكل' ? null : cat);
      });
      filtersEl.appendChild(btn);
    });

    function renderGrid(filterCat) {
      grid.innerHTML = '';
      const list = filterCat ? services.filter(s => s.cat === filterCat) : services;
      if (list.length === 0) { empty.style.display = 'block'; return; }
      empty.style.display = 'none';

      list.forEach((s, i) => {
        const card = document.createElement('article');
        card.className = 'offer-card';
        card.style.animationDelay = (i * 100) + 'ms';

        const accent = document.createElement('div');
        accent.className = 'offer-card-accent';

        const head = document.createElement('div');
        head.className = 'offer-card-head';
        const offerName = document.createElement('div');
        offerName.className = 'offer-name';
        offerName.textContent = s.name;
        const offerTag = document.createElement('div');
        offerTag.className = 'offer-tag';
        offerTag.textContent = s.cat;
        head.append(offerName, offerTag);

        let description = null;
        if (s.details || s.giftLabel) {
          description = document.createElement('div');
          description.className = 'offer-details';
          if (s.details) {
            const detailText = document.createElement('p');
            detailText.textContent = s.details;
            description.appendChild(detailText);
          }
          if (s.giftLabel) {
            const gift = document.createElement('span');
            gift.className = 'offer-gift';
            const giftIcon = document.createElement('i');
            giftIcon.className = 'fas fa-gift';
            giftIcon.setAttribute('aria-hidden', 'true');
            gift.appendChild(giftIcon);
            const giftText = document.createElement('span');
            giftText.textContent = s.giftLabel;
            gift.appendChild(giftText);
            description.appendChild(gift);
          }
        }

        const priceSec = document.createElement('div');
        priceSec.className = 'offer-price-section';
        const priceHTML = `${s.starting ? 'ابتداءً من ' : ''}${formatPrice(s.price)} ج.م`;
        const oldHTML = s.originalPrice !== null && s.originalPrice > 0
          ? `<span class='offer-old-price'><span class='offer-old-label'>قبل العرض</span><span class='offer-old-value'>${formatPrice(s.originalPrice)} ج.م</span></span>`
          : '';
        const priceLabel = s.originalPrice !== null && s.originalPrice > 0 ? 'سعر العرض' : 'السعر';
        priceSec.innerHTML = `<div class='offer-current-price'><span class='offer-price-label'>${priceLabel}</span><div class='offer-price'>${priceHTML}</div></div>${oldHTML}`;

        const divider = document.createElement('div');
        divider.className = 'offer-divider';

        const actions = document.createElement('div'); actions.className = 'offer-actions';
        const book = document.createElement('button'); book.className = 'offer-btn offer-btn-book'; book.textContent = 'احجزي الآن';
        const details = document.createElement('button'); details.className = 'offer-btn offer-btn-details'; details.textContent = 'التفاصيل';

        book.addEventListener('click', () => {
          const categorySlugMap = {
              'العناية بالبشرة': 'derma',
              'العناية بالشعر': 'derma',
              'ليزر': 'laser',
              'الليزر': 'laser',
              'تقشير': 'derma',
              'التجميل بالحقن': 'cosm',
              'الجلدية وعلاجات الجلدية': 'derma',
              'الجلدية وعلاجات البشرة': 'derma',
              'فيلر': 'cosm',
              'بوتوكس': 'cosm',
              'الإجراءات التجميلية': 'cosm'
          };
          const deptSlug = categorySlugMap[s.cat] || '';
          window.location.href = `<?= $base_url ?>/?dept=${encodeURIComponent(deptSlug)}&type=offer&service=${encodeURIComponent(s.name)}#booking-section`;
        });
        details.addEventListener('click', () => {
          window.open('https://wa.me/01108343753', '_blank');
        });

        actions.appendChild(book);
        actions.appendChild(details);

        card.appendChild(accent);
        card.appendChild(head);
        if (description) card.appendChild(description);
        card.appendChild(priceSec);
        card.appendChild(divider);
        card.appendChild(actions);
        grid.appendChild(card);
      });
    }

    renderGrid(null);
  </script>

  <?php include 'footer.php'; ?>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="assets/js/whatsapp-integration.js"></script>
  <script>AOS.init({ duration: 800, once: true });</script>
  <script src="assets/js/home-scripts.js"></script>
  <?php include 'booking-modal-include.php'; ?>
</body>
</html>
