<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

function clean_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $parcelle_id = intval($_POST['parcelle_id']);
                $annee_culturale = intval($_POST['annee_culturale']);
                $pourcentage = intval($_POST['pourcentage']);
                $stade = clean_input($_POST['stade']);
                $date_debut = clean_input($_POST['date_debut']);
                $heure_debut = clean_input($_POST['heure_debut']);
                $date_fin = clean_input($_POST['date_fin']);
                $heure_fin = clean_input($_POST['heure_fin']);
                
                
                $datetime_debut = $date_debut . ' ' . $heure_debut;
                $datetime_fin = $date_fin . ' ' . $heure_fin;

                $db->exec('BEGIN TRANSACTION');

                $stmt = $db->prepare('INSERT INTO interventions_phytosanitaires (parcelle_id, annee_culturale, pourcentage, stade, date_debut, date_fin) VALUES (:parcelle_id, :annee_culturale, :pourcentage, :stade, :date_debut, :date_fin)');
                $stmt->bindValue(':parcelle_id', $parcelle_id, SQLITE3_INTEGER);
                $stmt->bindValue(':annee_culturale', $annee_culturale, SQLITE3_INTEGER);
                $stmt->bindValue(':pourcentage', $pourcentage, SQLITE3_FLOAT);
                $stmt->bindValue(':stade', $stade, SQLITE3_TEXT);
                $stmt->bindValue(':date_debut', $datetime_debut, SQLITE3_TEXT);
                $stmt->bindValue(':date_fin', $datetime_fin, SQLITE3_TEXT);
                $stmt->execute();

                $intervention_id = $db->lastInsertRowID();

                foreach ($_POST['produit_id'] as $key => $produit_id) {
                    $volume_total = floatval($_POST['volume_total'][$key]);
                    $cible = clean_input($_POST['cible'][$key]);

                    $stmt = $db->prepare('INSERT INTO details_interventions_phytosanitaires (intervention_id, produit_id, volume_total, cible) VALUES (:intervention_id, :produit_id, :volume_total, :cible)');
                    $stmt->bindValue(':intervention_id', $intervention_id, SQLITE3_INTEGER);
                    $stmt->bindValue(':produit_id', $produit_id, SQLITE3_INTEGER);
                    $stmt->bindValue(':volume_total', $volume_total, SQLITE3_FLOAT);
                    $stmt->bindValue(':cible', $cible, SQLITE3_TEXT);
                    $stmt->execute();
                }

                $db->exec('COMMIT');
                break;

            case 'delete':
                $id = intval($_POST['id']);

                $db->exec('BEGIN TRANSACTION');

                $stmt = $db->prepare('DELETE FROM details_interventions_phytosanitaires WHERE intervention_id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();

                $stmt = $db->prepare('DELETE FROM interventions_phytosanitaires WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();

                $db->exec('COMMIT');
                break;
        }
    }
}

