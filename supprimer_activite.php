<?php
require_once 'db.php'; 
$activites = [];
$error_message = '';
$success_message = '';

// Gérer l'action de suppression si elle vient d'être déclenchée
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $activity_id_to_delete = (int)$_GET['id'];

    try {
        $mysqlClient->beginTransaction();

        // Étape 1: Supprimer les enregistrements liés dans d'autres tables (si elles existent)
        // Par exemple, si vous avez une table 'participants' qui référence 'activites.id'
        // Assurez-vous d'avoir une contrainte ON DELETE CASCADE sur votre base de données,
        // ou supprimez manuellement les dépendances ici pour éviter des erreurs de clé étrangère.
        // Exemple (si vous aviez une table participants liée à activites.id):
        // $stmt_delete_participants = $pdo->prepare("DELETE FROM participants WHERE activite_id = :id");
        // $stmt_delete_participants->execute([':id' => $activity_id_to_delete]);

        // Étape 2: Supprimer l'activité principale
        $stmt_delete_activity = $mysqlClient->prepare("DELETE FROM activites WHERE id = :id");
        $stmt_delete_activity->execute([':id' => $activity_id_to_delete]);

        $mysqlClient->commit();
        $success_message = "L'activité (ID: {$activity_id_to_delete}) a été supprimée avec succès.";

        // Rediriger pour éviter la re-soumission du DELETE si la page est rafraîchie
        header("Location: gerer_activites.php?msg=" . urlencode($success_message));
        exit();

    } catch (PDOException $e) {
        $mysqlClient->rollBack();
        $error_message = "Erreur lors de la suppression de l'activité (ID: {$activity_id_to_delete}) : " . htmlspecialchars($e->getMessage());
    }
}

// Récupérer un message de succès depuis la redirection (si une suppression a eu lieu)
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}

?> 