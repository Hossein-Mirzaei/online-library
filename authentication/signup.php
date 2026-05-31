<?php
include '../database/config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/admin_panel.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$total_books = 0;
$total_users = 0;
$total_categories = 0;

if (!$conn->connect_error) {
    // تعداد کتاب‌ها
    $result = $conn->query("SELECT COUNT(*) as count FROM books");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_books = $row['count'];
    }
    
    // تعداد کاربران
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_users = $row['count'];
    }
    
    // تعداد دسته‌بندی‌ها - اصلاح شده
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_categories = $row['count'];
    }
}

$error = '';
$success = '';
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['first_name'] = trim($_POST['first_name'] ?? '');
    $form_data['last_name'] = trim($_POST['last_name'] ?? '');
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['address'] = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // اعتبارسنجی نام
    if (empty($form_data['first_name'])) {
        $errors[] = 'نام را وارد کنید.';
    } elseif (strlen($form_data['first_name']) < 2) {
        $errors[] = 'نام باید حداقل ۲ کاراکتر باشد.';
    }
    
    // اعتبارسنجی نام خانوادگی
    if (empty($form_data['last_name'])) {
        $errors[] = 'نام خانوادگی را وارد کنید.';
    } elseif (strlen($form_data['last_name']) < 2) {
        $errors[] = 'نام خانوادگی باید حداقل ۲ کاراکتر باشد.';
    }
    
    // اعتبارسنجی نام کاربری
    if (empty($form_data['username'])) {
        $errors[] = 'نام کاربری را وارد کنید.';
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = 'نام کاربری باید حداقل ۳ کاراکتر باشد.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، اعداد و زیرخط باشد.';
    }
    
    // اعتبارسنجی ایمیل
    if (empty($form_data['email'])) {
        $errors[] = 'ایمیل را وارد کنید.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل معتبر نیست.';
    }
    
    // اعتبارسنجی رمز عبور
    if (empty($password)) {
        $errors[] = 'رمز عبور را وارد کنید.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    }
    
    // بررسی تطابق رمز عبور
    if ($password !== $confirm_password) {
        $errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
    }
    
    // بررسی تکراری نبودن نام کاربری و ایمیل
    if (empty($errors)) {
        if ($conn->connect_error) {
            $errors[] = 'خطا در اتصال به دیتابیس.';
        } else {
            $check_sql = "SELECT username, email FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if ($check_stmt) {
                $check_stmt->bind_param('ss', $form_data['username'], $form_data['email']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    while ($row = $check_result->fetch_assoc()) {
                        if ($row['username'] === $form_data['username']) {
                            $errors[] = 'این نام کاربری قبلاً ثبت شده است.';
                        }
                        if ($row['email'] === $form_data['email']) {
                            $errors[] = 'این ایمیل قبلاً ثبت شده است.';
                        }
                    }
                }
                $check_stmt->close();
            }
        }
        
        // ثبت کاربر جدید
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (first_name, last_name, username, email, address, password, user_type) 
                          VALUES (?, ?, ?, ?, ?, ?, 'user')";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if ($insert_stmt) {
                $insert_stmt->bind_param('ssssss', 
                    $form_data['first_name'], 
                    $form_data['last_name'], 
                    $form_data['username'], 
                    $form_data['email'], 
                    $form_data['address'], 
                    $hashed_password
                );
                
                if ($insert_stmt->execute()) {
                    $success = 'ثبت‌نام با موفقیت انجام شد. اکنون می‌توانید وارد شوید.';
                    $form_data = [
                        'first_name' => '',
                        'last_name' => '',
                        'username' => '',
                        'email' => '',
                        'address' => ''
                    ];
                } else {
                    $errors[] = 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.';
                }
                
                $insert_stmt->close();
            } else {
                $errors[] = 'خطا در آماده‌سازی درخواست.';
            }
        }
    }
    
    if (!empty($errors)) {
        $error = implode(' ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام در فروشگاه کتاب</title>
    <link rel="stylesheet" href="../static/css/signup.css">
    <style>
        
    </style>
</head>
<body>
    <div class="signup-wrapper">
        <div class="glass-card">
            
            <div class="register-form-side">
                <div class="mobile-logo">
                    <h2>📚 فروشگاه کتاب</h2>
                </div>
                
                <div class="desktop-title">
                    <h2 class="form-title">📝 ایجاد حساب کاربری</h2>
                    <p class="form-subtitle">برای ثبت‌نام اطلاعات زیر را وارد کنید</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <span>❌</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="success-message">
                    <span>✅</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-grid">
                        <div class="input-wrapper">
                            <input type="text" 
                                   id="first_name" 
                                   name="first_name" 
                                   class="input-field"
                                   placeholder="نام"
                                   value="<?php echo htmlspecialchars($form_data['first_name']); ?>"
                                   required>
                            <span class="input-icon">👤</span>
                        </div>
                        
                        <div class="input-wrapper">
                            <input type="text" 
                                   id="last_name" 
                                   name="last_name" 
                                   class="input-field"
                                   placeholder="نام خانوادگی"
                                   value="<?php echo htmlspecialchars($form_data['last_name']); ?>"
                                   required>
                            <span class="input-icon">👤</span>
                        </div>
                    </div>
                    
                    <div class="input-wrapper">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="input-field"
                               placeholder="نام کاربری"
                               value="<?php echo htmlspecialchars($form_data['username']); ?>"
                               required>
                        <span class="input-icon">👨‍💻</span>
                    </div>
                    
                    <div class="input-wrapper">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="input-field"
                               placeholder="ایمیل"
                               value="<?php echo htmlspecialchars($form_data['email']); ?>"
                               required>
                        <span class="input-icon">📧</span>
                    </div>
                    
                    <div class="input-wrapper">
                        <input type="text" 
                               id="address" 
                               name="address" 
                               class="input-field"
                               placeholder="آدرس (اختیاری)"
                               value="<?php echo htmlspecialchars($form_data['address']); ?>">
                        <span class="input-icon">📍</span>
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
                    
                    <div class="input-wrapper">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="input-field"
                               placeholder="تکرار رمز عبور"
                               required>
                        <span class="input-icon">🔐</span>
                    </div>
                    
                    <button type="submit" class="btn-register" id="submitBtn">
                        <span>ثبت‌نام</span>
                        <span>✨</span>
                    </button>
                </form>
                
                <div class="divider">
                    <div class="divider-line"></div>
                    <div class="divider-text">
                        <span>قبلاً ثبت‌نام کرده‌اید؟</span>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="login.php" class="login-link">
                        <span>ورود به حساب کاربری</span>
                        <span>🔑</span>
                    </a>
                </div>
                
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
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
    
    <script src="../static/js/signup.js"></script>
</body>
</html>