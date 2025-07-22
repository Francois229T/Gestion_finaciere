<?php

// Assurez-vous que le chemin vers votre autoloader Composer est correct
require_once 'vendor/autoload.php';
// Assurez-vous que le chemin vers votre fichier de connexion à la base de données est correct
require_once 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Construit le contenu HTML pour un ordre de virement pour une banque spécifique.
 * Cette fonction est une helper pour genererOrdreVirementParBanque.
 */
function buildHtmlForBankOrder(array $activity_data, string $bank_name, array $bank_participants, float $total_amount_for_bank, string $date_actuelle_formatee): string {
    $nom_activite = $activity_data['nom'] ?? 'Activité Inconnue';
    $html_content = "
     <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Maiandra GD', sans-serif; margin: 15mm; font-size: 10pt; }
                .header-container { width: 100%; display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10mm; }
                .header-left, .header-right { width: 48%; }
                .header-left { text-align: left; }
                .header-left p { margin: 0; line-height: 1.2; font-size: 9pt; }
                .header-left { font-size: 12pt; }
                .header-right { text-align: right; }
                .header-right p { margin: 0; line-height: 1.2; font-size: 9pt; text-transform: uppercase; }

                .title { text-align: left; margin-bottom: 5mm; }
                .title h1 { font-size: 08pt; margin: 0; line-height: 1.3; }
                .title p { font-size: 08pt; margin-top: 5px; }

                .activity-details { text-align: left; margin-bottom: 15mm; font-size: 10pt; }
                .activity-details p { margin: 2px 0; }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                    border: 1px solid #ddd;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: center;
                    vertical-align: top;
                }
                th {
                    background-color: #f9f9f9;
                    font-weight: bold;
                    color: #333;
                    text-transform: uppercase;
                }
                .bold { font-weight: bold; }
                 .total-row td {
                    font-weight: bold;
                    background-color: #e0e0e0;
                    vertical-align: middle !important; /* Pour centrer verticalement le texte */
                }
                
                /* Styles pour le tableau de signature */
                .signature-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 30mm; /* Marge supérieure pour l'ensemble de la section signature */
                    border: none; /* Supprime les bordures du tableau */
                }
                .signature-table td {
                    width: 50%; /* Chaque colonne prendra 50% de la largeur du tableau */
                    padding: 0; /* Pas de padding par défaut pour les cellules */
                    vertical-align: top;
                    border: 0; /* Supprime les bordures des cellules */
                }
                .signature-cell.left-align {
                    text-align: center;
                }
                .signature-cell.right-align {
                    text-align: center;
                }
                .signature-cell p { margin: 0; line-height: 1.5; font-size: 10pt; }
                .signature-cell .bold-underline { font-weight: bold; text-decoration: underline; }
            </style>
        </head>
    <body>
     <table class='signature-table'>
                <tr>
                    <td class='signature-cell left-align'>
                    <p class='header-info'><strong>REPUBLIQUE DU BENIN</strong></p>
                <p class='header-info'><strong>MINISTÈRE DE lA ********</strong></p>
                <p class='header-info'><strong>DIRECTION **************</strong></p>
                <p class='header-info'><strong>SERVICE *********</strong></p>
                </td>
                    <td class='signature-cell right-align'>
                     <p>Cotonou le ............</p>
                    <h1>ORDRE DE VIREMENT " . htmlspecialchars(mb_strtoupper($bank_name, 'UTF-8')) . "</h1>
            <p>DES INDEMITES ET FRAIS D'ENTRETIEN ACCORDES AUX MEMBRES DE LA COMMISSION CHARGE DE LA <span class='bold'>" . htmlspecialchars(mb_strtoupper($nom_activite, 'UTF-8')) . "</span></p>
                    </td>
                </tr>
            </table>
        <table>
            <thead>
                <tr>
                    <th>N</th>
                    <th>NOM ET PRENOMS</th>
                    <th>QUALITE</th>
                    <th>MONTANT</th>
                    <th>BANQUE</th>
                    <th>RIB</th>
                </tr>
            </thead>
            <tbody>";
            
            $ordre = 1;
            foreach ($bank_participants as $participant) {
                $nom_complet = $participant['nom'] ?? '';
                if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                    $nom_complet .= ' ' . $participant['prenom'];
                }
                $numero_compte = $participant['numero_compte'] ?? 'N/A';
                $montant = $participant['montant_a_payer'] ?? 0; // IMPORTANT: Assurez-vous que ce champ existe dans vos données

                $html_content .= "
                    <tr>
                        <td>" . $ordre++ . "</td>
                        <td class='text-left'>" . htmlspecialchars($nom_complet) . "</td>
                        <td class='text-right'>" . htmlspecialchars($participant['qualite']) . "</td>
                        <td class='text-right'>" . number_format($montant, 0, ',', ' ') . "</td>
                         <td class='text-right'>" . htmlspecialchars(mb_strtoupper($bank_name, 'UTF-8')) . "</td>
                        <td>" . htmlspecialchars($numero_compte) . "</td>
                    </tr>";
            }

            $html_content .= "
                <tr class='total-row'>
                    <td colspan='3' class='text-right'><strong>TOTAL (   ) :</strong></td>
                    <td rowspan='1' class='text-center'><strong>" . number_format($total_amount_for_bank, 0, ',', ' ') . " FCFA</strong></td>
                    <td colspan='2' style='border:none;'></td>
                </tr>
            </tbody>
        </table>

        <div class='signature-block' text-align: center>
                <p><b>Arrêté le présent ordre de virement à la somme de ....... " . number_format($total_amount_for_bank, 0, ',', ' ') . " FCFA<</b></p>
                 </div>

        <table class='signature-table'>
                <tr>
                    <td class='signature-cell left-align'>
                        <p style='margin-top: 20px;'><b>LE C/GAP</b></p>
                        <p>(ici titre financier et son nom en bas)</p>
                        <p class='bold-underline' style='margin-top: 10px;'>" . htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'Financier', 'UTF-8')) . "</p>
                    </td>
                     <td class='signature-cell right-align'>
                        <p style='margin-top: 20px;'><b>LE CMAP</b></p>
                        <p>(ici le 1 er respo et son nom en bas)</p>
                        <p class='bold-underline' style='margin-top: 10px;'>" . htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre Responsable', 'UTF-8')) . "</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

    return $html_content;
}


