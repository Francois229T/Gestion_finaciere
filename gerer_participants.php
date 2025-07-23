<?php

require_once 'db.php';

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$activity_name = '';
$current_participants = [];
$error_message = '';
$success_message = '';
$search_query = isset($_GET['search_participant']) ? trim($_GET['search_participant']) : '';


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

// Récupérer un message de succès depuis la redirection (si une suppression a eu lieu)
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}


// --- 3. Récupérer les participants déjà liés à cette activité pour affichage (avec recherche) ---
if ($activite_id > 0) {
    try {
        $sql_participants = "
            SELECT p.id, p.type_participant, p.participant_id,
                   CASE
                       WHEN p.type_participant = 'individu' THEN CONCAT(pp.prenom,' ',pp.nom)
                       WHEN p.type_participant = 'personne_morale' THEN pm.denomination
                       ELSE 'Inconnu'
                   END AS nom_participant,
                   p.taux_journalier_copie, p.forfait_participant, p.frais_deplacement,
                   p.nb_jours_deplacement, p.nb_jours_copies, p.titre,
                   cb.rib_pdf_path  -- Assurez-vous d'avoir cette colonne dans votre table 'participations'
            FROM participations p
            LEFT JOIN personnes_physiques pp ON p.participant_id = pp.participant_id AND p.type_participant = 'individu'
            LEFT JOIN personnes_morales pm ON p.participant_id = pm.participant_id AND p.type_participant = 'personne_morale'
            LEFT JOIN comptes_bancaires cb ON cb.id_compte = p.compte_id
            WHERE p.activite_id = :activite_id
        ";

        // Ajouter la clause de recherche si une requête est présente
        if (!empty($search_query)) {
            $sql_participants .= " AND (
                CASE
                    WHEN p.type_participant = 'individu' THEN CONCAT(pp.prenom,' ',pp.nom)
                    WHEN p.type_participant = 'personne_morale' THEN pm.denomination
                    ELSE 'Inconnu'
                END LIKE :search_query
            )";
        }

        $sql_participants .= " ORDER BY nom_participant";

        $stmt_current_participants = $mysqlClient->prepare($sql_participants);
        $stmt_current_participants->bindValue(':activite_id', $activite_id);

        if (!empty($search_query)) {
            $stmt_current_participants->bindValue(':search_query', '%' . $search_query . '%');
        }

        $stmt_current_participants->execute();
        $current_participants = $stmt_current_participants->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message .= " Erreur lors de la récupération des participants actuels : " . htmlspecialchars($e->getMessage());
    }
}

?>

<?php

require_once 'db.php';

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$activity_name = '';
$current_participants = [];
$error_message = '';
$success_message = '';
$search_query = isset($_GET['search_participant']) ? trim($_GET['search_participant']) : '';


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

// Récupérer un message de succès depuis la redirection (si une suppression a eu lieu)
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}


