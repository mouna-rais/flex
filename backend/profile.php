<?php
session_start();
include("cnx.php");

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth_login.html");
    exit();
}

// Récupération des données utilisateur
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // Récupération des infos utilisateur
    $stmt = $cnx->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Utilisateur introuvable");
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = trim($_POST['new_password']);
        
        // Vérification mot de passe actuel
        if (!password_verify($current_password, $user['password_hash'])) {
            throw new Exception("Mot de passe actuel incorrect");
        }
        
        // Mise à jour des informations
        $update_fields = [];
        $params = [];
        $types = '';
        
        if ($username !== $user['username']) {
            $update_fields[] = "username = ?";
            $params[] = $username;
            $types .= 's';
            $_SESSION['username'] = $username;
        }
        
        if ($email !== $user['email']) {
            // Vérification email unique
            $check = $cnx->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Cet email est déjà utilisé");
            }
            
            $update_fields[] = "email = ?";
            $params[] = $email;
            $types .= 's';
            $_SESSION['email'] = $email;
        }
        
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password_hash = ?";
            $params[] = $hashed_password;
            $types .= 's';
        }
        
        if (!empty($update_fields)) {
            $types .= 'i';
            $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $cnx->prepare($query);
            $params[] = $user_id;
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour");
            }
            
            $success = "Profil mis à jour avec succès";
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MovieFlex</title>
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
        
        .profile-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .form-control {
            background-color: #333;
            border: 1px solid #444;
            color: white;
        }
        
        .form-control:focus {
            background-color: #444;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2">
                        <i class="bi bi-person-circle me-2"></i>Mon Profil
                    </h1>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="profile-card p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control" name="username" 
                                           value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Adresse email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                    <small class="text-muted">Requis pour toute modification</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="new_password">
                                    <small class="text-muted">Laisser vide pour ne pas modifier</small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-lg btn-danger">
                                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation des mots de passe
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('[name="new_password"]');
            
            if (newPassword.value.length > 0 && newPassword.value.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères');
            }
        });
    </script>
</body>
</html>