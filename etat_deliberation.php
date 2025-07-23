<?php

require_once 'vendor/autoload.php';
require_once 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Génère l'État de Paiement sous forme de lettre administrative pour le PDF,
 * et un tableau pour l'Excel.
 * Accepte les données de l'activité et un tableau de tous les participants.
 * Retourne le chemin du fichier généré (PDF et XLSX).
 *
 * @param array $activity_data Données de l'activité.
 * @param array $participants_data Tableau des participants.
 * @param string $output_dir Répertoire de sortie pour les documents générés.
 * @param string $header_left_content_raw Contenu brut pour l'en-tête gauche, lignes séparées par '@@@' ou '\n'.
 * @param string $note_generatrice_n Numéro de la note génératrice.
 * @param string $reference_ref Référence du document.
 * @param string $document_date_str Date du document au format 'jj/mm/AAAA' ou 'AAAA-MM-JJ'.
 * @return array Chemins des fichiers PDF et Excel générés.
 */
function genererEtatPaiement(array $activity_data, array $participants_data, string $output_dir, string $header_left_content_raw = '', string $note_generatrice_n = '', string $reference_ref = '', string $document_date_str = ''): array {
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
    
    // Lieu de déroulement de l'activité (utilisé pour "Lieu : ")
    $lieu_activite = $activity_data['lieu'] ?? "Lieu Inconnu"; 
    // Lieu pour la ligne "Cotonou, le..." (fixe selon la demande précédente)
    $lieu_fixe_entete = "Cotonou"; 

    $filename_prefix = "Etat_paiement_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';
    $total_general_amount = 0;

    foreach ($participants_data as $participant) {
        $total_general_amount += ($participant['montant_a_payer'] ?? 0);
    }

    // Traitement du contenu de l'en-tête gauche (Ministère...)
    if (empty($header_left_content_raw)) {
        $header_left_content = "<strong>RÉPUBLIQUE DU BÉNIN</strong><br><strong>MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES</strong><br><strong>DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE</strong><br><strong>Direction des Affaires Administratives et Financières</strong><br><strong>Service du Personnel et des Moyens</strong>";
    } else {
        // Ensure all lines in header_left_content are bold and use Arial
        $header_left_content = str_replace('@@@', '<br>', htmlspecialchars($header_left_content_raw));
        $header_left_content = nl2br($header_left_content);
        $header_left_content = "<span style='font-family: Arial; font-weight: bold;'>" . $header_left_content . "</span>";
    }
    
    // Déterminer la note génératrice et la référence à afficher, avec les valeurs par défaut
    $note_generatrice_display = !empty($note_generatrice_n) ? htmlspecialchars($note_generatrice_n) : 'Note génératrice N';
    $reference_display = !empty($reference_ref) ? htmlspecialchars($reference_ref) : 'Référence REF';

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
                    
                    .ref-note-left {
                        text-align: left; /* Aligned to the left */
                        margin-top: 10mm; 
                        margin-bottom: 5mm; 
                        font-weight: normal; /* These should not be bold unless explicitly asked for. Revert if needed. */
                    }
                    .ref-note-left p {
                        margin: 1mm 0;
                        font-size: 9pt;
                    }

                    .place-day-right {
                        text-align: right; /* Aligned to the right */
                        margin-top: 10mm; /* Space after the main title block */
                        margin-bottom: 5mm;
                    }
                    .place-day-right p {
                        margin: 1mm 0;
                        font-size: 9pt;
                        font-weight: bold; /* Keep bold for Lieu/Journée */
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
                    th:nth-child(1), td:nth-child(1) { width: 4%; } /* N° */
                    th:nth-child(2), td:nth-child(2) { width: 22%; } /* NOM ET PRENOMS */
                    th:nth-child(3), td:nth-child(3) { width: 12%; } /* QUALITÉ */
                    th:nth-child(4), td:nth-child(4) { width: 10%; } /* Taux par Jour */
                    th:nth-child(5), td:nth-child(5) { width: 10%; } /* Nombre de Jours */
                    th:nth-child(6), td:nth-child(6) { width: 12%; } /* MONTANT */
                    th:nth-child(7), td:nth-child(7) { width: 15%; } /* BANQUE */
                    th:nth-child(8), td:nth-child(8) { width: 15%; } /* RIB */


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
                            <h1>ETAT DE PAIEMENT</h1>
                            <p class='sub-title'>DES INDEMNITÉS ET FRAIS D'ENTRETIEN ACCORDÉS AUX MEMBRES DE LA COMMISSION CHARGÉE DE LA <span class='bold'>" . htmlspecialchars(mb_strtoupper($nom_activite, 'UTF-8')) . "</span></p>
                        </div>
                    </div>
                </div>
                <div class='clear-float'></div>

                <div class='header-container' style='margin-top: 10mm;'>
                    <div class='header-left-cell' style='text-align:left; font-weight:normal;'> <div class='ref-note-left'>
                            <p><strong>NOTE GÉNÉRATRICE N°:</strong> " . $note_generatrice_display . "</p>
                            <p><strong>RÉFÉRENCE:</strong> " . $reference_display . "</p>
                        </div>
                    </div>
                    <div class='header-right-cell' style='text-align:right;'>
                        <div class='place-day-right'>
                            <p>Lieu : " . htmlspecialchars($lieu_activite) . "</p>
                            <p>Journée : " . htmlspecialchars($date_pour_entete_et_journee) . "</p>
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
                            <th>Taux par Jour</th>
                            <th>Nombre de Jours</th>
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
                    <p><b>Arrêté le présent état de paiement à la somme de " . number_format($total_general_amount, 0, ',', ' ') . " FCFA</b></p>
                    <p><b>(en toutes lettres : " . htmlspecialchars(nombreEnLettres($total_general_amount)) . " francs CFA)</b></p>
                </div>

                <table class='signature-table'>
                    <tr>
                        <td class='signature-cell left-align'>
                            <p style='margin-top: 20px;'><b>LE CHEF DU SERVICE</b></p>
                            <div class='signature-line'></div> 
                            <p class='bold-underline' style='margin-top: 10px;'>" . htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'Nom du Chef du Chef du Service Financier', 'UTF-8')) . "</p>
                        </td>
                        <td class='signature-cell right-align'>
                            <p style='margin-top: 20px;'><b>LE DIRECTEUR</b></p>
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
        $max_items_per_page_pdf = 14; // Number of participant rows per PDF page

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
                        <td colspan='5' class='text-right'><strong>REPORT :</strong></td>
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
                    <td>" . number_format($participant['taux_journalier_copie'], 0, ',', ' ') . "</td>
                    <td>" . number_format($participant['nb_jours_copies'], 0, ',', ' ') . "</td>
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
                        <td colspan='5' class='text-right'><strong>À REPORTER :</strong></td>
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
                            <th>Taux par Jour</th>
                            <th>Nombre de Jours</th>
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

        $dompdf->loadHtml($html_header_and_table_start . $html_participants_rows . $html_table_end_and_footer);
        $dompdf->setPaper('A4', 'landscape'); // Landscape for better table fit
        $dompdf->render();

        // Add page numbering
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont("Arial", "normal");
        $canvas->page_text(700, 560, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0, 0, 0)); // Adjusted position for landscape

        $pdf_path = $output_dir . '/' . $filename_prefix . '.pdf';
        file_put_contents($pdf_path, $dompdf->output());
        error_log("État de Paiement PDF généré : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'État de Paiement PDF : " . $e->getMessage());
    }

    // --- Génération Excel (avec PhpSpreadsheet) ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('État de Paiement'); 

        // Styles de base - Cohérence avec le PDF
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10); // Set default font for Excel
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        // En-tête gauche (Ministère...) - Centré dans la colonne A-C, tout en gras
        $header_left_lines = explode("\n", $header_left_content_raw); 
        if (empty($header_left_content_raw)) {
            $header_left_lines = ["RÉPUBLIQUE DU BÉNIN", "MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES", "DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE", "Direction des Affaires Administratives et Financières", "Service du Personnel et des Moyens"];
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
        $sheet->setCellValue('E1', htmlspecialchars($lieu_fixe_entete) . ', le ' . htmlspecialchars(mb_strtoupper($date_pour_entete_et_journee, 'UTF-8')));
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('E1:H1');

        // Titre "ETAT DE PAIEMENT" en haut à droite (centré dans la plage E-H), en gras
        $sheet->setCellValue('E2', 'ETAT DE PAIEMENT');
        $sheet->getStyle('E2')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->getStyle('E2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('E2:H2'); 

        // Subtitle directly below main title in Excel (centré dans la plage E-H), en gras
        $sheet->setCellValue('E3', 'DES INDEMNITES ET FRAIS D\'ENTRETIEN ACCORDES AUX MEMBRES DE LA COMMISSION CHARGEE DE LA : ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->getStyle('E3')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('E3:H3');


        // Note Génératrice et Référence à gauche (alignées à gauche, pas en gras)
        $note_generatrice_display_excel = !empty($note_generatrice_n) ? htmlspecialchars($note_generatrice_n) : 'Note génératrice N';
        $reference_display_excel = !empty($reference_ref) ? htmlspecialchars($reference_ref) : 'Référence REF';

        $sheet->setCellValue('A' . ($current_row_excel_header + 2), 'NOTE GÉNÉRATRICE N°: ' . $note_generatrice_display_excel);
        $sheet->getStyle('A' . ($current_row_excel_header + 2))->getFont()->setBold(false)->setSize(9); // Not bold
        $sheet->mergeCells('A' . ($current_row_excel_header + 2) . ':C' . ($current_row_excel_header + 2));
        $sheet->getStyle('A' . ($current_row_excel_header + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('A' . ($current_row_excel_header + 3), 'RÉFÉRENCE: ' . $reference_display_excel);
        $sheet->getStyle('A' . ($current_row_excel_header + 3))->getFont()->setBold(false)->setSize(9); // Not bold
        $sheet->mergeCells('A' . ($current_row_excel_header + 3) . ':C' . ($current_row_excel_header + 3));
        $sheet->getStyle('A' . ($current_row_excel_header + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);


        // Lieu et Journée à droite (alignés à droite, en gras)
        $sheet->setCellValue('E' . ($current_row_excel_header + 2), 'Lieu : ' . htmlspecialchars($lieu_activite));
        $sheet->getStyle('E' . ($current_row_excel_header + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . ($current_row_excel_header + 2))->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('E' . ($current_row_excel_header + 2) . ':H' . ($current_row_excel_header + 2));

        $sheet->setCellValue('E' . ($current_row_excel_header + 3), 'Journée : ' . htmlspecialchars(mb_strtoupper($date_pour_entete_et_journee, 'UTF-8')));
        $sheet->getStyle('E' . ($current_row_excel_header + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . ($current_row_excel_header + 3))->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('E' . ($current_row_excel_header + 3) . ':H' . ($current_row_excel_header + 3));

        $row_excel = max($current_row_excel_header + 5, 8); // Ensure table starts sufficiently low

        // En-têtes du tableau
        $headerRow_excel = $row_excel;
        $sheet->setCellValue('A' . $headerRow_excel, 'N°');
        $sheet->setCellValue('B' . $headerRow_excel, 'NOM ET PRÉNOMS');
        $sheet->setCellValue('C' . $headerRow_excel, 'QUALITÉ');
        $sheet->setCellValue('D' . $headerRow_excel, 'Taux par Jour');
        $sheet->setCellValue('E' . $headerRow_excel, 'Nombre de Jours');
        $sheet->setCellValue('F' . $headerRow_excel, 'MONTANT (FCFA)');
        $sheet->setCellValue('G' . $headerRow_excel, 'BANQUE');
        $sheet->setCellValue('H' . $headerRow_excel, 'RIB');

        // Styles des en-têtes
        $sheet->getStyle('A' . $headerRow_excel . ':H' . $headerRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow_excel . ':H' . $headerRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
        $sheet->getStyle('A' . $headerRow_excel . ':H' . $headerRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
        $sheet->getStyle('A' . $headerRow_excel . ':H' . $headerRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row_excel++;

        // Contenu du tableau avec logique de report pour Excel
        $ordre_excel = 1;
        $current_sheet_total_excel = 0;
        $previous_sheet_total_excel = 0;
        $items_on_sheet_excel = 0;
        $max_items_per_sheet_excel = 20; // Adjust this number for Excel sheet breaks

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
                $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
                $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('F' . $row_excel, $previous_sheet_total_excel);
                $sheet->getStyle('F' . $row_excel)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                $row_excel++;
            }
            
            $current_sheet_total_excel += $montant;

            $sheet->setCellValue('A' . $row_excel, $ordre_excel++);
            $sheet->setCellValue('B' . $row_excel, htmlspecialchars($nom_complet));
            $sheet->setCellValue('C' . $row_excel, htmlspecialchars($participant['qualite']));
            $sheet->setCellValue('D' . $row_excel, $participant['taux_journalier_copie']);
            $sheet->setCellValue('E' . $row_excel, $participant['nb_jours_copies']);
            $sheet->setCellValue('F' . $row_excel, $montant); 
            $sheet->setCellValue('G' . $row_excel, htmlspecialchars($banque));
            $sheet->setCellValue('H' . $row_excel, htmlspecialchars($numero_compte));

            // Appliquer les bordures et rendre tout bold
            $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
            $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); 
            $sheet->getStyle('F' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); 
            $sheet->getStyle('F' . $row_excel)->getNumberFormat()->setFormatCode('#,##0'); 
            $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getFont()->setBold(true); // Make all cell content bold

            $row_excel++;
            $items_on_sheet_excel++;

            // Logic for "À REPORTER" in Excel
            if ($items_on_sheet_excel >= $max_items_per_sheet_excel && ($participant_index + 1) < count($participants_data)) {
                $sheet->setCellValue('A' . $row_excel, 'À REPORTER :');
                $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
                $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('F' . $row_excel, $current_sheet_total_excel);
                $sheet->getStyle('F' . $row_excel)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
                $sheet->getStyle('A' . $row_excel . ':H' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                
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
        $sheet->mergeCells('A' . $totalRow_excel . ':E' . $totalRow_excel); 
        $sheet->getStyle('A' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $totalRow_excel . ':H' . $totalRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow_excel . ':H' . $totalRow_excel)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A' . $totalRow_excel . ':H' . $totalRow_excel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');

        $sheet->setCellValue('F' . $totalRow_excel, $total_general_amount); 
        $sheet->getStyle('F' . $totalRow_excel)->getNumberFormat()->setFormatCode('#,##0'); 
        $sheet->getStyle('F' . $totalRow_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $row_excel++; 

        // Ajout de la somme en toutes lettres - Déjà en gras
        $sheet->setCellValue('A' . $row_excel, 'Arrêté le présent état de paiement à la somme de ' . number_format($total_general_amount, 0, ',', ' ') . ' FCFA');
        $sheet->mergeCells('A' . $row_excel . ':H' . $row_excel);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, '(en toutes lettres : ' . htmlspecialchars(nombreEnLettres($total_general_amount)) . ' francs CFA)');
        $sheet->mergeCells('A' . $row_excel . ':H' . $row_excel);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row_excel++;

        // Définir des largeurs de colonne
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(25);

        // Bloc de signature - Déjà en gras
        $signature_start_row_excel = $row_excel + 3; 

        // Signature du Chef du Service Financier
        $sheet->setCellValue('B' . $signature_start_row_excel, 'LE CHEF DU SERVICE');
        $sheet->getStyle('B' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('B' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . $signature_start_row_excel . ':D' . $signature_start_row_excel); 

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('B' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('B' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 1) . ':D' . ($signature_start_row_excel + 1));


        $sheet->setCellValue('B' . ($signature_start_row_excel + 3), htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'Nom du Chef du Service Financier', 'UTF-8')));
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('B' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('B' . ($signature_start_row_excel + 3) . ':D' . ($signature_start_row_excel + 3));

        // Signature du Directeur
        $sheet->setCellValue('F' . $signature_start_row_excel, 'LE DIRECTEUR');
        $sheet->getStyle('F' . $signature_start_row_excel)->getFont()->setBold(true);
        $sheet->getStyle('F' . $signature_start_row_excel)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('F' . $signature_start_row_excel . ':H' . $signature_start_row_excel); 

        // Ajouter une ligne vide pour l'espace de signature
        $sheet->setCellValue('F' . ($signature_start_row_excel + 1), '');
        $sheet->getStyle('F' . ($signature_start_row_excel + 1))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells('F' . ($signature_start_row_excel + 1) . ':H' . ($signature_start_row_excel + 1));

        $sheet->setCellValue('F' . ($signature_start_row_excel + 3), htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre du Responsable', 'UTF-8')));
        $sheet->getStyle('F' . ($signature_start_row_excel + 3))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('F' . ($signature_start_row_excel + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('F' . ($signature_start_row_excel + 3) . ':H' . ($signature_start_row_excel + 3));

        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $excel_path = $output_dir . '/' . $filename_prefix . '.xlsx';
        $writer->save($excel_path);
        error_log("État de Paiement Excel généré : " . $excel_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'État de Paiement Excel : " . $e->getMessage());
    }
    
    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}

// Fonction pour convertir un nombre en lettres (exemple simplifié)
function nombreEnLettres($nombre) {
    // Cette classe nécessite l'extension Intl de PHP.
    // Si elle n'est pas activée, vous devrez fournir une implémentation manuelle ou une autre bibliothèque.
    $formatter = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
    return $formatter->format($nombre);
}


// --- Logique principale de generer_etat_paiement.php ---

if (isset($_GET['activite_id'])) {
    global $mysqlClient; 

    $activite_id = (int)$_GET['activite_id'];
    $document_type = $_GET['type'] ?? 'pdf'; 

    // Récupération des paramètres facultatifs de l'URL
    $header_left_content_raw = $_GET['header_left_content'] ?? '';
    $note_generatrice_n = $_GET['note_generatrice_n'] ?? '';
    $reference_ref = $_GET['reference_ref'] ?? '';
    $document_date_str = $_GET['document_date'] ?? '22/07/2025'; // Default for the HTML template

    try {

        // --- FIN DU BLOC DE GENERATION DE DONNEES FACTICES ---


        // SI VOUS UTILISEZ LA BASE DE DONNÉES, DECOMMENTEZ ET UTILISEZ LE CODE CI-DESSOUS :
       
        // 1. Récupérer les données de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if (!$activity_data) {
            die("Activité non trouvée.");
        }

        // Apply URL parameters if provided, otherwise use DB or defaults
        $activity_data['reference_document'] = !empty($reference_ref) ? $reference_ref : ($activity_data['reference_document'] ?? 'N°____/MEF/DGTCP/DAAF/SPM');
        $activity_data['note_generatrice'] = !empty($note_generatrice_n) ? $note_generatrice_n : ($activity_data['note_generatrice'] ?? 'Note Génératrice par défaut');
        $activity_data['responsable_titre'] = htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'LE DIRECTEUR GÉNÉRAL DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE', 'UTF-8'));
        $activity_data['financier'] = htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'LE CHEF SERVICE FINANCIER', 'UTF-8'));
        // Ensure 'lieu' is set, possibly from DB or a default
        $activity_data['lieu'] = $activity_data['lieu'] ?? 'Cotonou'; 


        // 2. Récupérer les participants liés à cette activité avec le montant à payer
        $sql_participants = "
            SELECT
                part.type_participant,
                COALESCE(pp.nom, pm.denomination) AS nom,
                pp.prenom,
                part.titre AS qualite,
                part.nb_jours_copies,
                part.taux_journalier_copie,
                part.nb_jours_deplacement,
                part.frais_deplacement,
                part.forfait_participant,
                cb.banque,
                cb.numero_compte,
                (part.nb_jours_copies * part.taux_journalier_copie + part.nb_jours_deplacement * part.frais_deplacement + part.forfait_participant) AS montant_a_payer
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
                banque ASC, nom ASC, prenom ASC;
        ";

        $stmt_participants = $mysqlClient->prepare($sql_participants);
        $stmt_participants->execute([':activite_id' => $activite_id]);
        $participants_data = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);

        if (empty($participants_data)) {
            die("Aucun participant trouvé pour cette activité.");
        }
      

        // Spécifier le répertoire de sortie
        $output_directory = __DIR__ . '/generated_documents';
        if (!is_dir($output_directory)) {
            mkdir($output_directory, 0777, true);
        }

        // Appeler la fonction générerEtatPaiement
        $generated_files = genererEtatPaiement(
            $activity_data,
            $participants_data,
            $output_directory,
            rawurldecode($header_left_content_raw), // Decode for function use
            rawurldecode($note_generatrice_n),    // Decode for function use
            rawurldecode($reference_ref),         // Decode for function use
            $document_date_str
        );

        // Proposer le téléchargement du PDF ou Excel selon le type demandé
        if ($document_type === 'pdf' && file_exists($generated_files['pdf'])) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($generated_files['pdf']) . '"');
            readfile($generated_files['pdf']);
            unlink($generated_files['pdf']); // Supprimer après téléchargement
            if (file_exists($generated_files['excel'])) {
                unlink($generated_files['excel']); // Supprimer aussi l'Excel si le PDF est demandé
            }
        } elseif ($document_type === 'excel' && file_exists($generated_files['excel'])) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($generated_files['excel']) . '"');
            readfile($generated_files['excel']);
            unlink($generated_files['excel']); // Supprimer après téléchargement
            if (file_exists($generated_files['pdf'])) {
                unlink($generated_files['pdf']); // Supprimer aussi le PDF si l'Excel est demandé
            }
        } else {
            die("Fichier non trouvé ou type de document invalide.");
        }
        
    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de la récupération des données pour l'état de paiement : " . $e->getMessage());
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération de l'état de paiement : " . $e->getMessage());
        die("Une erreur inattendue est survenue: " . $e->getMessage());
    }
} else {
    die("ID d'activité manquant. Veuillez fournir un 'activite_id' dans l'URL.");
}

?>