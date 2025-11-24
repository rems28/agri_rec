<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$db = getDB();

function clean_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "create":
                $nom = clean_input($_POST["nom"]);
                $unite = clean_input($_POST["unite"]);
                $NO3 = floatval($_POST["NO3"]);
                $P2O5 = floatval($_POST["P2O5"]);
                $K2O = floatval($_POST["K2O"]);
                $SO3 = floatval($_POST["SO3"]);
                $MgO = floatval($_POST["MgO"]);
                $CaO = floatval($_POST["CaO"]);

                $stmt = $db->prepare(
                    "INSERT INTO engrais (nom, unite, NO3, P2O5, K2O, SO3, MgO, CaO) VALUES (:nom, :unite, :NO3, :P2O5, :K2O, :SO3, :MgO, :CaO)",
                );
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":unite", $unite, SQLITE3_TEXT);
                $stmt->bindValue(":NO3", $NO3, SQLITE3_FLOAT);
                $stmt->bindValue(":P2O5", $P2O5, SQLITE3_FLOAT);
                $stmt->bindValue(":K2O", $K2O, SQLITE3_FLOAT);
                $stmt->bindValue(":SO3", $SO3, SQLITE3_FLOAT);
                $stmt->bindValue(":MgO", $MgO, SQLITE3_FLOAT);
                $stmt->bindValue(":CaO", $CaO, SQLITE3_FLOAT);
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $nom = clean_input($_POST["nom"]);
                $unite = clean_input($_POST["unite"]);
                $NO3 = floatval($_POST["NO3"]);
                $P2O5 = floatval($_POST["P2O5"]);
                $K2O = floatval($_POST["K2O"]);
                $SO3 = floatval($_POST["SO3"]);
                $MgO = floatval($_POST["MgO"]);
                $CaO = floatval($_POST["CaO"]);

                $stmt = $db->prepare(
                    "UPDATE engrais SET nom = :nom, unite = :unite, NO3 = :NO3, P2O5 = :P2O5, K2O = :K2O, SO3 = :SO3, MgO = :MgO, CaO = :CaO WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":unite", $unite, SQLITE3_TEXT);
                $stmt->bindValue(":NO3", $NO3, SQLITE3_FLOAT);
                $stmt->bindValue(":P2O5", $P2O5, SQLITE3_FLOAT);
                $stmt->bindValue(":K2O", $K2O, SQLITE3_FLOAT);
                $stmt->bindValue(":SO3", $SO3, SQLITE3_FLOAT);
                $stmt->bindValue(":MgO", $MgO, SQLITE3_FLOAT);
                $stmt->bindValue(":CaO", $CaO, SQLITE3_FLOAT);
                $stmt->execute();
                break;

            case "delete":
                $id = intval($_POST["id"]);

                $stmt = $db->prepare("DELETE FROM engrais WHERE id = :id");
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->execute();
                break;
        }
    }
}

$engrais = $db->query("SELECT * FROM engrais");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des engrais</title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>

    <h3> Navigation dans les pages de gestion </h3>
    <ul>
        <li><a href="interventions_engrais.php">Création d'une intervention engrais</a></li>
        <li><a href="index.php">Retour à l'accueil</a></li>
    </ul>

    <h1>Gestion des engrais</h1>

    <h3>Ajouter un engrais</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="nom" placeholder="Nom de l'engrais" required>
        <input type="text" name="unite" placeholder="Unité" required>
        <input type="number" name="NO3" step="0.001" placeholder="% NO3" required>
        <input type="number" name="P2O5" step="0.001" placeholder="% P2O5" required>
        <input type="number" name="K2O" step="0.001" placeholder="% K2O" required>
        <input type="number" name="SO3" step="0.001" placeholder="% SO3" required>
        <input type="number" name="MgO" step="0.001" placeholder="% MgO" required>
        <input type="number" name="CaO" step="0.001" placeholder="% CaO" required>
        <input type="submit" value="Ajouter">
    </form>

    <h3>Liste des engrais</h3>
    <table>
        <tr>
            <th>Nom</th>
            <th>Unité</th>
            <th>% NO3</th>
            <th>% P2O5</th>
            <th>% K2O</th>
            <th>% SO3</th>
            <th>% MgO</th>
            <th>% CaO</th>
            <th>Actions</th>
        </tr>
        <?php while ($engrais_item = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?php echo htmlspecialchars_decode(
                $engrais_item["nom"],
            ); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["unite"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["NO3"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["P2O5"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["K2O"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["SO3"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["MgO"]); ?></td>
            <td><?php echo htmlspecialchars($engrais_item["CaO"]); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $engrais_item[
                        "id"
                    ]; ?>">
                    <input type="submit" value="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet engrais ?');">
                </form>
                <button onclick="showUpdateForm(<?php echo htmlspecialchars(
                    json_encode($engrais_item),
                ); ?>)">Modifier</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div id="updateForm" style="display:none;">
        <h3>Modifier un engrais</h3>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="update_id">

            <table>
                <tr>
                    <td>Nom</td>
                    <td>Unité</td>
                    <td>% NO3</td>
                    <td>% P2O5</td>
                    <td>% K2O</td>
                    <td>% SO3</td>
                    <td>% MgO</td>
                    <td>% CaO</td>
                </tr>
                <tr>
                    <td><input type="text" name="nom" id="update_nom" required></td>
                    <td><input type="text" name="unite" id="update_unite" required></td>
                    <td><input type="number" name="NO3" id="update_NO3" step="0.001" required></td>
                    <td><input type="number" name="P2O5" id="update_P2O5" step="0.001" required></td>
                    <td><input type="number" name="K2O" id="update_K2O" step="0.001" required></td>
                    <td><input type="number" name="SO3" id="update_SO3" step="0.001" required></td>
                    <td><input type="number" name="MgO" id="update_MgO" step="0.001" required></td>
                    <td><input type="number" name="CaO" id="update_CaO" step="0.001" required></td>
                </tr>
            </table>

            <input type="submit" value="Modifier">
        </form>
    </div>

    <script src="includes/functions.js"></script>
    <script>
    function showUpdateForm(engrais) {
        document.getElementById('updateForm').style.display = 'block';
        document.getElementById('update_id').value = engrais.id;
        document.getElementById('update_nom').value = htmlSpecialCharsDecode(engrais.nom);
        document.getElementById('update_unite').value = engrais.unite;
        document.getElementById('update_NO3').value = engrais.NO3;
        document.getElementById('update_P2O5').value = engrais.P2O5;
        document.getElementById('update_K2O').value = engrais.K2O;
        document.getElementById('update_SO3').value = engrais.SO3;
        document.getElementById('update_MgO').value = engrais.MgO;
        document.getElementById('update_CaO').value = engrais.CaO;
    }
    </script>

</body>
</html>
