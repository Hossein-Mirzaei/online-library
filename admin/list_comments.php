<?php
include '../database/config.php';

requirePermission('list_comments');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $comment_id = intval($_POST['comment_id']);
        
        $sql = "DELETE FROM comments WHERE comment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $comment_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'نظر با موفقیت حذف شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در حذف نظر']);
        }
        $stmt->close();
        exit();
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'هیچ نظری انتخاب نشده است.']);
            exit();
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM comments WHERE comment_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        
        if ($stmt->execute()) {
            $count = $stmt->affected_rows;
            echo json_encode(['success' => true, 'message' => "$count نظر با موفقیت حذف شد."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در حذف نظرات.']);
        }
        $stmt->close();
        exit();
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'respond') {
        $comment_id = intval($_POST['comment_id']);
        $admin_response = trim($_POST['response']);
        $response_date = date('Y-m-d H:i:s');
        
        if (empty($admin_response)) {
            echo json_encode(['success' => false, 'message' => 'پاسخ نمی‌تواند خالی باشد']);
            exit();
        }
        
        $sql = "UPDATE comments SET admin_response = ?, response_date = ? WHERE comment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $admin_response, $response_date, $comment_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'پاسخ با موفقیت ثبت شد',
                'response' => nl2br(htmlspecialchars($admin_response)),
                'response_date' => $response_date
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت پاسخ']);
        }
        $stmt->close();
        exit();
    }
}

$stats_query = "SELECT 
                    COUNT(*) as total_comments,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN admin_response IS NOT NULL AND admin_response != '' THEN 1 ELSE 0 END) as responded_count,
                    SUM(CASE WHEN admin_response IS NULL OR admin_response = '' THEN 1 ELSE 0 END) as unresponded_count
                FROM comments";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : 'all';
