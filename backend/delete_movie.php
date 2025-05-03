<?php
session_start();
include("cnx.php");

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Accès refusé : autorisation insuffisante";
    header("Location: movies.php");
    exit();
}

// Vérifier si l'ID du film est présent et valide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de film invalide";
    header("Location: movies.php");
    exit();
}

$movie_id = intval($_GET['id']);

// Si confirmation reçue via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Commencer une transaction
        $cnx->begin_transaction();

        // 1. Récupérer le chemin de l'affiche
        $stmt = $cnx->prepare("SELECT poster_path FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();

        if (!$movie) {
            throw new Exception("Film non trouvé");
        }

        // 2. Supprimer les relations
        $tables = ['movie_genres', 'movie_actors', 'reviews', 'favorites'];
        foreach ($tables as $table) {
            // Vérifier si la table existe avant de tenter la suppression
            $check_table = $cnx->query("SHOW TABLES LIKE '$table'");
            if ($check_table->num_rows > 0) {
                $delete_stmt = $cnx->prepare("DELETE FROM $table WHERE movie_id = ?");
                $delete_stmt->bind_param("i", $movie_id);
                if (!$delete_stmt->execute()) {
                    throw new Exception("Erreur lors de la suppression dans $table: " . $cnx->error);
                }
            }
        }

        // 3. Supprimer le film
        $delete_movie = $cnx->prepare("DELETE FROM movies WHERE id = ?");
        $delete_movie->bind_param("i", $movie_id);
        if (!$delete_movie->execute()) {
            throw new Exception("Erreur lors de la suppression du film: " . $cnx->error);
        }

        // 4. Supprimer l'affiche si elle existe
        if (!empty($movie['poster_path']) && file_exists($movie['poster_path'])) {
            if (!unlink($movie['poster_path'])) {
                throw new Exception("Erreur lors de la suppression du fichier d'affiche");
            }
        }

        // Valider la transaction
        $cnx->commit();

        $_SESSION['success'] = "Film supprimé avec succès";
        header("Location: movies.php");
        exit();

    } catch (Exception $e) {
        // Annuler en cas d'erreur
        if (isset($cnx) && $cnx) {
            $cnx->rollback();
        }
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
        header("Location: movies.php");
        exit();
    }
}

// Si pas de confirmation, afficher la page de confirmation
$stmt = $cnx->prepare("SELECT id, title FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();

if (!$movie) {
    $_SESSION['error'] = "Film non trouvé";
    header("Location: movies.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la suppression</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .confirmation-card {
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .btn-confirm {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card confirmation-card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Confirmer la suppression</h4>
            </div>
            <div class="card-body">
                <p>Êtes-vous sûr de vouloir supprimer définitivement le film :</p>
                <h5 class="text-center my-4"><?= htmlspecialchars($movie['title']) ?></h5>
                
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                    <div class="d-flex justify-content-between mt-4">
                        <a href="movies.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" name="confirm_delete" class="btn btn-confirm text-white">
                            <i class="bi bi-trash"></i> Confirmer la suppression
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>