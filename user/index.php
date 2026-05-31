<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

$page_title = "صفحه اصلی";

$sql_latest = "SELECT b.*, c.category_name, c.category_icon 
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               ORDER BY b.created_at DESC LIMIT 5";
$result_latest = $conn->query($sql_latest);

$sql_bestsellers = "SELECT b.*, c.category_name, c.category_icon, COUNT(o.order_id) as order_count
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.category_id 
                    LEFT JOIN orders o ON b.book_id = o.book_id
                    GROUP BY b.book_id
                    HAVING order_count > 0
                    ORDER BY order_count DESC LIMIT 5";
$result_bestsellers = $conn->query($sql_bestsellers);

$sql_popular = "SELECT b.*, c.category_name, c.category_icon, 
                       AVG(co.rating) as avg_rating, COUNT(co.comment_id) as comment_count
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.category_id 
                LEFT JOIN comments co ON b.book_id = co.book_id
                GROUP BY b.book_id
                HAVING comment_count > 0 AND avg_rating >= 4
                ORDER BY avg_rating DESC, comment_count DESC LIMIT 5";
$result_popular = $conn->query($sql_popular);

$sql_cheapest = "SELECT b.*, c.category_name, c.category_icon 
                 FROM books b 
                 LEFT JOIN categories c ON b.category_id = c.category_id 
                 ORDER BY b.price ASC LIMIT 5";
$result_cheapest = $conn->query($sql_cheapest);

$sql_special = "SELECT b.*, c.category_name, c.category_icon 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.category_id 
                ORDER BY RAND() LIMIT 5";
$result_special = $conn->query($sql_special);

$sql_discover = "SELECT b.*, c.category_name, c.category_icon 
                 FROM books b 
                 LEFT JOIN categories c ON b.category_id = c.category_id 
                 ORDER BY RAND() LIMIT 5";
$result_discover = $conn->query($sql_discover);

$random_comments = [];
$comments_query = "
    SELECT c.comment_id, c.rating, c.comment, c.created_at, c.admin_response, c.response_date,
           u.first_name, u.last_name,
           b.name as book_name, b.book_id, b.image
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    JOIN books b ON c.book_id = b.book_id
    ORDER BY RAND()
    LIMIT 10
";
$comments_result = $conn->query($comments_query);
while ($comment = $comments_result->fetch_assoc()) {
    $random_comments[] = $comment;
}

ob_start();
?>

<link rel="stylesheet" href="../static/css/index.css">

