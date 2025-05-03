<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = intval($_POST['id']);

// Delete associated records first
$conn->query("DELETE FROM reviews WHERE movie_id = $id");
$conn->query("DELETE FROM favorites WHERE movie_id = $id");
$conn->query("DELETE FROM watchlist WHERE movie_id = $id");

// Then delete the movie
$sql = "DELETE FROM movies WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>