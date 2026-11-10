<?php
require_once '../config/init.php';
include '../config/connection.php';

// تحقق CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        die('طلب غير صالح (CSRF) - توكن مفقود.');
    }
    // مقارنة آمنة مقاومة للـ timing attacks
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('طلب غير صالح (CSRF) - توكن غير صحيح.');
    }

}
// حماية: لازم يكون مسجل دخول كـ superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // إضافة أدمن جديد
    if (isset($_POST['addAdmin'])) {
        $name = trim($_POST['new_admin_name']);
        $pass =  password_hash($_POST['new_admin_pass'], PASSWORD_DEFAULT);

        if (!empty($name) && !empty($pass)) {
            $sql = "INSERT INTO admins (admin_name, admin_password) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $name, $pass);

            if ($stmt->execute()) {
                $_SESSION['msg'] = "✅ تم إضافة الأدمن ($name) بنجاح";
            } else {
                $_SESSION['msg'] = "❌ خطأ أثناء إضافة الأدمن";
            }
            $stmt->close();
        } else {
            $_SESSION['msg'] = "⚠️ يجب إدخال اسم وكلمة مرور";
        }

        header("Location: dashboard.php");
        exit();
    }

    // حذف أدمن
    if (isset($_POST['deleteAdmin'])) {
        $adminToDelete = $_POST['delete_admin'];

        $sql = "DELETE FROM admins WHERE admin_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $adminToDelete);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "🗑️ تم حذف الأدمن ($adminToDelete) بنجاح";
        } else {
            $_SESSION['msg'] = "❌ خطأ أثناء حذف الأدمن";
        }
        $stmt->close();

        header("Location: dashboard.php");
        exit();
    }
}
?>
