<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'config/init.php';
include 'config/connection.php';
$dept_bf = $conn->query("SELECT slug, name, icon FROM departments WHERE visible = 1 ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#C39E31">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>حجز موعد في عيادة براجيا | Brèagha Clinic</title>
  <meta name="description" content="احجز موعدك الآن في عيادة براجيا للتجميل والليزر — حجز أونلاين سريع وسهل.">
  <meta name="keywords" content="حجز موعد, عيادة براجيا, حجز عيادة تجميل, العبور">
  <meta name="robots" content="noindex, follow">
  <link rel="canonical" href="https://breaghaclinic.com/booking">
  <?php require_once __DIR__ . '/config/seo.php'; seo_head_meta('booking'); ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/booking-form.css">
<style>
  /* ── Schedule Info Sheet ── */
  .sched-info-box {
    background: linear-gradient(135deg, #fdf8ed, #fff);
    border: 2px solid #e8d49a;
    border-radius: 16px;
    padding: 18px 22px;
    margin-bottom: 24px;
    font-family: 'Cairo', sans-serif;
  }

  .sched-info-box h3 {
    color: #a38227;
    font-size: 1rem;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .sched-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    color: #444;
  }

  .sched-table th {
    background: #C39E31;
    color: #fff;
    padding: 7px 10px;
    text-align: right;
    font-weight: 700;
  }

  .sched-table td {
    padding: 7px 10px;
    border-bottom: 1px solid #f0ece0;
  }

  .sched-table tr:last-child td {
    border-bottom: none;
  }

  .sched-table tr:nth-child(even) td {
    background: #faf8f0;
  }

  .sched-note-row td {
    background: #fff8df !important;
    color: #7a5c00;
    font-weight: 600;
    font-size: 0.82rem;
  }

  /* ── Form layout ── */
  .registration-form .container {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .form-group label {
    font-weight: 700;
    color: #444;
    font-size: 0.92rem;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #ddd;
    border-radius: 10px;
    background: #faf9f6;
    font-family: 'Cairo', sans-serif;
    font-size: 0.95rem;
    transition: border-color 0.3s, box-shadow 0.3s;
    color: #333;
  }

  .form-group input:focus,
  .form-group select:focus {
    border-color: #C39E31;
    box-shadow: 0 0 8px rgba(195, 158, 49, 0.25);
    outline: none;
  }

  .form-group select:disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }

  .submit-btn {
    width: 100%;
    background: #C39E31;
    color: #fff;
    border: none;
    padding: 15px;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 30px;
    cursor: pointer;
    margin-top: 8px;
    font-family: 'Cairo', sans-serif;
    transition: all 0.3s ease;
  }

  .submit-btn:hover {
    background: #a38227;
    transform: translateY(-2px);
  }

  .capacity-warning {
    background: #fff3cd;
    border: 1px solid #ffe082;
    border-radius: 10px;
    padding: 10px 14px;
    color: #7a5a00;
    font-size: 0.88rem;
    display: none;
  }

  .alert { padding: 15px 20px; border-radius: 10px; font-size: 1rem; text-align: center; }
  .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
  .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }

  @media (max-width: 650px) {
    .form-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<!-- ورقة المواعيد -->
<div class="sched-info-box">
  <h3>🗓️ مواعيد الأقسام</h3>
  <table class="sched-table">
    <thead>
      <tr>
        <th>القسم</th>
        <th>الأيام</th>
        <th>المواعيد</th>
        <th>ملاحظة</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="4" style="text-align:center;padding:16px;color:#666;">
          تظهر المواعيد تلقائياً حسب القسم والجنس المختار من القائمة أدناه.
        </td>
      </tr>
      <tr class="sched-note-row">
        <td colspan="4">⚠️ أقصى عدد حجوزات في الساعة الواحدة: <strong>2 أشخاص فقط</strong> — إذا امتلأت الساعة اختار
          ساعة أخرى</td>
      </tr>
    </tbody>
  </table>
</div>

<form method="POST" class="registration-form" id="mainBookingForm" onsubmit="return false;">
  <section class="registration" id="form">
    <div class="container">

      <!-- الاسم والعمر -->
      <div class="form-row">
        <div class="form-group">
          <label>الاسم الكامل</label>
          <input type="text" required name="name" minlength="10" placeholder="اكتب الاسم ثلاثي على الأقل">
        </div>
        <div class="form-group">
          <label>العمر</label>
          <input type="number" min="15" max="80" required name="age">
        </div>
      </div>

      <!-- الهاتف والنوع -->
      <div class="form-row">
        <div class="form-group">
          <label>رقم الهاتف</label>
          <input type="tel" pattern="[0-9]{11}" required name="phone" placeholder="01xxxxxxxxx">
        </div>
        <div class="form-group">
          <label for="gender">النوع:</label>
          <select name="gender" id="gender" required onchange="onGenderChange()">
            <option value="">-- اختر النوع --</option>
            <option value="ذكر">ذكر</option>
            <option value="أنثى">أنثى</option>
          </select>
        </div>
      </div>

      <!-- القسم ونوع الحجز -->
      <div class="form-row">
        <div class="form-group">
          <label for="department">القسم:</label>
          <select name="department" id="department" required onchange="onDeptChange()">
            <option value="">-- اختر القسم --</option>
            <?php if ($dept_bf): $dept_bf->data_seek(0); while ($d = $dept_bf->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($d['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endwhile; endif; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="booking_type">نوع الحجز:</label>
          <select name="booking_type" id="booking_type" required disabled onchange="onBookingTypeChange()">
            <option value="">-- اختر القسم أولاً --</option>
            <option value="service">خدمة عادية</option>
            <option value="offer">عرض خاص</option>
          </select>
        </div>
      </div>

      <!-- الخدمة / العرض والتاريخ -->
      <div class="form-row">
        <div class="form-group">
          <label for="service" id="service_label">الخدمة:</label>
          <select name="service" id="service" required disabled onchange="onServiceChange()">
            <option value="">-- اختر نوع الحجز أولاً --</option>
          </select>
        </div>
        <div class="form-group">
          <label for="booking_date">تاريخ الحجز:</label>
          <select name="booking_date" id="booking_date" required disabled onchange="buildTimeSlots()">
            <option value="">-- اختر الخدمة أولاً --</option>
          </select>
        </div>
      </div>

      <!-- الساعة وتحذير امتلاء -->
      <div class="form-row">
        <div class="form-group">
          <label for="time_slot">الساعة:</label>
          <select name="time_slot" id="time_slot" required disabled>
            <option value="">-- اختر التاريخ أولاً --</option>
          </select>
        </div>
        <div class="form-group" style="visibility: hidden;">
          <!-- Placeholder to maintain grid symmetry -->
        </div>
      </div>

      <!-- تحذير امتلاء -->
      <div class="capacity-warning" id="capacityWarning">
        ⚠️ هذه الساعة قد تكون ممتلئة — أقصى حجوزات في الساعة 2 فقط. إذا رُفض الحجز اختر ساعة أخرى.
      </div>

      <!-- Hidden fields -->
      <input type="hidden" name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="price" id="price">

      <button type="submit" class="submit-btn">إتمام الحجز</button>
    </div>
  </section>
</form>

<div id="bf-result" style="display:none; margin-top:20px;"></div>

<script>
  // ════════════════════════════════════════════════════
  //  DATA: Services and Offers per department
  // ════════════════════════════════════════════════════
  const deptServices = {};
  const deptOffers = {};

  // Load services dynamically
  async function loadAllServices() {
    try {
      const res = await fetch('api/services.php');
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      data.forEach(s => {
        if (!deptServices[s.department_slug]) deptServices[s.department_slug] = [];
        deptServices[s.department_slug].push(s.name);
      });
    } catch (e) {
      console.error('Failed to load services:', e);
    }
  }
  loadAllServices();

  // Load offers dynamically
  async function loadAllOffers() {
    try {
      const res = await fetch('api/offers.php');
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      
      // Category map to department slugs
      const categoryMap = {
        'الليزر': 'laser',
        'الإجراءات التجميلية': 'cosm',
        'الجلدية وعلاجات الجلدية': 'derma',
        'أمراض الباطنة': 'gastro',
        'النساء والتوليد': 'gyn'
      };
      
      data.forEach(o => {
        const slug = categoryMap[o.category];
        if (slug) {
          if (!deptOffers[slug]) deptOffers[slug] = [];
          deptOffers[slug].push(o);
        }
      });
    } catch (e) {
      console.error('Failed to load offers:', e);
    }
  }
  loadAllOffers();

  // ════════════════════════════════════════════════════
  //  Dept schedule cache (loaded from API)
  // ════════════════════════════════════════════════════
  let deptScheduleCache = null;
  const DAY_MAP = { saturday: 6, sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5 };

  async function loadDeptSchedules(gender) {
    try {
      const genderParam = (gender === 'ذكر') ? 'male' : (gender === 'أنثى') ? 'female' : '';
      const url = genderParam ? `api/departments.php?gender=${genderParam}` : 'api/departments.php';
      const r = await fetch(url);
      if (!r.ok) throw new Error('Network');
      deptScheduleCache = await r.json();
    } catch (e) {
      console.error('Failed to load dept schedules:', e);
      deptScheduleCache = [];
    }
  }
  loadDeptSchedules();

  function getDeptSchedule(slug) {
    if (!deptScheduleCache) return null;
    return deptScheduleCache.find(d => d.slug === slug) || null;
  }

  function getDeptAllowedDays(dept) {
    const deptSch = getDeptSchedule(dept);
    if (deptSch) {
      const workingDays = deptSch.working_days || [];
      return workingDays.map(d => DAY_MAP[d]).filter(d => d !== undefined);
    }
    return [];
  }

  function getDeptTimeSlots(dept, gender, dayOfWeek) {
    const deptSch = getDeptSchedule(dept);
    if (!deptSch) return [];

    let startHour = parseInt((deptSch.start_time || '10:00:00').split(':')[0]);
    let endHour = parseInt((deptSch.end_time || '23:00:00').split(':')[0]);

    const slots = [];
    for (let h = startHour; h < endHour; h++) {
      const nextH = h + 1;
      const hLabel = h <= 12 ? (h === 12 ? 12 : h) : (h - 12);
      const nextLabel = nextH <= 12 ? (nextH === 12 ? 12 : nextH) : (nextH - 12);
      const period = h < 12 ? 'ص' : 'م';
      const nextPeriod = nextH < 12 ? 'ص' : (nextH === 12 ? 'م' : 'م');
      slots.push(`${hLabel}${period} - ${nextLabel}${nextPeriod}`);
    }
    return slots;
  }

  // ════════════════════════════════════════════════════
  //  ARABIC DAY NAMES
  // ════════════════════════════════════════════════════
  const arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
  const arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

  function formatDate(d) {
    return arabicDays[d.getDay()] + ' ' + d.getDate() + ' ' + arabicMonths[d.getMonth()];
  }

  function toYMD(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  // ════════════════════════════════════════════════════
  //  BUILD DATE SELECT
  // ════════════════════════════════════════════════════
  function buildDates() {
    const dept = document.getElementById('department').value;
    const gender = document.getElementById('gender').value;
    const service = document.getElementById('service').value;
    const dateSel = document.getElementById('booking_date');
    const timeSel = document.getElementById('time_slot');

    dateSel.innerHTML = '';
    timeSel.innerHTML = '<option value="">-- اختر التاريخ أولاً --</option>';
    timeSel.disabled = true;

    if (!dept || !gender) {
      dateSel.innerHTML = '<option value="">-- اختر القسم والنوع أولاً --</option>';
      dateSel.disabled = true;
      return;
    }

    if (!service) {
      dateSel.innerHTML = '<option value="">-- اختر الخدمة أولاً --</option>';
      dateSel.disabled = true;
      return;
    }

    let allowedDays = getDeptAllowedDays(dept);

    if (allowedDays.length === 0) {
      dateSel.innerHTML = '<option value="">-- لا توجد أيام متاحة --</option>';
      dateSel.disabled = true;
      return;
    }

    dateSel.innerHTML = '<option value="">-- اختر التاريخ --</option>';
    dateSel.disabled = false;

    const today = new Date();
    let added = 0;
    for (let i = 1; i <= 60 && added < 30; i++) {
      const d = new Date(today);
      d.setDate(today.getDate() + i);
      if (allowedDays.includes(d.getDay())) {
        const opt = document.createElement('option');
        opt.value = toYMD(d);
        opt.textContent = formatDate(d) + ' – ' + toYMD(d);
        dateSel.appendChild(opt);
        added++;
      }
    }
  }

  // ════════════════════════════════════════════════════
  //  BUILD TIME SLOTS
  // ════════════════════════════════════════════════════
  function buildTimeSlots() {
    const dept = document.getElementById('department').value;
    const gender = document.getElementById('gender').value;
    const dateVal = document.getElementById('booking_date').value;
    const timeSel = document.getElementById('time_slot');

    timeSel.innerHTML = '';
    timeSel.disabled = true;

    if (!dateVal) return;

    const slots = getDeptTimeSlots(dept, gender);

    timeSel.disabled = false;
    timeSel.innerHTML = '<option value="">-- اختر الساعة --</option>';
    slots.forEach(s => {
      const o = document.createElement('option');
      o.value = s;
      o.textContent = s;
      timeSel.appendChild(o);
    });
  }

  // ════════════════════════════════════════════════════
  //  REACTIVITY HANDLERS
  // ════════════════════════════════════════════════════
  async function onGenderChange() {
    const gender = document.getElementById('gender').value;
    const dept = document.getElementById('department').value;
    if (dept) {
      await loadDeptSchedules(gender);
    }
    buildDates();
  }

  async function onDeptChange() {
    const dept = document.getElementById('department').value;
    const gender = document.getElementById('gender').value;
    await loadDeptSchedules(gender);
    const typeSel = document.getElementById('booking_type');
    const serviceSel = document.getElementById('service');
    const dateSel = document.getElementById('booking_date');
    const timeSel = document.getElementById('time_slot');

    // Reset fields downstream
    typeSel.value = '';
    typeSel.disabled = !dept;
    if (!dept) {
      typeSel.innerHTML = '<option value="">-- اختر القسم أولاً --</option>';
    } else {
      typeSel.innerHTML = `
        <option value="">-- اختر نوع الحجز --</option>
        <option value="service">خدمة عادية</option>
        <option value="offer">عرض خاص</option>
      `;
    }

    serviceSel.innerHTML = '<option value="">-- اختر نوع الحجز أولاً --</option>';
    serviceSel.disabled = true;

    dateSel.innerHTML = '<option value="">-- اختر الخدمة أولاً --</option>';
    dateSel.disabled = true;

    timeSel.innerHTML = '<option value="">-- اختر التاريخ أولاً --</option>';
    timeSel.disabled = true;

    document.getElementById('price').value = '';
  }

  function onBookingTypeChange() {
    const dept = document.getElementById('department').value;
    const type = document.getElementById('booking_type').value;
    const serviceSel = document.getElementById('service');
    const label = document.getElementById('service_label');

    serviceSel.innerHTML = '';
    serviceSel.disabled = true;
    document.getElementById('price').value = '';

    if (!dept || !type) {
      serviceSel.innerHTML = '<option value="">-- اختر نوع الحجز أولاً --</option>';
      if (label) label.textContent = 'الخدمة:';
      return;
    }

    if (label) {
      label.textContent = type === 'offer' ? 'العرض:' : 'الخدمة:';
    }

    serviceSel.disabled = false;
    if (type === 'offer') {
      serviceSel.innerHTML = '<option value="">-- اختر العرض --</option>';
      const offers = deptOffers[dept] || [];
      if (offers.length === 0) {
        serviceSel.innerHTML = '<option value="">-- لا توجد عروض متاحة في هذا القسم --</option>';
      } else {
        offers.forEach(o => {
          const opt = document.createElement('option');
          opt.value = o.name;
          opt.textContent = `${o.name} (${parseFloat(o.price)} ج.م)`;
          opt.setAttribute('data-price', o.price);
          serviceSel.appendChild(opt);
        });
      }
    } else {
      serviceSel.innerHTML = '<option value="">-- اختر الخدمة --</option>';
      const services = deptServices[dept] || [];
      if (services.length === 0) {
        serviceSel.innerHTML = '<option value="">-- لا توجد خدمات متاحة في هذا القسم --</option>';
      } else {
        services.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s;
          opt.textContent = s;
          serviceSel.appendChild(opt);
        });
      }
    }

    buildDates();
  }

  function onServiceChange() {
    const serviceSel = document.getElementById('service');
    const selectedOpt = serviceSel.options[serviceSel.selectedIndex];
    const price = selectedOpt ? selectedOpt.getAttribute('data-price') : '';
    document.getElementById('price').value = price || '';

    buildDates();
  }

  // Show capacity warning when time is selected
  document.getElementById('time_slot')?.addEventListener('change', function () {
    const warn = document.getElementById('capacityWarning');
    if (warn) warn.style.display = this.value ? 'block' : 'none';
  });

  // ════════════════════════════════════════════════════
  //  AJAX FORM SUBMISSION
  // ════════════════════════════════════════════════════
  document.getElementById('mainBookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = this.querySelector('button[type="submit"]');
    const resultDiv = document.getElementById('bf-result');

    // Basic validation
    const required = ['name', 'age', 'department', 'service', 'booking_date', 'time_slot'];
    for (const id of required) {
      const el = document.getElementById(id);
      if (!el || !el.value) {
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-danger">يرجى تعبئة جميع الحقول المطلوبة</div>';
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }

    btn.disabled = true;
    btn.textContent = 'جاري الحجز...';

    const formData = new FormData(this);
    try {
      const res = await fetch('api/booking.php', {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();

      // Update CSRF token from response
      if (data.csrf_token) {
        this.querySelector('input[name="csrf_token"]').value = data.csrf_token;
      }

      resultDiv.style.display = 'block';

      if (data.success) {
        resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
        // Reset form
        this.reset();
        document.getElementById('service').innerHTML = '<option value="">-- اختر الخدمة أولاً --</option>';
        document.getElementById('booking_date').innerHTML = '<option value="">-- اختر الخدمة أولاً --</option>';
        document.getElementById('time_slot').innerHTML = '<option value="">-- اختر التاريخ أولاً --</option>';
        document.getElementById('price').value = '';
        if (data.booking_id) {
          resultDiv.innerHTML += '<p><strong>رقم الحجز:</strong> ' + data.booking_id + '</p>';
        }
      } else {
        resultDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'حدث خطأ أثناء الحجز') + '</div>';
      }
      resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (err) {
      resultDiv.style.display = 'block';
      resultDiv.innerHTML = '<div class="alert alert-danger">فشل الاتصال بالخادم</div>';
    } finally {
      btn.disabled = false;
      btn.textContent = 'إتمام الحجز';
    }
  });
</script>