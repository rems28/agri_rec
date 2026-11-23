<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Vérifier si l'utilisateur est authentifié
if (isset($_SESSION['user_id'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

?>