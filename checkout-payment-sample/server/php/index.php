<?php

error_reporting(0);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once((dirname(dirname(dirname(dirname(__FILE__))))) . '/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(dirname(dirname(dirname(dirname(__FILE__)))));
$dotenv->load();

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->setBasePath("/mercadopago2/checkout-payment-sample");

//REPLACE WITH YOUR ACCESS TOKEN AVAILABLE IN: https://developers.mercadopago.com/panel/credentials
MercadoPago\SDK::setAccessToken("MERCADO_PAGO_SAMPLE_ACCESS_TOKEN");
MercadoPago\SDK::setIntegratorId("dev_24c65fb163bf11ea96500242ac130004");
//$path = ( dirname( dirname( dirname( __FILE__ ) ) ) ) ;


$app->get('/', function (Request $request, Response $response, $args) {
    $loader = new FilesystemLoader(__DIR__ . '/../../client');
    $twig = new Environment($loader);

    $response->getBody()->write($twig->render('index.html', ['public_key' => $_ENV["MERCADO_PAGO_SAMPLE_PUBLIC_KEY"]]));
    return $response;
});



// process create_preference
$app->post('/create_preference', function (Request $request, Response $response, $args) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $payer = new MercadoPago\Payer();
        $payer->name = "Lalo";
        $payer->surname = "Landa";
        $payer->email = "test_user_47713184@testuser.com";
        $payer->date_created = date('Y-m-d') . "T".date('H:i').":41.425-04:00";
        $payer->phone = array(
            "area_code" => "11",
            "number" => "22223333"
        );

        $payer->identification = array(
            "type" => "DNI",
            "number" => "12345678"
        );

        $payer->address = array(
            "street_name" => "Falsa",
            "street_number" => 123,
            "zip_code" => "1111"
        );

        $preference = new MercadoPago\Preference();

        $item = new MercadoPago\Item();
        $item->id = "1234";
        $item->title = $data->description;
        $item->description = $data->description;
        $item->category_id = "book";
        $item->quantity = (int)$data->quantity;
        $item->unit_price = (float)$data->price;

        $preference->items = array($item);

        $preference->payment_methods = array(
            "excluded_payment_methods" => [
                ["id" => "amex"]
            ],
            "excluded_payment_types" => [
                ["id" => "atm"]
            ],
            "installments" => 6
        );
        $preference->back_urls = array(
            "success" => "https://feneg.com.ar/mercadopago2/checkout-payment-sample/feedback",
            "failure" => "https://feneg.com.ar/mercadopago2/checkout-payment-sample/feedback",
            "pending" => "https://feneg.com.ar/mercadopago2/checkout-payment-sample/feedback"
        );
        $preference->auto_return = "approved";
        $preference->payer = $payer;
        $preference->notification_url = "https://webhook.site/9ab4bc9c-8f0e-4986-8eec-f7682dd17267";

        $preference->external_reference = "gaston-alta@hotmail.com";

        $preference->save();

        $response_fields = array(
            'id' => $preference->id,
        );

        $response_body = json_encode($response_fields);
        $response->getBody()->write($response_body);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $exception) {
        $response_fields = array('error_message' => $exception->getMessage());

        $response_body = json_encode($response_fields);
        $response->getBody()->write($response_body);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// process create_preference
$app->post('/feedback', function (Request $request, Response $response, $args) {
    try {
        $response_fields = array(
            'Payment' => $_GET['payment_id'],
            'Status' => $_GET['status'],
            'MerchantOrder' => $_GET['merchant_order_id']
        );

        $response_body = json_encode($response_fields);
        $response->getBody()->write($response_body);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $exception) {
        $response_fields = array('error_message' => $exception->getMessage());

        $response_body = json_encode($response_fields);
        $response->getBody()->write($response_body);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});



$app->get('/{filetype}/{filename}', function (Request $request, Response $response, $args) {
    switch ($args['filetype']) {
        case 'css':
            $fileFolderPath = __DIR__ . '/../../client/css/';
            $mimeType = 'text/css';
            break;

        case 'js':
            $fileFolderPath = __DIR__ . '/../../client/js/';
            $mimeType = 'application/javascript';
            break;

        case 'img':
            $fileFolderPath = __DIR__ . '/../../client/img/';
            $mimeType = 'image/png';
            break;

        default:
            $fileFolderPath = '';
            $mimeType = '';
    }

    $filePath = $fileFolderPath . $args['filename'];

    if (!file_exists($filePath)) {
        return $response->withStatus(404, 'File not found');
    }

    $newResponse = $response->withHeader('Content-Type', $mimeType . '; charset=UTF-8');
    $newResponse->getBody()->write(file_get_contents($filePath));

    return $newResponse;
});

$app->run();
//$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/* switch($path){
    case '':
    case '/':
        require __DIR__ . '/../../client/index.html';
        break;
    case '/create_preference':
        $json = file_get_contents("php://input");
        $data = json_decode($json);

        $preference = new MercadoPago\Preference();

        $item = new MercadoPago\Item();
        $item->title = $data->description;
        $item->quantity = $data->quantity;
        $item->unit_price = $data->price;

        $preference->items = array($item);

        $preference->back_urls = array(
            "success" => "http://localhost:8080/feedback",
            "failure" => "http://localhost:8080/feedback", 
            "pending" => "http://localhost:8080/feedback"
        );
        $preference->auto_return = "approved"; 

        $preference->save();

        $response = array(
            'id' => $preference->id,
        ); 
        echo json_encode($response);
        break;        
    case '/feedback':
        $respuesta = array(
            'Payment' => $_GET['payment_id'],
            'Status' => $_GET['status'],
            'MerchantOrder' => $_GET['merchant_order_id']        
        ); 
        echo json_encode($respuesta);
        break;
    //Server static resources
    default:
        $file = __DIR__ . '/../../client' . $path;
        $extension = end(explode('.', $path));
        $content = 'text/html';
        switch($extension){
            case 'js': $content = 'application/javascript'; break;
            case 'css': $content = 'text/css'; break;
            case 'png': $content = 'image/png'; break;
        }
        header('Content-Type: '.$content);
        readfile($file);          
}
 */