// --- 3. Récupérer les participants déjà liés à cette activité pour affichage (avec recherche) ---
if ($activite_id > 0) {
    try {
        $sql_participants = "
            SELECT p.id, p.type_participant, p.participant_id,
                    CASE
                        WHEN p.type_participant = 'individu' THEN CONCAT(pp.prenom,' ',pp.nom)
                        WHEN p.type_participant = 'personne_morale' THEN pm.denomination
                        ELSE 'Inconnu'
                    END AS nom_participant,
                    p.taux_journalier_copie, p.forfait_participant, p.frais_deplacement,
                    p.nb_jours_deplacement, p.nb_jours_copies, p.titre,
                    cb.rib_pdf_path
            FROM participations p
            LEFT JOIN personnes_physiques pp ON p.participant_id = pp.participant_id AND p.type_participant = 'individu'
            LEFT JOIN personnes_morales pm ON p.participant_id = pm.participant_id AND p.type_participant = 'personne_morale'
            LEFT JOIN comptes_bancaires cb ON cb.id_compte = p.compte_id
            WHERE p.activite_id = :activite_id
        ";

        // Ajouter la clause de recherche si une requête est présente
        if (!empty($search_query)) {
            $sql_participants .= " AND (
                CASE
                    WHEN p.type_participant = 'individu' THEN CONCAT(pp.prenom,' ',pp.nom)
                    WHEN p.type_participant = 'personne_morale' THEN pm.denomination
                    ELSE 'Inconnu'
                END LIKE :search_query
            )";
        }

        $sql_participants .= " ORDER BY nom_participant";

        $stmt_current_participants = $mysqlClient->prepare($sql_participants);
        $stmt_current_participants->bindValue(':activite_id', $activite_id);

        if (!empty($search_query)) {
            $stmt_current_participants->bindValue(':search_query', '%' . $search_query . '%');
        }

        $stmt_current_participants->execute();
        $current_participants = $stmt_current_participants->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message .= " Erreur lors de la récupération des participants actuels : " . htmlspecialchars($e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants pour l'Activité : <?php echo $activity_name; ?></title>
    <link rel="stylesheet" href="class1.css">
    <style>
        /* Styles CSS globaux pour le corps et la structure */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            font-size: 0.85em; /* Taille de police générale réduite */
        }

        /* En-tête (Header) */
        header {
            background-color: #004d40; /* Vert foncé */
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            font-size: 0.9em; /* Police de l'en-tête légèrement plus petite */
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 15px; /* Espace entre le logo et le texte */
        }

        #logo {
            height: 60px; /* Taille du logo réduite */
            width: auto;
        }

        .site-branding h1 {
            margin: 0;
            font-size: 1.5em; /* Taille du titre réduite */
        }

        .site-branding p {
            margin: 0;
            font-size: 0.8em; /* Taille du sous-titre réduite */
            opacity: 0.9;
        }

        .header-utility {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-bar {
            display: flex;
        }

        .search-bar input[type="search"] {
            padding: 8px; /* Réduction du padding */
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 0.8em; /* Taille de police réduite */
        }

        .search-bar button {
            padding: 8px 12px; /* Réduction du padding */
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 0.8em; /* Taille de police réduite */
        }

        .utility-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 15px; /* Réduction de l'espace */
        }

        .utility-nav a {
            color: white;
            text-decoration: none;
            font-size: 0.8em; /* Taille de police réduite */
        }

        .main-nav {
            background-color: #00695c; /* Vert légèrement plus clair */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .main-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        .main-nav li a {
            display: block;
            color: white;
            text-align: center;
            padding: 10px 15px; /* Réduction du padding */
            text-decoration: none;
            transition: background-color 0.3s ease;
            font-size: 0.9em; /* Taille de police légèrement réduite */
        }

        .main-nav li a:hover, .main-nav .dropdown:hover .dropbtn {
            background-color: #004d40;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-size: 0.85em; /* Taille de police réduite */
        }

        .dropdown-content a:hover {
            background-color: #ddd;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Contenu principal */
        main {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #004d40;
            margin-bottom: 20px;
            font-size: 1.6em; /* Taille du titre de section réduite */
        }

        h3 {
            text-align: center;
            color: #0056b3; /* Un bleu pour les sous-titres */
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        /* Messages d'erreur/succès */
        .message-erreur {
            color: red;
            text-align: center;
            margin: 10px auto; /* Marge réduite */
            padding: 8px; /* Padding réduit */
            background-color: #ffe0e0;
            border: 1px solid #ffb3b3;
            border-radius: 5px;
            width: 90%; /* Ajustement de la largeur */
            font-size: 0.8em; /* Taille de police réduite */
        }
        .message-succes {
            color: green;
            text-align: center;
            margin: 10px auto; /* Marge réduite */
            padding: 8px; /* Padding réduit */
            background-color: #e0ffe0;
            border: 1px solid #b3ffb3;
            border-radius: 5px;
            width: 90%; /* Ajustement de la largeur */
            font-size: 0.8em; /* Taille de police réduite */
        }

        .no-participants {
            text-align: center;
            color: #555;
            margin: 20px 0; /* Marge réduite */
            font-style: italic;
            font-size: 0.8em; /* Taille de police réduite */
        }

        /* Bouton "Ajouter un nouveau participant" */
        p a.btn-ajouter {
            display: block; /* Prend toute la largeur disponible */
            width: fit-content; /* S'adapte au contenu */
            margin: 15px auto; /* Centre le bouton */
            padding: 8px 15px; /* Padding réduit */
            background-color: #28a745; /* Vert */
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em; /* Taille de police légèrement réduite */
            text-align: center;
            transition: background-color 0.3s ease;
        }
        p a.btn-ajouter:hover {
            background-color: #218838;
        }

        /* Formulaire de recherche */
        .search-form {
            display: flex;
            justify-content: center;
            margin: 15px auto; /* Marge réduite */
            width: 98%; /* Utilise plus de largeur */
            gap: 5px; /* Espacement réduit */
        }

        .search-form input[type="search"] {
            flex-grow: 1;
            padding: 6px; /* Padding réduit */
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 0.8em; /* Taille de police réduite */
        }

        .search-form button, .search-form a.btn-modifier { /* Cible aussi le lien "Effacer Recherche" */
            padding: 6px 10px; /* Padding réduit */
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8em; /* Taille de police réduite */
            text-decoration: none; /* Pour le lien */
            display: inline-flex; /* Pour centrer le texte du lien */
            align-items: center;
            justify-content: center;
        }

        .search-form button:hover, .search-form a.btn-modifier:hover {
            opacity: 0.9;
        }

        .search-form a.btn-modifier { /* Couleur spécifique pour "Effacer Recherche" */
             background-color: #6c757d !important;
        }


        /* Tableau des participants */
        table.participants-table {
            width: 98%; /* Utilise une plus grande partie de la largeur */
            border-collapse: collapse;
            margin: 15px auto; /* Marge réduite */
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            font-size: 0.8em; /* Taille de police du tableau réduite */
            table-layout: fixed; /* Très important : Distribue les largeurs de colonnes de manière égale */
        }
        table.participants-table th, table.participants-table td {
            border: 1px solid #ddd;
            padding: 6px; /* **Padding réduit pour les cellules du tableau** */
            text-align: left;
            vertical-align: middle; /* Aligner le contenu au milieu verticalement */
            word-wrap: break-word; /* Permet aux mots longs de se casser */
        }
        table.participants-table th {
            background-color: #e6e6e6; /* Fond de l'en-tête légèrement plus foncé */
            font-weight: bold;
            text-align: center; /* Centrer les titres de colonnes */
        }
        table.participants-table tr:nth-child(even) {
            background-color: #f6f6f6;
        }
        table.participants-table tr:hover {
            background-color: #e9e9e9;
        }

        /* Ajustement des largeurs de colonnes spécifiques (exemple, ajustez selon votre contenu) */
        table.participants-table th:nth-child(1), /* Participant */
        table.participants-table td:nth-child(1) {
            width: 20%;
        }
        table.participants-table th:nth-child(2), /* Titre */
        table.participants-table td:nth-child(2) {
            width: 15%;
        }
        table.participants-table th:nth-child(3), /* Taux Journalier */
        table.participants-table td:nth-child(3),
        table.participants-table th:nth-child(4), /* Forfait */
        table.participants-table td:nth-child(4),
        table.participants-table th:nth-child(5), /* Frais Déplacement */
        table.participants-table td:nth-child(5) {
            width: 10%;
        }
        table.participants-table th:nth-child(6), /* Jours Déplacement */
        table.participants-table td:nth-child(6),
        table.participants-table th:nth-child(7), /* Jours Copies */
        table.participants-table td:nth-child(7) {
            width: 8%;
        }
        table.participants-table th:nth-child(8), /* Actions */
        table.participants-table td:nth-child(8) {
            width: 19%; /* Prend le reste de l'espace */
            min-width: 160px; /* Assure suffisamment de place pour les boutons */
        }


        /* Styles pour la colonne Actions */
        td.action-buttons { /* Cible spécifiquement la cellule d'actions */
            display: flex;
            flex-direction: column; /* Empile les boutons verticalement */
            gap: 3px; /* Espacement réduit entre les boutons */
            align-items: center; /* Centrer les boutons dans la cellule */
            justify-content: center;
            padding: 3px; /* Rembourrage réduit pour la cellule d'actions */
        }

        /* Styles génériques pour TOUS les boutons/liens d'action dans la colonne d'actions */
        td.action-buttons button,
        td.action-buttons a {
            padding: 6px 8px; /* **Padding réduit pour les boutons d'action** */
            border: none;
            border-radius: 3px; /* Rayon de bordure légèrement réduit */
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-size: 0.75em; /* **Taille de police des boutons réduite** */
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px; /* Largeur minimale pour tous les boutons */
            height: 28px; /* Hauteur fixe pour tous les boutons */
            box-sizing: border-box;
            flex-shrink: 0; /* Empêche les boutons de rétrécir au-delà de min-width */
            white-space: nowrap; /* Empêche le texte de se casser sur plusieurs lignes */
            width: 90%; /* Prend presque toute la largeur disponible dans la colonne */
        }
        /* Couleurs spécifiques (conservées) */
        .btn-modifier { background-color: #007bff; }
        .btn-supprimer { background-color: #dc3545; }
        .btn-telecharger { background-color: #6c757d; }

        td.action-buttons button:hover,
        td.action-buttons a:hover {
            opacity: 0.85; /* Légère réduction de l'opacité au survol */
            transform: translateY(-1px);
        }

        /* Pied de page */
        footer {
            text-align: center;
            padding: 10px;
            background-color: #004d40;
            color: white;
            font-size: 0.75em; /* Taille de police réduite */
            margin-top: 30px;
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
                <li><a href="accueil.html">Accueil</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Activités</a>
                    <div class="dropdown-content">
                        <a href="creer_activite.php">Créer Activité</a>
                        <a href="gerer_activites.php">Gérer Activité</a>
                    </div>
                </li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="activity-participants-section">
            <h2>Participants pour l'Activité : <?php echo $activity_name; ?></h2>

            <?php if (!empty($error_message)): ?>
                <p class="message-erreur"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p class="message-succes"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if ($activite_id === 0): ?>
                <p class="message-erreur">Veuillez sélectionner une activité pour gérer ses participants depuis la <a href="gerer_activites.php">liste des activités</a>.</p>
            <?php else: ?>
                <p>
                    <a href="ajouter_participant.php?activite_id=<?php echo $activite_id; ?>" class="btn-ajouter">Ajouter un nouveau participant à cette activité</a>
                </p>

                <form action="" method="GET" class="search-form">
                    <input type="hidden" name="activite_id" value="<?php echo $activite_id; ?>">
                    <input type="search" name="search_participant" placeholder="Rechercher un participant..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Rechercher</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="gerer_participants.php?activite_id=<?php echo $activite_id; ?>" class="btn-modifier" style="background-color: #6c757d;">Effacer Recherche</a>
                    <?php endif; ?>
                </form>

                <h3>Liste des participants actuels</h3>
                <?php if (empty($current_participants)): ?>
                    <p class="no-participants">Aucun participant n'est encore lié à cette activité<?php echo !empty($search_query) ? " correspondant à votre recherche." : "."; ?></p>
                <?php else: ?>
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Titre</th>
                                <th>Taux Journalier</th>
                                <th>Forfait</th>
                                <th>Frais Déplacement</th>
                                <th>Jours Déplacement</th>
                                <th>Jours Copies</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_participants as $participant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($participant['nom_participant']); ?></td>
                                    <td><?php echo htmlspecialchars($participant['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($participant['taux_journalier_copie'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['forfait_participant'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['frais_deplacement'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['nb_jours_deplacement'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['nb_jours_copies'] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <a href="modifier_participation.php?id=<?php echo htmlspecialchars($participant['id'])?>&activite_id=<?php echo $activite_id?>&action=update_participation" class="btn-modifier">Modifier</a>
                                        <button class="btn-supprimer" onclick="confirmDeleteParticipation(<?php echo htmlspecialchars($participant['id']); ?>, '<?php echo htmlspecialchars($participant['nom_participant']); ?>');">Supprimer</button>
                                        <?php if (!empty($participant['rib_pdf_path'])): ?>
                                            <a href="telecharger_rib.php?participation_id=<?php echo htmlspecialchars($participant['id']); ?>" class="btn-telecharger" target="_blank">Télécharger RIB</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>

    <script>
        function confirmDeleteParticipation(id, nomParticipant) {
            if (confirm("Êtes-vous sûr de vouloir retirer " + nomParticipant + " de cette activité (ID participation: " + id + ") ?")) {
                window.location.href = 'supprimer_participant.php?activite_id=<?php echo $activite_id; ?>&action=delete_participation&participation_id=' + id;
            }
        }
    </script>
</body>
</html>