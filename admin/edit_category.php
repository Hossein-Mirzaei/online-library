<?php
include '../database/config.php';

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'owner' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: /new-web/authentication/login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list_categories.php");
    exit();
}

$category_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param('i', $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list_categories.php");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

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
    
    $check = $conn->prepare("SELECT category_id FROM categories WHERE category_slug = ? AND category_id != ?");
    $check->bind_param('si', $category_slug, $category_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = 'این slug قبلاً استفاده شده است.';
    }
    $check->close();
    
    if ($parent_id == $category_id) {
        $errors[] = 'یک دسته‌بندی نمی‌تواند والد خودش باشد.';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE categories 
            SET category_name = ?, category_slug = ?, category_icon = ?, category_description = ?, parent_id = ? 
            WHERE category_id = ?
        ");
        $stmt->bind_param('ssssii', $category_name, $category_slug, $category_icon, $category_description, $parent_id, $category_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'دسته‌بندی با موفقیت ویرایش شد.';
            $_SESSION['message_type'] = 'success';
            header("Location: list_categories.php");
            exit();
        } else {
            $errors[] = 'خطا در ویرایش دسته‌بندی.';
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $error = implode(' ', $errors);
    }
}

$parent_categories = [];
$result = $conn->query("
    SELECT category_id, category_name, category_icon 
    FROM categories 
    WHERE category_id != $category_id AND parent_id IS NULL 
    ORDER BY category_name ASC
");
while ($row = $result->fetch_assoc()) {
    $parent_categories[] = $row;
}

$page_title = "ویرایش دسته‌بندی";

ob_start();
?>

<link rel="stylesheet" href="../static/css/edit_category.css">

<div class="main-container">
    <div class="form-container">
        <h2 class="form-title">
            <span>✏️</span>
            ویرایش دسته‌بندی
        </h2>
        
        <?php if (isset($error)): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="categoryForm">
            <div class="form-group">
                <label class="form-label">📝 نام دسته‌بندی</label>
                <input type="text" name="category_name" class="form-input" placeholder="مثال: رمان" required value="<?= htmlspecialchars($category['category_name']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">🔗 Slug (شناسه انگلیسی)</label>
                <input type="text" name="category_slug" class="form-input" placeholder="مثال: novel" value="<?= htmlspecialchars($category['category_slug']) ?>">
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
                    <div class="selected-icon-preview" id="iconPreview"><?= htmlspecialchars($category['category_icon'] ?? '📁') ?></div>
                    <input type="hidden" name="category_icon" id="iconInput" value="<?= htmlspecialchars($category['category_icon'] ?? '📁') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">📂 دسته‌بندی والد (اختیاری)</label>
                <select name="parent_id" class="form-select">
                    <option value="">بدون والد (دسته‌بندی اصلی)</option>
                    <?php foreach ($parent_categories as $parent): ?>
                        <option value="<?= $parent['category_id'] ?>" <?= $category['parent_id'] == $parent['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['category_icon'] ?? '📁') ?> <?= htmlspecialchars($parent['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">📄 توضیحات (اختیاری)</label>
                <textarea name="category_description" class="form-textarea" placeholder="توضیح مختصر درباره این دسته‌بندی"><?= htmlspecialchars($category['category_description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <span>💾</span> ذخیره تغییرات
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

<script src="../static/js/edit_category.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>