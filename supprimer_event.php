<?php
// Connexion à la base de données
$host = "127.0.0.1";
$dbname = "u501368352_ambrosiarose";
$username = "u501368352_ambrosia";
$password = "Obossoooh1";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Vérifie si l'ID est fourni
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Supprime l'événement
    $stmt = $pdo->prepare("DELETE FROM evenements WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo "Événement supprimé avec succès.";
} else {
    echo "Aucun ID fourni.";
}
?>
