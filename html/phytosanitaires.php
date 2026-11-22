<?php
session_start();
require_once "includes/config.php";
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
                $type_produit = clean_input($_POST["type_produit"]);
                $amm = clean_input($_POST["amm"]);

                $stmt = $db->prepare(
                    "INSERT INTO produits_phytosanitaires (nom, unite_emballage, type_produit, amm) VALUES (:nom, :unite_emballage, :type_produit, :amm)",
                );
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":unite_emballage", $unite_emballage, SQLITE3_TEXT);
                $stmt->bindValue(":type_produit", $type_produit, SQLITE3_TEXT);
                $stmt->bindValue(":amm", $amm, SQLITE3_TEXT);
                $stmt->execute();
                break;

            case "update":
                $id = intval($_POST["id"]);
                $nom = clean_input($_POST["nom"]);
                $unite_emballage = clean_input($_POST["unite_emballage"]);
                $type_produit = clean_input($_POST["type_produit"]);
                $amm = clean_input($_POST["amm"]);

                $stmt = $db->prepare(
                    "UPDATE produits_phytosanitaires SET nom = :nom, unite_emballage = :unite_emballage, type_produit = :type_produit, amm = :amm WHERE id = :id",
                );
                $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
                $stmt->bindValue(":nom", $nom, SQLITE3_TEXT);
                $stmt->bindValue(":unite_emballage", $unite_emballage, SQLITE3_TEXT);
                $stmt->bindValue(":type_produit", $type_produit, SQLITE3_TEXT);
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
    <title>Catalogue Phytosanitaires - AgriRec</title>
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
                <li><a href="interventions_phyto.php">Nouvelle Intervention</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div id="popupProduits" class="popup-produits">
        <div class="popup-header">
            <span>Mise à jour des produits</span>
            <button type="button" onclick="fermerPopupProduits()">×</button>
        </div>

        <iframe id="iframeProduits" title="Mise à jour des produits"></iframe>
    </div>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Catalogue Phytosanitaires</h1>
            <p style="color: var(--text-muted);">Gérez votre catalogue de produits de protection des cultures.</p>
        </div>

        <section class="card">
            <div class="flex-inline">
                <h3>Ajouter un produit </h3>
                <a style="margin-left: auto;" href="#" onclick='ouvrirPopupProduits(); return false;' class="button">Mettre à jour la base produits</a>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom
                            du produit</label>
                        <input type="text" id="nom_produit" name="nom" placeholder="ex: RoundUp" required style="width:100%;"
                            onkeyup="if(this.value.length>=2)this.setAttribute('list','produitspublics');"
                            onkeydown="if(this.value.length<2||event.keyCode==8)this.removeAttribute('list');"
                            autofocus onblur="fillFromNom()" />
                        <datalist id="produitspublics">
                            <?php
                            var_dump(DB_PUBLIC_PPP);
                            $pdo_public_ppp = new PDO('sqlite:' . DB_PUBLIC_PPP);
                            $pdo_public_ppp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            var_dump($pdo_public_ppp);
                            $produitspp = $pdo_public_ppp->query("SELECT nom_produit FROM produits ORDER BY nom_produit");
                            while ($produitpp = $produitspp->fetchArray(SQLITE3_ASSOC)) {
                                echo "<option data-typeproduit='" . htmlspecialchars($produitpp['type_produit']) . "' data-amm='" . htmlspecialchars($produitpp['numero_amm']) . "' value='" . htmlspecialchars($produitpp['nom_produit']) . "'>" . htmlspecialchars_decode($produitpp['nom_produit']) . "</option>";
                            }
                            ?>
                        </datalist>
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Type produit</label>
                        <input type="text" id="type_produit" name="type_produit" placeholder="ex: ADJUVANT" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Unité
                            d'emballage</label>
                        <input type="text" name="unite_emballage" placeholder="ex: Litre" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">N°
                            AMM</label>
                        <input type="text" id="amm" name="amm" placeholder="ex: 2040523" required style="width:100%;">
                    </div>
                </div>
                <div style="margin-top: 1.5rem; text-align: right; width: 100%;">
                    <input type="submit" value="Enregistrer le produit">
                </div>
            </form>
        </section>

        <section>
            <h3>Catalogue des produits</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Unité</th>
                            <th>Type produit</th>
                            <th>AMM</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($produit = $produits->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars_decode($produit["nom"]); ?>
                            </td>
                            <td style="color: var(--text-muted);">
                                <?php echo htmlspecialchars($produit["unite_emballage"]); ?>
                            </td>
                            <td style="color: var(--text-muted);">
                                <?php echo htmlspecialchars($produit["type_produit"]); ?>
                            </td>
                            <td><?php echo htmlspecialchars($produit["amm"]); ?></td>
                            <td style="text-align: center;">
                                <div>
                                    <button class="secondary"
                                        onclick="showUpdateForm(<?php echo htmlspecialchars(json_encode($produit)); ?>)">Modifier</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $produit["id"]; ?>">
                                        <button type="submit" class="danger"
                                            onclick="return confirm('Supprimer ce produit ?');">Supprimer</button>
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
            <div class="card" style="width: 100%; max-width: 600px; margin: 2rem;">
                <h3>Modifier le produit</h3>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="update_id">

                    <div style="width: 100%; margin-bottom: 1rem;">
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Nom</label>
                        <input type="text" name="nom" id="update_nom" required style="width:100%;">
                    </div>

                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; margin-bottom: 1.5rem;">
                        <div>
                            <label
                                style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Unité</label>
                            <input type="text" name="unite_emballage" id="update_unite_emballage" required
                                style="width:100%;">
                        </div>
                        <div>
                            <label
                                style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Type produit</label>
                            <input type="text" name="type_produit" id="update_type_produit" required
                                style="width:100%;">
                        </div>
                        <div>
                            <label
                                style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">AMM</label>
                            <input type="text" name="amm" id="update_amm" required style="width:100%;">
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
      function showUpdateForm(produit) {
        document.getElementById('updateFormBackdrop').style.display = 'flex';
        document.getElementById('update_id').value = produit.id;
        document.getElementById('update_nom').value = htmlSpecialCharsDecode(produit.nom);
        document.getElementById('update_unite_emballage').value = produit.unite_emballage;
        document.getElementById('update_type_produit').value = produit.type_produit;
        document.getElementById('update_amm').value = produit.amm;
      }

      function hideUpdateForm() {
        document.getElementById('updateFormBackdrop').style.display = 'none';
      }

      function ouvrirPopupProduits() {
        document.getElementById("popupProduits").style.display = "block";

        document.getElementById("iframeProduits").src = "produits-publics.php";
      }

      function fermerPopupProduits() {
        document.getElementById("popupProduits").style.display = "none";
      }

      function fillFromNom() {
        const nom_produit = document.getElementById('nom_produit').value;
        const datalist = document.getElementById('produitspublics');

        const produit = Array.from(datalist.options).find(
          option => option.value === nom_produit
        );
        if (!produit) {
          return;
        }

        document.getElementById('type_produit').value = produit.dataset.typeproduit || '';
        document.getElementById('amm').value = produit.dataset.amm || '';
      }
    </script>
</body>

</html>
