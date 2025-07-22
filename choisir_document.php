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
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            margin-top: 20px;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea {
            width: calc(100% - 22px); /* Padding accounted for */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group textarea {
            resize: vertical; /* Permet le redimensionnement vertical */
            min-height: 100px;
        }
        .document-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .document-options button {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            min-width: 200px;
        }
        .document-options button:hover {
            background-color: #0056b3;
        }
        .info-note {
            font-size: 0.9em;
            color: #777;
            margin-top: 5px;
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
                        <a href="gerer_activite.php">Gérer Activités</a>
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
                    <textarea id="header_left_content" name="header_left_content" rows="6" placeholder="Ex:&#10;RÉPUBLIQUE DU BÉNIN&#10;MINISTÈRE DE L'ÉCONOMIE ET DES FINANCES&#10;DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE&#10;Direction des Affaires Administratives et Financières&#10;Service du Personnel et des Moyens"></textarea>
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

                <div class="document-options">
                    <button type="button" onclick="setFormAction('generer_attestation.php')">Attestation Collective</button>
                    <button type="button" onclick="setFormAction('generer_noteservice.php')">Note de Service</button>
                    <button type="button" onclick="setFormAction('generer_ordre_virement.php')">Ordre de Virement par Banque</button>
                    <button type="button" onclick="setFormAction('generer_ordre_virement_global.php')">Ordre de Virement Global</button>
                    <button type="button" onclick="setFormAction('etat_paiment.php')">État de Paiement</button>
                    <button type="button" onclick="setFormAction('generer_document.php?document_type=etat_deliberation')">État de Délibération</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>

    <script>
        function setFormAction(actionUrl) {
            const form = document.getElementById('documentForm');
            
            // Pour le cas spécifique de 'etat_deliberation' qui a déjà un paramètre 'document_type'
            if (actionUrl.includes('?')) {
                form.action = actionUrl + '&type=pdf'; // Assume PDF as default for documents without explicit type selection
            } else {
                form.action = actionUrl + '?type=pdf'; // Assume PDF as default type for new generation scripts
            }
            
            form.submit();
        }

        // Pré-remplir la date actuelle si le champ est vide
        window.onload = function() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0'); // Jan is 0
            const day = String(today.getDate()).padStart(2, '0');
            const dateField = document.getElementById('document_date');
            if (!dateField.value) {
                dateField.value = `${year}-${month}-${day}`;
            }
        };
    </script>
</body>
</html>