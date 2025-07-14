<?php
require_once 'db.php'; 
$activites = [];
$error_message = '';
$success_message = '';

// Récupération de toutes les activités (le code principal reste le même)
try {
    $stmt = $mysqlClient->prepare("SELECT * FROM activites ORDER BY id DESC");
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
            width: 95%; /* Légèrement plus large pour les boutons */
            border-collapse: collapse;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
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
        .action-buttons {
            display: flex; /* Permet aux boutons d'être sur une ligne */
            gap: 5px; /* Espace entre les boutons */
            flex-wrap: wrap; /* Pour que les boutons passent à la ligne si pas assez de place */
        }
        .action-buttons button, .action-buttons a {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* Pour les liens stylisés comme des boutons */
            color: white;
            font-size: 0.9em;
            text-align: center;
            display: inline-block; /* Assure que padding et margin fonctionnent */
        }
        .btn-modifier { background-color: #007bff; } /* Bleu */
        .btn-participants { background-color: #28a745; } /* Vert */
        .btn-paiements { background-color: #ffc107; color: #333; } /* Jaune */
        .btn-supprimer { background-color: #dc3545; } /* Rouge */
        .btn-details { background-color: #6c757d; } /* Gris */

        .action-buttons button:hover, .action-buttons a:hover {
            opacity: 0.9;
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
                        <a href="gerer_activite.php">Gérer Activités</a> </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Paiements</a></li>
                <li><a href="#">Documents</a></li>
                <li><a href="dashboard_financier.html">Tableau de Bord</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="login.html">Déconnexion</a></li>
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

            <?php if (empty($activites)): ?>
                <p class="no-activities">Aucune activité n'a été trouvée pour le moment.</p>
                <p class="no-activities">Vous pouvez <a href="creer_activite.php">créer une nouvelle activité ici</a>.</p>
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
                            <th>Actions</th> </tr>
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

                                    <a href="gerer_paiements.php?activite_id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-paiements">Paiements</a>

                                    <a href="afficher_details_activite.php?id=<?php echo htmlspecialchars($activite['id']); ?>" class="btn-details">Détails</a>

                                    <button class="btn-supprimer" onclick="confirmDelete(<?php echo htmlspecialchars($activite['id']); ?>, '<?php echo htmlspecialchars($activite['nom']); ?>');">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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