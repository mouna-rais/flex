<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = intval($_POST['id']);

// Prevent deleting yourself
if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit();
}

// Delete associated records first
$conn->query("DELETE FROM reviews WHERE user_id = $id");
$conn->query("DELETE FROM favorites WHERE user_id = $id");
$conn->query("DELETE FROM watchlist WHERE user_id = $id");

// Then delete the user
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>