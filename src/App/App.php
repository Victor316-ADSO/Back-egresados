<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$aux = new \DI\Container();
AppFactory::setContainer($aux);
$app = AppFactory::create();
$app->setBasePath('/back_egresados');

$container = $app->getContainer();
$app->add(function($request, $handler){
    $response = $handler->handle($request);
    return $response
    ->withHeader('Access-Control-Allow-Origin','*')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

// Cargar rutas, configuración y dependencias
require __DIR__ . '/Routes.php';

require __DIR__ . '/Config.php';

require __DIR__ . '/Dependencies.php';


$app->run();
