<?php
// 1. Initialisation de la session et imports
session_start();
require 'db.php';

// 2. Middleware d'authentification (Redirection si non connecté)
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

// 3. Routage des actions simples (Déconnexion)
if (isset($_GET['action']) && $_GET['action'] === 'deconnexion') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 4. Initialisation des variables
$id_utilisateur = $_SESSION['id_utilisateur'];
$voyages = [];
$erreur = "";

// 5. Récupération des données (Logique métier)
try {
    $requete = $pdo->prepare("
        SELECT v.id_voyage, v.titre_destination, v.date_debut, v.duree_jours 
        FROM Voyages v
        JOIN Participants p ON v.id_voyage = p.id_voyage
        WHERE p.id_utilisateur = :id_user
        ORDER BY v.date_debut ASC
    ");
    $requete->execute([':id_user' => $id_utilisateur]);
    $voyages = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // En production, on log l'erreur serveur, mais on affiche un message générique à l'utilisateur
    $erreur = "Une erreur est survenue lors du chargement de vos voyages.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - Planificateur de vacances</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>Bienvenue, <?= htmlspecialchars($_SESSION['pseudo']) ?> ! 👋</h2>
        <a href="index.php?action=deconnexion" class="btn-deconnexion">Se déconnecter</a>
    </header>

    <main class="dashboard-main">
        <section class="dashboard-actions">
            <h3>Mes Voyages Prévus</h3>
            <a href="nouveau_voyage.php" class="btn-nouveau">+ Créer un nouveau voyage</a>
        </section>

        <?php if (!empty($erreur)): ?>
            <div class="message-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <section class="liste-voyages">
            <?php if (empty($voyages)): ?>
                <p class="message-vide">Vous n'avez pas encore de voyage prévu. Commencez par en créer un !</p>
            <?php else: ?>
                <?php foreach ($voyages as $voyage): ?>
                    <article class="voyage-carte">
                        <h4>🌍 <?= htmlspecialchars($voyage['titre_destination']) ?></h4>
                        <p><strong>Départ le :</strong> <?= date('d/m/Y', strtotime($voyage['date_debut'])) ?></p>
                        <p><strong>Durée :</strong> <?= htmlspecialchars($voyage['duree_jours']) ?> jours</p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
    <script src="sae.js"></script>
</body>
</html>
