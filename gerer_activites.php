<?php
require_once 'db.php';
$activites = [];
$error_message = '';
$success_message = '';

// --- PARAMÈTRES DE PAGINATION ---
$activities_per_page = 5; // Nombre d'activités à afficher par page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// S'assurer que la page est au moins 1
if ($current_page < 1) {
    $current_page = 1;
}

$offset = ($current_page - 1) * $activities_per_page;

// --- RÉCUPÉRATION DU NOMBRE TOTAL D'ACTIVITÉS ---
$total_activities = 0;
try {
    $stmt_count = $mysqlClient->prepare("SELECT COUNT(*) FROM activites");
    $stmt_count->execute();
    $total_activities = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération du nombre total d'activités : " . htmlspecialchars($e->getMessage());
}

$total_pages = ceil($total_activities / $activities_per_page);

// S'assurer que la page actuelle ne dépasse pas le nombre total de pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $activities_per_page; // Recalculer l'offset
} elseif ($total_pages === 0) {
    $current_page = 1; // Si aucune activité, la page est 1
    $offset = 0;
}


// --- RÉCUPÉRATION DES ACTIVITÉS POUR LA PAGE ACTUELLE ---
try {
    $stmt = $mysqlClient->prepare("SELECT * FROM activites ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $activities_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des activités : " . htmlspecialchars($e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste et Gestion des Activités</title>
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

        .no-activities {
            text-align: center;
            color: #555;
            margin: 20px 0; /* Marge réduite */
            font-style: italic;
            font-size: 0.8em; /* Taille de police réduite */
        }

        /* Tableau */
        table {
            width: 98%; /* Légèrement plus large pour les boutons */
            border-collapse: collapse;
            margin: 15px auto; /* Marge réduite */
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            font-size: 0.8em; /* Taille de police du tableau réduite */
            table-layout: fixed; /* Très important : Distribue les largeurs de colonnes de manière égale */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px; /* **Padding réduit pour les cellules du tableau** */
            text-align: left;
            vertical-align: middle; /* Aligner le contenu au milieu verticalement */
            word-wrap: break-word; /* Permet aux mots longs de se casser */
        }
        th {
            background-color: #e6e6e6; /* Fond de l'en-tête légèrement plus foncé */
            font-weight: bold;
            text-align: center; /* Centrer les titres de colonnes */
        }
        tr:nth-child(even) {
            background-color: #f6f6f6;
        }
        tr:hover {
            background-color: #e9e9e9;
        }

        /* Styles pour la colonne Actions */
        .action-buttons {
            display: flex;
            flex-direction: column; /* Empile les boutons verticalement */
            gap: 3px; /* Espacement réduit entre les boutons */
            align-items: center; /* Centrer les boutons dans la cellule */
            justify-content: center;
            min-width: 100px; /* Largeur minimale de la colonne pour les actions */
            padding: 3px; /* Rembourrage réduit pour la cellule d'actions */
        }

        /* Styles génériques pour TOUS les boutons/liens d'action */
        .action-buttons button,
        .action-buttons a {
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
            min-width: 80px; /* Largeur minimale pour tous les boutons */
            height: 28px; /* Hauteur fixe pour tous les boutons */
            box-sizing: border-box;
            flex-shrink: 0; /* Empêche les boutons de rétrécir au-delà de min-width */
            white-space: nowrap; /* Empêche le texte de se casser sur plusieurs lignes */
        }
        /* Couleurs spécifiques (conservées) */
        .btn-modifier { background-color: #007bff; }
        .btn-participants { background-color: #28a745; }
        .btn-paiements { background-color: #ffc107; color: #333; }
        .btn-supprimer { background-color: #dc3545; }
        .btn-details { background-color: #6c757d; }
        .btn-generer-documents-link { background-color: #6f42c1; }

        .action-buttons button:hover,
        .action-buttons a:hover {
            opacity: 0.85; /* Légère réduction de l'opacité au survol */
            transform: translateY(-1px);
        }

        /* Styles pour la pagination */
        .pagination {
            text-align: center;
            margin-top: 15px; /* Marge réduite */
            padding: 8px 0; /* Padding réduit */
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px; /* Espacement réduit */
            font-size: 0.8em; /* Taille de police réduite */
        }
        .pagination a, .pagination span {
            padding: 6px 10px; /* Padding réduit */
            border: 1px solid #ddd;
            text-decoration: none;
            color: #007bff;
            border-radius: 3px; /* Rayon de bordure réduit */
            transition: background-color 0.3s ease;
        }
        .pagination a:hover {
            background-color: #f2f2f2;
        }
        .pagination span.current-page {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: bold;
        }
        .pagination span.disabled {
            color: #ccc;
            cursor: not-allowed;
            background-color: #f9f9f9;
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

        /* Ajustements spécifiques pour les colonnes de date */
        td:nth-child(6) { /* Cible la 6ème colonne (Période) */
            font-size: 0.75em; /* Taille de police encore plus petite pour les dates */
            line-height: 1.2; /* Réduit l'espace entre les lignes */
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
                    <form action="rechercher_activite.php" metho ="GET">
                    <input   type="search" name ="q" placeholder="Rechercher une activité..." aria-label="Rechercher" >
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
                        <a href="gerer_activite.php">Gérer Activités</a> </div>
                </li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="activity-list-section">
            <h2>Liste et Gestion des Activités</h2>

            <?php if (!empty($error_message)): ?>
                <p class="message-erreur"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <p class="message-succes"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if (empty($activites) && $current_page === 1): ?>
                <p class="no-activities">Aucune activité n'a été trouvée pour le moment.</p>
                <p class="no-activities">Vous pouvez <a href="creer_activite.php">créer une nouvelle activité ici</a>.</p>
            <?php elseif (empty($activites) && $current_page > 1): ?>
                   <p class="no-activities">Aucune activité trouvée sur cette page.</p>
                   <p class="no-activities">Retournez à la <a href="gerer_activite.php?page=<?php echo $current_page - 1; ?>">page précédente</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Responsable</th>
                            <th>Organisateur</th>
                            <th>Financier</th>
                            <th>Période</th>
                            <th>Lieu</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activites as $activite): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activite['id']); ?></td>
                                <td><?php echo htmlspecialchars($activite['nom']); ?></td>
                                <td><?php echo htmlspecialchars($activite['responsable_titre']); ?></td>
                                <td><?php echo htmlspecialchars($activite['organisateur_titre']); ?></td>
                                <td><?php echo htmlspecialchars($activite['financier_titre']); ?></td>
                                <td>
                                    Du <?php echo htmlspecialchars($activite['periode_debut']); ?> <br>
                                    Au <?php echo htmlspecialchars($activite['periode_fin']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($activite['centre']); ?></td>
                                <td class="action-buttons">
                                    <a href="modifier_activite.php?id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-modifier">Modifier</a>
                                    <a href="gerer_participants.php?activite_id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-participants">Participants</a>
                                    <a href="afficher_details_activite.php?id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-details">Détails</a>
                                    <a href="choisir_document.php?activite_id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-generer-documents-link">Générer Documents</a>
                                    <button class="btn-supprimer" onclick="confirmDelete(<?php echo htmlspecialchars($activite['id']); ?>, '<?php echo htmlspecialchars($activite['nom']); ?>');">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>">Précédent</a>
                        <?php else: ?>
                            <span class="disabled">Précédent</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>">Suivant</a>
                        <?php else: ?>
                            <span class="disabled">Suivant</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>

    <script>
        function confirmDelete(id, nom) {
            if (confirm("Êtes-vous sûr de vouloir supprimer l'activité '" + nom + "' (ID: " + id + ") ? Cette action est irréversible.")) {
                window.location.href = 'supprimer_activite.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>