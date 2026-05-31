<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

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

$social_links = [
    'instagram' => 'https://instagram.com/Hoseiin_28',
    'telegram'   => 'https://t.me/Hoseiin_28',
    'github'     => 'https://github.com/hoseinmirzaii'
];

$page_title = "درباره ما";

ob_start();
?>

<link rel="stylesheet" href="../static/css/about.css">

<div class="about-page-container">
    
    <div class="page-section">
        <div class="section-header">
            <div class="section-icon-box"><span>👥</span></div>
            <h2 class="section-title">درباره من</h2>
        </div>
        <div class="about-content">
            <div class="about-image">
                <img src="../static/images/me.jpg" alt="محمد حسین میرزایی - طراح و توسعه‌دهنده وب">
            </div>
            <div class="about-text">
                <h3>محمد حسین میرزایی</h3>
                <p class="about-intro">
                    طراح و توسعه‌دهنده فول‌استک وب با بیش از ۳ سال تجربه در زمینه طراحی سایت‌های فروشگاهی،
                    شرکتی و شخصی. تخصص اصلی من در حوزه Front-end و Back-end با استفاده از تکنولوژی‌های
                    مدرن مانند PHP ، JavaScript ، Html, Css می‌باشد.
                </p>

                <div class="about-services">
                    <h4>🚀 خدمات تخصصی</h4>
                    <ul class="services-list">
                        <li><span>🛍️</span> طراحی و پیاده‌سازی فروشگاه‌های اینترنتی کامل</li>
                        <li><span>📱</span> طراحی سایت‌های ریسپانسیو و واکنش‌گرا</li>
                        <li><span>🎨</span> طراحی رابط کاربری (UI) و تجربه کاربری (UX) مدرن</li>
                        <li><span>⚙️</span> بهینه‌سازی و رفع باگ سایت‌های موجود</li>
                        <li><span>🤖</span> پیاده‌سازی سیستم‌های هوش مصنوعی و چت‌بات</li>
                        <li><span>📊</span> طراحی پنل‌های مدیریتی و داشبوردهای تحلیلی</li>
                    </ul>
                </div>

                <div class="about-collaboration">
                    <h4>🤝 پذیرش پروژه و همکاری</h4>
                    <p>
                        در حال حاضر برای پروژه‌های جدید در حوزه طراحی و توسعه وب آماده همکاری هستم.
                        اگر نیاز به یک سایت حرفه‌ای، فروشگاه اینترنتی، پنل مدیریت یا مشاوره فنی دارید،
                        می‌توانید از طریق راه‌های ارتباطی زیر با من در تماس باشید.
                    </p>
                    <div class="collab-badges">
                        <span class="collab-badge">✅ پروژه‌های فروشگاهی</span>
                        <span class="collab-badge">✅ سایت‌های شرکتی</span>
                        <span class="collab-badge">✅ پنل مدیریت</span>
                        <span class="collab-badge">✅ مشاوره فنی</span>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-item"><span>🎨</span> طراحی مدرن و کاربرپسند</div>
                    <div class="feature-item"><span>⚡</span> سرعت بالا و بهینه‌سازی SEO</div>
                    <div class="feature-item"><span>📱</span> کاملاً ریسپانسیو و موبایل فرندلی</div>
                    <div class="feature-item"><span>🔒</span> امنیت بالا و محافظت از داده‌ها</div>
                </div>

                <div class="contact-info-box">
                    <h4>📬 راه‌های ارتباطی</h4>
                    <div class="contact-details">
                        <div class="contact-detail-item">
                            <span class="contact-icon">📞</span>
                            <div>
                                <span class="contact-label">تلفن مستقیم</span>
                                <span class="contact-value" dir="ltr">0937 434 0324</span>
                            </div>
                        </div>
                        <div class="contact-detail-item">
                            <span class="contact-icon">✉️</span>
                            <div>
                                <span class="contact-label">ایمیل</span>
                                <span class="contact-value">mh.mirzaii1382@gmail.com</span>
                            </div>
                        </div>
                        <div class="contact-detail-item">
                            <span class="contact-icon">⏰</span>
                            <div>
                                <span class="contact-label">ساعت پاسخگویی</span>
                                <span class="contact-value">۹ صبح تا ۹ شب</span>
                            </div>
                        </div>
                        <div class="contact-detail-item">
                            <span class="contact-icon">📍</span>
                            <div>
                                <span class="contact-label">موقعیت</span>
                                <span class="contact-value">همدان، ایران (پروژه‌های دورکاری سراسر کشور)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="social-links">
                    <?php if (!empty($social_links['instagram'])): ?>
                        <a href="<?= htmlspecialchars($social_links['instagram']) ?>" target="_blank" rel="noopener noreferrer" class="social-link-item">
                            <img src="../static/images/icon-instagram.png" alt="Instagram" class="social-icon-img">
                            <span>Instagram</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($social_links['telegram'])): ?>
                        <a href="<?= htmlspecialchars($social_links['telegram']) ?>" target="_blank" rel="noopener noreferrer" class="social-link-item">
                            <img src="../static/images/icon-telegram.png" alt="Telegram" class="social-icon-img">
                            <span>Telegram</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($social_links['github'])): ?>
                        <a href="<?= htmlspecialchars($social_links['github']) ?>" target="_blank" rel="noopener noreferrer" class="social-link-item">
                            <span style="font-size: 1.3rem;">💻</span>
                            <span>GitHub</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="page-section">
        <div class="section-header">
            <div class="section-icon-box"><span>💬</span></div>
            <h2 class="section-title">نظرات و پیشنهادات</h2>
            <span class="section-count"><?= count($random_comments) ?>+ نظر</span>
        </div>

        <div class="feedback-content">
            <div class="feedback-form-wrapper">
                <form id="feedbackForm" class="feedback-form">
                    <div class="form-row">
                        <input type="text" name="name" placeholder="نام و نام خانوادگی" required>
                        <input type="email" name="email" placeholder="آدرس ایمیل" required>
                    </div>
                    <textarea name="message" placeholder="نظر، پیشنهاد یا انتقاد شما..." rows="4" required></textarea>
                    <button type="submit" class="btn-feedback-submit">
                        <span>📨</span>
                        <span>ارسال نظر</span>
                    </button>
                </form>
                <div class="quick-contact">
                    <div class="quick-contact-item"><span>📞</span><span>09374340324</span></div>
                    <div class="quick-contact-item"><span>✉️</span><span>mh.mirzaii1382@gmail.com</span></div>
                    <div class="quick-contact-item"><span>⏰</span><span>پاسخگویی: ۹ صبح تا ۹ شب</span></div>
                </div>
            </div>

            <div class="feedback-stats">
                <?php
                $stats_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM comments";
                $stats_result = $conn->query($stats_query);
                $stats = $stats_result->fetch_assoc();
                ?>
                <div class="stat-circle">
                    <div class="stat-circle-inner">
                        <span class="stat-number"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></span>
                        <span class="stat-label">از ۵</span>
                    </div>
                </div>
                <div class="stat-info">
                    <h4>رضایت کاربران</h4>
                    <p>بر اساس <?= number_format($stats['total'] ?? 0) ?> نظر ثبت شده</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../static/js/about.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>