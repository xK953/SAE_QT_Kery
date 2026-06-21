<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

$erreur = "";

if (isset($_POST['creer_voyage'])) {
    $titre_destination = trim($_POST['titre_destination']);
    $date_debut = $_POST['date_debut'];
    $duree_jours = (int) $_POST['duree_jours'];
    $id_utilisateur = (int) $_SESSION['id_utilisateur'];

    if ($titre_destination === "" || $date_debut === "" || $duree_jours <= 0) {
        $erreur = "Veuillez remplir correctement tous les champs.";
    } else {
        try {
            $pdo->beginTransaction();

            $requete = $pdo->prepare("
                INSERT INTO Voyages (id_createur, titre_destination, date_debut, duree_jours)
                VALUES (:id_createur, :titre_destination, :date_debut, :duree_jours)
            ");
            $requete->execute([
                ':id_createur' => $id_utilisateur,
                ':titre_destination' => $titre_destination,
                ':date_debut' => $date_debut,
                ':duree_jours' => $duree_jours
            ]);

            $id_voyage = $pdo->lastInsertId();

            $requete = $pdo->prepare("
                INSERT INTO Participants (id_utilisateur, id_voyage)
                VALUES (:id_utilisateur, :id_voyage)
            ");
            $requete->execute([
                ':id_utilisateur' => $id_utilisateur,
                ':id_voyage' => $id_voyage
            ]);

            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreur = "Une erreur est survenue lors de la creation du voyage.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau voyage - Planificateur de vacances</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>Creer un nouveau voyage</h2>
        <a href="index.php" class="btn-deconnexion">Retour</a>
    </header>

    <main class="dashboard-main">
        <?php if (!empty($erreur)): ?>
            <div class="message-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="titre_destination">Titre / destination :</label>
            <input
                type="text"
                id="titre_destination"
                name="titre_destination"
                required
            >

            <label for="date_debut">Date de depart :</label>
            <input
                type="date"
                id="date_debut"
                name="date_debut"
                required
            >

            <label for="duree_jours">Duree en jours :</label>
            <input
                type="number"
                id="duree_jours"
                name="duree_jours"
                min="1"
                required
            >

            <button type="submit" name="creer_voyage">Creer le voyage</button>
        </form>
    </main>
    <script src="sae.js"></script>
</body>
</html>
