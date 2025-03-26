<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // ⚠️ Démarrer la session pour récupérer l'utilisateur connecté

require 'stripe-php-master/init.php';
require 'db_connect.php';

\Stripe\Stripe::setApiKey("sk_test_51Qx0rBF0zfhK77Yn1AAB2xNvsbaLtg7gf2OAnqf7rFusfqTzuMkrMdqzGsRiU7eqoAWfm8Z00rxb4avXlZMKmeuU002XgfrQi8");

// ✅ Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("❌ Erreur : Vous devez être connecté pour effectuer un paiement.");
}
$user_id = $_SESSION['user_id']; // ✅ Utiliser l'ID de l'utilisateur connecté

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'] ?? null;
    $montant = $_POST['prix'] ?? null;

    if (!$event_id || !$montant) {
        die("❌ Erreur : `event_id` ou `montant` manquant !");
    }

    try {
        // ✅ Vérifier si l'événement existe
        $stmt = $pdo->prepare("SELECT id FROM evenements WHERE id = ?");
        $stmt->execute([$event_id]);
        $evenement = $stmt->fetch();
        if (!$evenement) {
            die("❌ Erreur : L'événement avec ID $event_id n'existe pas !");
        }

        // ✅ Créer la session Stripe Checkout
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Ticket pour l'événement",
                    ],
                    'unit_amount' => $montant,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'http://ambrosiarose.404cahorsfound.fr/paiement_evenement_succes.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://ambrosiarose.404cahorsfound.fr/paiement_evenement.php?event_id=' . $event_id,
        ]);

        // ✅ Insérer le paiement en base
        $stmt = $pdo->prepare("INSERT INTO paiements (user_id, event_id, montant, status, transaction_id, methode_paiement) 
                               VALUES (?, ?, ?, 'en attente', ?, 'stripe')");
        $stmt->execute([$user_id, $event_id, $montant / 100, $session->id]);

        // 🔄 Rediriger vers Stripe Checkout
        header("Location: " . $session->url);
        exit();
    } catch (Exception $e) {
        die("<h1>❌ Erreur</h1><p>" . $e->getMessage() . "</p>");
    }
}
?>



