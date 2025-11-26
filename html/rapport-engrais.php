<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// Paramètres de filtre
$annee_filter = isset($_GET['annee']) ? $_GET['annee'] : null;
$parcelle_filter = isset($_GET['parcelle']) ? $_GET['parcelle'] : null;

// Requête SQL avec filtres
$query = "
    SELECT 
        ie.id AS intervention_id,
        STRFTIME('%d/%m/%Y', ie.date) AS date,
        ie.annee_culturale,
        p.id AS parcelle_id,
        p.nom AS parcelle_nom,
        p.ilot AS parcelle_ilot,
        p.culture AS type_culture,
        p.surface AS parcelle_surface,
        e.nom AS engrais_nom,
        e.unite AS engrais_unite,
        ie.quantite,
        round((ie.quantite / p.surface), 2) AS quantite_par_ha,
        round((ie.quantite * e.NO3 / p.surface), 2)  AS total_NO3,
        round((ie.quantite * e.P2O5 / p.surface), 2) AS total_P2O5,
        round((ie.quantite * e.K2O / p.surface), 2) AS total_K2O,
        round((ie.quantite * e.SO3 / p.surface), 2) AS total_SO3,
        round((ie.quantite * e.MgO / p.surface), 2) AS total_MgO,
        round((ie.quantite * e.CaO / p.surface), 2) AS total_CaO,
        u.entity,
        u.telepac
    FROM 
        interventions_engrais ie, users u
    JOIN 
        parcelles p ON ie.parcelle_id = p.id
    JOIN 
        engrais e ON ie.engrais_id = e.id
    " . ($annee_filter ? "AND ie.annee_culturale = :annee_culturale " : "") . "
    " . ($parcelle_filter ? "AND p.id = :parcelle_id " : "") . "
    ORDER BY 
        p.nom, ie.annee_culturale, ie.date
";

$stmt = $db->prepare($query);
if ($annee_filter) {
    $stmt->bindValue(':annee_culturale', $annee_filter, SQLITE3_TEXT);
}
if ($parcelle_filter) {
    $stmt->bindValue(':parcelle_id', $parcelle_filter, SQLITE3_INTEGER);
}
$result = $stmt->execute();

