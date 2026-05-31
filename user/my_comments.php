<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header("Location: ../authentication/login.php?redirect=my_comments.php");
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM comments WHERE user_id = ?");
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$total_comments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $per_page);
$count_stmt->close();

$comments = [];
$stmt = $conn->prepare("
    SELECT c.comment_id, c.rating, c.comment, c.created_at, 
           c.admin_response, c.response_date,
           b.book_id, b.name as book_name, b.image, b.author as book_author
    FROM comments c
    JOIN books b ON c.book_id = b.book_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $user_id, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

$stats = [];
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN admin_response IS NOT NULL AND admin_response != '' THEN 1 ELSE 0 END) as responded
    FROM comments 
    WHERE user_id = ?
");
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$page_title = "نظرات من";

ob_start();
?>

<link rel="stylesheet" href="../static/css/my_comments.css">


<div class="comments-container">
    
    <div class="page-header">
        <div class="page-title-section">
            <div class="page-icon">
                <span>💬</span>
            </div>
            <h1 class="page-title">نظرات من</h1>
            <span class="comment-count"><?= $total_comments ?> نظر</span>
        </div>
        <a href="dashboard.php" class="back-link">
            <span>◀</span>
            <span>بازگشت به داشبورد</span>
        </a>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-content">
                <div class="stat-label">کل نظرات</div>
                <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
                <span class="stat-unit">نظر</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <div class="stat-label">میانگین امتیاز شما</div>
                <div class="stat-value"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
                <span class="stat-unit">از ۵</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-label">پاسخ داده شده</div>
                <div class="stat-value"><?= number_format($stats['responded'] ?? 0) ?></div>
                <span class="stat-unit">نظر</span>
            </div>
        </div>
    </div>
    
    <?php if (empty($comments)): ?>
        <div class="empty-state">
            <div class="empty-icon">💭</div>
            <h3>هنوز نظری ثبت نکرده‌اید</h3>
            <p>با ثبت نظر در مورد کتاب‌ها، به سایر کاربران کمک کنید انتخاب بهتری داشته باشند.</p>
            <a href="books.php" class="btn-browse">
                <span>📚</span>
                <span>مشاهده کتاب‌ها</span>
            </a>
        </div>
    <?php else: ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <div class="book-image">
                            <img src="../<?= htmlspecialchars($comment['image']) ?>" alt="<?= htmlspecialchars($comment['book_name']) ?>" onerror="this.src='../static/images/placeholder-book.jpg'">
                        </div>
                        <div class="comment-info">
                            <a href="book_details.php?id=<?= $comment['book_id'] ?>" class="book-name">
                                <?= htmlspecialchars($comment['book_name']) ?>
                            </a>
                            <div class="book-author">
                                <span>✍️</span>
                                <?= htmlspecialchars($comment['book_author'] ?? 'نویسنده نامشخص') ?>
                            </div>
                            <div class="comment-meta">
                                <div class="comment-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?= $i <= $comment['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <div class="comment-date">
                                    <span>📅</span>
                                    <?= date('Y/m/d', strtotime($comment['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="comment-body">
                        <div class="comment-text">
                            <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                        </div>
                        
                        <?php if (!empty($comment['admin_response'])): ?>
                            <div class="admin-response">
                                <div class="response-header">
                                    <span class="response-label">
                                        <span>🛡️</span>
                                        پاسخ ادمین
                                    </span>
                                    <span class="response-date">
                                        <?= date('Y/m/d', strtotime($comment['response_date'])) ?>
                                    </span>
                                </div>
                                <div class="response-text">
                                    <?= nl2br(htmlspecialchars($comment['admin_response'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php
                    if ($page > 1): ?>
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