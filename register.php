<?php
session_start(); // Démarrer la session au tout début
require_once 'db.php'; // Inclure le fichier de connexion à la base de données

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des entrées
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse e-mail n'est pas valide.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // Vérifier si le nom d'utilisateur ou l'e-mail existe déjà
            $stmt = $mysqlClient->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Ce nom d'utilisateur ou cette adresse e-mail est déjà utilisé(e).";
            } else {
                // Hacher le mot de passe avant de l'enregistrer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insérer le nouvel utilisateur dans la base de données
                $stmt = $mysqlClient->prepare("INSERT INTO users (username, email, mot_de_passe) VALUES (:username, :email, :password)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);

                $success_message = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
                // Optionnel: Rediriger vers la page de connexion après l'inscription
                // header("Location: login.php?msg=" . urlencode($success_message));
                // exit();
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'inscription : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="class1.css"> <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1em; }
        .btn-submit:hover { background-color: #218838; }
        .message-error, .message-success { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .links { text-align: center; margin-top: 15px; }
        .links a { color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inscription</h2>

        <?php if (!empty($error_message)): ?>
            <p class="message-error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="message-success"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Adresse e-mail :</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">S'inscrire</button>
            </div>
        </form>
        <div class="links">
            Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici</a>
        </div>
    </div>
</body>
</html>