<?php
session_start();
include("cnx.php");

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth_login.html");
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    try {
        // Empêcher la suppression de l'admin actuel
        if ($user_id === $_SESSION['user_id']) {
            throw new Exception("Vous ne pouvez pas supprimer votre propre compte ici");
        }

        $cnx->begin_transaction();

        // Suppression des relations
        $tables = ['reviews', 'favorites'];
        foreach ($tables as $table) {
            $stmt = $cnx->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        // Suppression de l'utilisateur
        $stmt = $cnx->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $cnx->commit();
        $_SESSION['success'] = "Utilisateur supprimé avec succès";

    } catch (Exception $e) {
        $cnx->rollback();
        $_SESSION['error'] = "Erreur de suppression : " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $new_password = trim($_POST['new_password']);

    try {
        // Vérifier l'existence de l'utilisateur
        $stmt = $cnx->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) throw new Exception("Utilisateur introuvable");

        // Mise à jour des informations
        $update_fields = [];
        $params = [];
        $types = '';

        // Vérifier l'email unique
        if ($email !== $user['email']) {
            $check = $cnx->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Cet email est déjà utilisé");
            }
            $update_fields[] = "email = ?";
            $params[] = $email;
            $types .= 's';
        }

        // Mise à jour du mot de passe
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password_hash = ?";
            $params[] = $hashed_password;
            $types .= 's';
        }

        // Mise à jour du rôle
        if (in_array($role, ['user', 'admin'])) {
            $update_fields[] = "role = ?";
            $params[] = $role;
            $types .= 's';
        }

        // Mise à jour du username
        if ($username !== $user['username']) {
            $update_fields[] = "username = ?";
            $params[] = $username;
            $types .= 's';
        }

        if (!empty($update_fields)) {
            $types .= 'i'; // Pour le paramètre user_id
            $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $cnx->prepare($query);
            $params[] = $user_id;
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $_SESSION['success'] = "Profil mis à jour avec succès";
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur de mise à jour : " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}

// Récupération des utilisateurs
$users = [];
try {
    $result = $cnx->query("
        SELECT id, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC
    ");
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de récupération : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.03);
        }
        .role-badge {
            font-size: 0.8em;
            padding: 0.35em 0.65em;
        }
        #tab, #f, #u{
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="dashbord.php">
            <i class="bi bi-camera-reels me-2" ></i>MovieFlex Admin
        </a>

        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashbord.php' ? 'active' : '' ?>" 
                       href="dashbord.php">
                        <i class="bi bi-speedometer2" id="tab"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : '' ?>" 
                       href="movies.php">
                        <i class="bi bi-film" id="f"></i>Films
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" 
                       href="users.php">
                        <i class="bi bi-people" id="u"></i>Utilisateurs
                    </a>
                </li>
            </ul>

            <!-- User Dropdown -->
            <div class="d-flex align-items-center">
                <span class="text-light me-3">
                    <i class="bi bi-person-circle"></i> 
                    <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 0.8rem 1rem;
    background-color: #141414 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.navbar-brand {
    font-weight: 600;
    color: #e50914 !important;
    font-size: 1.25rem;
}

.navbar-brand:hover {
    color: #c40812 !important;
}

.nav-link {
    font-size: 0.95rem;
    transition: all 0.2s;
    position: relative;
}

.nav-link.active {
    color: #fff !important;
    font-weight: 500;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e50914;
}

.nav-link:hover:not(.active) {
    color: rgba(255, 255, 255, 0.75) !important;
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>

    <div class="container mt-5">
        <h2 class="mb-4">Gestion des utilisateurs</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Inscrit le</th>
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
                            <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?> role-badge">
                                <?= $user['role'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-btn" 
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
    </div>

    <!-- Modal d'édition -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier l'utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select class="form-select" name="role" id="editRole">
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" class="form-control" name="new_password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remplissage du modal d'édition
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