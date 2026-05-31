<?php
include '../database/config.php';

requirePermission('admin_panel');

function gregorian_to_jalali($gy, $gm, $gd)
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = ($days < 186) ? ($days % 31) + 1 : (($days - 186) % 30) + 1;
    return [$jy, $jm, $jd];
}

function jdate($format, $timestamp = null)
{
    if ($timestamp === null) $timestamp = time();
    list($gy, $gm, $gd) = gregorian_to_jalali(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
    $days_fa = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
    $months_fa = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    switch ($format) {
        case 'Y/m/d':
            return "$gy/$gm/$gd";
        case 'Y/m/d H:i':
            return "$gy/$gm/$gd " . date('H:i', $timestamp);
        case 'd F Y':
            return "$gd {$months_fa[$gm - 1]} $gy";
        case 'l, d F Y':
            return "{$days_fa[date('w',$timestamp)]}، $gd {$months_fa[$gm - 1]} $gy";
        case 'H:i':
            return date('H:i', $timestamp);
        case 'l':
            return $days_fa[date('w', $timestamp)];
        case 'F':
            return $months_fa[$gm - 1];
        case 'd':
            return $gd;
        default:
            return "$gy/$gm/$gd";
    }
}

$stats = [];
$tables = ['books', 'users', 'orders', 'comments', 'messages', 'categories'];

foreach (['books', 'categories'] as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $t" . ($t == 'users' ? " WHERE user_type = 'user'" : ""));
    $stats[$t] = $r->fetch_assoc()['c'];
}
$r = $conn->query("SELECT COUNT(*) as c FROM users WHERE user_type = 'user'");
$stats['users'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM orders");
$stats['orders'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM comments");
$stats['comments'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM messages");
$stats['messages'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COALESCE(SUM(total_price), 0) as t FROM orders");
$stats['revenue'] = $r->fetch_assoc()['t'];

$r = $conn->query("SELECT AVG(rating) as a FROM comments WHERE rating > 0");
$stats['avg_rating'] = round($r->fetch_assoc()['a'] ?? 0, 1);

$today = date('Y-m-d');
$r = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(total_price), 0) as t FROM orders WHERE DATE(order_date) = '$today'");
$td = $r->fetch_assoc();
$stats['orders_today'] = $td['c'];
$stats['revenue_today'] = $td['t'];

$r = $conn->query("SELECT COUNT(*) as c FROM users WHERE user_type = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users_week'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM books WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_books_week'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM comments WHERE admin_response IS NULL OR admin_response = ''");
$stats['pending_comments'] = $r->fetch_assoc()['c'];

$recent_orders = [];
$r = $conn->query("SELECT o.*, u.first_name, u.last_name, b.name as book_name, b.book_id
    FROM orders o JOIN users u ON o.user_id = u.user_id JOIN books b ON o.book_id = b.book_id
    ORDER BY o.order_date DESC LIMIT 6");
while ($row = $r->fetch_assoc()) $recent_orders[] = $row;

$top_books = [];
$r = $conn->query("SELECT b.name, b.book_id, b.author, COUNT(o.order_id) as cnt, COALESCE(SUM(o.total_price),0) as rev
    FROM books b LEFT JOIN orders o ON b.book_id = o.book_id
    GROUP BY b.book_id ORDER BY cnt DESC LIMIT 5");
while ($row = $r->fetch_assoc()) $top_books[] = $row;

$weekly_sales = [];
$weekly_dates = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $r = $conn->query("SELECT COALESCE(SUM(total_price), 0) as t FROM orders WHERE DATE(order_date) = '$d'");
    $weekly_sales[] = ['date' => $d, 'total' => $r->fetch_assoc()['t'], 'label' => jdate('d', strtotime($d))];
}
$max_sale = max(array_column($weekly_sales, 'total')) ?: 1;

$cat_stats = [];
$r = $conn->query("SELECT c.category_id, c.category_name, c.category_icon, COUNT(b.book_id) as cnt
    FROM categories c LEFT JOIN books b ON c.category_id = b.category_id
    GROUP BY c.category_id ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) $cat_stats[] = $row;
$total_books = $stats['books'] ?: 1;

$page_title = "داشبورد مدیریت";
ob_start();
?>

<link rel="stylesheet" href="../static/css/admin_panel.css">

<div class="dashboard">

    <div class="glass-card welcome-card">
        <div class="welcome-left">
            <div class="welcome-avatar"><?= $_SESSION['user_type'] === 'owner' ? '👑' : '🛡️' ?></div>
            <div>
                <h2 class="welcome-name"><?= htmlspecialchars($_SESSION['first_name'] ?? 'مدیر') ?> عزیز، خوش آمدید</h2>
                <p class="welcome-date"><?= jdate('l, d F Y') ?> | امروز: <strong><?= number_format($stats['revenue_today']) ?> تومان</strong> فروش</p>
            </div>
        </div>
        <div class="welcome-right">
            <div class="mini-stat">
                <span class="mini-stat-val"><?= number_format($stats['orders_today']) ?></span>
                <span class="mini-stat-lbl">سفارش امروز</span>
            </div>
            <div class="mini-stat">
                <span class="mini-stat-val"><?= number_format($stats['revenue']) ?></span>
                <span class="mini-stat-lbl">تومان فروش کل</span>
            </div>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format($stats['books']) ?></span>
                <span class="stat-lbl">کتاب</span>
                <span class="stat-sub">+<?= $stats['new_books_week'] ?> این هفته</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">📂</div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format($stats['categories']) ?></span>
                <span class="stat-lbl">دسته‌بندی</span>
                <span class="stat-sub">فعال</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format($stats['users']) ?></span>
                <span class="stat-lbl">کاربر</span>
                <span class="stat-sub">+<?= $stats['new_users_week'] ?> جدید</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format($stats['orders']) ?></span>
                <span class="stat-lbl">سفارش</span>
                <span class="stat-sub positive"><?= $stats['orders_today'] ?> امروز</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <span class="stat-num"><?= $stats['avg_rating'] ?></span>
                <span class="stat-lbl">میانگین امتیاز</span>
                <span class="stat-sub">از <?= number_format($stats['comments']) ?> نظر</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">📧</div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format($stats['messages']) ?></span>
                <span class="stat-lbl">پیام</span>
                <span class="stat-sub warning"><?= $stats['messages'] ?> خوانده نشده</span>
            </div>
        </div>
    </div>

    <div class="panels-row">
        <div class="glass-card panel">
            <div class="panel-head">
                <h3><span>🕐</span> آخرین سفارش‌ها</h3>
                <a href="list_orders.php">همه ◀</a>
            </div>
            <div class="panel-body">
                <?php foreach ($recent_orders as $o): ?>
                    <div class="order-row">
                        <div class="order-user">
                            <span class="order-name"><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></span>
                            <span class="order-book"><?= htmlspecialchars(mb_substr($o['book_name'], 0, 28)) ?></span>
                        </div>
                        <div class="order-detail">
                            <span><?= $o['quantity'] ?>×</span>
                            <span class="order-amount"><?= number_format($o['total_price']) ?> ت</span>
                            <span class="order-time"><?= jdate('H:i', strtotime($o['order_date'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="glass-card panel">
            <div class="panel-head">
                <h3><span>🏆</span> پرفروش‌ترین کتاب‌ها</h3>
                <a href="list_book.php">مدیریت ◀</a>
            </div>
            <div class="panel-body">
                <?php foreach ($top_books as $i => $b): ?>
                    <div class="book-row">
                        <span class="book-rank"><?= $i + 1 ?></span>
                        <div class="book-detail">
                            <span class="book-title"><?= htmlspecialchars(mb_substr($b['name'], 0, 30)) ?></span>
                            <span class="book-author"><?= htmlspecialchars($b['author'] ?? 'نامشخص') ?></span>
                        </div>
                        <div class="book-sales-info">
                            <span class="sales-count"><?= $b['cnt'] ?> فروش</span>
                            <span class="sales-rev"><?= number_format($b['rev']) ?> ت</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panels-row">
        <div class="glass-card panel">
            <div class="panel-head">
                <h3><span>📈</span> فروش ۷ روز اخیر</h3>
                <span class="panel-sum"><?= number_format(array_sum(array_column($weekly_sales, 'total'))) ?> تومان</span>
            </div>
            <div class="chart-bars">
                <?php foreach ($weekly_sales as $d): ?>
                    <div class="bar-col">
                        <div class="bar-container">
                            <div class="bar-fill" style="height:<?= max(4, ($d['total'] / $max_sale) * 100) ?>%">
                                <span class="bar-tip"><?= number_format($d['total']) ?></span>
                            </div>
                        </div>
                        <span class="bar-label"><?= $d['label'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="glass-card panel">
            <div class="panel-head">
                <h3><span>📊</span> توزیع کتاب‌ها</h3>
            </div>
            <div class="dist-list">
                <?php foreach ($cat_stats as $c): ?>
                    <div class="dist-item">
                        <div class="dist-label">
                            <span><?= htmlspecialchars($c['category_icon'] ?? '📁') ?></span>
                            <span><?= htmlspecialchars($c['category_name']) ?></span>
                        </div>
                        <div class="dist-bar-wrap">
                            <div class="dist-bar" style="width:<?= ($c['cnt'] / $total_books) * 100 ?>%"></div>
                        </div>
                        <span class="dist-num"><?= $c['cnt'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="glass-card quick-actions">
        <h3><span>⚡</span> دسترسی سریع</h3>
        <div class="actions-row">
            <a href="add_book.php" class="act-btn act-primary">➕ کتاب جدید</a>
            <a href="add_category.php" class="act-btn act-primary">📂 دسته‌بندی جدید</a>
            <a href="list_orders.php" class="act-btn act-secondary">🛒 سفارش‌ها</a>
            <a href="list_comments.php" class="act-btn act-secondary">
                💬 نظرات
                <?php if ($stats['pending_comments'] > 0): ?>
                    <span class="act-badge"><?= $stats['pending_comments'] ?></span>
                <?php endif; ?>
            </a>
            <a href="list_messages.php" class="act-btn act-secondary">
                📧 پیام‌ها
                <?php if ($stats['messages'] > 0): ?>
                    <span class="act-badge"><?= $stats['messages'] ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>