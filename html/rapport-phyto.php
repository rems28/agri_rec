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
$parcelle_filter = isset($_GET['parcelle']) ? $_GET['parcelle'] : null;

// Pagination
$limit = 20; // Nombre d'interventions par page
$page = isset($_GET['page']) && ctype_digit($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Requête SQL avec filtres et pagination
$query = "
    SELECT
        ip.id AS intervention_id,
        STRFTIME('%d/%m/%Y %H:%M', ip.date_debut) AS date_debut,
        STRFTIME('%d/%m/%Y %H:%M', ip.date_fin) AS date_fin,
        ip.annee_culturale,
        ip.stade,
        ip.pourcentage AS parcelle_pourcentage,
        p.id AS parcelle_id,
        p.nom AS parcelle_nom,
        p.parcelle AS parcelle_parcelle,
        p.ilot AS parcelle_ilot,
        p.culture AS type_culture,
        p.surface,
        pp.nom AS produit_nom,
        pp.unite_emballage AS produit_unite,
        pp.type_produit AS produit_type,
        pp.amm AS produit_amm,
        dip.volume_total,
        dip.cible AS cible,
        u.entity,
        u.telepac,
        u.siret
    FROM
        interventions_phytosanitaires ip, users u
    JOIN
        parcelles p ON ip.parcelle_id = p.id
    JOIN
        details_interventions_phytosanitaires dip ON ip.id = dip.intervention_id
    JOIN
        produits_phytosanitaires pp ON dip.produit_id = pp.id
";

$params = [];
if ($annee_filter) {
    $query .= " AND ip.annee_culturale = :annee_culturale";
    $params[':annee_culturale'] = $annee_filter;
}
if ($parcelle_filter) {
    $query .= " AND p.id = :parcelle_id";
    $params[':parcelle_id'] = $parcelle_filter;
}

$query .= " ORDER BY ip.annee_culturale, p.nom, ip.date_debut, pp.nom
            LIMIT :limit OFFSET :offset";

$params[':limit'] = $limit;
$params[':offset'] = $offset;

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
        $key = $row['annee_culturale'] . '_' . $row['parcelle_id'];

        $entity  = $row['entity'];
        $telepac = $row['telepac'];
        $siret   = $row['siret'];

        if (!isset($interventions[$key])) {
            $interventions[$key] = [
                'annee_culturale' => $row['annee_culturale'],
                'parcelle_id' => $row['parcelle_id'],
                'parcelle_nom' => $row['parcelle_nom'],
                'parcelle_parcelle' => $row['parcelle_parcelle'],
                'parcelle_ilot' => $row['parcelle_ilot'],
                'surface' => $row['surface'],
                'type_culture' => $row['type_culture'],
                'interventions' => []
            ];
        }

        $interventions[$key]['interventions'][] = [
            'date_debut' => $row['date_debut'],
            'date_fin' => $row['date_fin'],
            'stade' => $row['stade'],
            'produit_nom' => $row['produit_nom'],
            'produit_unite' => $row['produit_unite'],
            'produit_type' => $row['produit_type'],
            'produit_amm' => $row['produit_amm'],
            'volume_total' => $row['volume_total'],
            'surface' => $row['surface'],
            'parcelle_pourcentage' => $row['parcelle_pourcentage'],
            'cible' => $row['cible']
        ];
    }

}
catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Compter le nombre total d'interventions pour la pagination
$count_query = "SELECT COUNT(DISTINCT ip.id) as total FROM interventions_phytosanitaires ip
                JOIN parcelles p ON ip.parcelle_id = p.id
                WHERE 1=1";
if ($annee_filter) {
    $count_query .= " AND ip.annee_culturale = :annee_culturale";
}
if ($parcelle_filter) {
    $count_query .= " AND p.id = :parcelle_id";
}

$count_stmt = $db->prepare($count_query);
if ($annee_filter) {
    $count_stmt->bindValue(':annee_culturale', $annee_filter, SQLITE3_TEXT);
}
if ($parcelle_filter) {
    $count_stmt->bindValue(':parcelle_id', $parcelle_filter, SQLITE3_INTEGER);
}

