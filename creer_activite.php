<?php
require 'db.php';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $errors = []; // Initialisez votre tableau d'erreurs ici
    $success = ""; // Pour les messages de succès

    $nom_activite = trim($_POST ['activityName'] ?? '');
    $description_activite = trim($_POST['activityDescription'] ?? '');
    $premier_responsable = trim($_POST['Premier_Responsable'] ?? '');
    $organisateur = trim($_POST['Organisateur'] ?? '');
    $financier = trim($_POST['Financier'] ?? '');
    $start_date = trim($_POST['startDate'] ?? '');
    $end_date = trim($_POST['endDate'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $note_generatrice = '';

    // --- 2. Validations des champs ---

    // 2.1. Nom de l'activité (Obligatoire)
    if (empty($nom_activite)) {
        $errors['activityName'] = "Le nom de l'activité est obligatoire.";
    } elseif (mb_strlen($nom_activite) < 5 || mb_strlen($nom_activite) > 100) {
        $errors['activityName'] = "Le nom de l'activité doit contenir entre 5 et 100 caractères.";
    } else {
        $nom_activite = htmlspecialchars($nom_activite, ENT_QUOTES, 'UTF-8');
    }

    // 2.2. Description de l'activité (Optionnel, mais validation de longueur si rempli)
    if (!empty($description_activite)) {
        if (mb_strlen($description_activite) > 500) {
            $errors['activityDescription'] = "La description de l'activité ne doit pas dépasser 500 caractères.";
        }
        $description_activite = htmlspecialchars($description_activite, ENT_QUOTES, 'UTF-8');
    }

    // 2.3. Premier Responsable (Obligatoire)
    if (empty($premier_responsable)) {
        $errors['premier_responsable'] = "Le nom du Premier Responsable est obligatoire.";
    } elseif (mb_strlen($premier_responsable) < 3 || mb_strlen($premier_responsable) > 255) {
        $errors['premier_responsable'] = "Le nom du Premier Responsable doit contenir entre 3 et 255 caractères.";
    } else {
        $premier_responsable = htmlspecialchars($premier_responsable, ENT_QUOTES, 'UTF-8');
    }

    // 2.4. Organisateur (Obligatoire)
    if (empty($organisateur)) {
        $errors['organisateur'] = "Le nom de l'Organisateur est obligatoire.";
    } elseif (mb_strlen($organisateur) < 3 || mb_strlen($organisateur) > 255) {
        $errors['organisateur'] = "Le nom de l'Organisateur doit contenir entre 3 et 255 caractères.";
    } else {
        $organisateur = htmlspecialchars($organisateur, ENT_QUOTES, 'UTF-8');
    }

    // 2.5. Financier (Obligatoire - selon votre liste, il est maintenant un champ à considérer)
    if (empty($financier)) {
        $errors['financier'] = "Le nom du Financier est obligatoire.";
    } elseif (mb_strlen($financier) < 3 || mb_strlen($financier) > 255) {
        $errors['financier'] = "Le nom du Financier doit contenir entre 3 et 255 caractères.";
    } else {
        $financier = htmlspecialchars($financier, ENT_QUOTES, 'UTF-8');
    }

    // 2.6. Dates de début et de fin (Obligatoires)
    if (empty($start_date)) {
        $errors['startDate'] = "La date de début est obligatoire.";
    } elseif (!strtotime($start_date)) {
        $errors['startDate'] = "La date de début n'est pas un format valide.";
    }

    if (empty($end_date)) {
        $errors['endDate'] = "La date de fin est obligatoire.";
    } elseif (!strtotime($end_date)) {
        $errors['endDate'] = "La date de fin n'est pas un format valide.";
    }

    // Si les deux dates sont valides au format, vérifier leur cohérence
    if (empty($errors['startDate']) && empty($errors['endDate'])) {
        if (strtotime($start_date) > strtotime($end_date)) {
            $errors['dateRange'] = "La date de début ne peut pas être postérieure à la date de fin.";
        }
        // Optionnel : s'assurer que la date de fin n'est pas dans le passé pour une nouvelle activité
        if (strtotime($end_date) < strtotime(date('Y-m-d'))) {
            // Supprimez cette ligne si vous autorisez la création d'activités avec des dates de fin passées.
            $errors['endDate'] = "La date de fin ne peut pas être dans le passé pour une nouvelle activité.";
        }
    }

    // 2.7. Lieu de l'activité (Optionnel, mais validation de longueur si rempli)
    if (!empty($location)) {
        if (mb_strlen($location) > 100) {
            $errors['location'] = "Le lieu de l'activité ne doit pas dépasser 100 caractères.";
        }
        $location = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
    }

// Assurez-vous que ces constantes sont définies quelque part dans votre configuration,
// ou définissez-les ici pour l'exemple.
define('UPLOAD_DIR_NOTES', __DIR__ . '/uploads/notes/'); // Chemin d'upload absolu
define('MAX_FILE_SIZE_PDF', 5 * 1024 * 1024); // 5 Mo maximum pour un PDF (ajustez si nécessaire)
define('ALLOWED_PDF_MIMES', ['application/pdf']); // Types MIME autorisés
define('ALLOWED_PDF_EXTENSIONS', ['pdf']); // Extensions autorisées

$notePdfPath = null; // Initialisation pour stocker le chemin si l'upload réussit
$uploadError = '';   // Pour stocker les messages d'erreur spécifiques à l'upload

// 1. Vérifier si le fichier a été envoyé via HTTP POST et sans erreur initiale
if (isset($_FILES['note_generatrice']) && $_FILES['note_generatrice']['error'] == UPLOAD_ERR_OK) {

    $file = $_FILES['note_generatrice'];

    // 2. Vérifier si le fichier est bien un fichier uploadé temporaire
    if (!is_uploaded_file($file['tmp_name'])) {
        $uploadError = "Le fichier n'a pas été téléchargé via une requête HTTP POST valide.";
    }
    // 3. Valider la taille du fichier
    elseif ($file['size'] > MAX_FILE_SIZE_PDF) {
        $uploadError = "Le fichier est trop volumineux. La taille maximale est de " . (MAX_FILE_SIZE_PDF / (1024 * 1024)) . " Mo.";
    }
    // 4. Valider le type MIME du fichier (contenu réel)
    elseif (!in_array($file['type'], ALLOWED_PDF_MIMES)) {
        // Alternative plus robuste: utiliser finfo_open pour vérifier le vrai type MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        if (!in_array($mime_type, ALLOWED_PDF_MIMES)) {
             $uploadError = "Type de fichier non autorisé. Seuls les fichiers PDF sont acceptés. Type détecté: " . htmlspecialchars($mime_type);
        }
        finfo_close($finfo);
    }
    // 5. Valider l'extension du fichier (pour le nommage et une couche supplémentaire)
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($fileExtension), ALLOWED_PDF_EXTENSIONS)) {
        $uploadError = "Extension de fichier non autorisée. Seuls les fichiers .pdf sont acceptés.";
    }

    // Si aucune erreur de validation jusqu'ici
    if (empty($uploadError)) {
        // 6. Créer le dossier d'upload s'il n'existe pas avec des permissions plus sécurisées
        if (!is_dir(UPLOAD_DIR_NOTES)) {
            // Permissions 0755 : propriétaire (rwx), groupe (rx), autres (rx)
            if (!mkdir(UPLOAD_DIR_NOTES, 0755, true)) {
                $uploadError = "Impossible de créer le dossier d'upload des notes.";
            }
        }

        if (empty($uploadError)) {
            // 7. Générer un nom de fichier unique et sécurisé
            // Combine un ID unique avec l'extension de fichier vérifiée.
            $newFileName = uniqid('note_generatrice_', true) . '.' . $fileExtension;
            $uploadFilePath = UPLOAD_DIR_NOTES . $newFileName;

            // 8. Déplacer le fichier temporaire vers sa destination finale
            if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                $notePdfPath = $uploadFilePath; // Chemin à stocker dans la base de données
            } else {
                $uploadError = "Une erreur inconnue est survenue lors du déplacement du fichier.";
                // Vous pouvez obtenir plus de détails sur l'erreur ici avec error_get_last()
                // ou en vérifiant le code d'erreur de move_uploaded_file si votre version de PHP le permet.
            }
        }
    }

} else {
    // Gérer les erreurs initiales d'upload (avant même d'atteindre le traitement)
    switch ($_FILES['note_generatrice']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $uploadError = "Le fichier téléchargé dépasse la taille maximale autorisée par le serveur.";
            break;
        case UPLOAD_ERR_PARTIAL:
            $uploadError = "Le fichier n'a été que partiellement téléchargé.";
            break;
        case UPLOAD_ERR_NO_FILE:
            $uploadError = "Aucun fichier n'a été téléchargé.";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $uploadError = "Dossier temporaire manquant.";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $uploadError = "Échec de l'écriture du fichier sur le disque.";
            break;
        case UPLOAD_ERR_EXTENSION:
            $uploadError = "Une extension PHP a arrêté le téléchargement du fichier.";
            break;
        default:
            $uploadError = "Une erreur de téléchargement inconnue s'est produite.";
            break;
    }
}

