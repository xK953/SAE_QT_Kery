<?php
require 'db.php';

if (isset($_POST['inscription'])) {
    $pseudo = $_POST['pseudo'];
    $email = $_POST['email'];
    $mdp_en_clair = $_POST['mdp'];
    $hash_mdp = password_hash($mdp_en_clair, PASSWORD_DEFAULT);

    try {
        $requete = $pdo->prepare("INSERT INTO Utilisateurs (pseudo, email, hash_mdp) VALUES (:pseudo, :email, :hash)");
        $requete->execute([
            ':pseudo' => $pseudo,
            ':email' => $email,
            ':hash' => $hash_mdp
        ]);

        // --- LA SOLUTION EST ICI ---
        // Au lieu de faire un simple 'echo', on redirige l'utilisateur vers la page de connexion
        // Le 'exit;' est obligatoire juste après un 'header()' pour stopper le script.
        header("Location: login.php?succes=1");
        exit;
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<p style='color: red;'>Erreur : Cet email est déjà utilisé.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'inscription : " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Gestionnaire de Voyage</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <h2>Créer un compte</h2>
    
    <form action="" method="POST">
        <label for="pseudo">Pseudo :</label>
        <input type="text" id="pseudo" name="pseudo" required>
        <br><br>

        <label for="email">Adresse Email :</label>
        <input type="email" id="email" name="email" required>
        <br><br>

        <label for="mdp">Mot de passe :</label>
        <input type="password" id="mdp" name="mdp" required>
        <br><br>

        <button type="submit" name="inscription">S'inscrire</button>
    </form>
    <p class="connexion">
        Vous avez déjà un compte ? <a href="login.php">Se connecter </a>
    </p>
</body>
</html>
