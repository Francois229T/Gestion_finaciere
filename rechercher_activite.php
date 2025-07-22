<?php
session_start(); // Toujours démarrer la session au début

require_once 'db.php'; // Inclure votre fichier de connexion à la base de données

$search_query = $_GET['q'] ?? ''; // Récupère le terme de recherche depuis l'URL (méthode GET)
$activities = []; // Initialise un tableau vide pour stocker les résultats

// Traitement de la recherche si un terme a été soumis
if (!empty($search_query)) {
    try {
        // Préparer la requête SQL pour rechercher des activités
        // Nous recherchons dans le nom de l'activité. Utilisez LIKE pour une recherche partielle.
        // Les pourcentages (%) sont des jokers pour LIKE.
        // CONCAT permet d'ajouter les jokers autour du terme de recherche.
        $stmt = $mysqlClient->prepare("SELECT id, nom, description, periode_debut, periode_fin FROM activites WHERE nom LIKE CONCAT('%', :search_term, '%') ORDER BY nom ASC");

        // Exécuter la requête en liant le paramètre
        $stmt->execute([':search_term' => $search_query]);

        // Récupérer tous les résultats
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // En cas d'erreur de base de données, stocker le message d'erreur
        $_SESSION['error_message'] = "Erreur lors de la recherche des activités : " . htmlspecialchars($e->getMessage());
        // Vous pouvez aussi rediriger vers une page d'erreur ou la page d'accueil
        // header("Location: index.php");
        // exit();
    }
}

// Récupérer les messages de session pour l'affichage (succès ou erreur d'autres pages)
$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher une Activité - Plateforme de Gestion des Paiements</title>
    <link rel="stylesheet" href="class1.css">
    <style>
        /* Styles spécifiques à cette page, vous pouvez les ajouter à class1.css si vous préférez */
        .search-container {
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .search-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-form input[type="search"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .search-form button:hover {
            background-color: #0056b3;
        }

        .results-container {
            margin-top: 30px;
            width: 80%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .activity-card {
            background-color: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .activity-card h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .activity-card p {
            margin-bottom: 5px;
            line-height: 1.5;
        }
        .activity-card .actions {
            margin-top: 15px;
            text-align: right;
        }
        .activity-card .actions a {
            display: inline-block;
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .activity-card .actions a:hover {
            background-color: #218838;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        .message-erreur { /* Repris de votre CSS précédent */
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
        .message-succes { /* Repris de votre CSS précédent */
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
                    <form action="rechercher_activite.php" method="GET">
                        <input type="search" name="q" placeholder="Rechercher une activité..." aria-label="Rechercher" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">Rechercher</button>
                    </form>
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
                        <a href="rechercher_activite.php">Rechercher Activité</a> </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Documents</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="login.html">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="search-container">
            <h2>Rechercher une Activité</h2>

            <?php if (!empty($error_message)): ?>
                <p class="message-erreur"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p class="message-succes"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <form action="rechercher_activite.php" method="GET" class="search-form">
                <input type="search" id="search_term" name="q" placeholder="Entrez le nom de l'activité..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Rechercher</button>
            </form>
        </section>

        <section class="results-container">
            <?php if (!empty($search_query) && empty($activities)): ?>
                <p class="no-results">Aucune activité trouvée pour "<?php echo htmlspecialchars($search_query); ?>".</p>
            <?php elseif (!empty($activities)): ?>
                <h2>Résultats de la recherche (<?php echo htmlspecialchars($search_query); ?>)</h2>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-card">
                        <h3><?php echo htmlspecialchars($activity['nom']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($activity['description']); ?></p>
                        <p><strong>Début:</strong> <?php echo htmlspecialchars($activity['periode_debut']); ?></p>
                        <p><strong>Fin:</strong> <?php echo htmlspecialchars($activity['periode_fin']); ?></p>
                        <div class="actions">
                            <a href="gerer_participants.php?activite_id=<?php echo htmlspecialchars($activity['id']); ?>">Gérer Participants</a>
                            <a href="modifier_activite.php?id=<?php echo htmlspecialchars($activity['id']); ?>">Modifier Activité</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results">Entrez un terme de recherche pour trouver des activités.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        </footer>
</body>
</html>