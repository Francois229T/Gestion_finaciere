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
        $mysqlClient->commit();
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

?> 