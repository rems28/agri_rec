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
                $unite_emballage = clean_input($_POST["unite_emballage"]);
                $amm = clean_input($_POST["amm"]);

                $stmt = $db->prepare(
                    "INSERT INTO produits_phytosanitaires (nom, unite_emballage, amm) VALUES (:nom, :unite_emballage, :amm)",
                );
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(
                    ":unite_emballage",
                    $unite_emballage,
                    SQLITE3_TEXT,
                );
                $stmt->bindValue(":amm", $amm, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $nom = clean_input($_POST["nom"]);
                $unite_emballage = clean_input($_POST["unite_emballage"]);
                $amm = clean_input($_POST["amm"]);

                $stmt = $db->prepare(
                    "UPDATE produits_phytosanitaires SET nom = :nom, unite_emballage = :unite_emballage, amm = :amm WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(
                    ":unite_emballage",
                    $unite_emballage,
                    SQLITE3_TEXT,
                );
                $stmt->bindValue(":amm", $amm, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "delete":
                $id = intval($_POST["id"]);

                $stmt = $db->prepare(
                    "DELETE FROM produits_phytosanitaires WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->execute();
                break;
        }
    }
}

$produits = $db->query("SELECT * FROM produits_phytosanitaires ORDER BY nom");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des produits phytosanitaires</title>
    <link rel="stylesheet" href="includes/style.css" />
</head>
<body>
    <h3> Navigation dans les pages de gestion </h3>

    <ul>
        <li><a href="interventions_phyto.php">Création d'une intervention phytosanitaire</a></li>
    </ul>
    <br/>
    <p><a href="index.php">Retour à l'accueil</a></p>
    <h1>Gestion des produits phytosanitaires</h1>

    <h3>Ajouter un produit phytosanitaire</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="nom" placeholder="Nom du produit" required>
        <input type="text" name="unite_emballage" placeholder="Unité d'emballage" required>
        <input type="text" name="amm" placeholder="AMM" required>
        <input type="submit" value="Ajouter">
    </form>

    <h2>Liste des produits phytosanitaires</h2>
    <table>
        <tr>
            <th>Nom</th>
            <th>Unité d'emballage</th>
            <th>AMM</th>
            <th>Actions</th>
        </tr>
        <?php while ($produit = $produits->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?php echo htmlspecialchars_decode($produit["nom"]); ?></td>
            <td><?php echo htmlspecialchars(
                $produit["unite_emballage"],
            ); ?></td>
            <td><?php echo htmlspecialchars($produit["amm"]); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $produit[
                        "id"
                    ]; ?>">
                    <input type="submit" value="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                </form>
                <button onclick="showUpdateForm(<?php echo htmlspecialchars(
                    json_encode($produit),
                ); ?>)">Modifier</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div id="updateForm" style="display:none;">
        <h2>Modifier un produit phytosanitaire</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="update_id">

            <table>
                <tr>
                    <td>Nom</td>
                    <td>Unité d'emballage</td>
                    <td>AMM</td>
                </tr>
                <tr>
                    <td><input type="text" name="nom" id="update_nom" required></td>
                    <td><input type="text" name="unite_emballage" id="update_unite_emballage" required></td>
                    <td><input type="text" name="amm" id="update_amm" required></td>
                </tr>
            </table>
            <input type="submit" value="Modifier">
        </form>
    </div>

    <script src="includes/functions.js"></script>
    <script>
    function showUpdateForm(produit) {
        document.getElementById('updateForm').style.display = 'block';
        document.getElementById('update_id').value = produit.id;
        document.getElementById('update_nom').value = htmlSpecialCharsDecode(produit.nom);
        document.getElementById('update_unite_emballage').value = produit.unite_emballage;
        document.getElementById('update_amm').value = produit.amm;
    }
    </script>

</body>
</html>