/**
 * Génère les Ordres de Virement, un document PDF par banque.
 * Retourne un tableau des chemins des fichiers PDF générés.
 */
function genererOrdreVirementParBanque(array $activity_data, array $participants_data, string $output_dir): array {
    $generated_files_paths = [];
    $date_actuelle_formatee = date('d F Y'); // Formatage de la date actuelle

    // 1. Grouper les participants par banque
    $participants_by_bank = [];
    foreach ($participants_data as $participant) {
        $bank = $participant['banque'] ?? 'Banque Inconnue';
        $participants_by_bank[$bank][] = $participant;
    }

    // 2. Parcourir chaque banque pour générer un document PDF distinct
    foreach ($participants_by_bank as $bank_name => $bank_participants) {
        $total_amount_for_bank = 0;
        foreach ($bank_participants as $p) {
            // !!! RAPPEL IMPORTANT : Le champ 'montant_a_payer' doit être récupéré depuis votre DB.
            $p_montant = $p['montant_a_payer'] ?? 0; 
            $total_amount_for_bank += $p_montant;
        }

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Verdana');

            $dompdf = new Dompdf($options);

            // Construire le HTML pour l'ordre de virement de cette banque
            $html_bank_order = buildHtmlForBankOrder($activity_data, $bank_name, $bank_participants, $total_amount_for_bank, $date_actuelle_formatee);

            $dompdf->loadHtml($html_bank_order);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $bank_filename = "Ordre_Virement_" . preg_replace('/[^a-zA-Z0-9_]/', '', $bank_name) . "_" . ($activity_data['id'] ?? 'sans_id') . ".pdf";
            $pdf_path = $output_dir . '/' . $bank_filename;
            file_put_contents($pdf_path, $dompdf->output());
            $generated_files_paths[] = $pdf_path; // Stocke le chemin du fichier généré
            error_log("Ordre de Virement PDF généré pour " . $bank_name . " : " . $pdf_path);

        } catch (Exception $e) {
            error_log("Erreur lors de la génération de l'Ordre de Virement PDF pour " . $bank_name . " : " . $e->getMessage());
        }
    }
    return $generated_files_paths; // Retourne tous les chemins des fichiers PDF générés
}


