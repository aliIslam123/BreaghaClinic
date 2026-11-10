<?php
header('Content-Type: text/html; charset=utf-8');

http_response_code(404);

$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($host, 'localhost') !== false);
$base_url = $is_local ? '/Breagha project' : '';
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
  <title>404 - الصفحة غير موجودة | Brèagha Clinic</title>
  <meta name="description" content="عذراً، الصفحة التي تبحث عنها غير موجودة. يمكنك العودة إلى الصفحة الرئيسية.">
  <meta name="robots" content="noindex, nofollow">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Cairo', sans-serif;
      background: #faf8f3;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
      padding: 20px;
    }
    .error-wrap {
      max-width: 500px;
      background: #fff;
      border: 2px solid #e8d49a;
      border-radius: 20px;
      padding: 50px 30px;
      box-shadow: 0 10px 40px rgba(195,158,49,0.1);
    }
    .error-code {
      font-size: 100px;
      font-weight: 800;
      color: #C39E31;
      line-height: 1;
      margin-bottom: 10px;
    }
    h1 {
      font-size: 1.4rem;
      color: #333;
      margin-bottom: 15px;
    }
    p {
      color: #666;
      font-size: 1rem;
      margin-bottom: 25px;
      line-height: 1.7;
    }
    .btn-home {
      display: inline-block;
      background: #C39E31;
      color: #fff;
      padding: 12px 35px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 700;
      font-size: 1rem;
      transition: background 0.3s;
    }
    .btn-home:hover { background: #a38227; }
    .logo img { max-width: 120px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="logo">
      <img src="imgs/Br%C3%A8agha%20-%20Logomark.svg" alt="Brèagha Clinic">
    </div>
    <div class="error-code">404</div>
    <h1>عذراً، الصفحة غير موجودة</h1>
    <p>الصفحة التي تبحث عنها قد تكون انتقلت أو تم إزالتها. يمكنك العودة إلى الصفحة الرئيسية لتصفح خدماتنا.</p>
    <a href="<?= $base_url ?>/" class="btn-home">الرجوع إلى الرئيسية</a>
  </div>
</body>
</html>
