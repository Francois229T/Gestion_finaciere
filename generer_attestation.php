<?php

// Assurez-vous que le chemin vers votre autoloader Composer est correct
require_once 'vendor/autoload.php';
// Assurez-vous que le chemin vers votre fichier de connexion à la base de données est correct
require_once 'db.php'; // Ce fichier doit contenir la connexion $mysqlClient

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * Génère l'Attestation Collective de Travail sous forme de lettre administrative pour le PDF,
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
function genererAttestationCollective(
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
            // Chaque ligne est rendue en gras et prendra la police du corps du document (Verdana)
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
    $note_generatrice_display = !empty($note_generatrice_n) ? htmlspecialchars($note_generatrice_n) : 'Note génératrice N';
    $reference_display = !empty($reference_ref) ? htmlspecialchars($reference_ref) : 'Référence REF';

    // Assurez-vous que le nom de fichier est unique, par exemple basé sur l'activité
    $filename_prefix = "Attestation_Collective_" . preg_replace('/[^a-zA-Z0-9_]/', '', $nom_activite) . "_" . ($activity_data['id'] ?? 'sans_id');

    $pdf_path = '';
    $excel_path = '';

    // --- Génération PDF (avec Dompdf) ---
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif'); 
        
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
            <title>Attestation Collective de Travail</title>
            <style>
                body {
                    font-family: 'Verdana', sans-serif; /* Conserver Verdana pour une cohérence */
                    margin: 12.7mm; /* Marges égales de 1.27 cm (12.7mm) de tous les côtés */
                    font-size: 11pt;
                    color: #000; /* Utilise le noir pur */
                    line-height: 1.6;
                }

                /* Conteneur principal pour l'en-tête */
                .header-main-container {
                    width: 100%;
                    margin-bottom: 10mm;
                }

                /* Tableau pour les blocs gauche et droit de l'en-tête */
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10mm; /* Espace sous l'en-tête principal */
                }
                .header-table td {
                    vertical-align: top;
                    padding: 0;
                    border: none;
                }

                .header-left-block {
                    width: 60%; /* Plus de place pour les infos du ministère */
                    text-align: center; /* Centré */
                    font-size: 9.5pt;
                    /* Ajout du gras par défaut et alignement sur la police du body */
                    font-weight: bold; 
                    font-family: 'Verdana', sans-serif; 
                }
                .header-left-block p {
                    margin: 0;
                    line-height: 1.2;
                    word-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                    overflow-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                }
                /* Le strong à l'intérieur de p s'il est utilisé restera fort */
                .header-left-block strong {
                    font-size: 10.5pt; /* Peut être ajusté si l'on veut certaines lignes plus grandes */
                    display: block;
                    margin-bottom: 1px;
                }

                .header-right-block {
                    width: 40%;
                    text-align: center; /* Centré */
                    font-size: 10pt;
                    padding-top: 5px; /* Pour un meilleur alignement visuel avec la gauche */
                }
                .header-right-block img {
                    max-width: 100px; /* Taille du logo */
                    height: auto;
                    margin-bottom: 5px;
                }
                .header-right-block .attestation-title {
                    font-size: 14pt; /* Taille agréable pour le titre */
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 5px 0 0 0; /* Ajuster les marges */
                    line-height: 1.2;
                    color: #000;
                    text-transform: uppercase;
                    word-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                    overflow-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                }
                .header-right-block .activity-name-title {
                    font-size: 11pt;
                    font-weight: bold;
                    margin-top: 2px;
                    text-transform: uppercase;
                    word-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                    overflow-wrap: break-word; /* Assure le passage à la ligne pour les longs mots */
                }
                .header-right-block .header-detail { /* Nouveau style pour Période et Lieu */
                    margin-top: 5px; /* Espace entre les détails et le titre de l'activité */
                    margin-bottom: 2px;
                    font-size: 9.5pt;
                    line-height: 1.2;
                }
                .header-right-block .place-date {
                    margin-top: 10px; /* Espace au-dessus de la date/lieu */
                    font-style: italic; /* Garde l'italique, ajoute le gras via <strong> */
                    font-size: 9.5pt;
                }

                /* Objet de la lettre */
                .object-section {
                    margin-top: 5mm; 
                    margin-bottom: 5mm; /* Réduit pour rapprocher du tableau */
                    text-align: left;
                    font-size: 11pt;
                }
                .object-section strong {
                    font-weight: bold;
                    text-decoration: underline;
                }

                /* Corps du texte - Le tableau */
                .participants-table {
                    width: 100%; /* Le tableau prendra toute la largeur disponible */
                    border-collapse: collapse;
                    margin-top: 3mm; /* Réduit pour rapprocher du tableau */
                    margin-bottom: 0mm; 
                    table-layout: fixed; /* Aide à la gestion des largeurs de colonnes */
                }
                .participants-table th,
                .participants-table td {
                    border: 1px solid #777; /* Bordures plus visibles */
                    padding: 8px 10px;
                    text-align: left;
                    vertical-align: top;
                    font-size: 9.5pt; 
                }
                .participants-table th {
                    background-color: #e0e0e0; /* Fond légèrement plus foncé pour les en-têtes */
                    font-weight: bold;
                    color: #000;
                    text-transform: uppercase;
                    text-align: center; /* Centrer les titres de colonnes */
                }
                .participants-table td { /* Met les données du tableau en gras */
                    font-weight: bold; 
                }
                .participants-table td:first-child { /* Colonne N° */
                    text-align: center;
                    width: 5%; 
                }
                .participants-table td:nth-child(2) { /* Nom et Prénoms */
                    width: 35%; 
                }
                .participants-table td:nth-child(3) { /* Qualité */
                    width: 27%; 
                }
                .participants-table td:nth-child(4) { /* Banque */
                    width: 15%; 
                }
                .participants-table td:last-child { /* Numéro de Compte */
                    text-align: center;
                    width: 18%; 
                }
                /* S'assurer que les en-têtes de tableau se répètent sur chaque page */
                thead { display: table-header-group; }


                /* Bloc de signatures */
                .signature-section {
                    width: 100%;
                    margin-top: 5mm; /* Ajouté un espacement de 5mm */
                }
                .signature-section td {
                    width: 50%;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                }
                .signature-section .left-signature,
                .signature-section .right-signature {
                    text-align: center;
                }
                .signature-section p {
                    margin: 2px 0;
                    line-height: 1.4;
                    font-size: 10.5pt;
                }
                .signature-section .title-signature {
                    font-weight: bold;
                    margin-bottom: 15mm; /* Espace pour la signature */
                }
                .signature-section .name-title {
                    font-weight: bold;
                    text-decoration: underline;
                    text-transform: uppercase;
                    margin-top: 5mm; /* Espace entre 'Signature' et le nom */
                }
            </style>
        </head>
        <body>
            <table class='header-table'>
                <tr>
                    <td class='header-left-block'>" . $header_left_html . "</td>
                    <td class='header-right-block'>
                        " . ($logo_src ? "<img src='" . $logo_src . "' alt='Logo Trésor Public Bénin'><br>" : "") . "
                        <p class='attestation-title'>ATTESTATION COLLECTIVE DE TRAVAIL</p>
                        <p class='activity-name-title'>DES MEMBRES CHARGÉS DE L'ACTIVITÉ<br>" . strtoupper(htmlspecialchars($nom_activite)) . "</p>
                        <p class='header-detail'><strong>Période :</strong> Du <strong>" . htmlspecialchars($date_debut_activite) . "</strong> au <strong>" . htmlspecialchars($date_fin_activite) . "</strong></p>
                       
                    </td>
                </tr>
            </table>

            <div class='object-section'>
                <p><strong>" . $note_generatrice_display . "</strong></p>
                <p><strong>" . $reference_display . "</strong></p>
                 <p class='header-detail'><strong>Lieu :</strong> <strong>" . htmlspecialchars($lieu_activite) . "</strong></p>
                        <p class='place-date'><strong>À " . htmlspecialchars(mb_strtoupper($lieu_activite, 'UTF-8')) . ", le " . $date_document . "</strong></p>
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
                    <td class='left-signature'>
                        <p class='title-signature'>LE CCOM</p>
                        <p>(Signature)</p>
                        <p class='name-title'>" . htmlspecialchars(mb_strtoupper($activity_data['organisateur_titre'] ?? 'NOM ET TITRE DE L\'ORGANISATEUR', 'UTF-8')) . "</p>
                    </td>
                    <td class='right-signature'>
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
        // X = 770 points (environ 271mm du bord gauche)
        // Y = 570 points (environ 201mm du bord supérieur, soit 9mm du bord inférieur)
        $canvas->page_text(770, 570, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        $pdf_path = $output_dir . '/' . $filename_prefix . '.pdf';
        file_put_contents($pdf_path, $dompdf->output());
        error_log("Attestation Collective PDF générée : " . $pdf_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Attestation Collective PDF : " . $e->getMessage());
    }

    // --- Génération Excel ---
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attestation Collective');

        // Styles par défaut
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // En-tête gauche (dynamique)
        if (!empty($header_left_content_raw)) {
            $lines = explode('@@@', $header_left_content_raw);
            $excel_header_row_start = 1;
            foreach ($lines as $line) {
                $sheet->setCellValue('A' . $excel_header_row_start, htmlspecialchars($line));
                // Appliquer le gras à chaque ligne de l'en-tête gauche pour Excel
                $sheet->getStyle('A' . $excel_header_row_start)->getFont()->setBold(true);
                $sheet->mergeCells('A' . $excel_header_row_start . ':C' . $excel_header_row_start);
                $excel_header_row_start++;
            }
        } else {
            // Contenu par défaut si non fourni
            $sheet->setCellValue('A1', 'RÉPUBLIQUE DU BÉNIN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->setCellValue('A2', 'MINISTÈRE DE L\'ÉCONOMIE ET DES FINANCES');
            $sheet->setCellValue('A3', 'DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE');
            $sheet->setCellValue('A4', 'Direction des Affaires Administratives et Financières');
            $sheet->setCellValue('A5', 'Service du Personnel et des Moyens');
            foreach (range(1, 5) as $row_num) {
                $sheet->mergeCells('A' . $row_num . ':C' . $row_num);
                // Appliquer le gras aux lignes par défaut de l'en-tête gauche pour Excel
                $sheet->getStyle('A' . $row_num)->getFont()->setBold(true);
            }
        }

        // En-tête droit (lieu et date - dynamique)
        $sheet->setCellValue('D1', htmlspecialchars(mb_strtoupper($lieu_activite, 'UTF-8')) . ', LE ' . htmlspecialchars(mb_strtoupper($date_document, 'UTF-8')));
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D1')->getFont()->setSize(9)->setBold(true);
        $sheet->mergeCells('D1:E1');

        // Titre central
        $row_excel = max($excel_header_row_start ?? 0, 8); // Ensure title starts below header left block
        $sheet->setCellValue('A' . $row_excel, 'ATTESTATION COLLECTIVE DE TRAVAIL');
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true)->setSize(14)->setUnderline(Font::UNDERLINE_SINGLE);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'ACTIVITÉS DE ' . strtoupper(htmlspecialchars($nom_activite)));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++; 

        // Détails de l'activité (pour Excel, une introduction moins verbeuse)
        $sheet->setCellValue('A' . $row_excel, 'La présente attestation collective de travail certifie la participation aux activités de :');
        $sheet->getStyle('A' . $row_excel)->getFont()->setSize(10);
        $sheet->getStyle('A' . $row_excel)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'Objet : ' . htmlspecialchars($nom_activite));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'Période : Du ' . htmlspecialchars($date_debut_activite) . ' au ' . htmlspecialchars($date_fin_activite));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, 'Lieu : ' . htmlspecialchars($lieu_activite));
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++; 

        // Note génératrice et Référence (dynamique)
        $sheet->setCellValue('A' . $row_excel, $note_generatrice_display);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++;

        $sheet->setCellValue('A' . $row_excel, $reference_display);
        $sheet->getStyle('A' . $row_excel)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row_excel . ':E' . $row_excel);
        $row_excel++; // Maintient l'espacement à un seul interligne avant le tableau

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
        $signature_start_row = $row_excel + 1; 

        // Bloc 1: L'ORGANISATEUR (côté gauche)
        $sheet->setCellValue('A' . $signature_start_row, 'L\'ORGANISATEUR');
        $sheet->getStyle('A' . $signature_start_row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $signature_start_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->mergeCells('A' . $signature_start_row . ':B' . $signature_start_row); 

        $sheet->setCellValue('A' . ($signature_start_row + 1), '(Signature)');
        $sheet->getStyle('A' . ($signature_start_row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->mergeCells('A' . ($signature_start_row + 1) . ':B' . ($signature_start_row + 1));

        $sheet->setCellValue('A' . ($signature_start_row + 4), htmlspecialchars(mb_strtoupper($activity_data['organisateur_titre'] ?? 'Nom et Titre Organisateur', 'UTF-8')));
        $sheet->getStyle('A' . ($signature_start_row + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('A' . ($signature_start_row + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->mergeCells('A' . ($signature_start_row + 4) . ':C' . ($signature_start_row + 4));

        // Bloc 2: LE PREMIER RESPONSABLE (côté droit, sur la même ligne)
        $sheet->setCellValue('D' . $signature_start_row, 'LE PREMIER RESPONSABLE');
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
        error_log("Attestation Collective Excel générée : " . $excel_path);
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de l'Attestation Collective Excel : " . $e->getMessage());
    }
    
    return ['pdf' => $pdf_path, 'excel' => $excel_path];
}


// --- Logique principale de generer_attestation.php ---

if (isset($_GET['activite_id'])) {
    global $mysqlClient; // Assurez-vous que $mysqlClient est accessible ici

    $activite_id = (int)$_GET['activite_id'];
    $document_type = $_GET['type'] ?? 'pdf'; // 'pdf' ou 'excel'

    // Récupérer les nouveaux paramètres de l'URL
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
        $generated_files = genererAttestationCollective(
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

        // ... le reste de la logique de distribution du fichier
    } catch (PDOException $e) {
        error_log("Erreur de base de données : " . $e->getMessage());
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    } catch (Exception $e) {
        error_log("Erreur inattendue : " . $e->getMessage());
        die("Une erreur inattendue est survenue.");
    }

    // Envoi du fichier au navigateur
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    flush(); // Flush system output buffer
    readfile($file_path);
    exit;
} else {
    die("ID d'activité manquant.");
}

?>