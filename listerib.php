<?php

// Activer l'affichage des erreurs pour le débogage. À retirer en production !
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
 * Génère une Liste des Coordonnées Bancaires (RIB) pour tous les participants d'une activité.
 *
 * @param array $activity_data Données de l'activité.
 * @param array $participants_data Tableau des participants.
 * @param string $output_dir Répertoire de sortie pour les documents générés.
 * @param string $header_left_content_raw Contenu brut pour l'en-tête gauche, lignes séparées par '@@@' ou '\n'.
 * @param string $document_date_str Date du document au format 'AAAA-MM-JJ' ou 'jj/mm/AAAA'.
 * @return array Chemins des fichiers PDF et Excel générés.
 */
function genererListeRib(array $activity_data, array $participants_data, string $output_dir, string $header_left_content_raw = '', string $document_date_str = ''): array {
    $nom_activite = $activity_data['nom'] ?? 'Activité Inconnue';
    
    // Gérer la date du document : utilise la date fournie dans $document_date_str si valide, sinon la date actuelle.
    $document_date_obj = DateTime::createFromFormat('Y-m-d', $document_date_str);
    if ($document_date_obj === false && !empty($document_date_str)) {
        $document_date_obj = DateTime::createFromFormat('d/m/Y', $document_date_str);
    }
    if ($document_date_obj === false || empty($document_date_str)) {
        $document_date_obj = new DateTime(); // Date actuelle si non fournie ou invalide
    }
    
    $date_pour_entete_et_journee = $document_date_obj->format('d F Y'); // Format for "Journée" and top-right date
    
    // Lieu pour la ligne "Cotonou, le..." (fixe selon la demande)
    $lieu_fixe_entete = "Cotonou"; 

    $filename_prefix = "Liste_RIB_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';

    // Traitement du contenu de l'en-tête gauche (Ministère...)
    if (empty($header_left_content_raw)) {
        $header_left_content = "<strong>RÉPUBLIQUE DU BÉNIN</strong><br><strong>MINISTÈRE DE LA (À compléter)</strong><br><strong>DIRECTION (À compléter)</strong><br><strong>SERVICE (À compléter)</strong>";
    } else {
        // Ensure all lines in header_left_content are bold and use Arial
        $header_left_content = str_replace('@@@', '<br>', htmlspecialchars($header_left_content_raw));
        $header_left_content = nl2br($header_left_content);
        $header_left_content = "<span style='font-family: Arial; font-weight: bold;'>" . $header_left_content . "</span>";
    }
    
    // --- Génération PDF (avec Dompdf) ---
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial'); // Set default font for PDF

        $dompdf = new Dompdf($options);

        $html_header_and_table_start = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: 'Arial', sans-serif; margin: 15mm; font-size: 10pt; }
                    .header-container { 
                        width: 100%; 
                        display: table;
                        table-layout: fixed;
                        margin-bottom: 5mm; 
                    }
                    .header-left-cell { 
                        display: table-cell; 
                        width: 50%; 
                        text-align: center; /* Centered */
                        vertical-align: top; 
                        font-weight: bold; /* Make content bold */
                    }
                    .header-right-cell { 
                        display: table-cell; 
                        width: 50%; 
                        text-align: center; /* Centered */
                        vertical-align: top; 
                        font-weight: bold; /* Make content bold */
                    }
                    .header-left-cell p, .header-right-cell p { margin: 0; line-height: 1.2; font-size: 9pt; }
                    .header-left-cell strong { font-size: 10pt; } 
                    .header-right-cell p { text-transform: uppercase; }

                    .top-right-date {
                        text-align: right; /* Specific alignment for this line */
                        margin-bottom: 2mm; /* Space between date and main title */
                        font-size: 9pt;
                        font-weight: bold;
                    }

                    .title-block-right {
                        text-align: center; /* Centered within its column */
                        margin-left: auto; /* For centering within the available space */
                        margin-right: auto; /* For centering within the available space */
                        width: fit-content; /* Make block size fit content */
                    }
                    .title-block-right h1 { 
                        font-size: 14pt; 
                        margin: 0; 
                        line-height: 1.3; 
                        text-decoration: underline; 
                        font-weight: bold; /* Make H1 bold */
                    }
                    .title-block-right .sub-title {
                        font-size: 10pt; 
                        margin-top: 5px; 
                        display: block; 
                        font-weight: bold; /* Make subtitle bold */
                    }
                    
                    .clear-float { clear: both; }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 15px; 
                        border: 1px solid #ddd;
                        table-layout: fixed; /* Added to keep column widths consistent */
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: center;
                        vertical-align: middle; /* Changed from top for better centering of text */
                        font-weight: bold; /* All table content bold */
                        font-size: 9pt; /* Adjusted for better fit if needed */
                    }
                    th {
                        background-color: #f9f9f9;
                        color: #333;
                        text-transform: uppercase;
                    }
                    /* Column Widths (Adjusted for only 4 columns) */
                    th:nth-child(1), td:nth-child(1) { width: 5%; } /* N° */
                    th:nth-child(2), td:nth-child(2) { width: 35%; } /* NOM ET PRENOMS */
                    th:nth-child(3), td:nth-child(3) { width: 30%; } /* BANQUE */
                    th:nth-child(4), td:nth-child(4) { width: 30%; } /* RIB */

                    .bold { font-weight: bold; }
                    .text-left { text-align: left; }
                    .text-right { text-align: right; }
                    
                    .signature-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 25mm; 
                        border: none;
                    }
                    .signature-table td {
                        width: 50%;
                        padding: 0;
                        vertical-align: top;
                        border: 0;
                    }
                    .signature-cell.left-align, .signature-cell.right-align {
                        text-align: center;
                    }
                    .signature-cell p { margin: 0; line-height: 1.5; font-size: 10pt; }
                    .signature-cell .bold-underline { font-weight: bold; text-decoration: underline; }

                    .signature-line {
                        height: 20mm; 
                        border-bottom: 1px solid #000;
                        width: 70%; 
                        margin: 5px auto 0 auto; 
                    }
                    /* For page breaks */
                    .page-break {
                        page-break-before: always;
                    }
                </style>
            </head>
            <body>
                <div class='header-container'>
                    <div class='header-left-cell'>
                        " . $header_left_content . "
                    </div>
                    <div class='header-right-cell'>
                        <p class='top-right-date'>" . htmlspecialchars($lieu_fixe_entete) . ", le " . htmlspecialchars($date_pour_entete_et_journee) . "</p>
                        <div class='title-block-right'>
                            <h1>LISTE DES COORDONNÉES BANCAIRES (RIB)</h1>
                            <p class='sub-title'>POUR L'ACTIVITÉ : <span class='bold'>" . htmlspecialchars(mb_strtoupper($nom_activite, 'UTF-8')) . "</span></p>
                        </div>
                    </div>
                </div>
                <div class='clear-float'></div>

                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOM ET PRÉNOMS</th>
                            <th>BANQUE</th>
                            <th>RIB</th>
                        </tr>
                    </thead>
                    <tbody>";
            
        $html_table_end_and_footer = "
                    </tbody>
                </table>

                <table class='signature-table'>
                    <tr>
                        <td class='signature-cell left-align'>
                            <p style='margin-top: 20px;'><b>L'ADMINISTRATION</b></p>
                            <div class='signature-line'></div> 
                            <p class='bold-underline' style='margin-top: 10px;'>[Cachet et Signature]</p>
                        </td>
                        <td class='signature-cell right-align'>
                            <p style='margin-top: 20px;'><b>LE SERVICE DU PERSONNEL</b></p>
                            <div class='signature-line'></div> 
                            <p class='bold-underline' style='margin-top: 10px;'>[Cachet et Signature]</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $html_participants_rows = '';
        $ordre = 1;

        foreach ($participants_data as $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';

            $html_participants_rows .= "
                <tr>
                    <td>" . $ordre . "</td>
                    <td class='text-left'>" . htmlspecialchars($nom_complet) . "</td>
                    <td>" . htmlspecialchars($banque) . "</td>
                    <td>" . htmlspecialchars($numero_compte) . "</td>
                </tr>";
            
            $ordre++; 
        }

        $dompdf->loadHtml($html_header_and_table_start . $html_participants_rows . $html_table_end_and_footer);
        $dompdf->setPaper('A4', 'landscape'); 
        $dompdf->render();

        // Add page numbering
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont("Arial", "normal");
        // Adjusted position for landscape (x, y) - original was 500, 800 for portrait
        $canvas->page_text(700, 570, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0, 0, 0)); 

        $pdf_path = $output_dir . '/' . $filename_prefix . '.pdf';
        file_put_contents($pdf_path, $dompdf->output());
        error_log("Liste des RIB PDF générée : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la Liste des RIB PDF : " . $e->getMessage());
    }

    // --- Génération Excel (avec PhpSpreadsheet) ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liste RIB'); 

        // Styles de base - Cohérence avec le PDF
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10); // Set default font for Excel
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // En-tête gauche (Ministère...) - Centré dans la colonne A-B, tout en gras
        $header_left_lines = explode("\n", $header_left_content_raw); 
        if (empty($header_left_content_raw)) {
            $header_left_lines = ["RÉPUBLIQUE DU BÉNIN", "MINISTÈRE DE LA (À compléter)", "DIRECTION (À compléter)", "SERVICE (À compléter)"];
        } else {
            $header_left_lines = explode('@@@', str_replace('\n', '@@@', $header_left_content_raw));
        }

        $current_row_excel_header = 1;
        foreach ($header_left_lines as $line) {
            $sheet->setCellValue('A' . $current_row_excel_header, trim($line));
            $sheet->getStyle('A' . $current_row_excel_header)->getFont()->setBold(true)->setSize(10);
            $sheet->mergeCells('A' . $current_row_excel_header . ':B' . $current_row_excel_header); // Adjusted merge
            $sheet->getStyle('A' . $current_row_excel_header)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $current_row_excel_header++;
        }
        
        // Date "Cotonou, le..." en haut à droite, en gras
        $sheet->setCellValue('C1', htmlspecialchars($lieu_fixe_entete) . ', le ' . htmlspecialchars(mb_strtoupper($date_pour_entete_et_journee, 'UTF-8')));
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('C1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('C1:D1'); // Adjusted merge

        // Titre "LISTE DES COORDONNÉES BANCAIRES (RIB)" en haut à droite (centré dans la plage C-D), en gras
        $sheet->setCellValue('C2', 'LISTE DES COORDONNÉES BANCAIRES (RIB)');
        $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->getStyle('C2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('C2:D2'); // Adjusted merge

        // Subtitle directly below main title in Excel (centré dans la plage C-D), en gras
        $sheet->setCellValue('C3', 'POUR L\'ACTIVITÉ : ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->getStyle('C3')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('C3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('C3:D3'); // Adjusted merge

        $row_excel = max($current_row_excel_header + 5, 8); // Ensure table starts sufficiently low

        // En-têtes du tableau (Adjusted for 4 columns)
        $headerRow_excel = $row_excel;
        $sheet->setCellValue('A' . $headerRow_excel, 'N°');
        $sheet->setCellValue('B' . $headerRow_excel, 'NOM ET PRÉNOMS');
        $sheet->setCellValue('C' . $headerRow_excel, 'BANQUE');
        $sheet->setCellValue('D' . $headerRow_excel, 'RIB');

        // Styles des en-têtes
        $sheet->getStyle('A' . $headerRow_excel . ':D' . $headerRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow_excel . ':D' . $headerRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
        $sheet->getStyle('A' . $headerRow_excel . ':D' . $headerRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
        $sheet->getStyle('A' . $headerRow_excel . ':D' . $headerRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row_excel++;

        // Contenu du tableau
        $ordre_excel = 1;

        foreach ($participants_data as $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';

            $sheet->setCellValue('A' . $row_excel, $ordre_excel++);
            $sheet->setCellValue('B' . $row_excel, htmlspecialchars($nom_complet));
            $sheet->setCellValue('C' . $row_excel, htmlspecialchars($banque));
            $sheet->setCellValue('D' . $row_excel, htmlspecialchars($numero_compte));

            // Appliquer les bordures et rendre tout bold
            $sheet->getStyle('A' . $row_excel . ':D' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
            $sheet->getStyle('A' . $row_excel . ':D' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); 
            $sheet->getStyle('A' . $row_excel . ':D' . $row_excel)->getFont()->setBold(true); // Make all cell content bold

            $row_excel++;
        }

        // Définir des largeurs de colonne (Adjusted)
        $sheet->getColumnDimension('A')->setWidth(8); // N°
        $sheet->getColumnDimension('B')->setWidth(45); // NOM ET PRÉNOMS
        $sheet->getColumnDimension('C')->setWidth(25); // BANQUE
        $sheet->getColumnDimension('D')->setWidth(35); // RIB

        // Bloc de signature
        $signature_start_row_excel = $row_excel + 3; 

        // Signature 1
        $sheet->setCellValue('B' . $signature_start_row_excel, 'L\'ADMINISTRATION');
        $sheet->getStyle('B' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('B' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . $signature_start_row_excel . ':C' . $signature_start_row_excel); 

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('B' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('B' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 1) . ':C' . ($signature_start_row_excel + 1));

        $sheet->setCellValue('B' . ($signature_start_row_excel + 3), '[Cachet et Signature]');
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 3) . ':C' . ($signature_start_row_excel + 3));

        // Signature 2
        $sheet->setCellValue('D' . $signature_start_row_excel, 'LE SERVICE DU PERSONNEL');
        $sheet->getStyle('D' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('D' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('D' . $signature_start_row_excel . ':E' . $signature_start_row_excel); // Adjusted merge, using E for symmetry.

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('D' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('D' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('D' . ($signature_start_row_excel + 1) . ':E' . ($signature_start_row_excel + 1));

        $sheet->setCellValue('D' . ($signature_start_row_excel + 3), '[Cachet et Signature]');
        $sheet->getStyle('D' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('D' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('D' . ($signature_start_row_excel + 3) . ':E' . ($signature_start_row_excel + 3));

        $writer = new Xlsx($spreadsheet);
        $excel_path = $output_dir . '/' . $filename_prefix . '.xlsx';
        $writer->save($excel_path);
        error_log("Liste des RIB Excel générée : " . $excel_path);

    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la Liste des RIB Excel : " . $e->getMessage());
    }

    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}

// --- Logique principale du script ---

// Vérifie si les paramètres nécessaires sont présents dans l'URL
if (isset($_GET['activite_id']) && isset($_GET['format'])) { 
    global $mysqlClient; // Accède à la connexion PDO définie dans db.php

    $activite_id = (int)$_GET['activite_id'];
    $output_format = strtolower($_GET['format']); // 'pdf' ou 'excel'

    // Récupérer les données personnalisées envoyées par le formulaire (via GET)
    $custom_header_data = [
        'header_left_content' => $_GET['header_left_content'] ?? null,
        'document_date'       => $_GET['document_date'] ?? null,
    ];

    // Validation du format de sortie
    if (!in_array($output_format, ['pdf', 'excel'])) {
        die("Format de sortie non valide. Utilisez 'pdf' ou 'excel'.");
    }

    try {
        // 1. Récupérer les données de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if (!$activity_data) {
            die("Activité non trouvée pour l'ID : " . $activite_id);
        }
        
        // Pour une liste de RIB, ces champs ne sont pas directement utilisés dans le contenu
        // mais sont conservés dans $activity_data pour la cohérence si d'autres parties du code les utilisent.
        $activity_data['responsable_titre'] = htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'LE CHEF DE SERVICE', 'UTF-8'));
        $activity_data['financier'] = htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'L\'ADMINISTRATION', 'UTF-8'));


        // 2. Récupérer les participants liés à cette activité avec seulement les informations de RIB
        $sql_participants = "
            SELECT
                part.type_participant,
                COALESCE(pp.nom, pm.denomination) AS nom,
                pp.prenom,
                cb.banque,
                cb.numero_compte
            FROM
                participations part
            LEFT JOIN
                personnes_physiques pp ON part.participant_id = pp.participant_id AND part.type_participant = 'individu'
            LEFT JOIN
                personnes_morales pm ON part.participant_id = pm.participant_id AND part.type_participant = 'personne_morale'
            LEFT JOIN participants ppa ON ppa.id = part.participant_id
            LEFT JOIN
                comptes_bancaires cb ON ppa.id = cb.participant_id
            WHERE
                part.activite_id = :activite_id
            ORDER BY
                cb.banque ASC, nom ASC, prenom ASC;
        ";

        $stmt_participants = $mysqlClient->prepare($sql_participants);
        $stmt_participants->execute([':activite_id' => $activite_id]);
        $participants_data = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);

        if (empty($participants_data)) {
            die("Aucun participant trouvé avec des informations de compte bancaire pour cette activité.");
        }

        // 3. Définir le répertoire de sortie temporaire
        $output_dir = __DIR__ . '/temp_documents';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        // 4. Générer la liste des RIB
        $generated_files = genererListeRib(
            $activity_data,
            $participants_data,
            $output_dir,
            $custom_header_data['header_left_content'], 
            $custom_header_data['document_date'] 
        );

        $generated_file = $generated_files[$output_format];

        if ($generated_file && file_exists($generated_file)) {
            // 5. Proposer le téléchargement du fichier généré
            header('Content-Description: File Transfer');
            if ($output_format === 'pdf') {
                header('Content-Type: application/pdf');
            } elseif ($output_format === 'excel') {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            }
            header('Content-Disposition: attachment; filename="' . basename($generated_file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($generated_file));
            ob_clean(); // Nettoie le tampon de sortie
            flush(); // Force l'envoi des en-têtes et du contenu du tampon
            readfile($generated_file); // Lit le fichier et l'envoie au navigateur
            unlink($generated_file); // Supprime le fichier temporaire après le téléchargement
            exit; // Termine le script
        } else {
            die("La génération du fichier a échoué ou le fichier n'existe pas.");
        }

    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de la récupération des données pour la liste des RIB : " . $e->getMessage());
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération/téléchargement de la liste des RIB : " . $e->getMessage());
        die("Une erreur inattendue est survenue.");
    }
} else {
    die("ID d'activité ou format manquant. Veuillez fournir 'activite_id' et 'format' (pdf ou excel) dans l'URL.");
}

?>