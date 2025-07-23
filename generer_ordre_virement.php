<?php

// Assurez-vous que le chemin vers votre autoloader Composer est correct
require_once 'vendor/autoload.php';
// Assurez-vous que le chemin vers votre fichier de connexion à la base de données est correct
require_once 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive; // N'oubliez pas d'inclure ZipArchive

/**
 * Construit le contenu HTML pour un ordre de virement pour une banque spécifique.
 * Cette fonction est une helper pour genererOrdreVirementParBanque.
 *
 * @param array $activity_data Données de l'activité.
 * @param string $bank_name Nom de la banque.
 * @param array $bank_participants Participants pour cette banque.
 * @param float $total_amount_for_bank Montant total pour cette banque.
 * @param string $document_date_str Date du document au format 'jj/mm/AAAA'.
 * @param string $header_left_content_raw Contenu brut pour l'en-tête gauche.
 * @param string $note_generatrice_n Numéro de la note génératrice.
 * @param string $reference_ref Référence du document.
 * @return string Contenu HTML de l'ordre de virement.
 */
function buildHtmlForBankOrder(
    array $activity_data,
    string $bank_name,
    array $bank_participants,
    float $total_amount_for_bank,
    string $document_date_str,
    string $header_left_content_raw,
    string $note_generatrice_n, // Nouveau paramètre pour la note génératrice
    string $reference_ref // Nouveau paramètre pour la référence
): string {
    $nom_activite = $activity_data['nom'] ?? 'Activité Inconnue';
    $lieu_activite = $activity_data['centre'] ?? 'Cotonou'; // Utilise le lieu de l'activité comme lieu du document si disponible
    
    // Date de début et fin de l'activité pour le bloc "Période"
    $date_debut_activite_obj = DateTime::createFromFormat('Y-m-d', $activity_data['date_debut']);
    $date_debut_activite = $date_debut_activite_obj ? $date_debut_activite_obj->format('d/m/Y') : 'N/A';

    $date_fin_activite_obj = DateTime::createFromFormat('Y-m-d', $activity_data['date_fin']);
    $date_fin_activite = $date_fin_activite_obj ? $date_fin_activite_obj->format('d/m/Y') : 'N/A';

    // Déterminer la note génératrice et la référence à afficher, avec les valeurs par défaut
    $note_generatrice_display = !empty($note_generatrice_n) ? htmlspecialchars($note_generatrice_n) : 'Note génératrice N';
    $reference_display = !empty($reference_ref) ? htmlspecialchars($reference_ref) : 'Référence REF';


    // Traiter l'en-tête gauche dynamique
    $header_left_html = '';
    if (!empty($header_left_content_raw)) {
        $lines = explode('@@@', $header_left_content_raw); // Utilise '@@@' comme séparateur
        foreach ($lines as $line) {
            $header_left_html .= '<p><strong>' . htmlspecialchars($line) . '</strong></p>';
        }
    } else {
        // Contenu par défaut si non fourni, similaire à la Note de Service
        $header_left_html = "
            <p><strong>RÉPUBLIQUE DU BÉNIN</strong></p>
            <p><strong>MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES</strong></p>
            <p><strong>DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE</strong></p>
            <p><strong>DIRECTION DES AFFAIRES ADMINISTRATIVES ET FINANCIÈRES</strong></p>
            <p><strong>SERVICE DU PERSONNEL ET DES MOYENS</strong></p>
        ";
    }

    $html_content = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ordre de Virement - {$bank_name}</title>
            <style>
                body {
                    font-family: 'Verdana', sans-serif;
                    margin: 12.7mm; /* Marge uniforme pour A4 */
                    font-size: 10pt;
                    color: #000;
                    line-height: 1.6;
                }

                .header-container {
                    width: 100%;
                    display: table; /* Use table display for layout */
                    table-layout: fixed;
                    margin-bottom: 5mm; /* Reduced margin here as date is separate */
                }

                .header-left-cell,
                .header-right-cell {
                    display: table-cell;
                    vertical-align: top;
                    padding: 0;
                }

                .header-left-cell {
                    width: 45%; /* Left header content width */
                    text-align: center;
                    font-size: 8.5pt;
                    font-weight: bold;
                    line-height: 1.2;
                }
                .header-left-cell p { margin: 0; }
                .header-left-cell strong { display: block; }

                .header-right-cell {
                    width: 55%; /* Right content (title, ref, date) width */
                    text-align: right;
                    font-size: 9pt;
                    line-height: 1.4;
                    position: relative; /* For absolute positioning of the title block */
                }
                .header-right-cell p { margin: 0; }
                /* No .header-right-cell .date anymore as it's separate */


                /* Title block styling to be inside header-right-cell */
                .title-block {
                    text-align: center; /* Center content within its cell */
                    margin-top: 15mm; /* Pushed down to clear the left header and date */
                    margin-bottom: 5mm;
                }
                .title-block h1 {
                    font-size: 14pt;
                    margin: 0;
                    padding: 0;
                    line-height: 1.2;
                    text-decoration: underline;
                    text-transform: uppercase;
                }
                .title-block .subtitle {
                    font-size: 10pt;
                    margin-top: 3px;
                    line-height: 1.3;
                    font-weight: bold;
                }

                /* New container for Note/Ref and Period/Place details */
                .details-container {
                    width: 100%;
                    display: table;
                    table-layout: fixed;
                    margin-top: 5mm; /* Space after main header blocks */
                    margin-bottom: 5mm;
                    font-size: 10pt;
                }
                .details-container .details-left-cell,
                .details-container .details-right-cell {
                    display: table-cell;
                    vertical-align: top;
                    padding: 0;
                }
                .details-left-cell {
                    width: 50%; /* Adjust width as needed */
                    text-align: left;
                }
                .details-left-cell p {
                    margin: 1mm 0;
                    line-height: 1.2;
                    font-weight: bold; /* Make the note/reference bold */
                }

                .details-right-cell {
                    width: 50%; /* Adjust width as needed */
                    text-align: right;
                }
                .details-right-cell p {
                    margin: 1mm 0;
                    line-height: 1.2;
                    font-weight: bold; /* Make Period/Lieu bold */
                }

                /* New Date container */
                .date-container {
                    width: 100%;
                    text-align: right;
                    font-size: 9pt;
                    font-style: italic;
                    text-transform: capitalize;
                    margin-top: 0; /* Align right after .header-container */
                    margin-bottom: 5mm; /* Space before title-block */
                }


                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px; /* Adjusted to follow the new details-container */
                    border: 1px solid #777;
                    table-layout: fixed;
                }
                th, td {
                    border: 1px solid #777;
                    padding: 6px 8px;
                    text-align: center;
                    vertical-align: middle;
                    font-size: 9pt;
                }
                th {
                    background-color: #e0e0e0;
                    font-weight: bold;
                    color: #000;
                    text-transform: uppercase;
                }
                td.text-left { text-align: left; }
                td.text-right { text-align: right; }
                td.text-center { text-align: center; }

                /* Column widths for the participants table in LANDSCAPE mode */
                th:nth-child(1), td:nth-child(1) { width: 4%; } /* N° */
                th:nth-child(2), td:nth-child(2) { width: 28%; } /* NOM ET PRENOMS */
                th:nth-child(3), td:nth-child(3) { width: 14%; } /* QUALITE */
                th:nth-child(4), td:nth-child(4) { width: 14%; } /* MONTANT */
                th:nth-child(5), td:nth-child(5) { width: 14%; } /* BANQUE */
                th:nth-child(6), td:nth-child(6) { width: 26%; } /* RIB */


                .total-row td {
                    font-weight: bold;
                    background-color: #e0e0e0;
                }
                
                .signature-block-total {
                    margin-top: 10mm;
                    text-align: center;
                    font-size: 10pt;
                }
                .signature-block-total p {
                    margin: 2px 0;
                }

                .signature-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20mm; /* Adjusted top margin */
                    border: none;
                }
                .signature-table td {
                    width: 50%;
                    padding: 0;
                    vertical-align: top;
                    border: 0;
                }
                .signature-cell.left-align, .signature-cell.right-align {
                    text-align: center; /* Centered for both signatures */
                }
                .signature-cell p { margin: 0; line-height: 1.5; font-size: 9.5pt; } /* Adjusted font size */
                .signature-cell .bold-underline {
                    font-weight: bold;
                    text-decoration: underline;
                    text-transform: uppercase; /* Name in uppercase */
                }
            </style>
        </head>
        <body>
            <div class='header-container'>
                <div class='header-left-cell'>
                    {$header_left_html}
                </div>
                <div class='header-right-cell'>
                    <p class='date-container'>{$lieu_activite}, le {$document_date_str}</p>
                    <div class='title-block'>
                        <h1>ORDRE DE VIREMENT {$bank_name}</h1>
                        <p class='subtitle'>DES INDEMNITES ET FRAIS D'ENTRETIEN ACCORDES AUX 
                        MEMBRES DE LA COMMISSION CHARGE DE LA <span class='bold'>{$nom_activite}</span></p>
                    </div>
                </div>
            </div>

            <div class='details-container'>
                <div class='details-left-cell'>
                    <p><strong>Réf : {$reference_display}</strong></p>
                    <p><strong>(Note Génératrice n° {$note_generatrice_display})</strong></p>
                </div>
                <div class='details-right-cell'>
                    <p><strong>Période : Du {$date_debut_activite} au {$date_fin_activite}</strong></p>
                    <p><strong>Lieu : {$lieu_activite}</strong></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>NOM ET PRÉNOMS</th>
                        <th>QUALITÉ</th>
                        <th>MONTANT</th>
                        <th>BANQUE</th>
                        <th>RIB</th>
                    </tr>
                </thead>
                <tbody>
    HTML;
            
                $ordre = 1;
                foreach ($bank_participants as $participant) {
                    $nom_complet = $participant['nom'] ?? '';
                    if (($participant['type_participant'] ?? '') === 'individu' && !empty($participant['prenom'])) {
                        $nom_complet .= ' ' . $participant['prenom'];
                    }
                    $numero_compte = $participant['numero_compte'] ?? 'N/A';
                    $montant = $participant['montant_a_payer'] ?? 0;

                    $html_content .= "
                        <tr>
                            <td>" . $ordre++ . "</td>
                            <td class='text-left'>" . htmlspecialchars($nom_complet) . "</td>
                            <td>" . htmlspecialchars($participant['qualite']) . "</td>
                            <td class='text-right'>" . number_format($montant, 0, ',', ' ') . "</td>
                            <td>" . htmlspecialchars(mb_strtoupper($bank_name, 'UTF-8')) . "</td>
                            <td>" . htmlspecialchars($numero_compte) . "</td>
                        </tr>";
                }

    $html_content .= <<<HTML
                    <tr class='total-row'>
                        <td colspan='3' class='text-right'><strong>TOTAL :</strong></td>
                        <td class='text-center'><strong>" . number_format($total_amount_for_bank, 0, ',', ' ') . " FCFA</strong></td>
                        <td colspan='2' style='border:none;'></td>
                    </tr>
                </tbody>
            </table>

            <div class='signature-block-total'>
                <p><b>Arrêté le présent ordre de virement à la somme de " . number_format($total_amount_for_bank, 0, ',', ' ') . " FCFA</b></p>
            </div>

            <table class='signature-table'>
                <tr>
                    <td class='signature-cell left-align'>
                        <p style='margin-top: 20px;'><b>LE C/GAP</b></p>
                        <p>(cachet et signature)</p>
                        <p class='bold-underline' style='margin-top: 10px;'>{$activity_data['financier']}</p>
                    </td>
                    <td class='signature-cell right-align'>
                        <p style='margin-top: 20px;'><b>LE CMAP</b></p>
                        <p>(cachet et signature)</p>
                        <p class='bold-underline' style='margin-top: 10px;'>{$activity_data['responsable_titre']}</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
