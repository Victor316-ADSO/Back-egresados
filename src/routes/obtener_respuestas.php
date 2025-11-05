<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\Key;

return function (App $app) {
    // Obtener respuestas del usuario para la última encuesta realizada
    $app->get('/api/mis-respuestas', function (Request $request, Response $response) {
        $jwt = $request->getHeaderLine('Authorization');
        if (empty($jwt)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Token de autorización requerido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $jwt = str_replace('Bearer ', '', $jwt);

        try {
            $key = new Key($_ENV['JWT_SECRET'], 'HS256');
            $decoded = \Firebase\JWT\JWT::decode($jwt, $key);
            $userData = $decoded->data;

            $db = getDatabase();

            // Obtener la última encuesta realizada por el usuario
            $stmtEnc = $db->prepare('SELECT id FROM tecni_encuesta_realizada WHERE id_persona = ? ORDER BY fecha DESC LIMIT 1');
            $stmtEnc->execute([$userData->iden_pers]);
            $enc = $stmtEnc->fetch(PDO::FETCH_ASSOC);
            if (!$enc) {
                $payload = ['success' => true, 'data' => []];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json');
            }
            $idEncuesta = $enc['id'];

            // Obtener respuestas asociadas a esa encuesta
            $stmt = $db->prepare('SELECT id_pregunta, valor_respuesta FROM tecni_respuestas_usuario WHERE id_encuesta_realizada = ?');
            $stmt->execute([$idEncuesta]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear: devolver un mapa id_pregunta => valor
            $result = [];
            foreach ($rows as $r) {
                $val = $r['valor_respuesta'];
                // intentar decodificar JSON si fue almacenado como tal
                $decodedVal = json_decode($val, true);
                $result[(int)$r['id_pregunta']] = $decodedVal !== null ? $decodedVal : $val;
            }

            $payload = ['success' => true, 'data' => $result];
            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (Throwable $e) {
            $payload = ['success' => false, 'message' => $e->getMessage()];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
};
