<?php
session_start();
require_once "includes/config.php";

// Vérifier si le fichier de base de données existe
if (!file_exists(DB_PATH)) {
    header("Location: install.php");
    exit();
}

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Si l'utilisateur est authentifié et la base de données existe, afficher le contenu principal
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - AgriRec</title>
    <link rel="stylesheet" href="includes/style.css">
</head>

<body class="cute-background">
    <header>
        <div class="logo-area">
            <a href="index.php" style="display: flex; align-items: center; gap: 0.5rem; color: inherit;">
                <img src="assets/logo.png" alt="AgriRec Logo">
                <h2 style="margin:0; font-size: 1.25rem;">AgriRec</h2>
            </a>
        </div>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">

        <div class="dashboard-grid">
            <a autofocus href="parcelles.php" class="nav-card">
                <h3>Gestion des parcelles PAC</h3>
                <p>Créez et listez vos parcelles</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>

            <a autofocus href="intervenants.php" class="nav-card">
                <h3>Gestion des intervenants externes</h3>
                <p>Créez et listez les intervenants externes</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>

            <a href="engrais.php" class="nav-card">
                <h3>Catalogue d'engrais</h3>
                <p>Créez et listez les engrais de votre ferme</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>

            <a href="phytosanitaires.php" class="nav-card">
                <h3>Catalogue phytosanitaire</h3>
                <p>Créez et listez les phytos de votre ferme</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>
        </div>
        <div class="dashboard-grid">
            <a href="interventions_engrais.php" class="nav-card">
                <h3>Intervention d'engrais</h3>
                <p>Saisir les apports sur vos parcelles</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>

            <a href="interventions_phyto.php" class="nav-card">
                <h3>Intervention phyto</h3>
                <p>Saisir les traitements phytosanitaires</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>
        </div>
        <div class="dashboard-grid">
            <a href="rapport-engrais.php" class="nav-card">
                <h3>Registre engrais</h3>
                <p>Historique et bilans des fertilisations</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>

            <a href="rapport-phyto.php" class="nav-card">
                <h3>Registre phyto</h3>
                <p>Historique et bilans phytosanitaires</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>
            <a href="stock-phyto.php" class="nav-card">
                <h3>Stock phyto</h3>
                <p>Utilisation des stocks phytosanitaires</p>
                <div style="margin-top: 1rem; font-weight: 600;">Accéder &rarr;</div>
            </a>
        </div>
    </div>
</body>

</html>
