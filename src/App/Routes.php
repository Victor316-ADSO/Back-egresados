<?php
use Slim\Routing\RouteCollectorProxy;

// Middleware para CORS - aplicar a todas las rutas ANTES de las rutas
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Middleware para añadir headers CORS a todas las respuestas
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});


// Agrupar rutas bajo el prefijo /api
$app->group('/api', function(RouteCollectorProxy $group){
    
    // Ruta de test
    $group->get('/test', function ($request, $response) {
        $response->getBody()->write(json_encode(['message' => 'API funcionando correctamente', 'status' => 'OK']));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    //===========================[Rutas de Autenticación]=========================
    require __DIR__ . '/Routes/Auth.php';
    
    //===========================[Rutas de Programas]=========================
    require __DIR__ . '/Routes/Programas.php';
    
    //===========================[Rutas de Preguntas]=========================
    require __DIR__ . '/Routes/Preguntas.php';
    
    //===========================[Rutas de Cuestionario]=========================
    require __DIR__ . '/Routes/Cuestionario.php';
    
    //===========================[Rutas de Usuario]=========================
    require __DIR__ . '/Routes/Usuario.php';
    
    //===========================[Rutas de Respuestas]=========================
    require __DIR__ . '/Routes/Respuestas.php';
});


