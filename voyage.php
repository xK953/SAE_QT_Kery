<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_voyage = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_voyage <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $requete = $pdo->prepare("
        SELECT v.id_voyage, v.titre_destination, v.date_debut, v.duree_jours
        FROM Voyages v
        JOIN Participants p ON v.id_voyage = p.id_voyage
        WHERE v.id_voyage = :id_voyage
        AND p.id_utilisateur = :id_utilisateur
    ");
    $requete->execute([
        ':id_voyage' => $id_voyage,
        ':id_utilisateur' => $id_utilisateur
    ]);
    $voyage = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$voyage) {
        header("Location: index.php");
        exit;
    }

    $requete = $pdo->prepare("
        SELECT u.pseudo, u.email
        FROM Utilisateurs u
        JOIN Participants p ON u.id_utilisateur = p.id_utilisateur
        WHERE p.id_voyage = :id_voyage
        ORDER BY u.pseudo ASC
    ");
    $requete->execute([':id_voyage' => $id_voyage]);
    $participants = $requete->fetchAll(PDO::FETCH_ASSOC);

    $requete = $pdo->prepare("
        SELECT nom_etape, num_jour, prix
        FROM Etapes
        WHERE id_voyage = :id_voyage
        ORDER BY num_jour ASC, id_etape ASC
    ");
    $requete->execute([':id_voyage' => $id_voyage]);
    $etapes = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors du chargement du voyage.");
}

$total = 0;
foreach ($etapes as $etape) {
    $total += (float) $etape['prix'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Details du voyage - Planificateur de vacances</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>Details du voyage</h2>
        <a href="index.php" class="btn-deconnexion">Retour</a>
    </header>

    <main class="dashboard-main">
        <section class="voyage-detail">
            <h3><?= htmlspecialchars($voyage['titre_destination']) ?></h3>
            <p><strong>Date de depart :</strong> <?= date('d/m/Y', strtotime($voyage['date_debut'])) ?></p>
            <p><strong>Duree :</strong> <?= htmlspecialchars($voyage['duree_jours']) ?> jours</p>
            <p><strong>Total du voyage :</strong> <?= number_format($total, 2, ',', ' ') ?> EUR</p>
            <p><strong>Total du voyage par personne :</strong> 
                <?= count($participants) > 0 ? number_format($total / count($participants), 2, ',', ' ') : '0,00' ?> EUR
            </p>

            <a href="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" class="btn-modifier">Modifier ce voyage</a>
        </section>

        <section class="section-modification">
            <h3>Participants</h3>

            <?php if (empty($participants)): ?>
                <p class="message-vide">Aucun participant pour ce voyage.</p>
            <?php else: ?>
                <?php foreach ($participants as $participant): ?>
                    <div class="ligne-liste">
                        <div>
                            <strong><?= htmlspecialchars($participant['pseudo']) ?></strong>
                            <span><?= htmlspecialchars($participant['email']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="section-modification">
            <h3>Etapes</h3>

            <?php if (empty($etapes)): ?>
                <p class="message-vide">Aucune etape pour ce voyage.</p>
            <?php else: ?>
                <?php foreach ($etapes as $etape): ?>
                    <div class="ligne-liste">
                        <div>
                            <strong>Jour <?= htmlspecialchars($etape['num_jour']) ?> - <?= htmlspecialchars($etape['nom_etape']) ?></strong>
                            <span><?= number_format((float) $etape['prix'], 2, ',', ' ') ?> EUR</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script src="sae.js"></script>
</body>
</html>
