<?php
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id'])) {
    $activite_id = (int)$_GET['id'] ?? '0';
    
    $participation_id = (int)$_GET['participation_id'] ?? '0';

    $participation_update = "UPDATE participations SET 
    'compte_id' = $compte_id
    'type_participant' = $type_participant ,
    'titre' = $titre, 
    'nb_jours_copies' = $nb_jours_copies,
    'taux_journalier_copie' = $taux_jounalier_copie,
    'forfait_participant' = $forfait_participant,
    'nb_deplacement' = $nb_deplacement,
    'frais_deplacement' = $frais_deplacement,
    'date_enregistrement' = NOW()
     WHERE participation.id = $participation_id AND activite_id = $activite_id ";
    $participation_to_update = $mysqlClient->prepare($participation_update);
    $participation_to_update->execute([
    ':n'
    ]);
}
?> 