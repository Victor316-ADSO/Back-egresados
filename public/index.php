<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;



// Carga .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Función para obtener la conexión a la base de datos
function getDatabase() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'curn';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Crear la app
$app = AppFactory::create();

// Set base path for /back_egresados/public
$app->setBasePath('/back_egresados/public');

// Middleware para parseo de body y routing
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Global error middleware to return JSON on errors (including 404)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// CORS manejado por Apache .htaccess - solo pasar la petición
$app->add(function (Request $request, RequestHandler $handler) {
    return $handler->handle($request);
});

// CORS manejado completamente por Apache .htaccess

// Ruta básica para la raíz
$app->get('/', function ($request, $response, $args) {
    $data = [
        'message' => 'API de Egresados CURN funcionando correctamente',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /auth/login' => 'Autenticación de usuarios',
            'POST /auth/reset-token' => 'Regenerar token de usuario',
            'GET /preguntas' => 'Obtener lista de preguntas del cuestionario',
            'POST /respuestas' => 'Guardar respuestas del cuestionario'
        ],
        'status' => 'OK'
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Ruta de prueba simple
$app->get('/test', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Slim funciona correctamente']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Registrar rutas
$authRoutes = require __DIR__ . '/../src/routes/auth.php';
$authRoutes($app);  

$preguntasRoutes = require __DIR__ . '/../src/routes/preguntas.php';
$preguntasRoutes($app);  

$respuestasRoutes = require __DIR__ . '/../src/routes/respuestas.php';
$respuestasRoutes($app);

// Ruta para guardar respuestas
$guardarRespuestaRoutes = require __DIR__ . '/../src/routes/guardar_respuesta.php';
$guardarRespuestaRoutes($app);

// Cuestionario routes (protected)
$cuestionarioRoutes = require __DIR__ . '/../src/routes/cuestionario.php';
$cuestionarioRoutes($app);

// Obtener respuestas del usuario (última encuesta)
$obtenerRespuestasRoutes = require __DIR__ . '/../src/routes/obtener_respuestas.php';
$obtenerRespuestasRoutes($app);

// Test token route
$testRoutes = require __DIR__ . '/../src/routes/test.php';
$testRoutes($app);

// Programas
$programasRoutes = require __DIR__ . '/../src/routes/programas.php';
$programasRoutes($app);

// Rutas de usuario
$usuarioRoutes = require __DIR__ . '/../src/routes/usuario.php';
$usuarioRoutes($app);

// Rutas de reset token
$resetTokenRoutes = require __DIR__ . '/../src/routes/reset_token.php';
$resetTokenRoutes($app);

// Debug route to inspect Authorization header quickly (remove in prod)
$app->get('/debug-auth', function ($request, $response) {
    $headers = $request->getHeaders();
    $auth = $request->getHeaderLine('Authorization');
    $payload = [
        'authorization' => $auth,
        'headers' => $headers,
        'has_env_jwt_secret' => !empty($_ENV['JWT_SECRET'])
    ];
    $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Debug route to see available routes
$app->get('/debug-routes', function ($request, $response) {
    $routes = [];
    foreach ($app->getRouteCollector()->getRoutes() as $route) {
        $routes[] = [
            'methods' => $route->getMethods(),
            'pattern' => $route->getPattern()
        ];
    }
    $payload = [
        'base_path' => $app->getBasePath(),
        'routes' => $routes,
        'request_uri' => $request->getUri()->getPath()
    ];
    $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
