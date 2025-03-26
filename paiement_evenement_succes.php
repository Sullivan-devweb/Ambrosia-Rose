<?php
require __DIR__ . '/stripe-php-master/init.php'; // Inclure Stripe
require __DIR__ . '/db_connect.php'; // Connexion à la BDD

\Stripe\Stripe::setApiKey("sk_test_51Qx0rBF0zfhK77Yn1AAB2xNvsbaLtg7gf2OAnqf7rFusfqTzuMkrMdqzGsRiU7eqoAWfm8Z00rxb4avXlZMKmeuU002XgfrQi8"); // Clé secrète Stripe

$session_id = $_GET['session_id'] ?? null;
$paiement_id = $_GET['paiement_id'] ?? null;

if (!$session_id || !$paiement_id) {
    die("Erreur : Session Stripe ou ID paiement manquant.");
}

// Vérifier si le `transaction_id` existe bien dans la base
$stmt = $pdo->prepare("SELECT transaction_id FROM paiements WHERE id = ?");
$stmt->execute([$paiement_id]);
$paiement = $stmt->fetch();

if (!$paiement || $paiement['transaction_id'] !== $session_id) {
    die("Erreur : Problème de correspondance entre session_id et transaction_id.");
}

// Récupérer les détails de la session Stripe
try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status === "paid") {
        // Mettre à jour le statut du paiement en "payé"
        $stmt = $pdo->prepare("UPDATE paiements SET status = 'payé' WHERE id = ?");
        $stmt->execute([$paiement_id]);

        // Récupérer `user_id` et `event_id`
        $stmt = $pdo->prepare("SELECT user_id, event_id FROM paiements WHERE id = ?");
        $stmt->execute([$paiement_id]);
        $paiement = $stmt->fetch();

        if ($paiement) {
            $user_id = $paiement['user_id'];
            $event_id = $paiement['event_id'];

            // Vérifier si l'utilisateur est déjà inscrit à l'événement
            $stmt = $pdo->prepare("SELECT id FROM participants WHERE user_id = ? AND event_id = ?");
            $stmt->execute([$user_id, $event_id]);
            $existe = $stmt->fetch();

            if (!$existe) {
                // Inscrire l'utilisateur à l'événement
                $stmt = $pdo->prepare("INSERT INTO participants (user_id, event_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $event_id]);
            }
        }

        // 🔄 Redirection vers evenements.php avec un message
        header("Location: https://ambrosiarose.404cahorsfound.fr/evenements.php?message=paiement_reussi");
        exit();
    } else {
        header("Location: https://ambrosiarose.404cahorsfound.fr/evenements.php?message=paiement_echoue");
        exit();
    }
} catch (Exception $e) {
    header("Location: https://ambrosiarose.404cahorsfound.fr/evenements.php?message=erreur&details=" . urlencode($e->getMessage()));
    exit();
}
?>


