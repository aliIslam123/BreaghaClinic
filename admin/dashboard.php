<?php
header('Content-Type: text/html; charset=utf-8');

require_once '../config/init.php';
require_once '../config/connection.php';
if (file_exists(__DIR__ . '/../config/visit_counter.php')) {
    require_once __DIR__ . '/../config/visit_counter.php';
}

// Auth check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'] ?? 'admin';
$page = isset($_GET['page']) ? preg_replace('/[^a-z_]/', '', $_GET['page']) : 'home';

// Restrict services/offers management to superadmin only
$admin_pages = ['manage_services', 'add_service', 'edit_service', 'manage_offers', 'add_offer', 'edit_offer', 'manage_departments', 'add_department', 'edit_department', 'manage_bookings', 'view_booking', 'cancelled_bookings', 'manage_schedules'];
if (in_array($page, $admin_pages) && $role !== 'superadmin') {
    header("Location: dashboard.php");
    exit();
}

// Handle POST actions
$msg = '';
$msg_type = 'success';

// CSRF verification for all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
}

// ─── Add Service ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $dept_slug = sanitize_input($_POST['dept_slug'] ?? '');
    $new_dept = sanitize_input($_POST['new_dept'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');

    // Handle new department
    if ($dept_slug === '__new__' && !empty($new_dept)) {
        $slug = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', mb_strtolower($new_dept, 'UTF-8')));
        if (empty($slug)) $slug = 'dept_' . time();
        $stmt = $conn->prepare("INSERT IGNORE INTO departments (slug, name, sort_order) VALUES (?, ?, 99)");
        $stmt->bind_param("ss", $slug, $new_dept);
        $stmt->execute();
        $dept_slug = $slug;
    }

    if (empty($name)) {
        $msg = '❌ الرجاء إدخال اسم الخدمة';
        $msg_type = 'error';
    } else {
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/services/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image = 'uploads/services/' . $filename;
                }
            }
        }
        $stmt = $conn->prepare("INSERT INTO services (department_slug, name, description, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $dept_slug, $name, $desc, $image);
        if ($stmt->execute()) {
            $msg = '✅ تم إضافة الخدمة بنجاح';
        } else {
            $msg = '❌ فشل في إضافة الخدمة: ' . $conn->error;
            $msg_type = 'error';
        }
    }
}

// ─── Edit Service ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $id = (int)($_POST['service_id'] ?? 0);
    $dept_slug = sanitize_input($_POST['dept_slug'] ?? '');
    $new_dept = sanitize_input($_POST['new_dept'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');
    $remove_image = isset($_POST['remove_image']);

    if ($dept_slug === '__new__' && !empty($new_dept)) {
        $slug = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', mb_strtolower($new_dept, 'UTF-8')));
        if (empty($slug)) $slug = 'dept_' . time();
        $stmt = $conn->prepare("INSERT IGNORE INTO departments (slug, name, sort_order) VALUES (?, ?, 99)");
        $stmt->bind_param("ss", $slug, $new_dept);
        $stmt->execute();
        $dept_slug = $slug;
    }

    if ($id && !empty($name)) {
        $image = null;
        if ($remove_image) {
            $image = null;
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/services/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image = 'uploads/services/' . $filename;
                }
            }
        } else {
            // Keep existing image
            $existing = $conn->query("SELECT image FROM services WHERE id = $id");
            if ($existing && $row = $existing->fetch_assoc()) {
                $image = $row['image'];
            }
        }

        $stmt = $conn->prepare("UPDATE services SET department_slug=?, name=?, description=?, image=? WHERE id=?");
        $stmt->bind_param("ssssi", $dept_slug, $name, $desc, $image, $id);
        if ($stmt->execute()) {
            $msg = '✅ تم تعديل الخدمة بنجاح';
        } else {
            $msg = '❌ فشل في تعديل الخدمة: ' . $conn->error;
            $msg_type = 'error';
        }
    } else {
        $msg = '❌ بيانات غير صالحة';
        $msg_type = 'error';
    }
}

// ─── Delete Service ───
if (isset($_GET['delete_service'])) {
    $id = (int)$_GET['delete_service'];
    $stmt = $conn->prepare("UPDATE services SET visible = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $msg = '✅ تم حذف الخدمة';
    header("Location: dashboard.php?page=manage_services&msg=" . urlencode($msg));
    exit();
}

// ─── Toggle Department Visibility ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_department_visibility'])) {
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    if ($dept_id > 0) {
        $stmt = $conn->prepare("UPDATE departments SET visible = 1 - visible WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $msg = '✅ تم تحديث حالة ظهور القسم بنجاح';
        header("Location: dashboard.php?page=manage_departments&msg=" . urlencode($msg));
        exit();
    }
}

// ─── Add Department ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department_btn'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '');
    $icon = '';
    $desc = sanitize_input($_POST['description'] ?? '');
    
    // Process slug: lowercase, replace spaces with underscores, alphanumeric only
    $slug = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', mb_strtolower($slug, 'UTF-8')));
    if (empty($slug)) {
        $slug = 'dept_' . time();
    }
    
    if (empty($name)) {
        $msg = '❌ الرجاء إدخال اسم القسم';
        $msg_type = 'error';
    } else {
        // Check if slug is unique
        $chk = $conn->prepare("SELECT id FROM departments WHERE slug = ?");
        $chk->bind_param("s", $slug);
        $chk->execute();
        $chk_res = $chk->get_result();
        if ($chk_res && $chk_res->num_rows > 0) {
            $msg = '❌ هذا الرابط الفريد (Slug) مستخدم بالفعل لقسم آخر. الرجاء استخدام رابط مختلف.';
            $msg_type = 'error';
        } else {
            // Calculate sort_order
            $sort_res = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS max_sort FROM departments");
            $sort_row = $sort_res->fetch_assoc();
            $sort_order = (int)$sort_row['max_sort'];
            
            $stmt = $conn->prepare("INSERT INTO departments (slug, name, icon, description, sort_order, visible) VALUES (?, ?, ?, ?, ?, 1)");
            if (!$stmt) {
                $msg = '❌ خطأ في قاعدة البيانات: ' . $conn->error;
                $msg_type = 'error';
            } else {
                $stmt->bind_param("sssss", $slug, $name, $icon, $desc, $sort_order);
                if ($stmt->execute()) {
                    $msg = '✅ تم إضافة القسم بنجاح';
                    header("Location: dashboard.php?page=manage_departments&msg=" . urlencode($msg));
                    exit();
                } else {
                    $msg = '❌ فشل في إضافة القسم: ' . $conn->error;
                    $msg_type = 'error';
                }
            }
        }
    }
}

// ─── Edit Department ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_department_btn'])) {
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $visible = isset($_POST['visible']) ? 1 : 0;
    
    if ($dept_id > 0 && !empty($name)) {
        $stmt = $conn->prepare("UPDATE departments SET name = ?, description = ?, sort_order = ?, visible = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssiii", $name, $desc, $sort_order, $visible, $dept_id);
            if ($stmt->execute()) {
                $msg = '✅ تم تعديل القسم بنجاح';
                header("Location: dashboard.php?page=manage_departments&msg=" . urlencode($msg));
                exit();
            } else {
                $msg = '❌ فشل في تعديل القسم: ' . $conn->error;
                $msg_type = 'error';
            }
        } else {
            $msg = '❌ خطأ في قاعدة البيانات: ' . $conn->error;
            $msg_type = 'error';
        }
    } else {
        $msg = '❌ بيانات غير صالحة';
        $msg_type = 'error';
    }
}

// ─── Delete Department ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_department_btn'])) {
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    if ($dept_id > 0) {
        $stmt = $conn->prepare("SELECT slug, name FROM departments WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $dept = $res->fetch_assoc()) {
            $slug = $dept['slug'];
            $name = $dept['name'];
            
            $svc_check = $conn->prepare("SELECT COUNT(*) AS c FROM services WHERE department_slug = ? AND visible = 1");
            $svc_check->bind_param("s", $slug);
            $svc_check->execute();
            $svc_res = $svc_check->get_result()->fetch_assoc();
            
            if ($svc_res['c'] > 0) {
                $msg = "❌ لا يمكن حذف القسم '{$name}' لوجود {$svc_res['c']} خدمة نشطة مرتبطة به. يرجى نقل أو حذف الخدمات أولاً.";
                $msg_type = 'error';
            } else {
                $del = $conn->prepare("DELETE FROM departments WHERE id = ?");
                $del->bind_param("i", $dept_id);
                if ($del->execute()) {
                    $msg = "✅ تم حذف القسم '{$name}' بنجاح.";
                } else {
                    $msg = "❌ فشل في حذف القسم: " . $conn->error;
                    $msg_type = 'error';
                }
            }
        } else {
            $msg = '❌ القسم غير موجود.';
            $msg_type = 'error';
        }
        header("Location: dashboard.php?page=manage_departments&msg=" . urlencode($msg) . "&msg_type=" . ($msg_type ?? 'success'));
        exit();
    }
}

