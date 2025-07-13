<?php
        $host = 'localhost';
        $dbname = 'Gestion_financiere';
        $user = 'root';
        $pass = '';

        try
        {
            $mysqlClient = new PDO(
                "mysql:host=$host;port = 3306;dbname=$dbname;charset=utf8mb4", // Utilisation de utf8mb4 pour un support complet des caractères Unicode
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Lance des exceptions en cas d'erreurs SQL
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // Récupère les résultats sous forme de tableaux associatifs par défaut
                    PDO::ATTR_EMULATE_PREPARES   => false,                       // Désactive l'émulation des requêtes préparées pour une meilleure sécurité et performance
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'          // Assure que le jeu de caractères de la connexion est bien utf8mb4 (utile pour les anciennes versions de MySQL ou certaines configurations)
                ]
            );

            $currentDb = $mysqlClient->query("SELECT DATABASE()")->fetchColumn();
    echo "<p style='color: green;'>PHP est actuellement connecté à la base de données : <strong>" . htmlspecialchars($currentDb) . "</strong></p>";
            // Si la connexion réussit, vous pouvez ajouter un message (optionnel, pour le débogage)
            // echo "Connexion à la base de données réussie !";
        }
        catch(PDOException $e) // Il est préférable de capturer spécifiquement PDOException pour les erreurs de connexion PDO
        {
            // En production, vous ne devriez pas afficher $e->getMessage() directement à l'utilisateur pour des raisons de sécurité.
            // Au lieu de cela, vous devriez logger l'erreur et afficher un message générique.
            die('Erreur de connexion à la base de données : '.$e->getMessage());
        }
?>