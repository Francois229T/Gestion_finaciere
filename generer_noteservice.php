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
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * Génère la Note de Service sous forme de lettre administrative pour le PDF,
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
 * @param string $document_date_str Date du document au format 'jj/mm/AAAA'.
 * @return array Chemins des fichiers PDF et Excel générés.
 */
function generernoteservice(
    array $activity_data,
    array $participants_data,
    string $output_dir,
    string $header_left_content_raw = '',
    string $note_generatrice_n = '',
    string $reference_ref = '',
    string $document_date_str = ''
): array {
    // Données de l'activité pour l'en-tête et le titre
    $nom_activite = $activity_data['nom'] ?? 'Activité Inconnue';
    $date_debut_activite = $activity_data['periode_debut'] ?? 'N/A';
    $date_fin_activite = $activity_data['periode_fin'] ?? 'N/A';
    $lieu_activite = $activity_data['centre'] ?? 'N/A';
    
    // Déterminer la date du document (si fournie ou date actuelle)
    $date_document = !empty($document_date_str) ? htmlspecialchars($document_date_str) : date('d/m/Y');

    // Traiter les retours à la ligne standards (\n) et s'assurer que '@@@' est le délimiteur unique
    if (!empty($header_left_content_raw)) {
        $header_left_content_raw = str_replace(array("\r\n", "\r", "\n"), '@@@', $header_left_content_raw);
    }

    // Déterminer le contenu du bloc supérieur gauche
    $header_left_html = '';
    if (!empty($header_left_content_raw)) {
        $lines = explode('@@@', $header_left_content_raw); // Utilise '@@@' comme séparateur
        foreach ($lines as $line) {
            $header_left_html .= '<p><strong>' . htmlspecialchars($line) . '</strong></p>';
        }
    } else {
        // Contenu par défaut si non fourni
        $header_left_html = "
            <p><strong>RÉPUBLIQUE DU BÉNIN</strong></p>
            <p><strong>MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES</strong></p>
            <p><strong>DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE</strong></p>
            <p><strong>Direction des Affaires Administratives et Financières</strong></p>
            <p><strong>Service du Personnel et des Moyens</strong></p>
        ";
    }

    // Déterminer la note génératrice et la référence
    $note_generatrice_display = !empty($note_generatrice_n) ? htmlspecialchars($note_generatrice_n) : 'Note Génératrice N° [À COMPLÉTER]';
    $reference_display = !empty($reference_ref) ? htmlspecialchars($reference_ref) : 'Réf. [À COMPLÉTER]';

    // Assurez-vous que le nom de fichier est unique, par exemple basé sur l'activité
    $filename_prefix = "Note_de_service_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';

    // --- Génération PDF (avec Dompdf) ---
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Verdana');
        
        $dompdf = new Dompdf($options);

        $logo_path = realpath(__DIR__ . '/tresorpubbenin.png');
        $logo_src = '';
        if (file_exists($logo_path)) {
            $logo_src = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
        } else {
            error_log("Logo tresorpubbenin.png not found at: " . $logo_path);
        }

        // --- Début du HTML pour le PDF (Format Lettre Administrative avec Tableau) ---
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Note de Service</title>
            <style>
                body {
                    font-family: 'Verdana', sans-serif;
                    margin: 12.7mm; /* Marges égales de 1.27 cm (12.7mm) de tous les côtés */
                    font-size: 11pt;
                    color: #000;
                    line-height: 1.6;
                }

                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 5mm; /* Réduit la marge pour rapprocher les infos */
                }
                .header-table td {
                    vertical-align: top;
                    padding: 0;
                    border: none;
                }

                .header-left-block {
                    width: 60%;
                    text-align: center;
                    font-size: 9.5pt;
                    font-weight: bold;
                    font-family: 'Verdana', sans-serif;
                }
                .header-left-block p {
                    margin: 0;
                    line-height: 1.2;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                .header-left-block strong {
                    font-size: 10.5pt;
                    display: block;
                    margin-bottom: 1px;
                }

                .header-right-block {
                    width: 40%;
                    text-align: center;
                    font-size: 10pt;
                    padding-top: 5px;
                }
                .header-right-block img {
                    max-width: 100px;
                    height: auto;
                    margin-bottom: 5px;
                }
                .header-right-block .document-title {
                    font-size: 14pt;
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 5px 0 0 0;
                    line-height: 1.2;
                    color: #000;
                    text-transform: uppercase;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                .header-right-block .activity-name-subtitle {
                    font-size: 11pt;
                    font-weight: bold;
                    margin-top: 2px;
                    text-transform: uppercase;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                .header-right-block .place-date {
                    margin-top: 10px;
                    font-style: italic;
                    font-size: 9.5pt;
                }

                /* MODIFICATION: Assure l'alignement à gauche pour la section des détails de l'activité */
                .details-section { 
                    margin-top: 0mm; /* Supprime la marge supérieure */
                    margin-bottom: 3mm;
                    font-size: 11pt;
                    line-height: 1.4;
                    text-align: left; /* Assure l'alignement à gauche */
                }
                .details-section p {
                    margin: 0;
                }
                .details-section strong {
                    font-weight: bold;
                }

                .object-section {
                    margin-top: 5mm; 
                    margin-bottom: 5mm;
                    text-align: left;
                    font-size: 11pt;
                }
                .object-section strong {
                    font-weight: bold;
                    text-decoration: underline;
                }
                

                .participants-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 3mm;
                    margin-bottom: 0mm; 
                    table-layout: fixed;
                }
                .participants-table th,
                .participants-table td {
                    border: 1px solid #777;
                    padding: 8px 10px;
                    text-align: left;
                    vertical-align: top;
                    font-size: 9.5pt; 
                }
                .participants-table th {
                    background-color: #e0e0e0;
                    font-weight: bold;
                    color: #000;
                    text-transform: uppercase;
                    text-align: center;
                }
                .participants-table td {
                    font-weight: bold; 
                }
                .participants-table td:first-child {
                    text-align: center;
                    width: 5%; 
                }
                .participants-table td:nth-child(2) {
                    width: 35%; 
                }
                .participants-table td:nth-child(3) {
                    width: 27%; 
                }
                .participants-table td:nth-child(4) {
                    width: 15%; 
                }
                .participants-table td:last-child {
                    text-align: center;
                    width: 18%; 
                }
                thead { display: table-header-group; } /* Important for repeating headers on page breaks */


                .signature-section {
                    width: 100%;
                    margin-top: 5mm;
                }
                .signature-section td {
                    width: 100%; /* For single signature, takes full width */
                    vertical-align: top;
                    padding: 0;
                    border: none;
                    text-align: center; /* Center the single signature block */
                }
                .signature-section p {
                    margin: 2px 0;
                    line-height: 1.4;
                    font-size: 10.5pt;
                }
                .signature-section .title-signature {
                    font-weight: bold;
                    margin-bottom: 15mm;
                }
                .signature-section .name-title {
                    font-weight: bold;
                    text-decoration: underline;
                    text-transform: uppercase;
                    margin-top: 5mm;
                }
            </style>
        </head>
        <body>
            <table class='header-table'>
                <tr>
                    <td class='header-left-block'>" . $header_left_html . "</td>
                    <td class='header-right-block'>
                        " . ($logo_src ? "<img src='" . $logo_src . "' alt='Logo Trésor Public Bénin'><br>" : "") . "
                        <p class='place-date'><strong>À " . htmlspecialchars(mb_strtoupper($lieu_activite, 'UTF-8')) . ", le " . $date_document . "</strong></p>
                        <p class='document-title'>NOTE DE SERVICE</p>
                        <p class='activity-name-subtitle'>PORTANT LA CONSTITUTION DE LA COMMISSION DES MEMBRES <br>CHARGÉS DE L'ACTIVITÉ <span class='bold'>" . strtoupper(htmlspecialchars($nom_activite)) . "</span></p>
                    </td>
                </tr>
            </table>

            <div class='details-section'>
                <p><strong>Période : Du " . htmlspecialchars($date_debut_activite) . " au " . htmlspecialchars($date_fin_activite) . "</strong></p>
                <p><strong>Lieu : " . htmlspecialchars($lieu_activite) . "</strong></p>
            </div>
            
            <div class='object-section'>
                <p><strong>" . $note_generatrice_display . "</strong></p>
                <p><strong>" . $reference_display . "</strong></p>
            </div>
            
            <table class='participants-table'>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom et Prénoms</th>
                        <th>Qualité</th>
                        <th>Banque</th>
                        <th>Numéro de Compte</th>
                    </tr>
                </thead>
                <tbody>";
                
                $ordre = 1;
                foreach ($participants_data as $participant) {
                    $nom_complet = $participant['nom'] ?? '';
                    if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                        $nom_complet .= ' ' . $participant['prenom'];
                    }
                    $qualite = $participant['qualite'] ?? '';
                    $banque = $participant['banque'] ?? 'N/A';
                    $numero_compte = $participant['numero_compte'] ?? 'N/A';

                    $html .= "
                        <tr>
                            <td>" . $ordre++ . "</td>
                            <td>" . htmlspecialchars($nom_complet) . "</td>
                            <td>" . htmlspecialchars($qualite) . "</td>
                            <td>" . htmlspecialchars($banque) . "</td>
                            <td>" . htmlspecialchars($numero_compte) . "</td>
                        </tr>";
                }

                $html .= "
                </tbody>
            </table>

            <table class='signature-section'>
                <tr>
                    <td>
                        <p class='title-signature'>LE CMAP</p>
                        <p>(Signature)</p>
                        <p class='name-title'>" . htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'NOM ET TITRE DU PREMIER RESPONSABLE', 'UTF-8')) . "</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
        // --- Fin du HTML pour le PDF ---


        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); 
        $dompdf->render();

        // Ajout de la pagination au PDF
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont("Verdana", "normal");
        // Coordonnées ajustées pour le bas à droite en mode paysage (A4: 842x595 points)
        $canvas->page_text(770, 570, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0)); 

        $pdf_path = $output_dir . '/' . $filename_prefix . '.pdf';
        file_put_contents($pdf_path, $dompdf->output());
        error_log("Note de Service PDF générée : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la Note de Service PDF : " . $e->getMessage());
    }

    // --- Génération Excel ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Note de Service');

        // Styles par défaut
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11); // Conserver Calibri pour Excel
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // En-tête gauche (dynamique)
        $excel_header_row_start = 1;
        if (!empty($header_left_content_raw)) {
            $lines = explode('@@@', $header_left_content_raw);
            foreach ($lines as $line) {
                $sheet->setCellValue('A' . $excel_header_row_start, htmlspecialchars($line));
                $sheet->getStyle('A' . $excel_header_row_start)->getFont()->setBold(true);
                $sheet->mergeCells('A' . $excel_header_row_start . ':C' . $excel_header_row_start);
                $excel_header_row_start++;
            }
        } else {
            // Contenu par défaut si non fourni
            $sheet->setCellValue('A1', 'RÉPUBLIQUE DU BÉNIN');
            $sheet->setCellValue('A2', 'MINISTÈRE DE L\'ÉCONOMIE ET DES FINANCES');
            $sheet->setCellValue('A3', 'DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE');
            $sheet->setCellValue('A4', 'Direction des Affaires Administratives et Financières');
            $sheet->setCellValue('A5', 'Service du Personnel et des Moyens');
            foreach (range(1, 5) as $row_num) {
                $sheet->mergeCells('A' . $row_num . ':C' . $row_num);
                $sheet->getStyle('A' . $row_num)->getFont()->setBold(true);
            }
            $excel_header_row_start = 6; // Set to the row after the default header
        }

        // En-tête droit (lieu et date du document)
        $sheet->setCellValue('D1', htmlspecialchars(mb_strtoupper($lieu_activite, 'UTF-8')) . ', LE ' . $date_document);
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('D1:E1');

        // Titre central
        $row_excel = max($excel_header_row_start, 8); // Ensure title starts below header left block
        $sheet->setCellValue('A' . $row_excel, 'NOTE DE SERVICE');
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true)->setSize(14)->setUnderline(Font::UNDERLINE_SINGLE);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'PORTANT LA CONSTITUTION DE LA COMMISSION DES MEMBRES CHARGÉS DE L\'ACTIVITÉ ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel+=2; // Two empty lines after the main title

        // MODIFICATION: Détails de l'activité (Période et Lieu) AVANT la note génératrice et la référence
        $sheet->setCellValue('A' . $row_excel, 'Période : Du ' . htmlspecialchars($date_debut_activite) . ' au ' . htmlspecialchars($date_fin_activite));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'Lieu : ' . htmlspecialchars($lieu_activite));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel+=2; // Space before next block

        // Note génératrice et Référence (maintenant après Lieu/Période)
        $sheet->setCellValue('A' . $row_excel, $note_generatrice_display);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, $reference_display);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel+=2; // Space before table

        // En-têtes du tableau
        $headerRow_excel = $row_excel;
        $sheet->setCellValue('A' . $headerRow_excel, 'N°');
        $sheet->setCellValue('B' . $headerRow_excel, 'Nom et Prénoms');
        $sheet->setCellValue('C' . $headerRow_excel, 'Qualité');
        $sheet->setCellValue('D' . $headerRow_excel, 'Banque');
        $sheet->setCellValue('E' . $headerRow_excel, 'Numéro de Compte');

        // Styles des en-têtes
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9E9E9');
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF999999');
        $sheet->getStyle('A' . $headerRow_excel . ':E' . $headerRow_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row_excel++;

        // Contenu du tableau
        $ordre = 1;
        foreach ($participants_data as $participant) {
            $nom_complet = $participant['nom'] ?? '';
            if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                $nom_complet .= ' ' . $participant['prenom'];
            }
            $qualite = $participant['qualite'] ?? '';
            $banque = $participant['banque'] ?? 'N/A';
            $numero_compte = $participant['numero_compte'] ?? 'N/A';

            $sheet->setCellValue('A' . $row_excel, $ordre++);
            $sheet->setCellValue('B' . $row_excel, htmlspecialchars($nom_complet));
            $sheet->setCellValue('C' . $row_excel, htmlspecialchars($qualite));
            $sheet->setCellValue('D' . $row_excel, htmlspecialchars($banque));
            $sheet->setCellValue('E' . $row_excel, htmlspecialchars($numero_compte));

            // Appliquer les bordures aux cellules de données
            $sheet->getStyle('A' . $row_excel . ':E' . $row_excel)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF999999');
            $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrer le N°
            $sheet->getStyle('B' . $row_excel . ':D' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Aligner les infos à gauche
            $sheet->getStyle('E' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrer le numéro de compte
            
            // Met les données du tableau en gras pour Excel
            $sheet->getStyle('A' . $row_excel . ':E' . $row_excel)->getFont()->setBold(true);

            $row_excel++;
        }

        // Définir des largeurs de colonne fixes
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(32); 
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(28); 


        // Déterminer la ligne de départ pour les signatures
        $signature_start_row = $row_excel + 3; 

        // Single Signature Block for Note de Service (right-aligned, similar to Collective Attestation right signature)
        $sheet->setCellValue('D' . $signature_start_row, 'LE CMAP');
        $sheet->getStyle('D' . $signature_start_row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $signature_start_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . $signature_start_row . ':E' . $signature_start_row); 

        $sheet->setCellValue('D' . ($signature_start_row + 1), '(Signature)');
        $sheet->getStyle('D' . ($signature_start_row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . ($signature_start_row + 1) . ':E' . ($signature_start_row + 1));

        $sheet->setCellValue('D' . ($signature_start_row + 4), htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'Nom et Titre Responsable', 'UTF-8')));
        $sheet->getStyle('D' . ($signature_start_row + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('D' . ($signature_start_row + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->mergeCells('D' . ($signature_start_row + 4) . ':E' . ($signature_start_row + 4));

        $row_excel = $signature_start_row + 4;


        $writer = new Xlsx($spreadsheet);
        $excel_path = $output_dir . '/' . $filename_prefix . '.xlsx';
        $writer->save($excel_path);
        error_log("Note de Service Excel générée : " . $excel_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la Note de Service Excel : " . $e->getMessage());
    }
    
    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}


// --- Logique principale ---

if (isset($_GET['activite_id'])) {
    global $mysqlClient; // Assurez-vous que $mysqlClient est accessible ici

    $activite_id = (int)$_GET['activite_id'];
    $document_type = $_GET['type'] ?? 'pdf'; // 'pdf' ou 'excel'

    // Récupérer les nouveaux paramètres de l'URL
    // Utilisez '@@@' comme séparateur de ligne dans l'URL pour header_left_content
    $header_left_content = $_GET['header_left_content'] ?? '';
    $note_generatrice_n = $_GET['note_generatrice_n'] ?? '';
    $reference_ref = $_GET['reference_ref'] ?? '';
    $document_date_str = $_GET['document_date'] ?? ''; // Format attendu: jj/mm/AAAA

    try {
        // 1. Récupérer les données de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if (!$activity_data) {
            die("Activité non trouvée.");
        }

        // Assigner les valeurs par défaut si non définies dans la base de données ou les paramètres GET
        // Ces valeurs peuvent être surchargées par des données spécifiques de l'activité si elles existent
        $activity_data['organisateur_titre'] = $activity_data['organisateur_titre'] ?? 'LE DIRECTEUR GÉNÉRAL DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE';
        $activity_data['responsable_titre'] = $activity_data['responsable_titre'] ?? 'LE CHEF DU CENTRE DU MATÉRIEL ET DES APPLICATIONS DU PERSONNEL';


        // 2. Récupérer les participants liés à cette activité
        $sql_participants = "
            SELECT
                part.type_participant,
                COALESCE(pp.nom, pm.denomination) AS nom,
                pp.prenom,
                part.titre AS qualite,
                cb.banque,
                cb.numero_compte
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
        $output_dir = __DIR__ . '/temp_documents'; // Créez ce répertoire à la racine de votre projet
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true); // Crée le répertoire s'il n'existe pas
        }

        // 4. Générer le document en passant les nouveaux paramètres
        $generated_files = generernoteservice(
            $activity_data,
            $participants_data,
            $output_dir,
            $header_left_content,
            $note_generatrice_n,
            $reference_ref,
            $document_date_str
        );

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
            // Optionally, delete the file after sending it
            // unlink($file_path); 
            exit;
        } else {
            die("Erreur : Le fichier à télécharger n'existe pas.");
        }

    } catch (PDOException $e) {
        error_log("Erreur de base de données lors de la récupération des données pour la note de service : " . $e->getMessage());
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération/téléchargement de la note de service : " . $e->getMessage());
        die("Une erreur inattendue est survenue.");
    }
} else {
    die("ID d'activité manquant.");
}

?>