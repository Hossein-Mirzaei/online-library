<?php
include '../database/config.php';

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'owner' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: ../authentication/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list_users.php");
    exit();
}

$user_id = intval($_POST['user_id']);
$current_type = $_POST['current_type'];

if ($user_id == $_SESSION['user_id']) {
    header("Location: list_users.php?error=" . urlencode("نمی‌توانید نوع حساب خود را تغییر دهید"));
    exit();
}

$new_type = $current_type === 'admin' ? 'user' : 'admin';

$sql = "UPDATE users SET user_type = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $new_type, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['message'] = "نوع کاربر با موفقیت تغییر کرد.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "خطا در تغییر نوع کاربر.";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$conn->close();

header("Location: list_users.php");
exit();
?>