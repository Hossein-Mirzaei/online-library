<?php
header('Content-Type: application/json; charset=utf-8');

include '../database/config.php';

$user_id = intval($_GET['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'messages' => []]);
    exit();
}

$stmt = $conn->prepare("
    SELECT message, response, created_at 
    FROM ai_chats 
    WHERE user_id = ? 
    ORDER BY created_at ASC 
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => $row['message'],
        'response' => $row['response'],
        'created_at' => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>