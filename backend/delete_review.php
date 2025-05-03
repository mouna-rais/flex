<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = intval($_POST['id']);

// Delete the review
$sql = "DELETE FROM reviews WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$success = $stmt->execute();

// Update movie average rating if review was deleted
if ($success) {
    $movie_id = $conn->query("SELECT movie_id FROM reviews WHERE id = $id")->fetch_assoc()['movie_id'];
    $conn->query("UPDATE movies SET rating = (
        SELECT AVG(rating) FROM reviews WHERE movie_id = $movie_id
    ) WHERE id = $movie_id");
}

echo json_encode(['success' => $success]);
?>