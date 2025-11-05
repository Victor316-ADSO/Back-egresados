<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;

return function (App $app) {

    $app->post('/auth/reset-token', function (Request $request, Response $response) {
        $data = (array) ($request->getParsedBody() ?? []);
        $programa = $data['programa'] ?? null;
        $identificacion = $data['identificacion'] ?? null;

        // Validar que se envíen los datos requeridos
        if (!$programa || !$identificacion) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Programa e identificación son requeridos'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = getDatabase();
        $stmt = $db->prepare("SELECT * FROM egresados WHERE codi_prog = ? AND iden_pers = ?");
        $stmt->execute([$programa, $identificacion]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generar nuevo token con tiempo de expiración extendido
            $payload = [
                'iss' => 'http://localhost',
                'aud' => 'http://localhost',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 2), // 2 horas
                'data' => [
                    'iden_pers' => $user['iden_pers'],
                    'codi_prog' => $user['codi_prog']
                ]
            ];

            $jwt = null;
            if (!empty($_ENV['JWT_SECRET'])) {
                $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
            }

            $payloadOut = [
                'success' => true,
                'message' => 'Token regenerado exitosamente',
                'user' => $user
            ];
            if ($jwt) {
                $payloadOut['token'] = $jwt;
            }

            $response->getBody()->write(json_encode($payloadOut));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    });

};
