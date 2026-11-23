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
    header("Location: engrais.php");
}

$engrais = $db->query("SELECT * FROM engrais ORDER BY nom");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue d'engrais - AgriRec</title>
    <link rel="stylesheet" href="includes/style.css">
</head>

<body>
    <header>
        <div class="logo-area">
            <a href="index.php" style="display: flex; align-items: center; gap: 0.5rem; color: inherit;">
                <img src="assets/logo.png" alt="AgriRec Logo">
                <h2 style="margin:0; font-size: 1.25rem;">AgriRec</h2>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Tableau de bord</a></li>
                <li><a href="interventions_engrais.php">Nouvelle Intervention</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Catalogue d'engrais</h1>
            <p style="color: var(--text-muted);">Configurez votre catalogue de fertilisants et précisez leurs
                composants.</p>
        </div>

        <section class="card">
            <h3>Ajouter un nouvel engrais</h3>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(75px, 1fr)); gap: 1rem; width: 100%;">
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom</label>
                        <input type="text" name="nom" placeholder="ex: Ammonitrate" required style="width:100%;" autofocus>
                    </div>
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Unité</label>
                        <input type="text" name="unite" placeholder="ex: kg" required style="width:100%;">
                    </div>
                </div>

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 1rem; width: 100%; margin-top: 1rem;">
                    <div><label style="font-size: 0.75rem;">NO3 (%)</label><input type="number" name="NO3" step="0.001"
                            value="0" required style="width:100%;"></div>
                    <div><label style="font-size: 0.75rem;">P2O5 (%)</label><input type="number" name="P2O5"
                            step="0.001" value="0" required style="width:100%;"></div>
                    <div><label style="font-size: 0.75rem;">K2O (%)</label><input type="number" name="K2O" step="0.001"
                            value="0" required style="width:100%;"></div>
                    <div><label style="font-size: 0.75rem;">SO3 (%)</label><input type="number" name="SO3" step="0.001"
                            value="0" required style="width:100%;"></div>
                    <div><label style="font-size: 0.75rem;">MgO (%)</label><input type="number" name="MgO" step="0.001"
                            value="0" required style="width:100%;"></div>
                    <div><label style="font-size: 0.75rem;">CaO (%)</label><input type="number" name="CaO" step="0.001"
                            value="0" required style="width:100%;"></div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right; width: 100%;">
                    <input type="submit" value="Enregistrer l'engrais">
                </div>
            </form>
        </section>

        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3>Liste des engrais enregistrés</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Unité</th>
                            <th>NO3</th>
                            <th>P2O5</th>
                            <th>K2O</th>
                            <th>SO3</th>
                            <th>MgO</th>
                            <th>CaO</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($engrais_item = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars_decode($engrais_item["nom"]); ?>
                            </td>
                            <td style="color: var(--text-muted);">
                                <?php echo htmlspecialchars($engrais_item["unite"]); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["NO3"]); ?>%
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["P2O5"]); ?>%
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["K2O"]); ?>%
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["SO3"]); ?>%
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["MgO"]); ?>%
                            </td>
                            <td>
                                <?php echo htmlspecialchars($engrais_item["CaO"]); ?>%
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="secondary"
                                        onclick="showUpdateForm(<?php echo htmlspecialchars(json_encode($engrais_item)); ?>)">Modifier</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $engrais_item["id"]; ?>">
                                        <button type="submit" class="danger"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet engrais ?');">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php
endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Modal pour la modification -->
        <div id="updateFormBackdrop"
            style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div class="card" style="width: 100%; max-width: 800px; margin: 2rem;">
                <h3>Modifier l'engrais</h3>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="update_id">

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                        <div>
                            <label
                                style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom</label>
                            <input type="text" name="nom" id="update_nom" required style="width:100%;">
                        </div>
                        <div>
                            <label
                                style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Unité</label>
                            <input type="text" name="unite" id="update_unite" required style="width:100%;">
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 1rem; width: 100%; margin-top: 1rem;">
                        <div><label style="font-size: 0.75rem;">NO3</label><input type="number" name="NO3"
                                id="update_NO3" step="0.001" required style="width:100%;"></div>
                        <div><label style="font-size: 0.75rem;">P2O5</label><input type="number" name="P2O5"
                                id="update_P2O5" step="0.001" required style="width:100%;"></div>
                        <div><label style="font-size: 0.75rem;">K2O</label><input type="number" name="K2O"
                                id="update_K2O" step="0.001" required style="width:100%;"></div>
                        <div><label style="font-size: 0.75rem;">SO3</label><input type="number" name="SO3"
                                id="update_SO3" step="0.001" required style="width:100%;"></div>
                        <div><label style="font-size: 0.75rem;">MgO</label><input type="number" name="MgO"
                                id="update_MgO" step="0.001" required style="width:100%;"></div>
                        <div><label style="font-size: 0.75rem;">CaO</label><input type="number" name="CaO"
                                id="update_CaO" step="0.001" required style="width:100%;"></div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; width: 100%;">
                        <button type="button" class="secondary" onclick="hideUpdateForm()">Annuler</button>
                        <input type="submit" value="Mettre à jour">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/functions.js"></script>
    <script>
        function showUpdateForm(engrais) {
            document.getElementById('updateFormBackdrop').style.display = 'flex';
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

        function hideUpdateForm() {
            document.getElementById('updateFormBackdrop').style.display = 'none';
        }
    </script>
</body>

</html>
