<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: books.php");
    exit();
}

$book_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT b.*, c.category_name, c.category_icon, c.category_slug 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.category_id 
    WHERE b.book_id = ?
");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: books.php");
    exit();
}

$comments_stmt = $conn->prepare("
    SELECT c.*, 
           u.first_name, u.last_name, u.user_id,
           CASE 
               WHEN u.user_id = ? THEN 1 
               ELSE 0 
           END as is_owner
    FROM comments c 
    JOIN users u ON c.user_id = u.user_id 
    WHERE c.book_id = ? 
    ORDER BY c.created_at DESC
");
$comments_stmt->bind_param("ii", $user_id, $book_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$count_query = "SELECT COUNT(*) as total FROM comments WHERE book_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $book_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_comments = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$comment_message = '';
$comment_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!$isLoggedIn) {
        $comment_status = 'error';
        $comment_message = 'برای ثبت نظر باید وارد حساب خود شوید.';
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);

        if ($rating < 1 || $rating > 5) {
            $comment_status = 'error';
            $comment_message = 'امتیاز باید بین ۱ تا ۵ باشد.';
        } elseif (empty($comment)) {
            $comment_status = 'error';
            $comment_message = 'لطفا نظر خود را وارد کنید.';
        } else {

            $insert_stmt = $conn->prepare("
                INSERT INTO comments (book_id, user_id, rating, comment) 
                VALUES (?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iiis", $book_id, $user_id, $rating, $comment);

            if ($insert_stmt->execute()) {
                $comment_status = 'success';
                $comment_message = 'نظر شما با موفقیت ثبت شد.';

                $comments_stmt->close();
                $comments_stmt = $conn->prepare("
                    SELECT c.*, 
                           u.first_name, u.last_name, u.user_id,
                           CASE 
                               WHEN u.user_id = ? THEN 1 
                               ELSE 0 
                           END as is_owner
                    FROM comments c 
                    JOIN users u ON c.user_id = u.user_id 
                    WHERE c.book_id = ? 
                    ORDER BY c.created_at DESC
                ");
                $comments_stmt->bind_param("ii", $user_id, $book_id);
                $comments_stmt->execute();
                $comments_result = $comments_stmt->get_result();

                $count_stmt = $conn->prepare($count_query);
                $count_stmt->bind_param("i", $book_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $total_comments = $count_result->fetch_assoc()['total'];
                $count_stmt->close();
            } else {
                $comment_status = 'error';
                $comment_message = 'خطا در ثبت نظر. لطفا دوباره تلاش کنید.';
            }
            $insert_stmt->close();
        }
    }
}

$page_title = $book['name'] . ' | جزئیات کتاب';

ob_start();
?>

<link rel="stylesheet" href="../static/css/book-details.css">

<script>
    window.BOOK_DETAILS = {
        bookId: <?php echo $book_id; ?>,
        totalComments: <?php echo $total_comments; ?>,
        commentMessage: <?php echo !empty($comment_message) ? json_encode($comment_message) : 'null'; ?>,
        commentStatus: <?php echo !empty($comment_status) ? json_encode($comment_status) : 'null'; ?>
    };
</script>

<div id="customAlert" class="custom-alert-overlay hidden">
    <div id="alertBox" class="custom-alert-box">
        <div class="alert-icon-wrapper">
            <span id="alertIcon" class="alert-icon-emoji">✅</span>
        </div>
        <h3 id="alertTitle" class="alert-title">موفقیت</h3>
        <p id="alertMessage" class="alert-message"></p>
        <button id="alertButton" class="alert-btn">متوجه شدم</button>
    </div>
</div>

<div class="details-container">

    <div class="breadcrumb">
        <a href="index.php">🏠 صفحه اصلی</a>
        <span class="breadcrumb-separator">◀</span>
        <a href="books.php">📚 کتاب‌ها</a>
        <span class="breadcrumb-separator">◀</span>
        <span><?php echo htmlspecialchars($book['name']); ?></span>
    </div>

    <div class="glass-panel">
        <div class="book-header">

            <div class="book-image-section">
                <div class="book-image-wrapper">
                    <div class="book-detail-image">
                        <img src="../<?php echo htmlspecialchars($book['image']); ?>"
                            alt="<?php echo htmlspecialchars($book['name']); ?>"
                            onerror="this.src='../static/images/placeholder-book.jpg'">
                    </div>
                    <div class="category-badge">
                        <span><?php echo htmlspecialchars($book['category_icon'] ?? '📚'); ?></span>
                        <?php echo htmlspecialchars($book['category_name'] ?? 'سایر'); ?>
                    </div>
                </div>
            </div>

            <div class="book-info-section">
                <h1 class="book-title-detail">
                    <?php echo htmlspecialchars($book['name']); ?>
                </h1>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon-box"><span>✍️</span></div>
                        <div class="info-content">
                            <div class="info-label">نویسنده</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['author']); ?></div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon-box"><span>📅</span></div>
                        <div class="info-content">
                            <div class="info-label">سال انتشار</div>
                            <div class="info-value"><?php echo $book['publication_year']; ?></div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon-box"><span>💰</span></div>
                        <div class="info-content">
                            <div class="info-label">قیمت</div>
                            <div class="info-value price"><?php echo number_format($book['price']); ?> تومان</div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon-box"><span>✅</span></div>
                        <div class="info-content">
                            <div class="info-label">وضعیت</div>
                            <div class="info-value status">موجود در انبار</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($book['caption'])): ?>
                    <div class="summary-box">
                        <div class="summary-title"><span>📝</span> خلاصه کتاب</div>
                        <p class="summary-text"><?php echo nl2br(htmlspecialchars($book['caption'])); ?></p>
                    </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <?php if ($isLoggedIn): ?>
                        <button onclick="addToCart(<?php echo $book['book_id']; ?>)" class="btn-primary-large">
                            <span>🛒</span>
                            <span>افزودن به سبد خرید</span>
                        </button>
                    <?php else: ?>
                        <a href="../authentication/login.php?redirect=book_details.php?id=<?php echo $book['book_id']; ?>" class="btn-primary-large">
                            <span>🔑</span>
                            <span>ورود برای خرید</span>
                        </a>
                    <?php endif; ?>

                    <a href="books.php" class="btn-secondary-large">
                        <span>⬅️</span>
                        <span>بازگشت به کتاب‌ها</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($book['description'])): ?>
        <div class="glass-panel">
            <div class="section-header">
                <div class="section-icon"><span>📄</span></div>
                <h2 class="section-title">توضیحات کامل</h2>
            </div>
            <div class="full-description">
                <?php echo nl2br(htmlspecialchars($book['description'])); ?>
            </div>

            <div class="ai-analysis-box" id="aiAnalysisBox">
                <div class="ai-header">
                    <div class="ai-header-left">
                        <div class="ai-icon">🤖</div>
                        <div class="ai-title">تحلیل هوشمند نظرات</div>
                    </div>
                    <button class="btn-ai-analyze" id="analyzeBtn">
                        <span>🔍</span>
                        <span>تحلیل نظرات</span>
                    </button>
                </div>
                <div class="ai-content" id="aiAnalysisContent">
                    <div class="ai-placeholder">
                        <span>💡</span> برای مشاهده تحلیل هوشمند نظرات کاربران، روی دکمه "تحلیل نظرات" کلیک کنید.
                    </div>
                </div>
                <div class="ai-footer">
                    <span class="ai-note">
                        <span>📊</span>
                        این تحلیل بر اساس <span id="commentCount"><?php echo $total_comments; ?></span> نظر ثبت شده انجام می‌شود.
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-panel">
        <div class="section-header">
            <div class="section-icon"><span>💬</span></div>
            <h2 class="section-title">نظرات کاربران</h2>
            <span class="section-badge"><?php echo $total_comments; ?> نظر</span>
        </div>

        <div class="comments-list">
            <?php if ($comments_result->num_rows > 0): ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div class="comment-user">
                                <div class="user-avatar">
                                    <?php echo mb_substr($comment['first_name'], 0, 1); ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        <span><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></span>
                                        <?php if ($comment['is_owner']): ?>
                                            <span class="owner-badge">شما</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="<?php echo $i <= $comment['rating'] ? 'star-filled' : 'star-empty'; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="comment-date">
                                <?php echo date('Y/m/d', strtotime($comment['created_at'])); ?>
                            </span>
                        </div>

                        <p class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </p>

                        <?php if (!empty($comment['admin_response'])): ?>
                            <div class="admin-response">
                                <div class="response-header">
                                    <span>🛡️ پاسخ ادمین:</span>
                                    <span class="comment-date">
                                        <?php echo date('Y/m/d', strtotime($comment['response_date'])); ?>
                                    </span>
                                </div>
                                <p class="response-text">
                                    <?php echo nl2br(htmlspecialchars($comment['admin_response'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-comments">
                    <div class="empty-icon-large">💭</div>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">هنوز نظری برای این کتاب ثبت نشده است.</p>
                    <p style="color: rgba(255,255,255,0.3); font-size: 0.7rem; margin-top: 0.25rem;">اولین نفری باشید که نظر می‌دهید!</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isLoggedIn): ?>
            <div class="comment-form-section">
                <h3 class="form-title"><span>✏️</span> ثبت نظر جدید</h3>

                <form method="POST" action="" class="comment-form">
                    <input type="hidden" name="add_comment" value="1">

                    <div class="rating-section">
                        <span class="rating-label">امتیاز شما:</span>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <textarea name="comment" rows="4" required class="comment-textarea" placeholder="نظر خود را درباره این کتاب بنویسید..."></textarea>

                    <button type="submit" class="btn-submit-comment">
                        <span>📨</span>
                        <span>ارسال نظر</span>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <p>برای ثبت نظر باید وارد حساب خود شوید.</p>
                <a href="../authentication/login.php?redirect=book_details.php?id=<?php echo $book['book_id']; ?>" class="btn-secondary-large">
                    <span>🔑</span>
                    <span>ورود به حساب</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../static/js/book-details.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>