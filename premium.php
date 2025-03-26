<?php
// Activer l'affichage des erreurs PHP pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_handler.php'; // Gestion de session et inactivité
require_once 'db_connect.php'; // Connexion à la base de données

// Inclure manuellement les fichiers Stripe
require_once 'stripe-php-master/init.php'; // Assurez-vous que le chemin est correct

// Clés API Stripe
\Stripe\Stripe::setApiKey('sk_test_51QuZU9Qh2IE36uBHBNKHRid65xG5zg2uMj213GbRww4dS5aC6fVcsNR15pSEnf4r226uLWyuNPuwEuieUdpzIlGg00BOGR9y2M'); // Remplacez par votre clé secrète

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirige vers la connexion si non connecté
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['stripeToken'];
    $amount = 1999; // Montant en centimes (19.99 euros)

    try {
        // Créer une charge
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'eur',
            'description' => 'Abonnement Premium',
            'source' => $token,
        ]);

        // Mettre à jour le rôle de l'utilisateur dans la base de données
        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = 'premium' WHERE id_utilisateur = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        // Rediriger vers une page de succès
        header("Location: paiement_succes.php");
        exit();

    } catch (\Stripe\Exception\CardException $e) {
        $error_message = $e->getError()->message;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparaison des fonctionnalités - Ambrosia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Styles spécifiques à cette page */
        body {
            font-family: 'Poppins', sans-serif;
            color: #001F54;
            background: linear-gradient(to right, #f8f9fa, #eef2f3);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            margin-top: 20px;
        }

        h1 {
            text-align: center;
            color: #001F54;
            font-size: 2em;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 10px;
        }

        h1::after {
            content: "";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            width: 60%;
            height: 4px;
            background-color: #EEC4C9;
            border-radius: 5px;
        }

        .table th, .table td {
            background-color: #001F54;
            color: #fff;
            font-size: 1em;
        }

        .table td {
            background-color: #fff;
            color: #001F54;
            border-color: #EEC4C9;
        }

        .table .feature {
            text-align: left;
            font-weight: bold;
        }

        .icon.checked {
            color: #4caf50;
        }

        .icon.cross {
            color: #E63946;
        }

        .payment-section {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px solid #EEC4C9;
        }

        .payment-method {
            background: #fff;
            padding: 10px;
            border-radius: 15px;
            border: 2px solid #EEC4C9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
        }

        .payment-method img {
            max-width: 80px; /* Taille uniforme des icônes de paiement */
        }

        .payment-method p {
            margin-top: 10px;
            font-size: 1em;
            color: #001F54;
        }

        .payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .subscribe-button {
            background-color: #EEC4C9;
            color: #001F54;
            font-weight: bold;
            font-size: 1em;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-top: 15px;
        }

        .subscribe-button:hover {
            background-color: #FFC1E3;
            transform: scale(1.05);
        }

        .card-info-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 15px;
            border: 2px solid #EEC4C9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-info-section.active {
            display: block;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .StripeElement {
            background-color: white;
            padding: 10px 12px;
            border: 1px solid #ccd0d2;
            border-radius: 4px;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            margin-bottom: 10px;
        }

        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;
        }
    </style>
</head>
<body>
    <!-- Inclure la navigation -->
    <?php include 'nav.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-grow-1 content-wrapper">
        <div class="container-lg">
            <h1>Comparaison des fonctionnalités</h1>
            <table class="table">
                <thead>
                    <tr>
                        <th class="feature">Fonctionnalité</th>
                        <th class="premium">Premium</th>
                        <th class="free">Gratuit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature">Voir les profils qui ont consulté son profil</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Voir les personnes qui ont liké son profil</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Envoyer des messages illimités</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Ouvrir des discussions privées illimitées</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Créer un événement</td>
                        <td class="premium"><span class="icon cross">❌</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Icône/couronne premium</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">Mise en avant sur la page d'accueil</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon cross">❌</span></td>
                    </tr>
                    <tr>
                        <td class="feature">S'inscrire aux événements de façon illimitée</td>
                        <td class="premium"><span class="icon checked">✔️</span></td>
                        <td class="free"><span class="icon checked">✔️</span></td>
                    </tr>
                </tbody>
            </table>

            <!-- Section des moyens de paiement -->
            <div class="payment-section">
                <h2>Moyens de paiement</h2>
                <div class="row payment-methods justify-content-center">
                    <div class="col-md-4 payment-method text-center" onclick="showCardInfo('visa')">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="img-fluid">
                        <p>Visa</p>
                    </div>
                    <div class="col-md-4 payment-method text-center" onclick="showCardInfo('mastercard')">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard" class="img-fluid">
                        <p>Mastercard</p>
                    </div>
                    <div class="col-md-4 payment-method text-center" onclick="window.location.href='https://www.paypal.com/signin'">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" class="img-fluid">
                        <p>PayPal</p>
                    </div>
                </div>
                <div class="text-center">
                    <button class="subscribe-button" onclick="showCardInfo('none')">Devenir Premium</button>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Section de saisie des informations de carte -->
                <div id="card-info-section" class="card-info-section active">
                    <h3>Informations de carte</h3>
                    <form id="payment-form" method="POST" action="">
                        <div class="mb-3">
                            <label for="card-element" class="form-label">Détails de la carte</label>
                            <div id="card-element" class="StripeElement">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>
                            <!-- Used to display form errors. -->
                            <div id="card-errors" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Payer</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Inclure le footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        var stripe = Stripe('pk_test_51QuZU9Qh2IE36uBHdCX4dS15N3jNXzGjg8GrtSgI3ONhIh69os9KxXjWT2jsTmJbdxF5FA7jaocxss8VjYFsWMPd009wCC0fKp'); // Remplacez par votre clé publique
        var elements = stripe.elements();

        var style = {
            base: {
                color: '#32325d',
                lineHeight: '24px',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        var card = elements.create('card', {style: style});
        card.mount('#card-element');

        card.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripeToken');
                    hiddenInput.setAttribute('value', result.token.id);
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>