<?php
require_once 'db.php'; // Assurez-vous que ce fichier gère correctement la connexion PDO $mysqlClient

$error_message = '';
$success_message = '';
$activity_name = 'Chargement...'; // Valeur par défaut
$current_participation_data = []; // Pour stocker les données actuelles du participant

// --- 1. Récupération des IDs et de l'activité ---
$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$participation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Vérifier si les IDs nécessaires sont présents
if ($activite_id === 0 || $participation_id === 0) {
    $error_message = "ID d'activité ou de participation manquant. Impossible de modifier.";
} else {
    try {
        // Récupérer le nom de l'activité
        $stmt_activity = $mysqlClient->prepare("SELECT nom FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);

        if ($activity_data) {
            $activity_name = htmlspecialchars($activity_data['nom']);
        } else {
            $error_message .= " Activité non trouvée.";
        }

        // Récupérer les informations actuelles de la participation pour pré-remplir le formulaire
        $query_participation_data = $mysqlClient->prepare("
            SELECT
                p.titre,
                p.nb_jours_copies,
                p.taux_journalier_copie,
                p.forfait_participant,
                p.nb_jours_deplacement,
                p.frais_deplacement,
                CASE
                    WHEN p.type_participant = 'individu' THEN pp.nom
                    WHEN p.type_participant = 'personne_morale' THEN pm.denomination
                    ELSE NULL
                END AS nom_participant,
                CASE
                    WHEN p.type_participant = 'individu' THEN pp.prenom
                    ELSE NULL
                END AS prenom_participant
            FROM
                participations p
            LEFT JOIN
                personnes_physiques pp ON p.participant_id = pp.participant_id AND p.type_participant = 'individu'
            LEFT JOIN
                personnes_morales pm ON p.participant_id = pm.participant_id AND p.type_participant = 'personne_morale'
            WHERE
                p.id = :participation_id AND p.activite_id = :activite_id
        ");
        $query_participation_data->execute([
            ':participation_id' => $participation_id,
            ':activite_id' => $activite_id
        ]);
        $current_participation_data = $query_participation_data->fetch(PDO::FETCH_ASSOC);

        if (!$current_participation_data) {
            $error_message .= " Participation non trouvée pour cette activité.";
            // Si la participation n'est pas trouvée, rediriger ou arrêter le script
            // header("Location: gerer_activites.php?msg=" . urlencode("Participation introuvable."));
            // exit();
        }

    } catch (PDOException $e) {
        $error_message .= " Erreur lors de la récupération des données : " . htmlspecialchars($e->getMessage());
    }
}


// --- 2. Traitement de la soumission du formulaire (requête POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activite_id > 0 && $participation_id > 0) {
    // Récupérer les données du formulaire
    $titre = trim($_POST['titre_participant'] ?? '');
    $nb_jours_copies = (int)($_POST['nb_jours_copies'] ?? 0);
    $taux_jounalier_copie = (float)($_POST['taux_journalier'] ?? 0);
    $forfait_participant = (float)($_POST['forfait'] ?? 0); 
    $nb_deplacement = (int)($_POST['nb_jours_deplacement'] ?? 0);
    $frais_deplacement = (float)($_POST['frais_deplacement'] ?? 0);

    // Valider les données (exemple simple, à étoffer)
    if (empty($titre)) {
        $error_message = "Le titre du participant est requis.";
    } elseif ($nb_jours_copies < 0 || $taux_jounalier_copie < 0 || $forfait_participant < 0 || $nb_deplacement < 0 || $frais_deplacement < 0) {
        $error_message = "Les valeurs numériques ne peuvent pas être négatives.";
    } else {
        try {
            $mysqlClient->beginTransaction();

            $participation_update = "UPDATE participations SET
                `titre`                 = :titre,
                `nb_jours_copies`       = :nb_jours_copies,
                `taux_journalier_copie` = :taux_journalier_copie,
                `forfait_participant`   = :forfait_participant,
                `nb_jours_deplacement`  = :nb_deplacement,
                `frais_deplacement`     = :frais_deplacement,
                `date_enregistrement`   = NOW()
            WHERE id = :participation_id AND activite_id = :activite_id"; // Utilisez des placeholders pour tous les critères WHERE

            $participation_to_update = $mysqlClient->prepare($participation_update);
            $participation_to_update->execute([
                ':titre'                 => $titre,
                ':nb_jours_copies'       => $nb_jours_copies,
                ':taux_journalier_copie' => $taux_jounalier_copie,
                ':forfait_participant'   => $forfait_participant, 
                ':nb_deplacement'        => $nb_deplacement,
                ':frais_deplacement'     => $frais_deplacement,
                ':participation_id'      => $participation_id, // Important : passer l'ID ici
                ':activite_id'           => $activite_id       // Important : passer l'ID ici
            ]);

            $mysqlClient->commit();
            $success_message = "La participation a été modifiée avec succès.";
            
            // Recharger les données pour que le formulaire affiche les nouvelles valeurs
            // ou rediriger vers la page de gestion avec le message de succès.
            header("Location: gerer_participants.php?activite_id={$activite_id}&msg=" . urlencode($success_message));
            exit();

        } catch (PDOException $e) {
            $mysqlClient->rollBack();
            $error_message = "Erreur lors de la modification de la participation : " . htmlspecialchars($e->getMessage());
        }
    }
}

// --- Si une erreur grave empêche l'affichage du formulaire, on peut s'arrêter ici ---
if (!empty($error_message) && empty($current_participation_data)) {
    // Dans ce cas, nous affichons l'erreur et n'affichons pas le formulaire
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la participation de 
        <?php 
        if (isset($current_participation_data['prenom_participant']) && isset($current_participation_data['nom_participant'])) {
            echo htmlspecialchars($current_participation_data['prenom_participant'] . ' ' . $current_participation_data['nom_participant']);
        } else if (isset($current_participation_data['nom_participant'])) {
             echo htmlspecialchars($current_participation_data['nom_participant']);
        } else {
            echo "Participant inconnu";
        }
        ?> 
        à l'Activité : <?php echo $activity_name; ?>
    </title>
    <link rel="stylesheet" href="class1.css">
    <style>
        /* (Conservez les styles CSS pour les formulaires, messages, etc. du précédent exemple) */
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Pour inclure padding et border dans la largeur */
        }
        .form-group button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .message-erreur {
            color: red;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #ffe0e0;
            border: 1px solid #ffb3b3;
            border-radius: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .message-succes {
            color: green;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #e0ffe0;
            border: 1px solid #b3ffb3;
            border-radius: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
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
                <li><a href="accueil.html">Accueil Public</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Activités</a>
                    <div class="dropdown-content">
                        <a href="creer_activite.php">Créer Activité</a>
                        <a href="gerer_activites.php">Gérer Activité</a>
                    </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Documents</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="login.html">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="add-participant-form-section">
            <h2>Modifier la participation de 
                <?php 
                if (isset($current_participation_data['prenom_participant']) && isset($current_participation_data['nom_participant'])) {
                    echo htmlspecialchars($current_participation_data['prenom_participant'] . ' ' . $current_participation_data['nom_participant']);
                } else if (isset($current_participation_data['nom_participant'])) {
                     echo htmlspecialchars($current_participation_data['nom_participant']);
                } else {
                    echo "Participant inconnu";
                }
                ?> 
                à l'Activité : <?php echo $activity_name; ?>
            </h2>

            <?php if (!empty($error_message)): ?>
                <p class="message-erreur"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p class="message-succes"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if ($activite_id === 0 || $participation_id === 0 || empty($current_participation_data)): ?>
                <p class="message-erreur">Une erreur est survenue ou la participation n'existe pas. Veuillez retourner à la <a href="gerer_activites.php">liste des activités</a>.</p>
            <?php else: ?>
                <form action="modifier_participation.php?activite_id=<?php echo $activite_id; ?>&id=<?php echo $participation_id; ?>" method="post">
                    <div class="form-group">
                        <label for="titre_participant">Titre du participant :</label>
                        <input type="text" id="titre_participant" name="titre_participant" value="<?php echo htmlspecialchars($current_participation_data['titre'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="taux_journalier">Taux Journalier Alloué :</label>
                        <input type="number" id="taux_journalier" name="taux_journalier" value="<?php echo htmlspecialchars($current_participation_data['taux_journalier_copie'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label for="forfait">Forfait Alloué :</label>
                        <input type="number" id="forfait" name="forfait" value="<?php echo htmlspecialchars($current_participation_data['forfait_participant'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label for="frais_deplacement">Frais de Déplacement Alloués :</label>
                        <input type="number" id="frais_deplacement" name="frais_deplacement" value="<?php echo htmlspecialchars($current_participation_data['frais_deplacement'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_deplacement">Nombre de Jours de Déplacement :</label>
                        <input type="number" id="nb_jours_deplacement" name="nb_jours_deplacement" value="<?php echo htmlspecialchars($current_participation_data['nb_jours_deplacement'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_copies">Nombre de Jours/Copies :</label>
                        <input type="number" id="nb_jours_copies" name="nb_jours_copies" value="<?php echo htmlspecialchars($current_participation_data['nb_jours_copies'] ?? 0); ?>">
                    </div>

                    <div class="form-group">
                        <button type="submit">Modifier la Participation</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer>
    </footer>
</body>
</html>