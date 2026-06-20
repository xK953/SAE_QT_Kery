

<?php
session_start();
require 'db.php';

$erreur = "";

// Message de succès venant de inscription.php
$succes = isset($_GET['succes']) ? "Inscription réussie ! Vous pouvez vous connecter." : "";

if (isset($_POST['connexion'])) {
    $email = $_POST['email'];
    $mdp_en_clair = $_POST['mdp'];

    try {
        // On cherche l'utilisateur par son email
        $requete = $pdo->prepare("SELECT * FROM Utilisateurs WHERE email = :email");
        $requete->execute([':email' => $email]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        // On vérifie si l'utilisateur existe ET si le mot de passe est correct
        if ($utilisateur && password_verify($mdp_en_clair, $utilisateur['hash_mdp'])) {
            // Connexion réussie : on stocke les infos en session
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['pseudo'] = $utilisateur['pseudo'];

            // On redirige vers la page principale
            header("Location: index.php");
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }

    } catch(PDOException $e) {
        $erreur = "Erreur lors de la connexion : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Gestionnaire de Voyage</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <h2>Se connecter</h2>

    <?php if ($succes): ?>
        <p style="color: green;"><?= htmlspecialchars($succes) ?></p>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <p style="color: red;"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="email">Adresse Email :</label>
        <input type="email" id="email" name="email" required>
        <br><br>

        <label for="mdp">Mot de passe :</label>
        <input type="password" id="mdp" name="mdp" required>
        <br><br>

        <button type="submit" name="connexion">Se connecter</button>
    </form>

    <p class="connexion">
        Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
    </p>
</body>
</html>
