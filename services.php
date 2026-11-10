<?php
header('Content-Type: text/html; charset=utf-8');
 require_once 'config/init.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#C39E31">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>خدمات التجميل والليزر والجلدية | Brèagha Clinic</title>
  <meta name="description" content="تصفح جميع خدمات عيادة براجيا: ليزر إزالة الشعر، بوتوكس، فيلر، عناية بالبشرة، نساء وتوليد، وأمراض البطن. احجز موعدك الآن في العبور.">
  <meta name="keywords" content="خدمات تجميل, ليزر إزالة شعر, بوتوكس, فيلر, تقشير كيميائي, ميزوثيرابي, PRP, عناية بالبشرة, العبور, skin care obour, botox obour, filler obour">
  <meta name="author" content="Brèagha Clinic">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">

  <!-- Open Graph -->
  <meta property="og:title" content="خدمات التجميل والليزر والجلدية | Brèagha Clinic">
  <meta property="og:description" content="جميع خدمات عيادة براجيا في مكان واحد — تصفحي الخدمات واحجزي موعدك الآن">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://breaghaclinic.com/services">
  <meta property="og:image" content="https://breaghaclinic.com/uploads/services/hairRemove.webp">
  <meta property="og:site_name" content="Brèagha Clinic">
  <meta property="og:locale" content="ar_AR">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="خدمات التجميل والليزر والجلدية | Brèagha Clinic">
  <meta name="twitter:description" content="جميع خدمات عيادة براجيا — تصفحي واحجزي موعدك الآن">
  <meta name="twitter:image" content="https://breaghaclinic.com/uploads/services/hairRemove.webp">

  <!-- Canonical -->
  <link rel="canonical" href="https://breaghaclinic.com/services">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/services.css">
  <link rel="stylesheet" href="assets/css/footer.css">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <?php require_once 'config/seo.php'; seo_head_meta('services'); ?>
  <?php include 'seo-schema.php'; ?>
</head>

<body>

  <?php
  require_once 'config/connection.php';
  require_once 'config/visit_counter.php';
  record_visit('services');
  $visit_counts = get_visit_counts();

  $depts_result = $conn->query("SELECT slug, name, icon, description FROM departments WHERE visible = 1 ORDER BY sort_order ASC");
  $departments = [];
  while ($d = $depts_result->fetch_assoc()) {
      $departments[] = $d;
  }

  $services_result = $conn->query("SELECT s.*, d.name AS dept_name, d.icon AS dept_icon FROM services s LEFT JOIN departments d ON s.department_slug = d.slug WHERE s.visible = 1 ORDER BY FIELD(s.department_slug, 'derma','cosm','laser','gyn','gastro'), s.id ASC");
  $services_by_dept = [];
  while ($s = $services_result->fetch_assoc()) {
      $services_by_dept[$s['department_slug']][] = $s;
  }
  ?>

  <nav class="navbar">
    <a href="<?= $base_url ?>/" class="logo" aria-label="الانتقال إلى الصفحة الرئيسية">Brèagha</a>
    <button class="nav-toggle" aria-label="فتح القائمة" aria-expanded="false"><span></span></button>
    <ul class="nav-links">
      <li><a href="<?= $base_url ?>/">الرئيسية</a></li>
      <li><a href="<?= $base_url ?>/services">خدماتنا</a></li>
      <li><a href="<?= $base_url ?>/offers">العروض</a></li>
      <li><a href="<?= $base_url ?>/about">من نحن</a></li>
      <li><a href="javascript:void(0)" onclick="smoothScrollToBooking()" class="nav-book-btn">احجز الآن</a></li>
    </ul>
  </nav>

  <header class="hero">
    <video autoplay muted loop playsinline class="bg-video">
      <source src="assets/imgs/service.mp4" type="video/mp4">
      <source src="assets/imgs/v1.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>
    <div class="hero-text" data-aos="fade-up">
      <h1>خدماتنا الطبية والتجميلية</h1>
      <p>رحلة متكاملة من الجمال والعناية تبدأ من هنا</p>
    </div>
  </header>

  <div class="dept-tabs-wrapper">
    <div class="dept-tabs-container">
      <div class="dept-tabs" id="deptTabs">
        <?php foreach ($departments as $i => $dept): ?>
        <button class="dept-tab <?= $i === 0 ? 'active' : '' ?>" data-target="sec-<?= htmlspecialchars($dept['slug'], ENT_QUOTES, 'UTF-8') ?>">
          <span class="tab-icon"><?= htmlspecialchars($dept['icon'] ?? '📋', ENT_QUOTES, 'UTF-8') ?></span>
          <span class="tab-text"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php foreach ($departments as $i => $dept):
    $slug = $dept['slug'];
    $services_list = $services_by_dept[$slug] ?? [];
    if (empty($services_list)) continue;
  ?>
  <section id="sec-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="dept-section <?= $i === 0 ? 'active' : '' ?>">
    <div class="dept-header luxury-header" data-aos="fade-up">
      <div class="header-main">
        <span class="dept-icon"><?= htmlspecialchars($dept['icon'] ?? '📋', ENT_QUOTES, 'UTF-8') ?></span>
        <div class="header-text">
          <h2 class="luxury-title"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
      </div>
    </div>
    <div class="services-section">
      <?php foreach ($services_list as $svc): ?>
      <div class="service-card" data-aos="fade-up">
        <?php if ($svc['image']):
          $alt = htmlspecialchars($svc['name'], ENT_QUOTES, 'UTF-8') . ' - عيادة براجيا للتجميل والليزر';
        ?>
        <img src="<?= htmlspecialchars($svc['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= $alt ?>" loading="lazy">
        <?php endif; ?>
        <div class="service-content">
          <h3><?= htmlspecialchars($svc['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          <?php if ($svc['description']): ?>
          <p><?= htmlspecialchars($svc['description'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <a href="javascript:void(0)" onclick="openBookingModal('<?= htmlspecialchars($svc['name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($svc['department_slug'], ENT_QUOTES, 'UTF-8') ?>')" class="btn" aria-label="احجز موعد لـ <?= htmlspecialchars($svc['name'], ENT_QUOTES, 'UTF-8') ?>">احجز الآن</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <?php include 'footer.php'; ?>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 900, offset: 120, once: true });

    const tabs = document.querySelectorAll('.dept-tab');
    const sections = document.querySelectorAll('.dept-section');
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));
        tab.classList.add('active');
        const target = document.getElementById(tab.dataset.target);
        if (target) {
          target.classList.add('active');
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    function openBookingModal(serviceName, deptSlug) {
      window.location.href = '<?= $base_url ?>/?dept=' + encodeURIComponent(deptSlug || '') + '&type=service&service=' + encodeURIComponent(serviceName || '') + '#booking-section';
    }
  </script>
  <script src="assets/js/home-scripts.js"></script>
  <?php include 'booking-modal-include.php'; ?>

</body>
</html>
