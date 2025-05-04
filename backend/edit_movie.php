<?php
session_start();
include("cnx.php");

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_login.html");
    exit();
}

$movie = [];
$errors = [];

// Récupérer les données du film à modifier
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    
    try {
        $stmt = $cnx->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Film introuvable";
            header("Location: movies.php");
            exit();
        }
        
        $movie = $result->fetch_assoc();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: movies.php");
        exit();
    }
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $movie_id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $year = intval($_POST['year']);
    $genre = trim($_POST['genre']);
    $duration = trim($_POST['duration']);
    $director = trim($_POST['director']);
    $link = trim($_POST['link']);

    try {
        // Validation basique
        if (empty($title)) throw new Exception("Le titre est obligatoire");
        if ($year < 1900 || $year > date('Y')) throw new Exception("Année invalide");

        // Gestion de l'image (mise à jour facultative)
        $poster_path = $_POST['current_poster'];
        
        if ($_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            // Supprimer l'ancienne image
            if (file_exists($poster_path)) {
                unlink($poster_path);
            }

            // Upload nouvelle image
            $uploadDir = 'uploads/posters/';
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->file($_FILES['poster']['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé ($fileType)");
            }

            $extension = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ][$fileType];
            
            $fileName = uniqid('poster_') . '.' . $extension;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
                throw new Exception("Échec de l'upload du fichier");
            }
            
            $poster_path = $targetPath;
        }

        // Mise à jour en base de données
        $stmt = $cnx->prepare("UPDATE movies SET 
            title = ?,
            description = ?,
            year = ?,
            genre = ?,
            duration = ?,
            director = ?,
            link = ?,
            poster_path = ?
            WHERE id = ?");
        
        $stmt->bind_param("ssisssssi", 
            $title,
            $description,
            $year,
            $genre,
            $duration,
            $director,
            $link,
            $poster_path,
            $movie_id);

        if (!$stmt->execute()) {
            throw new Exception("Erreur de mise à jour : " . $stmt->error);
        }

        $_SESSION['success'] = "Film mis à jour avec succès";
        header("Location: movies.php");
        exit();

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $movie = $_POST;
        $movie['id'] = $movie_id;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le film</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Reprendre le style de movies.php */
        :root {
            --primary-color: #e50914;
            --dark-color: #141414;
            --light-color: #f4f4f4;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .current-poster {
            max-width: 200px;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-custom:hover {
            background-color: #c40812;
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container-fluid">
        <div class="row">
            <?php include("sidebar.php"); ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="form-container">
                    <h2 class="mb-4">Modifier le film</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?= $error ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $movie['id'] ?? '' ?>">
                        <input type="hidden" name="current_poster" value="<?= $movie['poster_path'] ?? '' ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titre</label>
                                <input type="text" class="form-control" name="title" 
                                    value="<?= htmlspecialchars($movie['title'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Année</label>
                                <input type="number" class="form-control" name="year" 
                                    value="<?= htmlspecialchars($movie['year'] ?? '') ?>" 
                                    min="1900" max="<?= date('Y') ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required><?= 
                                    htmlspecialchars($movie['description'] ?? '') ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Genre</label>
                                <input type="text" class="form-control" name="genre" 
                                    value="<?= htmlspecialchars($movie['genre'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Durée (minutes)</label>
                                <input type="text" class="form-control" name="duration" 
                                    value="<?= htmlspecialchars($movie['duration'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Réalisateur</label>
                                <input type="text" class="form-control" name="director" 
                                    value="<?= htmlspecialchars($movie['director'] ?? '') ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Lien du film</label>
                                <input type="url" class="form-control" name="link" 
                                    value="<?= htmlspecialchars($movie['link'] ?? '') ?>" 
                                    placeholder="https://example.com/video.mp4" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Affiche actuelle</label><br>
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <img src="<?= htmlspecialchars($movie['poster_path']) ?>" 
                                        class="current-poster" 
                                        alt="Affiche actuelle">
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nouvelle affiche (optionnel)</label>
                                <input type="file" class="form-control" name="poster" 
                                    accept="image/jpeg, image/png, image/webp">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-custom btn-lg">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                                <a href="movies.php" class="btn btn-secondary btn-lg">
                                    Annuler
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>