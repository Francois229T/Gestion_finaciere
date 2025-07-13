<?php

require_once 'db.php'; 

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$activity_name = '';
$participants_list = [];
$error_message = '';
$success_message = '';

// --- 1. Récupérer les détails de l'activité ---
if ($activite_id > 0) {
    try {
        $stmt_activity = $mysqlClient->prepare("SELECT nom FROM activites WHERE id = :id");
        $stmt_activity->execute([':id' => $activite_id]);
        $activity_data = $stmt_activity->fetch(PDO::FETCH_ASSOC);
        if ($activity_data) {
            $activity_name = htmlspecialchars($activity_data['nom']);
        } else {
            $error_message = "Activité non trouvée.";
            $activite_id = 0; // Réinitialiser pour éviter d'autres opérations incorrectes
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération de l'activité : " . htmlspecialchars($e->getMessage());
    }
}

// --- 2. Récupérer la liste de tous les participants (pour le selectbox) ---
if ($activite_id > 0) { // On ne récupère les participants que si l'activité est valide
    try {
        // Récupérer les personnes physiques
        $stmt_physiques = $mysqlClient->query("SELECT id, nom_complet AS nom_display, 'physique' AS type FROM personnes_physiques ORDER BY nom_complet");
        while ($row = $stmt_physiques->fetch(PDO::FETCH_ASSOC)) {
            $participants_list[] = $row;
        }

        // Récupérer les personnes morales
        $stmt_morales = $mysqlClient->query("SELECT id, raison_sociale AS nom_display, 'morale' AS type FROM personnes_morales ORDER BY raison_sociale");
        while ($row = $stmt_morales->fetch(PDO::FETCH_ASSOC)) {
            $participants_list[] = $row;
        }
    } catch (PDOException $e) {
        $error_message .= " Erreur lors de la récupération des participants : " . htmlspecialchars($e->getMessage());
    }
}


// --- 3. Gérer la soumission du formulaire d'ajout de participant à l'activité ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $activite_id > 0) {
    // Récupération et validation des données du formulaire
    $participant_full_id    = trim($_POST['participant_id'] ?? ''); // Format 'type_ID' (ex: 'physique_1', 'morale_5')
    $taux_journalier        = trim($_POST['taux_journalier'] ?? '');
    $forfait                = trim($_POST['forfait'] ?? '');
    $frais_deplacement      = trim($_POST['frais_deplacement'] ?? '');
    $nb_jours_deplacement   = trim($_POST['nb_jours_deplacement'] ?? '');
    $nb_jours_copies        = trim($_POST['nb_jours_copies'] ?? '');

    $participant_parts = explode('_', $participant_full_id);
    $type_participant = $participant_parts[0] ?? '';
    $participant_id = (int)($participant_parts[1] ?? 0);

    // Basic validation
    if ($participant_id === 0 || empty($type_participant)) {
        $error_message = "Veuillez sélectionner un participant valide.";
    } else {
        try {
            $mysqlClient->beginTransaction();

            $sql = "INSERT INTO participations (
                        activite_id,
                        participant_id,
                        type_participant,
                        taux_journalier_alloue,
                        forfait_alloue,
                        frais_deplacement_alloue,
                        nb_jours_deplacement_alloue,
                        nb_jours_copies_alloue,
                        date_enregistrement
                    ) VALUES (
                        :activite_id,
                        :participant_id,
                        :type_participant,
                        :taux_journalier,
                        :forfait,
                        :frais_deplacement,
                        :nb_jours_deplacement,
                        :nb_jours_copies,
                        NOW()
                    )";

            $stmt = $mysqlClient->prepare($sql);

            $stmt->execute([
                ':activite_id'          => $activite_id,
                ':participant_id'       => $participant_id,
                ':type_participant'     => $type_participant,
                ':taux_journalier'      => !empty($taux_journalier) ? (float)$taux_journalier : NULL,
                ':forfait'              => !empty($forfait) ? (float)$forfait : NULL,
                ':frais_deplacement'    => !empty($frais_deplacement) ? (float)$frais_deplacement : NULL,
                ':nb_jours_deplacement' => !empty($nb_jours_deplacement) ? (int)$nb_jours_deplacement : NULL,
                ':nb_jours_copies'      => !empty($nb_jours_copies) ? (int)$nb_jours_copies : NULL
            ]);

            $mysqlClient->commit();
            $success_message = "Participant ajouté à l'activité avec succès !";

            // Après l'ajout réussi, redirigez l'utilisateur vers la page de gestion des participants
            // pour voir la liste mise à jour.
            header("Location: gerer_participants.php?activite_id={$activite_id}&msg=" . urlencode($success_message));
            exit();

        } catch (PDOException $e) {
            $mysqlClient->rollBack();
            $error_message = "Erreur lors de l'ajout du participant : " . htmlspecialchars($e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Participant à l'Activité : <?php echo $activity_name; ?></title>
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
        </header>

    <main>
        <section class="add-participant-form-section">
            <h2>Ajouter un Participant à l'Activité : <?php echo $activity_name; ?></h2>

            <?php if (!empty($error_message)): ?>
                <p class="message-erreur"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p class="message-succes"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if ($activite_id === 0): ?>
                <p class="message-erreur">Une erreur est survenue. L'activité n'a pas été spécifiée.</p>
                <p class="message-erreur">Veuillez retourner à la <a href="lister_activites.php">liste des activités</a>.</p>
            <?php else: ?>
                <form action="ajouter_participant_activite.php?activite_id=<?php echo $activite_id; ?>" method="post">
                    <div class="form-group">
                        <label for="participant_id">Sélectionner un participant :</label>
                        <select id="participant_id" name="participant_id" required>
                            <option value="">-- Choisir un participant --</option>
                            <?php if (empty($participants_list)): ?>
                                <option value="" disabled>Aucun participant disponible. Veuillez en créer un d'abord.</option>
                            <?php else: ?>
                                <?php foreach ($participants_list as $participant): ?>
                                    <option value="<?php echo htmlspecialchars($participant['type'] . '_' . $participant['id']); ?>">
                                        <?php echo htmlspecialchars($participant['nom_display']); ?> (<?php echo htmlspecialchars($participant['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="taux_journalier">Taux Journalier Alloué :</label>
                        <input type="number" step="0.01" id="taux_journalier" name="taux_journalier">
                    </div>
                    <div class="form-group">
                        <label for="forfait">Forfait Alloué :</label>
                        <input type="number" step="0.01" id="forfait" name="forfait">
                    </div>
                    <div class="form-group">
                        <label for="frais_deplacement">Frais de Déplacement Alloués :</label>
                        <input type="number" step="0.01" id="frais_deplacement" name="frais_deplacement">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_deplacement">Nombre de Jours de Déplacement :</label>
                        <input type="number" id="nb_jours_deplacement" name="nb_jours_deplacement">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_copies">Nombre de Jours Copies :</label>
                        <input type="number" id="nb_jours_copies" name="nb_jours_copies">
                    </div>

                    <div class="form-group">
                        <button type="submit">Ajouter le Participant</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        </footer>
</body>
</html>