<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header("Location: ../authentication/login.php?redirect=my_orders.php");
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);
$count_stmt->close();

$orders = [];
$stmt = $conn->prepare("
    SELECT o.order_id, o.quantity, o.total_price, o.order_date,
           b.book_id, b.name as book_name, b.image, b.author as book_author, b.price as unit_price
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $user_id, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

$stats = [];
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_price), 0) as total_spent,
        COUNT(DISTINCT book_id) as unique_books,
        AVG(total_price) as avg_order_value,
        MAX(order_date) as last_order_date
    FROM orders 
    WHERE user_id = ?
");
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$favorite_category = null;
$cat_stmt = $conn->prepare("
    SELECT c.category_name, c.category_icon, COUNT(*) as count
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    LEFT JOIN categories c ON b.category_id = c.category_id
    WHERE o.user_id = ?
    GROUP BY c.category_id
    ORDER BY count DESC
    LIMIT 1
");
$cat_stmt->bind_param('i', $user_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
if ($cat_result->num_rows > 0) {
    $favorite_category = $cat_result->fetch_assoc();
}
$cat_stmt->close();

$page_title = "سفارشات من";

ob_start();
?>

<link rel="stylesheet" href="../static/css/my_order.css">

<div class="orders-container">
    
    <div class="page-header">
        <div class="page-title-section">
            <div class="page-icon">
                <span>📦</span>
            </div>
            <h1 class="page-title">سفارشات من</h1>
            <span class="order-count"><?= $total_orders ?> سفارش</span>
        </div>
        <a href="dashboard.php" class="back-link">
            <span>◀</span>
            <span>بازگشت به داشبورد</span>
        </a>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <div class="stat-label">کل سفارشات</div>
                <div class="stat-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
                <span class="stat-unit">سفارش</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-label">مجموع خرید</div>
                <div class="stat-value"><?= number_format($stats['total_spent'] ?? 0) ?></div>
                <span class="stat-unit">تومان</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
                <div class="stat-label">کتاب‌های مختلف</div>
                <div class="stat-value"><?= number_format($stats['unique_books'] ?? 0) ?></div>
                <span class="stat-unit">عنوان</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-label">میانگین خرید</div>
                <div class="stat-value"><?= number_format($stats['avg_order_value'] ?? 0) ?></div>
                <span class="stat-unit">تومان</span>
            </div>
        </div>
    </div>
    
    <?php if ($favorite_category): ?>
        <div class="favorite-banner">
            <div class="favorite-icon"><?= htmlspecialchars($favorite_category['category_icon'] ?? '📚') ?></div>
            <div class="favorite-content">
                <div class="favorite-title">دسته‌بندی مورد علاقه شما</div>
                <div class="favorite-text">
                    شما بیشترین خرید را از دسته‌بندی <strong style="color: var(--primary-color);"><?= htmlspecialchars($favorite_category['category_name']) ?></strong> داشته‌اید (<?= $favorite_category['count'] ?> سفارش)
                </div>
            </div>
            <a href="books.php?category=<?= $favorite_category['category_name'] ?>" class="btn-category">
                <span>📚</span>
                مشاهده کتاب‌های این دسته
            </a>
        </div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <h3>هنوز سفارشی ثبت نکرده‌اید</h3>
            <p>کتاب‌های مورد علاقه خود را انتخاب و سفارش دهید.</p>
            <a href="books.php" class="btn-browse">
                <span>📚</span>
                <span>مشاهده کتاب‌ها</span>
            </a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id">
                            <span>🆔</span>
                            سفارش #<?= $order['order_id'] ?>
                        </span>
                        <span class="order-date">
                            <span>📅</span>
                            <?= date('Y/m/d H:i', strtotime($order['order_date'])) ?>
                        </span>
                    </div>
                    
                    <div class="order-body">
                        <div class="book-image">
                            <img src="../<?= htmlspecialchars($order['image']) ?>" alt="<?= htmlspecialchars($order['book_name']) ?>" onerror="this.src='../static/images/placeholder-book.jpg'">
                        </div>
                        
                        <div class="order-details">
                            <a href="book_details.php?id=<?= $order['book_id'] ?>" class="book-name">
                                <?= htmlspecialchars($order['book_name']) ?>
                            </a>
                            <div class="book-author">
                                <span>✍️</span>
                                <?= htmlspecialchars($order['book_author'] ?? 'نویسنده نامشخص') ?>
                            </div>
                            
                            <div class="order-info-grid">
                                <div class="info-item">
                                    <span class="info-label">قیمت واحد</span>
                                    <span class="info-value"><?= number_format($order['unit_price']) ?> تومان</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">تعداد</span>
                                    <span class="info-value"><?= $order['quantity'] ?> عدد</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">قیمت کل</span>
                                    <span class="info-value price"><?= number_format($order['total_price']) ?> تومان</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="book_details.php?id=<?= $order['book_id'] ?>" class="btn-view-book">
                            <span>📖</span>
                            مشاهده کتاب
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-link">◀ قبلی</a>
                    <?php else: ?>
                        <span class="page-link disabled">◀ قبلی</span>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="?page=1" class="page-link">1</a>
                        <?php if ($start > 2): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                        <a href="?page=<?= $total_pages ?>" class="page-link"><?= $total_pages ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-link">بعدی ▶</a>
                    <?php else: ?>
                        <span class="page-link disabled">بعدی ▶</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>