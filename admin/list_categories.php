<?php
include '../database/config.php';

requirePermission('list_categories');

if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);

    $check = $conn->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
    $check->bind_param('i', $category_id);
    $check->execute();
    $result = $check->get_result();
    $count = $result->fetch_assoc()['count'];
    $check->close();

    if ($count > 0) {
        $error = "این دسته‌بندی دارای $count کتاب است و قابل حذف نیست.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $category_id);
        if ($stmt->execute()) {
            $success = "دسته‌بندی با موفقیت حذف شد.";
        } else {
            $error = "خطا در حذف دسته‌بندی.";
        }
        $stmt->close();
    }
}

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

$page_title = "مدیریت دسته‌بندی‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/list_categories.css">

<div class="main-container">

    <div class="page-header">
        <div class="page-title-section">
            <div class="page-icon">
                <span>📂</span>
            </div>
            <h1 class="page-title">مدیریت دسته‌بندی‌ها</h1>
            <span class="category-count"><?= count($categories) ?> دسته</span>
        </div>
        <a href="add_category.php" class="btn-add">
            <span>➕</span>
            <span>افزودن دسته‌بندی جدید</span>
        </a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <span>✅</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <span>❌</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="categories-grid">
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>هیچ دسته‌بندی یافت نشد</h3>
                <p>هنوز هیچ دسته‌بندی در سیستم ثبت نشده است.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-icon-large">
                            <?= htmlspecialchars($cat['category_icon'] ?? '📁') ?>
                        </div>
                        <div class="category-info">
                            <div class="category-name"><?= htmlspecialchars($cat['category_name']) ?></div>
                            <div class="category-slug">slug: <?= htmlspecialchars($cat['category_slug']) ?></div>
                        </div>
                    </div>

                    <?php if (!empty($cat['category_description'])): ?>
                        <div class="category-description">
                            <?= htmlspecialchars($cat['category_description']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="category-stats">
                        <div class="stat-item">
                            <span>📚</span>
                            <span class="stat-value"><?= $cat['book_count'] ?></span>
                            <span class="stat-label">کتاب</span>
                        </div>
                        <?php if (!empty($cat['parent_id'])): ?>
                            <div class="stat-item">
                                <span>📁</span>
                                <span class="stat-label">زیرمجموعه</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="category-actions">
                        <a href="edit_category.php?id=<?= $cat['category_id'] ?>" class="btn-edit">
                            <span>✏️</span> ویرایش
                        </a>
                        <?php if ($cat['book_count'] > 0): ?>
                            <button class="btn-delete disabled" disabled title="این دسته‌بندی دارای کتاب است و قابل حذف نیست">
                                <span>🔒</span> حذف
                            </button>
                        <?php else: ?>
                            <button class="btn-delete" onclick="confirmDelete(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['category_name']) ?>')">
                                <span>🗑️</span> حذف
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="confirmDialog" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #1a1a1a; border-radius: 16px; width: 90%; max-width: 380px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255,215,0,0.15);">
        <div style="width: 60px; height: 60px; margin: 0 auto 1rem; border-radius: 50%; background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; font-size: 2rem;">⚠️</div>
        <h3 style="color: #ef4444; font-size: 1.2rem; margin-bottom: 0.5rem;">تأیید حذف</h3>
        <p id="deleteMessage" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 1.25rem;">آیا از حذف این دسته‌بندی اطمینان دارید؟</p>
        <div style="display: flex; gap: 0.75rem;">
            <button onclick="hideDialog()" style="flex: 1; padding: 0.7rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; cursor: pointer;">انصراف</button>
            <button id="confirmBtn" style="flex: 1; padding: 0.7rem; background: #ef4444; border: none; border-radius: 8px; color: white; cursor: pointer;">حذف</button>
        </div>
    </div>
</div>

<script src="../static/js/list_categories.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>