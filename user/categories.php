<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

$query = "SELECT c.*, COUNT(b.book_id) as book_count 
          FROM categories c 
          LEFT JOIN books b ON c.category_id = b.category_id 
          GROUP BY c.category_id 
          ORDER BY c.category_name ASC";
$result = $conn->query($query);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$parent_categories = [];
$sub_categories = [];

foreach ($categories as $cat) {
    if ($cat['parent_id'] === null) {
        $parent_categories[] = $cat;
    } else {
        if (!isset($sub_categories[$cat['parent_id']])) {
            $sub_categories[$cat['parent_id']] = [];
        }
        $sub_categories[$cat['parent_id']][] = $cat;
    }
}

$total_books = 0;
foreach ($categories as $cat) {
    $total_books += $cat['book_count'];
}

$page_title = "دسته‌بندی کتاب‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/categories.css">

<div class="categories-container">
    
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">📚</div>
            <h1 class="hero-title">دسته‌بندی کتاب‌ها</h1>
            <p class="hero-subtitle">کتاب‌ها را بر اساس موضوع مورد علاقه خود کاوش کنید</p>
            
            <div class="stats-wrapper">
                <div class="hero-stat">
                    <span class="hero-stat-icon">📖</span>
                    <span class="hero-stat-number"><?= number_format($total_books) ?></span>
                    <span class="hero-stat-label">کتاب</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-icon">📂</span>
                    <span class="hero-stat-number"><?= count($parent_categories) ?></span>
                    <span class="hero-stat-label">دسته اصلی</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-icon">📁</span>
                    <span class="hero-stat-number"><?= count($categories) - count($parent_categories) ?></span>
                    <span class="hero-stat-label">زیرمجموعه</span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>هیچ دسته‌بندی یافت نشد</h3>
            <p>در حال حاضر دسته‌بندی برای نمایش وجود ندارد.</p>
        </div>
    <?php else: ?>
        
        <?php if (!empty($parent_categories)): ?>
            <div class="section-header">
                <div class="section-icon">📂</div>
                <h2 class="section-title">دسته‌بندی‌های اصلی</h2>
                <span class="section-count"><?= count($parent_categories) ?> دسته</span>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($parent_categories as $parent): ?>
                    <a href="books.php?category=<?= $parent['category_id'] ?>" class="category-card">
                        <div class="card-header">
                            <div class="card-icon"><?= htmlspecialchars($parent['category_icon'] ?? '📁') ?></div>
                            <div class="card-title-section">
                                <div class="card-title"><?= htmlspecialchars($parent['category_name']) ?></div>
                                <span class="card-book-count">
                                    <span>📚</span> <?= $parent['book_count'] ?> کتاب
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($parent['category_description'])): ?>
                            <div class="card-description">
                                <?= htmlspecialchars($parent['category_description']) ?>
                            </div>
                        <?php else: ?>
                            <div class="card-description">
                                مجموعه‌ای از بهترین کتاب‌های <?= htmlspecialchars($parent['category_name']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($sub_categories[$parent['category_id']]) && !empty($sub_categories[$parent['category_id']])): ?>
                            <div class="subcategories-list">
                                <div class="subcategories-title">
                                    <span>🔗</span> زیرمجموعه‌ها
                                </div>
                                <div class="subcategory-items">
                                    <?php foreach (array_slice($sub_categories[$parent['category_id']], 0, 3) as $sub): ?>
                                        <a href="books.php?category=<?= $sub['category_id'] ?>" class="subcategory-link" onclick="event.stopPropagation()">
                                            <?= htmlspecialchars($sub['category_icon'] ?? '📄') ?>
                                            <?= htmlspecialchars($sub['category_name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if (count($sub_categories[$parent['category_id']]) > 3): ?>
                                        <span class="subcategory-link" style="opacity: 0.7;">
                                            +<?= count($sub_categories[$parent['category_id']]) - 3 ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-footer">
                            <span class="card-link">
                                مشاهده کتاب‌ها
                                <span>◀</span>
                            </span>
                            <?php if (isset($sub_categories[$parent['category_id']])): ?>
                                <span class="subcategories-badge">
                                    <span>📁</span> <?= count($sub_categories[$parent['category_id']]) ?> زیرمجموعه
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $orphan_categories = [];
        foreach ($categories as $cat) {
            if ($cat['parent_id'] === null && !in_array($cat['category_id'], array_column($parent_categories, 'category_id'))) {
                $orphan_categories[] = $cat;
            }
        }
        ?>
        
        <?php if (!empty($orphan_categories)): ?>
            <div class="section-header">
                <div class="section-icon">📚</div>
                <h2 class="section-title">سایر دسته‌بندی‌ها</h2>
                <span class="section-count"><?= count($orphan_categories) ?> دسته</span>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($orphan_categories as $cat): ?>
                    <a href="books.php?category=<?= $cat['category_id'] ?>" class="category-card">
                        <div class="card-header">
                            <div class="card-icon"><?= htmlspecialchars($cat['category_icon'] ?? '📁') ?></div>
                            <div class="card-title-section">
                                <div class="card-title"><?= htmlspecialchars($cat['category_name']) ?></div>
                                <span class="card-book-count">
                                    <span>📚</span> <?= $cat['book_count'] ?> کتاب
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-description">
                            <?= !empty($cat['category_description']) ? htmlspecialchars($cat['category_description']) : 'مجموعه‌ای از بهترین کتاب‌های ' . htmlspecialchars($cat['category_name']) ?>
                        </div>
                        
                        <div class="card-footer">
                            <span class="card-link">
                                مشاهده کتاب‌ها
                                <span>◀</span>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php
        $orphan_subcategories = [];
        foreach ($categories as $cat) {
            if ($cat['parent_id'] !== null && !isset($sub_categories[$cat['parent_id']])) {
                $orphan_subcategories[] = $cat;
            }
        }
        ?>
        
        <?php if (!empty($orphan_subcategories)): ?>
            <div class="section-header">
                <div class="section-icon">📄</div>
                <h2 class="section-title">زیرمجموعه‌ها</h2>
                <span class="section-count"><?= count($orphan_subcategories) ?> زیرمجموعه</span>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($orphan_subcategories as $cat): ?>
                    <a href="books.php?category=<?= $cat['category_id'] ?>" class="category-card">
                        <div class="card-header">
                            <div class="card-icon"><?= htmlspecialchars($cat['category_icon'] ?? '📄') ?></div>
                            <div class="card-title-section">
                                <div class="card-title"><?= htmlspecialchars($cat['category_name']) ?></div>
                                <span class="card-book-count">
                                    <span>📚</span> <?= $cat['book_count'] ?> کتاب
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <span class="card-link">
                                مشاهده کتاب‌ها
                                <span>◀</span>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2.5rem;">
            <a href="books.php" class="view-all-link">
                <span>📚</span>
                مشاهده همه کتاب‌ها
                <span>◀</span>
            </a>
        </div>
        
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>