<?php
session_start();
include("cnx.php");

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth_login.html");
    exit();
}

// Récupération de l'ID du film
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Requête pour les détails du film
$query = $cnx->prepare("SELECT * FROM movies WHERE id = ?");
$query->bind_param("i", $movie_id);
$query->execute();
$movie = $query->get_result()->fetch_assoc();

if (!$movie) {
    die("Film non trouvé");
}

// Requête pour les films similaires
$similar_query = $cnx->prepare("SELECT * FROM movies WHERE genre = ? AND id != ? LIMIT 6");
$similar_query->bind_param("si", $movie['genre'], $movie_id);
$similar_query->execute();
$similar_movies = $similar_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> | MovieFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
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
        .movie-detail {
            display: flex;
            gap: 40px;
            padding: 80px 40px 40px;
            background: linear-gradient(to right, rgba(20, 20, 20, 0.95) 0%, rgba(20, 20, 20, 0.7) 100%);
        }

        .movie-poster {
            flex: 0 0 300px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            height: 450px;
        }

        .movie-poster img {
            width: 100%;
            height: auto;
        }

        .movie-info {
            flex: 1;
            color: var(--light-text);
        }

        .movie-meta {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .rating {
            color: var(--primary-color);
        }

        .user-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .section {
            padding: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .carousel {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
        <div class="movie-detail">
            <div class="movie-poster">
                <img src="<?= htmlspecialchars($movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            </div>
            <div class="movie-info">
                <h1><?= htmlspecialchars($movie['title']) ?></h1>
                <div class="movie-meta">
                    <span><?= date('Y', strtotime($movie['year'])) ?></span>
                    <span>PG-13</span>
                    <span><?= htmlentities($movie['duration']) ?> min</span>
                </div>
                <p class="description"><?= htmlspecialchars($movie['description']) ?></p>

                <div class="user-actions">
                    <a href="<?= htmlspecialchars($movie['link'])?>"><button class="btn btn-danger btn-lg">
                        <i class="bi bi-play-fill"></i> Lire
                    </button></a>
                    <form action="add_to_watchlist.php" method="POST">
                        <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                        <button type="submit" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-bookmark-plus"></i> Ma liste
                        </button>
                    </form>
                </div>

                <div class="section mt-4">
                    <h4>Plus d'informations</h4>
                    <p><strong>Réalisateur :</strong> <?= htmlspecialchars($movie['director']) ?></p>
                    <p><strong>Genres :</strong> <?= htmlspecialchars($movie['genre']) ?></p>
                </div>
            </div>
        </div>

        <!-- Films similaires -->
        <div class="section">
            <h3>Contenu similaire</h3>
            <div class="carousel">
                <?php foreach ($similar_movies as $similar): ?>
                    <a href="movie-details.php?id=<?= $similar['id'] ?>" class="similar-movie">
                        <img src="<?= htmlspecialchars($similar['poster_url']) ?>" 
                             alt="<?= htmlspecialchars($similar['title']) ?>"
                             class="img-fluid rounded">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation de l'en-tête
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            header.classList.toggle('scrolled', window.scrollY > 100);
        });

        // Gestion de la watchlist
        document.querySelectorAll('.add-to-watchlist').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const form = e.target.closest('form');
                const formData = new FormData(form);

                try {
                    const response = await fetch('api/watchlist.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        button.innerHTML = result.inWatchlist ? 
                            '<i class="bi bi-bookmark-check"></i> Dans ma liste' : 
                            '<i class="bi bi-bookmark-plus"></i> Ma liste';
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                }
            });
        });
    </script>
</body>
</html>