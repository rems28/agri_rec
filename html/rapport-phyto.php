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
        STRFTIME('%d/%m/%Y %H:%M', ip.date) AS date,
        ip.annee_culturale,
        p.id AS parcelle_id,
        p.nom AS parcelle_nom,
        p.ilot AS parcelle_ilot,
        p.culture AS type_culture,
        p.surface,
        pp.nom AS produit_nom,
        pp.unite_emballage AS produit_unite,
        pp.amm AS produit_amm,
        dip.volume_total,
        round((dip.volume_total / p.surface), 2) AS volume_par_ha,
        dip.cible AS cible,
        u.entity,
        u.telepac
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

$query .= " ORDER BY ip.annee_culturale, p.nom, ip.date, pp.nom
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
            'produit_nom' => $row['produit_nom'],
            'produit_unite' => $row['produit_unite'],
            'produit_amm' => $row['produit_amm'],
            'volume_total' => $row['volume_total'],
            'volume_par_ha' => $row['volume_par_ha'],
            'cible' => $row['cible']
        ];
    }

} catch (Exception $e) {
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
    <title>Rapport des interventions phytosanitaires</title>
    <link rel="stylesheet" href="includes/style.css" />
</head>
<body>
    <h3>Navigation dans les pages de gestion</h3>
    <ul>
        <li><a href="interventions_phyto.php">Création des interventions phytosanitaires</a></li>
        <li><a href="index.php">Retour à l'accueil</a></li>
    </ul>
    <h1>Rapport des interventions phytosanitaires</h1>
    <h2><?php echo $entity; ?> - Telepac: <?php echo $telepac; ?></h2>
    <br>
    <!-- Formulaire de tri -->
    <form method="get">
        <select name="annee">
            <option value="">Toutes les années</option>
            <?php
            $annees = $db->query("SELECT DISTINCT annee_culturale FROM interventions_phytosanitaires ORDER BY annee_culturale");
            while ($annee = $annees->fetchArray(SQLITE3_ASSOC)) {
                $selected = ($annee['annee_culturale'] == $annee_filter) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($annee['annee_culturale']) . "' $selected>" . htmlspecialchars($annee['annee_culturale']) . "</option>";
            }
            ?>
        </select>
        <select name="parcelle">
            <option value="">Toutes les parcelles</option>
            <?php
            $parcelles = $db->query("SELECT id, nom FROM parcelles ORDER BY nom");
            while ($parcelle = $parcelles->fetchArray(SQLITE3_ASSOC)) {
                $selected = ($parcelle['id'] == $parcelle_filter) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($parcelle['id']) . "' $selected>" . htmlspecialchars_decode($parcelle['nom']) . "</option>";
            }
            ?>
        </select>
        <input type="submit" value="Filtrer">
    </form>

    <!-- Affichage du tableau des interventions -->
    <?php if (empty($interventions)) : ?>
        <p>Aucune intervention trouvée pour les critères sélectionnés.</p>
    <?php else : ?>
        <?php foreach ($interventions as $intervention) : ?>
            <table>
                <thead>
                    <tr>
                        <th colspan="7">
                            Année culturale: <?= htmlspecialchars($intervention['annee_culturale']) ?> | 
                            Parcelle : <?= htmlspecialchars_decode($intervention['parcelle_nom']) ?> <br/>
                            Ilot : <?= htmlspecialchars($intervention['parcelle_ilot']) ?> <br/>
                            Surface : <?= htmlspecialchars($intervention['surface']) ?> ha |
                            Culture : <?= htmlspecialchars_decode($intervention['type_culture']) ?>
                        </th>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>AMM</th>
                        <th>Volume total</th>
                        <th>Volume par ha</th>
                        <th>Cible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intervention['interventions'] as $detail) : ?>
                        <tr>
                            <td><?php if ($detail['date'] !== $last_date) { echo htmlspecialchars($detail['date']); $last_date = $detail['date'];}  ?></td>
                            <td><?= htmlspecialchars_decode($detail['produit_nom']) ?></td>
                            <td><?= htmlspecialchars($detail['produit_amm']) ?></td>
                            <td><?= htmlspecialchars($detail['volume_total']) ?> <?= htmlspecialchars($detail['produit_unite']) ?></td>
                            <td><?= htmlspecialchars($detail['volume_par_ha']) ?> <?= htmlspecialchars($detail['produit_unite']) ?></td>
                            <td><?= htmlspecialchars_decode($detail['cible']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="intervention-separator"></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
            <?php if ($i == $page) : ?>
                <span class="current-page"><?= $i ?></span>
            <?php else : ?>
                <a href="?page=<?= $i ?><?= $annee_filter ? '&annee=' . urlencode($annee_filter) : '' ?><?= $parcelle_filter ? '&parcelle=' . urlencode($parcelle_filter) : '' ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

</body>
</html>