$count_result = $count_stmt->execute();
$total_interventions = $count_result->fetchArray(SQLITE3_ASSOC)['total'];
$total_pages = ceil($total_interventions / $limit);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rapport Phyto - AgriRec</title>
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
            <h1>Registre phytosanitaire</h1>
            <p style="color: var(--text-muted);">Consultez le registre légal des traitements phytosanitaires.</p>
        </div>

        <section class="card" style="margin-bottom: 3rem;">
            <h3 style="display: inline">Filtrer le registre</h3><a class="button" autofocus style="margin-left: 20px" href="rapport-phyto-brut.php?annee=<?php echo $annee_filter; ?>&parcelle=<?php echo $parcelle_filter; ?>" target="_blank">Imprimer le rapport</a>
            <div style="margin-top: 2rem;">
            <form method="get" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div style="flex:1;">
                    <label style="display:block; font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Année
                        Culturale</label>
                    <select name="annee" style="width:100%;">
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
                    <label
                        style="display:block; font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Parcelle</label>
                    <select name="parcelle" style="width:100%;">
                        <option value="">Toutes les parcelles</option>
                        <?php
$parcelles = $db->query("SELECT id, nom FROM parcelles ORDER BY nom");
while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)) {
    $selected = ($parcelle['id'] == $parcelle_filter) ? 'selected' : '';
    echo "<option value='" . htmlspecialchars($parcelle['id']) . "' $selected>" . htmlspecialchars_decode($parcelle['nom']) . "</option>";
}
?>
                    </select>
                </div>
                <button type="submit">Appliquer les filtres</button>
            </form>
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
                    <h3 style="margin:0; color: white;">Parcelle :
                        <?= htmlspecialchars_decode($intervention['parcelle_nom'])?>
                    </h3>
                    <div class="report-meta">
                        <span>Ilot: <b><?= htmlspecialchars($intervention['parcelle_ilot'])?></b></span>
                        <span>Parcelle: <b><?= htmlspecialchars($intervention['parcelle_parcelle'])?></b></span>
                        <span>Surface: <b><?= htmlspecialchars($intervention['surface'])?> ha</b></span>
                        <span>Culture: <b><?= htmlspecialchars_decode($intervention['type_culture'])?></b></span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="badge">CAMPAGNE <?= htmlspecialchars($intervention['annee_culturale'])?>
                    </span>
                </div>
            </div>
            <div class="table-container" style="border-radius: 0; box-shadow: none; margin-bottom: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Date & heure de début</th>
                            <th>Date & heure de fin</th>
                            <th>Stade</th>
                            <th>Produit</th>
                            <th>Type de produit</th>
                            <th>AMM</th>
                            <th>Volume total</th>
                            <th>% réalisé</th>
                            <th>Dose/ha</th>
                            <th>Cible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $last_date_debut = ''; ?>
                        <?php $last_date_fin = ''; ?>
                        <?php foreach ($intervention['interventions'] as $detail): ?>
                        <tr>
                            <td>
                                <?php if ($detail['date_debut'] !== $last_date_debut) { ?>
                                <span style="font-weight: 600;">
                                    <?= htmlspecialchars($detail['date_debut'])?>
                                </span>
                                <?php $last_date_debut = $detail['date_debut']; $print = '1' ?>
                                <?php
                                    } ?>
                            </td>
                            <td>
                                <?php if ($print == '1') { ?>
                                <span style="font-weight: 600;">
                                    <?= htmlspecialchars($detail['date_fin'])?>
                                </span>
                                <?php
                                    } ?>
                            </td>
                            <td>
                                <?php if ($print == '1') { ?>
                                <span style="font-weight: 600;">
                                    <?= htmlspecialchars($detail['stade'])?>
                                </span>
                                <?php $print = '0';
                                    } ?>
                            </td>
                            <td style="font-weight: 500;">
                                <?= htmlspecialchars_decode($detail['produit_nom'])?>
                            </td>
                            <td><?= htmlspecialchars($detail['produit_type'])?></td>
                            <td><?= htmlspecialchars($detail['produit_amm'])?></td>
                            <td>
                                <?= htmlspecialchars($detail['volume_total'])?>
                                <?= htmlspecialchars($detail['produit_unite'])?>
                            </td>
                            <td>
                                <?= htmlspecialchars($detail['parcelle_pourcentage'])?>
                            </td>
                            <td><span style="color: var(--primary-color); font-weight: 600;">
                                    <?= round($detail['volume_total'] / ($intervention['surface'] * ($detail['parcelle_pourcentage'] / 100)), 3);?>
                                    <?= htmlspecialchars($detail['produit_unite'])?>/ha
                                </span></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= htmlspecialchars_decode($detail['cible'])?>
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