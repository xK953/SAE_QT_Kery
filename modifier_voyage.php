<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_voyage = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$erreur = "";
$succes = "";

if ($id_voyage <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $requete = $pdo->prepare("
        SELECT COUNT(*)
        FROM Participants
        WHERE id_voyage = :id_voyage
        AND id_utilisateur = :id_utilisateur
    ");
    $requete->execute([
        ':id_voyage' => $id_voyage,
        ':id_utilisateur' => $id_utilisateur
    ]);

    if ((int) $requete->fetchColumn() === 0) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur lors de la verification des droits.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'modifier_voyage') {
            $titre_destination = trim($_POST['titre_destination']);
            $date_debut = $_POST['date_debut'];
            $duree_jours = (int) $_POST['duree_jours'];

            if ($titre_destination === "" || $date_debut === "" || $duree_jours <= 0) {
                $erreur = "Veuillez remplir correctement les informations du voyage.";
            } else {
                $requete = $pdo->prepare("
                    SELECT MAX(num_jour)
                    FROM Etapes
                    WHERE id_voyage = :id_voyage
                ");
                $requete->execute([':id_voyage' => $id_voyage]);
                $dernier_jour_etape = (int) $requete->fetchColumn();

                if ($dernier_jour_etape > $duree_jours) {
                    $erreur = "La duree est trop courte par rapport aux etapes deja ajoutees.";
                } else {
                    $requete = $pdo->prepare("
                        UPDATE Voyages
                        SET titre_destination = :titre_destination,
                            date_debut = :date_debut,
                            duree_jours = :duree_jours
                        WHERE id_voyage = :id_voyage
                    ");
                    $requete->execute([
                        ':titre_destination' => $titre_destination,
                        ':date_debut' => $date_debut,
                        ':duree_jours' => $duree_jours,
                        ':id_voyage' => $id_voyage
                    ]);

                    $succes = "Le voyage a bien ete modifie.";
                }
            }
        }

        if ($action === 'ajouter_participant') {
            $email = trim($_POST['email']);

            if ($email === "") {
                $erreur = "Veuillez saisir l'email du participant.";
            } else {
                $requete = $pdo->prepare("
                    SELECT id_utilisateur
                    FROM Utilisateurs
                    WHERE email = :email
                ");
                $requete->execute([':email' => $email]);
                $participant = $requete->fetch(PDO::FETCH_ASSOC);

                if (!$participant) {
                    $erreur = "Aucun utilisateur ne correspond a cet email.";
                } else {
                    $requete = $pdo->prepare("
                        INSERT IGNORE INTO Participants (id_utilisateur, id_voyage)
                        VALUES (:id_utilisateur, :id_voyage)
                    ");
                    $requete->execute([
                        ':id_utilisateur' => $participant['id_utilisateur'],
                        ':id_voyage' => $id_voyage
                    ]);

                    $succes = "Le participant a bien ete ajoute.";
                }
            }
        }

        if ($action === 'supprimer_participant') {
            $id_participant = (int) $_POST['id_participant'];

            $requete = $pdo->prepare("
                SELECT COUNT(*)
                FROM Participants
                WHERE id_voyage = :id_voyage
            ");
            $requete->execute([':id_voyage' => $id_voyage]);
            $nombre_participants = (int) $requete->fetchColumn();

            if ($nombre_participants <= 1) {
                $erreur = "Il doit rester au moins un participant au voyage.";
            } else {
                $requete = $pdo->prepare("
                    DELETE FROM Participants
                    WHERE id_voyage = :id_voyage
                    AND id_utilisateur = :id_utilisateur
                ");
                $requete->execute([
                    ':id_voyage' => $id_voyage,
                    ':id_utilisateur' => $id_participant
                ]);

                if ($id_participant === $id_utilisateur) {
                    header("Location: index.php");
                    exit;
                }

                $succes = "Le participant a bien ete retire.";
            }
        }

        if ($action === 'ajouter_etape') {
            $nom_etape = trim($_POST['nom_etape']);
            $num_jour = (int) $_POST['num_jour'];
            $prix = (float) str_replace(',', '.', $_POST['prix']);

            $requete = $pdo->prepare("
                SELECT duree_jours
                FROM Voyages
                WHERE id_voyage = :id_voyage
            ");
            $requete->execute([':id_voyage' => $id_voyage]);
            $duree_jours = (int) $requete->fetchColumn();

            if ($nom_etape === "" || $num_jour <= 0 || $num_jour > $duree_jours || $prix < 0) {
                $erreur = "Veuillez saisir une etape valide.";
            } else {
                $requete = $pdo->prepare("
                    INSERT INTO Etapes (id_voyage, nom_etape, num_jour, prix)
                    VALUES (:id_voyage, :nom_etape, :num_jour, :prix)
                ");
                $requete->execute([
                    ':id_voyage' => $id_voyage,
                    ':nom_etape' => $nom_etape,
                    ':num_jour' => $num_jour,
                    ':prix' => $prix
                ]);

                $succes = "L'etape a bien ete ajoutee.";
            }
        }

        if ($action === 'supprimer_etape') {
            $id_etape = (int) $_POST['id_etape'];

            $requete = $pdo->prepare("
                DELETE FROM Etapes
                WHERE id_etape = :id_etape
                AND id_voyage = :id_voyage
            ");
            $requete->execute([
                ':id_etape' => $id_etape,
                ':id_voyage' => $id_voyage
            ]);

            $succes = "L'etape a bien ete supprimee.";
        }
    } catch (PDOException $e) {
        $erreur = "Une erreur est survenue pendant la modification.";
    }
}

try {
    $requete = $pdo->prepare("
        SELECT id_voyage, titre_destination, date_debut, duree_jours
        FROM Voyages
        WHERE id_voyage = :id_voyage
    ");
    $requete->execute([':id_voyage' => $id_voyage]);
    $voyage = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$voyage) {
        header("Location: index.php");
        exit;
    }

    $requete = $pdo->prepare("
        SELECT u.id_utilisateur, u.pseudo, u.email
        FROM Utilisateurs u
        JOIN Participants p ON u.id_utilisateur = p.id_utilisateur
        WHERE p.id_voyage = :id_voyage
        ORDER BY u.pseudo ASC
    ");
    $requete->execute([':id_voyage' => $id_voyage]);
    $participants = $requete->fetchAll(PDO::FETCH_ASSOC);

    $requete = $pdo->prepare("
        SELECT id_etape, nom_etape, num_jour, prix
        FROM Etapes
        WHERE id_voyage = :id_voyage
        ORDER BY num_jour ASC, id_etape ASC
    ");
    $requete->execute([':id_voyage' => $id_voyage]);
    $etapes = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors du chargement du voyage.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un voyage - Planificateur de vacances</title>
    <link rel="stylesheet" href="sae.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>Modifier le voyage</h2>
        <a href="index.php" class="btn-deconnexion">Retour</a>
    </header>

    <main class="dashboard-main">
        <?php if (!empty($erreur)): ?>
            <div class="message-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="message-succes"><?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <section class="section-modification">
            <h3>Informations du voyage</h3>
            <form action="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" method="POST">
                <input type="hidden" name="action" value="modifier_voyage">

                <label for="titre_destination">Titre / destination :</label>
                <input
                    type="text"
                    id="titre_destination"
                    name="titre_destination"
                    value="<?= htmlspecialchars($voyage['titre_destination']) ?>"
                    required
                >

                <label for="date_debut">Date de depart :</label>
                <input
                    type="date"
                    id="date_debut"
                    name="date_debut"
                    value="<?= htmlspecialchars($voyage['date_debut']) ?>"
                    required
                >

                <label for="duree_jours">Duree en jours :</label>
                <input
                    type="number"
                    id="duree_jours"
                    name="duree_jours"
                    min="1"
                    value="<?= htmlspecialchars($voyage['duree_jours']) ?>"
                    required
                >

                <button type="submit">Enregistrer</button>
            </form>
        </section>

        <section class="section-modification">
            <h3>Participants</h3>

            <?php foreach ($participants as $participant): ?>
                <div class="ligne-liste">
                    <div>
                        <strong><?= htmlspecialchars($participant['pseudo']) ?></strong>
                        <span><?= htmlspecialchars($participant['email']) ?></span>
                    </div>

                    <form action="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" method="POST" class="form-inline">
                        <input type="hidden" name="action" value="supprimer_participant">
                        <input type="hidden" name="id_participant" value="<?= htmlspecialchars($participant['id_utilisateur']) ?>">
                        <button type="submit" class="btn-danger">Retirer</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <form action="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" method="POST" class="form-secondaire">
                <input type="hidden" name="action" value="ajouter_participant">

                <label for="email">Ajouter un participant par email :</label>
                <input type="email" id="email" name="email" required>

                <button type="submit">Ajouter</button>
            </form>
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

                        <form action="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" method="POST" class="form-inline">
                            <input type="hidden" name="action" value="supprimer_etape">
                            <input type="hidden" name="id_etape" value="<?= htmlspecialchars($etape['id_etape']) ?>">
                            <button type="submit" class="btn-danger">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form action="modifier_voyage.php?id=<?= htmlspecialchars($id_voyage) ?>" method="POST" class="form-secondaire">
                <input type="hidden" name="action" value="ajouter_etape">

                <label for="nom_etape">Nom de l'etape :</label>
                <input type="text" id="nom_etape" name="nom_etape" required>

                <label for="num_jour">Jour :</label>
                <input type="number" id="num_jour" name="num_jour" min="1" max="<?= htmlspecialchars($voyage['duree_jours']) ?>" required>

                <label for="prix">Prix :</label>
                <input type="number" id="prix" name="prix" min="0" step="0.01" value="0.00" required>

                <button type="submit">Ajouter l'etape</button>
            </form>
        </section>
    </main>
</body>
</html>
