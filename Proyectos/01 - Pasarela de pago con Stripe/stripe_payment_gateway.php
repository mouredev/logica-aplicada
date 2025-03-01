<?php

use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\PaymentMethod;

require '../../vendor/autoload.php';

# Configuracion de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


$stripe = new StripeClient($apiKey);

if (!$apiKey) {
    die("La clave de API de Stripe no está configurada en el archivo .env");
}

$cardVisa = 'pm_card_visa_debit';

function createPaymentMethod(): string
{
    try {
        global $stripe;
        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => ["token" => "tok_visa"],
        ]);

        echo "Payment Method creado con exito! \n";
        echo "ID: {$paymentMethod->id} \n";
        echo "Tipo: {$paymentMethod->type} \n";
        echo "Marca Tarjeta: {$paymentMethod->card->brand} \n";
        echo "Ultimos 4 digitos: {$paymentMethod->card->last4} \n";

        return $paymentMethod->id;
    } catch (\Exception $e) {
        return "Error al crear el Payment Method: {$e->getMessage()}\n";
    }
}



#########  Crear un pago  #########

function createPayment(string $customerId, string $paymentMethodId, int $amount, string $currency)
{
    global $stripe;

    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => $amount * 100,
        'currency' => $currency,
        'customer' => $customerId,
        'payment_method' => $paymentMethodId,
        'payment_method_types' => ['card'],
        'confirm' => true,
    ]);

    echo "Pago creado con exito! \n";
    echo "ID: {$paymentIntent->id} \n";
    echo "Estado: {$paymentIntent->status} \n";
    echo "Moneda: {$paymentIntent->currency} \n";
    echo "Importe: " . number_format($paymentIntent->amount / 100, 2) . " \n";
}




#########  Crear un cliente y buscarlo por email #########

function createCustomer(string $name, string $email): string
{
    try {
        global $stripe;

        $customer = $stripe->customers->create([
            'name' => $name,
            'email' => $email,
        ]);

        echo "Cliente creado con exito! \n";
        echo "ID: {$customer->id} \n";
        echo "Email: {$customer->email} \n";

        return $customer->id;
    } catch (\Exception $e) {
        return "Error al crear el cliente: {$e->getMessage()}\n";
    }
}

function searchCustomer(string $email): string
{
    global $stripe;

    $customer = $stripe->customers->search( [
        'query' => 'email:\'' . $email . '\''
    ]);

    echo "Cliente encontrado con exito! \n";
    echo "ID: {$customer["data"][0]["id"]} \n";
    echo "Email: {$customer["data"][0]["email"]} \n";

    return $customer["data"][0]["id"];
}

################# ASOCIAR UN METODO DE PAGO A UN CLIENTE #########

function addPaymentMethodToCustomer(string $customerId, string $paymentMethodId)
{
    global $stripe;

    $stripe->paymentMethods->attach(
        $paymentMethodId,
        ['customer' => $customerId]
    );

    echo "Metodo de pago asociado al cliente con exito! \n";
}

################## OBTENER PRODUCTOS #########

function getProduct()
{
    global $stripe;

    $products = $stripe->products->all(['limit' => 1]);
    echo "Productos: " . $products . "\n";
    return $products["data"][0]["id"];
}

function getProductPrice(string $productId): array
{
    global $stripe;

    $productPrice = $stripe->prices->all(['product' => $productId, 'limit' => 1]);
    
    echo "productPrice: " . $productPrice . "\n";
    
    $price = number_format($productPrice["data"][0]["unit_amount"] / 100, 2);
    $currency = $productPrice["data"][0]["currency"];
    
    $data = array_merge([ "id" => $productPrice["data"][0]["id"]], ["unit_amount" => $price], ["currency" => $currency]);
    return $data;
}


############## EJECUCIONES ###############

// echo "Iniciando creación de método de pago con Stripe...\n";
// $paymentMethodId = createPaymentMethod();
// echo "Proceso completado.\n";

// $customerId = createCustomer('Juan Perez', 'juan.perez@gmail.com');

// addPaymentMethodToCustomer($customerId, $paymentMethodId);

// createPayment($customerId, $paymentMethodId); 

$productId = getProduct();
echo "ID del producto: {$productId} \n";
$price = getProductPrice($productId);
echo "Precio del producto: {$price["unit_amount"]} \n";

$customerId = searchCustomer('juan.perez@gmail.com');
echo "ID del cliente: {$customerId} \n";
