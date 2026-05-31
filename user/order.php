<?php
include '../database/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header("Location: ../authentication/login.php?redirect=order.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'remove') {
        $order_id = (int)$_POST['order_id'];
        
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $order_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'محصول با موفقیت از سبد خرید حذف شد.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در حذف محصول. لطفاً دوباره تلاش کنید.']);
        }
        $stmt->close();
        exit();
    }
    
    if ($_POST['action'] === 'update') {
        $order_id = (int)$_POST['order_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'تعداد باید حداقل ۱ باشد.']);
            exit();
        }
        
        $stmt = $conn->prepare("
            SELECT b.price FROM orders o 
            JOIN books b ON o.book_id = b.book_id 
            WHERE o.order_id = ? AND o.user_id = ?
        ");
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();
        
        if ($book) {
            $total_price = $book['price'] * $quantity;
            
            $stmt = $conn->prepare("
                UPDATE orders 
                SET quantity = ?, total_price = ? 
                WHERE order_id = ? AND user_id = ?
            ");
            $stmt->bind_param('iiii', $quantity, $total_price, $order_id, $user_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $sum_stmt = $conn->prepare("SELECT SUM(total_price) as total FROM orders WHERE user_id = ?");
                $sum_stmt->bind_param('i', $user_id);
                $sum_stmt->execute();
                $sum_result = $sum_stmt->get_result();
                $sum_row = $sum_result->fetch_assoc();
                $new_total = $sum_row['total'] ?? 0;
                $sum_stmt->close();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تعداد با موفقیت بروزرسانی شد.',
                    'item_total' => number_format($total_price),
                    'item_total_raw' => $total_price,
                    'cart_total' => number_format($new_total),
                    'cart_total_raw' => $new_total,
                    'price' => $book['price']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی تعداد.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'سفارش مورد نظر یافت نشد.']);
        }
        exit();
    }
}

$orders = [];
$total_price = 0;

$stmt = $conn->prepare("
    SELECT o.order_id, o.book_id, o.quantity, o.total_price as order_total,
           b.name, b.author, b.image, b.price
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $total_price += $row['order_total'];
}
$stmt->close();

$page_title = "سبد خرید";

ob_start();
?>

<link rel="stylesheet" href="../static/css/order.css">

<div class="cart-container">
    
    <div id="confirmDialog" class="confirm-overlay hidden">
        <div class="confirm-box">
            <div class="confirm-icon">🗑️</div>
            <h3 class="confirm-title">تأیید حذف</h3>
            <p id="confirmMessage" class="confirm-message">آیا از حذف این کتاب از سبد خرید اطمینان دارید؟</p>
            <div class="confirm-buttons">
                <button id="confirmCancel" class="confirm-btn cancel">انصراف</button>
                <button id="confirmOk" class="confirm-btn danger">بله، حذف کن</button>
            </div>
        </div>
    </div>

    <div class="cart-glass-panel">
        
        <div class="cart-header">
            <div class="cart-title-section">
                <div class="cart-icon-box">
                    <span>🛒</span>
                </div>
                <h1 class="cart-title">سبد خرید</h1>
                <?php if (count($orders) > 0): ?>
                    <span class="cart-count-badge">
                        <?php echo count($orders); ?> کتاب
                    </span>
                <?php endif; ?>
            </div>
            <a href="index.php" class="back-link">
                <span>⬅️</span>
                <span>بازگشت</span>
            </a>
        </div>

        <?php if (isset($_SESSION['order_success'])): ?>
            <div class="success-message">
                <span>✅</span>
                <span><?php echo $_SESSION['order_success']; ?></span>
            </div>
            <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <div class="orders-list">
                <?php foreach ($orders as $item): ?>
                <div id="order-<?php echo $item['order_id']; ?>" class="order-item">
                    <div class="order-row">
                        <div class="order-image">
                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 onerror="this.src='../static/images/placeholder-book.jpg'">
                        </div>
                        
                        <div class="order-info">
                            <h3 class="order-book-title">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <p class="order-book-author">
                                <span>✍️</span>
                                <?php echo htmlspecialchars($item['author'] ?? 'نویسنده نامشخص'); ?>
                            </p>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">قیمت واحد</span>
                                <span class="detail-value unit-price">
                                    <?php echo number_format($item['price']); ?> تومان
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">تعداد</span>
                                <div class="quantity-control">
                                    <input type="number" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           class="quantity-input"
                                           data-order-id="<?php echo $item['order_id']; ?>"
                                           data-price="<?php echo $item['price']; ?>">
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">قیمت کل</span>
                                <span class="detail-value item-total">
                                    <?php echo number_format($item['order_total']); ?> تومان
                                </span>
                            </div>
                            
                            <button class="remove-btn"
                                    data-order-id="<?php echo $item['order_id']; ?>"
                                    data-book-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    title="حذف">
                                <span>🗑️</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-footer">
                <div class="total-section">
                    <span class="total-label">جمع کل:</span>
                    <span id="total-price" class="total-value"><?php echo number_format($total_price); ?> تومان</span>
                </div>
                
                <div class="action-buttons">
                    <a href="books.php" class="btn-secondary">
                        <span>📚</span>
                        <span>ادامه خرید</span>
                    </a>
                    <button id="checkoutBtn" class="btn-primary">
                        <span>💳</span>
                        <span>پرداخت</span>
                    </button>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <span>🛒</span>
                </div>
                <h3 class="empty-cart-title">سبد خرید خالی است</h3>
                <p class="empty-cart-text">هنوز کتابی به سبد خرید اضافه نکرده‌اید</p>
                <a href="books.php" class="btn-browse">
                    <span>📖</span>
                    <span>مشاهده کتاب‌ها</span>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="../static/js/order.js"></script>

<?php
$content = ob_get_clean();
include '../includes/header-footer.php';
$conn->close();
?>