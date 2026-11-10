<?php
/**
 * booking-submit.php — Brèagha Clinic Booking Submission API
 *
 * Security:  CSRF token validation
 * Validates: All fields, date range (tomorrow → +30 days),
 *            time range (10:00–23:00), slot capacity (max 2/slot)
 * Returns:   JSON {success, message, [booking_id, details, field_errors]}
 */

require_once __DIR__ . '/../config/init.php';
include __DIR__ . '/../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

/* ── helpers ── */
function json_exit(bool $ok, string $msg, array $extra = []): void
{
    echo json_encode(array_merge(
        ['success' => $ok, 'message' => $msg, 'csrf_token' => $_SESSION['csrf_token'] ?? ''],
        $extra
    ));
    exit;
}

/* ── method guard ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit(false, 'طريقة الطلب غير مسموحة');
}

/* ═══════════════════════════════════════════════════════
   CSRF
══════════════════════════════════════════════════════ */
$submitted_token = trim($_POST['csrf_token'] ?? '');

if (
    empty($submitted_token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submitted_token)
) {
    json_exit(false, 'خطأ في التحقق الأمني — أعد تحميل الصفحة وحاول مجدداً');
}

/* ═══════════════════════════════════════════════════════
   RECEIVE & SANITIZE
══════════════════════════════════════════════════════ */
$full_name = sanitize_input($_POST['full_name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$age = (int) ($_POST['age'] ?? 0);
$gender = sanitize_input($_POST['gender'] ?? '');
$department = sanitize_input($_POST['department'] ?? '');
$booking_type = sanitize_input($_POST['booking_type'] ?? 'service');
if ($booking_type !== 'service' && $booking_type !== 'offer') {
    $booking_type = 'service';
}
$service = sanitize_input($_POST['service'] ?? '');
$price = null;
if (isset($_POST['price']) && $_POST['price'] !== '') {
    $price = (float)$_POST['price'];
}
$laser_area = sanitize_input($_POST['laser_area'] ?? '');
$sessions_raw = sanitize_input($_POST['sessions'] ?? '');
$booking_date = sanitize_input($_POST['booking_date'] ?? '');
$booking_time = sanitize_input($_POST['booking_time'] ?? '');

/* ═══════════════════════════════════════════════════════
   ANTI-DOUBLE BOOKING (1 per day)
══════════════════════════════════════════════════════ */
if (isset($_SESSION['booked_today']) && $_SESSION['booked_today'] === true) {
    json_exit(false, 'لقد قمت بحجز موعد بالفعل اليوم. لا يمكنك الحجز مرة أخرى في نفس اليوم.');
}

if (isset($_COOKIE['booked_today']) && $_COOKIE['booked_today'] === 'true') {
    json_exit(false, 'لقد قمت بحجز موعد بالفعل اليوم. لا يمكنك الحجز مرة أخرى في نفس اليوم.');
}


/* ═══════════════════════════════════════════════════════
   VALIDATE
══════════════════════════════════════════════════════ */
$errors = [];

// Full name
if (mb_strlen($full_name, 'UTF-8') < 5) {
    $errors['full_name'] = 'الاسم الكامل مطلوب (5 أحرف على الأقل)';
}

// Phone — 11 digits starting with 0
if (!preg_match('/^0[0-9]{10}$/', $phone)) {
    $errors['phone'] = 'رقم الهاتف يجب أن يكون 11 رقماً ويبدأ بـ 0';
}

// Age
if ($age < 10 || $age > 100) {
    $errors['age'] = 'العمر يجب أن يكون بين 10 و100 سنة';
}

// Department — dynamic from DB with gender-specific schedule
$valid_depts = [];
$dept_res = $conn->query("SELECT id, slug FROM departments WHERE visible = 1");
if ($dept_res) {
    while ($dr = $dept_res->fetch_assoc()) {
        $valid_depts[] = $dr['slug'];
    }
}
if (!in_array($department, $valid_depts, true)) {
    $errors['department'] = 'يرجى اختيار قسم صحيح';
}

// Service
if (empty($service)) {
    $errors['service'] = 'يرجى اختيار الخدمة';
}

// Laser area + sessions (for laser hair removal services)
$sessions = null;
$is_laser = (strpos($service, 'الليزر') !== false || strpos($service, 'Laser') !== false || strpos($service, 'ليزر') !== false);
if ($is_laser) {
    if (!empty($laser_area)) {
        $service = trim($service . ' — المنطقة: ' . $laser_area);
    }

    $valid_sessions = ['1 جلسة', '3 جلسات - عرض'];
    if (!in_array($sessions_raw, $valid_sessions, true)) {
        $errors['sessions'] = 'يرجى اختيار عدد الجلسات';
    } else {
        $sessions = $sessions_raw;
    }
}

// Booking date: must be tomorrow → +30 days + check gender-specific schedule
if (empty($booking_date)) {
    $errors['booking_date'] = 'تاريخ الحجز مطلوب';
} else {
    try {
        $sel = (new DateTime($booking_date))->setTime(0, 0, 0);
        $tmrw = (new DateTime('tomorrow'))->setTime(0, 0, 0);
        $max = (new DateTime('+30 days'))->setTime(23, 59, 59);

        if ($sel < $tmrw) {
            $errors['booking_date'] = 'لا يمكن الحجز لليوم الحالي — ابدأ من الغد';
        } elseif ($sel > $max) {
            $errors['booking_date'] = 'لا يمكن الحجز لأكثر من 30 يوماً مقدماً';
        } else {
            $day_of_week = strtolower($sel->format('l'));
            $day_map = [
                'saturday' => 'السبت', 'sunday' => 'الأحد', 'monday' => 'الإثنين',
                'tuesday' => 'الثلاثاء', 'wednesday' => 'الأربعاء',
                'thursday' => 'الخمис', 'friday' => 'الجمعة'
            ];

            $gender_enum = ($gender === 'ذكر') ? 'male' : 'female';
            $dept_id = 0;
            $dept_id_res = $conn->prepare("SELECT id FROM departments WHERE slug = ? AND visible = 1");
            if ($dept_id_res) {
                $dept_id_res->bind_param('s', $department);
                $dept_id_res->execute();
                $dept_row = $dept_id_res->get_result()->fetch_assoc();
                if ($dept_row) $dept_id = (int)$dept_row['id'];
                $dept_id_res->close();
            }

            if ($dept_id > 0) {
                $sched_stmt = $conn->prepare("SELECT working_days, start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = ?");
                if ($sched_stmt) {
                    $sched_stmt->bind_param('is', $dept_id, $gender_enum);
                    $sched_stmt->execute();
                    $sched_res = $sched_stmt->get_result();
                    $sched_row = $sched_res->fetch_assoc();
                    $sched_stmt->close();

                    if ($sched_row) {
                        $working_days = json_decode($sched_row['working_days'], true) ?: [];
                        if (!in_array($day_of_week, $working_days, true)) {
                            $ar_day = $day_map[$day_of_week] ?? $day_of_week;
                            $gender_label = ($gender_enum === 'male') ? 'الرجال' : 'السيدات';
                            $errors['booking_date'] = "قسم {$department} لا يعمل يوم {$ar_day} لـ{$gender_label}. يرجى اختيار يوم آخر.";
                        }
                    } else {
                        $errors['booking_date'] = 'لا يوجد جدول مواعيد محدد لهذا القسم والجنس.';
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors['booking_date'] = 'تاريخ الحجز غير صالح';
    }
}

// Booking time — validate against gender-specific schedule
if (empty($booking_time)) {
    $errors['booking_time'] = 'وقت الحجز مطلوب';
} elseif (!preg_match('/^\d{2}:\d{2}$/', $booking_time)) {
    $errors['booking_time'] = 'صيغة الوقت غير صحيحة';
} else {
    [$h, $m] = array_map('intval', explode(':', $booking_time));
    $total_min = $h * 60 + $m;

    $gender_enum = ($gender === 'ذكر') ? 'male' : 'female';
    $time_dept_id = 0;
    $time_dept_res = $conn->prepare("SELECT id FROM departments WHERE slug = ? AND visible = 1");
    if ($time_dept_res) {
        $time_dept_res->bind_param('s', $department);
        $time_dept_res->execute();
        $time_dept_row = $time_dept_res->get_result()->fetch_assoc();
        if ($time_dept_row) $time_dept_id = (int)$time_dept_row['id'];
        $time_dept_res->close();
    }

    if ($time_dept_id > 0) {
        $time_stmt = $conn->prepare("SELECT start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = ?");
        if ($time_stmt) {
            $time_stmt->bind_param('is', $time_dept_id, $gender_enum);
            $time_stmt->execute();
            $time_row = $time_stmt->get_result()->fetch_assoc();
            $time_stmt->close();

            if ($time_row) {
                $start_time = $time_row['start_time'] ?? '10:00:00';
                $end_time = $time_row['end_time'] ?? '23:00:00';
                [$sh, $sm] = array_map('intval', explode(':', $start_time));
                [$eh, $em] = array_map('intval', explode(':', $end_time));
                $start_min = $sh * 60 + $sm;
                $end_min = $eh * 60 + $em;

                if ($total_min < $start_min || $total_min >= $end_min) {
                    $errors['booking_time'] = "وقت الحجز يجب أن يكون بين {$sh}:{$sm} و {$eh}:{$em}";
                }
            } else {
                $errors['booking_time'] = 'عذراً، هذا القسم غير متاح للحجز في الوقت الحالي لعدم وجود جدول مواعيد.';
            }
        }
    } else {
        $errors['booking_time'] = 'عذراً، هذا القسم غير متاح للحجز في الوقت الحالي.';
    }
}

if (!empty($errors)) {
    json_exit(false, implode(' ، ', array_values($errors)), ['field_errors' => $errors]);
}

// حماية ضد التكرار خلال 5 دقائق في قاعدة البيانات
$dup_check = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM bookings WHERE phone = ? AND booking_date = ? AND booking_time = ? AND service = ? AND created_at >= NOW() - INTERVAL 5 MINUTE"
);
if ($dup_check) {
    $dup_check->bind_param('ssss', $phone, $booking_date, $booking_time, $service);
    $dup_check->execute();
    $dup_res = $dup_check->get_result()->fetch_assoc();
    $dup_check->close();
    if ($dup_res && (int)$dup_res['cnt'] > 0) {
        json_exit(false, 'لقد قمت بإجراء هذا الحجز للتو. يرجى الانتظار 5 دقائق قبل إعادة المحاولة.');
    }
}

/* ═══════════════════════════════════════════════════════
   SLOT CAPACITY — max 2 per (date, time)
══════════════════════════════════════════════════════ */
$chk = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM bookings WHERE booking_date = ? AND booking_time = ?"
);
if (!$chk) {
    json_exit(false, 'خطأ داخلي — تعذّر التحقق من الموعد');
}
$chk->bind_param('ss', $booking_date, $booking_time);
$chk->execute();
$slot_count = (int) $chk->get_result()->fetch_assoc()['cnt'];
$chk->close();

if ($slot_count >= 2) {
    json_exit(false, 'هذا الموعد ممتلئ، يرجى اختيار وقت آخر');
}

/* ═══════════════════════════════════════════════════════
   GENERATE BOOKING ID
══════════════════════════════════════════════════════ */
$booking_id = 'BRG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

/* ═══════════════════════════════════════════════════════
   INSERT
   Columns: booking_id(s) full_name(s) phone(s) age(i)
            department(s) service(s) sessions(s)
            booking_date(s) booking_time(s)
   Type string = "sssisssss" (9 = 3×s + i + 5×s)
══════════════════════════════════════════════════════ */
$ins = $conn->prepare(
    "INSERT INTO bookings
        (booking_id, full_name, phone, age, gender, department, service, sessions, booking_date, booking_time, status, booking_type, price)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
);

if (!$ins) {
    json_exit(false, 'خطأ في قاعدة البيانات: ' . $conn->error);
}

// Type string breakdown (12 params):
//  1 booking_id   → s
//  2 full_name    → s
//  3 phone        → s
//  4 age          → i
//  5 gender       → s
//  6 department   → s
//  7 service      → s
//  8 sessions     → s
//  9 booking_date → s
// 10 booking_time → s
// 11 booking_type → s
// 12 price        → s (null is mapped to NULL in DB)
//  = "sssissssssss"
$ins->bind_param(
    'sssissssssss',
    $booking_id,
    $full_name,
    $phone,
    $age,
    $gender,
    $department,
    $service,
    $sessions,
    $booking_date,
    $booking_time,
    $booking_type,
    $price
);

if (!$ins->execute()) {
    $db_err = $ins->error;
    $ins->close();
    json_exit(false, 'خطأ أثناء حفظ البيانات: ' . $db_err);
}
$ins->close();

/* ═══════════════════════════════════════════════════════
   SUCCESS RESPONSE & EMAIL NOTIFICATION
══════════════════════════════════════════════════════ */
// Set restriction for double booking
$_SESSION['booked_today'] = true;
$_SESSION['booking_date'] = date('Y-m-d');
setcookie('booked_today', 'true', strtotime('tomorrow'), "/");

$dept_name = $department;
$dept_stmt = $conn->prepare("SELECT name FROM departments WHERE slug = ? AND visible = 1");
if ($dept_stmt) {
    $dept_stmt->bind_param('s', $department);
    $dept_stmt->execute();
    $dept_label_res = $dept_stmt->get_result();
    if ($dept_label_res && $dept_label_res->num_rows > 0) {
        $dept_name = $dept_label_res->fetch_assoc()['name'];
    }
    $dept_stmt->close();
}

// Send Telegram notification
require_once __DIR__ . '/../config/telegram-notify.php';
$message = "🏥 <b>New Booking Received</b>\n" .
    "--------------------\n" .
    "🆔 <b>ID:</b> $booking_id\n" .
    "👤 <b>Name:</b> $full_name\n" .
    "📞 <b>Phone:</b> $phone\n" .
    "🎂 <b>Age:</b> $age\n" .
    "⚧ <b>Gender:</b> $gender\n" .
    "🏥 <b>Department:</b> $dept_name\n" .
    "💼 <b>Type:</b> " . ($booking_type === 'offer' ? 'عرض خاص' : 'خدمة عادية') . "\n" .
    "💼 <b>Service/Offer:</b> $service\n" .
    ($price !== null ? "💰 <b>Price:</b> $price ج.م\n" : "") .
    ($sessions ? "🔢 <b>Sessions:</b> $sessions\n" : "") .
    ($department === 'laser' && !empty($laser_area) ? "🎯 <b>Laser Area:</b> $laser_area\n" : "") .
    "📅 <b>Date:</b> $booking_date\n" .
    "⏰ <b>Time:</b> $booking_time\n" .
    "🕒 <b>Created:</b> " . date('Y-m-d H:i:s') . "\n" .
    "--------------------\n" .
    "<a href='https://breaghaclinic.com/admin/dashboard.php'>📊 View Dashboard</a>";
send_telegram_message($message);

generate_csrf_token();
json_exit(true, 'تم تسجيل حجزك بنجاح! سيتواصل معك فريق براجيا لتأكيد الموعد', [
    'booking_id' => $booking_id,
    'details' => [
        'full_name' => $full_name,
        'phone' => $phone,
        'age' => $age,
        'gender' => $gender,
        'department' => $dept_name,
        'booking_type' => $booking_type,
        'service' => $service,
        'price' => $price,
        'laser_area' => $is_laser ? $laser_area : '',
        'sessions' => $sessions,
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
    ],
]);
?>