<?php
require_once '../config/init.php';

// إزالة جميع بيانات الجلسة
$_SESSION = [];

// حذف كوكي الجلسة من المتصفح
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// تدمير الجلسة على الخادم
session_unset();
session_destroy();

header("Location: login.php?logged_out=1");
exit;
