<?php

// Assurez-vous que le chemin vers votre autoloader Composer est correct
require_once 'vendor/autoload.php';
// Assurez-vous que le chemin vers votre fichier de connexion à la base de données est correct
require_once 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Génère un Ordre de Virement Global (tous les participants, toutes les banques)
 * en format PDF et Excel.
 * Retourne un tableau des chemins des fichiers générés (PDF et XLSX).
 */
function genererOrdreVirementGlobal(array $activity_data, array $participants_data, string $output_dir): array {
    $nom_activite = $activity_data['nom'] ?? 'Activité Inconnue';
    $date_actuelle_formatee = date('d F Y');
    $filename_prefix = "Ordre_Virement_Global_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';
    $total_general_amount = 0;

    foreach ($participants_data as $participant) {
        $total_general_amount += ($participant['montant_a_payer'] ?? 0);
    }

    // --- Génération PDF (avec Dompdf) ---
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Verdana');

        $dompdf = new Dompdf($options);

        $html = "
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
                    <h1>ORDRE DE VIREMENT GLOBAL</h1>
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
                $total_general_amount=0;
                foreach ($participants_data as $participant) {
                    $nom_complet = $participant['nom'] ?? '';
                    if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                        $nom_complet .= ' ' . $participant['prenom'];
                    }
                    $banque = $participant['banque'] ?? 'N/A';
                    $numero_compte = $participant['numero_compte'] ?? 'N/A';
                    $montant = $participant['montant_a_payer'] ?? 0;

                    $html .= "
                        <tr>
                            <td>" . $ordre++ . "</td>
                            <td class='text-left'>" . htmlspecialchars($nom_complet) . "</td>
                             <td class='text-right'>" . htmlspecialchars($participant['qualite']) . "</td>
                             <td class='text-right'>" . number_format($montant, 0, ',', ' ') . "</td>
                            <td>" . htmlspecialchars($banque) . "</td>
                            <td>" . htmlspecialchars($numero_compte) . "</td>
                        
                        </tr>";
                        $total_general_amount += ($participant['montant_a_payer'] ?? 0);
                }

                $html .= "
                   <tr class='total-row'>
                    <td colspan='3' class='text-right'><strong>TOTAL (   ) :</strong></td>
                    <td rowspan='1' class='text-center'><strong>" . number_format( $total_general_amount, 0, ',', ' ') . " FCFA</strong></td>
                    <td colspan='2' style='border:none;'></td>
                </tr>
            </tbody>
            </table>

         <div class='signature-block' text-align: center>
                <p><b>Arrêté le présent ordre de virement à la somme de ....... " . number_format( $total_general_amount, 0, ',', ' ') . " FCFA<</b></p>
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

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_path = $output_dir . '/' . $filename_prefix . '.pdf';
        file_put_contents($pdf_path, $dompdf->output());
        error_log("Ordre de Virement Global PDF généré : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Ordre de Virement Global PDF : " . $e->getMessage());
    }

    // --- Génération Excel (avec PhpSpreadsheet) ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ordre Virement Global');

        // Styles de base
        $spreadsheet->getDefaultStyle()->getFont()->setName('Verdana')->setSize(10); 
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // En-tête gauche
        $sheet->setCellValue('A1', 'RÉPUBLIQUE DU BÉNIN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->setCellValue('A2', 'MINISTÈRE DE lA ********');
        $sheet->setCellValue('A3', 'DIRECTION **************');
        $sheet->setCellValue('A4', 'SERVICE *********');
        foreach (range(1, 4) as $row_num) {
            $sheet->mergeCells('A' . $row_num . ':C' . $row_num);
        }

        // En-tête droit (date)
        $sheet->setCellValue('D1', 'Cotonou le ' . htmlspecialchars(mb_strtoupper($date_actuelle_formatee, 'UTF-8')));
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('D1:E1');

        // Titre principal
        $sheet->setCellValue('A6', 'ORDRE DE VIREMENT GLOBAL');
        $sheet->mergeCells('A6:E6');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Sous-titre
        $sheet->setCellValue('A7', 'RELATIF À L\'ACTIVITÉ : ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->mergeCells('A7:E7');
        $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row_excel = 9; // Ligne de début pour le tableau des participants

        // En-têtes du tableau
        $headerRow_excel = $row_excel;
        $sheet->setCellValue('A' . $headerRow_excel, 'N');
        $sheet->setCellValue('B' . $headerRow_excel, 'Nom et Prénoms du Bénéficiaire');
        $sheet->setCellValue('C' . $headerRow_excel, 'Banque');
        $sheet->setCellValue('D' . $headerRow_excel, 'Numéro de Compte');
        $sheet->setCellValue('E' . $headerRow_excel, 'Montant (FCFA)');

        // Styles des en-têtes
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $row_excel++;

        // Contenu du tableau
        $ordre = 1;
        foreach ($participants_data as $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';
            $montant = $participant['montant_a_payer'] ?? 0;

            $sheet->setCellValue('A' . $row_excel, $ordre++);
            $sheet->setCellValue('B' . $row_excel, htmlspecialchars($nom_complet));
            $sheet->setCellValue('C' . $row_excel, htmlspecialchars($banque));
            $sheet->setCellValue('D' . $row_excel, htmlspecialchars($numero_compte));
            $sheet->setCellValue('E' . $row_excel, $montant); // PhpSpreadsheet formate les nombres automatiquement

            // Appliquer les bordures aux cellules de données
            $sheet->getStyle('A' . $row_excel . ':E' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
            $sheet->getStyle('A' . $row_excel . ':E' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nom à gauche
            $sheet->getStyle('E' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // Montant à droite

            $row_excel++;
        }

        // Ligne du total général
        $totalRow_excel = $row_excel;
        $sheet->setCellValue('A' . $totalRow_excel, 'TOTAL GÉNÉRAL À VIRER :');
        $sheet->mergeCells('A' . $totalRow_excel . ':D' . $totalRow_excel);
        $sheet->getStyle('A' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $totalRow_excel . ':E' . $totalRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow_excel . ':E' . $totalRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A' . $totalRow_excel . ':E' . $totalRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');

        $sheet->setCellValue('E' . $totalRow_excel, $total_general_amount);
        $sheet->getStyle('E' . $totalRow_excel)->getNumberFormat()->setFormatCode('#,##0 " FCFA"'); // Format monétaire
        $sheet->getStyle('E' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $row_excel++; // Après le total

        // Définir des largeurs de colonne
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(18);

        // Bloc de signature (placé plus bas)
        $signature_start_row = $row_excel + 3;
        $sheet->setCellValue('D' . $signature_start_row, 'LE DIRECTEUR');
        $sheet->getStyle('D' . $signature_start_row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $signature_start_row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . $signature_start_row . ':E' . $signature_start_row); 

        $sheet->setCellValue('D' . ($signature_start_row + 1), '(ou responsable désigné)');
        $sheet->getStyle('D' . ($signature_start_row + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . ($signature_start_row + 1) . ':E' . ($signature_start_row + 1));

        $sheet->setCellValue('D' . ($signature_start_row + 4), htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre Responsable', 'UTF-8')));
        $sheet->getStyle('D' . ($signature_start_row + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('D' . ($signature_start_row + 4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . ($signature_start_row + 4) . ':E' . ($signature_start_row + 4));


        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $excel_path = $output_dir . '/' . $filename_prefix . '.xlsx';
        $writer->save($excel_path);
        error_log("Ordre de Virement Global Excel généré : " . $excel_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Ordre de Virement Global Excel : " . $e->getMessage());
    }
    
    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}


// --- Logique principale de generer_ordre_virement_global.php ---

if (isset($_GET['activite_id'])) {
    $activite_id = (int)$_GET['activite_id'];
    $document_type = $_GET['type'] ?? 'pdf'; // 'pdf' ou 'excel'

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

        // 4. Générer le document (Ordre de Virement Global)
        $generated_files = genererOrdreVirementGlobal($activity_data, $participants_data, $output_dir);

        $file_path = '';
        $file_name = '';
        $mime_type = '';

        if ($document_type === 'pdf' && isset($generated_files['pdf']) && file_exists($generated_files['pdf'])) {
            $file_path = $generated_files['pdf'];
            $file_name = basename($file_path);
            $mime_type = 'application/pdf';
        } elseif ($document_type === 'excel' && isset($generated_files['excel']) && file_exists($generated_files['excel'])) {
            $file_path = $generated_files['excel'];
            $file_name = basename($file_path);
            $mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            die("Type de document invalide ou fichier non généré.");
        }

        // 5. Proposer le téléchargement du fichier
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            ob_clean();
            flush();
            readfile($file_path);
            unlink($file_path); // Supprime le fichier temporaire après le téléchargement
            exit;
        } else {
            die("Erreur : Le fichier à télécharger n'existe pas.");
        }

    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de la récupération des données pour l'ordre de virement global : " . $e->getMessage());
        die("Erreur de base de données.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération/téléchargement de l'ordre de virement global : " . $e->getMessage());
        die("Erreur interne du serveur.");
    }
} else {
    die("ID d'activité manquant. Veuillez fournir un 'activite_id' dans l'URL.");
}

?>