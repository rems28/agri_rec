<?php
require_once 'includes/db.php';

// Vérifier si le fichier de base de données n'existe pas
if (file_exists(DB_PATH)) {
    header('Location: login.php');
    exit();
}


// Création de l'utilisateur admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db = getDB();

    // Création des tables
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        entity TEXT NOT NULL,
        telepac INTEGER NOT NULL,
        password TEXT NOT NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS parcelles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        ilot INTEGER NOT NULL,
        surface REAL NOT NULL,
        culture TEXT NOT NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS engrais (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        unite TEXT NOT NULL,
        NO3 REAL,
        P2O5 REAL,
        K2O REAL,
        SO3 REAL,
        MgO REAL,
        CaO REAL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS produits_phytosanitaires (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        unite_emballage TEXT NOT NULL,
        amm TEXT NOT NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS interventions_engrais (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parcelle_id INTEGER,
        engrais_id INTEGER,
        date DATE NOT NULL,
        quantite REAL NOT NULL,
        annee_culturale INTEGER NOT NULL,
        FOREIGN KEY (parcelle_id) REFERENCES parcelles(id),
        FOREIGN KEY (engrais_id) REFERENCES engrais(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS interventions_phytosanitaires (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parcelle_id INTEGER,
        date DATE NOT NULL,
        annee_culturale INTEGER NOT NULL,
        stade TEXT NOT NULL,
        FOREIGN KEY (parcelle_id) REFERENCES parcelles(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS details_interventions_phytosanitaires (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        intervention_id INTEGER,
        produit_id INTEGER,
        volume_total REAL NOT NULL,
        cible TEXT NOT NULL,
        FOREIGN KEY (intervention_id) REFERENCES interventions_phytosanitaires(id),
        FOREIGN KEY (produit_id) REFERENCES produits_phytosanitaires(id)
    )");

    $username = htmlspecialchars(stripslashes(trim($_POST['username'])));
    $entity = htmlspecialchars(stripslashes(trim($_POST['entity'])));
    $telepac = htmlspecialchars(stripslashes(trim($_POST['telepac'])));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $db->prepare('INSERT OR IGNORE INTO users (username, entity, telepac, password) VALUES (:username, :entity, :telepac, :password)');
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':entity', $entity, SQLITE3_TEXT);
$stmt->bindValue(':telepac', $telepac, SQLITE3_TEXT);
$stmt->bindValue(':password', $password, SQLITE3_TEXT);
$stmt->execute();

    echo "Installation terminée. Les tables ont été créées et l'utilisateur $username a été ajouté.";
?>

<h3> <a href="login.php"> Accueil </a> </h3>
<?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration - AgriRec</title>
    <link rel="stylesheet" href="includes/style.css">
    <style>
        body {
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('assets/hero.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .setup-wizard {
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .setup-wizard section {
            background: white;
            padding: 3rem;
            border-radius: var(--radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .setup-wizard .logo {
            width: 80px;
            margin-bottom: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
        }

        .setup-wizard h1 {
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .setup-wizard p {
            color: var(--text-muted);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="setup-wizard">
        <section>
            <img src="assets/logo.png" alt="AgriRec Logo" class="logo">

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="success-message">
                <h3 style="margin-top:0;">Configuration Terminée !</h3>
                <p>La base de données a été initialisée avec succès.</p>
            </div>
            <a href="login.php" class="button"
                style="display: block; width: 100%; text-decoration: none; padding: 1rem;">Accéder au Tableau de
                Bord</a>
            <?php
else: ?>
            <h1>Bienvenue</h1>
            <p>Initialisez votre instance <b>AgriRec</b>. Veuillez créer le compte administrateur principal.</p>

            <form method="post">
                <div style="text-align: left; margin-bottom: 1.5rem;">
                    <label
                        style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Identifiant
                        Administrateur</label>
                    <input type="text" name="username" placeholder="ex: admin_exploitation" required
                        style="width:100%;">
                </div>

                <div style="text-align: left; margin-bottom: 1.5rem;">
                    <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom de
                        société</label>
                    <input type="text" name="entity" placeholder="EARL du terrier" required style="width:100%;">
                </div>


                <div style="text-align: left; margin-bottom: 1.5rem;">
                    <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">N° de
                        compte Telepac</label>
                    <input type="text" name="telepac" placeholder="ex: 0123456" required style="width:100%;">
                </div>

                <div style="text-align: left; margin-bottom: 2.5rem;">
                    <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Mot de
                        passe sécurisé</label>
                    <input type="password" name="password" placeholder="••••••••" required style="width:100%;">
                </div>

                <button type="submit" style="width:100%; padding: 1.25rem; font-size: 1.1rem;">Démarrer
                    l'installation</button>
            </form>
            <?php
endif; ?>
        </section>
    </div>
</body>

</html>
