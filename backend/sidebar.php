<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? 'user';
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar min-vh-100">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashbord.php' ? 'active' : '' ?>" 
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
                <a class="nav-link text-danger" href="logout.php">
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