$parcelles = $db->query('SELECT * FROM parcelles ORDER BY nom');
$produits = $db->query('SELECT * FROM produits_phytosanitaires ORDER BY nom');
$interventions = $db->query('SELECT ip.*, p.nom as parcelle_nom, p.surface, p.ilot AS parcelle_ilot, p.parcelle AS parcelle_parcelle
                             FROM interventions_phytosanitaires ip
                             JOIN parcelles p ON ip.parcelle_id = p.id
                             ORDER BY ip.date_debut');

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interventions Phyto - AgriRec</title>
    <link rel="stylesheet" href="includes/style.css">
    <script>
        function addProduit() {
            var container = document.getElementById('produits-container');
            var newProduit = document.createElement('div');
            newProduit.className = 'card';
            newProduit.style.background = '#f8f9fa';
            newProduit.style.marginTop = '1rem';
            newProduit.style.padding = '1rem';
            newProduit.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">Produit</label>
                        <select name="produit_id[]" required style="width:100%;">
                            <?php
$produits->reset();
while ($produit = $produits->fetchArray(SQLITE3_ASSOC)):
?>
                                <option value="<?php echo $produit['id']; ?>"><?php echo htmlspecialchars_decode($produit['nom']); ?>
                                <?php if ($produit['unite'] !== '') { ?> (<?php echo htmlspecialchars_decode($produit['unite_emballage']); ?>) <?php } ?>
                                </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">Volume total</label>
                        <input type="number" name="volume_total[]" step="0.01" placeholder="ex: 2.5" required style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">Cible</label>
                        <input type="text" name="cible[]" placeholder="ex: Adventices" required style="width:100%;">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="button" class="danger" onclick="this.parentElement.parentElement.parentElement.remove()" style="width:100%;">Retirer</button>
                    </div>
                </div>
            `;
            container.appendChild(newProduit);
        }
    </script>
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
                <li><a href="phytosanitaires.php">Catalogue Phyto</a></li>
                <li><a href="rapport-phyto.php">Rapports</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Interventions Phytosanitaires</h1>
            <p style="color: var(--text-muted);">Enregistrez les traitements de protection des cultures par parcelle.
            </p>
        </div>

        <section class="card">
            <h3>Saisir une nouvelle intervention</h3>
            <form style="display:block" method="post">
                <input type="hidden" name="action" value="create">

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <label
                            style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Parcelle</label>
                        <select name="parcelle_id" required style="width:100%;" autofocus>
                            <option value="" disabled selected>Choisir une parcelle</option>
                            <?php
$parcelles->reset();
while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $parcelle['id']; ?>">
                                <?php echo htmlspecialchars_decode($parcelle['nom']); ?> (<?php echo $parcelle['surface']; ?> ha)
                            </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Année culturale</label>
                        <input type="number"
                            name="annee_culturale"
                            min="<?php echo date(" Y") - 1; ?>"
                            max="<?php echo date("Y") + 3; ?>"
                            step="1"
                            value="<?php echo date("Y"); ?>"
                            required
                            style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Pourcentage réalisé de la parcelle (%)</label>
                        <input type="text" name="pourcentage" value="100" placeholder="" required style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Stade</label>
                        <input type="text" name="stade" value="" placeholder="Ex: 1er noeud" required style="width:100%;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Date du début de l'intervention</label>
                        <input type="date" name="date_debut" value="<?php echo date('d/m/Y'); ?>" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Heure de début</label>
                        <input type="time" name="heure_debut" value="<?php echo date('H:i'); ?>" required style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Date de fin d'intervention</label>
                        <input type="date" name="date_fin" value="<?php echo date('d/m/Y'); ?>" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Heure de fin</label>
                        <input type="time" name="heure_fin" value="<?php echo date('H:i'); ?>" required style="width:100%;">
                    </div>

                    
                </div>

                <div id="produits-container">
                    <h4 style="margin-bottom: 1rem;">Produits utilisés dans cette intervention</h4>
                    <div class="card" style="background: #f8f9fa; padding: 1rem;">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                            <div>
                                <label style="font-size: 0.8rem; font-weight: 600;">Produit</label>
                                <select name="produit_id[]" required style="width:100%;">
                                    <option value="" disabled selected>Choisir un phyto</option>
                                    <?php
$produits->reset();
while ($produit = $produits->fetchArray(SQLITE3_ASSOC)): ?>
                                    <option value="<?php echo $produit['id']; ?>">
                                        <?php echo htmlspecialchars_decode($produit['nom']); ?>
                                        <?php if ($produit['unite'] !== '') { ?> (<?php echo htmlspecialchars_decode($produit['unite_emballage']); ?>) <?php } ?>
                                    </option>
                                    <?php
endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; font-weight: 600;">Volume total</label>
                                <input type="number" name="volume_total[]" step="0.01" placeholder="ex: 2.5" required
                                    style="width:100%;">
                            </div>
                            <div colspan="2">
                                <label style="font-size: 0.8rem; font-weight: 600;">Cible</label>
                                <input type="text" name="cible[]" placeholder="ex: Adventices" required
                                    style="width:100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" class="secondary" onclick="addProduit()">+ Ajouter un autre produit à cette intervention</button>
                </div>
                <div style="margin-top: 2rem;">
                    <input type="submit" value="Enregistrer l'intervention">
                </div>
            </form>
        </section>

        <section>
            <h3>Historique des interventions phyto</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Campagne</th>
                            <th>Parcelle</th>
                            <th>Date / Heure</th>
                            <th>Stade</th>
                            <th>% réalisé</th>
                            <th>Détail intervention</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($intervention = $interventions->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><span class="badge" style="background: var(--primary-color); color: white;">
                                    <?php echo htmlspecialchars($intervention['annee_culturale']); ?>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars_decode($intervention['parcelle_nom']); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Ilot :
                                    <?php echo htmlspecialchars($intervention['parcelle_ilot']); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Parcelle :
                                    <?php echo htmlspecialchars($intervention['parcelle_parcelle']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Début :
                                    <?php echo date('d/m/Y H:i', strtotime($intervention['date_debut'])); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Fin :
                                    <?php echo date('d/m/Y H:i', strtotime($intervention['date_fin'])); ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars_decode($intervention['stade']); ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars_decode($intervention['pourcentage']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <?php
    $details = $db->query('SELECT dip.*, pp.nom as produit_nom, pp.unite_emballage as produit_unite
                                                           FROM details_interventions_phytosanitaires dip
                                                           JOIN produits_phytosanitaires pp ON dip.produit_id = pp.id
                                                           WHERE dip.intervention_id = ' . $intervention['id']);
    while ($detail = $details->fetchArray(SQLITE3_ASSOC)):
?>
                                    <div
                                        style="background: #f1f3f5; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem;">
                                        <b>
                                            <?php echo htmlspecialchars_decode($detail['produit_nom']); ?>
                                        </b>:
                                        <?php echo htmlspecialchars($detail['volume_total']); ?>
                                        <?php echo htmlspecialchars($detail['produit_unite']); ?>
                                        (<span style="color: var(--primary-color); font-weight: 600;">
                                            <?php echo round($detail['volume_total'] / ($intervention['surface'] * ($intervention['pourcentage'] / 100)), 3); ?>
                                            <?php echo htmlspecialchars($detail['produit_unite']); ?>/ha
                                        </span>)
                                        <br /><small style="color: var(--text-muted);">Cible:
                                            <?php echo htmlspecialchars_decode($detail['cible']); ?>
                                        </small>
                                    </div>
                                    <?php
    endwhile; ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $intervention['id']; ?>">
                                    <button type="submit" class="danger"
                                        onclick="return confirm('Supprimer cette intervention ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php
endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>

</body>

</html>
