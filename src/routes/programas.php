<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $app->get('/programas', function (Request $request, Response $response) {
        try {
            $db = getDatabase();
            
            // Lista de posibles consultas para diferentes esquemas
            $queries = [
                "SELECT EvalDCod_Prog AS codigo, EvalDNomb_Prog AS nombre FROM programa",
                "SELECT codi_prog AS codigo, nomb_prog AS nombre FROM programa", 
                "SELECT codigo, nombre FROM programa",
                "SELECT * FROM programa LIMIT 10"
            ];

            $programas = [];
            $lastError = null;
            
            foreach ($queries as $index => $sql) {
                try {
                    $stmt = $db->query($sql);
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($result)) {
                        $programas = $result;
                        break;
                    }
                } catch (Throwable $e) {
                    $lastError = "Query " . ($index + 1) . ": " . $e->getMessage();
                    continue;
                }
            }

            if (empty($programas)) {
                // Si no hay datos, devolver array vacío pero exitoso
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'programas' => [],
                    'message' => 'No se encontraron programas en la base de datos',
                    'debug' => $lastError
                ]));
            } else {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'programas' => $programas,
                    'message' => 'Programas obtenidos correctamente'
                ]));
            }
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Throwable $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
};
