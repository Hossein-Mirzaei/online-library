<?php
header('Content-Type: application/json');

if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ناقص است']);
    exit();
}

$user_id = intval($_POST['user_id']);

include '../database/config.php';

$stmt = $conn->prepare("DELETE FROM ai_chats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'تاریخچه چت با موفقیت پاک شد',
        'deleted_count' => $stmt->affected_rows
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در پایگاه داده']);
}

$stmt->close();
$conn->close();
?>