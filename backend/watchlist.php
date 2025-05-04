<?php
session_start();
include("cnx.php");

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Accès refusé',
        'text' => 'Vous devez vous connecter d\'abord',
        'redirect' => '../auth_login.html'
    ];
    header("Location: ../auth_login.html");
    exit();
}

// Récupération des films
$query = "SELECT * FROM movies";
$result = $cnx->query($query);

if (!$result) {
    die("Erreur de requête : " . $cnx->error);
}

$movies = $result->fetch_all(MYSQLI_ASSOC);

// Récupération des infos utilisateur
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéDashboard - Tous les films</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* styles.css */
:root {
    --primary-color: #e50914;
    --primary-hover: #f40612;
    --dark-bg: #141414;
    --light-text: #ffffff;
    --secondary-text: #e5e5e5;
    --dark-secondary: #2d2d2d;
    --overlay-bg: rgba(0, 0, 0, 0.85);
    --success-color: #28a745;
    --error-color: #dc3545;
}

/* Reset et styles de base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

body {
    background-color: var(--dark-bg);
    color: var(--light-text);
    min-height: 100vh;
}

a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

a:hover {
    color: var(--primary-color);
}

/* Header */
.navbar-cine {
    background: linear-gradient(to bottom, rgba(20, 20, 20, 0.95) 0%, rgba(20, 20, 20, 0.8) 100%);
    padding: 0.8rem 2rem;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.logo {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    text-transform: uppercase;
}

/* Sidebar */
.sidebar {
    background-color: var(--dark-bg);
    width: 280px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 80px;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    overflow-y: auto;
}

.nav-flex-column {
    padding: 1rem;
}

.nav-link {
    color: var(--secondary-text);
    padding: 0.8rem 1.5rem;
    margin: 0.5rem 0;
    border-radius: 5px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.nav-link.active,
.nav-link:hover {
    background-color: var(--dark-secondary);
    color: var(--light-text);
}

.nav-link i {
    font-size: 1.2rem;
    margin-right: 1rem;
    width: 25px;
}

/* Contenu principal */
main {
    margin-left: 280px;
    margin-top: 76px;
    padding: 2rem;
    min-height: calc(100vh - 76px);
}

.section-title {
    font-size: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-color);
    display: inline-block;
}

/* Cartes de film */
.recent-movie {
    background: var(--dark-secondary);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.recent-movie:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.movie-poster {
    height: 300px;
    object-fit: cover;
    width: 100%;
    border-bottom: 3px solid var(--primary-color);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
}

.bg-danger {
    background-color: var(--primary-color) !important;
}

/* Formulaire et boutons */
.btn {
    border-radius: 5px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-danger {
    background-color: var(--primary-color);
    border: none;
}

.btn-danger:hover {
    background-color: var(--primary-hover);
}

.btn-success {
    background-color: var(--success-color);
    border: none;
}

.btn-sm {
    padding: 0.3rem 0.8rem;
    font-size: 0.9rem;
}

/* Alertes */
.alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;
    min-width: 300px;
    backdrop-filter: blur(10px);
    background: var(--overlay-bg) !important;
    border: none;
}

.alert-error {
    border-left: 4px solid var(--error-color);
}

.alert-success {
    border-left: 4px solid var(--success-color);
}

/* Responsive Design */
@media (max-width: 992px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-top: 0;
    }

    main {
        margin-left: 0;
        margin-top: 120px;
    }

    .navbar-cine {
        padding: 1rem;
    }

    .logo {
        font-size: 1.5rem;
    }
}

@media (max-width: 768px) {
    .movie-poster {
        height: 200px;
    }

    .recent-movie {
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.5rem;
    }
}

/* Utilitaires */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.position-absolute {
    z-index: 2;
}

.w-100 {
    width: 100% !important;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

.text-secondary {
    color: var(--secondary-text) !important;
}

.text-muted {
    color: #6c757d !important;
}
        .recent-movie {
            transition: transform 0.3s;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .recent-movie:hover {
            transform: translateY(-5px);
        }
        
        .movie-poster {
            height: 300px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <?php include("nav.php"); ?>
    
    <!-- Sidebar -->
    <?php include("sidebar.php"); ?>

    <!-- Contenu principal -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <h1 class="section-title mb-4">
            <i class="bi bi-film"></i> Tous les films
        </h1>

        <!-- Liste des films -->
        <div class="row">
            <?php if (empty($movies)): ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-secondary">Aucun film disponible</h3>
                </div>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="recent-movie h-100 position-relative">
                            <img src="<?= htmlspecialchars($movie['poster_path']) ?>" 
                                 class="movie-poster w-100"
                                 alt="<?= htmlspecialchars($movie['title']) ?>">
                            <div class="p-3">
                                <center><h5 class="text-truncate" style="margin-bottom: 50px;"><?= htmlspecialchars($movie['title']) ?></h5></center>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger"><?= htmlspecialchars($movie['genre']) ?></span>
                                    <small class="text-muted"><?= date('Y', strtotime($movie['year'])) ?></small>
                                </div>
                                
                            </div>
                            <form action="add_to_watchlist.php" method="POST" class="position-absolute top-0 end-0 m-2">
                                <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                <button type="submit" 
                                        class="btn btn-success btn-sm"
                                        onclick="return confirm('Ajouter à la watchlist ?')">
                                    <i class="bi bi-bookmark-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.recent-movie').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (!e.target.closest('button')) {
                        const movieId = card.querySelector('input[name="movie_id"]').value;
                        window.location.href = `movie-details.php?id=${movieId}`;
                    }
                });
            });
        });
    </script>
</body>
</html>