// Stocker les interventions
$totals = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $interventions[] = $row;
    
    $key = $row['parcelle_nom'];
    if (!isset($totals[$key])) {
        $totals[$key] = [
            'parcelle_nom' => $row['parcelle_nom'],
            'surface' => $row['surface'],
            'NO3' => 0, 'P2O5' => 0, 'K2O' => 0,
            'SO3' => 0, 'MgO' => 0, 'CaO' => 0
        ];
    }
    foreach (['NO3', 'P2O5', 'K2O', 'SO3', 'MgO', 'CaO'] as $element) {
        $totals[$key][$element] += $row['total_' . $element];
    }
}
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
        <h3> Navigation dans les pages de gestion </h3>
        <ul>
            <li><a href="interventions_engrais.php">Création des interventions engrais</a></li>
            <li><a href="index.php">Retour à l'accueil</a></li>
        </ul>
        <h1>Rapport des interventions engrais</h1>
        <h2><?php echo $interventions[0]['entity']; ?> - Telepac: <?php echo $interventions[0]['telepac']; ?></h2>
        <!-- Formulaire de tri -->
        <form method="get">
            <select name="annee">
                <option value="">Toutes les années</option>
                <?php
                $annees = $db->query("SELECT DISTINCT annee_culturale FROM interventions_engrais ORDER BY annee_culturale");
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

        <?php if (empty($interventions)) { ?>
        <p>Aucune intervention trouvée pour les critères sélectionnés.</p>
        <?php } else {
            $current_parcelle = '';
            $current_annee = '';
            $totals = ['NO3' => 0, 'P2O5' => 0, 'K2O' => 0, 'SO3' => 0, 'MgO' => 0, 'CaO' => 0];

            function afficherTotal($annee, $totals) {
        ?>
                <tr class="total-row">
                    <td colspan="5">Total pour l'année <?php echo htmlspecialchars($annee); ?></td>
                    <td><?php echo round($totals['NO3'], 2); ?></td>
                    <td><?php echo round($totals['P2O5'], 2); ?></td>
                    <td><?php echo round($totals['K2O'], 2); ?></td>
                    <td><?php echo round($totals['SO3'], 2); ?></td>
                    <td><?php echo round($totals['MgO'], 2); ?></td>
                    <td><?php echo round($totals['CaO'], 2); ?></td>
                </tr>
                </table>
            <?php
            }

            foreach ($interventions as $index => $intervention) {
                if ($intervention['parcelle_nom'] !== $current_parcelle) {
                    if ($current_parcelle !== '') {
                        afficherTotal($current_annee, $totals);
                    };
                    $current_parcelle = $intervention['parcelle_nom'];
                    $current_annee = '';
                };

                if ($intervention['annee_culturale'] !== $current_annee) {
                    if ($current_annee !== '') {
                        afficherTotal($current_annee, $totals);
                    };
                    $totals = ['NO3' => 0, 'P2O5' => 0, 'K2O' => 0, 'SO3' => 0, 'MgO' => 0, 'CaO' => 0];
                    ?>
                    <table>
                        <tr>
                            <th>
                            Année culturale : <?php echo htmlspecialchars($intervention['annee_culturale']); ?> |
                            Parcelle : <?php echo htmlspecialchars_decode($intervention['parcelle_nom']); ?> <br/>
                            Ilot : <?php echo htmlspecialchars($intervention['parcelle_ilot']); ?> <br/>
                            Surface : <?php echo htmlspecialchars($intervention['parcelle_surface']); ?> |
                            Culture : <?php echo htmlspecialchars_decode($intervention['type_culture']); ?>
                            </th>
                            <th colspan="10" class="emptyth"></th>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <th>Engrais</th>
                            <th>Unité</th>
                            <th>Quantité totale</th>
                            <th>Quantité par ha</th>
                            <th>NO3 (U/ha)</th>
                            <th>P2O5 (U/ha)</th>
                            <th>K2O (U/ha)</th>
                            <th>SO3 (U/ha)</th>
                            <th>MgO (U/ha)</th>
                            <th>CaO (U/ha)</th>
                        </tr>
                    <?php
                    $current_annee = $intervention['annee_culturale'];
                } ;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($intervention['date']); ?></td>
                    <td><?php echo htmlspecialchars_decode($intervention['engrais_nom']); ?></td>
                    <td><?php echo htmlspecialchars($intervention['engrais_unite']); ?></td>
                    <td><?php echo htmlspecialchars($intervention['quantite']); ?></td>
                    <td><?php echo htmlspecialchars($intervention['quantite_par_ha']); ?></td>
                    <td><?php echo round($intervention['total_NO3'], 2); ?></td>
                    <td><?php echo round($intervention['total_P2O5'], 2); ?></td>
                    <td><?php echo round($intervention['total_K2O'], 2); ?></td>
                    <td><?php echo round($intervention['total_SO3'], 2); ?></td>
                    <td><?php echo round($intervention['total_MgO'], 2); ?></td>
                    <td><?php echo round($intervention['total_CaO'], 2); ?></td>
                </tr>
                <?php
                $totals['NO3'] += $intervention['total_NO3'];
                $totals['P2O5'] += $intervention['total_P2O5'];
                $totals['K2O'] += $intervention['total_K2O'];
                $totals['SO3'] += $intervention['total_SO3'];
                $totals['MgO'] += $intervention['total_MgO'];
                $totals['CaO'] += $intervention['total_CaO'];

                // Si c'est la dernière intervention, afficher le total
                if ($index === count($interventions) - 1) {
                    afficherTotal($current_annee, $totals);
                };
            };
        }; ?>

    </body>
</html>
