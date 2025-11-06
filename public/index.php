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

// Base path (ajústalo según la carpeta donde está tu public)
$app->setBasePath('/back_egresados/public');

// Middleware básico
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// 🔥 CORS Middleware (este reemplaza el del .htaccess)
$app->add(function (Request $request, RequestHandler $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// 🔥 Manejar preflight OPTIONS directamente
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Error handling global (mantén esto al final de los middlewares)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// Ruta básica
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

// Rutas de prueba y tus módulos
$app->get('/test', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Slim funciona correctamente']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Registrar tus rutas
$authRoutes = require __DIR__ . '/../src/routes/auth.php';
$authRoutes($app);  

$preguntasRoutes = require __DIR__ . '/../src/routes/preguntas.php';
$preguntasRoutes($app);  

$respuestasRoutes = require __DIR__ . '/../src/routes/respuestas.php';
$respuestasRoutes($app);

$guardarRespuestaRoutes = require __DIR__ . '/../src/routes/guardar_respuesta.php';
$guardarRespuestaRoutes($app);

$cuestionarioRoutes = require __DIR__ . '/../src/routes/cuestionario.php';
$cuestionarioRoutes($app);

$obtenerRespuestasRoutes = require __DIR__ . '/../src/routes/obtener_respuestas.php';
$obtenerRespuestasRoutes($app);

$testRoutes = require __DIR__ . '/../src/routes/test.php';
$testRoutes($app);

$programasRoutes = require __DIR__ . '/../src/routes/programas.php';
$programasRoutes($app);

$usuarioRoutes = require __DIR__ . '/../src/routes/usuario.php';
$usuarioRoutes($app);

$resetTokenRoutes = require __DIR__ . '/../src/routes/reset_token.php';
$resetTokenRoutes($app);

// Rutas debug (opcionales)
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

$app->get('/debug-routes', function ($request, $response) use ($app) {
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
