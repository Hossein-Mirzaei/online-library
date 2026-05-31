<?php
include '../database/config.php';

requirePermission('list_users');

$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];

if (isset($_POST['action']) && $_POST['action'] === 'toggle_user_type') {
    header('Content-Type: application/json');
    
    if ($current_user_type !== 'owner') {
        echo json_encode(['success' => false, 'message' => '⛔ فقط مالک سایت می‌تواند نوع کاربران را تغییر دهد.']);
        exit();
    }
    
    $user_id = intval($_POST['user_id']);
    $current_type = $_POST['current_type'];
    
    if ($current_type === 'owner') {
        echo json_encode(['success' => false, 'message' => '⛔ نوع حساب مالک سایت قابل تغییر نیست.']);
        exit();
    }
    
    if ($user_id == $current_user_id) {
        echo json_encode(['success' => false, 'message' => '⛔ شما مالک سایت هستید و نوع حساب شما قابل تغییر نیست.']);
        exit();
    }
    
    $new_type = ($current_type === 'admin') ? 'user' : 'admin';
    
    $sql = "UPDATE users SET user_type = ? WHERE user_id = ? AND user_type != 'owner'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $new_type, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => '✅ نوع کاربر با موفقیت تغییر کرد.',
            'new_type' => $new_type,
            'new_type_label' => ($new_type === 'admin') ? 'ادمین' : 'کاربر عادی'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در تغییر نوع کاربر.']);
    }
    
    $stmt->close();
    exit();
}

if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    
    if ($current_user_type !== 'owner') {
        $error = "⛔ فقط مالک سایت می‌تواند کاربران را حذف کند.";
    } elseif ($deleteId == $current_user_id) {
        $error = "⛔ شما نمی‌توانید حساب کاربری خود (مالک) را حذف کنید.";
    } else {
        $checkSql = "SELECT user_type FROM users WHERE user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $deleteId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $userToDelete = $checkResult->fetch_assoc();
        
        if ($userToDelete && $userToDelete['user_type'] === 'owner') {
            $error = "⛔ نمی‌توان حساب مالک سایت را حذف کرد.";
        } else {
            $deleteSql = "DELETE FROM users WHERE user_id = ? AND user_type != 'owner'";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $deleteId);
            
            if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                $success = "✅ کاربر با موفقیت حذف شد.";
            } else {
                $error = "خطا در حذف کاربر.";
            }
            $deleteStmt->close();
        }
        $checkStmt->close();
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_type_filter = isset($_GET['user_type']) ? $_GET['user_type'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'user_type';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= 'ssss';
}

