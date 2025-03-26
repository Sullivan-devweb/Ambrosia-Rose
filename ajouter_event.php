<?php
// Connexion à la base de données via PDO
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

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = $_POST['titre'];
    $heure = $_POST['heure'];
    $description = $_POST['description'];
    $date_event = $_POST['date_event'];

    // Prépare et exécute la requête d'insertion
    $sql = "INSERT INTO evenements (titre, description, date_event, heure) VALUES (:titre, :description, :date_event, :heure)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titre' => $titre,
        ':description' => $description,
        ':date_event' => $date_event,
        ':heure' => $heure
    ]);

    // Redirection après succès
    header("Location: evenements.php");
    exit();
}
?>
