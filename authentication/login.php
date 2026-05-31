<?php
include '../database/config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/admin_panel.php");
    } else {
        header("Location: ../user/index.php");
    }
    exit();
}

$total_books = 0;
$total_users = 0;
$total_categories = 0;

if (!$conn->connect_error) {
    $result = $conn->query("SELECT COUNT(*) as count FROM books");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_books = $row['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_users = $row['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_categories = $row['count'];
    }
}

$error = '';
$username_or_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_or_email) || empty($password)) {
        $error = 'لطفاً نام کاربری/ایمیل و رمز عبور را وارد کنید.';
    } else {
        if ($conn->connect_error) {
            $error = 'خطا در اتصال به دیتابیس.';
        } else {
            $sql = "SELECT user_id, first_name, last_name, username, email, password, user_type, address 
                    FROM users 
                    WHERE email = ? OR username = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param('ss', $username_or_email, $username_or_email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['address'] = $user['address'];
                        
                        if ($user['user_type'] === 'admin') {
                            header("Location: ../admin/admin_panel.php");
                        } else {
                            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../user/index.php';
                            header("Location: $redirect");
                        }
                        exit();
                    } else {
                        $error = 'نام کاربری/ایمیل یا رمز عبور اشتباه است.';
                    }
                } else {
                    $error = 'نام کاربری/ایمیل یا رمز عبور اشتباه است.';
                }
                
                $stmt->close();
            } else {
                $error = 'خطا در پردازش درخواست.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به فروشگاه کتاب</title>
    <link rel="stylesheet" href="../static/css/login.css">

</head>
<body>
    <div class="login-wrapper">
        <div class="glass-card">
            
            <div class="login-form-side">
                <div class="mobile-logo">
                    <h2>📚 فروشگاه کتاب</h2>
                </div>
                
                <div class="desktop-title">
                    <h2 class="form-title">🔐 ورود به حساب</h2>
                    <p class="form-subtitle">برای دسترسی به حساب کاربری خود وارد شوید</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <span>❌</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="input-wrapper">
                        <input type="text" 
                               id="username_or_email" 
                               name="username_or_email" 
                               class="input-field"
                               placeholder="نام کاربری یا ایمیل"
                               value="<?php echo htmlspecialchars($username_or_email); ?>"
                               required>
                        <span class="input-icon">👤</span>
                    </div>
                    
                    <div class="input-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="input-field"
                               placeholder="رمز عبور"
                               required>
                        <span class="input-icon">🔒</span>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox">
                            <span class="custom-checkbox"></span>
                            <span>مرا به خاطر بسپار</span>
                        </label>
                        <a href="#" class="forgot-link">
                            <span>فراموشی رمز عبور</span>
                            <span>◀</span>
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="submitBtn">
                        <span>ورود به حساب</span>
                        <span>◀</span>
                    </button>
                </form>
                
                <div class="divider">
                    <div class="divider-line"></div>
                    <div class="divider-text">
                        <span>عضو نیستید؟</span>
                    </div>
                </div>
                
                <div class="text-center" style="margin-bottom: 1rem;">
                    <a href="signup.php" class="signup-link">
                        <span>ثبت‌نام در فروشگاه</span>
                        <span>✨</span>
                    </a>
                </div>
                
                <div class="text-center">
                    <a href="../index.php" class="home-link">
                        <span>🏠</span>
                        <span>بازگشت به صفحه اصلی</span>
                    </a>
                </div>
            </div>
            
            <div class="info-side">
                <div>
                    <h2 class="info-title">📚 فروشگاه کتاب</h2>
                    <p class="info-description">
                        بزرگترین فروشگاه آنلاین کتاب با هزاران عنوان کتاب در دسته‌بندی‌های مختلف
                    </p>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon">📖</div>
                            <div class="feature-content">
                                <h4>کتاب‌های متنوع</h4>
                                <p>از علمی تا داستانی و تاریخی</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🏷️</div>
                            <div class="feature-content">
                                <h4>بهترین قیمت‌ها</h4>
                                <p>تخفیف‌های ویژه برای اعضا</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🚚</div>
                            <div class="feature-content">
                                <h4>ارسال سریع</h4>
                                <p>تحویل در کمترین زمان</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🎧</div>
                            <div class="feature-content">
                                <h4>پشتیبانی ۲۴ ساعته</h4>
                                <p>پاسخگویی به سوالات شما</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($total_books); ?>+</div>
                            <div class="stat-label">عنوان کتاب</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($total_users); ?>+</div>
                            <div class="stat-label">کاربر فعال</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($total_categories); ?>+</div>
                            <div class="stat-label">دسته‌بندی</div>
                        </div>
                    </div>
                    
                    <div class="info-footer">
                        <p class="copyright">© ۲۰۲۵ فروشگاه کتاب</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../static/js/login.js"></script>
</body>
</html>