if ($user_type_filter !== 'all') {
    $where_conditions[] = "user_type = ?";
    $params[] = $user_type_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$allowed_sorts = ['first_name', 'last_name', 'username', 'email', 'user_type', 'created_at'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'user_type';

$query = "SELECT * FROM users $where_clause 
          ORDER BY FIELD(user_type, 'owner', 'admin', 'user'), $sort_by $sort_order";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$total_users = count($users);
$owner_count = 0;
$admin_count = 0;
$user_count = 0;
$new_users_today = 0;
$today = date('Y-m-d');

foreach ($users as $user) {
    if ($user['user_type'] === 'owner') {
        $owner_count++;
    } elseif ($user['user_type'] === 'admin') {
        $admin_count++;
    } else {
        $user_count++;
    }
    if (date('Y-m-d', strtotime($user['created_at'])) === $today) {
        $new_users_today++;
    }
}

$page_title = "مدیریت کاربران";
ob_start();
?>

<link rel="stylesheet" href="../static/css/list_users.css">

<div class="main-container">
    
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 3px solid #ffd700;">
            <div class="stat-icon"><span>👑</span></div>
            <div class="stat-content">
                <div class="stat-label">مالک سایت</div>
                <div class="stat-value"><?= $owner_count ?></div>
                <div class="stat-sub">نفر</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span>🛡️</span></div>
            <div class="stat-content">
                <div class="stat-label">ادمین‌ها</div>
                <div class="stat-value"><?= $admin_count ?></div>
                <div class="stat-sub">نفر</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span>👤</span></div>
            <div class="stat-content">
                <div class="stat-label">کاربران عادی</div>
                <div class="stat-value"><?= $user_count ?></div>
                <div class="stat-sub">نفر</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span>🆕</span></div>
            <div class="stat-content">
                <div class="stat-label">عضویت امروز</div>
                <div class="stat-value"><?= $new_users_today ?></div>
                <div class="stat-sub">نفر</div>
            </div>
        </div>
    </div>

    <div class="search-filter-section">
        <form method="GET" class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">🔍 جستجوی کاربر</label>
                <input type="text" name="search" class="search-input" 
                       placeholder="نام، نام کاربری یا ایمیل..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">👤 نوع کاربر</label>
                <select name="user_type" class="filter-select">
                    <option value="all" <?= $user_type_filter === 'all' ? 'selected' : '' ?>>همه کاربران</option>
                    <option value="owner" <?= $user_type_filter === 'owner' ? 'selected' : '' ?>>👑 مالک</option>
                    <option value="admin" <?= $user_type_filter === 'admin' ? 'selected' : '' ?>>🛡️ ادمین</option>
                    <option value="user" <?= $user_type_filter === 'user' ? 'selected' : '' ?>>👤 کاربر عادی</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">📊 مرتب‌سازی</label>
                <select name="sort" class="sort-select">
                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>تاریخ ثبت‌نام</option>
                    <option value="first_name" <?= $sort_by === 'first_name' ? 'selected' : '' ?>>نام</option>
                    <option value="username" <?= $sort_by === 'username' ? 'selected' : '' ?>>نام کاربری</option>
                    <option value="email" <?= $sort_by === 'email' ? 'selected' : '' ?>>ایمیل</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <div class="button-group">
                    <button type="submit" class="btn-search"><span>🔍</span> جستجو</button>
                    <a href="list_users.php" class="btn-reset"><span>🔄</span> بازنشانی</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><span>⚠️</span> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><span>✅</span> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="results-info">
        <div class="results-count"><span>👥</span> <?= $total_users ?> کاربر پیدا شد</div>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-tag"><span>🔍</span> <?= htmlspecialchars($search) ?></span>
            <?php endif; ?>
            <?php if ($user_type_filter !== 'all'): ?>
                <?php
                $filter_labels = ['owner' => '👑 مالک', 'admin' => '🛡️ ادمین', 'user' => '👤 کاربر عادی'];
                $label = $filter_labels[$user_type_filter] ?? $user_type_filter;
                ?>
                <span class="filter-tag"><span>👤</span> <?= $label ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام و نام خانوادگی</th>
                    <th>نام کاربری</th>
                    <th>ایمیل</th>
                    <th>آدرس</th>
                    <th>نوع کاربر</th>
                    <th>تاریخ عضویت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">👻</div>
                                <h3>هیچ کاربری یافت نشد</h3>
                                <p><?= (!empty($search) || $user_type_filter !== 'all') ? 'با فیلترهای انتخاب شده هیچ کاربری یافت نشد.' : 'هنوز هیچ کاربری در سیستم ثبت‌نام نکرده است.' ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $index = 1; ?>
                    <?php foreach ($users as $user): ?>
                        <tr id="user-<?= $user['user_id'] ?>" class="<?= $user['user_type'] === 'owner' ? 'row-owner' : '' ?>">
                            <td><?= $index++ ?></td>
                            <td>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                <?php if ($user['user_type'] === 'owner'): ?>
                                    <span style="margin-right:5px;">👑</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['address'] ?? 'ثبت نشده') ?></td>
                            <td>
                                <span class="user-type-badge <?= $user['user_type'] ?>" id="badge-<?= $user['user_id'] ?>">
                                    <?php
                                    if ($user['user_type'] === 'owner') echo '👑 مالک';
                                    elseif ($user['user_type'] === 'admin') echo '🛡️ ادمین';
                                    else echo '👤 کاربر عادی';
                                    ?>
                                </span>
                            </td>
                            <td><?= date('Y/m/d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="user-actions">
                                    <?php if ($user['user_type'] === 'owner'): ?>
                                        <span class="current-user-badge"><span>🔒</span> غیرقابل تغییر</span>
                                    
                                    <?php elseif ($user['user_id'] == $current_user_id): ?>
                                        <span class="current-user-badge"><span>🔒</span> شما</span>
                                    
                                    <?php else: ?>
                                        <?php if ($current_user_type === 'owner'): ?>
                                            <button onclick="toggleUserType(<?= $user['user_id'] ?>, '<?= $user['user_type'] ?>')" 
                                                    class="user-action-btn toggle-btn">
                                                <span>🔄</span>
                                                <?= $user['user_type'] === 'admin' ? 'تبدیل به کاربر' : 'تبدیل به ادمین' ?>
                                            </button>
                                            <button onclick="confirmDelete(<?= $user['user_id'] ?>)" 
                                                    class="user-action-btn delete-btn">
                                                <span>🗑️</span> حذف
                                            </button>
                                        <?php else: ?>
                                            <span class="current-user-badge"><span>🚫</span> عدم دسترسی</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="confirmDialog" class="custom-alert-overlay hidden">
    <div class="custom-alert-box">
        <div class="alert-icon-wrapper" id="dialogIcon">⚠️</div>
        <h3 class="alert-title" id="dialogTitle">تأیید</h3>
        <p class="alert-message" id="dialogMessage"></p>
        <div class="alert-buttons">
            <button class="alert-btn cancel" id="dialogCancel">انصراف</button>
            <button class="alert-btn danger" id="dialogConfirm">تأیید</button>
        </div>
    </div>
</div>

<script src="../static/js/list_users.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>