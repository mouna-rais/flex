<?php
session_start();
include("cnx.php");

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour accéder à cette fonctionnalité'
    ];
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $movie_id = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT);

    // Validation des données
    if (!$movie_id) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Film invalide'
        ];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        // Vérifier l'existence du film
        $stmt = $cnx->prepare("SELECT id FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Film introuvable'
            ];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Vérifier si déjà dans la watchlist
        $stmt = $cnx->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->bind_param("ii", $user_id, $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Retirer de la watchlist
            $stmt = $cnx->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
            $stmt->bind_param("ii", $user_id, $movie_id);
            $stmt->execute();
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Film retiré de votre watchlist'
            ];
        } else {
            // Ajouter à la watchlist
            $stmt = $cnx->prepare("INSERT INTO watchlist (user_id, movie_id, added_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $movie_id);
            $stmt->execute();
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Film ajouté à votre watchlist'
            ];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Une erreur est survenue : ' . $e->getMessage()
        ];
    } finally {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}