<?php
require 'db.php';
?> 


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Financier - Gestion des Paiements</title>
    <link rel="stylesheet" href="class1.css">
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
                    <input type="search" placeholder="Rechercher une activité..." aria-label="Rechercher">
                    <button type="submit">Rechercher</button>
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
                        <a href="gerer_activites.php">Gérer Activité</a>
                    </div>
                </li>
                <li><a href="#">Participants</a></li>
                <li><a href="#">Mon Profil</a></li>
                <li><a href="login.html">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="form-section">
            <h2>Créer une Nouvelle Activité</h2>
            <p class="form-description">Remplissez le formulaire ci-dessous pour définir une nouvelle activité.</p>

            <form action="#" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>Informations Générales de l’Activité</legend>

                    <div class="form-group">
                        <label for="activityName">Nom de l’activité :</label>
                        <input type="text" id="activityName" name="activityName" placeholder="Ex. Examen BEPC, Formation RH" minlength="5" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label for="activityDescription">Description :</label>
                        <textarea id="activityDescription" name="activityDescription" rows="5" placeholder="Brève description de l’activité…" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Premier_Responsable">Premier Responsable de l'activité:</label>
                        <textarea id="Premier_Responsable" name="Premier_Responsable" placeholder="Inscrivez ici le nom du premier Responsable de l 'activité" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Organisateur">Organisateur:</label>
                        <textarea id="Organisateur" name="Organisateur" placeholder="Inscrivez ici, le nom de L'Organisateur" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="Financier">Financier :</label>
                        <textarea id="Financier" name="Financier"  placeholder="Inscrivez ici le nom du financier" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="startDate">Date de début :</label>
                        <input type="date" id="startDate" name="startDate" required>
                    </div>

                    <div class="form-group">
                        <label for="endDate">Date de fin :</label>
                        <input type="date" id="endDate" name="endDate" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Lieu de l’activité :</label>
                        <input type="text" id="location" name="location" placeholder="Ex. Palais des Congrès, Cotonou" maxlength="100">
                    </div>

                     <div class="form-group">
                                <label for="note_generatrice"> Note generatrice:</label>
                                <input type="file" id="note_generatrice" name="note_generatrice" accept="application/pdf" required>
                                <small>Fichier PDF uniquement.</small>
                     </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Créer l’Activité</button>
                    <button type="reset" class="btn secondary">Réinitialiser le formulaire</button>
                    <a href="tableau_de_bord_financier.html" class="btn secondary">Annuler et Retourner au Tableau de Bord</a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Trésor Public Bénin. Tous droits réservés.</p>
    </footer>
</body>
</html>
