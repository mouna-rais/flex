<?php
session_start();
include("cnx.php");

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_login.html");
    exit();
}

// Récupération des statistiques
try {
    // Statistiques de base
    $total_users = $cnx->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $total_movies = $cnx->query("SELECT COUNT(*) FROM movies")->fetch_row()[0];
    $avg_rating = $cnx->query("SELECT ROUND(AVG(rating),1) FROM reviews")->fetch_row()[0];

    // Distribution des notes
    $ratings = $cnx->query("
        SELECT rating, COUNT(*) as count 
        FROM reviews 
        GROUP BY rating 
        ORDER BY rating DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // Activité récente
    $recent_users = $cnx->query("
        SELECT username, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    $recent_movies = $cnx->query("
        SELECT title, created_at 
        FROM movies 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de récupération des données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytique - MovieFlex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #e50914;
            --dark-bg: #141414;
            --light-text: #f4f4f4;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--light-text);
        }
        
        .stats-card {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #333;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .chart-container {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h1 class="h2"><i class="bi bi-graph-up me-2"></i>Tableau de bord analytique</h1>
                </div>

                <!-- Métriques principales -->
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                    <div class="col">
                        <div class="stats-card text-center p-4 rounded">
                            <h3 class="text-primary"><?= number_format($total_users) ?></h3>
                            <p class="mb-0">Utilisateurs inscrits</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stats-card text-center p-4 rounded">
                            <h3 class="text-primary"><?= number_format($total_movies) ?></h3>
                            <p class="mb-0">Films disponibles</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stats-card text-center p-4 rounded">
                            <h3 class="text-primary"><?= $avg_rating ?>/5</h3>
                            <p class="mb-0">Note moyenne</p>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <canvas id="ratingsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="bi bi-people me-2"></i>Nouveaux utilisateurs</h5>
                            <div class="list-group">
                                <?php foreach ($recent_users as $user): ?>
                                <div class="list-group-item bg-dark text-light border-secondary">
                                    <div class="d-flex justify-content-between">
                                        <span><?= htmlspecialchars($user['username']) ?></span>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="bi bi-film me-2"></i>Nouveaux films</h5>
                            <div class="list-group">
                                <?php foreach ($recent_movies as $movie): ?>
                                <div class="list-group-item bg-dark text-light border-secondary">
                                    <div class="d-flex justify-content-between">
                                        <span><?= htmlspecialchars($movie['title']) ?></span>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($movie['created_at'])) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Graphique des notes
        const ratingsCtx = document.getElementById('ratingsChart').getContext('2d');
        new Chart(ratingsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($ratings, 'rating')) ?>,
                datasets: [{
                    label: 'Distribution des notes',
                    data: <?= json_encode(array_column($ratings, 'count')) ?>,
                    backgroundColor: 'rgba(229, 9, 20, 0.8)',
                    borderColor: 'rgba(229, 9, 20, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#fff' }
                    },
                    x: {
                        ticks: { color: '#fff' }
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                }
            }
        });

        // Graphique circulaire activité
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Utilisateurs', 'Films'],
                datasets: [{
                    data: [<?= $total_users ?>, <?= $total_movies ?>],
                    backgroundColor: ['#e50914', '#333'],
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>