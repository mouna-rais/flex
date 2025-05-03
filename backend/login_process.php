<?php
session_start();
include("cnx.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
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
            $_SESSION['role'] = $user['role']; // Stocker le rôle complet
            
            // Message de succès avec redirection vers le dashboard
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Connexion réussie',
                'text' => 'Redirection vers votre tableau de bord...',
                'redirect' => 'dashbord.php'
            ];
            
            // Redirection immédiate vers le dashboard
            header("Location: dashbord.php");
            exit();
        }
    }
    
    // En cas d'échec
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Échec de la connexion',
        'text' => "Nom d\'utilisateur ou mot de passe incorrect",
        'redirect' => false,
    ];
    
    header("Location: login_process.php");
    exit();
}
?>