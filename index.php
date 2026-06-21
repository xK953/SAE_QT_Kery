<?php
// 1. Initialisation de la session et imports
session_start();
require 'db.php';

// 2. Middleware d'authentification
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

// 3. Routage des actions simples
if (isset($_GET['action']) && $_GET['action'] === 'deconnexion') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 4. Initialisation des variables
$id_utilisateur = (int) $_SESSION['id_utilisateur'];
$voyages = [];
$erreur = "";
$succes = isset($_GET['supprime']) ? "Le voyage a bien ete supprime." : "";
$id_confirmation_suppression = isset($_GET['confirmer_suppression']) ? (int) $_GET['confirmer_suppression'] : 0;

if (isset($_POST['suppression_confirmee'])) {
    $id_voyage_a_supprimer = (int) $_POST['id_voyage'];

    try {
        $requete = $pdo->prepare("
            SELECT id_createur
            FROM Voyages
            WHERE id_voyage = :id_voyage
        ");
        $requete->execute([':id_voyage' => $id_voyage_a_supprimer]);
        $voyage_a_supprimer = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$voyage_a_supprimer || (int) $voyage_a_supprimer['id_createur'] !== $id_utilisateur) {
            $erreur = "Vous n'etes pas le createur de ce voyage.";
        } else {
            $requete = $pdo->prepare("
                DELETE FROM Voyages
                WHERE id_voyage = :id_voyage
            ");
            $requete->execute([':id_voyage' => $id_voyage_a_supprimer]);

            header("Location: index.php?supprime=1");
            exit;
        }
    } catch(PDOException $e) {
        $erreur = "Une erreur est survenue lors de la suppression du voyage.";
    }
}

// 5. Recuperation des donnees
try {
    $requete = $pdo->prepare("
        SELECT v.id_voyage, v.id_createur, v.titre_destination, v.date_debut, v.duree_jours
        FROM Voyages v
        JOIN Participants p ON v.id_voyage = p.id_voyage
        WHERE p.id_utilisateur = :id_user
        ORDER BY v.date_debut ASC
    ");
    $requete->execute([':id_user' => $id_utilisateur]);
    $voyages = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $erreur = "Une erreur est survenue lors du chargement de vos voyages.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - On Va Où ?</title>
    <link rel="stylesheet" href="sae.css">
    <link rel="icon" type="image/png" href="logo_ovo.png">
</head>
<body>
    <header class="dashboard-header">
        <div class="app-brand">
            <img src="logo_ovo.png" alt="Logo On Va Où ?" class="app-logo">
            <div>
                <strong>On Va Où ?</strong>
                <span>Gestion de voyages et depenses</span>
            </div>
        </div>

        <div class="user-zone">
            <span><?= htmlspecialchars($_SESSION['pseudo']) ?></span>
            <button type="button" id="mode-nuit-toggle" class="btn-mode-nuit">Mode nuit </button>
            <a href="index.php?action=deconnexion" class="btn-deconnexion">Se deconnecter</a>
        </div>
    </header>

    <main class="dashboard-main">
        <section class="dashboard-hero">
            <div>
                <p class="hero-eyebrow">Tableau de bord</p>
                <h2>Bienvenue, <?= htmlspecialchars($_SESSION['pseudo']) ?></h2>
                <p>Organisez vos voyages, vos participants, vos etapes et vos depenses depuis un seul espace.</p>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <span>Voyages</span>
                    <strong><?= count($voyages) ?></strong>
                </div>
                <div class="stat-card">
                    <span>Prochain depart</span>
                    <strong><?= !empty($voyages) ? date('d/m', strtotime($voyages[0]['date_debut'])) : '-' ?></strong>
                </div>
            </div>
        </section>

        <div class="dashboard-grid">
            <section class="dashboard-panel">
                <div class="dashboard-actions">
                    <div>
                        <p class="section-eyebrow">Planification</p>
                        <h3>Mes voyages prevus</h3>
                    </div>
                    <a href="nouveau_voyage.php" class="btn-nouveau">+ Creer un nouveau voyage</a>
                </div>

                <?php if (!empty($erreur)): ?>
                    <div class="message-erreur"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>

                <?php if (!empty($succes)): ?>
                    <div class="message-succes"><?= htmlspecialchars($succes) ?></div>
                <?php endif; ?>

                <section class="liste-voyages">
                    <?php if (empty($voyages)): ?>
                        <p class="message-vide">Vous n'avez pas encore de voyage prevu. Commencez par en creer un !</p>
                    <?php else: ?>
                        <?php foreach ($voyages as $voyage): ?>
                            <article class="voyage-carte">
                                <h4><?= htmlspecialchars($voyage['titre_destination']) ?></h4>
                                <p><strong>Depart le :</strong> <?= date('d/m/Y', strtotime($voyage['date_debut'])) ?></p>
                                <p><strong>Duree :</strong> <?= htmlspecialchars($voyage['duree_jours']) ?> jours</p>
                                <p
                                    class="date-retour-liste"
                                    data-date-debut="<?= htmlspecialchars($voyage['date_debut']) ?>"
                                    data-duree-jours="<?= htmlspecialchars($voyage['duree_jours']) ?>"
                                ></p>
                                <div class="voyage-actions">
                                    <a href="voyage.php?id=<?= htmlspecialchars($voyage['id_voyage']) ?>" class="btn-voir">Voir</a>
                                    <a href="modifier_voyage.php?id=<?= htmlspecialchars($voyage['id_voyage']) ?>" class="btn-modifier">Modifier</a>
                                    <a href="index.php?confirmer_suppression=<?= htmlspecialchars($voyage['id_voyage']) ?>" class="btn-supprimer">Supprimer</a>
                                </div>

                                <?php if ($id_confirmation_suppression === (int) $voyage['id_voyage']): ?>
                                    <div class="confirmation-suppression">
                                        <?php if ((int) $voyage['id_createur'] === $id_utilisateur): ?>
                                            <p>Voulez-vous vraiment supprimer ce voyage et toutes ses informations ?</p>
                                            <form action="index.php" method="POST" class="form-confirmation">
                                                <input type="hidden" name="id_voyage" value="<?= htmlspecialchars($voyage['id_voyage']) ?>">
                                                <button type="submit" name="suppression_confirmee" class="btn-danger">Confirmer la suppression</button>
                                            </form>
                                        <?php else: ?>
                                            <p>Vous n'etes pas le createur de ce voyage.</p>
                                        <?php endif; ?>
                                        <a href="index.php" class="btn-annuler">Annuler</a>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </section>

            <aside class="dashboard-sidebar">
                <section class="side-card">
                    <p class="section-eyebrow">Resume</p>
                    <h3>Activite</h3>
                    <div class="resume-ligne">
                        <span>Voyages suivis</span>
                        <strong><?= count($voyages) ?></strong>
                    </div>
                    <div class="resume-ligne">
                        <span>Compte</span>
                        <strong><?= htmlspecialchars($_SESSION['pseudo']) ?></strong>
                    </div>
                </section>

                <section class="side-card side-card-accent">
                    <p class="section-eyebrow">Conseil</p>
                    <h3>Prochaine etape</h3>
                    <?php if (empty($voyages)): ?>
                        <p>Creer un premier voyage pour commencer a ajouter des participants et des etapes.</p>
                    <?php else: ?>
                        <p>Ouvrez un voyage pour consulter les participants, les etapes et le total des depenses.</p>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="sae.js"></script>
</body>
</html>
