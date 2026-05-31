<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب خود شوید.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'کتاب نامعتبر است.']);
    exit();
}

include '../database/config.php';

$check_book = $conn->prepare("SELECT book_id, price FROM books WHERE book_id = ?");
$check_book->bind_param('i', $book_id);
$check_book->execute();
$book_result = $check_book->get_result();

if ($book_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'کتاب مورد نظر یافت نشد.']);
    exit();
}

$book = $book_result->fetch_assoc();
$check_book->close();

$check_order = $conn->prepare("SELECT order_id, quantity FROM orders WHERE user_id = ? AND book_id = ?");
$check_order->bind_param('ii', $user_id, $book_id);
$check_order->execute();
$order_result = $check_order->get_result();

if ($order_result->num_rows > 0) {
    $order = $order_result->fetch_assoc();
    $new_quantity = $order['quantity'] + 1;
    $new_total = $book['price'] * $new_quantity;
    
    $update_order = $conn->prepare("UPDATE orders SET quantity = ?, total_price = ? WHERE order_id = ?");
    $update_order->bind_param('iii', $new_quantity, $new_total, $order['order_id']);
    
    if ($update_order->execute()) {
        echo json_encode(['success' => true, 'message' => 'تعداد کتاب در سبد خرید افزایش یافت.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی سبد خرید.']);
    }
    $update_order->close();
} else {
    $quantity = 1;
    $total_price = $book['price'] * $quantity;
    
    $insert_order = $conn->prepare("INSERT INTO orders (user_id, book_id, quantity, total_price) VALUES (?, ?, ?, ?)");
    $insert_order->bind_param('iiii', $user_id, $book_id, $quantity, $total_price);
    
    if ($insert_order->execute()) {
        echo json_encode(['success' => true, 'message' => 'کتاب با موفقیت به سبد خرید اضافه شد.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در افزودن به سبد خرید.']);
    }
    $insert_order->close();
}

$check_order->close();
$conn->close();
?>