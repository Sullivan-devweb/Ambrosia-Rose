 <?php
// Connexion à la base de données
$host = "127.0.0.1";
$dbname = "u501368352_ambrosiarose";
$username = "u501368352_ambrosia";
$password = "Obossoooh1";

try {
    // Utilisation de l'encodage utf8mb4
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Gestion des erreurs PDO
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>