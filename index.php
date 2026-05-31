<?php
require_once 'database/config.php';

$books_query = "SELECT COUNT(*) as total FROM books";
$books_result = $conn->query($books_query);
$books_count = $books_result->fetch_assoc()['total'];

$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = $conn->query($users_query);
$users_count = $users_result->fetch_assoc()['total'];

$categories_query = "SELECT COUNT(*) as total FROM categories";
$categories_result = $conn->query($categories_query);
$categories_count = $categories_result->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فروشگاه کتاب - کتابخانه آنلاین</title>
	<link rel="icon" type="image/x-icon" href="static/images/logo.jpg">
    <link rel="stylesheet" href="static/css/welcome.css">
</head>

<body>
    <div class="container">
        <div class="glass-card">
            <div class="logo">
                <h1>📚 فروشگاه کتاب</h1>
                <span>کتابخانه آنلاین تخصصی</span>
            </div>

            <div class="welcome-text">
                <h2>به جمع کتاب‌دوستان خوش آمدید!</h2>
                <p>
                    هزاران کتاب در زمینه‌های موسیقی، علم، داستان، تاریخ و... 
                    منتظر شما هستند
                </p>
            </div>

            <a href="user/index.php" class="enter-btn">
                <span>ورود به کتابخانه</span>
                <span>✨</span>
            </a>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">📖</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($books_count); ?></span>
                        <span class="stat-label">عنوان کتاب</span>
                        <span class="stat-desc">در دسته‌بندی‌های متنوع</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($users_count); ?></span>
                        <span class="stat-label">عضو فعال</span>
                        <span class="stat-desc">از سراسر کشور</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($categories_count); ?></span>
                        <span class="stat-label">دسته‌بندی</span>
                        <span class="stat-desc">موضوعات گوناگون</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>© ۲۰۲۶ فروشگاه کتاب | همه حقوق محفوظ است</p>
        </div>
    </div>
</body>

</html>