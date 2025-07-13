
<?php
 require'db.php';

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $mysqlClient->beginTransaction();
    $errors = []; // Initialisez votre tableau d'erreurs ici
    $success = ""; // Pour les messages de succès

    // 1. Récupération et validation des champs du participant principal
    $raison_sociale = trim($_POST['raison_sociale'] ?? '');
    $ifu_morale = trim($_POST['ifu_morale'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $contact_nom = trim($_POST['contact_nom'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_tel = trim($_POST['contact_tel'] ?? '');

    if (empty($raison_sociale) || empty($ifu_morale) || empty($adresse) || empty($contact_nom) || empty($contact_email) || empty($contact_tel)) {
        $errors['participant_fields'] = "Tous les champs d'identification du participant sont obligatoires.";
    }

    if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide";

    // 2. Validation si au moins un compte bancaire est fourni (si c'est obligatoire)
    if (!isset($_POST['bank_morale']) || !is_array($_POST['bank_morale']) || count(array_filter($_POST['bank_morale'])) === 0) {
        $errors['no_bank_accounts'] = "Veuillez fournir au moins un compte bancaire.";
    }

    // Si des erreurs initiales sont trouvées, annulez la transaction et affichez les erreurs
    if (!empty($errors)) {
        $mysqlClient->rollBack();
        foreach ($errors as $error_key => $error_msg) {
            echo "<p style='color:red;'>" . htmlspecialchars($error_msg) . "</p>";
        }
        exit; // Arrête l'exécution du script
    }

    // --- Si pas d'erreurs initiales, procédez aux insertions ---
    try {
        // 1-Insertion participants
        $type = 'personne_morale';
        $sql = 'INSERT INTO participants(type) VALUES (:type)';
        $insertParticipants = $mysqlClient->prepare($sql);
        $insertParticipants->execute(['type' => $type]);
        $participant_id = $mysqlClient->lastInsertId(); // ID du participant généré

        // 2-Insertion personnes physiques
        $sqlQuery = 'INSERT INTO personnes_morales (participant_id, denomination, adresse, ifu, representant_legal, contact_email, contact_tel) VALUES (:participant_id, :denomination, :adresse, :ifu, :representant_legal, :contact_email, :contact_tel)';
        $insertUsers = $mysqlClient->prepare($sqlQuery);
        $insertUsers->execute([
            'participant_id' => $participant_id,
            'denomination' => $raison_sociale,
            'adresse' => $adresse,
            'ifu' => $ifu_morale,
            'representant_legal' => $contact_nom,
            'contact_email' => $contact_email,
            'contact_tel' => $contact_tel
        ]);

        // 3. Traitement et insertion des comptes bancaires
        if (isset($_POST['bank_morale']) && is_array($_POST['bank_morale']) &&
            isset($_POST['account_number_morale']) && is_array($_POST['account_number_morale']) &&
            isset($_FILES['rib_pdf_morale']) && is_array($_FILES['rib_pdf_morale']['name'])) {

            $num_comptes = count($_POST['bank_morale']);

            for ($i = 0; $i < $num_comptes; $i++){
                $nom_Banque = trim($_POST['bank_morale'][$i] ?? '');
                $numero_compte_current = trim($_POST['account_number_morale'][$i] ?? '');
                $ribPdfPath = ''; // Initialiser pour chaque itération

                // --- Validation des champs pour ce compte spécifique ---
                // Si la ligne de formulaire est vide (tous les champs vides), on la saute
                if (empty($nom_Banque) && empty($numero_compte_current) && (!isset($_FILES['rib_pdf_morale']['error'][$i]) || $_FILES['rib_pdf_morale']['error'][$i] == UPLOAD_ERR_NO_FILE)) {
                    continue; // Passe au compte suivant
                }

                // Valider si les champs sont obligatoires pour les lignes soumises non vides
                if (empty($nom_Banque)) {
                    $errors[] = "Le nom de la banque est obligatoire pour le compte " . ($i + 1) . ".";
                }
                if (empty($numero_compte_current)) {
                    $errors[] = "Le numéro de compte est obligatoire pour le compte " . ($i + 1) . ".";
                }

                $isFileUploaded = (isset($_FILES['rib_pdf_morale']['error'][$i]) && $_FILES['rib_pdf_morale']['error'][$i] == UPLOAD_ERR_OK);
                if (!$isFileUploaded) { // Si le fichier n'a pas été uploadé pour cette entrée
                    // Vous pouvez ajouter une condition ici si le RIB est obligatoire uniquement si les autres champs sont remplis
                    if (!empty($nom_Banque) || !empty($numero_compte_current)) {
                        $errors[] = "Le fichier RIB PDF est obligatoire pour le compte " . ($i + 1) . ".";
                    }
                }

                // Si des erreurs ont été détectées pour ce compte, passez au suivant sans l'insérer
                if (!empty($errors) && count($errors) > (isset($initial_error_count) ? $initial_error_count : 0)) {
                    // Cette condition vérifie si de nouvelles erreurs spécifiques aux comptes ont été ajoutées.
                    // Si vous voulez que *toute* la transaction échoue à la première erreur de compte,
                    // vous pourriez faire un `throw new Exception("Validation failed for account " . ($i+1));`
                    continue; // Sinon, on continue la boucle pour valider les autres comptes
                }

                // --- Si la validation passe, procédez à l'upload du fichier et à l'insertion en BD ---
                if ($isFileUploaded) {
                    $uploadDir = __DIR__ . '/uploads/ribs/';
                    if (!is_dir($uploadDir)){
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = uniqid('rib_', true) . '_' . basename($_FILES['rib_pdf_morale']['name'][$i]);
                    $uploadFilePath = $uploadDir . $fileName;

                    if (move_uploaded_file ($_FILES['rib_pdf_morale']['tmp_name'][$i], $uploadFilePath)){
                        $ribPdfPath = $uploadFilePath;
                    } else {
                        throw new Exception("Erreur lors du déplacement du fichier RIB pour le compte " . ($i+1) . ".");
                    }
                }

                $sqlQuery2 = 'INSERT INTO comptes_bancaires (participant_id, banque, numero_compte, rib_pdf_path) VALUES (:participant_id, :banque, :numero_compte, :rib_pdf_path)';
                $insertInfoCompte = $mysqlClient->prepare($sqlQuery2);
                $insertInfoCompte->execute([
                    'participant_id' => $participant_id,
                    'banque' => $nom_Banque,
                    'numero_compte' => $numero_compte_current,
                    'rib_pdf_path' => $ribPdfPath
                ]);
            }
        } // Fin du if (isset($_POST['bank']) ...)

        // Si nous arrivons ici sans exceptions et que le tableau $errors est vide, on commit
        if (empty($errors)) {
            $mysqlClient->commit();
            $success = "Participant et ses données enregistrés avec succès !";
            echo "<p style='color:green;'>" . htmlspecialchars($success) . "</p>";
        } else {
            // S'il y a des erreurs (par ex. validation de comptes individuels)
            // Debugging des erreurs d'upload PHP
if (isset($_FILES['rib_pdf_morale']['error'][$i]) && $_FILES['rib_pdf_morale']['error'][$i] != UPLOAD_ERR_OK) {
    $php_upload_error_code = $_FILES['rib_pdf_morale']['error'][$i];
    $php_upload_errors = [
        UPLOAD_ERR_OK         => "No error",
        UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
        UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
    ];
    $error_message = $php_upload_errors[$php_upload_error_code] ?? "Unknown upload error";
    echo "<p style='color:orange;'>Erreur PHP d'upload pour le compte " . ($i+1) . " (Code: " . $php_upload_error_code . "): " . htmlspecialchars($error_message) . "</p>";
    // Si une erreur d'upload PHP est détectée, nous ne devons pas essayer de déplacer le fichier
    // et nous voulons probablement que la transaction échoue.
    throw new Exception("Erreur d'upload du fichier RIB pour le compte " . ($i+1) . ": " . $error_message);
}
            $mysqlClient->rollBack();
            foreach ($errors as $error_msg) {
                echo "<p style='color:red;'>" . htmlspecialchars($error_msg) . "</p>";
            }
        }

    } catch (PDOException $e) {
        $mysqlClient->rollBack();
        error_log("Erreur BD lors de l'insertion : " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        echo "<p style='color:red;'>Une erreur de base de données est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    } catch (Exception $e) {
        $mysqlClient->rollBack();
        error_log("Erreur application lors de l'insertion : " . $e->getMessage());
        echo "<p style='color:red;'>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Personne Morale - Plateforme Financière</title>
    <link rel="stylesheet" href="class1.css">
    <link rel="icon" href="./images/favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <div class="header-top">
            <div class="header-content">
                <img src="./images/logo.png" alt="Logo de l'entreprise" id="logo">
                <div class="site-branding">
                    <h1>Plateforme de Gestion Financière</h1>
                    <p>Votre partenaire pour une gestion optimisée</p>
                </div>
            </div>
            <div class="header-utility">
                <div class="search-bar">
                    <input type="search" placeholder="Rechercher...">
                    <button type="submit">Rechercher</button>
                </div>
                <nav class="utility-nav">
                    <ul>
                        <li><a href="aide.html">Aide</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="accueil.html">Accueil</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn active">Participants & Activités</a>
                    <div class="dropdown-content">
                        <a href="inscription_part.php">Enregistrer un participant</a>
                        <a href="inscription_etat.php">Inscription Agent de l'État</a>
                        <a href="inscription_morale.php" class="active">Inscription Personne Morale</a>
                        <a href="gerer-participant.html">Gérer les participants</a>
                        <a href="creer_activite.html">Créer une activité</a>
                        <a href="gerer_activite.html">Gérer les activités</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Tableaux de bord</a>
                    <div class="dropdown-content">
                        <a href="dashboard_financier.html">Tableau de bord financier</a>
                        <a href="#">Autres tableaux de bord</a>
                    </div>
                </li>
                <li><a href="rapports.html">Rapports</a></li>
                <li><a href="parametres.html">Paramètres</a></li>
                <li><a href="login.html">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="form-section">
            <h2>Inscription d'une Personne Morale</h2>
            <p class="form-description">Remplissez ce formulaire pour l'enrôlement d'une entité (entreprise, organisation, etc.).</p>

            <form action="#" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>Informations d'Identification de la Personne Morale</legend>
                    <div class="form-group">
                        <label for="raison-sociale">Raison Sociale :</label>
                        <input type="text" id="raison-sociale" name="raison_sociale" required placeholder="Nom de l'entreprise/organisation">
                    </div>
                    <div class="form-group">
                        <label for="ifu-morale">IFU (Identifiant Fiscal Unique) :</label>
                        <input type="text" id="ifu-morale" name="ifu_morale" required placeholder="IFU de la personne morale">
                    </div>
                    <div class="form-group">
                        <label for="adresse">Adresse du siège social :</label>
                        <input type="text" id="adresse" name="adresse" placeholder="Adresse complète">
                    </div>
                    <div class="form-group">
                        <label for="contact-nom">Nom du contact principal :</label>
                        <input type="text" id="contact-nom" name="contact_nom">
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email du contact principal :</label>
                        <input type="email" id="contact-email" name="contact_email" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-tel">Téléphone du contact principal :</label>
                        <input type="tel" id="contact-tel" name="contact_tel">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Coordonnées Bancaires de la Personne Morale</legend>
                    <div id="bank-accounts-container-morale">
                        <div class="bank-account-block" style="border: 1px dashed #e0e0e0; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                            <div class="form-group">
                                <label for="bank-morale-1">Banque:</label>
                                <input type="text" id="bank-morale-1" name="bank_morale[]" required placeholder="Nom de la banque">
                            </div>
                            <div class="form-group">
                                <label for="account-number-morale-1">Numéro de compte bancaire:</label>
                                <input type="text" id="account-number-morale-1" name="account_number_morale[]" required placeholder="RIB/Numéro de compte">
                            </div>
                            <div class="form-group">
                                <label for="rib-pdf-morale-1">Copie PDF du RIB:</label>
                                <input type="file" id="rib-pdf-morale-1" name="rib_pdf_morale[]" accept="application/pdf" required>
                                <small>Fichier PDF uniquement.</small>
                            </div>
                            <button type="button" class="btn secondary btn-small remove-bank-account-morale" style="display:none;">Supprimer ce compte</button>
                        </div>
                    </div>
                    <button type="button" class="btn primary btn-small" id="add-bank-account-morale" style="margin-top: 10px;">Ajouter un autre compte bancaire</button>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Enregistrer la Personne Morale</button>
                    <button type="reset" class="btn secondary">Annuler</button>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Plateforme de Gestion Financière. Tous droits réservés.</p>
    </footer>

    <script>
        // JavaScript pour ajouter/supprimer des blocs de comptes bancaires pour la personne morale
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('bank-accounts-container-morale');
            const addButton = document.getElementById('add-bank-account-morale');
            let accountIndex = 1;

            function updateRemoveButtons() {
                const removeButtons = container.querySelectorAll('.remove-bank-account-morale');
                if (removeButtons.length > 1) {
                    removeButtons.forEach(button => button.style.display = 'inline-block');
                } else {
                    removeButtons.forEach(button => button.style.display = 'none');
                }
            }

            updateRemoveButtons();

            addButton.addEventListener('click', function() {
                accountIndex++;
                const newBlock = document.createElement('div');
                newBlock.classList.add('bank-account-block');
                newBlock.style.cssText = "border: 1px dashed #e0e0e0; padding: 15px; margin-bottom: 15px; border-radius: 5px;";
                newBlock.innerHTML = `
                    <div class="form-group">
                        <label for="bank-morale-${accountIndex}">Banque:</label>
                        <input type="text" id="bank-morale-${accountIndex}" name="bank_morale[]" required placeholder="Nom de la banque">
                    </div>
                    <div class="form-group">
                        <label for="account-number-morale-${accountIndex}">Numéro de compte bancaire:</label>
                        <input type="text" id="account-number-morale-${accountIndex}" name="account_number_morale[]" required placeholder="RIB/Numéro de compte">
                    </div>
                    <div class="form-group">
                        <label for="rib-pdf-morale-${accountIndex}">Copie PDF du RIB:</label>
                        <input type="file" id="rib-pdf-morale-${accountIndex}" name="rib_pdf_morale[]" accept="application/pdf" required>
                        <small>Fichier PDF uniquement.</small>
                    </div>
                    <button type="button" class="btn secondary btn-small remove-bank-account-morale">Supprimer ce compte</button>
                `;
                container.appendChild(newBlock);
                updateRemoveButtons();
            });

            container.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-bank-account-morale')) {
                    event.target.closest('.bank-account-block').remove();
                    updateRemoveButtons();
                }
            });
        });
    </script>
</body>
</html>