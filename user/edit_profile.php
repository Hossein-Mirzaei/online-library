<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header("Location: ../authentication/login.php");
    exit();
}

$message = '';
$message_type = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($first_name)) $errors[] = 'نام الزامی است.';
    if (empty($last_name)) $errors[] = 'نام خانوادگی الزامی است.';
    if (empty($email)) $errors[] = 'ایمیل الزامی است.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل معتبر نیست.';
    
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check->bind_param('si', $email, $user_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = 'این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.';
    }
    $check->close();
    
    $password_changed = false;
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'رمز عبور فعلی اشتباه است.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'رمز عبور جدید و تکرار آن مطابقت ندارند.';
        } else {
            $password_changed = true;
        }
    }
    
    if (empty($errors)) {
        if ($password_changed) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, address=?, password=? WHERE user_id=?");
            $stmt->bind_param('sssssi', $first_name, $last_name, $email, $address, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, address=? WHERE user_id=?");
            $stmt->bind_param('ssssi', $first_name, $last_name, $email, $address, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['address'] = $address;
            
            $message = 'اطلاعات با موفقیت بروزرسانی شد.';
            $message_type = 'success';
            
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['email'] = $email;
            $user['address'] = $address;
        } else {
            $message = 'خطا در بروزرسانی اطلاعات.';
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $message_type = 'error';
    }
}

$page_title = "ویرایش پروفایل";

ob_start();
?>

<link rel="stylesheet" href="../static/css/edit_profile.css">

<div class="edit-profile-container">
    <div class="form-panel">
        <h2 class="form-title">
            <span>✏️</span>
            ویرایش اطلاعات حساب کاربری
        </h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?>">
                <span><?= $message_type === 'success' ? '✅' : '❌' ?></span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="profile-form-grid">
                
                <div class="form-section">
                    <h3 class="section-title">
                        <span>👤</span>
                        اطلاعات شخصی
                    </h3>
                    
                    <div class="user-avatar-preview">
                        <div class="avatar-circle">
                            <?= mb_substr($user['first_name'] ?? 'U', 0, 1) ?>
                        </div>
                        <div class="avatar-info">
                            <div class="avatar-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                            <div class="avatar-email"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>📝</span> نام
                        </label>
                        <input type="text" name="first_name" class="form-input" value="<?= htmlspecialchars($user['first_name']) ?>" placeholder="نام خود را وارد کنید" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>📝</span> نام خانوادگی
                        </label>
                        <input type="text" name="last_name" class="form-input" value="<?= htmlspecialchars($user['last_name']) ?>" placeholder="نام خانوادگی خود را وارد کنید" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>📧</span> ایمیل
                        </label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" placeholder="example@domain.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>📍</span> آدرس
                        </label>
                        <input type="text" name="address" class="form-input" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="آدرس خود را وارد کنید (اختیاری)">
                    </div>
                </div>
            
                <div class="form-section">
                    <h3 class="section-title">
                        <span>🔐</span>
                        تغییر رمز عبور
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>🔒</span> رمز عبور فعلی
                        </label>
                        <input type="password" name="current_password" class="form-input" placeholder="••••••••">
                        <div class="password-hint">برای تغییر رمز عبور، این فیلد را پر کنید</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>🆕</span> رمز عبور جدید
                        </label>
                        <input type="password" name="new_password" class="form-input" placeholder="حداقل ۶ کاراکتر">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>✓</span> تکرار رمز عبور جدید
                        </label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="تکرار رمز عبور جدید">
                    </div>
                    
                    <div class="password-hint" style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(255,215,0,0.05); border-radius: 6px;">
                        <span>💡</span> اگر نمی‌خواهید رمز عبور تغییر کند، این بخش را خالی بگذارید.
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <span>💾</span>
                        ذخیره تغییرات
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <span>✖️</span>
                        بازگشت به داشبورد
                    </a>
                </div>
                
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>