<?php
// get_banks_for_participant.php

require_once 'db.php'; // Assurez-vous que ce fichier initialise bien $mysqlClient

header('Content-Type: application/json'); // Indique que la réponse est du JSON

$participant_id = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;

$banks = []; // Tableau pour stocker les noms des banques

if ($participant_id > 0) {
    try {
        $stmt = $mysqlClient->prepare("SELECT banque FROM comptes_bancaires WHERE participant_id = :participant_id");
        $stmt->execute([':participant_id' => $participant_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['banque'])) {
                $banks[] = $row['banque'];
            }
        }
    } catch (PDOException $e) {
        // En cas d'erreur, vous pouvez renvoyer un message d'erreur JSON ou un tableau vide
        error_log("Erreur lors de la récupération des banques pour participant " . $participant_id . ": " . $e->getMessage());
        // Pour des raisons de sécurité, ne pas exposer le message d'erreur complet à l'utilisateur final.
        // echo json_encode(['error' => 'Erreur serveur lors de la récupération des banques.']);
        // exit();
    }
}

echo json_encode($banks); // Renvoyer la liste des banques au format JSON
?>