// Si une erreur est survenue pendant tout le processus d'upload
if (!empty($uploadError)) {
    throw new Exception("Erreur lors de l'upload de la note génératrice : " . $uploadError);
}

// Maintenant, $notePdfPath contient le chemin du fichier PDF si tout s'est bien passé
// ou une exception a été levée si une erreur est survenue.
// Vous pouvez utiliser $notePdfPath pour l'enregistrer dans votre base de données.
// Par exemple : $stmt->bindParam(':note_pdf_path', $notePdfPath);


    // --- 3. Gestion des erreurs après toutes les validations ---

    if (empty($errors)) {
        // Aucune erreur de validation, les données sont prêtes pour l'insertion en base de données.
        echo "<p style='color: green;'>Toutes les données de l'activité sont valides. Prêt pour l'insertion en base de données.</p>";

        // Exemple d'utilisation des données validées (pour l'insertion en DB via PDO) :
        

    } else {
        // Des erreurs de validation ont été trouvées, affichez-les.
        echo "<p style='color: red;'>Des erreurs de validation ont été détectées :</p>";
        echo "<ul>";
        foreach ($errors as $field => $message) {
            echo "<li><strong>" . htmlspecialchars($field) . " :</strong> " . htmlspecialchars($message) . "</li>";
        }
        echo "</ul>";
        // Vous pouvez aussi afficher les messages d'erreur directement à côté des champs dans votre formulaire HTML.
    }

 
    try {
    
        $mysqlClient->beginTransaction();
        $InsertActivity = "INSERT INTO activites (nom, description, responsable_titre, organisateur_titre, financier_titre, periode_debut, periode_fin, centre, note_generatrice)
        VALUES (:nom_activite, :description_activite, :premier_responsable, :organisateur, :financier, :start_date, :end_date, :location, :note_generatrice)";
        $stmt = $mysqlClient->prepare($InsertActivity);
        $stmt->execute([
             ':nom_activite'         => $nom_activite,
             ':description_activite' => !empty($description_activite) ? $description_activite : NULL,
             ':premier_responsable'  => $premier_responsable,
             ':organisateur'         => $organisateur,
             ':financier'            => $financier,
             ':start_date'           => $start_date,
             ':end_date'             => $end_date,
             ':location'             => $location,
             ':note_generatrice'     => $notePdfPath
             ]);
             $mysqlClient->commit();
             echo "<p style='color: green;'>Activité insérée avec succès dans la base de données !</p>";
            header("Location: gerer_participants.php");
            exit();


    }catch (PDOException $e) {
        $mysqlClient->rollBack();
        error_log("Erreur BD lors de l'insertion : " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        echo "<p style='color:red;'>Une erreur de base de données est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    } catch (Exception $e) {
        $mysqlClient->rollBack();
        error_log("Erreur application lors de l'insertion : " . $e->getMessage());
        echo "<p style='color:red;'>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} 
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Financier - Gestion des Paiements</title>
    <link rel="stylesheet" href="class1.css">
</head>
<body>
    <header>
        <div class="header-top">
            <div class="header-content">
                <img src="tresorpubbenin.png" alt="Logo Trésor Public Bénin" id="logo">
                <div class="site-branding">
                    <h1>Plateforme de Gestion des Paiements</h1>
                    <p>Bienvenue sur la plateforme de paiement des activités</p>
                </div>
            </div>
            <div class="header-utility">
                <div class="search-bar">
                    <input type="search" placeholder="Rechercher une activité..." aria-label="Rechercher">
                    <button type="submit">Rechercher</button>
                </div>
                <nav class="utility-nav">
                    <ul>
                        <li><a href="page_aide.html">Aide</a></li>
                        <li><a href="page_contact.html">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="accueil.html">Accueil</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Activités</a>
                    <div class="dropdown-content">
                        <a href="creer_activite.php">Créer Activité</a>
                        <a href="gerer_activites.php">Gérer Activité</a>
                    </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="login.html">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="form-section">
            <h2>Créer une Nouvelle Activité</h2>
            <p class="form-description">Remplissez le formulaire ci-dessous pour définir une nouvelle activité.</p>

            <form action="#" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>Informations Générales de l’Activité</legend>

                    <div class="form-group">
                        <label for="activityName">Nom de l’activité :</label>
                        <input type="text" id="activityName" name="activityName" placeholder="Ex. Examen BEPC, Formation RH" minlength="5" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label for="activityDescription">Description :</label>
                        <textarea id="activityDescription" name="activityDescription" rows="5" placeholder="Brève description de l’activité…" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Premier_Responsable">Premier Responsable de l'activité:</label>
                        <textarea id="Premier_Responsable" name="Premier_Responsable" placeholder="Inscrivez ici le nom du premier Responsable de l 'activité" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Organisateur">Organisateur:</label>
                        <textarea id="Organisateur" name="Organisateur" placeholder="Inscrivez ici, le nom de L'Organisateur" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Financier">Financier :</label>
                        <textarea id="Financier" name="Financier"  placeholder="Inscrivez ici le nom du financier" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="startDate">Date de début :</label>
                        <input type="date" id="startDate" name="startDate" required>
                    </div>

                    <div class="form-group">
                        <label for="endDate">Date de fin :</label>
                        <input type="date" id="endDate" name="endDate" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Lieu de l’activité :</label>
                        <input type="text" id="location" name="location" placeholder="Ex. Palais des Congrès, Cotonou" maxlength="100">
                    </div>

                     <div class="form-group">
                                <label for="note_generatrice"> Note generatrice:</label>
                                <input type="file" id="note_generatrice" name="note_generatrice" accept="application/pdf" required>
                                <small>Fichier PDF uniquement.</small>
                     </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Créer l’Activité</button>
                    <button type="reset" class="btn secondary">Réinitialiser le formulaire</button>
                    <a href="tableau_de_bord_financier.html" class="btn secondary">Annuler et Retourner au Tableau de Bord</a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>
</body>
</html>


