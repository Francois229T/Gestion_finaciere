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
        /* Styles CSS additionnels pour le tableau et les boutons */
        table {
            width: 100%; /* Légèrement plus large pour les boutons */
            border-collapse: collapse;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
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
        .no-activities {
            text-align: center;
            color: #555;
            margin: 30px 0;
            font-style: italic;
        }
        
            /* Conteneur Flexbox pour les boutons à l'intérieur de la cellule d'action */
        .action-buttons {
            display: flex;
            flex-direction: column; /* Aligne les boutons verticalement */
            gap: 5px; /* Espace entre les boutons */
            width: 100%; /* S'assure que le conteneur prend toute la largeur de sa cellule parent */
            box-sizing: border-box; /* Inclut padding et border dans la largeur */
            /* Permet aux boutons de rester centrés si la cellule est plus large que le contenu */
            align-items: center; /* Centre les éléments enfants (les boutons) horizontalement */
        }

        /* Styles pour tous les boutons et liens qui agissent comme des boutons dans la colonne Actions */
        .action-buttons button,
        .action-buttons a {
            /* Styles existants pour l'apparence */
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* Pour les liens stylisés comme des boutons */
            color: white;
            font-size: 0.9em;
            text-align: center;
            
            /* Propriétés pour la taille uniforme */
            display: block; /* Force le bouton/lien à prendre toute la largeur disponible du parent .action-buttons */
            width: 100%; /* C'est la clé : chaque bouton prend 100% de la largeur de son conteneur (.action-buttons) */
            box-sizing: border-box; /* S'assure que padding et border sont inclus dans la largeur */

            /* Gestion du texte long */
            white-space: nowrap; /* Empêche le texte de passer à la ligne */
            overflow: hidden; /* Cache le texte qui dépasse */
            text-overflow: ellipsis; /* Affiche "..." si le texte est coupé */
            max-width: 150px; /* Optionnel: Limite la largeur maximale d'un bouton pour éviter qu'un bouton trop long n'étire trop la colonne */
        }

        /* Styles de couleur spécifiques */
        .btn-modifier { background-color: #007bff; } /* Bleu */
        .btn-participants { background-color: #28a745; } /* Vert */
        .btn-details { background-color: #6c757d; } /* Gris */
        .btn-generer-documents-link {
            background-color: #6f42c1; /* Violet */
            color: white;
        }
        .btn-supprimer { background-color: #dc3545; } /* Rouge */

        /* Effet de survol */
        .action-buttons button:hover,
        .action-buttons a:hover {
            opacity: 0.9;
        }



        /* Styles pour la pagination */
        .pagination {
            text-align: center;
            margin-top: 20px;
            padding: 10px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #007bff;
            border-radius: 4px;
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
                        <a href="gerer_activites.php">Gérer Activités</a> </div>
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

            <?php if (empty($activites) && $current_page === 1): // Message si aucune activité du tout ?>
                <p class="no-activities">Aucune activité n'a été trouvée pour le moment.</p>
                <p class="no-activities">Vous pouvez <a href="creer_activite.php">créer une nouvelle activité ici</a>.</p>
            <?php elseif (empty($activites) && $current_page > 1): // Message si page vide mais il y a des activités précédentes ?>
                   <p class="no-activities">Aucune activité trouvée sur cette page.</p>
                   <p class="no-activities">Retournez à la <a href="gerer_activite.php?page=<?php echo $current_page - 1; ?>">page précédente</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>N0:</th>
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
                        <?php $compt = 0; ?>

                        <?php foreach ($activites as $activite): ?>
                            <?php $compt += 1; ?>
                            <tr>
                                <td><?php echo $compt; ?></td>
                                <td><?php echo htmlspecialchars($activite['nom']); ?></td>
                                <td><?php echo htmlspecialchars($activite['responsable_titre']); ?></td>
                                <td><?php echo htmlspecialchars($activite['organisateur_titre']); ?></td>
                                <td><?php echo htmlspecialchars($activite['financier_titre']); ?></td>
                                <td>
                                    Du <?php echo htmlspecialchars($activite['periode_debut']); ?> <br>
                                    Au <?php echo htmlspecialchars($activite['periode_fin']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($activite['centre']); ?></td>
                                <td class="action-cell"> <div class="action-buttons"> </div>
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