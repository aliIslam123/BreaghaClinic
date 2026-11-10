<?php
header('Content-Type: text/html; charset=utf-8');
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($host, 'localhost') !== false);
$base_url = $is_local ? '/Breagha project' : '';
?>
<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#C39E31">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>فريق عيادة براجيا الطبي | استشاريي الجلدية والتجميل | Brèagha Clinic</title>
  <meta name="description" content="تعرف على فريق عيادة براجيا الطبي: د. سماح جمال استشاري الجلدية والتجميل والليزر، د. ريهام جادو استشاري نساء وتوليد، د. ربيع إسماعيل استشاري أمراض الباطنة.">
  <meta name="keywords" content="فريق براجيا, دكتور سماح جمال, دكتور ربيع إسماعيل, عيادة براجيا, أطباء تجميل العبور, استشاري جلدية العبور">
  <meta name="author" content="Brèagha Clinic">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">

  <!-- Open Graph -->
  <meta property="og:title" content="فريق عيادة براجيا الطبي | Brèagha Clinic">
  <meta property="og:description" content="تعرف على فريق عيادة براجيا الطبي واستشاريي الجلدية والتجميل والليزر">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://breaghaclinic.com/about">
  <meta property="og:image" content="https://breaghaclinic.com/assets/imgs/about%20us/%D8%B3%D9%85%D8%A7%D8%AD.png">
  <meta property="og:site_name" content="Brèagha Clinic">
  <meta property="og:locale" content="ar_AR">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="فريق عيادة براجيا الطبي | Brèagha Clinic">
  <meta name="twitter:description" content="تعرف على فريق عيادة براجيا الطبي واستشاريي الجلدية والتجميل والليزر">
  <meta name="twitter:image" content="https://breaghaclinic.com/assets/imgs/about%20us/%D8%B3%D9%85%D8%A7%D8%AD.png">

  <!-- Canonical -->
  <link rel="canonical" href="https://breaghaclinic.com/about">

  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/navbar.css" />
  <link rel="stylesheet" href="assets/css/home-redesign.css" />
  <link rel="stylesheet" href="assets/css/aboutus.css" />
  <link rel="stylesheet" href="assets/css/footer.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <?php require_once 'config/seo.php'; seo_head_meta('about'); ?>
  <?php include 'seo-schema.php'; ?>
</head>

