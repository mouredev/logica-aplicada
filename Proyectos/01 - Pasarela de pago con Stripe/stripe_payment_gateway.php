<?php

use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\PaymentMethod;

require '../../vendor/autoload.php';

# Configuracion de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = 'sk_test_QOsNVHHTcF6EYMtGgukplNPv0090lbFXgV';
$stripe = new StripeClient($apiKey);

if(!$apiKey){
    die("La clave de API de Stripe no está configurada en el archivo .env");
}

$cardVisa = 'pm_card_visa_debit';

function createPaymentMethod(): PaymentMethod
{
    global $stripe;
    $paymentMethod = $stripe->paymentMethods->create([
        'type' => 'card',
        'card' =>[
          'number' => '4242424242424242',
          'exp_month' => 12,
          'exp_year' => 2025,
          'cvc' => '123',
        ],
    ]);
    
    echo "Payment Method creado con exito! \n";
    echo "ID: {$paymentMethod->id} \n";
    echo "Tipo: {$paymentMethod->type} \n";
    echo "Marca Tarjeta: {$paymentMethod->card->brand} \n";
    echo "Ultimos 4 digitos: {$paymentMethod->card->last4} \n";

    return $paymentMethod;
}

// Llamar a la función para probarla
echo "Iniciando creación de método de pago con Stripe...\n";
createPaymentMethod();
echo "Proceso completado.\n";