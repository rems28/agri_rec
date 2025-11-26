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
    <title>Création du couple utilisateur / mot de passe</title>
</head>
<body>
    <h1>Bienvenue dans l'installation</h1>
    <h2>Création du couple utilisateur / mot de passe et saisie du code telepac</h2>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <br/>
        <input type="text" name="entity" placeholder="Nom de société" required><br>
        <br/>
        <input type="text" name="telepac" placeholder="Code telepac" required><br>
        <br/>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <br/>
        <input type="submit" value="Création">
    </form>
</body>
</html>
