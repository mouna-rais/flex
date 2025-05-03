<?php
session_start();
include("cnx.php");

// Vérifier si admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_lohin.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $year = intval($_POST['year']);
    $genre = trim($_POST['genre']);
    $duration = trim($_POST['duration']);
    $director = trim($_POST['director']);

    // Gestion de l'upload de l'image
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/posters/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['poster']['name']);
        $targetPath = $uploadDir . $fileName;

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($_FILES['poster']['tmp_name']);

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
                // Insertion en base de données
                $stmt = $cnx->prepare("INSERT INTO movies (title, description, year, genre, duration, director, poster_path) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissss", $title, $description, $year, $genre, $duration, $director, $targetPath);
                
                if ($stmt->execute()) {
                    $success = "Film ajouté avec succès!";
                } else {
                    $error = "Erreur lors de l'ajout du film: " . $cnx->error;
                }
            } else {
                $error = "Erreur lors de l'upload de l'image";
            }
        } else {
            $error = "Type de fichier non autorisé. Formats acceptés: JPG, PNG, WEBP";
        }
    } else {
        $error = "Veuillez sélectionner une affiche pour le film";
    }
}

// Récupérer la liste des films
$movies = [];
$result = $cnx->query("SELECT * FROM movies ORDER BY created_at DESC");
if ($result) {
    $movies = $result->fetch_all(MYSQLI_ASSOC);
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

/* Responsive */
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
        <a class="navbar-brand" href="../dashboard.php">
            <i class="bi bi-camera-reels me-2"></i>MovieFlex
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
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
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i>
                    Gestion des utilisateurs
                </a>
            </li>
        </ul>
    </div>
</nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h1 class="h2 mb-4">Gestion des films</h1>

                <!-- Formulaire d'ajout -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ajouter un nouveau film</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

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
                                            <a href="delete_movie.php?id=<?= $movie['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>