// ─── Reorder Department ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder_department'])) {
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    $direction = $_POST['direction'] ?? '';
    if ($dept_id > 0 && ($direction === 'up' || $direction === 'down')) {
        // Get current sort_order
        $stmt = $conn->prepare("SELECT sort_order FROM departments WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $dept = $res->fetch_assoc()) {
            $current_sort = (int)$dept['sort_order'];
            
            // Find neighbor
            if ($direction === 'up') {
                $n_stmt = $conn->prepare("SELECT id, sort_order FROM departments WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
            } else {
                $n_stmt = $conn->prepare("SELECT id, sort_order FROM departments WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
            }
            $n_stmt->bind_param("i", $current_sort);
            $n_stmt->execute();
            $n_res = $n_stmt->get_result();
            if ($n_res && $neighbor = $n_res->fetch_assoc()) {
                $neighbor_id = (int)$neighbor['id'];
                $neighbor_sort = (int)$neighbor['sort_order'];
                
                $conn->begin_transaction();
                try {
                    $u_stmt = $conn->prepare("UPDATE departments SET sort_order = ? WHERE id = ?");
                    $u_stmt->bind_param("ii", $neighbor_sort, $dept_id);
                    $u_stmt->execute();
                    
                    $u_stmt->bind_param("ii", $current_sort, $neighbor_id);
                    $u_stmt->execute();
                    
                    $conn->commit();
                    $msg = '✅ تم إعادة ترتيب الأقسام بنجاح';
                } catch (Exception $e) {
                    $conn->rollback();
                    $msg = '❌ فشل في إعادة ترتيب الأقسام: ' . $e->getMessage();
                    $msg_type = 'error';
                }
            } else {
                $msg = 'ℹ️ القسم بالفعل في نهاية أو بداية الترتيب';
            }
        }
        header("Location: dashboard.php?page=manage_departments&msg=" . urlencode($msg) . "&msg_type=" . ($msg_type ?? 'success'));
        exit();
    }
}

// ─── Change Booking Status ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_booking_status'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $new_status = sanitize_input($_POST['new_status'] ?? '');
    $valid_statuses = ['pending', 'confirmed', 'cancelled'];
    if ($booking_id > 0 && in_array($new_status, $valid_statuses, true)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        $stmt->execute();
        $status_labels = ['pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي'];
        $msg = '✅ تم تحديث الحالة إلى: ' . $status_labels[$new_status];
        $redirect_page = $new_status === 'cancelled' ? 'cancelled_bookings' : 'manage_bookings';
        header("Location: dashboard.php?page={$redirect_page}&msg=" . urlencode($msg));
        exit();
    }
}

