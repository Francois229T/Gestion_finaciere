<?php

require_once 'db.php'; // Inclure le fichier de connexion à la base de données

// --- Configuration du répertoire d'upload des RIB ---
// ATTENTION : Ce répertoire DOIT être en dehors de la racine web (public_html, www, htdocs)
// pour des raisons de sécurité. Exemple: /var/www/uploads/ribs/ ou C:/chemin/vers/uploads/ribs/
define('RIB_UPLOAD_DIR', '/path/to/your/secure/rib_uploads/'); // Remplacez par le chemin réel de votre dossier d'upload

if (isset($_GET['participation_id']) && !empty($_GET['participation_id'])) {
    $participation_id = (int)$_GET['participation_id'];

    if ($participation_id <= 0) {
        die("ID de participation invalide.");
    }

    try {
        // 1. Récupérer le nom du fichier RIB associé à cette participation
        $stmt = $mysqlClient->prepare("SELECT rib_pdf_path FROM participations
        JOIN comptes_bancaires ON comptes_bancaires.id_compte=participations.compte_id
         WHERE id = :participation_id");
        $stmt->execute([':participation_id' => $participation_id]);
        $participation_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($participation_data && !empty($participation_data['rib_pdf_path'])) {
            $rib_filename = basename($participation_data['rib_pdf_path']); // Assure qu'il n'y a pas de chemin dans le nom de fichier
            $file_path = RIB_UPLOAD_DIR . $rib_filename;

            // 2. Vérifier si le fichier existe et est lisible
            if (file_exists($file_path) && is_readable($file_path)) {
                // 3. Définir les en-têtes HTTP pour forcer le téléchargement
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream'); // Type générique pour forcer le téléchargement
                header('Content-Disposition: attachment; filename="' . $rib_filename . '"'); // Nom du fichier lors du téléchargement
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));

                // 4. Lire et envoyer le fichier au navigateur
                readfile($file_path);
                exit; // Terminer le script après l'envoi du fichier
            } else {
                die("Erreur : Le fichier RIB n'existe pas ou est illisible.");
            }
        } else {
            die("Aucun fichier RIB trouvé pour cette participation ou participation inexistante.");
        }

    } catch (PDOException $e) {
        die("Erreur de base de données lors de la récupération du RIB : " . htmlspecialchars($e->getMessage()));
    }
} else {
    die("ID de participation non spécifié.");
}

?>