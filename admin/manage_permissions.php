<?php
// active: /new-web/admin/manage_permissions.php
include '../database/config.php';

// فقط owner میتونه دسترسی‌ها رو مدیریت کنه
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'owner') {
    header("Location: /new-web/authentication/login.php");
    exit();
}

$message = '';
$messageType = '';

// ذخیره تغییرات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $admin_id = intval($_POST['admin_id']);
    $pages = getAllDefinedPages();
    
    // اول همه دسترسی‌های قبلی این ادمین رو پاک کن
    $deleteStmt = $conn->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
    $deleteStmt->bind_param('i', $admin_id);
    $deleteStmt->execute();
    
    // بعد دسترسی‌های جدید رو اضافه کن
    $insertStmt = $conn->prepare("INSERT INTO admin_permissions (admin_id, page_name, can_access) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE can_access = 1");
    
    foreach ($pages as $page_key => $page_label) {
        if (isset($_POST['perm_' . $page_key])) {
            $insertStmt->bind_param('is', $admin_id, $page_key);
            $insertStmt->execute();
        }
    }
    
    $message = '✅ دسترسی‌ها با موفقیت ذخیره شد.';
    $messageType = 'success';
}

// گرفتن لیست ادمین‌ها
$admins_query = "SELECT user_id, first_name, last_name, email, created_at FROM users WHERE user_type = 'admin' ORDER BY created_at DESC";
$admins_result = $conn->query($admins_query);
$admins = [];
while ($row = $admins_result->fetch_assoc()) {
    $admins[] = $row;
}

// ادمین انتخاب شده
$selected_admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : ($admins[0]['user_id'] ?? 0);
$selected_admin = null;

$permissions = [];
if ($selected_admin_id > 0) {
    $perm_query = "SELECT page_name, can_access FROM admin_permissions WHERE admin_id = ?";
    $perm_stmt = $conn->prepare($perm_query);
    $perm_stmt->bind_param('i', $selected_admin_id);
    $perm_stmt->execute();
    $perm_result = $perm_stmt->get_result();
    
    while ($row = $perm_result->fetch_assoc()) {
        $permissions[$row['page_name']] = $row['can_access'];
    }
    
    // اطلاعات ادمین انتخاب شده
    $admin_query = "SELECT * FROM users WHERE user_id = ? AND user_type = 'admin'";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bind_param('i', $selected_admin_id);
    $admin_stmt->execute();
    $selected_admin = $admin_stmt->get_result()->fetch_assoc();
}

$all_pages = getAllDefinedPages();
$page_title = "مدیریت دسترسی ادمین‌ها";
ob_start();
?>

<link rel="stylesheet" href="../static/css/manage_permissions.css">

<div class="main-container">
    
    <?php if ($message): ?>
    <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
        <span><?= $messageType === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>

    <div class="permissions-layout">
        <!-- لیست ادمین‌ها -->
        <div class="admins-list-panel">
            <h3>🛡️ ادمین‌ها</h3>
            <div class="admins-list">
                <?php foreach ($admins as $admin): ?>
                <a href="?admin_id=<?= $admin['user_id'] ?>" 
                   class="admin-item <?= $selected_admin_id == $admin['user_id'] ? 'active' : '' ?>">
                    <div class="admin-avatar">
                        <?= mb_substr($admin['first_name'], 0, 1) ?>
                    </div>
                    <div class="admin-info">
                        <span class="admin-name"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></span>
                        <span class="admin-date"><?= date('Y/m/d', strtotime($admin['created_at'])) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
                
                <?php if (empty($admins)): ?>
                <div class="empty-admins">
                    <span style="font-size:2rem;display:block;margin-bottom:0.5rem;">👻</span>
                    هیچ ادمینی وجود ندارد
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- پنل دسترسی‌ها -->
        <div class="permissions-panel">
            <?php if ($selected_admin): ?>
            <div class="panel-header">
                <h3>
                    🔑 دسترسی‌های 
                    <span><?= htmlspecialchars($selected_admin['first_name'] . ' ' . $selected_admin['last_name']) ?></span>
                </h3>
                <span class="admin-email"><?= htmlspecialchars($selected_admin['email']) ?></span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="admin_id" value="<?= $selected_admin_id ?>">
                
                <div class="permissions-grid">
                    <?php foreach ($all_pages as $page_key => $page_label): ?>
                    <label class="permission-item <?= isset($permissions[$page_key]) && $permissions[$page_key] ? 'active' : '' ?>">
                        <div class="permission-info">
                            <span class="permission-label"><?= $page_label ?></span>
                            <span class="permission-key"><?= $page_key ?></span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="perm_<?= $page_key ?>" 
                                   <?= isset($permissions[$page_key]) && $permissions[$page_key] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="panel-actions">
                    <button type="button" class="btn-select-all" onclick="selectAll()">✅ انتخاب همه</button>
                    <button type="button" class="btn-deselect-all" onclick="deselectAll()">❌ حذف همه</button>
                    <button type="submit" name="save_permissions" class="btn-save">💾 ذخیره دسترسی‌ها</button>
                </div>
            </form>
            
            <?php else: ?>
            <div class="no-admin-selected">
                <div class="empty-icon">👈</div>
                <h3>یک ادمین انتخاب کنید</h3>
                <p>برای مدیریت دسترسی‌ها، یک ادمین را از لیست سمت راست انتخاب کنید.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// مقداردهی اولیه
document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        this.closest('.permission-item').classList.toggle('active', this.checked);
    });
});

function selectAll() {
    document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(cb => {
        cb.checked = true;
        cb.closest('.permission-item').classList.add('active');
    });
}

function deselectAll() {
    document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
        cb.closest('.permission-item').classList.remove('active');
    });
}
</script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>