$response_filter = isset($_GET['response']) ? $_GET['response'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR b.name LIKE ? OR c.comment LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if ($rating_filter !== 'all') {
    $where_conditions[] = "c.rating = ?";
    $params[] = intval($rating_filter);
    $types .= 'i';
}

if ($response_filter === 'responded') {
    $where_conditions[] = "c.admin_response IS NOT NULL AND c.admin_response != ''";
} elseif ($response_filter === 'unresponded') {
    $where_conditions[] = "(c.admin_response IS NULL OR c.admin_response = '')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$count_query = "SELECT COUNT(*) as total FROM comments c 
                JOIN users u ON c.user_id = u.user_id 
                JOIN books b ON c.book_id = b.book_id 
                $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_comments = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $per_page);

$allowed_sorts = ['created_at', 'rating', 'response_date', 'first_name', 'book_name'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'created_at';

$query = "SELECT c.comment_id, c.rating, c.comment, c.created_at, c.response_date,
                 u.first_name, u.last_name, u.email, c.admin_response,
                 b.book_id, b.name AS book_name, b.image
          FROM comments c 
          JOIN users u ON c.user_id = u.user_id 
          JOIN books b ON c.book_id = b.book_id 
          $where_clause
          ORDER BY $sort_by $sort_order
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$page_title = "مدیریت نظرات";

ob_start();
?>

<link rel="stylesheet" href="../static/css/list_comments.css">

<div class="main-container">
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><span>💬</span></div>
            <div class="stat-content">
                <div class="stat-label">کل نظرات</div>
                <div class="stat-value"><?= number_format($stats['total_comments'] ?? 0) ?></div>
                <div class="stat-sub">نظر ثبت شده</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span>⭐</span></div>
            <div class="stat-content">
                <div class="stat-label">میانگین امتیاز</div>
                <div class="stat-value"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
                <div class="stat-sub">از ۵ ستاره</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span>✅</span></div>
            <div class="stat-content">
                <div class="stat-label">پاسخ داده شده</div>
                <div class="stat-value"><?= number_format($stats['responded_count'] ?? 0) ?></div>
                <div class="stat-sub">نظر</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span>⏳</span></div>
            <div class="stat-content">
                <div class="stat-label">در انتظار پاسخ</div>
                <div class="stat-value"><?= number_format($stats['unresponded_count'] ?? 0) ?></div>
                <div class="stat-sub">نظر</div>
            </div>
        </div>
    </div>

    <div class="search-filter-section">
        <form method="GET" class="search-filter-grid" id="filterForm">
            <div class="form-group">
                <label class="form-label">🔍 جستجوی نظر</label>
                <input type="text" name="search" class="search-input" 
                       placeholder="نام کاربر، نام کتاب یا متن نظر..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">⭐ امتیاز</label>
                <select name="rating" class="filter-select">
                    <option value="all" <?= $rating_filter === 'all' ? 'selected' : '' ?>>همه امتیازها</option>
                    <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>۵ ستاره</option>
                    <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>۴ ستاره</option>
                    <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>۳ ستاره</option>
                    <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>۲ ستاره</option>
                    <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>۱ ستاره</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">💬 پاسخ</label>
                <select name="response" class="filter-select">
                    <option value="all" <?= $response_filter === 'all' ? 'selected' : '' ?>>همه نظرات</option>
                    <option value="responded" <?= $response_filter === 'responded' ? 'selected' : '' ?>>پاسخ داده شده</option>
                    <option value="unresponded" <?= $response_filter === 'unresponded' ? 'selected' : '' ?>>بدون پاسخ</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">📊 مرتب‌سازی</label>
                <select name="sort" class="sort-select">
                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>تاریخ ثبت</option>
                    <option value="rating" <?= $sort_by === 'rating' ? 'selected' : '' ?>>امتیاز</option>
                    <option value="response_date" <?= $sort_by === 'response_date' ? 'selected' : '' ?>>تاریخ پاسخ</option>
                    <option value="first_name" <?= $sort_by === 'first_name' ? 'selected' : '' ?>>نام کاربر</option>
                    <option value="book_name" <?= $sort_by === 'book_name' ? 'selected' : '' ?>>نام کتاب</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <div class="button-group">
                    <button type="submit" class="btn-search">
                        <span>🔍</span> جستجو
                    </button>
                    <a href="list_comments.php" class="btn-reset">
                        <span>🔄</span> بازنشانی
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="bulk-actions-bar">
        <div class="selected-info">
            <span>📋</span>
            <span class="selected-count" id="selectedCount">0</span>
            <span>مورد انتخاب شده</span>
        </div>
        <div class="bulk-actions">
            <button class="btn-clear-selection" id="clearSelectionBtn" onclick="clearAllSelections()">
                <span>✖️</span> لغو انتخاب
            </button>
            <button class="btn-bulk-delete" id="bulkDeleteBtn" onclick="bulkDelete()" disabled>
                <span>🗑️</span> حذف انتخاب‌شده‌ها
            </button>
        </div>
    </div>

    <div class="results-info">
        <div class="results-count">
            <span>💬</span> <?= $total_comments ?> نظر پیدا شد
        </div>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-tag"><span>🔍</span> <?= htmlspecialchars($search) ?></span>
            <?php endif; ?>
            <?php if ($rating_filter !== 'all'): ?>
                <span class="filter-tag"><span>⭐</span> <?= $rating_filter ?> ستاره</span>
            <?php endif; ?>
            <?php if ($response_filter !== 'all'): ?>
                <span class="filter-tag"><span>💬</span> <?= $response_filter === 'responded' ? 'پاسخ داده شده' : 'بدون پاسخ' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="comments-table-container">
        <table class="comments-table">
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                    </th>
                    <th>کاربر</th>
                    <th>کتاب</th>
                    <th>نظر</th>
                    <th>امتیاز</th>
                    <th>پاسخ ادمین</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">💭</div>
                                <h3>هیچ نظری یافت نشد</h3>
                                <p>
                                    <?php if (!empty($search) || $rating_filter !== 'all' || $response_filter !== 'all'): ?>
                                        با فیلترهای انتخاب شده هیچ نظری یافت نشد.
                                    <?php else: ?>
                                        هنوز هیچ نظری در سیستم ثبت نشده است.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <tr id="comment-<?= $comment['comment_id'] ?>">
                            <td class="checkbox-cell">
                                <input type="checkbox" class="comment-checkbox" value="<?= $comment['comment_id'] ?>" onchange="updateSelection()">
                            </td>
                            <td class="user-info">
                                <div class="user-name"><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($comment['email']) ?></div>
                            </td>
                            <td>
                                <div class="book-info">
                                    <img src="../<?= htmlspecialchars($comment['image']) ?>" alt="<?= htmlspecialchars($comment['book_name']) ?>" class="book-image" onerror="this.src='../static/images/placeholder-book.jpg'">
                                    <a href="../user/book_details.php?id=<?= $comment['book_id'] ?>" class="book-name" target="_self">
                                        <?= htmlspecialchars($comment['book_name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $comment['rating'] ? '' : 'empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <div class="admin-response <?= !empty($comment['admin_response']) ? 'has-response' : 'no-response' ?>" id="response-<?= $comment['comment_id'] ?>">
                                    <?= !empty($comment['admin_response']) ? nl2br(htmlspecialchars($comment['admin_response'])) : 'بدون پاسخ' ?>
                                    <?php if (!empty($comment['admin_response']) && $comment['response_date']): ?>
                                        <div class="response-date"><?= date('Y/m/d H:i', strtotime($comment['response_date'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="date-cell"><?= date('Y/m/d H:i', strtotime($comment['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="action-btn delete" data-id="<?= $comment['comment_id'] ?>" onclick="deleteSingle(<?= $comment['comment_id'] ?>)">
                                        <span>🗑️</span> حذف
                                    </button>
                                    <button class="action-btn respond" data-id="<?= $comment['comment_id'] ?>" onclick="toggleResponseForm(<?= $comment['comment_id'] ?>)">
                                        <span>💬</span> پاسخ
                                    </button>
                                </div>
                                <div class="response-form" id="response-form-<?= $comment['comment_id'] ?>" style="display: none;">
                                    <textarea class="response-textarea" id="response-text-<?= $comment['comment_id'] ?>" placeholder="پاسخ خود را وارد کنید..."><?= htmlspecialchars($comment['admin_response'] ?? '') ?></textarea>
                                    <div class="response-actions">
                                        <button class="cancel-response-btn" onclick="toggleResponseForm(<?= $comment['comment_id'] ?>)">انصراف</button>
                                        <button class="submit-response-btn" onclick="submitResponse(<?= $comment['comment_id'] ?>)">ارسال پاسخ</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <div class="pagination">
            <?php
            $query_params = $_GET;
            unset($query_params['page']);
            $base_url = 'list_comments.php?' . http_build_query($query_params);
            $base_url = $base_url ? $base_url . '&' : 'list_comments.php?';
            
            if ($page > 1): ?>
                <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="page-link">◀ قبلی</a>
            <?php else: ?>
                <span class="page-link disabled">◀ قبلی</span>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if ($start > 1): ?>
                <a href="<?= $base_url ?>page=1" class="page-link">1</a>
                <?php if ($start > 2): ?>
                    <span class="page-link disabled">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="<?= $base_url ?>page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                    <span class="page-link disabled">...</span>
                <?php endif; ?>
                <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="page-link"><?= $total_pages ?></a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="page-link">بعدی ▶</a>
            <?php else: ?>
                <span class="page-link disabled">بعدی ▶</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="confirmDialog" class="custom-alert-overlay hidden">
    <div class="custom-alert-box">
        <div class="alert-icon-wrapper" id="dialogIcon">⚠️</div>
        <h3 class="alert-title" id="dialogTitle">تأیید حذف</h3>
        <p class="alert-message" id="dialogMessage">آیا از حذف این نظر اطمینان دارید؟</p>
        <div class="alert-buttons">
            <button class="alert-btn cancel" id="dialogCancel">انصراف</button>
            <button class="alert-btn danger" id="dialogConfirm">حذف</button>
        </div>
    </div>
</div>

<script src="../static/js/list_comments.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>