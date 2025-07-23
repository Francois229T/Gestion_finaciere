<?php

require_once 'db.php'; 

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$activity_name = '';
$acteurs_list = [];
$banques_list = [];
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
        $stmt_participants = $mysqlClient->prepare("
    SELECT
    p.id,
    CASE
        WHEN p.type = 'individu' THEN pp.nom
        WHEN p.type = 'personne_morale' THEN pm.denomination
        ELSE NULL
    END AS nom_participant,
    CASE
        WHEN p.type = 'individu' THEN pp.prenom
        ELSE NULL
    END AS prenom_participant,
    p.type,
    cb.id_compte AS id_compte
    FROM
        participants p
    LEFT JOIN
        personnes_physiques pp ON p.id = pp.participant_id
    LEFT JOIN
        personnes_morales pm ON p.id = pm.participant_id
    LEFT JOIN
        comptes_bancaires cb ON p.id = cb.participant_id
    WHERE
        -- Condition pour l'activité si nécessaire (si id_participant n'est pas suffisant pour la lier)
        
        p.id NOT IN (SELECT participant_id FROM participations WHERE activite_id = :activite_id)
        -- Ou toute autre condition pertinente pour filtrer les participants
    ORDER BY
        nom_participant, prenom_participant;
        ");
        $stmt_participants->execute([':activite_id' => $activite_id]);
        while ($row = $stmt_participants->fetch(PDO::FETCH_ASSOC)) {
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
    $titre_participant      = trim($_POST['titre_participant'] ?? '');
    $current_bank           = trim($_POST['current_bank'] ?? '');

    $participant_parts = explode('_', $participant_full_id);
    $type_participant = $participant_parts[0] ?? '';

    $participant_id = (int)($participant_parts[1] ?? 0);
    $compte_id = (int)($participant_parts[2] ?? 0);

    // Basic validation
    if ($participant_id === 0 || empty($type_participant)) {
        $error_message = "Veuillez sélectionner un participant valide.";
    } else {
        try {
        // Récupérer les personnes physiques
        $stmt_data_for_acteurs = $mysqlClient->prepare("
                SELECT
                    p.id AS participant_main_id,
                    CASE
                        WHEN p.type = 'individu' THEN pp.nom
                        WHEN p.type = 'personne_morale' THEN pm.denomination
                        ELSE NULL
                    END AS nom_participant,
                    CASE
                        WHEN p.type = 'individu' THEN pp.prenom
                        ELSE NULL
                    END AS prenom_participant,
                    cb.banque AS compte_bancaire_choisi,
                    cb.rib_pdf_path AS rib_compte,
                    cb.numero_compte AS numero_compte  
                    FROM participants p
                LEFT JOIN
                    personnes_physiques pp ON p.id = pp.participant_id
                LEFT JOIN
                    personnes_morales pm ON p.id = pm.participant_id
                JOIN
                    comptes_bancaires cb ON p.id = cb.participant_id
                WHERE
                    p.id = :participant_id AND cb.banque = :current_bank_name AND cb.id_compte = :compte_id
            ");
                
            $stmt_data_for_acteurs->execute([
                ':participant_id'     => $participant_id,
                ':current_bank_name'  => $current_bank,
                ':compte_id'          => $compte_id // Utilisez le compte_id pour être plus spécifique
            ]);

            $acteur_data = $stmt_data_for_acteurs->fetch(PDO::FETCH_ASSOC);

            if (!$acteur_data) {
                $error_message .= "Impossible de trouver les données complètes du participant ou du compte bancaire sélectionné.";
            } else {
                // --- Étape 2 : Commencer la transaction pour l'insertion dans 'participations' et 'acteurs'
                $mysqlClient->beginTransaction();

                // Insertion dans la table 'participations' (Votre code existant pour les participations)
                $sql_participations = "INSERT INTO participations (
                                    activite_id,
                                    participant_id,
                                    compte_id,
                                    titre,
                                    type_participant,
                                    taux_journalier_copie,
                                    forfait_participant,
                                    frais_deplacement,
                                    nb_jours_deplacement,
                                    nb_jours_copies,
                                    date_enregistrement
                                ) VALUES (
                                    :activite_id,
                                    :participant_id,
                                    :compte_id,
                                    :titre,
                                    :type_participant,
                                    :taux_journalier,
                                    :forfait,
                                    :frais_deplacement,
                                    :nb_jours_deplacement,
                                    :nb_jours_copies,
                                    NOW()
                                )";

                $stmt_participations = $mysqlClient->prepare($sql_participations);
                $stmt_participations->execute([
                    ':activite_id'          => $activite_id,
                    ':participant_id'       => $participant_id,
                    ':type_participant'     => $type_participant,
                    ':compte_id'            => !empty($compte_id) ? (int) $compte_id : NULL,
                    ':titre'                => $titre_participant,
                    ':taux_journalier'      => !empty($taux_journalier) ? (float)$taux_journalier : NULL,
                    ':forfait'              => !empty($forfait) ? (float)$forfait : NULL,
                    ':frais_deplacement'    => !empty($frais_deplacement) ? (float)$frais_deplacement : NULL,
                    ':nb_jours_deplacement' => !empty($nb_jours_deplacement) ? (int)$nb_jours_deplacement : NULL,
                    ':nb_jours_copies'      => !empty($nb_jours_copies) ? (int)$nb_jours_copies : NULL
                ]);

                // --- Étape 3 : Insertion dans la table 'acteurs'
                // Utilisez les données récupérées dans $acteur_data
                $sql_acteurs = "INSERT INTO acteurs (
                                nom,
                                prenom,
                                compte_bancaire_choisi,
                                rib_compte,
                                titre,
                                id_activite,
                                numero_compte)
                                VALUES (
                                :nom,
                                :prenom,
                                :compte_bancaire_choisi,
                                :rib_compte,
                                :titre,
                                :id_activite,
                                :numero_compte)";

                $stmt_acteurs_insert = $mysqlClient->prepare($sql_acteurs); // Utilisez $mysqlClient ici !

                $stmt_acteurs_insert->execute([
                    ':nom'                     => $acteur_data['nom_participant'],
                    ':prenom'                  => $acteur_data['prenom_participant'],
                    ':compte_bancaire_choisi'  => $acteur_data['compte_bancaire_choisi'], // C'est le nom de la banque
                    ':rib_compte'              => $acteur_data['rib_compte'],
                    ':numero_compte'           => $acteur_data['numero_compte'],
                    ':titre'                   => $titre_participant, // Titre vient du formulaire HTML
                    ':id_activite'             => $activite_id
                ]);

                $mysqlClient->commit();
                $success_message = "Participant ajouté à l'activité et enregistré dans la table acteurs avec succès !";

                // Redirection après succès
                header("Location: gerer_participants.php?activite_id={$activite_id}&msg=" . urlencode($success_message));
                exit();
            }

        } catch (PDOException $e) {
            $mysqlClient->rollBack();
            $error_message = "Erreur lors de l'ajout (transaction) : " . htmlspecialchars($e->getMessage());
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
                        <a href="gerer_activite.php">Gérer Activité</a>
                    </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Paiements</a></li>
                <li><a href="#">Documents</a></li>
                <li><a href="dashboard_financier.html" class="active">Tableau de Bord</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
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
                <p class="message-erreur">Veuillez retourner à la <a href="gerer_activites.php">liste des activités</a>.</p>
            <?php else: ?>
                <form action="ajouter_participant.php?activite_id=<?php echo $activite_id; ?>" method="post">
                    <div class="form-group">
                        <label for="participant_id">Sélectionner un participant :</label>
                        <select id="participant_id" name="participant_id" required>
                            <option value="">-- Choisir un participant --</option>
                            <?php if (empty($participants_list)): ?>
                                <option value="" disabled>Aucun participant disponible. Veuillez en créer un d'abord.</option>
                            <?php else: ?>
                                <?php foreach ($participants_list as $participant): ?>
                                    <option value="<?php echo htmlspecialchars($participant['type'] . '_' . $participant['id'] . '_' . $participant['id_compte'] ?? '0'); ?>">
                                        <?php echo htmlspecialchars($participant['prenom_participant'] . ' ' . $participant['nom_participant']); ?> (<?php echo htmlspecialchars($participant['type']); ?>)
                                    </option>                           
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="titre_participant"> Titre du participant :</label>
                        <input type="text"  id="titre_participant" name="titre_participant">
                    </div>
                    <div class="form-group">
                        <label for="taux_journalier">Taux Journalier Alloué :</label>
                        <input type="number"  id="taux_journalier" name="taux_journalier">
                    </div>
                    <div class="form-group">
                        <label for="forfait">Forfait Alloué :</label>
                        <input type="number"  id="forfait" name="forfait">
                    </div>
                    <div class="form-group">
                        <label for="frais_deplacement">Frais de Déplacement Alloués :</label>
                        <input type="number"  id="frais_deplacement" name="frais_deplacement">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_deplacement">Nombre de Jours de Déplacement :</label>
                        <input type="number" id="nb_jours_deplacement" name="nb_jours_deplacement">
                    </div>
                    <div class="form-group">
                        <label for="nb_jours_copies">Nombre de Jours/Copies :</label>
                        <input type="number" id="nb_jours_copies" name="nb_jours_copies">
                    </div>

                   <div class="form-group">
                        <label for="current_bank"> Choisissez le compte bancaire à utiliser :</label>
                        <select id="current_bank" name="current_bank" required>
                            <option value="">-- Choisir une banque --</option>
                            <?php if (!empty($error_message)): ?>
                                <option value="" disabled class="error-option"><?php echo $error_message; ?></option>
                            <?php elseif (empty($banques_list)): ?>
                                <option value="" disabled>Vous n'avez aucune banque enregistrée. Veuillez fournir vos comptes bancaires d'abord.</option>
                            <?php else: ?>
                                <?php foreach ($banques_list as $banque_name): ?>
                                    <option value="<?php echo htmlspecialchars($banque_name); ?>">
                                        <?php echo htmlspecialchars($banque_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
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
<script>
        document.addEventListener('DOMContentLoaded', function() {
            const participantSelect = document.getElementById('participant_id');
            const bankSelect = document.getElementById('current_bank');

            // Fonction pour charger les banques via AJAX
            function loadBanksForParticipant(participantId) {
                // Efface les options existantes et ajoute une option de chargement
                bankSelect.innerHTML = '<option value="">-- Chargement des banques --</option>';

                if (!participantId || participantId === '0') {
                    // Si pas de participant sélectionné, réinitialise avec le message par défaut
                    bankSelect.innerHTML = '<option value="">-- Choisir une banque --</option><option value="" disabled>Veuillez sélectionner un participant d\'abord.</option>';
                    return;
                }

                // Faites une requête AJAX vers le nouveau script PHP
                // Assurez-vous que le chemin est correct si get_banks_for_participant.php n'est pas dans le même dossier
                fetch('get_banks_for_participant.php?participant_id=' + participantId)
                    .then(response => {
                        if (!response.ok) {
                            // Gérer les erreurs de réseau/serveur (ex: 404, 500)
                            throw new Error('Erreur réseau ou serveur: ' + response.statusText);
                        }
                        return response.json(); // Parsez la réponse JSON
                    })
                    .then(data => {
                        // Réinitialise la liste déroulante avant d'ajouter de nouvelles options
                        bankSelect.innerHTML = '<option value="">-- Choisir une banque --</option>'; 
                        if (data.length > 0) {
                            data.forEach(bankName => {
                                const option = document.createElement('option');
                                option.value = bankName; // La valeur de l'option est le nom de la banque
                                option.textContent = bankName; // Le texte affiché est le nom de la banque
                                bankSelect.appendChild(option);
                            });
                        } else {
                            // Si aucune banque n'est trouvée pour ce participant
                            bankSelect.innerHTML += '<option value="" disabled>Aucune banque enregistrée pour ce participant.</option>';
                        }
                    })
                    .catch(error => {
                        // Gérer les erreurs JavaScript (ex: problème de parsing JSON, erreur réseau)
                        console.error('Erreur lors du chargement des banques:', error);
                        bankSelect.innerHTML = '<option value="">-- Choisir une banque --</option><option value="" disabled>Erreur de chargement des banques.</option>';
                    });
            }

            // Écouteur d'événement sur le changement de participant
            // Lorsque l'utilisateur sélectionne un participant dans la première liste
            participantSelect.addEventListener('change', function() {
                const selectedValue = this.value; // Ceci contient 'type_ID_idcompte' (ex: 'individu_1_123')
                const parts = selectedValue.split('_');
                const participantId = parts[1]; // Récupère l'ID réel du participant (le 2ème élément après split)

                loadBanksForParticipant(participantId); // Appelle la fonction pour charger les banques
            });

            // IMPORTANT : Charger les banques au chargement initial de la page si un participant est déjà sélectionné
            // Cela arrive si la page est rechargée avec un participant déjà choisi (par ex. après une erreur de validation)
            if (participantSelect.value && participantSelect.value !== '') {
                const initialSelectedValue = participantSelect.value;
                const initialParts = initialSelectedValue.split('_');
                const initialParticipantId = initialParts[1];
                if (initialParticipantId && initialParticipantId !== '0') {
                    loadBanksForParticipant(initialParticipantId);
                }
            } else {
                // Assurez-vous que le message par défaut est affiché si rien n'est sélectionné au début
                bankSelect.innerHTML = '<option value="">-- Choisir une banque --</option><option value="" disabled>Veuillez sélectionner un participant d\'abord.</option>';
            }

        });
    </script>
</body>
</html>