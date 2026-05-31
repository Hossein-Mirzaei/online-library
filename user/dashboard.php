<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header("Location: ../authentication/login.php?redirect=dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stats = [];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stats['orders'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stats['total_spent'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stats['comments'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$recent_orders = [];
$stmt = $conn->prepare("
    SELECT o.order_id, o.quantity, o.total_price, o.order_date,
           b.name as book_name, b.image, b.book_id
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}
$stmt->close();

$recent_comments = [];
$stmt = $conn->prepare("
    SELECT c.comment_id, c.rating, c.comment, c.created_at, c.admin_response,
           b.name as book_name, b.book_id
    FROM comments c
    JOIN books b ON c.book_id = b.book_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_comments[] = $row;
}
$stmt->close();

$page_title = "داشبورد کاربری";

ob_start();
?>

<link rel="stylesheet" href="../static/css/dashboard.css">

<div class="dashboard-container">
    
    <div class="welcome-section">
        <div class="welcome-avatar">
            <?= mb_substr($user['first_name'] ?? 'ک', 0, 1) ?>
        </div>
        <div class="welcome-info">
            <h2 class="welcome-title">
                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> عزیز، خوش آمدید!
            </h2>
            <p class="welcome-subtitle">
                <span>👤</span> <?= htmlspecialchars($user['username']) ?> | 
                <span>📧</span> <?= htmlspecialchars($user['email']) ?>
            </p>
        </div>
        <a href="edit_profile.php" class="btn-edit-profile">
            <span>✏️</span>
            ویرایش پروفایل
        </a>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-content">
                <div class="stat-label">تعداد سفارشات</div>
                <div class="stat-value"><?= number_format($stats['orders']) ?></div>
                <div class="stat-unit">سفارش</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-label">مجموع خرید</div>
                <div class="stat-value"><?= number_format($stats['total_spent']) ?></div>
                <div class="stat-unit">تومان</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-content">
                <div class="stat-label">نظرات ثبت شده</div>
                <div class="stat-value"><?= number_format($stats['comments']) ?></div>
                <div class="stat-unit">نظر</div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-icon">📦</div>
                <h3 class="panel-title">آخرین سفارشات</h3>
                <a href="my_order.php" class="panel-link">مشاهده همه ◀</a>
            </div>
            
            <div class="orders-list">
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-message">
                        <span>📭</span>
                        <p>هنوز سفارشی ثبت نکرده‌اید.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-image">
                                <img src="../<?= htmlspecialchars($order['image']) ?>" alt="<?= htmlspecialchars($order['book_name']) ?>" onerror="this.src='../static/images/placeholder-book.jpg'">
                            </div>
                            <div class="order-info">
                                <div class="order-book-name"><?= htmlspecialchars($order['book_name']) ?></div>
                                <div class="order-details">
                                    <span>تعداد: <?= $order['quantity'] ?></span>
                                    <span><?= date('Y/m/d', strtotime($order['order_date'])) ?></span>
                                </div>
                            </div>
                            <div class="order-price">
                                <?= number_format($order['total_price']) ?> تومان
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <div class="panel-icon">💬</div>
                <h3 class="panel-title">نظرات اخیر شما</h3>
                <a href="my_comments.php" class="panel-link">مشاهده همه ◀</a>
            </div>
            
            <div class="comments-list">
                <?php if (empty($recent_comments)): ?>
                    <div class="empty-message">
                        <span>💭</span>
                        <p>هنوز نظری ثبت نکرده‌اید.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-book">
                                    <a href="book_details.php?id=<?= $comment['book_id'] ?>" style="color: var(--primary-color); text-decoration: none;">
                                        <?= htmlspecialchars($comment['book_name']) ?>
                                    </a>
                                </span>
                                <div class="comment-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?= $i <= $comment['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                            <div class="comment-date"><?= date('Y/m/d', strtotime($comment['created_at'])) ?></div>
                            
                            <?php if (!empty($comment['admin_response'])): ?>
                                <div class="admin-response">
                                    <div class="admin-response-label">🛡️ پاسخ ادمین:</div>
                                    <div class="admin-response-text"><?= nl2br(htmlspecialchars($comment['admin_response'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="quick-actions">
        <a href="books.php" class="action-btn primary">
            <span>📚</span>
            مشاهده کتاب‌ها
        </a>
        <a href="order.php" class="action-btn">
            <span>🛒</span>
            سبد خرید
        </a>
        <a href="edit_profile.php" class="action-btn">
            <span>⚙️</span>
            تنظیمات حساب
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>