<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = intval($_POST['movie_id']);

// Check if already favorited
$sql = "SELECT * FROM favorites WHERE user_id = ? AND movie_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove from favorites
    $sql = "DELETE FROM favorites WHERE user_id = ? AND movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $movie_id);
    $success = $stmt->execute();
} else {
    // Add to favorites
    $sql = "INSERT INTO favorites (user_id, movie_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $movie_id);
    $success = $stmt->execute();
}

echo json_encode(['success' => $success]);
?>