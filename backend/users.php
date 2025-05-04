<?php
session_start();
include("cnx.php");

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_login.html");
    exit();
}

// Traitement des actions
require_once 'user_actions.php';

// Récupération des utilisateurs
$users = [];
try {
    $stmt = $cnx->prepare("
        SELECT id, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête : " . $cnx->error);
    }
    
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de récupération : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs - MovieFlex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --dark-bg: #141414;
            --light-text: #f4f4f4;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--light-text);
            min-height: 100vh;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        
        .badge-admin {
            background-color: var(--primary-color);
            padding: 0.5em 0.75em;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-camera-reels me-2"></i>MovieFlex Admin
        </a>

        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" 
                       href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : '' ?>" 
                       href="movies.php">
                        <i class="bi bi-film me-2"></i>Films
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" 
                       href="users.php">
                        <i class="bi bi-people me-2"></i>Utilisateurs
                    </a>
                </li>
            </ul>

            <!-- User Section -->
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-gear me-2"></i>Paramètres
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 0.8rem 1rem;
    background-color: #141414 !important;
    transition: background-color 0.3s ease;
}

.navbar.scrolled {
    background-color: rgba(20, 20, 20, 0.95) !important;
    backdrop-filter: blur(10px);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: #e50914 !important;
    transition: color 0.3s ease;
}

.navbar-brand:hover {
    color: #c40812 !important;
}

.nav-link {
    font-size: 1rem;
    position: relative;
    transition: color 0.3s ease;
}

.nav-link.active {
    color: #fff !important;
    font-weight: 500;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e50914;
    border-radius: 2px;
}

.dropdown-menu {
    background-color: #2a2a2a;
    border: 1px solid #404040;
}

.dropdown-item {
    color: #fff;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: #e50914;
    color: white !important;
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        padding: 1rem 0;
    }
    
    .dropdown {
        margin-top: 1rem;
    }
}
</style>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? 'user';
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar min-vh-100">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Tableau de bord
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : '' ?>" 
                   href="movies.php">
                    <i class="bi bi-film me-2"></i>
                    Gestion des films
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" 
                   href="users.php">
                    <i class="bi bi-people me-2"></i>
                    Gestion des utilisateurs
                </a>
            </li>

            <?php if ($role === 'admin'): ?>
            <div class="sidebar-divider my-4"></div>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>" 
                   href="analytics.php">
                    <i class="bi bi-graph-up me-2"></i>
                    Statistiques
                </a>
            </li>
            <?php endif; ?>

            <div class="sidebar-divider my-4"></div>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" 
                   href="profile.php">
                    <i class="bi bi-person me-2"></i>
                    Mon profil
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Déconnexion
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    background-color: #000 !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-link {
    color: #adb5bd;
    transition: all 0.3s;
    padding: 0.75rem 1.5rem;
}

.nav-link.active {
    color: #fff !important;
    background-color: rgba(229, 9, 20, 0.2);
    border-left: 3px solid #e50914;
}

.nav-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.05);
}

.sidebar-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin: 1rem 0;
}
</style>

            <!-- Contenu principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h1 class="h2">
                        <i class="bi bi-people me-2"></i>Gestion des utilisateurs
                    </h1>
                </div>

                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Tableau des utilisateurs -->
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge badge-admin">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-user-id="<?= $user['id'] ?>"
                                            data-username="<?= htmlspecialchars($user['username']) ?>"
                                            data-email="<?= htmlspecialchars($user['email']) ?>"
                                            data-role="<?= $user['role'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal d'édition -->
    <?php include 'edit_user_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('editUserId').value = btn.dataset.userId;
                document.getElementById('editUsername').value = btn.dataset.username;
                document.getElementById('editEmail').value = btn.dataset.email;
                document.getElementById('editRole').value = btn.dataset.role;
            });
        });
    </script>
</body>
</html>