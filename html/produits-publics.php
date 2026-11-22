<?php

// Appeler les variables
require_once 'includes/db.php';

// Augmenter les limites pour traiter un gros fichier CSV sans timeout
set_time_limit(600);
ini_set('memory_limit', '512M');

echo "--- ÉTAPE 1 : Récupération de l'URL du fichier ZIP ---\n";
$dataset_slug = "donnees-ouvertes-du-catalogue-e-phy-des-produits-phytopharmaceutiques-matieres-fertilisantes-et-supports-de-culture-adjuvants-produits-mixtes-et-melanges";
$api_url = "https://www.data.gouv.fr/api/1/datasets/" . $dataset_slug . "/";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Script-EPhy-Download');
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    die("Erreur : Impossible de joindre l'API data.gouv.fr (Code HTTP: $http_code)\n");
}

$data = json_decode($response, true);
$target_url = null;
foreach ($data['resources'] as $resource) {
    $file_url = $resource['url'] ?? '';
    if (preg_match('/decisionamm-intrant-format-csv-.*-utf8\.zip/i', $file_url)) {
        $target_url = $file_url;
        break;
    }
}

if (!$target_url) {
    die("Erreur : Impossible de trouver le fichier ZIP UTF-8 correspondant.\n");
}

$zip_filename = basename(parse_url($target_url, PHP_URL_PATH));
echo "Lien trouvé : $target_url\n";

// Extraire la date (8 chiffres AAAAMMJJ) du nom du fichier ZIP distant
$file_date = "";
if (preg_match('/(\d{8})/', $zip_filename, $matches)) {
    $file_date = $matches[1];
}

if (empty($file_date)) {
    die("Erreur : Impossible d'extraire la date du nom du fichier ZIP.\n");
}

echo "--- ÉTAPE 2 : Vérification de la mise à jour (Date source : $file_date) ---\n";

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si la date est déjà enregistrée en base
$stmt = $pdo->prepare("SELECT date_synchro FROM synchro WHERE synchro_produits_publics = 'date_source_donnees'");
$stmt->execute();
$stored_date = $stmt->fetchColumn();

if ($stored_date && $stored_date === $file_date) {
    echo "=> La base de données est déjà à jour (Date : $file_date). Aucune action requise.\n";
    exit; // Arrêt propre du script, pas de téléchargement inutile
}

echo "=> Nouvelle version détectée (Ancienne : " . ($stored_date ?: 'Aucune') . "). Lancement de la mise à jour...\n";

echo "--- ÉTAPE 3 : Téléchargement du fichier ZIP ---\n";
$fp = fopen($zip_filename, 'w+');
$ch = curl_init($target_url);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Script-EPhy-Download');
curl_exec($ch);
$download_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if ($download_code !== 200 || !file_exists($zip_filename)) {
    if (file_exists($zip_filename)) unlink($zip_filename);
    die("Erreur : Échec du téléchargement du fichier ZIP (Code HTTP: $download_code).\n");
}
echo "Téléchargement terminé : $zip_filename\n";

echo "--- ÉTAPE 4 : Extraction stricte du fichier 'produits_utf8.csv' ---\n";
$zip = new ZipArchive();
$csv_filename = 'produits_utf8.csv'; // Nom exact avec un 's'

if ($zip->open($zip_filename) === TRUE) {
    // Vérification stricte de la présence du fichier exact dans l'archive
    if ($zip->locateName($csv_filename) !== false) {
        $zip->extractTo('.', $csv_filename);
    } else {
        $zip->close();
        if (file_exists($zip_filename)) unlink($zip_filename);
        die("Erreur : Le fichier obligatoire '$csv_filename' est introuvable dans l'archive ZIP.\n");
    }
    $zip->close();
} else {
    if (file_exists($zip_filename)) unlink($zip_filename);
    die("Erreur : Impossible d'ouvrir l'archive ZIP.\n");
}

if (!file_exists($csv_filename)) {
    if (file_exists($zip_filename)) unlink($zip_filename);
    die("Erreur : Le fichier CSV n'a pas pu être extrait.\n");
}
echo "Fichier CSV extrait avec succès : $csv_filename\n";

echo "--- ÉTAPE 5 : Initialisation SQLite & Synchronisation (UPSERT) ---\n";

