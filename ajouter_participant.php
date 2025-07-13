<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD']==='POST')
{
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];

    $statement =  $pdo->prepare("INSERT into participants(nom,prenom,email)VALUES(?,?,?)");
    $statement->execute([$nom,$prenom,$email]);
    echo json_encode(['messsage'=>'Participant ajouté']);
}
?> 

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Participant à une Activité</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding in width */
        }
        input[type="number"] {
            -moz-appearance: textfield; /* Firefox hide arrows */
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #218838;
        }
        .info-text {
            font-size: 0.9em;
            color: #777;
            margin-top: 5px;
        }
        .flex-group {
            display: flex;
            gap: 20px; /* Space between columns */
        }
        .flex-group > div {
            flex: 1; /* Makes columns take equal width */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ajouter un Participant à une Activité</h2>
        <form action="traitement_participation.php" method="POST">

            <div class="form-group">
                <label for="activity_id">Sélectionner l'Activité :</label>
                <select id="activity_id" name="activity_id" required>
                    <option value="">-- Choisir une activité --</option>
                    <?php
                        // PHP : Ici, vous devez boucler sur les activités récupérées depuis votre base de données
                        // Exemple (à remplacer par votre logique de récupération de données) :
                        // foreach ($activites as $activite) {
                        //     echo "<option value='" . htmlspecialchars($activite['id']) . "'>" . htmlspecialchars($activite['nom_activite']) . "</option>";
                        // }
                    ?>
                    <option value="1">Examen du BEPC 2025</option>
                    <option value="2">Formation des Formateurs Janvier</option>
                    <option value="3">Campagne de Sensibilisation</option>
                    </select>
            </div>

            <div class="form-group">
                <label for="participant_id">Sélectionner le Participant :</label>
                <select id="participant_id" name="participant_id" required>
                    <option value="">-- Choisir un participant --</option>
                    <?php
                        // PHP : Ici, vous devez boucler sur les participants (personnes) récupérés depuis votre base de données
                        // Exemple (à remplacer par votre logique de récupération de données) :
                        // foreach ($participants as $participant) {
                        //     echo "<option value='" . htmlspecialchars($participant['id']) . "'>" . htmlspecialchars($participant['nom_complet']) . "</option>";
                        // }
                    ?>
                    <option value="101">M. Jean Dupont (Correcteur)</option>
                    <option value="102">Mme. Marie Curie (Superviseur)</option>
                    <option value="103">Dr. Ali Ben (Agent de Santé)</option>
                    </select>
            </div>

            <div class="form-group">
                <label for="titre_participant">Titre/Rôle du Participant dans cette Activité :</label>
                <input type="text" id="titre_participant" name="titre_participant" placeholder="Ex: Correcteur, Superviseur, Agent de Sécurité" required>
                <div class="info-text">Le rôle spécifique du participant pour cette activité.</div>
            </div>

            <div class="flex-group">
                <div class="form-group">
                    <label for="nb_jours_copies">Nombre de Jours / Copies :</label>
                    <input type="number" id="nb_jours_copies" name="nb_jours_copies" min="0" step="1" placeholder="Ex: 150 (copies) ou 5 (jours)" required>
                    <div class="info-text">Nombre d'unités de travail (jours ou copies).</div>
                </div>

                <div class="form-group">
                    <label for="taux_journalier_copie">Taux Journalier / Copie (XOF) :</label>
                    <input type="number" id="taux_journalier_copie" name="taux_journalier_copie" min="0" step="0.01" placeholder="Ex: 500 (par copie) ou 25000 (par jour)" required>
                    <div class="info-text">Montant unitaire de rémunération.</div>
                </div>
            </div>

            <div class="flex-group">
                <div class="form-group">
                    <label for="forfait">Montant Forfaitaire (XOF) :</label>
                    <input type="number" id="forfait" name="forfait" min="0" step="0.01" placeholder="Ex: 10000" value="0">
                    <div class="info-text">Montant fixe additionnel (si applicable).</div>
                </div>

                <div class="form-group">
                    <label for="frais_deplacement">Frais de Déplacement (XOF) :</label>
                    <input type="number" id="frais_deplacement" name="frais_deplacement" min="0" step="0.01" placeholder="Ex: 5000" value="0">
                    <div class="info-text">Coût total des déplacements (si applicable).</div>
                </div>
            </div>

            <div class="form-group">
                <label for="nombre_jour_deplacement">Nombre de Jours de Déplacement :</label>
                <input type="number" id="nombre_jour_deplacement" name="nombre_jour_deplacement" min="0" step="1" placeholder="Ex: 2" value="0">
                <div class="info-text">Nombre de jours pour lesquels des frais de déplacement sont alloués.</div>
            </div>

            <button type="submit">Ajouter la Participation</button>

        </form>
    </div>
</body>
</html>