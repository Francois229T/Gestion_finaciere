<?php
require_once 'db.php';

$compte_id = trim($_POST['']);
$type_participant  = trim($_POST['']);
$titre = trim($_POST['']);
$nb_jours_copies = trim($_POST['']);
$taux_jounalier_copie = trim($_POST['']);
$forfait_participant = trim($_POST['']); 
$nb_deplacement = trim($_POST['']);
$frais_deplacement = trim($_POST['']);
$prenom_participant;
$nom_participant;

if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id'])) {
    $activite_id = (int)$_GET['id'] ?? '0';
    
    $participation_id = (int)$_GET['participation_id'] ?? '0';

    $participation_update = "UPDATE participations SET 
    'compte_id' = ':compte_id',
    'type_participant' = ':type_participant' ,
    'titre' = ':titre', 
    'nb_jours_copies' = ':nb_jours_copies',
    'taux_journalier_copie' = ':taux_jounalier_copie',
    'forfait_participant' = ':forfait_participant',
    'nb_deplacement' = ':nb_deplacement',
    'frais_deplacement' = ':frais_deplacement',
    'date_enregistrement' = NOW()
     WHERE participation.id = $participation_id AND activite_id = $activite_id ";
    $participation_to_update = $mysqlClient->prepare($participation_update);
    $participation_to_update->execute([
    ':compte_id'  =>  $compte_id,
    ':type_participant'  => $type_participant,
    ':titre' => $titre,
    ':nb_jours_copies'  =>  $nb_jours_copies,
    ':taux_journalier_copie'   =>  $taux_jounalier_copie,
    ':forfait_participant'  => $forfait_participant, 
    ':nb_deplacement'   =>   $nb_deplacement,
    ':frais_deplacement'   =>   $frais_deplacement
    ]);
}
?> 

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la participation de <?php echo htmlspecialchars($prenom_participant . ' ' . $nom_participant)  ?> à l'Activité : <?php echo $activity_name; ?></title>
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
                <li><a href="login.html">Déconnexion</a></li>
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
                                        <?php echo htmlspecialchars($participant['nom_participant']); ?> (<?php echo htmlspecialchars($participant['type']); ?>)
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