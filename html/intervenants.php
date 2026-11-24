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
                $siret = clean_input($_POST["siret"]);

                $stmt = $db->prepare(
                    "INSERT INTO intervenants (nom, siret) VALUES (:nom, siret)",
                );
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":siret", $siret, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $nom = clean_input($_POST["nom"]);
                $siret = clean_input($_POST["siret"]);

                $stmt = $db->prepare(
                    "UPDATE intervenants SET nom = :nom, siret = :siret WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":siret", $siret, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "delete":
                $id = intval($_POST["id"]);

                $stmt = $db->prepare("DELETE FROM intervenants WHERE id = :id");
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->execute();
                break;
        }
    }
    header("Location: intervenants.php");
}

// Récupération des intervenants
$intervenants = $db->query("SELECT * FROM intervenants");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des intervenants externes</title>
    <link rel="stylesheet" href="includes/style.css" />
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
                <li><a href="parcelles.php">Parcelles</a></li>
                <li><a href="engrais.php">Engrais</a></li>
                <li><a href="phytosanitaires.php">Phyto</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Gestion des intervenants externes</h1>
            <p style="color: var(--text-muted);">Répertoriez vos intervenants externes.</p>
        </div>

        <section class="card">
            <h3>Ajouter un intervenant</h3>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom de l'intervenant</label>
                        <input type="text" name="nom" placeholder="ex: Durand SARL" required style="width:100%;" autofocus>
                    </div>
                </div>
                <div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">SIRET</label>
                        <input type="text" name="siret" placeholder="xxx xxx xxx xxx xxx" required style="width:100%;">
                    </div>
                </div>
                <div style="margin-top: 1.5rem; text-align: right; width: 100%;">
                    <input type="submit" value="Ajouter l'intervenant">
                </div>
            </form>
        </section>

        <section>
            <h3>Liste des intervenants externes</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>N° Siret</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($intervenant = $intervenants->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars_decode($intervenant["nom"]); ?></td>
                            <td><?php echo htmlspecialchars_decode($intervenant["siret"]); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="secondary"
                                        onclick="showUpdateForm(<?php echo htmlspecialchars(json_encode($intervenant)); ?>)">Modifier</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $intervenant["id"]; ?>">
                                        <button type="submit" class="danger"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet intervenant ?');">Supprimer</button>
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
            <div class="card" style="width: 100%; max-width: 600px; margin: 2rem;">
                <h3>Modifier l'intervenant</h3>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="update_id">

                    <div style="width: 100%; margin-bottom: 1rem;">
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom</label>
                        <input type="text" name="nom" id="update_nom" required style="width:100%;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; margin-bottom: 1rem;">
                        <div>
                            <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">SIRET</label>
                            <input type="text" name="siret" id="update_siret" required style="width:100%;">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; width: 100%;">
                        <button type="button" class="secondary" onclick="hideUpdateForm()">Annuler</button>
                        <input type="submit" value="Mettre à jour">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/functions.js"></script>
    <script>
        function showUpdateForm(intervenants) {
            document.getElementById('updateFormBackdrop').style.display = 'flex';
            document.getElementById('update_id').value = intervenants.id;
            document.getElementById('update_nom').value = htmlSpecialCharsDecode(intervenants.nom);
            document.getElementById('update_siret').value = htmlSpecialCharsDecode(intervenants.siret);
        }

        function hideUpdateForm() {
            document.getElementById('updateFormBackdrop').style.display = 'none';
        }
    </script>
</body>

</html>
