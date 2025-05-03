<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = intval($_POST['movie_id']);
$rating = intval($_POST['rating']);
$comment = trim($_POST['comment']);

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

// Check if user already reviewed this movie
$sql = "SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing review
    $sql = "UPDATE reviews SET rating = ?, comment = ? WHERE user_id = ? AND movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $rating, $comment, $user_id, $movie_id);
} else {
    // Insert new review
    $sql = "INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $user_id, $movie_id, $rating, $comment);
}

if ($stmt->execute()) {
    // Update movie average rating
    $sql = "UPDATE movies SET rating = (
        SELECT AVG(rating) FROM reviews WHERE movie_id = ?
    ) WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $movie_id, $movie_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>