<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// Validation stricte des entrées utilisateur
$annee_filter = isset($_GET['annee']) && ctype_digit($_GET['annee']) ? $_GET['annee'] : null;

// Requête SQL avec filtres et pagination
$query = "
    SELECT
        ip.annee_culturale,
        pp.nom AS produit_nom,
        pp.unite_emballage AS produit_unite,
        pp.type_produit AS produit_type,
        pp.amm AS produit_amm,
        SUM(dip.volume_total) AS volume_total,
        u.entity,
        u.telepac
    FROM
        interventions_phytosanitaires ip, users u
    JOIN
        details_interventions_phytosanitaires dip ON ip.id = dip.intervention_id
    JOIN
        produits_phytosanitaires pp ON dip.produit_id = pp.id
	
";

$params = [];
if ($annee_filter) {
    $query .= " WHERE ip.annee_culturale = :annee_culturale";
    $params[':annee_culturale'] = $annee_filter;
}

$query .= " GROUP BY ip.annee_culturale, pp.nom
            ORDER BY ip.annee_culturale, pp.nom";

try {
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception($db->lastErrorMsg());
    }

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    if (!$result) {
        throw new Exception($db->lastErrorMsg());
    }

    // Stocker les interventions groupées
    $interventions = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $key = $row['annee_culturale'];

        $entity = $row['entity'];
        $telepac = $row['telepac'];

        if (!isset($interventions[$key])) {
            $interventions[$key] = [
                'annee_culturale' => $row['annee_culturale'],
                'parcelle_id' => $row['parcelle_id'],
                'parcelle_nom' => $row['parcelle_nom'],
                'parcelle_ilot' => $row['parcelle_ilot'],
                'surface' => $row['surface'],
                'type_culture' => $row['type_culture'],
                'interventions' => []
            ];
        }

        $interventions[$key]['interventions'][] = [
            'date' => $row['date'],
            'stade' => $row['stade'],
            'produit_nom' => $row['produit_nom'],
            'produit_unite' => $row['produit_unite'],
            'produit_type' => $row['produit_type'],
            'produit_amm' => $row['produit_amm'],
            'volume_total' => $row['volume_total'],
            'volume_par_ha' => $row['volume_par_ha'],
            'cible' => $row['cible']
        ];
    }

}
catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stock Phyto - AgriRec</title>
    <link rel="stylesheet" href="includes/style.css" />
    <style>
        .report-header {
            background: var(--primary-dark);
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius) var(--radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-meta {
            font-size: 0.9rem;
            opacity: 0.9;
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
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
                <li><a href="interventions_phyto.php">Saisir une intervention</a></li>
            </ul>
        </nav>
        <form id="logout" action="logout.php" method="get">
            <button class="danger">Déconnexion</button>
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1>Stock phytosanitaire</h1>
            <p style="color: var(--text-muted);">Consultez le registre légal des stocks phytosanitaires.</p>
        </div>

        <section class="card" style="margin-bottom: 3rem;">
            <h3 >Filtrer le registre</h3>
            <div class="flex-inline">
                <div>
                    <form method="get" style="display: flex; gap: 1rem; align-items: flex-end;">
                        <div class="flex-inline">
                            <label style="display:block; font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Année
                                Culturale</label>
                            <select name="annee" style="width:100%;max-width:200px;">
                                <option value="">Toutes les années</option>
                                <?php
        $annees = $db->query("SELECT DISTINCT annee_culturale FROM interventions_phytosanitaires ORDER BY annee_culturale");
        while ($annee = $annees->fetchArray(SQLITE3_ASSOC)) {
            $selected = ($annee['annee_culturale'] == $annee_filter) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($annee['annee_culturale']) . "' $selected>" . htmlspecialchars($annee['annee_culturale']) . "</option>";
        }
        ?>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <button type="submit">Appliquer les filtres</button>
                        </div>
                        
                    </form>
                </div>
                <div style="flex:1; margin-top:20px; text-align:right"><a class="button" autofocus href="stock-phyto-brut.php?annee=<?php echo $annee_filter; ?>" target="_blank">Imprimer le rapport</a></div>
            </div>

        </section>

        <?php if (empty($interventions)): ?>
        <div class="card" style="text-align: center; color: var(--text-muted);">
            <p>Aucune intervention trouvée pour les critères sélectionnés.</p>
        </div>
        <?php
else: ?>
        <?php foreach ($interventions as $intervention): ?>
        <div style="margin-bottom: 3rem; box-shadow: var(--shadow-md); border-radius: var(--radius); overflow: hidden;">
            <div class="report-header">
                <div>
                    <h3 style="margin:0; color: white;">Année : <?= htmlspecialchars($intervention['annee_culturale'])?>
                    </h3>
                </div>
            </div>
            <div class="table-container" style="border-radius: 0; box-shadow: none; margin-bottom: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Type de produit</th>
                            <th>AMM</th>
                            <th>Volume total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $last_date = ''; ?>
                        <?php foreach ($intervention['interventions'] as $detail): ?>
                        <tr>
                            <td style="font-weight: 500;">
                                <?= htmlspecialchars_decode($detail['produit_nom'])?>
                            </td>
                            <td><?= htmlspecialchars($detail['produit_type'])?></td>
                            <td><?= htmlspecialchars($detail['produit_amm'])?></td>
                            <td>
                                <?= htmlspecialchars($detail['volume_total'])?>
                                <?= htmlspecialchars($detail['produit_unite'])?>
                            </td>
                        </tr>
                        <?php
        endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    endforeach; ?>
        <?php
endif; ?>

        <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; margin-bottom: 4rem;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i?><?= $annee_filter ? '&annee=' . urlencode($annee_filter) : ''?><?= $parcelle_filter ? '&parcelle=' . urlencode($parcelle_filter) : ''?>"
                style="padding: 0.5rem 1rem; border-radius: var(--radius); text-decoration: none; <?=($i == $page) ? 'background: var(--primary-color); color: white;' : 'background: white; color: var(--primary-color); border: 1px solid var(--primary-color);'?>">
                <?= $i?>
            </a>
            <?php
    endfor; ?>
        </div>
        <?php
endif; ?>
    </div>
</body>

</html>


</body>

</html>