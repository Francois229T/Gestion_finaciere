<?php
// Inclure votre fichier de connexion à la base de données si nécessaire
require_once 'db.php'; 

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;

if ($activite_id === 0) {
    die("ID d'activité manquant pour choisir un document.");
}

// Optionnel: Récupérer le nom de l'activité pour un affichage plus convivial
$activity_name = "Activité ID: " . $activite_id;
try {
    $stmt_activity = $mysqlClient->prepare("SELECT nom FROM activites WHERE id = :id");
    $stmt_activity->execute([':id' => $activite_id]);
    $activity_info = $stmt_activity->fetch(PDO::FETCH_ASSOC);
    if ($activity_info) {
        $activity_name = htmlspecialchars($activity_info['nom']);
    }
} catch (PDOException $e) {
    // Gérer l'erreur si la base de données n'est pas accessible
    error_log("Erreur de base de données lors de la récupération du nom de l'activité: " . $e->getMessage());
}

?>
<?php
// Inclure votre fichier de connexion à la base de données si nécessaire
require_once 'db.php';

$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;

if ($activite_id === 0) {
    die("ID d'activité manquant pour choisir un document.");
}

// Optionnel: Récupérer le nom de l'activité pour un affichage plus convivial
$activity_name = "Activité ID: " . $activite_id;
try {
    $stmt_activity = $mysqlClient->prepare("SELECT nom FROM activites WHERE id = :id");
    $stmt_activity->execute([':id' => $activite_id]);
    $activity_info = $stmt_activity->fetch(PDO::FETCH_ASSOC);
    if ($activity_info) {
        $activity_name = htmlspecialchars($activity_info['nom']);
    }
} catch (PDOException $e) {
    // Gérer l'erreur si la base de données n'est pas accessible
    error_log("Erreur de base de données lors de la récupération du nom de l'activité: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer Documents pour <?php echo $activity_name; ?></title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* S'assure que le footer est en bas */
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
            flex-grow: 1; /* Permet au main de prendre l'espace disponible */
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex; /* Utilise flexbox pour le centrage */
            justify-content: center; /* Centre horizontalement */
            align-items: flex-start; /* Aligne en haut, ajustez si nécessaire */
        }

        .container {
            background-color: #fff;
            padding: 20px; /* Réduit le padding du conteneur */
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre plus subtile */
            width: 100%;
            max-width: 700px; /* Max width légèrement réduit */
            margin-top: 0; /* Pas de marge supérieure si déjà dans main */
        }

        h2 {
            color: #004d40; /* Couleur cohérente avec le thème */
            text-align: center;
            margin-bottom: 20px; /* Marge réduite */
            font-size: 1.6em; /* Taille de titre ajustée */
        }

        .form-group {
            margin-bottom: 12px; /* Marge réduite entre les groupes de formulaire */
        }
        .form-group label {
            display: block;
            margin-bottom: 4px; /* Marge réduite */
            font-weight: bold;
            color: #555;
            font-size: 0.9em; /* Taille de police réduite pour les labels */
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 16px); /* Réduit encore la largeur pour 8px padding */
            padding: 8px; /* **Padding réduit pour tous les champs de formulaire** */
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em; /* Taille de police réduite pour les inputs */
            box-sizing: border-box; /* S'assure que padding est inclus dans la largeur */
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px; /* Hauteur minimale réduite */
        }
        .info-note {
            font-size: 0.75em; /* Taille de police très réduite pour les notes */
            color: #777;
            margin-top: 3px; /* Marge très réduite */
        }

        .document-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px; /* Espacement réduit entre les boutons */
            justify-content: center;
            margin-top: 20px; /* Marge réduite */
            padding-top: 15px; /* Padding réduit */
            border-top: 1px solid #eee;
        }

        .document-options button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px; /* **Padding réduit pour les boutons d'action** */
            border: none;
            border-radius: 4px; /* Rayon de bordure légèrement réduit */
            cursor: pointer;
            font-size: 0.8em; /* **Taille de police des boutons réduite** */
            transition: background-color 0.3s ease;
            min-width: 160px; /* Largeur minimale des boutons */
            box-sizing: border-box;
            flex-grow: 1; /* Permet aux boutons de grandir et de prendre l'espace */
            max-width: 48%; /* Deux boutons par ligne sur des écrans plus larges */
            text-align: center; /* Assure que le texte est centré */
            height: 38px; /* Hauteur fixe pour les boutons */
        }

        .document-options button:hover {
            background-color: #0056b3;
        }

        /* Pied de page */
        footer {
            text-align: center;
            padding: 10px;
            background-color: #004d40;
            color: white;
            font-size: 0.75em; /* Taille de police réduite */
            margin-top: auto; /* Pousse le footer vers le bas */
        }

        /* Styles pour le sélecteur de format */
        .format-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px; /* Marge réduite */
            padding: 8px; /* Padding réduit */
            background-color: #e9ecef;
            border-radius: 5px;
            font-size: 0.9em; /* Taille de police réduite */
        }
        .format-selector label {
            margin-right: 8px; /* Marge réduite */
            font-weight: bold;
        }
        .format-selector select {
            padding: 6px; /* Padding réduit pour le select */
            font-size: 0.9em;
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
                        <input type="search" name="q" placeholder="Rechercher une activité..." aria-label="Rechercher">
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
                <li><a href="accueil.html">Accueil</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Activités</a>
                    <div class="dropdown-content">
                        <a href="creer_activite.php">Créer Activité</a>
                        <a href="gerer_activites.php">Gérer Activités</a>
                    </div>
                </li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Générer Documents pour l'activité : <?php echo $activity_name; ?></h2>

            <form id="documentForm" method="GET">
                <input type="hidden" name="activite_id" value="<?php echo htmlspecialchars($activite_id); ?>">

                <div class="form-group">
                    <label for="header_left_content">Informations du Coin Supérieur Gauche (Entrée multi-lignes, utilisez ENTRÉE pour les retours à la ligne) :</label>
                    <textarea id="header_left_content" name="header_left_content" rows="4" placeholder="Ex:&#10;RÉPUBLIQUE DU BÉNIN&#10;MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES&#10;DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE&#10;Direction des Affaires Administratives et Financières&#10;Service du Personnel et des Moyens"></textarea>
                    <p class="info-note">Laissez vide pour utiliser le contenu par défaut.</p>
                </div>

                <div class="form-group">
                    <label for="note_generatrice_n">Note Génératrice N° :</label>
                    <input type="text" id="note_generatrice_n" name="note_generatrice_n" placeholder="Ex: 001/MEF/DGTCP/DAAF/SPM">
                    <p class="info-note">Laissez vide pour utiliser "Note génératrice N".</p>
                </div>

                <div class="form-group">
                    <label for="reference_ref">Référence :</label>
                    <input type="text" id="reference_ref" name="reference_ref" placeholder="Ex: REF/DGTCP/SPM">
                    <p class="info-note">Laissez vide pour utiliser "Référence REF".</p>
                </div>

                <div class="form-group">
                    <label for="document_date">Date du Document :</label>
                    <input type="date" id="document_date" name="document_date">
                    <p class="info-note">Laissez vide pour utiliser la date actuelle.</p>
                </div>

                <div class="form-group mb-3 format-selector">
                    <label for="document_format" class="form-label">Format du document :</label>
                    <select class="form-control" id="document_format" name="document_format">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>

                <div class="document-options">
                    <button type="button" onclick="setFormAction('generer_attestation.php')">Générer Attestation Collective</button>
                    <button type="button" onclick="setFormAction('generer_noteservice.php')">Générer Note de Service</button>
                    <button type="button" onclick="setFormAction('generer_ordre_virement.php')">Générer Ordre de Virement par Banque</button>
                    <button type="button" onclick="setFormAction('generer_ordre_global.php')">Générer Ordre de Virement Global</button>
                    <button type="button" onclick="setFormAction('etat_paiment.php')">Générer État de Paiement</button>
                    <button type="button" onclick="setFormAction('etat_deliberation.php')">Générer État de Délibération</button>
                    <button type="button" onclick="setFormAction('listerib.php')">Synthèse des RIB</button>
                    <button type="button" onclick="setFormAction('tableau_recapitulatif.php')">Tableau Récapitulatif</button> </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function setFormAction(baseUrl) {
            const form = document.getElementById('documentForm');
            const selectedFormat = document.getElementById('document_format').value;
            
            // Supprime tout champ caché 'type' existant pour éviter les doublons
            let oldTypeInput = document.getElementById('hidden_document_type');
            if (oldTypeInput) {
                oldTypeInput.remove();
            }

            // Crée un nouveau champ input caché pour le type de document
            const hiddenTypeInput = document.createElement('input');
            hiddenTypeInput.type = 'hidden';
            hiddenTypeInput.name = 'type'; // C'est le nom du paramètre attendu par PHP
            hiddenTypeInput.value = selectedFormat; // La valeur (pdf ou excel)
            hiddenTypeInput.id = 'hidden_document_type'; // Pour pouvoir le retrouver et le supprimer si besoin

            // Ajoute ce champ caché au formulaire
            form.appendChild(hiddenTypeInput);

            // Définissez l'action du formulaire comme la base URL.
            // Tous les champs du formulaire (y compris le nouveau champ 'type') seront soumis en GET.
            form.action = baseUrl; 
            
            // Pour le débogage (voir dans la console du navigateur)
            console.log("Form action set to: ", form.action);
            console.log("Hidden type field added with value: ", hiddenTypeInput.value);

            form.submit(); // Soumettez le formulaire
        }

        // Script pour pré-remplir la date avec la date du jour
        window.onload = function() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0'); // Jan is 0
            const day = String(today.getDate()).padStart(2, '0');
            const dateField = document.getElementById('document_date');
            if (!dateField.value) { // Pré-remplir seulement si vide
                dateField.value = `${year}-${month}-${day}`;
            }
        };
    </script>
</body>
</html>