<body class="page-aboutus">
  <!-- Navbar (shared) -->
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

  <div class="container">
    <section class="intro">
      <div class="clinic-info">
        <h1>من نحن — عيادة براجيا للتجميل والليزر</h1>
        <p>عيادة براجيا تهدف لتقديم رعاية طبية وتجميلية متكاملة، مع التركيز على الأمان والنتائج الملموسة. نستخدم أحدث
          الأجهزة ونراعي بروتوكولات علاجية عالمية، وفريقنا الطبي جاهز لمتابعتك خطوة بخطوة.</p>
      </div>

      <div class="video-wrap"
        style="flex:0 0 260px;height:360px;border-radius:16px;overflow:hidden;background:#000;box-shadow:0 12px 40px rgba(0,0,0,0.15)">
        <video autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover">
          <source src="assets/imgs/reel.mp4" type="video/mp4">
        </video>
      </div>
    </section>

    <section class="team">
      <h2 style="color:var(--gold)">فريق الأطباء</h2>

      <div class="divider"></div>
      <h3 style="color:var(--gold);margin-top:20px">قسم الجلدية</h3>
      <div class="spotlight-wrap">

        <!-- Spotlight 1 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/سماح.webp') center/cover no-repeat"></div>
          <div class="spot-info"><span class="icon">💎</span>
            <h3>د/ سماح جمال عبد الناصر</h3>
            <p class="tl-role">استشاري الجلدية والتجميل والليزر</p>
            <p class="tl-desc">بكالوريوس طب – جامعة عين شمس. ماجستير الجلدية والتجميل – جامعة الأزهر. دبلومة ليزر –
              جامعة القاهرة. زميل كلية Glasgow البريطانية.</p>
          </div>
        </div>

      </div>

      <div class="divider"></div>
      <h3 style="color:var(--gold);margin-top:20px">قسم الليزر</h3>
      <div class="spotlight-wrap">

        <!-- Spotlight 2 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/liser.jpg') center/cover no-repeat"></div>
          <div class="spot-info">
            <h3>دكتورة ليزر متخصصة</h3>
            <p class="tl-role">أخصائية ليزر</p>
            <p class="tl-desc">متخصصة في بروتوكولات إزالة الشعر وتقييم نوع البشرة للحصول على أفضل النتائج.</p>
          </div>
        </div>

        <!-- Spotlight 3 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/liser.jpg') center/cover no-repeat"></div>
          <div class="spot-info">
            <h3>دكتورة ليزر — متخصصة</h3>
            <p class="tl-role">أخصائية ليزر</p>
            <p class="tl-desc">خبرة واسعة في جلسات الليزر المتقدمة والعناية بأنواع البشرة المختلفة.</p>
          </div>
        </div>

      </div>

      <div class="divider"></div>
      <h3 style="color:var(--gold);margin-top:20px">قسم النساء والتوليد</h3>
      <div class="spotlight-wrap">

        <!-- Spotlight 4 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/ريهام.webp') center/cover no-repeat">
          </div>
          <div class="spot-info">
            <h3>دكتورة نسا متخصصه</h3>
            <p class="tl-role">استشاري نساء وتوليد</p>
            <p class="tl-desc">Bachelor’s degree — Kasr AlAiny Surgery — Master's in Obstetrics and Gynecology.</p>
          </div>
        </div>



      </div>

      <div class="divider"></div>
      <h3 style="color:var(--gold);margin-top:20px">قسم التغذية</h3>
      <div class="spotlight-wrap">

        <!-- Spotlight 6 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/nutritionist.png') center/cover no-repeat"></div>
          <div class="spot-info">
            <h3>دكتورة تغذية علاجية</h3>
            <p class="tl-role">أخصائية تغذية</p>
            <p class="tl-desc"> أخصائية باطنة وتغذية</p>
          </div>
        </div>
      </div>

      <div class="divider"></div>
      <h3 style="color:var(--gold);margin-top:20px">قسم أمراض الباطنة</h3>
      <div class="spotlight-wrap">

        <!-- Spotlight 7 -->
        <div class="spot-card fade-in hover-card">
          <div class="spot-img" style="background:url('assets/imgs/about%20us/rabbi3.jpeg') center/cover no-repeat"></div>
          <div class="spot-info">
            <h3>د. ربيع إسماعيل</h3>
            <p class="tl-role">استشاري أمراض الباطنة </p>
            <p class="tl-desc">متخصص في تشخيص وعلاج أمراض البطن واضطرابات الجهاز الهضمي، مثل القولون، حموضة المعدة،
              والتهابات الكبد.</p>
          </div>
        </div>
      </div>

    </section>

    <style>
      .spotlight-wrap {
        display: flex;
        flex-direction: column;
        gap: 26px;
        margin-top: 20px
      }

      .spot-card {
        display: flex;
        gap: 20px;
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 8px 35px rgba(0, 0, 0, 0.06);
        align-items: flex-start
      }

      .spot-img {
        flex: 0 0 160px;
        height: 180px;
        border-radius: 14px;
        background: #eee
      }

      .spot-info h3 {
        margin: 0 0 6px;
        color: var(--gold);
        font-size: 20px;
        font-weight: 700
      }

      .spot-info p {
        margin: 4px 0;
        color: #555;
        font-size: 15px
      }

      @media(max-width:780px) {
        .spot-card {
          flex-direction: column;
          align-items: center;
          text-align: center
        }

        .spot-img {
          width: 100%;
          height: 260px
        }
      }
    </style>

    <!-- تايم لاين CSS -->
  </div>

  <?php include 'footer.php'; ?>

  <script>
    // (خالي من الأخطاء أو المودالات) الأزرار توجه لصفحة الحجز أو تفتح واتساب كما طلبت
    // يمكنك تحديث أرقام الهواتف أو روابط الفيديو مباشرة في الـ HTML أعلاه
  </script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="assets/js/whatsapp-integration.js"></script>
  <script>
    AOS.init({ duration: 800, once: true });
  </script>
  <script src="assets/js/home-scripts.js"></script>

  <?php include 'booking-modal-include.php'; ?>
</body>

</html>