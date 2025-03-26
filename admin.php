<?php
require_once 'session_handler.php'; // Gestion de session et inactivité
require_once 'db_connect.php';

// 🔧 Affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 📌 Traitement des formulaires POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ➕ Ajouter un événement
    if (isset($_POST['ajouter_event'])) {
        $titre       = $_POST['titre'];
        $description = $_POST['description'];
        $date_event  = $_POST['date_event'];
        $heure       = $_POST['heure'];
        $prix        = isset($_POST['prix']) ? $_POST['prix'] : 0;

        $stmt = $pdo->prepare("INSERT INTO evenements (titre, description, date_event, heure, prix) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $date_event, $heure, $prix]);

        header("Location: admin.php");
        exit;
    }

    // 🔄 Modifier le rôle d'un utilisateur
    if (isset($_POST['modifier_role'])) {
        $user_id = $_POST['user'];
        $role    = $_POST['role'];

        // Une seule requête suffit
        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = :role WHERE id_utilisateur = :user_id");
        $stmt->execute(['role' => $role, 'user_id' => $user_id]);

        header("Location: admin.php");
        exit;
    }

    // ✏️ Modifier un événement
    if (isset($_POST['modifier_event'])) {
        $event_id    = $_POST['event_id'];
        $titre       = $_POST['titre'];
        $description = $_POST['description'];
        $date_event  = $_POST['date_event'];
        $heure       = $_POST['heure'];
        $prix        = $_POST['prix'];

        $stmt = $pdo->prepare("UPDATE evenements SET titre = ?, description = ?, date_event = ?, heure = ?, prix = ? WHERE id = ?");
        $stmt->execute([$titre, $description, $date_event, $heure, $prix, $event_id]);

        header("Location: admin.php");
        exit;
    }
}

// 🗑️ Suppression d’un événement (et de ses données liées)
if (isset($_GET['supprimer'])) {
    $event_id = $_GET['supprimer'];

    $pdo->prepare("DELETE FROM paiements WHERE event_id = ?")->execute([$event_id]);
    $pdo->prepare("DELETE FROM participants WHERE event_id = ?")->execute([$event_id]);
    $pdo->prepare("DELETE FROM evenements WHERE id = ?")->execute([$event_id]);

    header("Location: admin.php");
    exit;
}

// 📥 Récupération des données à afficher
$evenements = $pdo->query("SELECT * FROM evenements ORDER BY date_event ASC")->fetchAll(PDO::FETCH_ASSOC);
$users_result = $pdo->query("SELECT id_utilisateur, prenom, nom, role FROM utilisateurs");

// 📋 Si on veut modifier un événement
if (isset($_GET['modifier'])) {
    $event_id = $_GET['modifier'];
    $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration</title>

    <!-- 📦 Styles et bibliothèques -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include("nav.php"); ?>

    <div class="admin-page">
        <div class="container">
            <h1 class="admin-title text-center">Administration</h1>

            <div class="row">
                <!-- 👥 Gestion des rôles -->
                <div class="col-md-6">
                    <div class="admin-card">
                        <h4>Gestion des Rôles</h4>
                        <form class="admin-form" method="POST">
                            <div class="mb-3">
                                <label for="user" class="form-label">Sélectionner un utilisateur :</label>
                                <select name="user" id="user" class="form-select">
                                    <?php while ($user = $users_result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <option value="<?= $user['id_utilisateur']; ?>">
                                            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?> (<?= htmlspecialchars($user['role']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Sélectionner un rôle :</label>
                                <select name="role" id="role" class="form-select">
                                    <option value="admin">Admin</option>
                                    <option value="premium">Premium</option>
                                    <option value="user">Utilisateur</option>
                                    <option value="moderator">Modérateur</option>
                                </select>
                            </div>

                            <button type="submit" name="modifier_role" class="btn btn-primary admin-btn w-100">Mettre à jour le rôle</button>
                        </form>
                    </div>
                </div>

                <!-- 📅 Ajout d'événements -->
                <div class="col-md-6">
                    <div class="admin-card">
                        <h4>Ajout d'un Événement</h4>
                        <form class="admin-form" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Titre</label>
                                <input type="text" name="titre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_event" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Heure</label>
                                <input type="time" name="heure" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prix (€)</label>
                                <input type="number" name="prix" class="form-control" min="0" step="0.01" required>
                            </div>

                            <button type="submit" name="ajouter_event" class="btn btn-success admin-btn w-100">Ajouter l'événement</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 📄 Liste des événements -->
            <div class="admin-card mt-4">
                <h4>Liste des événements</h4>
                <table class="table admin-table table-striped">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Actions</th>
                            <th>Prix (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evenements as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['titre']) ?></td>
                                <td><?= htmlspecialchars($event['description']) ?></td>
                                <td><?= date("d/m/Y", strtotime($event['date_event'])) ?></td>
                                <td><?= date("H:i", strtotime($event['heure'])) ?></td>
                                <td>
                                    <a href="admin.php?supprimer=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet événement ?')">Supprimer</a>
                                    <a href="admin.php?modifier=<?= $event['id'] ?>" class="btn btn-danger btn-sm">Modifier</a>
                                </td>
                                <td><?= number_format($event['prix'], 2, ',', ' ') ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 📝 Formulaire de modification d’un événement -->
                <?php if (isset($_GET['modifier']) && isset($event)): ?>
                    <div class="admin-card mt-4">
                        <h4>Modifier l'événement</h4>
                        <form class="admin-form" method="POST">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Titre</label>
                                <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($event['titre']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" required><?= htmlspecialchars($event['description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_event" class="form-control" value="<?= $event['date_event'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Heure</label>
                                <input type="time" name="heure" class="form-control" value="<?= $event['heure'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix (€)</label>
                                <input type="number" name="prix" class="form-control" value="<?= $event['prix'] ?>" min="0" step="0.01" required>
                            </div>

                            <button type="submit" name="modifier_event" class="btn btn-success admin-btn w-100">Mettre à jour l'événement</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 📦 JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include("footer.php"); ?>
</body>
</html>
