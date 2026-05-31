<?php
include '../database/config.php';

requirePermission('list_book');

$categories_for_filter = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
while ($cat = $cat_result->fetch_assoc()) {
    $categories_for_filter[] = $cat;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(b.name LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($category_filter > 0) {
    $where_conditions[] = "b.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$allowed_sorts = ['name', 'author', 'price', 'publication_year', 'created_at'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'created_at';

$query = "SELECT b.*, c.category_name, c.category_icon 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.category_id 
          $where_clause 
          ORDER BY b.$sort_by $sort_order";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$total_books = count($books);
$page_title = "مدیریت کتاب‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/list_book.css">

<div class="main-container">
    
    <div class="add-book-button">
        <a href="add_book.php" class="add-book-btn">
            <span>➕</span>
            <span>افزودن کتاب جدید</span>
        </a>
    </div>

    <div class="search-filter-section">
        <form method="GET" class="search-filter-grid">
            <div class="form-group">
                <label class="form-label" for="search">🔍 جستجوی کتاب</label>
                <input type="text" id="search" name="search" class="search-input" 
                       placeholder="نام کتاب، نویسنده یا توضیحات..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="category">📂 دسته‌بندی</label>
                <select id="category" name="category" class="filter-select">
                    <option value="0" <?= $category_filter === 0 ? 'selected' : '' ?>>همه دسته‌بندی‌ها</option>
                    <?php foreach ($categories_for_filter as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $category_filter === $cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_icon'] ?? '📁') ?> <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="sort">📊 مرتب‌سازی</label>
                <select id="sort" name="sort" class="sort-select">
                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>تاریخ افزودن</option>
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>نام کتاب</option>
                    <option value="author" <?= $sort_by === 'author' ? 'selected' : '' ?>>نویسنده</option>
                    <option value="price" <?= $sort_by === 'price' ? 'selected' : '' ?>>قیمت</option>
                    <option value="publication_year" <?= $sort_by === 'publication_year' ? 'selected' : '' ?>>سال انتشار</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <div class="button-group">
                    <button type="submit" class="btn-search">
                        <span>🔍</span> جستجو
                    </button>
                    <a href="list_book.php" class="btn-reset">
                        <span>🔄</span> بازنشانی
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="results-info">
        <div class="results-count">
            <span>📚</span> <?= $total_books ?> کتاب پیدا شد
        </div>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-tag">
                    <span>🔍</span> <?= htmlspecialchars($search) ?>
                </span>
            <?php endif; ?>
            <?php if ($category_filter > 0): ?>
                <?php 
                $selected_cat_name = '';
                foreach ($categories_for_filter as $cat) {
                    if ($cat['category_id'] == $category_filter) {
                        $selected_cat_name = $cat['category_name'];
                        break;
                    }
                }
                ?>
                <span class="filter-tag">
                    <span>📂</span> <?= htmlspecialchars($selected_cat_name) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="books-grid">
        <?php if (empty($books)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>هیچ کتابی یافت نشد</h3>
                <p>
                    <?php if (!empty($search) || $category_filter > 0): ?>
                        با فیلترهای انتخاب شده هیچ کتابی یافت نشد. لطفاً فیلترها را تغییر دهید.
                    <?php else: ?>
                        هنوز هیچ کتابی در سیستم ثبت نشده است.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-image-container">
                        <img src="../<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['name']) ?>" class="book-image" onerror="this.src='../static/images/placeholder-book.jpg'">
                        <span class="book-category-badge">
                            <span><?= htmlspecialchars($book['category_icon'] ?? '📚') ?></span>
                            <?= htmlspecialchars($book['category_name'] ?? 'سایر') ?>
                        </span>
                    </div>
                    <div class="book-content">
                        <h3 class="book-title"><?= htmlspecialchars($book['name']) ?></h3>
                        <p class="book-author"><span>✍️</span> <?= htmlspecialchars($book['author']) ?></p>
                        <p class="book-year"><span>📅</span> <?= $book['publication_year'] ?></p>
                        <p class="book-price"><?= number_format($book['price']) ?> تومان</p>
                        <div class="book-actions">
                            <a href="edit_book.php?id=<?= $book['book_id'] ?>" class="book-action-btn edit-btn">
                                <span>✏️</span> ویرایش
                            </a>
                            <button class="book-action-btn delete-btn" data-id="<?= $book['book_id'] ?>" data-name="<?= htmlspecialchars($book['name']) ?>">
                                <span>🗑️</span> حذف
                            </button>
                            <button class="book-action-btn details-btn" 
                                    data-caption="<?= htmlspecialchars($book['caption'] ?? '') ?>"
                                    data-description="<?= htmlspecialchars($book['description'] ?? '') ?>">
                                <span>ℹ️</span> جزئیات
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">📄 جزئیات کتاب</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <h4>📝 کپشن</h4>
            <p id="modalCaption"></p>
            <h4>📖 توضیحات کامل</h4>
            <p id="modalDescription" class="modal-description"></p>
        </div>
    </div>
</div>

<div class="modal" id="deleteModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #ef4444;">⚠️ تأیید حذف</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <p id="deleteMessage" style="font-size: 1rem; margin-bottom: 1.5rem;">آیا از حذف این کتاب اطمینان دارید؟</p>
            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button onclick="closeDeleteModal()" style="padding: 0.6rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; cursor: pointer;">انصراف</button>
                <button id="confirmDeleteBtn" style="padding: 0.6rem 1.5rem; background: #ef4444; border: none; border-radius: 8px; color: white; cursor: pointer;">حذف</button>
            </div>
        </div>
    </div>
</div>

<script src="../static/js/list_book.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>