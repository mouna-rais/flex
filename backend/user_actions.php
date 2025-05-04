<?php
session_start();
include("cnx.php");

// Traitement de la suppression utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    try {
        // Validation de l'utilisateur courant
        if ($user_id === $_SESSION['user_id']) {
            throw new Exception("Auto-suppression interdite via cette interface");
        }

        $cnx->begin_transaction();

        // Suppression des dépendances
        $tables = ['reviews', 'favorites'];
        foreach ($tables as $table) {
            $query = "DELETE FROM `$table` WHERE user_id = ?";
            $stmt = $cnx->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Erreur préparation $table : " . $cnx->error);
            }
            
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Erreur exécution $table : " . $stmt->error);
            }
        }

        // Suppression de l'utilisateur
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $cnx->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erreur préparation suppression : " . $cnx->error);
        }
        
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Erreur exécution suppression : " . $stmt->error);
        }

        $cnx->commit();
        $_SESSION['success'] = "Utilisateur #$user_id supprimé avec succès";

    } catch (Exception $e) {
        $cnx->rollback();
        $_SESSION['error'] = "Erreur suppression : " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}

// Traitement de la mise à jour utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $new_password = trim($_POST['new_password']);

    try {
        // Vérification existence utilisateur
        $stmt = $cnx->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            throw new Exception("Utilisateur introuvable");
        }

        // Validation email unique
        if ($email !== $user['email']) {
            $check = $cnx->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("L'email $email est déjà utilisé");
            }
        }

        // Préparation mise à jour
        $update_fields = [];
        $params = [];
        $types = '';

        // Gestion mot de passe
        $password_hash = $user['password_hash'];
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                throw new Exception("Le mot de passe doit faire 8 caractères minimum");
            }
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password_hash = ?";
            $params[] = $password_hash;
            $types .= 's';
        }

        // Construction dynamique de la requête
        if ($username !== $user['username']) {
            $update_fields[] = "username = ?";
            $params[] = $username;
            $types .= 's';
        }

        if ($email !== $user['email']) {
            $update_fields[] = "email = ?";
            $params[] = $email;
            $types .= 's';
        }

        if (in_array($role, ['user', 'admin']) && $role !== $user['role']) {
            $update_fields[] = "role = ?";
            $params[] = $role;
            $types .= 's';
        }

        if (!empty($update_fields)) {
            $types .= 'i'; // Pour le user_id
            $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $cnx->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Erreur préparation mise à jour : " . $cnx->error);
            }
            
            $params[] = $user_id;
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur exécution mise à jour : " . $stmt->error);
            }
            
            $_SESSION['success'] = "Profil utilisateur #$user_id mis à jour";
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur mise à jour : " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}
?>