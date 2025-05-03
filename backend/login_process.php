<?php
session_start();
include("cnx.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    
    // Vérification de l'existence de l'utilisateur
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $cnx->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Vérification du mot de passe
        if (password_verify($password, $user['password_hash'])) {
            // Création de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Déterminer la redirection en fonction du rôle
            $dashbord = ($user['role'] === 'admin') ? 'dashbord.php' : 'dashbord.php';
            
            // Message de succès
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Connexion réussie',
                'text' => 'Redirection vers votre tableau de bord...',
                'redirect' => $dashbord
            ];
            
            // Redirection
            header("Location: " . $dashbord);
            exit();
        }
    }
    
    // En cas d'échec
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Échec de la connexion',
        'text' => 'Identifiants incorrects',
        'redirect' => false
    ];
    
    header("Location: ../page/auth_login.html");
    exit();
}
?>