<?php
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

// Crear contenedor DI
$aux = new \DI\Container();
AppFactory::setContainer($aux);
$app = AppFactory::create();
$container = $app->getContainer();

// Establecer base path (solo para Apache con subdirectorio)
// DESCOMENTADO para usar servidor PHP integrado
// $app->setBasePath('/back_egresados');

// Middleware CORS global
$app->add(function($request, $handler){
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin','*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Cargar rutas, configuración y dependencias
require __DIR__ . '/Routes.php';
require __DIR__ . '/Config.php';
require __DIR__ . '/Dependencies.php';

$app->run();
