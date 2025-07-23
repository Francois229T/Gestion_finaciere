<?php

require_once 'vendor/autoload.php';
require_once 'db.php'; // Assurez-vous que ce fichier contient la connexion à votre base de données.

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
 *
 * @param array $activity_data Données de l'activité.
 * @param array $participants_data Tableau des participants.
 * @param string $output_dir Répertoire de sortie pour les documents générés.
 * @param string $header_left_content_raw Contenu brut pour l'en-tête gauche, lignes séparées par '@@@' ou '\n'.
 * @param string $document_date_str Date du document au format 'jj/mm/AAAA' ou 'AAAA-MM-JJ'.
 * @return array Chemins des fichiers PDF et Excel générés.
 */
function genererOrdreVirementGlobal(array $activity_data, array $participants_data, string $output_dir, string $header_left_content_raw = '', string $document_date_str = ''): array {
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
    
    // Lieu pour la ligne "Cotonou, le..." (fixe selon la demande précédente)
    $lieu_fixe_entete = "Cotonou"; 

    $filename_prefix = "Ordre_Virement_Global_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';
    $total_general_amount = 0;

    foreach ($participants_data as $participant) {
        $total_general_amount += ($participant['montant_a_payer'] ?? 0);
    }

    // Traitement du contenu de l'en-tête gauche (Ministère...)
    if (empty($header_left_content_raw)) {
        $header_left_content = "<strong>RÉPUBLIQUE DU BÉNIN</strong><br><strong>MINISTÈRE DE LA ************</strong><br><strong>DIRECTION ********************</strong><br><strong>SERVICE ***************</strong>";
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
                    /* Column Widths */
                    th:nth-child(1), td:nth-child(1) { width: 5%; } /* N° */
                    th:nth-child(2), td:nth-child(2) { width: 25%; } /* NOM ET PRENOMS */
                    th:nth-child(3), td:nth-child(3) { width: 15%; } /* QUALITÉ */
                    th:nth-child(4), td:nth-child(4) { width: 15%; } /* MONTANT */
                    th:nth-child(5), td:nth-child(5) { width: 20%; } /* BANQUE */
                    th:nth-child(6), td:nth-child(6) { width: 20%; } /* RIB */


                    .bold { font-weight: bold; }
                    .text-left { text-align: left; }
                    .text-right { text-align: right; }
                    .total-row td {
                        font-weight: bold;
                        background-color: #e0e0e0;
                        vertical-align: middle !important;
                    }
                    
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
                            <h1>ORDRE DE VIREMENT GLOBAL</h1>
                            <p class='sub-title'>DES INDEMNITÉS ET FRAIS D'ENTRETIEN ACCORDÉS AUX MEMBRES DE LA COMMISSION CHARGÉE DE LA <span class='bold'>" . htmlspecialchars(mb_strtoupper($nom_activite, 'UTF-8')) . "</span></p>
                        </div>
                    </div>
                </div>
                <div class='clear-float'></div>

                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOM ET PRÉNOMS</th>
                            <th>QUALITÉ</th>
                            <th>MONTANT (FCFA)</th>
                            <th>BANQUE</th>
                            <th>RIB</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        $html_table_end_and_footer = "
                    </tbody>
                </table>

                <div style='text-align: center; margin-top: 15mm;'>
                    <p><b>Arrêté le présent ordre de virement à la somme de " . number_format($total_general_amount, 0, ',', ' ') . " FCFA</b></p>
                    <p><b>(en toutes lettres : " . htmlspecialchars(nombreEnLettres($total_general_amount)) . " francs CFA)</b></p>
                </div>

                <table class='signature-table'>
                    <tr>
                        <td class='signature-cell left-align'>
                            <p style='margin-top: 20px;'><b>LE C/GAP</b></p>
                            <div class='signature-line'></div> 
                            <p class='bold-underline' style='margin-top: 10px;'>" . htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'Nom du Chef du Chef du Service Financier', 'UTF-8')) . "</p>
                        </td>
                        <td class='signature-cell right-align'>
                            <p style='margin-top: 20px;'><b>LE CMAP</b></p>
                            <div class='signature-line'></div> 
                            <p class='bold-underline' style='margin-top: 10px;'>" . htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre du Responsable', 'UTF-8')) . "</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $html_participants_rows = '';
        $ordre = 1;
        $current_page_total_pdf = 0;
        $previous_page_total_pdf = 0;
        $items_on_page_pdf = 0;
        $max_items_per_page_pdf = 16; // Number of participant rows per PDF page

        foreach ($participants_data as $participant_index => $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';
            $montant = $participant['montant_a_payer'] ?? 0;

            // Add "REPORT" line at the beginning of a new page (if not the very first participant)
            if ($items_on_page_pdf === 0 && $participant_index > 0 && $previous_page_total_pdf > 0) {
                $html_participants_rows .= "
                    <tr class='total-row'>
                        <td colspan='3' class='text-right'><strong>REPORT :</strong></td>
                        <td class='text-right'><strong>" . number_format($previous_page_total_pdf, 0, ',', ' ') . " FCFA</strong></td>
                        <td colspan='2'></td>
                    </tr>";
            }
            
            $current_page_total_pdf += $montant;

            $html_participants_rows .= "
                <tr>
                    <td>" . $ordre . "</td>
                    <td class='text-left'>" . htmlspecialchars($nom_complet) . "</td>
                    <td>" . htmlspecialchars($participant['qualite']) . "</td>
                    <td class='text-right'>" . number_format($montant, 0, ',', ' ') . "</td>
                    <td>" . htmlspecialchars($banque) . "</td>
                    <td>" . htmlspecialchars($numero_compte) . "</td>
                </tr>";
            
            $items_on_page_pdf++;
            $ordre++; // Increment order for next row

            // Logic for page break and "A REPORTER"
            // Trigger a page break if max items per page is reached AND there are more participants to list
            if ($items_on_page_pdf >= $max_items_per_page_pdf && ($participant_index + 1) < count($participants_data)) {
                $html_participants_rows .= "
                    <tr class='total-row'>
                        <td colspan='3' class='text-right'><strong>À REPORTER :</strong></td>
                        <td class='text-right'><strong>" . number_format($current_page_total_pdf, 0, ',', ' ') . " FCFA</strong></td>
                        <td colspan='2'></td>
                    </tr>
                    </tbody>
                </table>
                <div class='page-break'></div>
                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOM ET PRÉNOMS</th>
                            <th>QUALITÉ</th>
                            <th>MONTANT (FCFA)</th>
                            <th>BANQUE</th>
                            <th>RIB</th>
                        </tr>
                    </thead>
                    <tbody>
                ";
                $previous_page_total_pdf = $current_page_total_pdf; // Current page total becomes the report for the next page
                $current_page_total_pdf = 0; // Reset total for the new page
                $items_on_page_pdf = 0; // Reset item count for the new page
            }
        }

        // C'est ici que la modification a été faite : de 'portrait' à 'landscape'
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
        error_log("Ordre de Virement Global PDF généré : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Ordre de Virement Global PDF : " . $e->getMessage());
    }

    // --- Génération Excel (avec PhpSpreadsheet) ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ordre Virement Global'); 

        // Styles de base - Cohérence avec le PDF
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10); // Set default font for Excel
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // En-tête gauche (Ministère...) - Centré dans la colonne A-C, tout en gras
        $header_left_lines = explode("\n", $header_left_content_raw); 
        if (empty($header_left_content_raw)) {
            $header_left_lines = ["RÉPUBLIQUE DU BÉNIN", "MINISTÈRE DE LA ************", "DIRECTION ********************", "SERVICE ***************"];
        } else {
            $header_left_lines = explode('@@@', str_replace('\n', '@@@', $header_left_content_raw));
        }

        $current_row_excel_header = 1;
        foreach ($header_left_lines as $line) {
            $sheet->setCellValue('A' . $current_row_excel_header, trim($line));
            $sheet->getStyle('A' . $current_row_excel_header)->getFont()->setBold(true)->setSize(10);
            $sheet->mergeCells('A' . $current_row_excel_header . ':C' . $current_row_excel_header);
            $sheet->getStyle('A' . $current_row_excel_header)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $current_row_excel_header++;
        }
        
        // Date "Cotonou, le..." en haut à droite, en gras
        $sheet->setCellValue('D1', htmlspecialchars($lieu_fixe_entete) . ', le ' . htmlspecialchars(mb_strtoupper($date_pour_entete_et_journee, 'UTF-8')));
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('D1:F1');

        // Titre "ORDRE DE VIREMENT GLOBAL" en haut à droite (centré dans la plage D-F), en gras
        $sheet->setCellValue('D2', 'ORDRE DE VIREMENT GLOBAL');
        $sheet->getStyle('D2')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->getStyle('D2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('D2:F2'); 

        // Subtitle directly below main title in Excel (centré dans la plage D-F), en gras
        $sheet->setCellValue('D3', 'DES INDEMNITÉS ET FRAIS D\'ENTRETIEN ACCORDÉS AUX MEMBRES DE LA COMMISSION CHARGÉE DE LA : ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->getStyle('D3')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('D3:F3');

        $row_excel = max($current_row_excel_header + 5, 8); // Ensure table starts sufficiently low

        // En-têtes du tableau
        $headerRow_excel = $row_excel;
        $sheet->setCellValue('A' . $headerRow_excel, 'N°');
        $sheet->setCellValue('B' . $headerRow_excel, 'NOM ET PRÉNOMS');
        $sheet->setCellValue('C' . $headerRow_excel, 'QUALITÉ');
        $sheet->setCellValue('D' . $headerRow_excel, 'MONTANT (FCFA)');
        $sheet->setCellValue('E' . $headerRow_excel, 'BANQUE');
        $sheet->setCellValue('F' . $headerRow_excel, 'RIB');

        // Styles des en-têtes
        $sheet->getStyle('A' . $headerRow_excel . ':F' . $headerRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow_excel . ':F' . $headerRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
        $sheet->getStyle('A' . $headerRow_excel . ':F' . $headerRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
        $sheet->getStyle('A' . $headerRow_excel . ':F' . $headerRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row_excel++;

        // Contenu du tableau avec logique de report pour Excel
        $ordre_excel = 1;
        $current_sheet_total_excel = 0;
        $previous_sheet_total_excel = 0;
        $items_on_sheet_excel = 0;
        $max_items_per_sheet_excel = 25; // Adjust this number for Excel sheet breaks

        foreach ($participants_data as $participant_index => $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';
            $montant = $participant['montant_a_payer'] ?? 0;

            // Add "REPORT" line at the beginning of a new logical "page" in Excel
            if ($items_on_sheet_excel === 0 && $participant_index > 0 && $previous_sheet_total_excel > 0) {
                $sheet->setCellValue('A' . $row_excel, 'REPORT :');
                $sheet->mergeCells('A' . $row_excel . ':C' . $row_excel);
                $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('D' . $row_excel, $previous_sheet_total_excel);
                $sheet->getStyle('D' . $row_excel)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                $row_excel++;
            }
            
            $current_sheet_total_excel += $montant;

            $sheet->setCellValue('A' . $row_excel, $ordre_excel++);
            $sheet->setCellValue('B' . $row_excel, htmlspecialchars($nom_complet));
            $sheet->setCellValue('C' . $row_excel, htmlspecialchars($participant['qualite']));
            $sheet->setCellValue('D' . $row_excel, $montant); 
            $sheet->setCellValue('E' . $row_excel, htmlspecialchars($banque));
            $sheet->setCellValue('F' . $row_excel, htmlspecialchars($numero_compte));

            // Appliquer les bordures et rendre tout bold
            $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
            $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); 
            $sheet->getStyle('D' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); 
            $sheet->getStyle('D' . $row_excel)->getNumberFormat()->setFormatCode('#,##0'); 
            $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getFont()->setBold(true); // Make all cell content bold

            $row_excel++;
            $items_on_sheet_excel++;

            // Logic for "À REPORTER" in Excel
            if ($items_on_sheet_excel >= $max_items_per_sheet_excel && ($participant_index + 1) < count($participants_data)) {
                $sheet->setCellValue('A' . $row_excel, 'À REPORTER :');
                $sheet->mergeCells('A' . $row_excel . ':C' . $row_excel);
                $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('D' . $row_excel, $current_sheet_total_excel);
                $sheet->getStyle('D' . $row_excel)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
                $sheet->getStyle('A' . $row_excel . ':F' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                
                $row_excel++; // Move to next row after "À REPORTER"
                $previous_sheet_total_excel = $current_sheet_total_excel; // Current total becomes the report for the next block
                $current_sheet_total_excel = 0; // Reset current total for the new block
                $items_on_sheet_excel = 0; // Reset item count for the new block

                // Add an empty row for visual separation, mimicking a page break in Excel
                $row_excel++; 
            }
        }

        // Ligne du total général - Déjà en gras
        $totalRow_excel = $row_excel;
        $sheet->setCellValue('A' . $totalRow_excel, 'TOTAL À VIRER (FCFA) :');
        $sheet->mergeCells('A' . $totalRow_excel . ':C' . $totalRow_excel); 
        $sheet->getStyle('A' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $totalRow_excel . ':F' . $totalRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow_excel . ':F' . $totalRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A' . $totalRow_excel . ':F' . $totalRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');

        $sheet->setCellValue('D' . $totalRow_excel, $total_general_amount); 
        $sheet->getStyle('D' . $totalRow_excel)->getNumberFormat()->setFormatCode('#,##0'); 
        $sheet->getStyle('D' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $row_excel++; 

        // Ajout de la somme en toutes lettres - Déjà en gras
        $sheet->setCellValue('A' . $row_excel, 'Arrêté le présent ordre de virement à la somme de ' . number_format($total_general_amount, 0, ',', ' ') . ' FCFA');
        $sheet->mergeCells('A' . $row_excel . ':F' . $row_excel);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, '(en toutes lettres : ' . htmlspecialchars(nombreEnLettres($total_general_amount)) . ' francs CFA)');
        $sheet->mergeCells('A' . $row_excel . ':F' . $row_excel);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row_excel++;

        // Définir des largeurs de colonne
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(25);

        // Bloc de signature - Déjà en gras
        $signature_start_row_excel = $row_excel + 3; 

        // Signature du Chef du Service Financier
        $sheet->setCellValue('B' . $signature_start_row_excel, 'LE C/GAP');
        $sheet->getStyle('B' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('B' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . $signature_start_row_excel . ':C' . $signature_start_row_excel); 

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('B' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('B' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 1) . ':C' . ($signature_start_row_excel + 1));


        $sheet->setCellValue('B' . ($signature_start_row_excel + 3), htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'Nom du Chef du Service Financier', 'UTF-8')));
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 3) . ':C' . ($signature_start_row_excel + 3));

        // Signature du Directeur
        $sheet->setCellValue('E' . $signature_start_row_excel, 'LE CMAP');
        $sheet->getStyle('E' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('E' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('E' . $signature_start_row_excel . ':F' . $signature_start_row_excel); 

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('E' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('E' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('E' . ($signature_start_row_excel + 1) . ':F' . ($signature_start_row_excel + 1));

        $sheet->setCellValue('E' . ($signature_start_row_excel + 3), htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre du Responsable', 'UTF-8')));
        $sheet->getStyle('E' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('E' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('E' . ($signature_start_row_excel + 3) . ':F' . ($signature_start_row_excel + 3));

        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $excel_path = $output_dir . '/' . $filename_prefix . '.xlsx';
        $writer->save($excel_path);
        error_log("Ordre de Virement Global Excel généré : " . $excel_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Ordre de Virement Global Excel : " . $e->getMessage());
    }

    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}

// Fonction de conversion de nombre en lettres (exemple, assurez-vous qu'elle existe dans db.php ou ailleurs)
// Cette fonction n'est pas fournie dans le code original, mais est nécessaire.
// Si vous ne l'avez pas, voici une version simple (peut nécessiter une bibliothèque plus robuste pour des cas complexes) :
if (!function_exists('nombreEnLettres')) {
    function nombreEnLettres(int $nombre): string {
        $formatter = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
        return $formatter->format($nombre);
    }
}

?>