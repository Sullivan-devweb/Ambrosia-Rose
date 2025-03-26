<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // ⚠️ Démarrer la session pour récupérer l'utilisateur connecté

require __DIR__ . '/stripe-php-master/init.php';
require __DIR__ . '/db_connect.php';

\Stripe\Stripe::setApiKey("sk_test_51Qx0rBF0zfhK77Yn1AAB2xNvsbaLtg7gf2OAnqf7rFusfqTzuMkrMdqzGsRiU7eqoAWfm8Z00rxb4avXlZMKmeuU002XgfrQi8");

// ✅ Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("❌ Erreur : Vous devez être connecté pour acheter un billet.");
}
$user_id = $_SESSION['user_id']; // ✅ Utiliser l'ID de l'utilisateur connecté

$event_id = $_GET['event_id'] ?? null;
if (!$event_id) {
    die("❌ Erreur : Événement non spécifié.");
}

// ✅ Vérifier si l'événement existe
$stmt = $pdo->prepare("SELECT titre, prix FROM evenements WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) {
    die("❌ Erreur : L'événement est introuvable.");
}

// ✅ Prix en centimes pour Stripe
$prix_en_centimes = $event['prix'] * 100;

// ✅ Étape 1 : Insérer le paiement en base AVANT Stripe
$stmt = $pdo->prepare("INSERT INTO paiements (user_id, event_id, montant, status, transaction_id, methode_paiement) 
                       VALUES (?, ?, ?, 'en attente', NULL, 'stripe')");
$stmt->execute([$user_id, $event_id, $event['prix']]);
$paiement_id = $pdo->lastInsertId(); // ✅ Récupérer l'ID du paiement

// ✅ Étape 2 : Créer une session Stripe Checkout
$checkout_session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => $event['titre'],
            ],
            'unit_amount' => $prix_en_centimes,
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'https://ambrosiarose.404cahorsfound.fr/paiement_evenement_succes.php?session_id={CHECKOUT_SESSION_ID}&paiement_id=' . $paiement_id,
    'cancel_url' => 'https://ambrosiarose.404cahorsfound.fr/evenements.php',
]);

// ✅ Étape 3 : Mettre à jour `transaction_id` dans la base avec `session_id`
$stmt = $pdo->prepare("UPDATE paiements SET transaction_id = ? WHERE id = ?");
$stmt->execute([$checkout_session->id, $paiement_id]);

// 🔄 Rediriger vers Stripe Checkout
header("Location: " . $checkout_session->url);
exit();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paiement - <?= htmlspecialchars($event['titre']) ?></title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <h1>Payer pour l'événement : <?= htmlspecialchars($event['titre']) ?></h1>
    <p>Prix : <?= number_format($event['prix'], 2) ?> €</p>

    <form action="process_paiement.php" method="POST">
        <input type="hidden" name="event_id" value="<?= $event_id ?>">
        <input type="hidden" name="prix" value="<?= $prix_en_centimes ?>">
        <script
            src="https://checkout.stripe.com/checkout.js"
            class="stripe-button"
            data-key="<?= $stripe_public_key ?>"
            data-amount="<?= $prix_en_centimes ?>"
            data-name="Achat de billet"
            data-description="Paiement pour <?= htmlspecialchars($event['titre']) ?>"
            data-currency="eur">
        </script>
    </form>
</body>
</html>

