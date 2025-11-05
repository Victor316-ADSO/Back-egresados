<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    // Obtener las opciones de respuestas para el cuestionario
    $app->get('/respuestas', function (Request $request, Response $response) {
        try {
            $db = getDatabase();
            
            // Obtener todas las preguntas con sus opciones de respuesta
            $sql = "SELECT 
                        p.id AS id_pregunta,
                        p.pregunta,
                        p.tipo_respuesta AS tipo_pregunta,
                        1 AS obligatoria,
                        p.id AS orden,
                        COALESCE(p.seccion, 'General') AS seccion,
                        COALESCE(p.estado, 1) AS estado,
                        p.id_pregunta_padre,
                        p.valor_activacion,
                        r.id_respuesta,
                        r.respuesta AS opcion_respuesta,
                        r.respuesta AS valor_respuesta,
                        r.id_respuesta AS orden_respuesta
                    FROM tecni_preguntas p
                    LEFT JOIN tecni_respuestas r ON p.id = r.id_pregunta
                    ORDER BY p.seccion ASC, p.id ASC, r.id_respuesta ASC";
            
            $stmt = $db->query($sql);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estructurar los datos para agrupar las opciones por pregunta
            $preguntas = [];
            foreach ($resultados as $fila) {
                $idPregunta = $fila['id_pregunta'];
                
                if (!isset($preguntas[$idPregunta])) {
                    $preguntas[$idPregunta] = [
                        'id_pregunta' => $fila['id_pregunta'],
                        'pregunta' => $fila['pregunta'],
                        'tipo_pregunta' => $fila['tipo_pregunta'],
                        'obligatoria' => (bool)$fila['obligatoria'],
                        'orden' => (int)$fila['orden'],
                        'seccion' => $fila['seccion'],
                        'estado' => (int)$fila['estado'],
                        'id_pregunta_padre' => $fila['id_pregunta_padre'] !== null ? (int)$fila['id_pregunta_padre'] : null,
                        'valor_activacion' => $fila['valor_activacion'],
                        'opciones' => []
                    ];
                }
                
                // Agregar opción de respuesta si existe
                if ($fila['id_respuesta'] !== null) {
                    $preguntas[$idPregunta]['opciones'][] = [
                        'id_respuesta' => $fila['id_respuesta'],
                        'opcion' => $fila['opcion_respuesta'],
                        'valor' => $fila['valor_respuesta'],
                        'orden' => (int)$fila['orden_respuesta']
                    ];
                }
            }
            
            // Convertir el array asociativo a numérico
            $preguntas = array_values($preguntas);
            
            $payload = [
                'success' => true,
                'data' => $preguntas
            ];
            
            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (Throwable $e) {
            $payload = [
                'success' => false,
                'message' => 'Error al obtener las opciones de respuesta',
                'error' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    });
};
