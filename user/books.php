<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

$categories_query = "SELECT * FROM categories ORDER BY category_name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$selected_cat_name = '';
$selected_cat_icon = '📚';
if ($category_filter > 0) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $category_filter) {
            $selected_cat_name = $cat['category_name'];
            $selected_cat_icon = $cat['category_icon'] ?? '📚';
            break;
        }
    }
}

$books_sql = "SELECT b.*, c.category_name, c.category_icon, c.category_slug 
              FROM books b 
              LEFT JOIN categories c ON b.category_id = c.category_id 
              WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $books_sql .= " AND (b.name LIKE ? OR b.author LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category_filter > 0) {
    $books_sql .= " AND b.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($sort_order == 'price_asc') {
    $books_sql .= " ORDER BY b.price ASC";
} elseif ($sort_order == 'price_desc') {
    $books_sql .= " ORDER BY b.price DESC";
} elseif ($sort_order == 'name_asc') {
    $books_sql .= " ORDER BY b.name ASC";
} else {
    $books_sql .= " ORDER BY b.created_at DESC";
}

$stmt = $conn->prepare($books_sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$books_result = $stmt->get_result();
$total_books = $books_result->num_rows;

$subcategories = [];
if ($category_filter > 0) {
    $subcats_query = $conn->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY category_name");
    $subcats_query->bind_param('i', $category_filter);
    $subcats_query->execute();
    $subcats_result = $subcats_query->get_result();
    while ($sub = $subcats_result->fetch_assoc()) {
        $subcategories[] = $sub;
    }
    $subcats_query->close();
}

$page_title = $category_filter > 0 ? $selected_cat_name . " | کتاب‌ها" : "کتاب‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/book.css">

<div class="books-container">
    
    <?php if ($category_filter > 0): ?>
        <div class="breadcrumb">
            <a href="index.php">🏠 خانه</a>
            <span>◀</span>
            <a href="categories.php">📂 دسته‌بندی‌ها</a>
            <span>◀</span>
            <span style="color: #ffd700;">
                <?= htmlspecialchars($selected_cat_icon) ?> 
                <?= htmlspecialchars($selected_cat_name) ?>
            </span>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title-section">
            <div class="page-icon-box">
                <span style="font-size: 1rem;"><?= $category_filter > 0 ? $selected_cat_icon : '📚' ?></span>
            </div>
            <h1 class="page-title">
                <?= $category_filter > 0 ? htmlspecialchars($selected_cat_name) : 'کتاب‌ها' ?>
            </h1>
            <?php if ($total_books > 0): ?>
                <span class="book-count-badge">
                    <?= $total_books ?> کتاب
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($category_filter > 0): ?>
            <a href="books.php" class="back-to-all">
                <span>📂</span>
                <span>همه کتاب‌ها</span>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($subcategories)): ?>
        <div class="subcategories-bar">
            <span class="subcategories-label">📁 زیرمجموعه‌ها:</span>
            <div class="subcategories-tags">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="?category=<?= $sub['category_id'] ?>" class="subcategory-tag">
                        <?= htmlspecialchars($sub['category_icon'] ?? '📄') ?>
                        <?= htmlspecialchars($sub['category_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="filter-panel">
        <form id="filterForm" method="GET" action="" class="filter-form">
            <div class="search-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       name="search" 
                       placeholder="جستجوی عنوان یا نویسنده..." 
                       value="<?= htmlspecialchars($search_query) ?>"
                       class="search-input">
            </div>

            <select name="category" class="filter-select">
                <option value="0">همه دسته‌بندی‌ها</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['category_icon'] ?? '📚') . ' ' . htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="filter-select">
                <option value="latest" <?= ($sort_order == 'latest') ? 'selected' : ''; ?>>جدیدترین</option>
                <option value="price_asc" <?= ($sort_order == 'price_asc') ? 'selected' : ''; ?>>قیمت: کم به زیاد</option>
                <option value="price_desc" <?= ($sort_order == 'price_desc') ? 'selected' : ''; ?>>قیمت: زیاد به کم</option>
                <option value="name_asc" <?= ($sort_order == 'name_asc') ? 'selected' : ''; ?>>عنوان: الف تا ی</option>
            </select>

            <button type="submit" class="filter-btn">
                <span>🔍</span>
                <span>اعمال فیلتر</span>
            </button>

            <?php if ($category_filter > 0 || !empty($search_query) || $sort_order != 'latest'): ?>
                <a href="books.php" class="reset-btn">
                    <span>✖️</span>
                    <span>پاک کردن</span>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_SESSION['order_success'])): ?>
        <div class="success-message">
            <span>✅</span>
            <span><?= $_SESSION['order_success'] ?></span>
        </div>
        <?php unset($_SESSION['order_success']); ?>
    <?php endif; ?>

    <?php if ($books_result->num_rows > 0): ?>
        <div class="books-grid">
            <?php while ($book = $books_result->fetch_assoc()): ?>
                <div class="book-card">
                    <div class="book-image-container">
                        <img src="../<?= htmlspecialchars($book['image']) ?>" 
                             alt="<?= htmlspecialchars($book['name']) ?>" 
                             class="book-image"
                             onerror="this.src='../static/images/placeholder-book.jpg'">
                        <div class="book-badge">
                            <span><?= htmlspecialchars($book['category_icon'] ?? '📚') ?></span>
                            <?= htmlspecialchars($book['category_name'] ?? 'سایر') ?>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">
                            <?= htmlspecialchars($book['name']) ?>
                        </h3>
                        <div class="book-author">
                            <span>✍️</span>
                            <?= htmlspecialchars($book['author'] ?? 'نویسنده نامشخص') ?>
                        </div>
                        <p class="book-description">
                            <?php 
                                $desc = strip_tags($book['description'] ?? $book['caption'] ?? '');
                                echo mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '...' : $desc;
                            ?>
                        </p>
                        <div class="book-footer">
                            <div class="book-price">
                                <span class="price-value"><?= number_format($book['price']) ?></span>
                                <span class="price-unit">تومان</span>
                            </div>
                            <div class="book-actions">
                                <a href="book_details.php?id=<?= $book['book_id'] ?>" class="btn-details">
                                    <span>ℹ️</span>
                                    <span>جزئیات</span>
                                </a>
                                <?php if ($isLoggedIn): ?>
                                    <button onclick="addToCart(<?= $book['book_id'] ?>)" class="btn-buy">
                                        <span>🛒</span>
                                        <span>خرید</span>
                                    </button>
                                <?php else: ?>
                                    <a href="/new-web/authentication/login.php?redirect=books.php" class="btn-login">
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
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon-box">
                <span class="empty-icon">🔍</span>
            </div>
            <h3 class="empty-title">کتابی یافت نشد</h3>
            <p class="empty-text">
                <?php if (!empty($search_query) || $category_filter > 0): ?>
                    با فیلترهای انتخاب شده کتابی پیدا نکردیم.
                <?php else: ?>
                    در حال حاضر کتابی در فروشگاه موجود نیست.
                <?php endif; ?>
            </p>
            <div class="empty-actions">
                <a href="books.php" class="btn-show-all">
                    <span>🔄</span>
                    <span>نمایش همه کتاب‌ها</span>
                </a>
                <?php if ($category_filter > 0): ?>
                    <a href="categories.php" class="btn-show-all" style="background: rgba(255,215,0,0.1);">
                        <span>📂</span>
                        <span>مشاهده دسته‌بندی‌ها</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="../static/js/book.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$stmt->close();
$conn->close();
?>