<?php
include '../database/config.php';

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'owner' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: /new-web/authentication/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = intval($_POST['book_id']);
    $name = trim($_POST['name']);
    $author = trim($_POST['author']);
    $publication_year = intval($_POST['publication_year']);
    $caption = trim($_POST['caption']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $price = intval($_POST['price']);

    $image = $_FILES['image']['name'] ?? '';
    $target_dir = "../uploads/";
    $uploadOk = 1;
    $target_file = '';
    $error = '';

    if (empty($image)) {
        $sql = "SELECT image FROM books WHERE book_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $oldImage = $result->fetch_assoc()['image'];
            $target_file = $oldImage;
        }
    } else {
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($imageFileType, $valid_extensions)) {
            $error = "فقط فرمت‌های JPG, JPEG, PNG & GIF مجاز هستند.";
            $uploadOk = 0;
        }

        if ($_FILES['image']['size'] > 2000000) {
            $error = "اندازه فایل نباید بیشتر از 2MB باشد.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            $sql = "SELECT image FROM books WHERE book_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $oldImage = $result->fetch_assoc()['image'];
                if (!empty($oldImage) && file_exists("../" . $oldImage)) {
                    unlink("../" . $oldImage); 
                }
            }

            $new_filename = time() . '_' . uniqid() . '.' . $imageFileType;
            $target_file = 'uploads/' . $new_filename;
            $full_path = $target_dir . $new_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                $error = "خطا در آپلود فایل.";
                $uploadOk = 0;
            }
        }
    }

    if ($uploadOk == 1 && empty($error)) {
        $sql = "UPDATE books SET name=?, author=?, publication_year=?, category_id=?, caption=?, description=?, image=?, price=? WHERE book_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiisssii', $name, $author, $publication_year, $category_id, $caption, $description, $target_file, $price, $book_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = 'کتاب با موفقیت ویرایش شد.';
            $_SESSION['message_type'] = 'success';
            header("Location: list_book.php");
            exit();
        } else {
            $error = "خطا در بروزرسانی کتاب: " . $stmt->error;
        }
    }
}

if (isset($_GET['id'])) {
    $book_id = intval($_GET['id']); 

    $sql = "SELECT b.*, c.category_name, c.category_icon 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.category_id 
            WHERE b.book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        header("Location: list_book.php?error=book_not_found");
        exit; 
    }
} else {
    header("Location: list_book.php?error=no_id");
    exit;
}

$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
while ($cat = $cat_result->fetch_assoc()) {
    $categories[] = $cat;
}

$current_year = date('Y');

$page_title = "ویرایش کتاب";

ob_start();
?>

<link rel="stylesheet" href="../static/css/edit_book.css">

<div class="main-container">
    <div class="form-container">
        <div class="form-header">
            <h2 class="form-title">
                <span>✏️</span>
                ویرایش کتاب
            </h2>
            <div class="book-info-badge">
                <span>📚</span>
                <?= htmlspecialchars($book['name']) ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">
                        <span>📚</span> نام کتاب
                    </label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($book['name']) ?>" placeholder="نام کتاب" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="author">
                        <span>✍️</span> نویسنده
                    </label>
                    <input type="text" id="author" name="author" class="form-control" 
                           value="<?= htmlspecialchars($book['author']) ?>" placeholder="نام نویسنده" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="publication_year">
                        <span>📅</span> سال انتشار
                    </label>
                    <input type="number" id="publication_year" name="publication_year" class="form-control" 
                           value="<?= $book['publication_year'] ?>" min="1000" max="<?= $current_year ?>" placeholder="مثال: ۱۴۰۲" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">
                        <span>📂</span> دسته‌بندی
                    </label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="" disabled>یک دسته‌بندی انتخاب کنید</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= ($book['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
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
                           value="<?= $book['price'] ?>" min="0" placeholder="مثال: ۱۲۰۰۰۰" required>
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
                                  rows="2" placeholder="یک معرفی کوتاه و جذاب از کتاب..." required><?= htmlspecialchars($book['caption']) ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label" for="description">
                            <span>📄</span> توضیحات کامل
                        </label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="5" placeholder="توضیحات کامل کتاب را وارد کنید..." required><?= htmlspecialchars($book['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="image-upload-section">
                <h3 class="section-title">
                    <span>🖼️</span>
                    تصویر کتاب
                </h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <div class="file-input-wrapper">
                            <input type="file" id="image" name="image" class="file-input" 
                                   accept="image/*">
                            <label for="image" class="file-input-button">
                                <span>📤</span>
                                انتخاب تصویر جدید
                            </label>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">(اختیاری)</span>
                        </div>

                        <div class="image-preview-container">
                            <div class="image-preview">
                                <img id="currentImage" src="../<?= $book['image'] ?>" alt="تصویر فعلی" onerror="this.src='../static/images/placeholder-book.jpg'">
                                <div class="image-preview-label">
                                    <span>📸</span> تصویر فعلی
                                </div>
                            </div>
                            
                            <div class="arrow-icon">
                                <span>➡️</span>
                            </div>
                            
                            <div class="image-preview">
                                <img id="newImage" src="" alt="تصویر جدید" style="display: none;">
                                <div class="image-preview-label">
                                    <span>🆕</span> تصویر جدید
                                </div>
                            </div>
                        </div>
                        
                        <div class="file-hint">
                            <span>ℹ️</span>
                            فرمت‌های مجاز: JPG, PNG, GIF | حداکثر حجم: ۲ مگابایت
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span>
                    ذخیره تغییرات
                </button>
                <a href="list_book.php" class="btn btn-secondary">
                    <span>✖️</span>
                    انصراف
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    window.APP_CONFIG = {
        currentYear: <?= $current_year ?>,
        baseUrl: '<?= htmlspecialchars(dirname($_SERVER['PHP_SELF'])) ?>'
    };
</script>

<script src="../static/js/edit_book.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>