HTML; // IMPORTANT: This closing tag MUST be at the very beginning of the line, no spaces or tabs before it.

    return $html_content;
}


/**
 * Génère les Ordres de Virement, un document PDF par banque.
 * Retourne un tableau des chemins des fichiers PDF générés.
 *
 * @param array $activity_data Données de l'activité.
 * @param array $participants_data Tableau des participants.
 * @param string $output_dir Répertoire de sortie pour les documents générés.
 * @param string $header_left_content_raw Contenu brut pour l'en-tête gauche.
 * @param string $document_date_str Date du document au format 'jj/mm/AAAA'.
 * @return array Chemins des fichiers PDF générés.
*/
function genererOrdreVirementParBanque(array $activity_data, array $participants_data, string $output_dir, string $header_left_content_raw, string $document_date_str): array {
    $generated_files_paths = [];

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
            $p_montant = $p['montant_a_payer'] ?? 0;
            $total_amount_for_bank += $p_montant;
        }

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Verdana');

            $dompdf = new Dompdf($options);

            // Définir les métadonnées du PDF pour une meilleure cohérence
            $dompdf->getOptions()->set('creator', 'Votre Application');
            $dompdf->getOptions()->set('title', 'Ordre de Virement - ' . $bank_name);
            $dompdf->getOptions()->set('subject', 'Ordre de Virement pour l\'activité : ' . ($activity_data['nom'] ?? 'Inconnue'));
            $dompdf->getOptions()->set('keywords', 'virement, banque, ' . $bank_name . ', ' . $activity_data['nom'] ?? 'activité');

            // Construire le HTML pour l'ordre de virement de cette banque, en passant les paramètres dynamiques
            $html_bank_order = buildHtmlForBankOrder(
                $activity_data,
                $bank_name,
                $bank_participants,
                $total_amount_for_bank,
                $document_date_str,
                $header_left_content_raw,
                // Passing the note_generatrice and reference_document from activity_data
                $activity_data['note_generatrice'] ?? '', 
                $activity_data['reference_document'] ?? '' 
            );

            $dompdf->loadHtml($html_bank_order);
            $dompdf->setPaper('A4', 'landscape'); // Mode paysage
            $dompdf->render();

            // Ajout de la pagination au PDF - Ajusté pour le mode paysage
            $canvas = $dompdf->getCanvas();
            $font = $dompdf->getFontMetrics()->getFont("Verdana", "normal");
            // Coordonnées pour le bas à droite en mode paysage
            $canvas->page_text(750, 560, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0, 0, 0));


            $bank_filename = "Ordre_Virement_" . preg_replace('/[^a-zA-Z0-9_]/', '', $bank_name) . "_" . ($activity_data['id'] ?? 'sans_id') . ".pdf";
            $pdf_path = $output_dir . '/' . $bank_filename;
            file_put_contents($pdf_path, $dompdf->output());
            $generated_files_paths[] = $pdf_path;
            error_log("Ordre de Virement PDF généré pour " . $bank_name . " : " . $pdf_path);

        } catch (Exception $e) {
            error_log("Erreur lors de la génération de l'Ordre de Virement PDF pour " . $bank_name . " : " . $e->getMessage());
        }
    }
    return $generated_files_paths;
}


