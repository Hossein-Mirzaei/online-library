<?php
include '../database/config.php';

requirePermission('list_orders');

if (isset($_GET['action']) && $_GET['action'] === 'get_orders') {
    header('Content-Type: application/json');
    
    $user_id = intval($_GET['user_id']);
    
    $sql = "SELECT o.order_id, o.quantity, o.total_price, o.order_date,
                   b.name as book_name, b.author, b.price as unit_price
            FROM orders o
            JOIN books b ON o.book_id = b.book_id
            WHERE o.user_id = ?
            ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
        $total_amount += $row['total_price'];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total_amount' => $total_amount,
        'count' => count($orders)
    ]);
    
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$allowed_sorts = ['first_name', 'last_name', 'email', 'order_date', 'total_amount', 'order_count'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'order_date';

$query = "SELECT 
            u.user_id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            COUNT(o.order_id) as order_count, 
            COALESCE(SUM(o.total_price), 0) as total_amount,
            MAX(o.order_date) as last_order_date
          FROM users u 
          LEFT JOIN orders o ON u.user_id = o.user_id
          $where_clause
          GROUP BY u.user_id, u.first_name, u.last_name, u.email
          HAVING order_count > 0
          ORDER BY $sort_by $sort_order";

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
$total_orders = 0;
$total_revenue = 0;

foreach ($users as $user) {
    $total_orders += $user['order_count'];
    $total_revenue += $user['total_amount'];
}

$page_title = "مدیریت سفارش‌ها";

ob_start();
?>

<link rel="stylesheet" href="../static/css/list_orders.css">

<div class="main-container">
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><span>👥</span></div>
            <div class="stat-content">
                <div class="stat-label">کاربران با سفارش</div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
                <div class="stat-sub">کاربر</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span>🛒</span></div>
            <div class="stat-content">
                <div class="stat-label">تعداد سفارش‌ها</div>
                <div class="stat-value"><?= number_format($total_orders) ?></div>
                <div class="stat-sub">سفارش ثبت شده</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span>💰</span></div>
            <div class="stat-content">
                <div class="stat-label">مجموع فروش</div>
                <div class="stat-value"><?= number_format($total_revenue) ?></div>
                <div class="stat-sub">تومان</div>
            </div>
        </div>
    </div>

    <div class="search-filter-section">
        <form method="GET" class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">🔍 جستجوی کاربر</label>
                <input type="text" name="search" class="search-input" 
                       placeholder="نام، نام خانوادگی یا ایمیل..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">📊 مرتب‌سازی</label>
                <div class="filter-controls">
                    <select name="sort" class="sort-select">
                        <option value="order_date" <?= $sort_by === 'order_date' ? 'selected' : '' ?>>آخرین سفارش</option>
                        <option value="first_name" <?= $sort_by === 'first_name' ? 'selected' : '' ?>>نام</option>
                        <option value="last_name" <?= $sort_by === 'last_name' ? 'selected' : '' ?>>نام خانوادگی</option>
                        <option value="order_count" <?= $sort_by === 'order_count' ? 'selected' : '' ?>>تعداد سفارش</option>
                        <option value="total_amount" <?= $sort_by === 'total_amount' ? 'selected' : '' ?>>مجموع خرید</option>
                    </select>
                    <button type="submit" class="btn-search">
                        <span>🔍</span> جستجو
                    </button>
                    <a href="list_orders.php" class="btn-reset">
                        <span>🔄</span> بازنشانی
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="results-info">
        <div class="results-count">
            <span>👥</span> <?= $total_users ?> کاربر با سفارش پیدا شد
        </div>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-tag"><span>🔍</span> <?= htmlspecialchars($search) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="orders-table-container">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>تعداد سفارش</th>
                    <th>مجموع خرید</th>
                    <th>آخرین سفارش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">🛒</div>
                                <h3>هیچ سفارشی یافت نشد</h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        با جستجوی انجام شده هیچ سفارشی یافت نشد.
                                    <?php else: ?>
                                        هنوز هیچ سفارشی در سیستم ثبت نشده است.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="user-info">
                                <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td>
                                <div class="order-stats">
                                    <span class="order-count"><?= $user['order_count'] ?></span>
                                    <span style="font-size: 0.65rem; color: var(--text-muted);">سفارش</span>
                                </div>
                            </td>
                            <td>
                                <div class="total-amount"><?= number_format($user['total_amount']) ?> تومان</div>
                            </td>
                            <td>
                                <div class="last-order">
                                    <?php if ($user['last_order_date']): ?>
                                        <?= date('Y/m/d', strtotime($user['last_order_date'])) ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button class="details-btn" onclick="showOrderDetails(<?= $user['user_id'] ?>)">
                                    <span>👁️</span> جزئیات
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><span>🛒</span> جزئیات سفارش‌ها</h2>
                <button class="modal-close" onclick="closeOrderModal()">✕</button>
            </div>
            <div class="modal-body" id="orderDetailsBody">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>در حال بارگذاری اطلاعات...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../static/js/list_orders.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-admin.php';
?>