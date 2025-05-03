<?php
session_start();
include("cnx.php");

// Vérification de l'autorisation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérification de l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de film invalide']);
    exit();
}

$id = intval($_POST['id']);

try {
    // Démarrer une transaction
    $conn->begin_transaction();

    // 1. Récupérer le chemin de l'affiche avant suppression
    $stmt = $conn->prepare("SELECT poster_path FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Film non trouvé']);
        exit();
    }
    
    $movie = $result->fetch_assoc();
    $posterPath = $movie['poster_path'];

    // 2. Supprimer les dépendances
    $tables = ['reviews', 'favorites', 'watchlist'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE movie_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la suppression des dépendances dans $table");
        }
    }

    // 3. Supprimer le film
    $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la suppression du film");
    }

    // 4. Supprimer le fichier d'affiche si il existe
    if (file_exists($posterPath)) {
        if (!unlink($posterPath)) {
            throw new Exception("Erreur lors de la suppression du fichier d'affiche");
        }
    }

    // Valider la transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Film supprimé avec succès']);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression',
        'error' => $e->getMessage()
    ]);
}