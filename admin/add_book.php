<?php
include '../database/config.php';

requirePermission('list_book');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['bookName'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publication_year = intval($_POST['publicationYear'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = intval($_POST['price'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    
    if (empty($name)) $errors[] = 'نام کتاب الزامی است.';
    if (empty($author)) $errors[] = 'نام نویسنده الزامی است.';
    if ($publication_year < 1000 || $publication_year > 2100) $errors[] = 'سال انتشار نامعتبر است.';
    if ($category_id <= 0) $errors[] = 'لطفاً دسته‌بندی را انتخاب کنید.';
    if ($price < 0) $errors[] = 'قیمت نامعتبر است.';
    if (empty($caption)) $errors[] = 'کپشن الزامی است.';
    if (empty($description)) $errors[] = 'توضیحات الزامی است.';
    
    $image_path = '';
    if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bookImage'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'فرمت تصویر باید JPG، PNG یا GIF باشد.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_dir = '../uploads/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path = 'uploads/' . $new_filename;
            } else {
                $errors[] = 'خطا در آپلود تصویر.';
            }
        }
    } else {
        $errors[] = 'لطفاً تصویر کتاب را انتخاب کنید.';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO books (name, author, publication_year, category_id, price, caption, description, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param('ssiiisss', $name, $author, $publication_year, $category_id, $price, $caption, $description, $image_path);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = '✅ کتاب با موفقیت اضافه شد.';
                $_SESSION['message_type'] = 'success';
                header("Location: add_book.php");
                exit();
            } else {
                $error_message = 'خطا در افزودن کتاب: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = 'خطا در آماده‌سازی درخواست.';
        }
    } else {
        $error_message = implode(' ', $errors);
    }
}

$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

$page_title = "افزودن کتاب جدید";

ob_start();
?>

<link rel="stylesheet" href="../static/css/add_book.css">

<div class="main-container">
    <div class="form-container">
        <div class="form-header">
            <h2 class="form-title">
                <span>📖</span>
                افزودن کتاب جدید
            </h2>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?= $_SESSION['message_type'] ?? 'success' ?>">
                <span><?= $_SESSION['message_type'] === 'success' ? '✅' : '❌' ?></span>
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <span>❌</span>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form id="addBookForm" method="POST" action="" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="bookName">
                        <span>📚</span> نام کتاب
                    </label>
                    <input type="text" id="bookName" name="bookName" class="form-control" 
                           placeholder="مثال: تاریخ بیهقی" 
                           value="<?= htmlspecialchars($_POST['bookName'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="author">
                        <span>✍️</span> نویسنده
                    </label>
                    <input type="text" id="author" name="author" class="form-control" 
                           placeholder="مثال: ابوالفضل بیهقی" 
                           value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="publicationYear">
                        <span>📅</span> سال انتشار
                    </label>
                    <input type="number" id="publicationYear" name="publicationYear" class="form-control" 
                           placeholder="مثال: ۱۴۰۲" min="1000" max="2100" 
                           value="<?= htmlspecialchars($_POST['publicationYear'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="category_id">
                        <span>📂</span> دسته‌بندی
                    </label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="" disabled <?= !isset($_POST['category_id']) ? 'selected' : '' ?>>یک دسته‌بندی انتخاب کنید</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" 
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_icon'] ?? '📁') ?> <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price">
                        <span>💰</span> قیمت (تومان)
                    </label>
                    <input type="number" id="price" name="price" class="form-control" 
                           placeholder="مثال: ۱۲۰۰۰۰" min="0" 
                           value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">
                    <span>📝</span>
                    توضیحات کتاب
                </h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label" for="caption">
                            <span>📌</span> کپشن (معرفی کوتاه)
                        </label>
                        <textarea id="caption" name="caption" class="form-control" 
                                  placeholder="یک معرفی کوتاه و جذاب از کتاب بنویسید..." 
                                  rows="2" required><?= htmlspecialchars($_POST['caption'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label" for="description">
                            <span>📄</span> توضیحات کامل
                        </label>
                        <textarea id="description" name="description" class="form-control" 
                                  placeholder="توضیحات کامل کتاب را وارد کنید..." 
                                  rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">
                    <span>🖼️</span>
                    تصویر کتاب
                </h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <div class="file-input-wrapper">
                            <input type="file" id="bookImage" name="bookImage" class="file-input" accept="image/*" required>
                            <label for="bookImage" class="file-input-label">
                                <span>📤</span>
                                انتخاب تصویر
                            </label>
                            <span class="file-name" id="fileName">
                                <span>📁</span>
                                هیچ فایلی انتخاب نشده است
                            </span>
                        </div>
                        <div class="file-hint">
                            <span>ℹ️</span>
                            فرمت‌های مجاز: JPG, PNG, GIF | حداکثر حجم: ۲ مگابایت
                        </div>
                        
                        <div id="imagePreview" class="image-preview-container" style="margin-top: 1rem; display: none;">
                            <img id="previewImg" src="" alt="پیش‌نمایش" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>➕</span>
                    افزودن کتاب
                </button>
                <a href="list_book.php" class="btn btn-secondary">
                    <span>📋</span>
                    بازگشت به لیست
                </a>
                <button type="reset" class="btn btn-reset" onclick="resetForm()">
                    <span>🔄</span>
                    پاک کردن فرم
                </button>
            </div>
        </form>
    </div>
</div>

<script src="../static/js/add_book.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>