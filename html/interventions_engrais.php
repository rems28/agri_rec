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
    header("Location: interventions_engrais.php");
}

$parcelles = $db->query("SELECT * FROM parcelles ORDER BY nom");

$engrais = $db->query("SELECT * FROM engrais ORDER BY nom");

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
    <title>Interventions Engrais - AgriRec</title>
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
                <li><a href="engrais.php">Catalogue Engrais</a></li>
                <li><a href="rapport-engrais.php">Rapports</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Interventions d'engrais</h1>
            <p style="color: var(--text-muted);">Enregistrez les apports de fertilisants effectués sur vos parcelles.
            </p>
        </div>

        <section class="card">
            <h3>Saisir une nouvelle intervention</h3>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Parcelle</label>
                        <select name="parcelle_id" required style="width:100%;" autofocus>
                            <option value="" disabled selected>Choisir une parcelle</option>
                            <?php
$parcelles->reset();
while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $parcelle["id"]; ?>">
                                <?php echo htmlspecialchars_decode($parcelle["nom"]); ?> (<?php echo htmlspecialchars_decode($parcelle["surface"]); ?> ha)
                            </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Engrais
                            utilisé</label>
                        <select name="engrais_id" required style="width:100%;">
                            <option value="" disabled selected>Choisir un engrais</option>
                            <?php
$engrais->reset();
while ($eng = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $eng["id"]; ?>">
                                <?php echo htmlspecialchars_decode($eng["nom"]); ?> (<?php echo htmlspecialchars_decode($eng["unite"]); ?>)
                            </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                </div>

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; width: 100%; margin-top: 1rem;">
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Date
                            d'application</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required
                            style="width:100%;">
                    </div>
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Quantité
                            totale</label>
                        <input type="number" name="quantite" step="0.01" placeholder="ex: 150" required
                            style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Année
                            culturale</label>
                        <input type="number"
                          name="annee_culturale"
                          min="<?php echo date(" Y") - 1; ?>"
                          max="<?php echo date("Y") + 3; ?>"
                          step="1"
                          value="<?php echo date("Y"); ?>"
                          required
                          style="width:100%;">
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right; width: 100%;">
                    <input type="submit" value="Enregistrer l'intervention">
                </div>
            </form>
        </section>

        <section>
            <h3>Historique des interventions</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Campagne</th>
                            <th>Parcelle</th>
                            <th>Date</th>
                            <th>Engrais</th>
                            <th>Quantité</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$interventions->reset();
while ($intervention = $interventions->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><span style="font-weight: 600; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($intervention["annee_culturale"]); ?>
                                </span></td>
                            <td>
                                <?php echo htmlspecialchars_decode($intervention["parcelle_nom"]); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($intervention["date"])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars_decode($intervention["engrais_nom"]); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($intervention["quantite"]); ?>
                                <?php echo htmlspecialchars($intervention["engrais_unite"]); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="secondary"
                                        onclick='showUpdateForm(<?php echo json_encode($intervention); ?>)'>Modifier</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $intervention["id"]; ?>">
                                        <button type="submit" class="danger"
                                            onclick="return confirm('Supprimer cette intervention ?');">Supprimer</button>
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

        <!-- Modal de modification -->
        <div id="updateFormBackdrop"
            style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div class="card" style="width: 100%; max-width: 700px; margin: 2rem;">
                <h3>Modifier l'intervention</h3>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="update_id">

                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; margin-bottom: 1rem;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600;">Parcelle</label>
                            <select name="parcelle_id" id="update_parcelle_id" required style="width:100%;">
                                <?php
$parcelles->reset();
while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $parcelle["id"]; ?>">
                                    <?php echo htmlspecialchars_decode($parcelle["nom"]); ?> (<?php echo htmlspecialchars_decode($parcelle["surface"]); ?> ha)
                                </option>
                                <?php
endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600;">Engrais</label>
                            <select name="engrais_id" id="update_engrais_id" required style="width:100%;">
                                <?php
$engrais->reset();
while ($eng = $engrais->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $eng["id"]; ?>">
                                    <?php echo htmlspecialchars_decode($eng["nom"]); ?> (<?php echo htmlspecialchars_decode($eng["unite"]); ?>)
                                </option>
                                <?php
endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; width: 100%; margin-bottom: 1.5rem;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600;">Date</label>
                            <input type="date" name="date" id="update_date" required style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600;">Quantité</label>
                            <input type="number" name="quantite" id="update_quantite" step="0.01" required
                                style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600;">Année</label>
                            <input type="number" name="annee_culturale" id="update_annee_culturale"
                                min="<?php echo date(" Y") - 1; ?>"
                                max="<?php echo date("Y") + 3; ?>"
                                step="1"
                                required
                                style="width:100%;">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; width: 100%;">
                        <button type="button" class="secondary" onclick="hideUpdateForm()">Annuler</button>
                        <input type="submit" value="Enregistrer les modifications">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showUpdateForm(intervention) {
            document.getElementById('updateFormBackdrop').style.display = 'flex';
            document.getElementById('update_id').value = intervention.id;
            document.getElementById('update_parcelle_id').value = intervention.parcelle_id;
            document.getElementById('update_engrais_id').value = intervention.engrais_id;
            document.getElementById('update_date').value = intervention.date;
            document.getElementById('update_quantite').value = intervention.quantite;
            document.getElementById('update_annee_culturale').value = intervention.annee_culturale;
        }

        function hideUpdateForm() {
            document.getElementById('updateFormBackdrop').style.display = 'none';
        }
    </script>
</body>

</html>
