<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="dashbord.php">
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
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashbord.php' ? 'active' : '' ?>" 
                       href="dashbord.php">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : '' ?>" 
                       href="movies.php">
                        <i class="bi bi-film me-2"></i>Films
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
                            <a class="dropdown-item text-danger" href="logout.php">
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