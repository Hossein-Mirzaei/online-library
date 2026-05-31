<?php
include '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ابتدا وارد حساب کاربری خود شوید.']);
        exit();
    }


    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : null;
    $user_id = $_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $comment = isset($_POST['comment']) ? htmlspecialchars($_POST['comment'], ENT_QUOTES) : null;


    if (!$book_id || !$rating || !$comment) {
        echo json_encode(['status' => 'error', 'message' => 'تمام فیلدها باید پر شوند.']);
        exit();
    }


    if ($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'امتیاز باید بین 1 و 5 باشد.']);
        exit();
    }

    $insert_sql = "INSERT INTO comments (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در آماده‌سازی کوئری: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("iiis", $book_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;

        $user_sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();

        echo json_encode([
            'status' => 'success',
            'html' => '
                <div class="comment">
                    <strong>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ' (امتیاز: ' . $rating . ')</strong>
                    <p>' . $comment . '</p>
                    <p><small>تاریخ ثبت: ' . date('Y-m-d H:i:s') . '</small></p>
                </div>'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت نظر: ' . $stmt->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'درخواست نامعتبر.']);
}

$conn->close();
?>