$pdo_public_ppp = new PDO('sqlite:' . DB_PUBLIC_PPP);
$pdo_public_ppp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Création de la table 'produits' (sans suppression des données existantes)
$pdo_public_ppp->exec("CREATE TABLE IF NOT EXISTS produits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_produit TEXT,
    numero_amm TEXT,
    nom_produit TEXT,
    etat_autorisation TEXT,
    date_retrait TEXT
)");

// Création de l'index unique indispensable pour l'UPSERT
$pdo_public_ppp->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_produit_unique ON produits(numero_amm, nom_produit);");

if (($handle = fopen($csv_filename, 'r')) !== FALSE) {
    $header = fgetcsv($handle, 0, ';');
    if (isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); // Nettoyage BOM UTF-8
    }

    $idx_type = array_search('type produit', $header);
    $idx_amm = array_search('numero AMM', $header);
    $idx_nom = array_search('nom produit', $header);
    $idx_sec = array_search('seconds noms commerciaux', $header);
    
    $idx_etat = false;
    $idx_retrait = false;
    foreach ($header as $i => $h) {
        $h_clean = trim(strtolower($h));
        if (strpos($h_clean, 'etat') !== false) $idx_etat = $i;
        if (strpos($h_clean, 'retrait') !== false) $idx_retrait = $i;
    }

    $pdo_public_ppp->beginTransaction();

    $stmt = $pdo_public_ppp->prepare("
        INSERT INTO produits (type_produit, numero_amm, nom_produit, etat_autorisation, date_retrait) 
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT(numero_amm, nom_produit) 
        DO UPDATE SET 
            type_produit = excluded.type_produit,
            etat_autorisation = excluded.etat_autorisation,
            date_retrait = excluded.date_retrait
    ");

    $count = 0;
    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
        $type = isset($row[$idx_type]) ? trim($row[$idx_type]) : '';
        $amm = isset($row[$idx_amm]) ? trim($row[$idx_amm]) : '';
        $nom_principal = isset($row[$idx_nom]) ? trim($row[$idx_nom]) : '';
        $seconds = ($idx_sec !== false && isset($row[$idx_sec])) ? trim($row[$idx_sec]) : '';
        $etat = ($idx_etat !== false && isset($row[$idx_etat])) ? trim($row[$idx_etat]) : '';
        $retrait = ($idx_retrait !== false && isset($row[$idx_retrait])) ? trim($row[$idx_retrait]) : '';

        $noms = [];
        if (!empty($nom_principal)) {
            $noms[] = $nom_principal;
        }

        if (!empty($seconds) && strtolower($seconds) !== 'nan') {
            $parts = explode('|', $seconds);
            foreach ($parts as $p) {
                $p_clean = trim($p);
                if (!empty($p_clean) && !in_array($p_clean, $noms)) {
                    $noms[] = $p_clean;
                }
            }
        }

        foreach ($noms as $nom) {
            $stmt->execute([$type, $amm, $nom, $etat, $retrait]);
            $count++;
        }
    }
    fclose($handle);
    $pdo_public_ppp->commit();

    // Mettre à jour la date source dans la table synchro
    $stmt = $pdo->prepare("INSERT INTO synchro (synchro_produits_publics, date_synchro) VALUES (:synchro_produits_publics, :date_synchro) ON CONFLICT(synchro_produits_publics) DO UPDATE SET date_synchro = :date_synchro");
    $stmt->execute([':synchro_produits_publics' => 'date_source_donnees', ':date_synchro' => $file_date]);


    echo "Synchronisation terminée avec succès !\n";
    echo "Total de lignes traitées / synchronisées : $count\n";
} else {
    // Nettoyage en cas d'échec d'ouverture du CSV
    if (file_exists($zip_filename)) unlink($zip_filename);
    if (file_exists($csv_filename)) unlink($csv_filename);
    die("Erreur : Impossible d'ouvrir le fichier CSV extrait.\n");
}

echo "--- ÉTAPE 6 : Nettoyage des fichiers temporaires ---\n";
if (file_exists($zip_filename)) {
    unlink($zip_filename);
    echo "Fichier ZIP supprimé : $zip_filename\n";
}
if (file_exists($csv_filename)) {
    unlink($csv_filename);
    echo "Fichier CSV temporaire supprimé : $csv_filename\n";
}

echo "Processus de mise à jour mené à son terme avec succès !\n";

?>