<?php
session_start();
include("cnx.php");

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

// Récupération des nouveautés depuis la base de données
try {
    $query = "SELECT * FROM movies ORDER BY year DESC LIMIT 4";
    $result = $cnx->query($query);
    
    // Vérification du succès de la requête
    if ($result === false) {
        throw new Exception("Erreur de requête : " . $cnx->error);
    }
    
    $new_movies = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
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
            --primary-color: #e50914;
            --hover-color: #f40612;
            --dark-color: #141414;
            --light-color: #f4f4f4;
            --secondary-color: #aaa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: var(--dark-color);
            color: var(--light-color);
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: var(--light-color);
        }
        
        ul {
            list-style: none;
        }
        
        img {
            width: 100%;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary-color);
            color: #fff;
            padding: 0.5rem 1.3rem;
            font-size: 1rem;
            text-align: center;
            border: none;
            cursor: pointer;
            margin-right: 0.5rem;
            outline: none;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.45);
            border-radius: 2px;
            transition: background 0.2s ease-in;
        }
        
        .btn:hover {
            background: var(--hover-color);
        }
        
        .btn-rounded {
            border-radius: 5px;
        }
        
        .btn.small {
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .btn.danger {
            background: #ff3333;
        }
        
        /* Header */
        header {
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7) 10%, rgba(0, 0, 0, 0));
            top: 0;
            width: 100%;
            z-index: 10;
            padding: 1rem 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        header.scrolled {
            background-color: var(--dark-color);
        }
        
        .logo {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 2rem;
        }
        
        nav {
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        nav ul {
            display: flex;
            margin-left: 2rem;
        }
        
        nav ul li {
            margin-left: 1.5rem;
        }
        
        nav ul li a {
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary-color);
        }
        
        .auth-buttons {
            margin-left: auto;
        }
        
        /* Dashboard Specific Styles */
        .sidebar {
            min-height: 100vh;
            background-color: var(--dark-color) !important;
            border-right: 1px solid #333;
        }
        
        .sidebar .nav-link {
            color: var(--secondary-color);
            transition: color 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--light-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .dashboard-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.7;
            color: var(--primary-color);
        }
        
        .movie-poster {
            height: 200px;
            object-fit: cover;
            border-radius: 4px 4px 0 0;
        }
        
        .recent-movie {
            transition: transform 0.3s;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .recent-movie:hover {
            transform: scale(1.05);
        }
        
        .badge-admin {
            background-color: var(--primary-color);
        }
        
        .badge-user {
            background-color: var(--secondary-color);
        }
        
        main {
            background-color: var(--dark-color);
            padding: 2rem;
        }
        
        .section-title {
            color: var(--light-color);
            margin-bottom: 1.5rem;
            font-weight: bold;
        }
        
        .list-group-item {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--light-color);
            border-color: #333;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation supérieure -->
    <header class="navbar-cine">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold logo text-white" href="#">
                <i class="bi bi-camera-reels me-2"></i>MovieFlex
            </a>
            <div class="d-flex align-items-center auth-buttons">
                <span class="text-white me-3">Bienvenue, <?php echo htmlspecialchars($username); ?></span>
                <span class="badge <?php echo $role === 'admin' ? 'badge-admin' : 'badge-user'; ?> me-3">
                    <?php echo ucfirst($role); ?>
                </span>
                <a href="logout.php" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashbord.php">
                                <i class="bi bi-speedometer2"></i>
                                Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="movies.php">
                                <i class="bi bi-film"></i>
                                Films
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="watchlist.php">
                                <i class="bi bi-bookmark-heart"></i>
                                Ma watchlist
                            </a>
                        </li>
                        <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="movies.php">
                                <i class="bi bi-pencil-square"></i>
                                Gestion des films
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i>
                                Gestion des utilisateurs
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i>
                                Mon compte
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenu principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h1 class="section-title">
                    <i class="bi bi-camera-reels"></i> Tableau de bord
                </h1>

                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Films populaires</h5>
                                        <p class="card-text text-secondary">Découvrez les films tendance</p>
                                    </div>
                                    <i class="bi bi-fire card-icon"></i>
                                </div>
                                <a href="watchlist.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Ma watchlist</h5>
                                        <p class="card-text text-secondary">Vos films à regarder</p>
                                    </div>
                                    <i class="bi bi-bookmark-plus card-icon"></i>
                                </div>
                                <a href="watchlist.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'admin'): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Espace Admin</h5>
                                        <p class="card-text text-secondary">Gestion complète du site</p>
                                    </div>
                                    <i class="bi bi-shield-lock card-icon"></i>
                                </div>
                                <a href="dashbord.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Section Nouveautés -->
                <div class="mb-4">
                    <h3 class="section-title"><i class="bi bi-arrow-up-circle"></i> Nouveautés</h3>
                    <div class="row">
                        <?php if (!empty($new_movies)): ?>
                            <?php foreach ($new_movies as $movie): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="recent-movie h-100" data-id="<?= $movie['id'] ?>">
                                        <img src="<?= htmlspecialchars($movie['poster_path']) ?>" 
                                             class="movie-poster" 
                                             alt="<?= htmlspecialchars($movie['title']) ?>">
                                        <div class="p-3">
                                            <h5><?= htmlspecialchars($movie['title']) ?></h5>
                                            <p class="text-secondary"><?= htmlspecialchars($movie['genre']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-secondary">
                                                    <?= date('Y', strtotime($movie['year'])) ?>
                                                </small>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-4">
                                <p class="text-secondary">Aucune nouveauté disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section Activité récente -->
                <div class="mb-4">
                    <h3 class="section-title"><i class="bi bi-clock-history"></i> Votre activité récente</h3>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-film text-primary me-2"></i>
                                Vous avez ajouté "Dune" à votre watchlist
                            </div>
                            <small class="text-secondary"><?= date('d/m/Y H:i', strtotime('-1 hour')) ?></small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                Vous avez noté "The Batman" 4/5
                            </div>
                            <small class="text-secondary"><?= date('d/m/Y H:i', strtotime('-1 day')) ?></small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Vous avez marqué "Inception" comme vu
                            </div>
                            <small class="text-secondary"><?= date('d/m/Y H:i', strtotime('-2 days')) ?></small>
                        </li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Redirection vers les détails du film
            document.querySelectorAll('.recent-movie').forEach(card => {
                card.addEventListener('click', function() {
                    const movieId = this.dataset.id;
                    window.location.href = 'movie-details.php?id=' + movieId;
                });
            });

            // Gestion du scroll de l'en-tête
            window.addEventListener('scroll', function() {
                const header = document.querySelector('header');
                header.classList.toggle('scrolled', window.scrollY > 50);
            });
        });
    </script>
</body>
</html>