// ─── Add Offer ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    $category = sanitize_input($_POST['category'] ?? '');
    $new_cat = sanitize_input($_POST['new_category'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $starting = isset($_POST['starting']) ? 1 : 0;

    if (!empty($new_cat)) $category = $new_cat;

    if (empty($name) || $price <= 0) {
        $msg = '❌ الرجاء إدخال اسم السعر صحيح';
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO offers (category, name, price, `starting`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $category, $name, $price, $starting);
        if ($stmt->execute()) {
            $msg = '✅ تم إضافة العرض بنجاح';
        } else {
            $msg = '❌ فشل في إضافة العرض: ' . $conn->error;
            $msg_type = 'error';
        }
    }
}

// ─── Edit Offer ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_offer'])) {
    $id = (int)($_POST['offer_id'] ?? 0);
    $category = sanitize_input($_POST['category'] ?? '');
    $new_cat = sanitize_input($_POST['new_category'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $starting = isset($_POST['starting']) ? 1 : 0;

    if (!empty($new_cat)) $category = $new_cat;

    if ($id && !empty($name) && $price > 0) {
        $stmt = $conn->prepare("UPDATE offers SET category=?, name=?, price=?, `starting`=? WHERE id=?");
        $stmt->bind_param("ssdii", $category, $name, $price, $starting, $id);
        $stmt->execute();
        $msg = '✅ تم تعديل العرض بنجاح';
    }
}

// ─── Delete Offer ───
if (isset($_GET['delete_offer'])) {
    $id = (int)$_GET['delete_offer'];
    $stmt = $conn->prepare("UPDATE offers SET visible = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $msg = '✅ تم حذف العرض';
    header("Location: dashboard.php?page=manage_offers&msg=" . urlencode($msg));
    exit();
}

// ─── Save Department Schedule ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dept_schedule'])) {
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    $gender = sanitize_input($_POST['gender'] ?? '');
    $start_time = sanitize_input($_POST['start_time'] ?? '19:00');
    $end_time = sanitize_input($_POST['end_time'] ?? '23:00');

    if (!in_array($gender, ['male', 'female'], true)) {
        $msg = '❌ الجنس غير صالح';
        $msg_type = 'error';
    } elseif ($dept_id <= 0) {
        $msg = '❌ القسم غير صالح';
        $msg_type = 'error';
    } else {
        $working_days = isset($_POST['working_days']) && is_array($_POST['working_days'])
            ? json_encode(array_map('sanitize_input', $_POST['working_days']), JSON_UNESCAPED_UNICODE)
            : json_encode([]);

        $stmt = $conn->prepare("INSERT INTO department_schedules (department_id, gender, working_days, start_time, end_time) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE working_days = VALUES(working_days), start_time = VALUES(start_time), end_time = VALUES(end_time)");
        if ($stmt) {
            $stmt->bind_param('issss', $dept_id, $gender, $working_days, $start_time, $end_time);
            if ($stmt->execute()) {
                $gender_label = ($gender === 'male') ? 'الرجال' : 'السيدات';
                $msg = "✅ تم حفظ جدول مواعيد {$gender_label} بنجاح";
            } else {
                $msg = '❌ فشل في حفظ الجدول: ' . $conn->error;
                $msg_type = 'error';
            }
            $stmt->close();
        } else {
            $msg = '❌ خطأ في قاعدة البيانات: ' . $conn->error;
            $msg_type = 'error';
        }
        header("Location: dashboard.php?page=manage_schedules&msg=" . urlencode($msg));
        exit();
    }
}

// Get message from redirect or session (manage_admin.php)
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}
if (isset($_GET['msg_type'])) {
    $msg_type = preg_replace('/[^a-z]/', '', $_GET['msg_type']);
}
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
if (isset($_SESSION['msg_type'])) {
    $msg_type = preg_replace('/[^a-z]/', '', $_SESSION['msg_type']);
    unset($_SESSION['msg_type']);
}

// ─── Stats data (customer + bookings UNION) ───
$totalQuery = $conn->query("SELECT SUM(c) AS total FROM (SELECT COUNT(*) AS c FROM customer UNION ALL SELECT COUNT(*) AS c FROM bookings WHERE status IN ('pending','confirmed')) t");
$totalBookings = $totalQuery ? (int)$totalQuery->fetch_assoc()['total'] : 0;
$maleQuery = $conn->query("SELECT SUM(c) AS males FROM (SELECT COUNT(*) AS c FROM customer WHERE gender='ذكر' UNION ALL SELECT COUNT(*) AS c FROM bookings WHERE gender='ذكر') t");
$totalMales = $maleQuery ? (int)$maleQuery->fetch_assoc()['males'] : 0;
$femaleQuery = $conn->query("SELECT SUM(c) AS females FROM (SELECT COUNT(*) AS c FROM customer WHERE gender='أنثى' UNION ALL SELECT COUNT(*) AS c FROM bookings WHERE gender='أنثى') t");
$totalFemales = $femaleQuery ? (int)$femaleQuery->fetch_assoc()['females'] : 0;
$totalServices = $conn->query("SELECT COUNT(*) AS c FROM services WHERE visible=1")->fetch_assoc()['c'] ?? 0;
$totalOffers = $conn->query("SELECT COUNT(*) AS c FROM offers WHERE visible=1")->fetch_assoc()['c'] ?? 0;

$totalVisits = 0;
if ($role === 'superadmin' && function_exists('get_visit_counts')) {
    $vc = get_visit_counts();
    $totalVisits = (int) array_sum($vc);
}

// ── Booking Status Stats ───
$pendingQuery = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status='pending'");
$totalPending = $pendingQuery ? (int)$pendingQuery->fetch_assoc()['c'] : 0;

$confirmedQuery = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status='confirmed'");
$totalConfirmed = $confirmedQuery ? (int)$confirmedQuery->fetch_assoc()['c'] : 0;

$cancelledQuery = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status='cancelled'");
$totalCancelled = $cancelledQuery ? (int)$cancelledQuery->fetch_assoc()['c'] : 0;

// Charts data (customer + bookings UNION)
$chartData = [];
$chartResult = $conn->query("SELECT booking_date, SUM(c) AS count FROM (SELECT booking_date, COUNT(*) AS c FROM customer GROUP BY booking_date UNION ALL SELECT booking_date, COUNT(*) AS c FROM bookings GROUP BY booking_date) t GROUP BY booking_date ORDER BY booking_date ASC LIMIT 30");
if ($chartResult) {
    while ($row = $chartResult->fetch_assoc()) {
        $chartData[] = $row;
    }
}

$slotData = [];
$slotResult = $conn->query("SELECT time_slot, SUM(c) AS total FROM (SELECT time_slot COLLATE utf8mb4_unicode_ci AS time_slot, COUNT(*) AS c FROM customer GROUP BY time_slot COLLATE utf8mb4_unicode_ci UNION ALL SELECT booking_time COLLATE utf8mb4_unicode_ci AS time_slot, COUNT(*) AS c FROM bookings GROUP BY booking_time) t GROUP BY time_slot ORDER BY time_slot ASC");
if ($slotResult) {
    while ($row = $slotResult->fetch_assoc()) {
        $slotData[] = $row;
    }
}

// Get departments for dropdowns
$depts = $conn->query("SELECT slug, name FROM departments WHERE visible=1 ORDER BY sort_order ASC");
$dept_list = [];
while ($d = $depts->fetch_assoc()) {
    $dept_list[] = $d;
}

// Get unique offer categories
$offer_cats = $conn->query("SELECT DISTINCT category FROM offers WHERE visible=1 ORDER BY category ASC");
$offer_cat_list = [];
while ($c = $offer_cats->fetch_assoc()) {
    $offer_cat_list[] = $c['category'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="icon" type="image/svg+xml" href="../assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - Brèagha Clinic</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://breaghaclinic.com/dashboard.php">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
    </style>
</head>
<body>

    <!-- Sidebar Toggle (mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ═══ Sidebar ═══ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Brèagha</h2>
            <p>لوحة التحكم</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?page=home" class="<?= $page === 'home' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> الرئيسية</a></li>
            <?php if ($role === 'superadmin'): ?>
            <li class="sidebar-divider"></li>
            <li><a href="dashboard.php?page=manage_services" class="<?= $page === 'manage_services' ? 'active' : '' ?>"><i class="fas fa-concierge-bell"></i> إدارة الخدمات</a></li>
            <li><a href="dashboard.php?page=add_service" class="<?= $page === 'add_service' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> إضافة خدمة</a></li>
            <li><a href="dashboard.php?page=manage_departments" class="<?= $page === 'manage_departments' ? 'active' : '' ?>"><i class="fas fa-folder"></i> إدارة الأقسام</a></li>
            <li class="sidebar-divider"></li>
            <li><a href="dashboard.php?page=manage_offers" class="<?= $page === 'manage_offers' ? 'active' : '' ?>"><i class="fas fa-tags"></i> إدارة العروض</a></li>
            <li><a href="dashboard.php?page=add_offer" class="<?= $page === 'add_offer' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> إضافة عرض</a></li>
            <li class="sidebar-divider"></li>
            <li><a href="dashboard.php?page=manage_bookings" class="<?= $page === 'manage_bookings' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> إدارة الحجوزات</a></li>
            <li><a href="dashboard.php?page=cancelled_bookings" class="<?= $page === 'cancelled_bookings' ? 'active' : '' ?>"><i class="fas fa-ban"></i> الحجوزات الملغاة</a></li>
            <li class="sidebar-divider"></li>
            <li><a href="export_bookings.php" target="_blank"><i class="fas fa-file-excel"></i> تصدير جميع الحجوزات</a></li>
            <li class="sidebar-divider"></li>
            <li><a href="dashboard.php?page=manage_schedules" class="<?= $page === 'manage_schedules' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> إدارة المواعيد</a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <a href="../index.php"><i class="fas fa-globe"></i> العودة للموقع</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            <div style="margin-top:8px; font-size:0.8rem; color:rgba(255,255,255,0.3); text-align:center;">
                <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?> (<?= $role === 'superadmin' ? 'مدير عام' : 'أدمن' ?>)
            </div>
        </div>
    </aside>

    <!-- ═══ Main Content ═══ -->
    <main class="main-content">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>">
                <i class="fas fa-<?= $msg_type === 'error' ? 'times-circle' : 'check-circle' ?>"></i>
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($page === 'home'): ?>
            <!-- ═══ HOME PAGE ═══ -->
            <div class="page-header">
                <h1>مرحباً، <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?> <small>نظرة عامة على العيادة</small></h1>
            </div>

            <div class="stats-grid">
                <?php if ($role === 'superadmin'): ?>
                <div class="stat-card">
                    <span class="stat-icon">👁️</span>
                    <span class="stat-value"><?= $totalVisits ?></span>
                    <span class="stat-label">إجمالي الزيارات</span>
                </div>
                <?php endif; ?>
                <div class="stat-card">
                    <span class="stat-icon">📋</span>
                    <span class="stat-value"><?= $totalBookings ?></span>
                    <span class="stat-label">إجمالي الحجوزات</span>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🧑</span>
                    <span class="stat-value"><?= $totalMales ?></span>
                    <span class="stat-label">الذكور</span>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">👩</span>
                    <span class="stat-value"><?= $totalFemales ?></span>
                    <span class="stat-label">الإناث</span>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">💼</span>
                    <span class="stat-value"><?= $totalServices ?></span>
                    <span class="stat-label">الخدمات</span>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🏷️</span>
                    <span class="stat-value"><?= $totalOffers ?></span>
                    <span class="stat-label">العروض</span>
                </div>
            </div>

            <?php if (!empty($chartData)): ?>
            <div class="charts-wrapper">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line" style="color:var(--primary)"></i> الحجوزات (آخر 30 يوم)</h3>
                    <canvas id="bookingsChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><i class="fas fa-clock" style="color:var(--primary)"></i> الفترات الزمنية</h3>
                    <canvas id="slotsChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter by day -->
            <div class="section-card">
                <h2><i class="fas fa-search" style="color:var(--primary)"></i> عرض الحجوزات حسب اليوم</h2>
                <div class="filter-section">
                    <form method="GET" action="" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <input type="hidden" name="page" value="home">
                        <label for="day">اختر اليوم:</label>
                        <input type="date" name="day" id="day" class="form-control" style="width:auto;flex:1;min-width:180px;" required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> عرض</button>
                    </form>
                </div>
                <?php if (isset($_GET['day'])): 
                    $day_raw = $_GET['day'];
                    $day = sanitize_input($day_raw);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day_raw)) {
                        $stmt_day = $conn->prepare(
                            "SELECT name, phone, service, gender, time_slot, booking_type, price FROM (
                                SELECT name COLLATE utf8mb4_unicode_ci AS name, phone COLLATE utf8mb4_unicode_ci AS phone, service COLLATE utf8mb4_unicode_ci AS service, gender COLLATE utf8mb4_unicode_ci AS gender, time_slot COLLATE utf8mb4_unicode_ci AS time_slot, booking_type COLLATE utf8mb4_unicode_ci AS booking_type, price FROM customer WHERE booking_date = ?
                                UNION ALL
                                SELECT full_name AS name, phone, service, COALESCE(gender,'') AS gender, booking_time AS time_slot, booking_type AS booking_type, price FROM bookings WHERE booking_date = ?
                            ) t ORDER BY time_slot ASC"
                        );
                        if ($stmt_day) {
                            $stmt_day->bind_param('ss', $day_raw, $day_raw);
                            $stmt_day->execute();
                            $res = $stmt_day->get_result();
                        } else {
                            $res = null;
                        }
                    } else {
                        $res = null;
                    }
                ?>
                    <h3 style="margin-top:16px;color:var(--text);font-size:1rem;">
                        <i class="fas fa-calendar-day"></i> حجوزات <?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>
                        (<?= $res && $res->num_rows ? $res->num_rows : 0 ?>)
                    </h3>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <div class="table-wrap" style="margin-top:12px;">
                            <table>
                                <thead>
                                    <tr><th>الاسم</th><th>الهاتف</th><th>الخدمة</th><th>النوع</th><th>الوقت</th></tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td dir="ltr" style="text-align:right"><?= htmlspecialchars($r['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($r['service'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php if (($r['booking_type'] ?? '') === 'offer'): ?>
                                                    <span class="offer-badge" style="background: rgba(195, 158, 49, 0.15); color: #C39E31; border: 1px solid #C39E31; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 5px; font-weight: bold; display: inline-block;">
                                                        عرض <?= $r['price'] !== null ? htmlspecialchars((float)$r['price'], ENT_QUOTES, 'UTF-8') . ' ج.م' : '' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($r['gender'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($r['time_slot'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:12px;">
                            <a href="print_bookings.php?day=<?= urlencode($day) ?>" target="_blank" class="btn btn-secondary">
                                <i class="fas fa-print"></i> طباعة تقرير هذا اليوم
                            </a>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-light);margin-top:12px;">لا توجد حجوزات في هذا اليوم</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Upcoming bookings -->
            <div class="section-card">
                <h2><i class="fas fa-calendar-check" style="color:var(--primary)"></i> الحجوزات القادمة</h2>
                <?php
                $today = date("Y-m-d");
                $upcoming = $conn->query("SELECT booking_date, SUM(c) AS total FROM (SELECT booking_date, COUNT(*) AS c FROM customer WHERE booking_date >= '$today' GROUP BY booking_date UNION ALL SELECT booking_date, COUNT(*) AS c FROM bookings WHERE booking_date >= '$today' GROUP BY booking_date) t GROUP BY booking_date ORDER BY booking_date ASC LIMIT 30");
                if ($upcoming && $upcoming->num_rows > 0): ?>
                    <ul class="list-items">
                        <?php while ($row = $upcoming->fetch_assoc()): ?>
                            <li>
                                <strong><?= htmlspecialchars($row['booking_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>عدد الحجوزات: <?= htmlspecialchars($row['total'], ENT_QUOTES, 'UTF-8') ?></span>
                                <a href="?page=home&day=<?= urlencode($row['booking_date']) ?>" class="btn btn-sm btn-secondary">عرض</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color:var(--text-light);">لا توجد حجوزات في الأيام القادمة</p>
                <?php endif; ?>
            </div>

            <!-- Admin management (superadmin only) -->
            <?php if ($role === 'superadmin'): ?>
            <div class="section-card">
                <h2><i class="fas fa-user-shield" style="color:var(--primary)"></i> إدارة الأدمن</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                    <form method="POST" action="manage_admin.php" style="background:#f9f9f9;padding:16px;border-radius:8px;">
                        <label style="display:block;margin-bottom:8px;font-weight:600;">إضافة أدمن جديد</label>
                        <input type="text" name="new_admin_name" class="form-control" placeholder="اسم الأدمن" required style="margin-bottom:8px;">
                        <input type="password" name="new_admin_pass" class="form-control" placeholder="كلمة المرور" required style="margin-bottom:8px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" name="addAdmin" class="btn btn-primary"><i class="fas fa-user-plus"></i> إضافة</button>
                    </form>
                    <form method="POST" action="manage_admin.php" style="background:#f9f9f9;padding:16px;border-radius:8px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <label style="display:block;margin-bottom:8px;font-weight:600;">حذف أدمن</label>
                        <select name="delete_admin" class="form-control" required style="margin-bottom:8px;">
                            <option value="">-- اختر --</option>
                            <?php $admins = $conn->query("SELECT admin_name FROM admins");
                            while ($a = $admins->fetch_assoc()): ?>
                                <option><?= htmlspecialchars($a['admin_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" name="deleteAdmin" class="btn btn-danger"><i class="fas fa-user-minus"></i> حذف</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($page === 'manage_departments'): ?>
            <!-- ═══ MANAGE DEPARTMENTS ══ -->
            <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h1>إدارة الأقسام <small>الأقسام المتاحة في الموقع</small></h1>
                <a href="dashboard.php?page=add_department" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة قسم جديد</a>
            </div>
            <?php
            $departments = $conn->query("SELECT * FROM departments ORDER BY sort_order ASC");
            if ($departments && $departments->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الترتيب</th>
                                <th>الاسم</th>
                                <th>Slug</th>
                                <th>الحالة</th>
                                <th>الخدمات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($dept = $departments->fetch_assoc()): 
                                $svc_count = $conn->query("SELECT COUNT(*) AS c FROM services WHERE department_slug = '{$dept['slug']}' AND visible = 1")->fetch_assoc()['c'];
                            ?>
                                <tr style="<?= !$dept['visible'] ? 'opacity:0.55;' : '' ?>">
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <div style="display:flex;gap:4px;">
                                            <form method="POST" action="dashboard.php?page=manage_departments" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                                <input type="hidden" name="direction" value="up">
                                                <button type="submit" name="reorder_department" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;" title="تحريك لأعلى">
                                                    <i class="fas fa-chevron-up"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="dashboard.php?page=manage_departments" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                                <input type="hidden" name="direction" value="down">
                                                <button type="submit" name="reorder_department" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;" title="تحريك لأسفل">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td><strong><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><code><?= htmlspecialchars($dept['slug'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td>
                                        <?php if ($dept['visible']): ?>
                                            <span style="background:rgba(39,174,96,0.15);color:#2cc16d;padding:4px 8px;border-radius:12px;font-size:12px;font-weight:bold;"><i class="fas fa-eye"></i> ظاهر</span>
                                        <?php else: ?>
                                            <span style="background:rgba(231,76,60,0.15);color:#e74c3c;padding:4px 8px;border-radius:12px;font-size:12px;font-weight:bold;"><i class="fas fa-eye-slash"></i> مخفي</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge" style="background:var(--bg-accent);color:var(--text-main);padding:3px 8px;border-radius:8px;font-size:12px;"><?= $svc_count ?> خدمة</span></td>
                                    <td>
                                        <div class="actions-group">
                                            <a href="dashboard.php?page=edit_department&id=<?= $dept['id'] ?>" class="btn btn-sm btn-primary" style="padding:4px 8px;"><i class="fas fa-edit"></i> تعديل</a>
                                            <form method="POST" action="dashboard.php?page=manage_departments" style="display:inline;" onsubmit="return confirm('<?= $dept['visible'] ? 'هل تريد إخفاء هذا القسم؟ سيتم إخفاؤه من الموقع.' : 'هل تريد إظهار هذا القسم؟ سيتم عرضه في الموقع.' ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                                <button type="submit" name="toggle_department_visibility" class="btn btn-sm <?= $dept['visible'] ? 'btn-warning' : 'btn-success' ?>" style="padding:4px 8px;">
                                                    <i class="fas <?= $dept['visible'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i> <?= $dept['visible'] ? 'إخفاء' : 'إظهار' ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="dashboard.php?page=manage_departments" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا القسم نهائياً؟ لا يمكن التراجع عن هذا الإجراء.');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                                <button type="submit" name="delete_department_btn" class="btn btn-sm btn-danger" style="padding:4px 8px;">
                                                    <i class="fas fa-trash-alt"></i> حذف
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:var(--text-light);">لا توجد أقسام مضافة حالياً.</p>
            <?php endif; ?>

        <?php elseif ($page === 'add_department'): ?>
            <!-- ═══ ADD DEPARTMENT ═══ -->
            <div class="page-header">
                <h1>إضافة قسم جديد <small>أضف تصنيفاً جديداً للخدمات</small></h1>
                <a href="dashboard.php?page=manage_departments" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
            </div>
                <div class="section-card">
                <form method="POST" action="dashboard.php?page=add_department" style="max-width:800px;" onsubmit="return validateSlug();">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>اسم القسم (بالعربية) <span class="req">*</span></label>
                            <input type="text" name="name" id="dept_name" class="form-control" placeholder="مثال: الأسنان، الجلدية" required oninput="generateSlug(this.value)">
                        </div>
                        <div class="form-group">
                            <label>الرابط الفريد (Slug) <span class="req">*</span></label>
                            <input type="text" name="slug" id="dept_slug" class="form-control" placeholder="مثال: dental, dermatology" required>
                            <div class="form-hint">يُستخدم في روابط الموقع وقاعدة البيانات (أحرف إنجليزية وأرقام وشرطة سفلية فقط)</div>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>وصف القسم</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="اكتب وصفاً موجزاً عن هذا القسم والخدمات التي يقدمها..."></textarea>
                        </div>
                    </div>
                    <div style="margin-top:20px;">
                        <button type="submit" name="add_department_btn" class="btn btn-primary"><i class="fas fa-save"></i> حفظ القسم</button>
                        <a href="dashboard.php?page=manage_departments" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
            
            <script>
            function generateSlug(text) {
                var slug = text.toLowerCase()
                               .replace(/\s+/g, '_')
                               .replace(/[^a-z0-9_]/g, '');
                if (slug.trim() !== '') {
                    document.getElementById('dept_slug').value = slug;
                }
            }
            function validateSlug() {
                var slugInput = document.getElementById('dept_slug').value;
                var cleanSlug = slugInput.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
                if (cleanSlug !== slugInput) {
                    alert('الرابط الفريد (Slug) يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة سفلية فقط وبدون مسافات.');
                    document.getElementById('dept_slug').value = cleanSlug;
                    return false;
                }
                return true;
            }
            </script>

        <?php elseif ($page === 'edit_department'): ?>
            <!-- ═══ EDIT DEPARTMENT ═══ -->
            <?php
            $dept_id = (int)($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $dept = $res->fetch_assoc()):
            ?>
                <div class="page-header">
                    <h1>تعديل قسم: <?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?> <small>تعديل بيانات التصنيف</small></h1>
                    <a href="dashboard.php?page=manage_departments" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
                </div>
                <div class="section-card">
                    <form method="POST" action="dashboard.php?page=manage_departments" style="max-width:800px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>اسم القسم (بالعربية) <span class="req">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>الرابط الفريد (Slug) <span class="req">*</span></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($dept['slug'], ENT_QUOTES, 'UTF-8') ?>" disabled style="background-color:rgba(255,255,255,0.08);cursor:not-allowed;">
                                <div class="form-hint" style="color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> الرابط الفريد غير قابل للتعديل للحفاظ على سلامة الحجوزات والخدمات المرتبطة به.</div>
                            </div>
                            <div class="form-group">
                                <label>ترتيب العرض (الوزن) <span class="req">*</span></label>
                                <input type="number" name="sort_order" class="form-control" value="<?= (int)$dept['sort_order'] ?>" required>
                                <div class="form-hint">أرقام أصغر تظهر أولاً (مثال: 1 يظهر قبل 2)</div>
                            </div>
                            <div class="form-group" style="grid-column: span 2; display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                                <input type="checkbox" name="visible" id="dept_visible" value="1" <?= $dept['visible'] ? 'checked' : '' ?> style="width:20px;height:20px;cursor:pointer;">
                                <label for="dept_visible" style="cursor:pointer;font-weight:bold;margin-bottom:0;">تفعيل ظهور القسم في شاشة الحجز والموقع الإلكتروني</label>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label>وصف القسم</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($dept['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                        </div>
                        <div style="margin-top:20px;">
                            <button type="submit" name="edit_department_btn" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                            <a href="dashboard.php?page=manage_departments" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-times-circle"></i> القسم المطلوب غير موجود.
                </div>
            <?php endif; ?>

        <?php elseif ($page === 'manage_bookings'): ?>
            <!-- ═══ MANAGE BOOKINGS ═══ -->
            <div class="page-header">
                <h1>إدارة الحجوزات <small>جميع الحجوزات الواردة</small></h1>
                <div style="display:flex;gap:10px;">
                    <a href="dashboard.php?page=manage_bookings&sort=oldest" class="btn btn-secondary <?= (!isset($_GET['sort']) || $_GET['sort'] === 'oldest') ? 'active' : '' ?>"><i class="fas fa-sort-amount-down"></i> الأقدم أولاً</a>
                    <a href="dashboard.php?page=manage_bookings&sort=newest" class="btn btn-secondary <?= (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'active' : '' ?>"><i class="fas fa-sort-amount-up"></i> الأحدث أولاً</a>
                </div>
            </div>

            <!-- Status Stats Cards -->
            <div class="stats-grid" style="margin-bottom:20px;">
                <div class="stat-card" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                    <span class="stat-icon">⏳</span>
                    <span class="stat-value"><?= $totalPending ?></span>
                    <span class="stat-label">قيد المراجعة</span>
                </div>
                <div class="stat-card" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">
                    <span class="stat-icon">✅</span>
                    <span class="stat-value"><?= $totalConfirmed ?></span>
                    <span class="stat-label">مؤكدة</span>
                </div>
                <div class="stat-card" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">
                    <span class="stat-icon">❌</span>
                    <span class="stat-value"><?= $totalCancelled ?></span>
                    <span class="stat-label">ملغاة</span>
                </div>
            </div>

            <?php
            $sort_order = isset($_GET['sort']) && $_GET['sort'] === 'newest' ? 'DESC' : 'ASC';
            $bookings_query = $conn->prepare("SELECT * FROM bookings ORDER BY created_at {$sort_order}");
            $bookings_query->execute();
            $bookings_result = $bookings_query->get_result();
            ?>

            <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الحجز</th>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>القسم</th>
                                <th>الخدمة</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($b = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="#"><?= $i++ ?></td>
                                    <td data-label="رقم الحجز"><code><?= htmlspecialchars($b['booking_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td data-label="الاسم"><?= htmlspecialchars($b['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الهاتف"><?= htmlspecialchars($b['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="القسم"><?= htmlspecialchars($b['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الخدمة">
                                        <?= htmlspecialchars($b['service'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (($b['booking_type'] ?? '') === 'offer'): ?>
                                            <span class="offer-badge" style="background: rgba(195, 158, 49, 0.15); color: #C39E31; border: 1px solid #C39E31; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 5px; font-weight: bold; display: inline-block;">
                                                عرض <?= $b['price'] !== null ? htmlspecialchars((float)$b['price'], ENT_QUOTES, 'UTF-8') . ' ج.م' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="التاريخ"><?= htmlspecialchars($b['booking_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الوقت"><?= htmlspecialchars($b['booking_time'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الحالة">
                                        <?php
                                        $status_colors = ['pending' => '#f39c12', 'confirmed' => '#27ae60', 'cancelled' => '#e74c3c'];
                                        $status_labels = ['pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي'];
                                        $sc = $status_colors[$b['status']] ?? '#999';
                                        $sl = $status_labels[$b['status']] ?? $b['status'];
                                        ?>
                                        <span class="status-badge" style="color:<?= $sc ?>;font-weight:600;"><?= $sl ?></span>
                                    </td>
                                    <td data-label="إجراءات" class="actions-cell">
                                        <a href="dashboard.php?page=view_booking&id=<?= $b['id'] ?>" class="btn btn-sm btn-secondary" title="عرض الفاتورة"><i class="fas fa-file-invoice"></i></a>
                                        <?php $wa_phone = '20' . preg_replace('/[^0-9]/', '', $b['phone']); ?>
                                        <a href="https://wa.me/<?= $wa_phone ?>?text=<?= urlencode('مرحباً ' . $b['full_name'] . '، بخصوص حجزك رقم ' . $b['booking_id'] . ' في عيادة براجيا') ?>" target="_blank" class="btn btn-sm btn-success" title="تواصل عبر واتساب"><i class="fab fa-whatsapp"></i></a>
                                        <select class="status-select" data-booking-id="<?= $b['id'] ?>" style="padding:4px 8px;border-radius:4px;border:1px solid #ddd;font-size:12px;">
                                            <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>قيد المراجعة</option>
                                            <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>مؤكد</option>
                                            <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:var(--text-light);text-align:center;padding:40px;">لا توجد حجوزات حالياً</p>
            <?php endif; ?>

        <?php elseif ($page === 'view_booking'): ?>
            <!-- ═══ VIEW BOOKING (INVOICE) ═══ -->
            <?php
            $view_id = (int)($_GET['id'] ?? 0);
            $view_stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
            $view_stmt->bind_param("i", $view_id);
            $view_stmt->execute();
            $view_result = $view_stmt->get_result();
            $booking = $view_result->fetch_assoc();
            ?>
            <?php if ($booking): ?>
                <div class="page-header no-print">
                    <h1>فاتورة الحجز <small><?= htmlspecialchars($booking['booking_id'], ENT_QUOTES, 'UTF-8') ?></small></h1>
                    <div style="display:flex;gap:10px;">
                        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> طباعة</button>
                        <a href="dashboard.php?page=manage_bookings" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
                    </div>
                </div>

                <div class="invoice-card">
                    <!-- Header -->
                    <div class="invoice-header">
                        <h1>Brèagha Clinic</h1>
                        <p>عيادة براجيا للتجميل والجلدية</p>
                        <p style="font-size:14px; opacity: 0.85;">فاتورة حجز موعد</p>
                    </div>

                    <!-- Booking Info -->
                    <div class="invoice-grid">
                        <div>
                            <h3 class="invoice-section-title">معلومات الحجز</h3>
                            <p><strong>رقم الحجز:</strong> <?= htmlspecialchars($booking['booking_id'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>تاريخ الحجز:</strong> <?= htmlspecialchars($booking['booking_date'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>وقت الحجز:</strong> <?= htmlspecialchars($booking['booking_time'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>تاريخ الإنشاء:</strong> <?= htmlspecialchars($booking['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <h3 class="invoice-section-title">معلومات العميل</h3>
                            <p><strong>الاسم:</strong> <?= htmlspecialchars($booking['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>العمر:</strong> <?= htmlspecialchars($booking['age'], ENT_QUOTES, 'UTF-8') ?> سنة</p>
                            <p><strong>النوع:</strong> <?= htmlspecialchars($booking['gender'] ?: '-', ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>الهاتف:</strong> <?= htmlspecialchars($booking['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <!-- Service Details -->
                    <div class="invoice-info-block">
                        <h3 class="invoice-section-title">تفاصيل الخدمة</h3>
                        <div class="invoice-details-grid">
                            <div>
                                <p><strong>القسم:</strong> <?= htmlspecialchars($booking['department'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p><strong>نوع الحجز:</strong> <?= ($booking['booking_type'] ?? '') === 'offer' ? 'عرض خاص' : 'خدمة عادية' ?></p>
                                <p><strong>الخدمة/العرض:</strong> <?= htmlspecialchars($booking['service'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div>
                                <?php if ($booking['sessions']): ?>
                                    <p><strong>عدد الجلسات:</strong> <?= htmlspecialchars($booking['sessions'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if ($booking['price'] !== null): ?>
                                    <p><strong>السعر:</strong> <?= htmlspecialchars((float)$booking['price'], ENT_QUOTES, 'UTF-8') ?> ج.م</p>
                                <?php endif; ?>
                                <p><strong>الحالة:</strong> 
                                    <?php
                                    $status_labels = ['pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي'];
                                    $status_colors = ['pending' => '#f39c12', 'confirmed' => '#27ae60', 'cancelled' => '#e74c3c'];
                                    $st = $booking['status'];
                                    ?>
                                    <span style="color:<?= $status_colors[$st] ?? '#999' ?>;font-weight:600;"><?= $status_labels[$st] ?? $st ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="invoice-footer">
                        <p>شكراً لاختياركم عيادة براجيا</p>
                        <p>للإستفسار: 01108343753</p>
                    </div>
                </div>

                <style>
                    @media print {
                        .no-print { display: none !important; }
                        .invoice-card { box-shadow: none !important; padding: 20px !important; border: none !important; }
                        body { background: #fff !important; }
                    }
                </style>
            <?php else: ?>
                <p style="color:red;text-align:center;">الحجز غير موجود</p>
            <?php endif; ?>

        <?php elseif ($page === 'cancelled_bookings'): ?>
            <!-- ═══ CANCELLED BOOKINGS ═══ -->
            <div class="page-header">
                <h1>الحجوزات الملغاة <small>جميع الحجوزات التي تم إلغاؤها</small></h1>
                <a href="dashboard.php?page=manage_bookings" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع لإدارة الحجوزات</a>
            </div>

            <?php
            $cancelled_query = $conn->prepare("SELECT * FROM bookings WHERE status = 'cancelled' ORDER BY created_at DESC");
            $cancelled_query->execute();
            $cancelled_result = $cancelled_query->get_result();
            ?>

            <?php if ($cancelled_result && $cancelled_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الحجز</th>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>القسم</th>
                                <th>الخدمة</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>تاريخ الإلغاء</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($b = $cancelled_result->fetch_assoc()): ?>
                                <tr style="background:#fff5f5;">
                                    <td data-label="#"><?= $i++ ?></td>
                                    <td data-label="رقم الحجز"><code><?= htmlspecialchars($b['booking_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td data-label="الاسم"><?= htmlspecialchars($b['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الهاتف"><?= htmlspecialchars($b['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="القسم"><?= htmlspecialchars($b['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الخدمة">
                                        <?= htmlspecialchars($b['service'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (($b['booking_type'] ?? '') === 'offer'): ?>
                                            <span class="offer-badge" style="background: rgba(195, 158, 49, 0.15); color: #C39E31; border: 1px solid #C39E31; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 5px; font-weight: bold; display: inline-block;">
                                                عرض <?= $b['price'] !== null ? htmlspecialchars((float)$b['price'], ENT_QUOTES, 'UTF-8') . ' ج.م' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="التاريخ"><?= htmlspecialchars($b['booking_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="الوقت"><?= htmlspecialchars($b['booking_time'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="تاريخ الإلغاء"><?= htmlspecialchars($b['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="إجراءات" class="actions-cell">
                                        <?php $wa_phone2 = '20' . preg_replace('/[^0-9]/', '', $b['phone']); ?>
                                        <a href="https://wa.me/<?= $wa_phone2 ?>?text=<?= urlencode('مرحباً ' . $b['full_name'] . '، بخصوص حجزك الملغي رقم ' . $b['booking_id'] . ' في عيادة براجيا') ?>" target="_blank" class="btn btn-sm btn-success" title="تواصل عبر واتساب">
                                            <i class="fab fa-whatsapp"></i> واتساب
                                        </a>
                                        <a href="dashboard.php?page=view_booking&id=<?= $b['id'] ?>" class="btn btn-sm btn-secondary" title="عرض الفاتورة">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:var(--text-light);text-align:center;padding:40px;">لا توجد حجوزات ملغاة</p>
            <?php endif; ?>

        <?php elseif ($page === 'manage_services'): ?>
            <!-- ═══ MANAGE SERVICES ═══ -->
            <div class="page-header">
                <h1>إدارة الخدمات <small>جميع الخدمات المتاحة</small></h1>
                <a href="dashboard.php?page=add_service" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة خدمة</a>
            </div>
            <?php
            $services = $conn->query("SELECT s.*, d.name AS dept_name, d.icon FROM services s LEFT JOIN departments d ON s.department_slug = d.slug WHERE s.visible = 1 ORDER BY d.sort_order ASC, s.id ASC");
            if ($services && $services->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>القسم</th>
                                <th>الاسم</th>
                                <th>الصورة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($s = $services->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($s['dept_name'] ?? $s['department_slug'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($s['image']): ?>
                                            <img src="../<?= htmlspecialchars($s['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:60px;height:45px;object-fit:cover;border-radius:6px;">
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="dashboard.php?page=edit_service&id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> تعديل</a>
                                        <a href="dashboard.php?delete_service=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الخدمة؟')"><i class="fas fa-trash"></i> حذف</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>لا توجد خدمات مضافة بعد</p>
                    <a href="dashboard.php?page=add_service" class="btn btn-primary" style="margin-top:12px;">إضافة خدمة</a>
                </div>
            <?php endif; ?>

        <?php elseif ($page === 'add_service'): ?>
            <!-- ═══ ADD SERVICE ═══ -->
            <div class="page-header">
                <h1>إضافة خدمة جديدة <small>أضف خدمة جديدة للعيادة</small></h1>
                <a href="dashboard.php?page=manage_services" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
            </div>
            <div class="section-card">
                <form method="POST" action="dashboard.php?page=add_service" enctype="multipart/form-data" style="max-width:800px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>القسم <span class="req">*</span></label>
                            <select name="dept_slug" class="form-control" required onchange="toggleNewDept(this)">
                                <option value="">-- اختر القسم --</option>
                                <?php foreach ($dept_list as $d): ?>
                                    <option value="<?= htmlspecialchars($d['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <option value="__new__">➕ إضافة قسم جديد...</option>
                            </select>
                        </div>
                        <div class="form-group" id="newDeptGroup" style="display:none;">
                            <label>اسم القسم الجديد</label>
                            <input type="text" name="new_dept" class="form-control" placeholder="مثال: قسم جديد">
                        </div>
                        <div class="form-group">
                            <label>اسم الخدمة <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="اسم الخدمة" required>
                        </div>
                        <div class="form-group">
                            <label>صورة الخدمة</label>
                            <input type="file" name="image" class="form-control-file" accept="image/*">
                            <div class="form-hint">اختياري. الصورة ستظهر في صفحة الخدمات</div>
                        </div>
                        <div class="form-group full-width">
                            <label>وصف الخدمة</label>
                            <textarea name="description" class="form-control" placeholder="وصف مختصر للخدمة"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_service" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الخدمة</button>
                        <a href="dashboard.php?page=manage_services" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>

            <script>
            function toggleNewDept(sel) {
                document.getElementById('newDeptGroup').style.display = sel.value === '__new__' ? 'block' : 'none';
            }
            </script>

        <?php elseif ($page === 'edit_service'): 
            $edit_id = (int)($_GET['id'] ?? 0);
            $edit_serv = $conn->query("SELECT * FROM services WHERE id = $edit_id AND visible = 1");
            if ($edit_serv && $edit_serv->num_rows > 0):
                $es = $edit_serv->fetch_assoc();
        ?>
            <!-- ═══ EDIT SERVICE ═══ -->
            <div class="page-header">
                <h1>تعديل الخدمة <small><?= htmlspecialchars($es['name'], ENT_QUOTES, 'UTF-8') ?></small></h1>
                <a href="dashboard.php?page=manage_services" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
            </div>
            <div class="section-card">
                <form method="POST" action="dashboard.php?page=edit_service" enctype="multipart/form-data" style="max-width:800px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="service_id" value="<?= $es['id'] ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>القسم <span class="req">*</span></label>
                            <select name="dept_slug" class="form-control" required onchange="toggleNewDept2(this)">
                                <?php foreach ($dept_list as $d): ?>
                                    <option value="<?= htmlspecialchars($d['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= $d['slug'] === $es['department_slug'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__new__">➕ إضافة قسم جديد...</option>
                            </select>
                        </div>
                        <div class="form-group" id="newDeptGroup2" style="display:none;">
                            <label>اسم القسم الجديد</label>
                            <input type="text" name="new_dept" class="form-control" placeholder="مثال: قسم جديد">
                        </div>
                        <div class="form-group">
                            <label>اسم الخدمة <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($es['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>صورة الخدمة</label>
                            <input type="file" name="image" class="form-control-file" accept="image/*">
                            <?php if ($es['image']): ?>
                                <div class="current-img">
                                    <img src="../<?= htmlspecialchars($es['image'], ENT_QUOTES, 'UTF-8') ?>" class="img-preview" alt="current">
                                    <label style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;cursor:pointer;">
                                        <input type="checkbox" name="remove_image" value="1"> إزالة الصورة
                                    </label>
</div>

            <div style="text-align:center;margin:20px 0;">
                <a href="export_bookings.php" class="btn btn-success" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;font-size:15px;border-radius:8px;">
                    <i class="fas fa-file-excel"></i> تصدير جميع الحجوزات لـ Excel
                </a>
            </div>
                            <?php endif; ?>
                            <div class="form-hint">اتركه فارغاً للاحتفاظ بالصورة الحالية</div>
                        </div>
                        <div class="form-group full-width">
                            <label>وصف الخدمة</label>
                            <textarea name="description" class="form-control"><?= htmlspecialchars($es['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_service" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                        <a href="dashboard.php?page=manage_services" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
            <script>
            function toggleNewDept2(sel) {
                document.getElementById('newDeptGroup2').style.display = sel.value === '__new__' ? 'block' : 'none';
            }
            </script>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>الخدمة غير موجودة</p></div>
        <?php endif; ?>

        <?php elseif ($page === 'manage_offers'): ?>
            <!-- ═══ MANAGE OFFERS ═══ -->
            <div class="page-header">
                <h1>إدارة العروض <small>جميع العروض المتاحة</small></h1>
                <a href="dashboard.php?page=add_offer" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة عرض</a>
            </div>
            <?php
            $offers = $conn->query("SELECT * FROM offers WHERE visible = 1 ORDER BY category ASC, id ASC");
            if ($offers && $offers->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>القسم</th>
                                <th>الاسم</th>
                                <th>السعر</th>
                                <th>سعر يبدأ من</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($o = $offers->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($o['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format($o['price'], 0) ?> ج.م</td>
                                    <td><?= $o['starting'] ? '✅' : '—' ?></td>
                                    <td class="actions-cell">
                                        <a href="dashboard.php?page=edit_offer&id=<?= $o['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> تعديل</a>
                                        <a href="dashboard.php?delete_offer=<?= $o['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا العرض؟')"><i class="fas fa-trash"></i> حذف</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <p>لا توجد عروض مضافة بعد</p>
                    <a href="dashboard.php?page=add_offer" class="btn btn-primary" style="margin-top:12px;">إضافة عرض</a>
                </div>
            <?php endif; ?>

        <?php elseif ($page === 'add_offer'): ?>
            <!-- ═══ ADD OFFER ═══ -->
            <div class="page-header">
                <h1>إضافة عرض جديد <small>أضف عرضاً جديداً</small></h1>
                <a href="dashboard.php?page=manage_offers" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
            </div>
            <div class="section-card">
                <form method="POST" action="dashboard.php?page=add_offer" style="max-width:600px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>القسم <span class="req">*</span></label>
                            <select name="category" class="form-control" required onchange="toggleNewOfferCat(this)">
                                <option value="">-- اختر القسم --</option>
                                <?php foreach ($offer_cat_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <option value="__new__">➕ إضافة قسم جديد...</option>
                            </select>
                        </div>
                        <div class="form-group" id="newOfferCatGroup" style="display:none;">
                            <label>اسم القسم الجديد</label>
                            <input type="text" name="new_category" class="form-control" placeholder="مثال: قسم جديد">
                        </div>
                        <div class="form-group">
                            <label>اسم العرض <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="اسم العرض" required>
                        </div>
                        <div class="form-group">
                            <label>السعر (ج.م) <span class="req">*</span></label>
                            <input type="number" name="price" class="form-control" placeholder="مثال: 1000" min="1" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="starting" value="1">
                                سعر يبدأ من (ابتداءً من)
                            </label>
                            <div class="form-hint">فعّل هذا إذا كان السعر ابتدائياً</div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_offer" class="btn btn-primary"><i class="fas fa-save"></i> حفظ العرض</button>
                        <a href="dashboard.php?page=manage_offers" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
            <script>
            function toggleNewOfferCat(sel) {
                document.getElementById('newOfferCatGroup').style.display = sel.value === '__new__' ? 'block' : 'none';
            }
            </script>

        <?php elseif ($page === 'edit_offer'):
            $edit_offer_id = (int)($_GET['id'] ?? 0);
            $edit_offer = $conn->query("SELECT * FROM offers WHERE id = $edit_offer_id AND visible = 1");
            if ($edit_offer && $edit_offer->num_rows > 0):
                $eo = $edit_offer->fetch_assoc();
        ?>
            <!-- ═══ EDIT OFFER ═══ -->
            <div class="page-header">
                <h1>تعديل العرض <small><?= htmlspecialchars($eo['name'], ENT_QUOTES, 'UTF-8') ?></small></h1>
                <a href="dashboard.php?page=manage_offers" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> رجوع</a>
            </div>
            <div class="section-card">
                <form method="POST" action="dashboard.php?page=edit_offer" style="max-width:600px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="offer_id" value="<?= $eo['id'] ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>القسم <span class="req">*</span></label>
                            <select name="category" class="form-control" required onchange="toggleNewOfferCat2(this)">
                                <?php foreach ($offer_cat_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $cat === $eo['category'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__new__">➕ إضافة قسم جديد...</option>
                            </select>
                        </div>
                        <div class="form-group" id="newOfferCatGroup2" style="display:none;">
                            <label>اسم القسم الجديد</label>
                            <input type="text" name="new_category" class="form-control" placeholder="مثال: قسم جديد">
                        </div>
                        <div class="form-group">
                            <label>اسم العرض <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($eo['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>السعر (ج.م) <span class="req">*</span></label>
                            <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($eo['price'], ENT_QUOTES, 'UTF-8') ?>" min="1" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="starting" value="1" <?= $eo['starting'] ? 'checked' : '' ?>>
                                سعر يبدأ من (ابتداءً من)
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_offer" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                        <a href="dashboard.php?page=manage_offers" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
            <script>
            function toggleNewOfferCat2(sel) {
                document.getElementById('newOfferCatGroup2').style.display = sel.value === '__new__' ? 'block' : 'none';
            }
            </script>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>العرض غير موجود</p></div>
        <?php endif; ?>

        <?php elseif ($page === 'manage_schedules'): ?>
            <!-- ═══ MANAGE SCHEDULES ═══ -->
            <div class="page-header">
                <h1>إدارة المواعيد <small>تحديد جداول العمل لكل قسم (السيدات والرجال)</small></h1>
            </div>
            <?php
            $all_days_map = ['saturday'=>'السبت','sunday'=>'الأحد','monday'=>'الإثنين','tuesday'=>'الثلاثاء','wednesday'=>'الأربعاء','thursday'=>'الخميس','friday'=>'الجمعة'];
            $departments = $conn->query("SELECT id, slug, name FROM departments WHERE visible = 1 ORDER BY sort_order ASC");
            if ($departments && $departments->num_rows > 0):
                while ($dept = $departments->fetch_assoc()):
                    $stmt_f = $conn->prepare("SELECT working_days, start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = 'female'");
                    $sched_female = ['working_days'=>[], 'start_time'=>'19:00', 'end_time'=>'23:00'];
                    if ($stmt_f) {
                        $stmt_f->bind_param('i', $dept['id']);
                        $stmt_f->execute();
                        $row_f = $stmt_f->get_result()->fetch_assoc();
                        if ($row_f) {
                            $sched_female['working_days'] = json_decode($row_f['working_days'], true) ?: [];
                            $sched_female['start_time'] = substr($row_f['start_time'], 0, 5);
                            $sched_female['end_time'] = substr($row_f['end_time'], 0, 5);
                        }
                        $stmt_f->close();
                    }
                    $stmt_m = $conn->prepare("SELECT working_days, start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = 'male'");
                    $sched_male = ['working_days'=>[], 'start_time'=>'19:00', 'end_time'=>'23:00'];
                    if ($stmt_m) {
                        $stmt_m->bind_param('i', $dept['id']);
                        $stmt_m->execute();
                        $row_m = $stmt_m->get_result()->fetch_assoc();
                        if ($row_m) {
                            $sched_male['working_days'] = json_decode($row_m['working_days'], true) ?: [];
                            $sched_male['start_time'] = substr($row_m['start_time'], 0, 5);
                            $sched_male['end_time'] = substr($row_m['end_time'], 0, 5);
                        }
                        $stmt_m->close();
                    }
            ?>
            <div class="schedule-dept-card">
                <h3 class="schedule-dept-title">
                    <i class="fas fa-folder" style="color:var(--primary);"></i>
                    <?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>
                    <code><?= htmlspecialchars($dept['slug'], ENT_QUOTES, 'UTF-8') ?></code>
                </h3>
                <div class="schedule-gender-grid">
                    <!-- Female Schedule -->
                    <div class="schedule-gender-card female">
                        <h4><i class="fas fa-venus"></i> مواعيد السيدات</h4>
                        <form method="POST" action="dashboard.php?page=manage_schedules">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                            <input type="hidden" name="gender" value="female">
                            <div>
                                <label class="schedule-days-label">أيام العمل:</label>
                                <div class="schedule-days-grid">
                                    <?php foreach ($all_days_map as $key => $label):
                                        $checked = in_array($key, $sched_female['working_days']) ? 'checked' : '';
                                    ?>
                                    <label class="schedule-day-chk <?= $checked ? 'active-female' : '' ?>">
                                        <input type="checkbox" name="working_days[]" value="<?= $key ?>" <?= $checked ?>>
                                        <?= $label ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="schedule-time-grid">
                                <div>
                                    <label>وقت البداية:</label>
                                    <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($sched_female['start_time'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div>
                                    <label>وقت النهاية:</label>
                                    <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($sched_female['end_time'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="save_dept_schedule" class="schedule-save-btn female"><i class="fas fa-save"></i> حفظ</button>
                        </form>
                    </div>
                    <!-- Male Schedule -->
                    <div class="schedule-gender-card male">
                        <h4><i class="fas fa-mars"></i> مواعيد الرجال</h4>
                        <form method="POST" action="dashboard.php?page=manage_schedules">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                            <input type="hidden" name="gender" value="male">
                            <div>
                                <label class="schedule-days-label">أيام العمل:</label>
                                <div class="schedule-days-grid">
                                    <?php foreach ($all_days_map as $key => $label):
                                        $checked = in_array($key, $sched_male['working_days']) ? 'checked' : '';
                                    ?>
                                    <label class="schedule-day-chk <?= $checked ? 'active-male' : '' ?>">
                                        <input type="checkbox" name="working_days[]" value="<?= $key ?>" <?= $checked ?>>
                                        <?= $label ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="schedule-time-grid">
                                <div>
                                    <label>وقت البداية:</label>
                                    <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($sched_male['start_time'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div>
                                    <label>وقت النهاية:</label>
                                    <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($sched_male['end_time'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="save_dept_schedule" class="schedule-save-btn male"><i class="fas fa-save"></i> حفظ</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <p style="color:var(--text-light);">لا توجد أقسام مضافة حالياً.</p>
            <?php endif; ?>

        <?php endif; ?><!-- end page routing -->

    </main>

    <script>
    // ─── Charts (home only) ───
    <?php if ($page === 'home' && !empty($chartData)): ?>
    const chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
    const labels = chartData.map(d => d.booking_date);
    const data = chartData.map(d => d.count);

    const ctx1 = document.getElementById('bookingsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'عدد الحجوزات',
                data: data,
                borderColor: '#C39E31',
                backgroundColor: 'rgba(195,158,49,0.2)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#C39E31',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }
            }
        }
    });

    const slotData = <?= json_encode($slotData, JSON_UNESCAPED_UNICODE) ?>;
    const slotLabels = slotData.map(d => d.time_slot);
    const slotCounts = slotData.map(d => d.total);

    const ctx2 = document.getElementById('slotsChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: slotLabels,
            datasets: [{
                data: slotCounts,
                backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8BC34A','#E91E63'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 11 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }
            }
        }
    });
    <?php endif; ?>

    // ─── Sidebar Toggle ───
    function toggleSidebar() {
        const isOpen = document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
        document.body.classList.toggle('sidebar-open', isOpen);
    }

    // ─── Dynamic Responsive Table Headers ───
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("table").forEach(function(table) {
            const headers = [];
            table.querySelectorAll("thead th").forEach(function(th) {
                headers.push(th.textContent.trim());
            });
            table.querySelectorAll("tbody tr").forEach(function(tr) {
                tr.querySelectorAll("td").forEach(function(td, index) {
                    if (headers[index]) {
                        td.setAttribute("data-label", headers[index]);
                    }
                });
            });
        });
    });

    // ─── Instant Status Change (AJAX) ───
    document.querySelectorAll('.status-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var bookingId = this.dataset.bookingId;
            var newStatus = this.value;
            var selectEl = this;
            var row = selectEl.closest('tr');
            var statusCell = row.querySelector('.status-badge');

            var statusMap = {
                'pending': { label: 'قيد المراجعة', color: '#f39c12' },
                'confirmed': { label: 'مؤكد', color: '#27ae60' },
                'cancelled': { label: 'ملغي', color: '#e74c3c' }
            };

            fetch('dashboard.php?page=manage_bookings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>&booking_id=' + bookingId + '&new_status=' + newStatus + '&change_booking_status=1'
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                if (statusMap[newStatus]) {
                    statusCell.textContent = statusMap[newStatus].label;
                    statusCell.style.color = statusMap[newStatus].color;
                }
                selectEl.style.borderColor = '#27ae60';
                showToast('✅ تم تحديث الحالة إلى: ' + statusMap[newStatus].label);
                setTimeout(function() { selectEl.style.borderColor = '#ddd'; }, 1500);
            })
            .catch(function() {
                showToast('❌ حدث خطأ أثناء تحديث الحالة', true);
            });
        });
    });

    // ─── Toast Notification ───
    function showToast(msg, isError) {
        var toast = document.createElement('div');
        toast.textContent = msg;
        toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);padding:12px 24px;border-radius:8px;color:#fff;font-size:14px;font-weight:600;z-index:9999;animation:toastIn 0.3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        toast.style.background = isError ? '#e74c3c' : '#27ae60';
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }
    </script>

</body>
</html>
