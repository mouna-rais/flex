<?php
session_start();
include("cnx.php");

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_login.html");
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $movie_id = intval($_POST['movie_id']);
    
    try {
        // Début de transaction
        $cnx->begin_transaction();

        // 1. Récupération du chemin de l'affiche
        $stmt = $cnx->prepare("SELECT poster_path FROM movies WHERE id = ?");
        if (!$stmt) throw new Exception("Erreur de préparation (SELECT): " . $cnx->error);
        
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();
        // 3. Suppression du film
        $delete_movie = $cnx->prepare("DELETE FROM movies WHERE id = ?");
        if (!$delete_movie) throw new Exception("Erreur de préparation (DELETE): " . $cnx->error);
        
        $delete_movie->bind_param("i", $movie_id);
        if (!$delete_movie->execute()) {
            throw new Exception("Erreur de suppression du film: " . $delete_movie->error);
        }

        // 4. Suppression du fichier image
        if (!empty($movie['poster_path']) && file_exists($movie['poster_path'])) {
            if (!unlink($movie['poster_path'])) {
                throw new Exception("Erreur de suppression du fichier image");
            }
        }

        // Validation finale
        $cnx->commit();
        $_SESSION['success'] = "Film supprimé avec succès";

    } catch (Exception $e) {
        $cnx->rollback();
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
    
    header("Location: movies.php");
    exit();
}

// Traitement de l'ajout de film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $year = intval($_POST['year']);
    $genre = trim($_POST['genre']);
    $duration = trim($_POST['duration']);
    $director = trim($_POST['director']);

    try {
        // Validation du fichier
        if (!isset($_FILES['poster']) || $_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Aucun fichier image valide téléchargé");
        }

        // Configuration de l'upload
        $uploadDir = 'uploads/posters/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier de destination");
            }
        }

        // Vérification du type MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($_FILES['poster']['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Type de fichier non autorisé ($fileType)");
        }

        // Génération du nom de fichier
        $extension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ][$fileType];
        
        $fileName = uniqid('poster_') . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        // Déplacement du fichier
        if (!move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
            throw new Exception("Échec de l'upload du fichier");
        }

        // Insertion en base de données
        $stmt = $cnx->prepare("INSERT INTO movies (title, description, year, genre, duration, director, poster_path) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Erreur de préparation (INSERT): " . $cnx->error);
        
        $stmt->bind_param("ssissss", $title, $description, $year, $genre, $duration, $director, $targetPath);
        
        if (!$stmt->execute()) {
            unlink($targetPath); // Nettoyage du fichier en cas d'erreur
            throw new Exception("Erreur d'insertion: " . $stmt->error);
        }

        $_SESSION['success'] = "Film ajouté avec succès";

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: movies.php");
    exit();
}

// Récupération des films
$movies = [];
try {
    $result = $cnx->query("SELECT * FROM movies ORDER BY created_at DESC");
    if ($result) {
        $movies = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de récupération des films: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des films</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --dark-color: #141414;
            --light-color: #f4f4f4;
            --secondary-color: #aaa;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--dark-color);
        }

        .navbar-admin {
            background-color: var(--dark-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: var(--secondary-color);
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--light-color);
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i {
            margin-right: 8px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .table img {
            border-radius: 4px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #c40812;
            border-color: #c40812;
        }

        .alert {
            border-radius: 4px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }

        /* Modal de confirmation */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }
        .confirmation-dialog {
            background: white;
            padding: 2rem;
            border-radius: 5px;
            max-width: 500px;
            width: 90%;
        }
        .btn-confirm {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
            
            main {
                padding-top: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark navbar-admin">
            <div class="container-fluid">
                <a class="navbar-brand" href="../dashbord.php">
                    <i class="bi bi-camera-reels me-2"></i>MovieFlex
                </a>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Décnxexion
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashbord.php">
                                <i class="bi bi-speedometer2"></i>
                                Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="movies.php">
                                <i class="bi bi-film"></i>
                                Gestion des films
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="delete_user.php">
                                <i class="bi bi-people"></i>
                                Gestion des utilisateurs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h1 class="h2 mb-4">Gestion des films</h1>

                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Formulaire d'ajout -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ajouter un nouveau film</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Titre du film</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="year" class="form-label">Année de sortie</label>
                                    <input type="number" class="form-control" id="year" name="year" min="1900" max="<?= date('Y') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="genre" class="form-label">Genre</label>
                                    <input type="text" class="form-control" id="genre" name="genre" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="duration" class="form-label">Durée (minutes)</label>
                                    <input type="text" class="form-control" id="duration" name="duration" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="director" class="form-label">Réalisateur</label>
                                    <input type="text" class="form-control" id="director" name="director" required>
                                </div>
                                <div class="col-12">
                                    <label for="poster" class="form-label">Affiche du film</label>
                                    <input type="file" class="form-control" id="poster" name="poster" accept="image/jpeg, image/png, image/webp" required>
                                    <div class="form-text">Formats acceptés: JPG, PNG, WEBP (max 5MB)</div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Ajouter le film</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des films -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Liste des films</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Affiche</th>
                                        <th>Titre</th>
                                        <th>Année</th>
                                        <th>Genre</th>
                                        <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movies as $movie): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= str_replace('../', '', $movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" style="width: 60px; height: 90px; object-fit: cover;">
                                        </td>
                                        <td><?= htmlspecialchars($movie['title']) ?></td>
                                        <td><?= $movie['year'] ?></td>
                                        <td><?= htmlspecialchars($movie['genre']) ?></td>
                                        <td><?= htmlspecialchars($movie['duration']) ?> min</td>
                                        <td>
                                            <a href="edit_movie.php?id=<?= $movie['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-movie-id="<?= $movie['id'] ?>" 
                                                    data-movie-title="<?= htmlspecialchars($movie['title']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-dialog">
            <h4>Confirmer la suppression</h4>
            <p>Êtes-vous sûr de vouloir supprimer le film : <strong id="movieTitle"></strong> ?</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="movie_id" id="movieIdInput">
                <input type="hidden" name="confirm_delete" value="1">
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Annuler</button>
                    <button type="submit" class="btn btn-confirm text-white">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('confirmationModal');
            const movieTitle = document.getElementById('movieTitle');
            const movieIdInput = document.getElementById('movieIdInput');
            const deleteForm = document.getElementById('deleteForm');
            const cancelBtn = document.getElementById('cancelBtn');
            
            // Boutons de suppression
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-movie-id');
                    const title = this.getAttribute('data-movie-title');
                    
                    movieTitle.textContent = title;
                    movieIdInput.value = id;
                    modal.style.display = 'flex';
                });
            });
            
            // Annulation
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Clic en dehors de la modal
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>