<div class="index-container">

    <div class="main-banner-container">
        <div class="main-banner">
            <img src="../static/images/image-1.jpeg" alt="به فروشگاه کتاب خوش آمدید">
            <div class="banner-overlay">
                <h1>به فروشگاه کتاب خوش آمدید</h1>
                <p>دنیای بی‌کران کتاب‌ها در انتظار شماست</p>
                <a href="books.php" class="banner-btn">
                    <span>📚</span>
                    <span>مشاهده کتاب‌ها</span>
                </a>
            </div>
        </div>
    </div>

    <?php
    function renderBookSection($title, $icon_emoji, $result, $isLoggedIn) {
        if (!$result || $result->num_rows == 0) return;
        ?>
        <div class="section-header">
            <div class="section-icon-box">
                <span><?php echo $icon_emoji; ?></span>
            </div>
            <h2 class="section-title"><?php echo $title; ?></h2>
            <span class="section-count"><?php echo $result->num_rows; ?> کتاب</span>
        </div>

        <div class="books-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="book-card">
                    <div class="book-image-container">
                        <img src="../<?php echo htmlspecialchars($row['image']); ?>"
                            alt="<?php echo htmlspecialchars($row['name']); ?>"
                            class="book-image"
                            onerror="this.src='../static/images/placeholder-book.jpg'">
                        <div class="book-badge">
                            <span><?php echo htmlspecialchars($row['category_icon'] ?? '📚'); ?></span>
                            <?php echo htmlspecialchars($row['category_name'] ?? 'سایر'); ?>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <div class="book-author">
                            <span>✍️</span>
                            <?php echo htmlspecialchars($row['author'] ?? 'نویسنده نامشخص'); ?>
                        </div>
                        <p class="book-description">
                            <?php
                            $desc = strip_tags($row['description'] ?? $row['caption'] ?? '');
                            echo mb_strlen($desc) > 50 ? mb_substr($desc, 0, 50) . '...' : $desc;
                            ?>
                        </p>
                        <div class="book-footer">
                            <div class="book-price">
                                <span class="price-value"><?php echo number_format($row['price']); ?></span>
                                <span class="price-unit">تومان</span>
                            </div>
                            <div class="book-actions">
                                <a href="book_details.php?id=<?php echo $row['book_id']; ?>" class="btn-details">
                                    <span>ℹ️</span>
                                    <span>جزئیات</span>
                                </a>
                                <?php if ($isLoggedIn): ?>
                                    <button onclick="addToCart(<?php echo $row['book_id']; ?>)" class="btn-buy">
                                        <span>🛒</span>
                                        <span>خرید</span>
                                    </button>
                                <?php else: ?>
                                    <a href="../authentication/login.php?redirect=index.php" class="btn-login">
                                        <span>🔑</span>
                                        <span>ورود</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php
    }

    renderBookSection(' جدیدترین کتاب‌ها', '🆕', $result_latest, $isLoggedIn);
    ?>

    <div class="promo-banner-container">
        <div class="promo-banner">
            <img src="../static/images/image-1.jpeg" alt="تخفیف ویژه">
            <div class="promo-content">
                <div class="promo-text">
                    <span class="promo-badge">🔥 پیشنهاد ویژه</span>
                    <h3>تا ۵۰٪ تخفیف کتاب‌های پرفروش</h3>
                    <p>فرصت استثنایی برای خرید بهترین کتاب‌ها</p>
                </div>
                <a href="books.php?sort=price_asc" class="promo-btn">
                    <span>🛍️</span>
                    <span>مشاهده تخفیف‌ها</span>
                </a>
            </div>
        </div>
    </div>

    <?php
    renderBookSection(' پرفروش‌ترین کتاب‌ها', '🔥', $result_bestsellers, $isLoggedIn);
    renderBookSection(' محبوب‌ترین کتاب‌ها', '⭐', $result_popular, $isLoggedIn);
    ?>

    <div class="promo-banner-container">
        <div class="promo-banner promo-banner-reverse">
            <img src="../static/images/image-1.jpeg" alt="ارسال رایگان">
            <div class="promo-content">
                <div class="promo-text">
                    <span class="promo-badge">🚚 ارسال رایگان</span>
                    <h3>ارسال رایگان برای سفارش‌های بالای ۳۰۰ هزار تومان</h3>
                    <p>کتاب‌های مورد علاقه خود را بدون هزینه ارسال دریافت کنید</p>
                </div>
                <a href="books.php" class="promo-btn">
                    <span>🛒</span>
                    <span>همین حالا خرید کنید</span>
                </a>
            </div>
        </div>
    </div>

    <?php
    renderBookSection(' کتاب‌های اقتصادی', '💰', $result_cheapest, $isLoggedIn);
    renderBookSection(' پیشنهادهای ویژه', '✨', $result_special, $isLoggedIn);
    ?>

    <div class="promo-banner-container">
        <div class="promo-banner">
            <img src="../static/images/image-1.jpeg" alt="عضویت ویژه">
            <div class="promo-content">
                <div class="promo-text">
                    <span class="promo-badge">⭐ اعضای ویژه</span>
                    <h3>عضو ویژه فروشگاه کتاب شوید</h3>
                    <p>از تخفیف‌های دائمی و پیشنهادات اختصاصی بهره‌مند شوید</p>
                </div>
                <a href="../authentication/signup.php" class="promo-btn">
                    <span>✨</span>
                    <span>ثبت‌نام کنید</span>
                </a>
            </div>
        </div>
    </div>

    <?php
    renderBookSection(' کتاب‌های تصادفی', '🎲', $result_discover, $isLoggedIn);
    ?>

    <?php if (isset($_SESSION['order_success'])): ?>
        <div class="success-message">
            <span>✅</span>
            <span><?php echo $_SESSION['order_success']; ?></span>
        </div>
        <?php unset($_SESSION['order_success']); ?>
    <?php endif; ?>

    <div class="testimonials-section">
        <div class="testimonials-header">
            <h3><span>⭐</span> نظرات کاربران درباره کتاب‌ها</h3>
        </div>

        <div class="testimonials-container" id="testimonialsContainer">
            <div class="testimonials-track" id="testimonialsTrack">
                <?php foreach ($random_comments as $comment): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-book">
                            <img src="../<?= htmlspecialchars($comment['image']) ?>" alt="<?= htmlspecialchars($comment['book_name']) ?>" onerror="this.src='../static/images/placeholder-book.jpg'">
                            <div class="testimonial-book-info">
                                <a href="book_details.php?id=<?= $comment['book_id'] ?>"><?= htmlspecialchars($comment['book_name']) ?></a>
                                <div class="testimonial-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?= $i <= $comment['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-content">
                            <p class="testimonial-text">"<?= htmlspecialchars(mb_substr($comment['comment'], 0, 150)) ?><?= mb_strlen($comment['comment']) > 150 ? '...' : '' ?>"</p>
                            <?php if (!empty($comment['admin_response'])): ?>
                                <div class="testimonial-response">
                                    <span class="response-badge">🛡️ پاسخ فروشگاه</span>
                                    <p>"<?= htmlspecialchars(mb_substr($comment['admin_response'], 0, 100)) ?><?= mb_strlen($comment['admin_response']) > 100 ? '...' : '' ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="testimonial-footer">
                            <div class="testimonial-user">
                                <span class="user-avatar-small"><?= mb_substr($comment['first_name'], 0, 1) ?></span>
                                <span class="user-name-small"><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?></span>
                            </div>
                            <span class="testimonial-date"><?= date('Y/m/d', strtotime($comment['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="../static/js/index.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>