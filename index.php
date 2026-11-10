<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'config/init.php';
include 'config/connection.php';
// visit counter for homepage
require_once 'config/visit_counter.php';
record_visit('home');
$visit_counts = get_visit_counts();

// Load departments for dynamic booking form
$depts_booking = $conn->query("SELECT slug, name, icon FROM departments WHERE visible = 1 ORDER BY sort_order ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
    <link rel="manifest" href="site.webmanifest">
    <meta name="theme-color" content="#C39E31">
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عيادة براجيا للتجميل والليزر في العبور | Brèagha Clinic</title>
    <meta name="description" content="عيادة براجيا للتجميل والليزر والجلدية في العبور — احجزي موعدك الآن مع أفضل الأطباء. خدمات: ليزر، بوتوكس، فيلر، عناية بالبشرة، نساء وتوليد، وأمراض البطن.">
    <meta name="keywords" content="عيادة براجيا, تجميل, ليزر, جلدية, العبور, بوتوكس, فيلر, إزالة شعر, نضارة البشرة, عيادة تجميل العبور, skin clinic obour, laser clinic obour, dermatologist obour">
    <meta name="author" content="Brèagha Clinic">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">

    <!-- Open Graph -->
    <meta property="og:title" content="عيادة براجيا للتجميل والليزر | Brèagha Clinic">
    <meta property="og:description" content="احجزي موعدك الآن في عيادة براجيا — أفضل خدمات التجميل والليزر والجلدية في العبور">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://breaghaclinic.com/">
    <meta property="og:image" content="https://breaghaclinic.com/assets/imgs/Br%C3%A8agha%20-%20English%20Logo.svg">
    <meta property="og:site_name" content="Brèagha Clinic">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="عيادة براجيا للتجميل والليزر | Brèagha Clinic">
    <meta name="twitter:description" content="احجزي موعدك الآن في عيادة براجيا — أفضل خدمات التجميل والليزر والجلدية في العبور">
    <meta name="twitter:image" content="https://breaghaclinic.com/assets/imgs/Br%C3%A8agha%20-%20English%20Logo.svg">

    <!-- Canonical -->
    <link rel="canonical" href="https://breaghaclinic.com/">

    <!-- AOS Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/home-redesign.css">
    <link rel="stylesheet" href="assets/css/booking-section.css">
    <link rel="stylesheet" href="assets/css/footer.css">

    <?php require_once 'config/seo.php'; seo_head_meta('home'); ?>
    <?php include 'seo-schema.php'; ?>
</head>

<body>
    <!-- ✅ شريط التنقل -->
    <nav class="navbar">
        <a href="<?= $base_url ?>/" class="logo" aria-label="الانتقال إلى الصفحة الرئيسية">Brèagha</a>
        <button class="nav-toggle" aria-label="فتح القائمة" aria-expanded="false"><span></span></button>
        <ul class="nav-links">
            <li><a href="<?= $base_url ?>/">الرئيسية</a></li>
            <li><a href="<?= $base_url ?>/services">خدماتنا</a></li>
            <li><a href="<?= $base_url ?>/offers">العروض</a></li>
            <li><a href="<?= $base_url ?>/about">من نحن</a></li>
            <li>
                <a href="javascript:void(0)" onclick="smoothScrollToBooking()" class="nav-book-btn">
                    احجز الآن
                </a>
            </li>
        </ul>
    </nav>

    <!-- Hero — Editorial Luxury -->
    <header class="hero" id="hero">
        <div class="hero__bg">
            <img src="assets/imgs/hero-bg.webp" alt="" class="hero__img" aria-hidden="true" width="1672" height="941" fetchpriority="high" decoding="async">
        </div>
        <div class="hero__shade"></div>
        <div class="hero__grain" aria-hidden="true"></div>
        <canvas class="hero__particles" aria-hidden="true" id="heroCanvas"></canvas>

        <div class="hero__layout">
            <div class="hero__brand" data-hero="brand">
                <span class="hero__logo-text">Brèagha</span>
                <span class="hero__logo-sub">C L I N I C</span>
            </div>

            <div class="hero__divider" data-hero="line"></div>

            <div class="hero__copy">
                <h1 class="hero__headline" data-hero="headline">عيادة براجيا للتجميل والليزر</h1>
                <p class="hero__tagline" data-hero="tagline">جمالك يبدأ من هنا، وإحنا هنا نوصلك لأفضل نسخة منك</p>
                <p class="hero__hours" data-hero="hours">
                    <span class="hero__hours-dot"></span>
                    مواعيدنا من 10ص إلى 11م
                </p>
                <div class="hero__actions" data-hero="actions">
                    <a href="javascript:void(0)" onclick="smoothScrollToBooking()" class="hero__cta hero__cta--primary">
                        <span>احجز الآن</span>
                    </a>
                    <a href="<?= $base_url ?>/offers" class="hero__cta hero__cta--ghost">عروض خاصة</a>
                </div>
            </div>
        </div>

        <div class="hero__scroll" data-hero="scroll">
            <div class="hero__scroll-diamond"></div>
            <span>اكتشف المزيد</span>
        </div>
    </header>

    <!-- 💎 قسم عن العيادة -->
    <section class="about-section" data-aos="fade-up">
        <h2>عن براجيا</h2>
        <p>
            في براجيا بنقدملك تجربة فريدة من نوعها، تعتمد على التكنولوجيا الحديثة
            والخبرة الطبية الدقيقة لتوصلك لأفضل نتائج تجميلية بدون مبالغة أو مجازفة.
            فريقنا الطبي المتخصص بيهتم بكل التفاصيل عشان يوصلك لأحسن إحساس بالثقة والجمال.
        </p>
    </section>

    <!-- 🌟 قسم المميزات -->
    <section class="features-section">
        <div class="feature" data-aos="fade-right">
            <img src="assets/imgs/Brèagha - English Logo.svg" alt="خبرة طبية">
            <div class="feature-text">
                <h3>فريق طبي محترف</h3>
                <p>كل أفراد الطاقم الطبي في براجيا من طبيبات جلدية بشريات معتمدات ومرخصات من:
                    المعهد القومي لليزر – جامعة القاهرة
                    كلية غلاسكو – اسكتلندا
                    جامعتي عين شمس والأزهر
                    بقيادة:
                    د. سماح جمال عبد الناصر
                    استشاري الجلدية والتجميل والليزر.
                    بكالوريوس طب – جامعة عين شمس.
                    ماجستير الجلدية والتجميل – جامعة الأزهر.
                    دبلومة ليزر – جامعة القاهرة.
                    زميل كلية Glasgow البريطانية.
                </p>
            </div>
        </div>
        <div class="feature reverse" data-aos="fade-left">
            <img src="uploads/services/koreanlaser.webp" alt="أحدث الأجهزة">
            <div class="feature-text">
                <h3>أحدث الأجهزة</h3>
                <p>نعتمد على تقنيات طبية متطورة وآمنة لضمان نتائج فعالة ودائمة.</p>
            </div>
        </div>
        <div class="feature" data-aos="fade-right">
            <img src="assets/imgs/location1.webp" alt="راحة العملاء">
            <div class="feature-text">
                <h3>راحة ورفاهية</h3>
                <p>من اللحظة الأولى لدخولك عيادة براجيا، هتحس إن كل حاجة معمولة علشان راحتك:
                    الاستقبال بالكامل نسائي لضمان الخصوصية.
                    ديكور هادئ وأجواء مريحة تساعد على الاسترخاء أثناء الجلسات.
                    منطقة انتظار راقية فيها بوفيه مجاني ومشروبات خفيفة.
                    متوفر كتب ومجلات للاستفادة بالوقت أثناء الانتظار.
                    مواعيد منظمة جدًا لتقليل فترات الانتظار.
                    بعد كل مريض بيتم تعقيم شامل للأجهزة وغرف الجلسات طبقًا لمعايير وزارة الصحة والمعهد القومي لليزر.
                </p>
            </div>
        </div>
    </section>

    <!-- 🎯 زر خدماتنا -->
    <div class="services-link" data-aos="zoom-in">
        <h2>اكتشف خدماتنا</h2>
        <a href="<?= $base_url ?>/services" class="btn-secondary">تصفح الخدمات</a>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         📅 قسم الحجز — Booking Section
    ═══════════════════════════════════════════════════════ -->
    <section id="booking-section" class="booking-section-enhanced" data-aos="fade-up">
        <div class="booking-layout">

            <!-- ─── Left: Info Panel ─── -->
            <div class="booking-info-panel">
                <h2>احجز موعدك الآن</h2>
                <p class="booking-tagline">حجز سهل وسريع مع أفضل الأطباء المتخصصين في مجال التجميل والجلدية</p>

                <div class="booking-benefits">
                    <div class="benefit-item">
                        <span class="benefit-icon">✓</span>
                        <span class="benefit-text">تأكيد فوري للموعد</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">✓</span>
                        <span class="benefit-text">عروض حصرية لجلسات الليزر المتعددة</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">✓</span>
                        <span class="benefit-text">مواعيد من 10 صباحاً حتى 11 مساءً</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">✓</span>
                        <span class="benefit-text">فريق براجيا في خدمتك دائماً</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">✓</span>
                        <span class="benefit-text">ضمان رضا العميل 100%</span>
                    </div>
                </div>

                <a href="https://wa.me/201108343753?text=السلام عليكم، أرغب في حجز موعد" target="_blank"
                    class="booking-whatsapp-alt">
                    <i class="fab fa-whatsapp"></i>
                    أو تواصل عبر واتساب
                </a>
            </div>

            <!-- ─── Right: Booking Form Card ─── -->
            <div class="booking-form-card">
                <h3>📋 نموذج <span>الحجز</span></h3>

                <!-- Global alert banner -->
                <div id="bf-global-alert" class="bf-global-alert" role="alert"></div>

                <!-- ── Form Wrap (hidden after success) ── -->
                <div id="booking-form-wrap">
                    <form id="inline-booking-form" novalidate>

                        <!-- CSRF token -->
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="price" id="bf-price">

                        <!-- Row 1: Name + Phone -->
                        <div class="bf-row">
                            <div class="bf-group">
                                <label for="bf-full_name">الاسم الكامل <span class="req">*</span></label>
                                <input type="text" id="bf-full_name" name="full_name" placeholder="اكتب اسمك الثلاثي"
                                    minlength="5" autocomplete="name" required>
                                <span class="bf-error" id="bf-full_name-err"></span>
                            </div>
                            <div class="bf-group">
                                <label for="bf-phone">رقم الهاتف <span class="req">*</span></label>
                                <input type="tel" id="bf-phone" name="phone" placeholder="01XXXXXXXXX"
                                    pattern="0[0-9]{10}" maxlength="11" autocomplete="tel" required>
                                <span class="bf-error" id="bf-phone-err"></span>
                            </div>
                        </div>

                        <!-- Row 2: Age + Department -->
                        <div class="bf-row">
                            <div class="bf-group">
                                <label for="bf-age">العمر <span class="req">*</span></label>
                                <input type="number" id="bf-age" name="age" min="10" max="100" placeholder="العمر"
                                    required>
                                <span class="bf-error" id="bf-age-err"></span>
                            </div>
                            <div class="bf-group">
                                <label for="bf-gender">النوع <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-gender" name="gender" required>
                                        <option value="">-- اختر النوع --</option>
                                        <option value="ذكر">ذكر</option>
                                        <option value="أنثى">أنثى</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-gender-err"></span>
                            </div>
                            <div class="bf-group">
                                <label for="bf-department">القسم <span class="req">*</span></label>
                                <div class="select-wrap">
                                        <select id="bf-department" name="department" required>
                                        <option value="">-- اختر القسم --</option>
                                        <?php if ($depts_booking): $depts_booking->data_seek(0); while ($d = $depts_booking->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($d['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endwhile; endif; ?>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-department-err"></span>
                            </div>
                        </div>

                        <!-- Row 3: Booking Type + Service/Offer -->
                        <div class="bf-row">
                            <div class="bf-group">
                                <label for="bf-booking_type">نوع الحجز <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-booking_type" name="booking_type" disabled required>
                                        <option value="">-- اختر نوع الحجز --</option>
                                        <option value="service">خدمة عادية</option>
                                        <option value="offer">عرض خاص</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-booking_type-err"></span>
                            </div>
                            <div class="bf-group">
                                <label id="bf-service-label" for="bf-service">الخدمة <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-service" name="service" disabled required>
                                        <option value="">-- اختر القسم أولاً --</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-service-err"></span>
                            </div>
                        </div>

                        <!-- Laser area (Laser only — animated show/hide) -->
                        <div id="laser-area-wrapper" class="sessions-field-wrapper">
                            <div class="bf-group">
                                <label for="bf-laser_area">المنطقة <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-laser_area" name="laser_area">
                                        <option value="">-- اختر المنطقة --</option>
                                        <option value="جسم كامل">جسم كامل</option>
                                        <option value="وجه">وجه</option>
                                        <option value="بكيني + اندر أرم">بكيني + اندر أرم</option>
                                        <option value="بكيني">بكيني</option>
                                        <option value="رجلين كاملين">رجلين كاملين</option>
                                        <option value="نصف رجلين">نصف رجلين</option>
                                        <option value="ذراعين كاملين">ذراعين كاملين</option>
                                        <option value="نصف ذراعين">نصف ذراعين</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-laser_area-err"></span>
                            </div>
                        </div>

                        <!-- Sessions (Laser only — animated show/hide) -->
                        <div id="sessions-wrapper" class="sessions-field-wrapper">
                            <div class="bf-group">
                                <label for="bf-sessions">عدد الجلسات <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-sessions" name="sessions">
                                        <option value="">-- اختر عدد الجلسات --</option>
                                        <option value="1 جلسة">جلسة واحدة (1 جلسة)</option>
                                        <option value="3 جلسات - عرض">3 جلسات — خصم خاص 🎉</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-sessions-err"></span>
                            </div>
                        </div>

                        <!-- Row 4: Date + Time -->
                        <div class="bf-row">
                            <div class="bf-group">
                                <label for="bf-booking_date">تاريخ الحجز <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-booking_date" name="booking_date" required>
                                        <option value="">-- اختر التاريخ --</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-booking_date-err"></span>
                            </div>
                            <div class="bf-group">
                                <label for="bf-booking_time">وقت الحجز <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="bf-booking_time" name="booking_time" required>
                                        <option value="">-- اختر الوقت --</option>
                                    </select>
                                </div>
                                <span class="bf-error" id="bf-booking_time-err"></span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="bf-submit-wrap">
                            <button type="submit" id="bf-submit-btn" class="bf-submit-btn">
                                <span class="btn-text">📅 تأكيد الحجز</span>
                                <span class="btn-loader">
                                    <span class="spin-ring"></span>
                                </span>
                            </button>
                        </div>

                    </form><!-- /#inline-booking-form -->
                </div><!-- /#booking-form-wrap -->

                <!-- ── Success Card (Invoice Style) ── -->
                <div id="bf-success-card" class="bf-success-card" role="status" aria-live="polite">

                    <div id="invoice-capture-area" class="invoice-container">
                        <div class="success-svg-wrap">
                            <svg viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
                                <circle class="checkmark-circle" cx="26" cy="26" r="25" stroke-width="2" />
                                <path class="checkmark-path" stroke-width="3" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                            </svg>
                        </div>
                        <h3>🎉 تم الحجز بنجاح!</h3>

                        <div class="invoice-header">
                            <div class="invoice-status">حالة الحجز: <span class="status-pending">قيد التأكيد (Pending
                                    Confirmation)</span></div>
                            <div id="ss-booking-id" class="bf-success-id">رقم الحجز: —</div>
                        </div>

                        <table class="bf-summary-table">
                            <tbody>
                                <tr>
                                    <td>الاسم</td>
                                    <td id="ss-name">—</td>
                                </tr>
                                <tr>
                                    <td>الهاتف</td>
                                    <td id="ss-phone">—</td>
                                </tr>
                                <tr>
                                    <td>العمر</td>
                                    <td id="ss-age">—</td>
                                </tr>
                                <tr>
                                    <td>القسم</td>
                                    <td id="ss-dept">—</td>
                                </tr>
                                <tr>
                                    <td>الخدمة</td>
                                    <td id="ss-service">—</td>
                                </tr>
                                <tr id="ss-laser-area-row" style="display:none;">
                                    <td>المنطقة</td>
                                    <td id="ss-laser-area">—</td>
                                </tr>
                                <tr id="ss-sessions-row">
                                    <td>الجلسات</td>
                                    <td id="ss-sessions">—</td>
                                </tr>
                                <tr id="ss-price-row" style="display:none;">
                                    <td>السعر</td>
                                    <td id="ss-price">—</td>
                                </tr>
                                <tr>
                                    <td>التاريخ</td>
                                    <td id="ss-date">—</td>
                                </tr>
                                <tr>
                                    <td>الوقت</td>
                                    <td id="ss-time">—</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="invoice-footer-logo">Brèagha Clinic</div>
                    </div>

                    <p style="color:#777; font-size:.9rem; margin-top:20px; margin-bottom:20px;">
                        لإتمام الحجز، يرجى تأكيد الموعد عبر واتساب. يمكنك أيضاً حفظ صورة الحجز.
                    </p>

                    <div class="bf-success-actions">
                        <button id="download-invoice-btn" class="bf-download-btn" onclick="downloadInvoice()">
                            <i class="fas fa-download"></i> حفظ كصورة
                        </button>
                        <a id="ss-whatsapp-btn" href="javascript:void(0)" class="bf-whatsapp-btn"
                            onclick="var card=document.getElementById('bf-success-card'); if(card&&card._bookingData){openWhatsAppConfirmation(card._bookingData);}">
                            <i class="fab fa-whatsapp"></i> تأكيد الحجز عبر واتساب
                        </a>
                    </div>
                </div><!-- /#bf-success-card -->

            </div><!-- /.booking-form-card -->
        </div><!-- /.booking-layout -->
    </section><!-- /#booking-section -->

    <?php include 'footer.php'; ?>

    <!-- JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="assets/js/booking-section.js"></script>
    <script src="assets/js/home-scripts.js"></script>

    <script>
        AOS.init({ duration: 1000, once: true });

        // Handle hash scroll on page load (#booking-section)
        window.addEventListener('load', function () {
            if (window.location.hash === '#booking-section') {
                setTimeout(smoothScrollToBooking, 400);
            }
        });
    </script>
</body>

</html>
