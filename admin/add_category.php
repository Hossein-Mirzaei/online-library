<?php
include '../database/config.php';

requirePermission('list_categories');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_slug = trim($_POST['category_slug'] ?? '');
    $category_icon = trim($_POST['category_icon'] ?? '📁');
    $category_description = trim($_POST['category_description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    
    $errors = [];
    
    if (empty($category_name)) {
        $errors[] = 'نام دسته‌بندی الزامی است.';
    }
    
    if (empty($category_slug)) {
        $category_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($category_name));
    } else {
        $category_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($category_slug));
    }
    
    $check = $conn->prepare("SELECT category_id FROM categories WHERE category_slug = ?");
    $check->bind_param('s', $category_slug);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = 'این slug قبلاً استفاده شده است.';
    }
    $check->close();
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO categories (category_name, category_slug, category_icon, category_description, parent_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssssi', $category_name, $category_slug, $category_icon, $category_description, $parent_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'دسته‌بندی با موفقیت اضافه شد.';
            $_SESSION['message_type'] = 'success';
            header("Location: list_categories.php");
            exit();
        } else {
            $errors[] = 'خطا در افزودن دسته‌بندی.';
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $error = implode(' ', $errors);
    }
}

$parent_categories = [];
$result = $conn->query("SELECT category_id, category_name, category_icon FROM categories WHERE parent_id IS NULL ORDER BY category_name ASC");
while ($row = $result->fetch_assoc()) {
    $parent_categories[] = $row;
}

$page_title = "افزودن دسته‌بندی";

ob_start();
?>

<link rel="stylesheet" href="../static/css/add_category.css">

<div class="main-container">
    <div class="form-container">
        <h2 class="form-title">
            <span>➕</span>
            افزودن دسته‌بندی جدید
        </h2>
        
        <?php if (isset($error)): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="categoryForm">
            <div class="form-group">
                <label class="form-label">📝 نام دسته‌بندی</label>
                <input type="text" name="category_name" class="form-input" placeholder="مثال: رمان" required value="<?= htmlspecialchars($_POST['category_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">🔗 Slug (شناسه انگلیسی)</label>
                <input type="text" name="category_slug" class="form-input" placeholder="مثال: novel (اختیاری - خودکار ساخته می‌شود)" value="<?= htmlspecialchars($_POST['category_slug'] ?? '') ?>">
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">
                    فقط حروف انگلیسی، اعداد و خط تیره (-) مجاز است
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">🎨 آیکون دسته‌بندی</label>
                <div class="icon-picker-container">
                    <button type="button" class="icon-picker-btn" id="openEmojiPicker">
                        <span>😊</span>
                        <span>انتخاب ایموجی</span>
                    </button>
                    <div class="selected-icon-preview" id="iconPreview">📁</div>
                    <input type="hidden" name="category_icon" id="iconInput" value="📁">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">📂 دسته‌بندی والد (اختیاری)</label>
                <select name="parent_id" class="form-select">
                    <option value="">بدون والد (دسته‌بندی اصلی)</option>
                    <?php foreach ($parent_categories as $parent): ?>
                        <option value="<?= $parent['category_id'] ?>" <?= ($_POST['parent_id'] ?? '') == $parent['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['category_icon'] ?? '📁') ?> <?= htmlspecialchars($parent['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">📄 توضیحات (اختیاری)</label>
                <textarea name="category_description" class="form-textarea" placeholder="توضیح مختصر درباره این دسته‌بندی"><?= htmlspecialchars($_POST['category_description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <span>💾</span> ذخیره دسته‌بندی
                </button>
                <a href="list_categories.php" class="btn-cancel">
                    <span>✖️</span> انصراف
                </a>
            </div>
        </form>
    </div>
</div>

<div class="emoji-modal" id="emojiModal">
    <div class="emoji-modal-content">
        <div class="emoji-modal-header">
            <span class="emoji-modal-title">😊 انتخاب ایموجی</span>
            <button class="emoji-modal-close" id="closeEmojiModal">&times;</button>
        </div>
        <div class="emoji-categories" id="emojiCategories">
            <button class="emoji-cat-btn active" data-cat="all">همه</button>
            <button class="emoji-cat-btn" data-cat="faces">😊 چهره‌ها</button>
            <button class="emoji-cat-btn" data-cat="animals">🐶 حیوانات</button>
            <button class="emoji-cat-btn" data-cat="food">🍕 غذا</button>
            <button class="emoji-cat-btn" data-cat="activities">⚽ فعالیت‌ها</button>
            <button class="emoji-cat-btn" data-cat="travel">🚗 سفر</button>
            <button class="emoji-cat-btn" data-cat="objects">💡 اشیاء</button>
            <button class="emoji-cat-btn" data-cat="symbols">❤️ نمادها</button>
            <button class="emoji-cat-btn" data-cat="flags">🏁 پرچم‌ها</button>
        </div>
        <div class="emoji-grid" id="emojiGrid">
        </div>
    </div>
</div>

<script src="../static/js/add_category.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>