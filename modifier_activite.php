<?php
require_once 'db.php'; // Assurez-vous que ce fichier gère correctement la connexion PDO $mysqlClient

$errors = [];
$success_message = '';
$activity_name_display = 'Chargement...'; // Nom pour l'affichage en haut de page
$current_activite_data = []; // Pour stocker toutes les données actuelles de l'activité

// --- 1. Récupération des IDs et des données actuelles de l'activité ---
$activite_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($activite_id === 0) {
    $errors['global'] = "ID d'activité manquant. Impossible de modifier.";
} else {
    // Récupérer toutes les données de l'activité pour pré-remplir le formulaire
    // Et pour afficher le nom en haut de page
    $stmt_activity = $mysqlClient->prepare("SELECT * FROM activites WHERE id = :id"); // Supposons 'participations'
    $stmt_activity->execute([':id' => $activite_id]);
    $current_activite_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

    if ($current_activite_data) {
        // Dé-encoder le nom pour l'affichage dans le titre de la page
        // Cette étape est nécessaire car vos données sont stockées encodées.
        // Si vos données étaient stockées propres, seul htmlspecialchars serait nécessaire ici.
        $activity_name_display = html_entity_decode($current_activite_data['nom'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Si le double encodage &#039; en &amp;#039; existe déjà dans la DB, il faut un double decode
        $activity_name_display = html_entity_decode($activity_name_display, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    } else {
        $errors['global'] = "Activité non trouvée avec l'ID fourni.";
        // Si l'activité n'est pas trouvée, autant ne pas essayer de traiter le POST
        $activite_id = 0; 
    }
}


// --- 2. Traitement de la soumission du formulaire (requête POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activite_id > 0) { // S'assurer que l'ID est valide pour le POST
    // Récupérer les données du formulaire
    $nom_activite = trim($_POST['activityName'] ?? '');
    $description_activite = trim($_POST['activityDescription'] ?? '');
    $premier_responsable = trim($_POST['Premier_Responsable'] ?? '');
    $organisateur = trim($_POST['Organisateur'] ?? '');
    $financier = trim($_POST['Financier'] ?? '');
    $start_date = trim($_POST['startDate'] ?? '');
    $end_date = trim($_POST['endDate'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $note_generatrice = trim($_POST['note_generatrice'] ?? '');

    // --- 2. Validations des champs ---
    // Les validations de longueur et de non-vide restent.
    // **AUCUN htmlspecialchars OU html_entity_decode ICI.**
    // Les valeurs $nom_activite, $description_activite etc. doivent rester BRUTES telles que soumises par le formulaire.

    // 2.1. Nom de l'activité (Obligatoire)
    if (empty($nom_activite)) {
        $errors['activityName'] = "Le nom de l'activité est obligatoire.";
    } elseif (mb_strlen($nom_activite) < 5 || mb_strlen($nom_activite) > 100) {
        $errors['activityName'] = "Le nom de l'activité doit contenir entre 5 et 100 caractères.";
    }

    // 2.2. Description de l'activité (Optionnel, mais validation de longueur si rempli)
    if (!empty($description_activite)) {
        if (mb_strlen($description_activite) > 500) {
            $errors['activityDescription'] = "La description de l'activité ne doit pas dépasser 500 caractères.";
        }
    }

    // 2.3. Premier Responsable (Obligatoire)
    if (empty($premier_responsable)) {
        $errors['premier_responsable'] = "Le nom du Premier Responsable est obligatoire.";
    } elseif (mb_strlen($premier_responsable) < 3 || mb_strlen($premier_responsable) > 255) {
        $errors['premier_responsable'] = "Le nom du Premier Responsable doit contenir entre 3 et 255 caractères.";
    }

    // 2.4. Organisateur (Obligatoire)
    if (empty($organisateur)) {
        $errors['organisateur'] = "Le nom de l'Organisateur est obligatoire.";
    } elseif (mb_strlen($organisateur) < 3 || mb_strlen($organisateur) > 255) {
        $errors['organisateur'] = "Le nom de l'Organisateur doit contenir entre 3 et 255 caractères.";
    }

    // 2.5. Financier (Obligatoire - selon votre liste, il est maintenant un champ à considérer)
    if (empty($financier)) {
        $errors['financier'] = "Le nom du Financier est obligatoire.";
    } elseif (mb_strlen($financier) < 3 || mb_strlen($financier) > 255) {
        $errors['financier'] = "Le nom du Financier doit contenir entre 3 et 255 caractères.";
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
    }

    // 2.7. Lieu de l'activité (Optionnel, mais validation de longueur si rempli)
    if (!empty($location)) {
        if (mb_strlen($location) > 100) {
            $errors['location'] = "Le lieu de l'activité ne doit pas dépasser 100 caractères.";
        }
    }
    // 2.8 Note génératrice (obligatoire)
    if (empty($note_generatrice)) {
        $errors['note_generatrice'] = "La note génératrice est obligatoire.";
    }


    // --- 3. Exécution de la mise à jour si aucune erreur de validation ---
    if (empty($errors)) {
        try {
            $mysqlClient->beginTransaction();

            $activite_update = "UPDATE activites SET
                `nom`                 = :nom_activite,
                `Description`       = :description_activite,
                `Responsable_titre` = :premier_responsable,
                `organisateur_titre`   = :organisateur,
                `financier_titre`  = :financier,
                `note_generatrice`     = :note_generatrice,
                `periode_debut`     = :start_date,
                `periode_fin`     = :end_date,
                `centre`   = :location
            WHERE id = :activite_id";

            $activite_to_update = $mysqlClient->prepare($activite_update);
            $activite_to_update->execute([
                ':nom_activite'         => $nom_activite, // ICI: Utiliser la valeur BRUTE de $_POST
                ':description_activite' => !empty($description_activite) ? $description_activite : NULL,
                ':premier_responsable'  => $premier_responsable,
                ':organisateur'         => $organisateur,
                ':financier'            => $financier,
                ':start_date'           => $start_date,
                ':end_date'             => $end_date,
                ':location'             => $location,
                ':note_generatrice'     => $note_generatrice,
                ':activite_id'          => $activite_id
            ]);

            $mysqlClient->commit();
            $success_message = "L'activité a été modifiée avec succès.";

            // Rediriger vers la page de gestion avec le message de succès.
            header("Location: gerer_activites.php?msg=" . urlencode($success_message));
            exit();

        } catch (PDOException $e) {
            $mysqlClient->rollBack();
            // Erreur de base de données. Ne pas HTML-échapper le message directement si c'est pour un débogage.
            // Pour l'affichage final à l'utilisateur, oui, mais pour cette erreur SQL, c'est mieux de la voir en clair.
            $errors['db_error'] = "Erreur lors de la modification de l'activité : " . $e->getMessage();
        }
    } else {
        // Si des erreurs de validation, on affiche les messages d'erreur au-dessus du formulaire
        // Les valeurs POST sont conservées pour pré-remplir les champs.
    }
}

// --- 4. Chargement initial des données pour le pré-remplissage du formulaire ---
// Si le formulaire n'a pas été soumis (GET) ou s'il y a des erreurs de validation (POST),
// les champs doivent être pré-remplis avec les données actuelles ou les données POST soumises.

// Pour le pré-remplissage, on va utiliser :
// 1. Les données POST si le formulaire a été soumis et qu'il y a des erreurs de validation
// 2. Les données de la base de données si c'est un chargement initial (GET)
$form_data = $current_activite_data; // Valeurs par défaut depuis la DB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    // Si le formulaire a été soumis avec des erreurs, on garde les valeurs saisies par l'utilisateur
    // pour qu'il n'ait pas à tout retaper.
    $form_data['nom'] = $_POST['activityName'] ?? '';
    $form_data['Description'] = $_POST['activityDescription'] ?? '';
    $form_data['Responsable_titre'] = $_POST['Premier_Responsable'] ?? '';
    $form_data['organisateur_titre'] = $_POST['Organisateur'] ?? '';
    $form_data['financier_titre'] = $_POST['Financier'] ?? '';
    $form_data['periode_debut'] = $_POST['startDate'] ?? '';
    $form_data['periode_fin'] = $_POST['endDate'] ?? '';
    $form_data['centre'] = $_POST['location'] ?? '';
    $form_data['note_generatrice'] = $_POST['note_generatrice'] ?? '';
}

// Pour l'affichage dans les champs HTML, on dé-encode les valeurs
// Cette étape est nécessaire car vos données sont stockées encodées dans la DB.
// Si votre DB était propre, seul htmlspecialchars serait nécessaire ici.
function get_decoded_html_value($value) {
    if ($value === null) {
        return '';
    }
    // Double décode pour les cas comme &amp;#039;
    $decoded_value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Décode simple pour les cas comme &#039;
    $decoded_value = html_entity_decode($decoded_value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Puis, HTML-échappe le résultat pour l'affichage sécurisé dans un attribut HTML
    return htmlspecialchars($decoded_value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Activité - Plateforme de Gestion des Paiements</title>
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
            <h2>Modifier l'activité : <?php echo htmlspecialchars($activity_name_display, ENT_QUOTES, 'UTF-8'); ?> </h2>
            <p class="form-description">Remplissez le formulaire ci-dessous pour procéder à la modification.</p>

            <?php if (!empty($errors)): ?>
                <div style="color: red; background-color: #ffe6e6; border: 1px solid red; padding: 10px; margin-bottom: 20px;">
                    <p>Des erreurs ont été détectées :</p>
                    <ul>
                        <?php foreach ($errors as $field => $message): ?>
                            <li><strong><?php echo htmlspecialchars($field); ?> :</strong> <?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div style="color: green; background-color: #e6ffe6; border: 1px solid green; padding: 10px; margin-bottom: 20px;">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <form action="modifier_activite.php?id=<?php echo $activite_id; ?>" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>Informations Générales de l’Activité</legend>

                    <div class="form-group">
                        <label for="activityName">Nom de l’activité :</label>
                        <input type="text" id="activityName" name="activityName" placeholder="Ex. Examen BEPC, Formation RH" minlength="5" maxlength="100" required
                               value="<?php echo get_decoded_html_value($form_data['nom'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="activityDescription">Description :</label>
                        <textarea id="activityDescription" name="activityDescription" rows="5" placeholder="Brève description de l’activité…" maxlength="500"><?php echo get_decoded_html_value($form_data['Description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Premier_Responsable">Premier Responsable de l'activité:</label>
                        <input type="text" id="Premier_Responsable" name="Premier_Responsable" placeholder="Inscrivez ici le nom du premier Responsable de l 'activité" maxlength="255" required
                               value="<?php echo get_decoded_html_value($form_data['Responsable_titre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="Organisateur">Organisateur:</label>
                        <input type="text" id="Organisateur" name="Organisateur" placeholder="Inscrivez ici, le nom de L'Organisateur" maxlength="255" required
                               value="<?php echo get_decoded_html_value($form_data['organisateur_titre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="Financier">Financier :</label>
                        <input type="text" id="Financier" name="Financier" placeholder="Inscrivez ici le nom du financier" maxlength="255" required
                               value="<?php echo get_decoded_html_value($form_data['financier_titre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="startDate">Date de début :</label>
                        <input type="date" id="startDate" name="startDate" required
                               value="<?php echo get_decoded_html_value($form_data['periode_debut'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="endDate">Date de fin :</label>
                        <input type="date" id="endDate" name="endDate" required
                               value="<?php echo get_decoded_html_value($form_data['periode_fin'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="location">Lieu de l’activité :</label>
                        <input type="text" id="location" name="location" placeholder="Ex. Palais des Congrès, Cotonou" maxlength="100"
                               value="<?php echo get_decoded_html_value($form_data['centre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="note_generatrice">Note génératrice:</label>
                        <input type="text" id="note_generatrice" name="note_generatrice" required
                               value="<?php echo get_decoded_html_value($form_data['note_generatrice'] ?? ''); ?>">
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Modifier l’Activité</button>
                    <button type="reset" class="btn secondary">Réinitialiser le formulaire</button>
                    <a href="gerer_activites.php" class="btn secondary">Annuler et Retourner à la Gestion des Activités</a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>
</body>
</html>