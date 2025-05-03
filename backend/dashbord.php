<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Accès refusé',
        'text' => 'Vous devez vous connecter d\'abord',
        'redirect' => 'login.php'
    ];
    header("Location: login.php");
    exit();
}

// Récupération des infos utilisateur
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéDashboard - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d253f;
            --secondary-color: #01b4e4;
            --tertiary-color: #90cea1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color) !important;
        }
        
        .navbar-cine {
            background-color: var(--primary-color) !important;
        }
        
        .bg-cine-primary {
            background-color: var(--primary-color) !important;
        }
        
        .bg-cine-secondary {
            background-color: var(--secondary-color) !important;
        }
        
        .bg-cine-tertiary {
            background-color: var(--tertiary-color) !important;
        }
        
        .text-cine-secondary {
            color: var(--secondary-color) !important;
        }
        
        .dashboard-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        
        .movie-poster {
            height: 200px;
            object-fit: cover;
            border-radius: 5px 5px 0 0;
        }
        
        .recent-movie {
            transition: transform 0.3s;
        }
        
        .recent-movie:hover {
            transform: scale(1.03);
        }
    </style>
</head>
<body>
    <!-- Barre de navigation supérieure -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cine">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-camera-reels me-2"></i>CinéDashboard
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Bonjour, <?php echo htmlspecialchars($username); ?></span>
                <span class="badge bg-<?php echo $role === 'admin' ? 'danger' : 'secondary'; ?> me-3">
                    <?php echo ucfirst($role); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="https://via.placeholder.com/150x50?text=CinéDB" alt="Logo" class="img-fluid mb-2">
                        <hr class="bg-light">
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="movies.php">
                                <i class="bi bi-film me-2"></i>
                                Films
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="watchlist.php">
                                <i class="bi bi-bookmark-heart me-2"></i>
                                Ma watchlist
                            </a>
                        </li>
                        <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin/movies.php">
                                <i class="bi bi-pencil-square me-2"></i>
                                Gestion des films
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin/users.php">
                                <i class="bi bi-people me-2"></i>
                                Gestion des utilisateurs
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="profile.php">
                                <i class="bi bi-person me-2"></i>
                                Mon compte
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-cine-secondary">
                        <i class="bi bi-camera-reels"></i> Tableau de bord Cinéma
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Partager</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Exporter</button>
                        </div>
                    </div>
                </div>

                <!-- Cards Section -->
                <div class="row mb-4">
                    <!-- Card Films populaires -->
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-cine-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Films populaires</h5>
                                        <p class="card-text">Découvrez les films tendance</p>
                                    </div>
                                    <i class="bi bi-fire card-icon"></i>
                                </div>
                                <a href="movies.php?filter=popular" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Ma watchlist -->
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-cine-secondary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Ma watchlist</h5>
                                        <p class="card-text">Vos films à regarder</p>
                                    </div>
                                    <i class="bi bi-bookmark-plus card-icon"></i>
                                </div>
                                <a href="watchlist.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Administration (visible seulement pour les admins) -->
                    <?php if ($role === 'admin'): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Espace Admin</h5>
                                        <p class="card-text">Gestion complète du site</p>
                                    </div>
                                    <i class="bi bi-shield-lock card-icon"></i>
                                </div>
                                <a href="admin/dashboard.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Section Derniers films ajoutés -->
                <div class="card mb-4">
                    <div class="card-header bg-cine-primary text-white">
                        <h5><i class="bi bi-arrow-up-circle"></i> Nouveautés</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Exemple de film 1 -->
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card recent-movie h-100">
                                    <img src="https://via.placeholder.com/300x450?text=Affiche+Film" class="card-img-top movie-poster" alt="Film">
                                    <div class="card-body">
                                        <h5 class="card-title">Dune: Partie 2</h5>
                                        <p class="card-text text-muted">Science-fiction</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">2024</small>
                                            <span class="badge bg-warning text-dark">4.8/5</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exemple de film 2 -->
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card recent-movie h-100">
                                    <img src="https://via.placeholder.com/300x450?text=Affiche+Film" class="card-img-top movie-poster" alt="Film">
                                    <div class="card-body">
                                        <h5 class="card-title">The Batman</h5>
                                        <p class="card-text text-muted">Action</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">2022</small>
                                            <span class="badge bg-warning text-dark">4.5/5</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exemple de film 3 -->
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card recent-movie h-100">
                                    <img src="https://via.placeholder.com/300x450?text=Affiche+Film" class="card-img-top movie-poster" alt="Film">
                                    <div class="card-body">
                                        <h5 class="card-title">Oppenheimer</h5>
                                        <p class="card-text text-muted">Drame</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">2023</small>
                                            <span class="badge bg-warning text-dark">4.7/5</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exemple de film 4 -->
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card recent-movie h-100">
                                    <img src="https://via.placeholder.com/300x450?text=Affiche+Film" class="card-img-top movie-poster" alt="Film">
                                    <div class="card-body">
                                        <h5 class="card-title">Spider-Man: No Way Home</h5>
                                        <p class="card-text text-muted">Super-héros</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">2021</small>
                                            <span class="badge bg-warning text-dark">4.9/5</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="../page/listings.html" class="btn btn-cine-secondary">Voir tous les films</a>
                        </div>
                    </div>
                </div>

                <!-- Section Activité récente -->
                <div class="card mb-4">
                    <div class="card-header bg-cine-secondary text-white">
                        <h5><i class="bi bi-clock-history"></i> Votre activité récente</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-film text-cine-secondary me-2"></i>
                                    Vous avez ajouté "Dune" à votre watchlist
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime('-1 hour')); ?></small>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-star-fill text-warning me-2"></i>
                                    Vous avez noté "The Batman" 4/5
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime('-1 day')); ?></small>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Vous avez marqué "Inception" comme vu
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime('-2 days')); ?></small>
                            </li>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour rendre les cartes de film cliquables
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.recent-movie').forEach(card => {
                card.addEventListener('click', function() {
                    window.location.href = 'movie-details.php?id=' + this.dataset.id;
                });
            });
        });
    </script>
</body>
</html>