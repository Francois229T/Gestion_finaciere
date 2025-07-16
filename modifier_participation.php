<?php
require_once 'db.php';

$error_message = [];
$participant_info = [];
//$compte_id = trim($_POST['']);
//$type_participant  = trim($_POST['']);
$titre = trim($_POST['titre_participant'] ?? '');
$nb_jours_copies = trim($_POST['nb_jours_copies'] ?? '');
$taux_jounalier_copie = trim($_POST['taux_journalier'] ?? '');
$forfait_participant = trim($_POST['forfait'] ?? ''); 
$nb_deplacement = trim($_POST['nb_jours_deplacement'] ?? '');
$frais_deplacement = trim($_POST['frais_deplacement'] ?? '');

if (isset($_GET['action']) && $_GET['action'] === 'update_participation' && isset($_GET['activite_id'])) {
    $activite_id = (int)$_GET['activite_id'] ?? '0';
}
$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
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
} if (isset($_GET['action']) || $_GET['action'] === 'update_participation' || isset($_GET['id'])) {
    $participation_id = (int)$_GET['id'];
 try {
    $query = $mysqlClient->prepare("
    SELECT ptions.id, 
	CASE
        WHEN ptions.type_participant = 'individu' THEN pp.nom
        WHEN ptions.type_participant = 'personne_morale' THEN pm.denomination
        ELSE NULL
	END AS nom_participant,
    CASE
        WHEN ptions.type_participant = 'individu' THEN pp.prenom
        ELSE NULL
    END AS prenom_participant
    FROM participations ptions
    LEFT JOIN personnes_physiques pp on ptions.participant_id = pp.participant_id
    LEFT JOIN personnes_morales pm on ptions.participant_id = pm.participant_id
    WHERE ptions.id = :participation_id");
    $query->execute([':participation_id' => $participation_id]);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $participant_info[] = $row;
        }
 } catch (PDOException $e) {
    $error_message .= " Erreur lors de la récupération des informations de participation du participant : " . htmlspecialchars($e->getMessage());
    }
}
try {
    $mysqlClient->beginTransaction();
    $participation_update = "UPDATE participations SET 
    'titre'                 = :titre,
    'nb_jours_copies'       = :nb_jours_copies,
    'taux_journalier_copie' = :taux_jounalier_copie,
    'forfait_participant'   = :forfait_participant,
    'nb_deplacement'        = :nb_deplacement,
    'frais_deplacement'     = :frais_deplacement,
    'date_enregistrement'   = NOW()
     WHERE participation.id = $participation_id AND activite_id = $activite_id ";
    $participation_to_update = $mysqlClient->prepare($participation_update);
    $participation_to_update->execute([
    ':titre'                   => $titre,
    ':nb_jours_copies'         =>  $nb_jours_copies,
    ':taux_journalier_copie'   =>  $taux_jounalier_copie,
    ':forfait_participant'     => $forfait_participant, 
    ':nb_deplacement'          =>   $nb_deplacement,
    ':frais_deplacement'       =>   $frais_deplacement
    ]);
    $mysqlClient->commit();
    $success_message = "La participation a été modifiée avec succès.";
    // Rediriger pour éviter la re-soumission du DELETE
    header("Location: gerer_participants.php?activite_id={$activite_id}&msg=" . urlencode($success_message));
    exit();
} catch (PDOException $e) {
    $mysqlClient->rollBack();
        $error_message = "Erreur lors de la modification de la participation : " . htmlspecialchars($e->getMessage());
}
?> 

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la participation de <?php echo htmlspecialchars($participants_info['prenom_participant'] . ' ' . $participants_info['nom_participant'])  ?> à l'Activité : <?php echo $activity_name; ?></title>
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
             <?php foreach ($participant_info as $participant) : ?> 
            <h2>Modifier la participation de <?php echo htmlspecialchars($participant['prenom_participant'] . ' ' . $participant['nom_participant'])  ?> à l'Activité : <?php echo $activity_name; ?>
             <?php endforeach; ?></title></h2>

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