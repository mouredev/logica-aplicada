<?php

use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\PaymentMethod;

require '../../../vendor/autoload.php';

# Configuracion de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['STRIPE_SECRET_KEY'];
$stripe = new StripeClient($apiKey);

if (!$apiKey) {
    die("La clave de API de Stripe no está configurada en el archivo .env");
}

$cardVisa = 'pm_card_visa_debit';

################  CREAR METODO DE PAGO ######################
function createPaymentMethod(): string
{
    global $stripe;
    
    try {
        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => ["token" => "tok_visa"],
        ]);
        
        if (!$paymentMethod) {
            throw new Exception("Error al crear el Payment Method");
        }

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

function searchPaymentMethod(string $customerId): string
{
    global $stripe;

    $paymentMethods = $stripe->customers->allPaymentMethods(
        $customerId,
        ['type' => 'card']
    );

    if (empty($paymentMethods["data"])) {
        throw new Exception("No se encontraron métodos de pago para el cliente");
    }

    echo "ID del método de pago: {$paymentMethods["data"][0]["id"]} \n";
    return $paymentMethods["data"][0]["id"];
}

#########  Crear un pago  #########

function createPayment(string $customerId, string $paymentMethodId, int $amount, string $currency, string $productId)
{
    global $stripe;
    
    try {

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'payment_method_types' => ['card'],
            'confirm' => true,
            'metadata' => [
                'product_id' => $productId,
            ],
        ]);

        echo "Pago creado con exito! \n";
        echo "ID: {$paymentIntent->id} \n";
        echo "Estado: {$paymentIntent->status} \n";
        echo "Moneda: {$paymentIntent->currency} \n";
        echo "Importe: " . (float) $paymentIntent->amount / 100 . " \n";
    } catch (\Stripe\Exception\CardException $e) {
        error_log("A payment error occurred: {$e->getError()->message}");
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        error_log("An invalid request occurred.");
    } catch (Exception $e) {
        error_log("Another problem occurred, maybe unrelated to Stripe.");
    }
}




#########  Crear un cliente y buscarlo por email #########

function createCustomer(string $name, string $email): string
{
    global $stripe;
    
    try {
        $customerId = searchCustomer($email);
        if ($customerId) {
            return $customerId;
        }
        
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

    try {

        $customer = $stripe->customers->search([
            'query' => 'email:\'' . $email . '\''
        ]);

        if (empty($customer["data"])) {
            throw new Exception("El cliente no existe");
        }

        echo "Cliente encontrado con exito! \n";
        echo "ID: {$customer["data"][0]["id"]} \n";
        echo "Email: {$customer["data"][0]["email"]} \n";

        return $customer["data"][0]["id"];
    } catch (\Exception $e) {
        return "Error al buscar el cliente: {$e->getMessage()}\n";
    }
}

################# ASOCIAR UN METODO DE PAGO A UN CLIENTE #########

function addPaymentMethodToCustomer(string $customerId, string $paymentMethodId): bool
{
    global $stripe;

    try {
        $stripe->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $customerId]
        );

        return true;
    } catch (\Stripe\Exception\CardException $e) {
        echo "Error de tarjeta: {$e->getMessage()} \n";
        throw $e;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo "Error de solicitud: {$e->getMessage()} \n";
        throw $e;
    } catch (Exception $e) {
        // Cualquier otro error
        echo "Error al asociar método de pago: {$e->getMessage()} \n";
        throw $e;
    }
}

################## OBTENER PRODUCTOS #########

function getProduct(): string
{
    global $stripe;

    try {
        $products = $stripe->products->all(['limit' => 1]);

        if (empty($products["data"])) {
            throw new Exception("No se encontraron productos");
        }

        return $products["data"][0]["id"];
    } catch (Exception $e) {
        throw new Exception("Error al obtener el producto: {$e->getMessage()}");
    }
}

function getProductPrice(string $productId): array
{
    global $stripe;

    try {
        $productPrice = $stripe->prices->all(['product' => $productId, 'limit' => 1]);
        $priceData = $productPrice["data"][0];

        $price = $priceData->unit_amount;
        $currency = $priceData->currency;

        $data = [
            "id" => $productPrice["data"][0]["id"],
            "unit_amount" => $price,
            "currency" => $currency
        ];
        return $data;
    } catch (Exception $e) {
        throw new Exception("Error al obtener el precio del producto: {$e->getMessage()}");
    }
}


############## EJECUCIONES ###############


$customerId = createCustomer('Juan Perez', 'juan.perez@gmail.com');

echo "Iniciando creación de método de pago con Stripe...\n";
$paymentMethodId = createPaymentMethod();
echo "Proceso completado.\n";


// addPaymentMethodToCustomer($customerId, $paymentMethodId);


$productId = getProduct();
echo "ID del producto: {$productId} \n";
$price = getProductPrice($productId);


$customerId = searchCustomer('juan.perez@gmail.com');
echo "ID del cliente: {$customerId} \n";
$paymentMethodId = searchPaymentMethod($customerId);
echo "ID del método de pago: {$paymentMethodId} \n";

createPayment(
    $customerId,
    $paymentMethodId,
    $price["unit_amount"],
    $price["currency"],
    $productId
);
