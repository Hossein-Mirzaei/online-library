<?php
include '../database/config.php';

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'owner' && $_SESSION['user_type'] !== 'admin')) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

if (isset($_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);
    $delete_stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $delete_stmt->bind_param("i", $comment_id);

    if ($delete_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'نظر با موفقیت حذف شد.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطا در حذف نظر.']);
    }

    $delete_stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'شناسه نظر ارسال نشده است.']);
}

$conn->close();
?>