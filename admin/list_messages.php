<?php
include '../database/config.php';

requirePermission('list-massages');

if (isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    header('Content-Type: application/json');
    
    $message_id = intval($_POST['message_id']);
    
    $sql = "DELETE FROM messages WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $message_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'پیام با موفقیت حذف شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در حذف پیام']);
    }
    $stmt->close();
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_user_info') {
    header('Content-Type: application/json');
    
    $email = $_GET['email'];
    
    $sql = "SELECT user_id, first_name, last_name, username, email, address, user_type, created_at 
            FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'user' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'کاربر یافت نشد']);
    }
    $stmt->close();
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(user_name LIKE ? OR user_email LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$allowed_sorts = ['created_at', 'user_name', 'user_email', 'message_id'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'created_at';

$query = "SELECT message_id, user_name, user_email, message, created_at 
        FROM messages 
        $where_clause
        ORDER BY $sort_by $sort_order";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$total_messages = count($messages);
$page_title = "لیست پیام‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/list_messages.css">

<style>
    
</style>

<div class="main-container">
    
    <div class="search-filter-section">
        <form method="GET" class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">🔍 جستجوی پیام</label>
                <input type="text" name="search" class="search-input" 
                       placeholder="نام کاربر، ایمیل یا متن پیام..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">📊 مرتب‌سازی</label>
                <div class="filter-controls">
                    <select name="sort" class="sort-select">
                        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>تاریخ ارسال</option>
                        <option value="user_name" <?= $sort_by === 'user_name' ? 'selected' : '' ?>>نام کاربر</option>
                        <option value="user_email" <?= $sort_by === 'user_email' ? 'selected' : '' ?>>ایمیل کاربر</option>
                        <option value="message_id" <?= $sort_by === 'message_id' ? 'selected' : '' ?>>شناسه پیام</option>
                    </select>
                    <button type="submit" class="btn-search">
                        <span>🔍</span> جستجو
                    </button>
                    <a href="list_messages.php" class="btn-reset">
                        <span>🔄</span> بازنشانی
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="results-info">
        <div class="results-count">
            <span>📧</span> <?= $total_messages ?> پیام پیدا شد
        </div>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-tag"><span>🔍</span> <?= htmlspecialchars($search) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="messages-table-container">
        <table class="messages-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>کاربر</th>
                    <th>ایمیل</th>
                    <th>پیام</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <h3>هیچ پیامی یافت نشد</h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        با جستجوی انجام شده هیچ پیامی یافت نشد.
                                    <?php else: ?>
                                        هنوز هیچ پیامی در سیستم ثبت نشده است.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <tr id="message-<?= $message['message_id'] ?>">
                            <td class="message-id">#<?= $message['message_id'] ?></td>
                            <td class="user-info">
                                <div class="user-name"><?= htmlspecialchars($message['user_name']) ?></div>
                            </td>
                            <td>
                                <div class="user-email"><?= htmlspecialchars($message['user_email']) ?></div>
                            </td>
                            <td>
                                <div class="message-text"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                            </td>
                            <td>
                                <div class="date-cell"><?= date('Y/m/d H:i', strtotime($message['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="action-btn info" onclick="showUserDetails('<?= htmlspecialchars($message['user_email']) ?>')">
                                        <span>👤</span> کاربر
                                    </button>
                                    <button class="action-btn delete" onclick="deleteMessage(<?= $message['message_id'] ?>)">
                                        <span>🗑️</span> حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><span>👤</span> جزئیات کاربر</h2>
            <button class="modal-close" onclick="closeUserDetails()">✕</button>
        </div>
        <div class="modal-body" id="userDetailsBody">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>در حال بارگذاری اطلاعات...</p>
            </div>
        </div>
    </div>
</div>

<div id="confirmDialog" class="confirm-overlay hidden">
    <div class="confirm-box">
        <div class="confirm-icon">⚠️</div>
        <h3 class="confirm-title">تأیید حذف</h3>
        <p class="confirm-message" id="confirmMessage">آیا از حذف این پیام اطمینان دارید؟</p>
        <div class="confirm-buttons">
            <button class="confirm-btn cancel" onclick="hideConfirmDialog()">انصراف</button>
            <button class="confirm-btn danger" id="confirmBtn">حذف</button>
        </div>
    </div>
</div>

<script src="../static/js/list_messages.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>