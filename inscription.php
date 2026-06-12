<?php
// 1. On inclut le pont vers la base de données
require 'db.php';

// 2. On vérifie si le formulaire a été soumis
if (isset($_POST['inscription'])) {
    
    // 3. On récupère les données tapées par l'utilisateur
    $pseudo = $_POST['pseudo'];
    $email = $_POST['email'];
    $mdp_en_clair = $_POST['mdp'];

    // 4. SÉCURITÉ : On hache le mot de passe (ne jamais stocker en clair !)
    $hash_mdp = password_hash($mdp_en_clair, PASSWORD_DEFAULT);

    try {
        // 5. On prépare la requête SQL d'insertion
        $requete = $pdo->prepare("INSERT INTO Utilisateurs (pseudo, email, hash_mdp) VALUES (:pseudo, :email, :hash)");
        
        // 6. On exécute la requête en injectant les vraies valeurs
        $requete->execute([
            ':pseudo' => $pseudo,
            ':email' => $email,
            ':hash' => $hash_mdp
        ]);

        echo "<p style='color: green;'>Inscription réussie ! Vous pouvez maintenant vous connecter.</p>";
        
    } catch(PDOException $e) {
        // Si l'email existe déjà (grâce à la contrainte UNIQUE mise dans la BDD)
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
</body>
</html>