// --- Logique principale de generer_ordre_virement.php ---

if (isset($_GET['activite_id'])) {
    $activite_id = (int)$_GET['activite_id'];

    try {
        // 1. Récupérer les données de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if (!$activity_data) {
            die("Activité non trouvée.");
        }

        // TODO: Ajoutez ici la logique pour récupérer $activity_data['responsable_titre']
        // Ex: $activity_data['responsable_titre'] = "NOM PRENOM DU RESPONSABLE, Fonction";


        // 2. Récupérer les participants liés à cette activité
        // !!! IMPORTANT : Modifiez cette requête pour récupérer le MONTANT RÉEL À PAYER
        $sql_participants = "
            SELECT
                part.type_participant,
                COALESCE(pp.nom, pm.denomination) AS nom,
                pp.prenom,
                part.titre AS qualite,
                cb.banque,
                cb.numero_compte,
                (part.nb_jours_copies*part.taux_journalier_copie + part.nb_jours_deplacement*part.frais_deplacement + part.forfait_participant) AS montant_a_payer 
            FROM
                participations part
            LEFT JOIN
                personnes_physiques pp ON part.participant_id = pp.participant_id AND part.type_participant = 'individu'
            LEFT JOIN
                personnes_morales pm ON part.participant_id = pm.participant_id AND part.type_participant = 'personne_morale'
            LEFT JOIN participants ppa ON ppa.id=part.participant_id
            LEFT JOIN
                comptes_bancaires cb ON part.participant_id = cb.participant_id
            WHERE
                part.activite_id = :activite_id
            ORDER BY
                 nom ASC, prenom ASC;
        ";

        $stmt_participants = $mysqlClient->prepare($sql_participants);
        $stmt_participants->execute([':activite_id' => $activite_id]);
        $participants_data = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);

        if (empty($participants_data)) {
            die("Aucun participant trouvé pour cette activité.");
        }

        // 3. Définir le répertoire de sortie temporaire
        $output_dir = __DIR__ . '/temp_documents';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        // 4. Générer les documents PDF par banque
        $generated_pdf_files = genererOrdreVirementParBanque($activity_data, $participants_data, $output_dir);

        if (empty($generated_pdf_files)) {
            die("Aucun ordre de virement PDF n'a pu être généré.");
        }

        // 5. Compresser les fichiers PDF générés dans un fichier ZIP
        $zip_filename = $output_dir . '/Ordres_Virement_' . ($activity_data['id'] ?? 'sans_id') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($generated_pdf_files as $pdf_file) {
                if (file_exists($pdf_file)) {
                    $zip->addFile($pdf_file, basename($pdf_file));
                }
            }
            $zip->close();

            // Supprimer les fichiers PDF individuels après les avoir ajoutés au ZIP
            foreach ($generated_pdf_files as $pdf_file) {
                if (file_exists($pdf_file)) {
                    unlink($pdf_file);
                }
            }

            // 6. Proposer le téléchargement du fichier ZIP
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_filename) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zip_filename));
            ob_clean();
            flush();
            readfile($zip_filename);
            unlink($zip_filename); // Supprime le fichier ZIP temporaire après le téléchargement
            exit;
        } else {
            die("Impossible de créer l'archive ZIP.");
        }

    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de la récupération des données pour les ordres de virement : " . $e->getMessage());
        die("Erreur de base de données.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération/téléchargement des ordres de virement : " . $e->getMessage());
        die("Erreur interne du serveur.");
    }
} else {
    die("ID d'activité manquant. Veuillez fournir un 'activite_id' dans l'URL.");
}

?>