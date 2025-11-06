<?php
use Slim\Routing\RouteCollectorProxy;

// Ruta raíz de prueba
$app->get('/', function ($request, $response, $args) {
    $data = [
        'message' => 'API de Egresados CURN funcionando correctamente ✅',
        'version' => '1.0.0',
        'status' => 'OK',
        'endpoints' => [
            'POST /api/auth/login' => 'Autenticación de usuarios',
            'POST /api/auth/refresh' => 'Refrescar token JWT',
            'GET /api/preguntas' => 'Obtener lista de preguntas',
            'POST /api/cuestionario/responder' => 'Guardar respuestas del cuestionario',
            'GET /api/programas' => 'Obtener lista de programas',
            'GET /api/usuario/perfil' => 'Obtener perfil de usuario',
        ],
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
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

// Ruta de debug para ver todas las rutas registradas
$app->get('/debug-routes', function ($request, $response) use ($app) {
    $routes = [];
    foreach ($app->getRouteCollector()->getRoutes() as $route) {
        $routes[] = [
            'name' => $route->getName() ?? 'unnamed',
            'methods' => $route->getMethods(),
            'pattern' => $route->getPattern()
        ];
    }
    $response->getBody()->write(json_encode([
        'total_routes' => count($routes),
        'routes' => $routes
    ], JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});
