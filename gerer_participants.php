<?php

require_once 'db.php'; 


$activite_id = isset($_GET['activite_id']) ? (int)$_GET['activite_id'] : 0;
$activity_name = '';
$current_participants = [];
$error_message = '';
$success_message = '';

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

// --- 2. Gérer la suppression d'une participation ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_participation' && isset($_GET['participation_id']) && $activite_id > 0) {
    $participation_id_to_delete = (int)$_GET['participation_id'];

    try {
        $mysqlClient->beginTransaction();
        $stmt_delete = $mysqlClient->prepare("DELETE FROM participations WHERE id = :participation_id AND activite_id = :activite_id");
        $stmt_delete->execute([
            ':participation_id' => $participation_id_to_delete,
            ':activite_id'      => $activite_id
        ]);
        $pdo->commit();
        $success_message = "La participation a été supprimée avec succès.";
        // Rediriger pour éviter la re-soumission du DELETE
        header("Location: gerer_participants.php?activite_id={$activite_id}&msg=" . urlencode($success_message));
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Erreur lors de la suppression de la participation : " . htmlspecialchars($e->getMessage());
    }
}

// Récupérer un message de succès depuis la redirection (si une suppression a eu lieu)
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}


// --- 3. Récupérer les participants déjà liés à cette activité pour affichage ---
if ($activite_id > 0) {
    try {
        $stmt_current_participants = $mysqlClient->prepare("
            SELECT p.id, p.type_participant, p.participant_id,
                   CASE
                       WHEN p.type_participant = 'physique' THEN pp.nom_complet
                       WHEN p.type_participant = 'morale' THEN pm.raison_sociale
                       ELSE 'Inconnu'
                   END AS nom_participant,
                   p.taux_journalier_alloue, p.forfait_alloue, p.frais_deplacement_alloue,
                   p.nb_jours_deplacement_alloue, p.nb_jours_copies_alloue
            FROM participations p
            LEFT JOIN personnes_physiques pp ON p.participant_id = pp.id AND p.type_participant = 'physique'
            LEFT JOIN personnes_morales pm ON p.participant_id = pm.id AND p.type_participant = 'morale'
            WHERE p.activite_id = :activite_id
            ORDER BY nom_participant
        ");
        $stmt_current_participants->execute([':activite_id' => $activite_id]);
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
        /* (Conservez les styles CSS du précédent exemple pour les messages, tableaux, et boutons) */
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
        .no-participants {
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
        .btn-ajouter { background-color: #28a745; } /* Vert */
        .btn-supprimer { background-color: #dc3545; } /* Rouge */

        .action-buttons button:hover, .action-buttons a:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header>
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
                <p class="message-erreur">Veuillez sélectionner une activité pour gérer ses participants depuis la <a href="lister_activites.php">liste des activités</a>.</p>
            <?php else: ?>
                <p>
                    <a href="ajouter_participant.php?activite_id=<?php echo $activite_id; ?>" class="btn-ajouter">Ajouter un nouveau participant à cette activité</a>
                </p>

                <h3>Liste des participants actuels</h3>
                <?php if (empty($current_participants)): ?>
                    <p class="no-participants">Aucun participant n'est encore lié à cette activité.</p>
                <?php else: ?>
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Type</th>
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
                                    <td><?php echo htmlspecialchars($participant['type_participant']); ?></td>
                                    <td><?php echo htmlspecialchars($participant['taux_journalier_alloue'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['forfait_alloue'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['frais_deplacement_alloue'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['nb_jours_deplacement_alloue'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($participant['nb_jours_copies_alloue'] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <a href="modifier_participation.php?id=<?php echo htmlspecialchars($participant['id']); ?>&activite_id=<?php echo $activite_id; ?>" class="btn-modifier">Modifier</a>
                                        <button class="btn-supprimer" onclick="confirmDeleteParticipation(<?php echo htmlspecialchars($participant['id']); ?>, '<?php echo htmlspecialchars($participant['nom_participant']); ?>');">Supprimer</button>
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
        </footer>

    <script>
        function confirmDeleteParticipation(id, nomParticipant) {
            if (confirm("Êtes-vous sûr de vouloir retirer " + nomParticipant + " de cette activité (ID participation: " + id + ") ?")) {
                // Redirige vers cette même page avec l'action de suppression
                window.location.href = 'gerer_participants.php?activite_id=<?php echo $activite_id; ?>&action=delete_participation&participation_id=' + id;
            }
        }
    </script>
</body>
</html>