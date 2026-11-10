<?php
header('Content-Type: text/html; charset=utf-8');

// login.php
require_once '../config/init.php';
require_once '../config/connection.php';

$error = "";

// PRG: show session error after redirect
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        $_SESSION['login_error'] = "⚠️ طلب غير صالح (CSRF). الرجاء إعادة تحميل الصفحة والمحاولة مجددًا.";
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['login_error'] = "⚠️ طلب غير صالح (CSRF).";
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = "⚠️ الرجاء إدخال اسم المستخدم وكلمة المرور.";
        } else {

            function verify_pass($plain, $stored) {
                if ($stored === null) return false;
                if (password_verify($plain, $stored)) return true;
                if (hash_equals((string)$stored, (string)$plain)) return true;
                return false;
            }

            $found = false;

            // 1) check superadmin
            $sql = "SELECT * FROM superadmin WHERE `super-name` = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows === 1) {
                    $found = true;
                    $user = $res->fetch_assoc();
                    if (verify_pass($password, $user['super-password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user'] = $username;
                        $_SESSION['role'] = 'superadmin';
                        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                        $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $_SESSION['last_activity'] = time();
                        generate_csrf_token();
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $_SESSION['login_error'] = "❌ كلمة المرور غير صحيحة.";
                    }
                }
                $stmt->close();
            } else {
                $_SESSION['login_error'] = "خطأ داخلي، حاول لاحقًا.";
            }

            // 2) if not found in superadmin, check admins
            if (!$found && empty($_SESSION['login_error'])) {
                $sql2 = "SELECT * FROM admins WHERE `admin_name` = ?";
                if ($stmt2 = $conn->prepare($sql2)) {
                    $stmt2->bind_param("s", $username);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    if ($res2 && $res2->num_rows === 1) {
                        $found = true;
                        $user2 = $res2->fetch_assoc();
                        if (verify_pass($password, $user2['admin_password'])) {
                            session_regenerate_id(true);
                            $_SESSION['user'] = $username;
                            $_SESSION['role'] = 'admin';
                            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                            $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                            $_SESSION['last_activity'] = time();
                            generate_csrf_token();
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $_SESSION['login_error'] = "❌ كلمة المرور غير صحيحة.";
                        }
                    }
                    $stmt2->close();
                } else {
                    $_SESSION['login_error'] = "خطأ داخلي، حاول لاحقًا.";
                }
            }

            // 3) if still not found
            if (!$found && empty($_SESSION['login_error'])) {
                $_SESSION['login_error'] = "❌ اسم المستخدم غير موجود.";
            }
        }
    }

    // PRG: redirect back to login page with session error
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="icon" type="image/svg+xml" href="../assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول - لوحة تحكم Brèagha Clinic</title>
  <meta name="description" content="تسجيل الدخول إلى لوحة تحكم عيادة براجيا">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="تسجيل الدخول - Brèagha Clinic">
  <meta property="og:description" content="لوحة تحكم عيادة براجيا">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://breaghaclinic.com/login.php">
  <meta property="og:site_name" content="Brèagha Clinic">
  <meta property="og:locale" content="ar_AR">

  <!-- Canonical -->
  <link rel="canonical" href="https://breaghaclinic.com/login.php">

  <link rel="stylesheet" href="../assets/css/home-redesign.css">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-top">
                <img src="../assets/imgs/Br%C3%A8agha%20-%20Logomark.svg" alt="شعار عيادة براجيا">
                <h1>تسجيل الدخول</h1>
            </div>
    <form action="login.php" method="POST">
      <div class="form-group">
        <label for="username">اسم المستخدم</label>
        <input type="text" id="username" name="username" required placeholder="ادخل اسم المستخدم">
      </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required placeholder="ادخل كلمة المرور">
                    <button type="button" class="pw-toggle" aria-label="إظهار كلمة المرور" title="إظهار كلمة المرور">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a22.06 22.06 0 0 1 5.06-6.06"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
            </div>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
      <button type="submit" class="login-btn">دخول</button>
    </form>
    <a href="../index.php" class="extra-btn">الصفحة الرئيسية</a>
    <?php if (!empty($error)): ?>
      <p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
         </div>
    </div>
    <script>
        (function(){
            const btn = document.querySelector('.pw-toggle');
            const input = document.getElementById('password');
            if (!btn || !input) return;
            btn.addEventListener('click', function(){
                const isShown = btn.classList.toggle('show');
                if (isShown) {
                    input.type = 'text';
                    btn.setAttribute('aria-label','إخفاء كلمة المرور');
                    btn.setAttribute('title','إخفاء كلمة المرور');
                } else {
                    input.type = 'password';
                    btn.setAttribute('aria-label','إظهار كلمة المرور');
                    btn.setAttribute('title','إظهار كلمة المرور');
                }
            });
        })();
    </script>
</body>
</html>
