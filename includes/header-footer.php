<?php
function isActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page == $page ? 'active' : '';
}

$isLoggedIn = $isLoggedIn ?? false;
$isAdmin = $isAdmin ?? false;
$user_id = $_SESSION['user_id'] ?? null;

include_once __DIR__ . '/../database/config.php';

$social_links = [
    'instagram' => 'https://instagram.com/Hoseiin_28',
    'telegram'   => 'https://t.me/Hoseiin_28',
    'twitter'    => '#'
];

$popular_categories = [];
$cat_query = "
    SELECT c.category_id, c.category_name, c.category_icon, c.category_slug,
           COUNT(o.order_id) as order_count
    FROM categories c
    LEFT JOIN books b ON c.category_id = b.category_id
    LEFT JOIN orders o ON b.book_id = o.book_id
    GROUP BY c.category_id
    HAVING order_count > 0
    ORDER BY order_count DESC
    LIMIT 3
";
$cat_result = $conn->query($cat_query);
while ($cat = $cat_result->fetch_assoc()) {
    $popular_categories[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>فروشگاه کتاب</title>
	<link rel="icon" type="image/x-icon" href="../static/images/logo.jpg">
    <link rel="stylesheet" href="../static/css/header-footer.css">
</head>
<body>

    <?php if ($isLoggedIn): ?>
    <div class="chat-trigger" id="chatTrigger">
        <span>💬</span>
    </div>
    <?php endif; ?>

    <?php if ($isLoggedIn): ?>
    <div class="chat-overlay" id="chatOverlay"></div>
    <div class="chat-drawer" id="chatDrawer">
        
        <div class="chat-drawer-header">
            <button class="chat-drawer-close" id="chatDrawerClose">✕</button>
            <span class="chat-header-title">مشاور هوشمند</span>
            <span class="chat-header-icon">🤖</span>
			<button class="chat-clear-btn" id="chatClearBtn" title="پاک کردن تاریخچه">
        🗑️
    </button>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">در حال بارگذاری...</div>
        </div>
        
        <div class="chat-welcome" id="chatWelcome" style="display: none;">
            <p>👋 سلام! دنبال چه کتابی می‌گردی؟</p>
            <div class="suggestion-chips">
                <span class="chip" onclick="sendSuggestion('تاریخی')">📜 تاریخی</span>
                <span class="chip" onclick="sendSuggestion('علمی')">🔬 علمی</span>
                <span class="chip" onclick="sendSuggestion('رمان')">📖 رمان</span>
                <span class="chip" onclick="sendSuggestion('روانشناسی')">🧠 روانشناسی</span>
            </div>
        </div>
        
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="سوالت رو بنویس..." autocomplete="off">
            <button class="chat-send-btn" id="chatSendBtn">📨</button>
        </div>
    </div>
    <?php endif; ?>

    <header class="main-header">
        <div class="header-container">
            <a href="/new-web/user/index.php" class="header-logo">
                <span>📖</span>
                <span>فروشگاه کتاب</span>
            </a>
            <button class="hamburger-btn" id="hamburgerBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer-menu" id="drawerMenu">
        
        <div class="drawer-header">
            <span class="drawer-title">فروشگاه کتاب</span>
            <button class="drawer-close" id="drawerClose">✕</button>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="drawer-profile">
            <div class="drawer-avatar">
                <?php echo mb_substr($_SESSION['first_name'] ?? 'ک', 0, 1); ?>
            </div>
            <div class="drawer-user-info">
                <span class="drawer-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></span>
                <span class="drawer-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="drawer-content">
            <a href="/new-web/user/index.php" class="drawer-item <?php echo isActive('index.php'); ?>">
                <span class="drawer-icon">🏠</span>
                <span>خانه</span>
            </a>
            <a href="/new-web/user/books.php" class="drawer-item <?php echo isActive('books.php'); ?>">
                <span class="drawer-icon">📚</span>
                <span>کتاب‌ها</span>
            </a>
            <a href="/new-web/user/categories.php" class="drawer-item <?php echo isActive('categories.php'); ?>">
                <span class="drawer-icon">📂</span>
                <span>دسته‌بندی‌ها</span>
            </a>
            <a href="/new-web/user/about.php" class="drawer-item <?php echo isActive('about.php'); ?>">
                <span class="drawer-icon">👥</span>
                <span>درباره ما</span>
            </a>
            
            <?php if ($isLoggedIn): ?>
            <div class="drawer-divider"></div>
            
            <a href="/new-web/user/dashboard.php" class="drawer-item <?php echo isActive('dashboard.php'); ?>">
                <span class="drawer-icon">📊</span>
                <span>داشبورد</span>
            </a>
            <a href="/new-web/user/order.php" class="drawer-item <?php echo isActive('order.php'); ?>">
                <span class="drawer-icon">🛒</span>
                <span>سبد خرید</span>
            </a>
            <a href="/new-web/user/my_orders.php" class="drawer-item">
                <span class="drawer-icon">📦</span>
                <span>سفارشات من</span>
            </a>
            <a href="/new-web/user/my_comments.php" class="drawer-item">
                <span class="drawer-icon">💬</span>
                <span>نظرات من</span>
            </a>
            <?php endif; ?>
            
            <?php if ($isAdmin || (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner')): ?>
            <div class="drawer-divider"></div>
            <a href="/new-web/admin/admin_panel.php" class="drawer-item admin-item">
                <span class="drawer-icon">⚙️</span>
                <span>پنل مدیریت</span>
            </a>
            <?php endif; ?>
            
            <div class="drawer-divider"></div>
            
            <?php if ($isLoggedIn): ?>
            <a href="/new-web/authentication/logout.php" class="drawer-item danger">
                <span class="drawer-icon">🚪</span>
                <span>خروج از حساب</span>
            </a>
            <?php else: ?>
            <a href="/new-web/authentication/login.php" class="drawer-item">
                <span class="drawer-icon">🔑</span>
                <span>ورود به حساب</span>
            </a>
            <a href="/new-web/authentication/signup.php" class="drawer-item">
                <span class="drawer-icon">✨</span>
                <span>ثبت‌نام</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="header-spacer"></div>

    <?php echo $content ?? ''; ?>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="/new-web/index.php" class="footer-logo">
                        <span>📖</span>
                        <span>فروشگاه کتاب</span>
                    </a>
                    <p class="footer-desc">دنیای بی‌کران کتاب‌ها، با طراحی مدرن و تجربه‌ای دلنشین</p>
                    <div class="footer-social">
                        <?php if (!empty($social_links['instagram'])): ?>
                        <a href="<?= htmlspecialchars($social_links['instagram']) ?>" target="_blank" rel="noopener" class="social-link">
                            <img src="../static/images/icon-instagram.png" alt="Instagram">
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['telegram'])): ?>
                        <a href="<?= htmlspecialchars($social_links['telegram']) ?>" target="_blank" rel="noopener" class="social-link">
                            <img src="/new-web/static/images/icon-telegram.png" alt="Telegram">
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['twitter'])): ?>
                        <a href="<?= htmlspecialchars($social_links['twitter']) ?>" target="_blank" rel="noopener" class="social-link">
                            <span>𝕏</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h4>لینک‌های مفید</h4>
                    <ul class="footer-links">
                        <li><a href="/new-web/user/books.php?sort=latest">جدیدترین کتاب‌ها</a></li>
                        <li><a href="/new-web/user/books.php?sort=price_asc">ارزان‌ترین کتاب‌ها</a></li>
                        <li><a href="/new-web/user/categories.php">دسته‌بندی‌ها</a></li>
                        <li><a href="/new-web/user/about.php">درباره ما</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4><span>🔥</span> محبوب‌ترین دسته‌بندی‌ها</h4>
                    <?php if (!empty($popular_categories)): ?>
                    <div class="popular-list">
                        <?php foreach ($popular_categories as $index => $cat): ?>
                        <a href="/new-web/user/books.php?category=<?= $cat['category_id'] ?>" class="popular-item">
                            <span><?= htmlspecialchars($cat['category_icon'] ?? '📚') ?></span>
                            <span class="popular-name"><?= htmlspecialchars($cat['category_name']) ?></span>
                            <span class="popular-count"><?= $cat['order_count'] ?> سفارش</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-col">
                    <h4>ارتباط با ما</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <span>📞</span>
                            <span>09374340324</span>
                        </div>
                        <div class="contact-item">
                            <span>✉️</span>
                            <span>mh.mirzaii1382@gmail.com</span>
                        </div>
                        <div class="contact-item">
                            <span>⏰</span>
                            <span>۹ صبح تا ۹ شب</span>
                        </div>
                    </div>
                    <div class="trust-badges">
                        <img src="/new-web/static/images/enamad.png" alt="نماد اعتماد">
                        <img src="/new-web/static/images/shaparak.jpg" alt="درگاه پرداخت">
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© 2026 تمامی حقوق برای <strong>فروشگاه کتاب</strong> محفوظ است.</p>
            </div>
        </div>
    </footer>

    <a href="#" id="scrollToTop" class="scroll-top">⬆️</a>

    <script src="../static/js/header-footer.js"></script>
    <?php if ($isLoggedIn): ?>
    <script src="../static/js/chat.js"></script>
    <script>window.CHAT_USER_ID = <?php echo $user_id; ?>;</script>
    <?php endif; ?>
</body>
</html>