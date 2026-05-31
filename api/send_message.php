<?php
header('Content-Type: application/json');

include '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً تمام فیلدها را پر کنید.']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'ایمیل معتبر نیست.']);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO messages (user_name, user_email, message) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $email, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'پیام شما با موفقیت ارسال شد.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره پیام.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'متد نامعتبر.']);
}

$conn->close();
?>