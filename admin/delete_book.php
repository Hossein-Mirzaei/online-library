<?php
include '../database/config.php';

if (isset($_POST['book_id'])) {
    $bookId = intval($_POST['book_id']);

    $sql = "DELETE FROM books WHERE book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
}
$conn->close();
?>