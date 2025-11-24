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
                $parcelle_id = intval($_POST["parcelle_id"]);
                $engrais_id = intval($_POST["engrais_id"]);
                $date = clean_input($_POST["date"]);
                $quantite = floatval($_POST["quantite"]);
                $annee_culturale = intval($_POST["annee_culturale"]);

                $stmt = $db->prepare(
                    "INSERT INTO interventions_engrais (parcelle_id, engrais_id, date, quantite, annee_culturale) VALUES (:parcelle_id, :engrais_id, :date, :quantite, :annee_culturale)",
                );
                $stmt->bindValue(":parcelle_id", $parcelle_id, SQLITE3_INTEGER);
                $stmt->bindValue(":engrais_id", $engrais_id, SQLITE3_INTEGER);
                $stmt->bindValue(":date", $date, SQLITE3_TEXT);
                $stmt->bindValue(":quantite", $quantite, SQLITE3_FLOAT);
                $stmt->bindValue(
                    ":annee_culturale",
                    $annee_culturale,
                    SQLITE3_INTEGER,
                );
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $parcelle_id = intval($_POST["parcelle_id"]);
                $engrais_id = intval($_POST["engrais_id"]);
                $date = clean_input($_POST["date"]);
                $quantite = floatval($_POST["quantite"]);
                $annee_culturale = intval($_POST["annee_culturale"]);

                $stmt = $db->prepare(
                    "UPDATE interventions_engrais SET parcelle_id = :parcelle_id, engrais_id = :engrais_id, date = :date, quantite = :quantite, annee_culturale = :annee_culturale WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":parcelle_id", $parcelle_id, SQLITE3_INTEGER);
                $stmt->bindValue(":engrais_id", $engrais_id, SQLITE3_INTEGER);
                $stmt->bindValue(":date", $date, SQLITE3_TEXT);
                $stmt->bindValue(":quantite", $quantite, SQLITE3_FLOAT);
                $stmt->bindValue(
                    ":annee_culturale",
                    $annee_culturale,
                    SQLITE3_INTEGER,
                );
                $stmt->execute();
                break;

            case "delete":
                $id = intval($_POST["id"]);

                $stmt = $db->prepare(
                    "DELETE FROM interventions_engrais WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->execute();
                break;
        }
    }
}

$parcelles = $db->query("SELECT * FROM parcelles");

$engrais = $db->query("SELECT * FROM engrais");

$interventions = $db->query('SELECT ie.*, p.nom as parcelle_nom, e.nom as engrais_nom, e.unite as engrais_unite
                             FROM interventions_engrais ie
                             JOIN parcelles p ON ie.parcelle_id = p.id
                             JOIN engrais e ON ie.engrais_id = e.id
                             ORDER BY ie.date, p.nom');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des interventions phytosanitaires</title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>
    <h3> Navigation dans les pages de gestion </h3>
    <ul>
        <li><a href="engrais.php">Création des engrais</a></li>
        <li><a href="parcelles.php">Création des parcelles</a></li>
        <li><a href="rapport-engrais.php">Visualisation des interventions engrais</a></li>
        <li><a href="index.php">Retour à l'accueil</a></li>
    </ul>

    <h1>Gestion des interventions d'engrais</h1>

    <h3>Ajouter une intervention</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <select name="parcelle_id" required>
            <option value="" disabled selected>Choisir une parcelle</option>
            <?php while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)): ?>
                <option value="<?php echo $parcelle[
                    "id"
                ]; ?>"><?php echo $parcelle["nom"]; ?></option>
            <?php endwhile; ?>
        </select>
        <select name="engrais_id" required>
            <option value="" disabled selected>Choisir un engrais</option>
            <?php while ($eng = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
                <option value="<?php echo $eng["id"]; ?>"><?php echo $eng[
    "nom"
]; ?></option>
            <?php endwhile; ?>
        </select>
        <input type="date" name="date" required>
        <input type="number" name="quantite" placeholder="Quantité totale" required>
        <input type="number" name="annee_culturale" min="<?php echo date("Y") -
            1; ?>" max="<?php echo date("Y") +
    3; ?>" step="1" value="<?php echo date(
    "Y",
); ?>" placeholder="Année culturale" required>
        <input type="submit" value="Ajouter">
    </form>

    <h3>Liste des interventions</h3>
    <table>
        <tr>
            <th>Année culturale</th>
            <th>Date</th>
            <th>Parcelle</th>
            <th>Engrais</th>
            <th>Unité</th>
            <th>Quantité totale</th>
            <th>Actions</th>
        </tr>
        <?php while (
            $intervention = $interventions->fetchArray(SQLITE3_ASSOC)
        ): ?>
        <tr>
            <td><?php echo htmlspecialchars(
                $intervention["annee_culturale"],
            ); ?></td>
            <td><?php echo htmlspecialchars($intervention["date"]); ?></td>
            <td><?php echo htmlspecialchars_decode(
                $intervention["parcelle_nom"],
            ); ?></td>
            <td><?php echo htmlspecialchars_decode(
                $intervention["engrais_nom"],
            ); ?></td>
            <td><?php echo htmlspecialchars(
                $intervention["engrais_unite"],
            ); ?></td>
            <td><?php echo htmlspecialchars($intervention["quantite"]); ?></td>

            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $intervention[
                        "id"
                    ]; ?>">
                    <input type="submit" value="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette intervention ?');">
                </form>
                <button onclick="showUpdateForm(<?php echo htmlspecialchars(
                    json_encode($intervention),
                ); ?>)">Modifier</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div id="updateForm" style="display:none;">
        <h3>Modifier une intervention</h3>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="update_id">

            <table>
                <tr>
                    <td>Parcelle</td>
                    <td>Engrais</td>
                    <td>Date</td>
                    <td>Quantité totale</td>
                    <td>Année culturale</td>
                </tr>
                <tr>
                    <td><select name="parcelle_id" id="update_parcelle_id" required>
                        <option value="" disabled selected>Choisir une parcelle</option>
                        <?php
                        $parcelles->reset();
                        while (
                            $parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)
                        ): ?>
                            <option value="<?php echo $parcelle[
                                "id"
                            ]; ?>"><?php echo $parcelle["nom"]; ?></option>
                        <?php endwhile;
                        ?>
                    </select></td>
                    <td><select name="engrais_id" id="update_engrais_id" required>
                        <option value="" disabled selected>Choisir une engrais</option>
                        <?php
                        $engrais->reset();
                        while ($eng = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $eng[
                                "id"
                            ]; ?>"><?php echo $eng["nom"]; ?></option>
                        <?php endwhile;
                        ?>
                    </select></td>
                    <td><input type="date" name="date" id="update_date" required></td>
                    <td><input type="number" name="quantite" id="update_quantite" step="0.01" required></td>
                    <td><input type="number" name="annee_culturale" id="update_annee_culturale" required></td>
                </tr>
            </table>

            <input type="submit" value="Modifier">
        </form>
    </div>

    <script>
    function showUpdateForm(intervention) {
        document.getElementById('updateForm').style.display = 'block';
        document.getElementById('update_id').value = intervention.id;
        document.getElementById('update_parcelle_id').value = intervention.parcelle_id;
        document.getElementById('update_engrais_id').value = intervention.engrais_id;
        document.getElementById('update_date').value = intervention.date;
        document.getElementById('update_quantite').value = intervention.quantite;
        document.getElementById('update_annee_culturale').value = intervention.annee_culturale;
    }
    </script>

</body>
</html>
