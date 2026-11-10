<?php
require_once __DIR__ . '/../config/init.php';
include __DIR__ . '/../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message, 'csrf_token' => $_SESSION['csrf_token'] ?? ''],
        $extra
    ));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'طريقة الطلب غير مسموحة.');
}

if (!empty($db_error) || !$conn) {
    json_response(false, 'فشل الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.');
}

$csrfError = verify_csrf();
if ($csrfError !== null) {
    json_response(false, $csrfError);
}

$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$service = sanitize_input($_POST['service'] ?? '');
$sessions = (int)($_POST['sessions'] ?? 1);
$offerEligible = isset($_POST['offer_eligible']) && in_array($_POST['offer_eligible'], ['1', 'true', 'yes'], true) ? 1 : 0;

if ($name === '' || $phone === '' || $service === '') {
    json_response(false, 'يرجى ملء جميع الحقول المطلوبة.');
}

if (!preg_match('/^0[0-9]{10}$/', $phone)) {
    json_response(false, 'رقم الهاتف غير صحيح.');
}

if ($sessions < 1 || $sessions > 100) {
    json_response(false, 'عدد الجلسات غير صحيح.');
}

// حماية ضد التكرار خلال 5 دقائق في جدول customer
$dup = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM customer WHERE phone = ? AND service = ? AND created_at >= NOW() - INTERVAL 5 MINUTE"
);
if ($dup) {
    $dup->bind_param('ss', $phone, $service);
    $dup->execute();
    $dup_res = $dup->get_result()->fetch_assoc();
    $dup->close();
    if ($dup_res && (int)$dup_res['cnt'] > 0) {
        json_response(false, 'لقد قمت بإجراء هذا الحجز للتو. يرجى الانتظار قليلاً قبل إعادة المحاولة.');
    }
}

$bookingId = 'BRG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
$age = 0;
$gender = 'غير محدد';
$department = 'quick';

$insert = 'INSERT INTO customer (name, Customer_age, phone, gender, department, service, sessions, offer_eligible, booking_date, time_slot, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), \'\', NOW())';

$stmtInsert = $conn->prepare($insert);
if (!$stmtInsert) {
    json_response(false, 'خطأ في قاعدة البيانات: ' . $conn->error);
}

$stmtInsert->bind_param('sisssiii', $name, $age, $phone, $gender, $department, $service, $sessions, $offerEligible);

if (!$stmtInsert->execute()) {
    json_response(false, 'حدث خطأ في حفظ البيانات: ' . $stmtInsert->error);
}

// Send Telegram notification
require_once __DIR__ . '/../config/telegram-notify.php';
$message = "🏥 <b>New Quick Booking</b>\n" .
           "--------------------\n" .
           "🆔 <b>ID:</b> $bookingId\n" .
           "👤 <b>Name:</b> $name\n" .
           "📞 <b>Phone:</b> $phone\n" .
           "🎂 <b>Age:</b> $age\n" .
           "⚧ <b>Gender:</b> $gender\n" .
           "🏥 <b>Department:</b> $department\n" .
           "💼 <b>Service:</b> $service\n" .
           "🔢 <b>Sessions:</b> $sessions\n" .
           "🎁 <b>Offer Eligible:</b> " . ($offerEligible ? 'Yes' : 'No') . "\n" .
           "📅 <b>Date:</b> " . date('Y-m-d') . "\n" .
           "⏰ <b>Time:</b> N/A\n" .
           "🕒 <b>Created:</b> " . date('Y-m-d H:i:s') . "\n" .
           "--------------------\n" .
           "<a href='https://breaghaclinic.com/admin/dashboard.php'>📊 View Dashboard</a>";
send_telegram_message($message);



json_response(true, 'تم الحجز بنجاح.', ['bookingId' => $bookingId]);
?>