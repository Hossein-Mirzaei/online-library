<?php

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'owner' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: ../user/index.php");
    exit();
}

$isLoggedIn = true;
$isAdmin = true;

$page_title = isset($page_title) ? $page_title : 'پنل مدیریت';
$current_first_name = $_SESSION['first_name'] ?? 'مدیر';
$current_last_name = $_SESSION['last_name'] ?? '';
$is_owner = ($_SESSION['user_type'] === 'owner');

$pending_comments = $conn->query("SELECT COUNT(*) as count FROM comments WHERE admin_response IS NULL OR admin_response = ''")->fetch_assoc()['count'];
$unread_messages = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];

function canAccessMenuItem($page_key) {
    if (!function_exists('hasPermission')) return true;
    if ($_SESSION['user_type'] === 'owner') return true;
    return hasPermission($page_key);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> | پنل مدیریت</title>
	<link rel="icon" type="image/x-icon" href="../static/images/logo.jpg">
    <link rel="stylesheet" href="../static/css/header-admin.css">
</head>
<body>

    <a href="#" id="scrollToTop" class="scroll-top">⬆️</a>

    <header class="admin-header">
        <div class="header-container">
            
            <a href="admin_panel.php" class="admin-logo">
                <span>⚙️</span>
                <span>پنل مدیریت</span>
            </a>
            
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="منو">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer-menu" id="drawerMenu">
        
        <div class="drawer-header">
            <span class="drawer-title">پنل مدیریت</span>
            <button class="drawer-close" id="drawerClose">✕</button>
        </div>
        
        <div class="drawer-profile">
            <div class="drawer-avatar">
                <?php echo mb_substr($current_first_name, 0, 1); ?>
            </div>
            <div class="drawer-user-info">
                <span class="drawer-name"><?php echo htmlspecialchars($current_first_name . ' ' . $current_last_name); ?></span>
                <span class="drawer-role">
                    <?php echo $is_owner ? '👑 مالک سایت' : '🛡️ مدیر سیستم'; ?>
                </span>
            </div>
        </div>
        
        <div class="drawer-content">
            
            <div class="drawer-section">
                <?php $has_dashboard = canAccessMenuItem('admin_panel'); ?>
                <a href="admin_panel.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : '' ?><?= !$has_dashboard ? ' disabled-item' : '' ?>"
                   <?= !$has_dashboard ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">📊</span>
                    <span class="drawer-item-text">داشبورد</span>
                    <?php if (!$has_dashboard): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
            </div>
            
            <div class="drawer-divider"></div>
            
            <div class="drawer-section">
                <span class="drawer-section-title">مدیریت محتوا</span>
                
                <?php $has_books = canAccessMenuItem('list_book'); ?>
                <a href="list_book.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_book.php' ? 'active' : '' ?><?= !$has_books ? ' disabled-item' : '' ?>"
                   <?= !$has_books ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">📚</span>
                    <span class="drawer-item-text">کتاب‌ها</span>
                    <?php if (!$has_books): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
                
                <?php $has_categories = canAccessMenuItem('list_categories'); ?>
                <a href="list_categories.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_categories.php' ? 'active' : '' ?><?= !$has_categories ? ' disabled-item' : '' ?>"
                   <?= !$has_categories ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">📂</span>
                    <span class="drawer-item-text">دسته‌بندی‌ها</span>
                    <?php if (!$has_categories): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
            </div>
            
            <div class="drawer-divider"></div>
            
            <div class="drawer-section">
                <span class="drawer-section-title">مدیریت کاربران</span>
                
                <?php $has_users = canAccessMenuItem('list_users'); ?>
                <a href="list_users.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_users.php' ? 'active' : '' ?><?= !$has_users ? ' disabled-item' : '' ?>"
                   <?= !$has_users ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">👥</span>
                    <span class="drawer-item-text">کاربران</span>
                    <?php if (!$has_users): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
                
                <?php $has_orders = canAccessMenuItem('list_orders'); ?>
                <a href="list_orders.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_orders.php' ? 'active' : '' ?><?= !$has_orders ? ' disabled-item' : '' ?>"
                   <?= !$has_orders ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">🛒</span>
                    <span class="drawer-item-text">سفارش‌ها</span>
                    <?php if (!$has_orders): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
            </div>
            
            <?php if ($is_owner): ?>
            <div class="drawer-divider"></div>
            <div class="drawer-section">
                <span class="drawer-section-title">تنظیمات پیشرفته</span>
                <a href="manage_permissions.php" class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'manage_permissions.php' ? 'active' : '' ?>">
                    <span class="drawer-item-icon">🔑</span>
                    <span class="drawer-item-text">دسترسی ادمین‌ها</span>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="drawer-divider"></div>
            
            <div class="drawer-section">
                <span class="drawer-section-title">ارتباطات</span>
                
                <?php $has_comments = canAccessMenuItem('list_comments'); ?>
                <a href="list_comments.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_comments.php' ? 'active' : '' ?><?= !$has_comments ? ' disabled-item' : '' ?>"
                   <?= !$has_comments ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">💭</span>
                    <span class="drawer-item-text">نظرات</span>
                    <?php if ($has_comments && $pending_comments > 0): ?>
                    <span class="drawer-badge"><?= $pending_comments ?></span>
                    <?php endif; ?>
                    <?php if (!$has_comments): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
                
                <?php $has_messages = canAccessMenuItem('list_messages'); ?>
                <a href="list_messages.php" 
                   class="drawer-item <?= basename($_SERVER['PHP_SELF']) == 'list_messages.php' ? 'active' : '' ?><?= !$has_messages ? ' disabled-item' : '' ?>"
                   <?= !$has_messages ? 'onclick="return false;" title="⛔ دسترسی به این بخش ندارید"' : '' ?>>
                    <span class="drawer-item-icon">📧</span>
                    <span class="drawer-item-text">پیام‌ها</span>
                    <?php if ($has_messages && $unread_messages > 0): ?>
                    <span class="drawer-badge"><?= $unread_messages ?></span>
                    <?php endif; ?>
                    <?php if (!$has_messages): ?><span class="lock-icon">🔒</span><?php endif; ?>
                </a>
            </div>
            
            <div class="drawer-divider"></div>
            
            <div class="drawer-section">
                <a href="../user/index.php" class="drawer-item">
                    <span class="drawer-item-icon">🏪</span>
                    <span class="drawer-item-text">مشاهده فروشگاه</span>
                </a>
                <a href="../authentication/logout.php" class="drawer-item danger">
                    <span class="drawer-item-icon">🚪</span>
                    <span class="drawer-item-text">خروج از حساب</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="header-spacer"></div>

    <main class="main-content">
        <?php echo $content ?? ''; ?>
    </main>

    <footer class="admin-footer">
        <div class="footer-container">
            <p>© 2026 <strong>فروشگاه کتاب</strong> - پنل مدیریت نسخه 1.0</p>
        </div>
    </footer>

    <script src="../static/js/header-admin.js"></script>
</body>
</html>