// --- Logique principale de generer_ordre_virement.php ---

if (isset($_GET['activite_id'])) {
    global $mysqlClient;

    $activite_id = (int)$_GET['activite_id'];

    // Récupérer les nouveaux paramètres de l'URL
 // Récupérer les nouveaux paramètres de l'URL
 $header_left_content = $_GET['header_left_content'] ?? '';
 $note_generatrice_n = $_GET['note_generatrice_n'] ?? '';
 $reference_ref = $_GET['reference_ref'] ?? '';
 $document_date_str = $_GET['document_date'] ?? ''; 


    try {
        // 1. Récupérer les données de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if (!$activity_data) {
            die("Activité non trouvée.");
        }

        // Compléter les données de l'activité avec les paramètres d'URL s'ils existent
        // et définir des valeurs par défaut pour les champs manquants ou non définis dans la BD
        $activity_data['reference_document'] = !empty($reference_ref) ? $reference_ref : ($activity_data['reference_document'] ?? 'N°____/MEF/DGTCP/DAAF/SPM');
        // If the note_generatrice_param exists and is not empty, use it directly. Otherwise, use the existing activity_data or default.
        $activity_data['note_generatrice'] = !empty($note_generatrice_n) ? $note_generatrice_n : ($activity_data['note_generatrice'] ?? 'Note Génératrice par défaut');
        
        // Ensure these titles are properly escaped and uppercase before passing to HTML building function
        $activity_data['responsable_titre'] = htmlspecialchars(mb_strtoupper($activity_data['responsable_titre'] ?? 'LE CHEF DU CENTRE DU MATÉRIEL ET DES APPLICATIONS DU PERSONNEL', 'UTF-8'));
        $activity_data['financier'] = htmlspecialchars(mb_strtoupper($activity_data['financier'] ?? 'LE CHEF DU SERVICE DES AFFAIRES FINANCIÈRES', 'UTF-8'));


        // 2. Récupérer les participants liés à cette activité avec le montant à payer
        $sql_participants = "
            SELECT
                part.type_participant,
                COALESCE(pp.nom, pm.denomination) AS nom,
                pp.prenom,
                part.titre AS qualite,
                cb.banque,
                cb.numero_compte,
                (part.nb_jours_copies * part.taux_journalier_copie + part.nb_jours_deplacement * part.frais_deplacement + part.forfait_participant) AS montant_a_payer 
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
            die("Aucun participant trouvé pour cette activité.");
        }

        // 3. Définir le répertoire de sortie temporaire
        $output_dir = __DIR__ . '/temp_documents';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        // 4. Générer les documents PDF par banque en passant les paramètres dynamiques
        $generated_pdf_files = genererOrdreVirementParBanque(
            $activity_data,
            $participants_data,
            $output_dir,
            $header_left_content,
            $note_generatrice_n,
            $reference_ref,
            $document_date_str
        );

        if (empty($generated_pdf_files)) {
            die("Aucun ordre de virement PDF n'a pu être généré.");
        }

        // 5. Compresser les fichiers PDF générés dans un fichier ZIP
        $zip_filename = $output_dir . '/Ordres_Virement_Activite_' . ($activity_data['id'] ?? 'sans_id') . '.zip';
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
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    } catch (Exception $e) {
        error_log("Erreur inattendue lors de la génération/téléchargement des ordres de virement : " . $e->getMessage());
        die("Une erreur inattendue est survenue.");
    }
} else {
    die("ID d'activité manquant. Veuillez fournir un 'activite_id' dans l'URL.");
}

?>