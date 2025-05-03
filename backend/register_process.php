<?php
session_start();
include("cnx.php");

function showAlert($icon, $title, $text, $redirect = null) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$text',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            })";
    if ($redirect) {
        echo ".then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '$redirect';
                }
            })";
    }
    echo ";
        });
    </script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$cnx) {
        showAlert('error', 'Erreur de connexion', 'Impossible de se connecter à la base de données.');
    }

    $username = trim($_POST['user']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['password2']);
    
    // Validation des données
    if (empty($username) || empty($email) || empty($password)) {
        showAlert('error', 'Erreur', 'Tous les champs sont obligatoires.');
    }
    
    if ($password !== $confirm_password) {
        showAlert('error', 'Erreur', 'Les mots de passe ne correspondent pas.');
    }
    
    if (strlen($password) < 6) {
        showAlert('error', 'Erreur', 'Le mot de passe doit contenir au moins 6 caractères.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showAlert('error', 'Erreur', 'L\'adresse email n\'est pas valide.');
    }
    
    // Vérification de l'existence de l'utilisateur
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $cnx->prepare($sql);
    
    if (!$stmt) {
        showAlert('error', 'Erreur', 'Erreur de préparation de la requête: ' . $cnx->error);
    }
    
    $stmt->bind_param("ss", $username, $email);
    if (!$stmt->execute()) {
        showAlert('error', 'Erreur', 'Erreur d\'exécution de la requête: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        showAlert('error', 'Erreur', 'Ce nom d\'utilisateur ou email est déjà utilisé.');
    }
    
    // Hachage du mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertion du nouvel utilisateur
    $sql = "INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $cnx->prepare($sql);
    
    if (!$stmt) {
        showAlert('error', 'Erreur', 'Erreur de préparation de la requête: ' . $cnx->error);
    }
    
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = false;
        
        // Redirection dans SweetAlert
        showAlert('success', 'Inscription réussie', 'Bienvenue '.htmlspecialchars($username).' !', '../page/auth_login.html');
    } else {
        showAlert('error', 'Erreur', 'Une erreur est survenue lors de l\'inscription: ' . $stmt->error);
    }
}
?>