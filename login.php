<?php
session_start();
require_once 'db.php';

$error_message = '';
$success_message = $_GET['msg'] ?? '';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: acceuil.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_message = "Veuillez saisir votre nom d'utilisateur/e-mail et votre mot de passe.";
    } else {
        try {
            // Tenter de récupérer l'utilisateur par nom d'utilisateur ou par e-mail
            $stmt = $mysqlClient->prepare("SELECT id, username, mot_de_passe FROM users WHERE username = :login_user OR email = :login_email");
            $stmt->execute([
                ':login_user' => $username_or_email,
                ':login_email' => $username_or_email // <- Ici, on passe la même valeur pour le second placeholder
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Mot de passe correct, démarrer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header("Location: accueil.html");
                exit();
            } else {
                $error_message = "Nom d'utilisateur / e-mail ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la connexion : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="class1.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1em; }
        .btn-submit:hover { background-color: #0056b3; }
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
        <h2>Connexion</h2>

        <?php if (!empty($error_message)): ?>
            <p class="message-error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="message-success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username_or_email">Nom d'utilisateur ou e-mail :</label>
                <input type="text" id="username_or_email" name="username_or_email" required value="<?php echo htmlspecialchars($username_or_email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">Se connecter</button>
            </div>
        </form>
        <div class="links">
            Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous ici</a>
        </div>
    </div>
</body>
</html>