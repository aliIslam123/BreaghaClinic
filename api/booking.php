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
$age = sanitize_input($_POST['age'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
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
$booking_date = sanitize_input($_POST['booking_date'] ?? '');
$time_slot = sanitize_input($_POST['time_slot'] ?? '');

if ($name === '') {
    json_response(false, 'الاسم مطلوب.');
}

if (!preg_match('/^0[0-9]{10}$/', $phone)) {
    json_response(false, 'رقم الهاتف يجب أن يكون 11 رقماً ويبدأ بـ 0.');
}

if (!is_numeric($age) || (int)$age < 10 || (int)$age > 120) {
    json_response(false, 'العمر غير صالح.');
}

if ($department === '') {
    json_response(false, 'القسم مطلوب.');
}

if ($service === '') {
    json_response(false, 'الخدمة مطلوبة.');
}

if ($booking_date === '' || strtotime($booking_date) === false) {
    json_response(false, 'تاريخ الحجز غير صالح.');
}

$dayOfWeek = (int) date('w', strtotime($booking_date));
$day_name_map_rev = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$day_of_week_str = $day_name_map_rev[$dayOfWeek] ?? '';

// Validate day against gender-specific schedule
$dayOfWeek = (int) date('w', strtotime($booking_date));
$day_name_map_fwd = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$day_map = ['saturday' => 6, 'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5];

$gender_enum = ($gender === 'ذكر') ? 'male' : 'female';

$dept_id = 0;
$dept_name = '';
$dept_id_res = $conn->prepare("SELECT id, name FROM departments WHERE slug = ? AND visible = 1");
if ($dept_id_res) {
    $dept_id_res->bind_param('s', $department);
    $dept_id_res->execute();
    $dept_id_row = $dept_id_res->get_result()->fetch_assoc();
    if ($dept_id_row) {
        $dept_id = (int)$dept_id_row['id'];
        $dept_name = $dept_id_row['name'];
    }
    $dept_id_res->close();
}

if ($dept_id === 0) {
    json_response(false, '⚠️ القسم المحدد غير موجود.');
}

$sched_stmt = $conn->prepare("SELECT working_days, start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = ?");
if ($sched_stmt) {
    $sched_stmt->bind_param('is', $dept_id, $gender_enum);
    $sched_stmt->execute();
    $sched_row = $sched_stmt->get_result()->fetch_assoc();
    $sched_stmt->close();

    if ($sched_row) {
        $working_days = json_decode($sched_row['working_days'], true) ?: [];
        $selected_day_str = $day_name_map_fwd[$dayOfWeek] ?? '';
        if (!in_array($selected_day_str, $working_days, true)) {
            $gender_label = ($gender_enum === 'male') ? 'الرجال' : 'السيدات';
            json_response(false, "⚠️ قسم {$dept_name} لا يعمل يوم " . ($day_map[$selected_day_str] ?? $selected_day_str) . " لـ{$gender_label}. يرجى اختيار يوم آخر.");
        }
    } else {
        json_response(false, '⚠️ لا يوجد جدول مواعيد محدد لهذا القسم والجنس.');
    }
}

if ($time_slot === '') {
    json_response(false, 'الساعة المحجوزة مطلوبة.');
}

$maxPerDay = 26;
$sqlDay = 'SELECT COUNT(*) as total FROM customer WHERE booking_date = ?';
$stmtDay = $conn->prepare($sqlDay);
if (!$stmtDay) {
    json_response(false, 'خطأ داخلي في قاعدة البيانات.');
}
$stmtDay->bind_param('s', $booking_date);
$stmtDay->execute();
$dayCount = (int) $stmtDay->get_result()->fetch_assoc()['total'];
if ($dayCount >= $maxPerDay) {
    json_response(false, '⚠️ تم اكتمال العدد لهذا اليوم. برجاء اختيار يوم آخر.');
}

$maxPerHour = 2;
$sqlSlot = 'SELECT COUNT(*) as total FROM customer WHERE booking_date = ? AND time_slot = ?';
$stmtSlot = $conn->prepare($sqlSlot);
if (!$stmtSlot) {
    json_response(false, 'خطأ داخلي في قاعدة البيانات.');
}
$stmtSlot->bind_param('ss', $booking_date, $time_slot);
$stmtSlot->execute();
$slotCount = (int) $stmtSlot->get_result()->fetch_assoc()['total'];
if ($slotCount >= $maxPerHour) {
    json_response(false, '⚠️ هذا الموعد ممتلئ (أقصى 2 في الساعة). برجاء اختيار ساعة أخرى أو يوم آخر.');
}

$sqlPhone = 'SELECT COUNT(*) as total FROM customer WHERE phone = ? AND booking_date = ?';
$stmtPhone = $conn->prepare($sqlPhone);
if (!$stmtPhone) {
    json_response(false, 'خطأ داخلي في قاعدة البيانات.');
}
$stmtPhone->bind_param('ss', $phone, $booking_date);
$stmtPhone->execute();
$phoneCount = (int) $stmtPhone->get_result()->fetch_assoc()['total'];
if ($phoneCount >= 2) {
    json_response(false, '⚠️ لقد حجزت بالفعل لهذا اليوم. يمكنك الحجز مجدداً في يوم آخر.');
}

$insert = 'INSERT INTO customer (name, Customer_age, phone, gender, department, booking_date, time_slot, service, booking_type, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$stmtInsert = $conn->prepare($insert);
if (!$stmtInsert) {
    json_response(false, 'خطأ في قاعدة البيانات: ' . $conn->error);
}

$ageInt = (int) $age;
$stmtInsert->bind_param('sissssssss', $name, $ageInt, $phone, $gender, $department, $booking_date, $time_slot, $service, $booking_type, $price);

if (!$stmtInsert->execute()) {
    json_response(false, 'خطأ أثناء حفظ الحجز: ' . $stmtInsert->error);
}

$bookingId = $conn->insert_id;

// Send Telegram notification
require_once __DIR__ . '/../config/telegram-notify.php';
$message = "🏥 <b>New Booking</b>\n" .
           "--------------------\n" .
           "🆔 <b>ID:</b> #$bookingId\n" .
           "👤 <b>Name:</b> $name\n" .
           "📞 <b>Phone:</b> $phone\n" .
           "🎂 <b>Age:</b> $age\n" .
           "⚧ <b>Gender:</b> $gender\n" .
           "🏥 <b>Department:</b> $department\n" .
           "💼 <b>Type:</b> " . ($booking_type === 'offer' ? 'عرض خاص' : 'خدمة عادية') . "\n" .
           "💼 <b>Service/Offer:</b> $service\n" .
           ($price !== null ? "💰 <b>Price:</b> $price ج.م\n" : "") .
           "📅 <b>Date:</b> $booking_date\n" .
           "⏰ <b>Time:</b> $time_slot\n" .
           "🕒 <b>Created:</b> " . date('Y-m-d H:i:s') . "\n" .
           "--------------------\n" .
           "<a href='https://breaghaclinic.com/admin/dashboard.php'>📊 View Dashboard</a>";
send_telegram_message($message);



generate_csrf_token();
json_response(true, 'تم الحجز بنجاح! سيتواصل معك فريقنا لتأكيد الموعد.', [
    'booking_id' => $bookingId,
    'booking_type' => $booking_type,
    'price' => $price
]);
?>