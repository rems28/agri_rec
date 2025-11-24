<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$db = getDB();

// Fonction pour nettoyer les entrées
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "create":
                $nom = clean_input($_POST["nom"]);
                $ilot = intval($_POST["ilot"]);
                $surface = floatval($_POST["surface"]);
                $culture = clean_input($_POST["culture"]);

                $stmt = $db->prepare(
                    "INSERT INTO parcelles (nom, ilot, surface, culture) VALUES (:nom, :ilot, :surface, :culture)",
                );
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":ilot", $ilot, SQLITE3_INTEGER);
                $stmt->bindValue(":surface", $surface, SQLITE3_FLOAT);
                $stmt->bindValue(":culture", $culture, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $nom = clean_input($_POST["nom"]);
                $ilot = intval($_POST["ilot"]);
                $surface = floatval($_POST["surface"]);
                $culture = clean_input($_POST["culture"]);

                $stmt = $db->prepare(
                    "UPDATE parcelles SET nom = :nom, ilot = :ilot, surface = :surface, culture = :culture WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":ilot", $ilot, SQLITE3_INTEGER);
                $stmt->bindValue(":surface", $surface, SQLITE3_FLOAT);
                $stmt->bindValue(":culture", $culture, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "delete":
                $id = intval($_POST["id"]);

                $stmt = $db->prepare("DELETE FROM parcelles WHERE id = :id");
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->execute();
                break;
        }
    }
}

// Récupération des parcelles
$parcelles = $db->query("SELECT * FROM parcelles");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des parcelles</title>
    <link rel="stylesheet" href="includes/style.css" />
</head>
<body>
    <h3> Navigation dans les pages de gestion </h3>
    <ul>
        <li><a href="engrais.php">Création / Liste des engrais</a></li>
        <li><a href="phytosanitaires.php">Création / Liste des produits phytosanitaires</a></li>
        <li><a href="interventions_phyto.php">Création d'une intervention phytosanitaire</a></li>
        <li><a href="interventions_engrais.php">Création d'une intervention engrais</a></li>
        <li><a href="index.php">Retour à l'accueil</a></li>
    </ul>
    <h1>Gestion des parcelles</h1>
    <!-- Formulaire de création -->
    <h3>Ajouter une parcelle</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="nom" placeholder="Nom de la parcelle" required>
        <input type="number" name="ilot" step="1" placeholder="Ilot" required>
        <input type="number" name="surface" step="0.01" placeholder="Surface" required>
        <input type="text" name="culture" placeholder="Type de culture" required>
        <input type="submit" value="Ajouter">
    </form>

    <!-- Liste des parcelles -->
    <h3>Liste des parcelles</h3>
    <table>
        <tr>
            <th>Nom</th>
            <th>Ilot</th>
            <th>Surface</th>
            <th>Culture</th>
            <th>Actions</th>
        </tr>
        <?php while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?php echo htmlspecialchars_decode($parcelle["nom"]); ?></td>
            <td><?php echo htmlspecialchars($parcelle["ilot"]); ?></td>
            <td><?php echo htmlspecialchars($parcelle["surface"]); ?></td>
            <td><?php echo htmlspecialchars_decode(
                $parcelle["culture"],
            ); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $parcelle[
                        "id"
                    ]; ?>">
                    <input type="submit" value="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette parcelle ?');">
                </form>
                <button onclick="showUpdateForm(<?php echo htmlspecialchars(
                    json_encode($parcelle),
                ); ?>)">Modifier</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Formulaire de modification (caché par défaut) -->
    <div id="updateForm" style="display:none;">
        <h3>Modifier une parcelle</h3>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="update_id">
            <table>
                <tr>
                    <td>Nom</td>
                    <td>Ilot</td>
                    <td>Surface</td>
                    <td>Culture</td>
                </tr>
                <tr>
                    <td><input type="text" name="nom" id="update_nom" required></td>
                    <td><input type="number" name="ilot" id="update_ilot" step="1" required></td>
                    <td><input type="number" name="surface" id="update_surface" step="0.01" required></td>
                    <td><input type="text" name="culture" id="update_culture" required></td>
                </tr>
            </table>

            <input type="submit" value="Modifier">
        </form>
    </div>

    <script src="includes/functions.js"></script>
    <script>
    function showUpdateForm(parcelle) {
        document.getElementById('updateForm').style.display = 'block';
        document.getElementById('update_id').value = parcelle.id;
        document.getElementById('update_nom').value = htmlSpecialCharsDecode(parcelle.nom);
        document.getElementById('update_ilot').value = parcelle.ilot;
        document.getElementById('update_surface').value = parcelle.surface;
        document.getElementById('update_culture').value = htmlSpecialCharsDecode(parcelle.culture);
    }
    </script>

</body>
</html>
