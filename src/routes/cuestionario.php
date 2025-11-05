<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $authMiddleware = require __DIR__ . '/../../middleware/AuthMiddleware.php';

    $app->post('/cuestionario/responder', function (Request $request, Response $response) {
        $data = (array) ($request->getParsedBody() ?? []);
        $user = $request->getAttribute('user');

        $id_pregunta = $data['id_pregunta'] ?? null;
        $respuesta = $data['respuesta'] ?? null;

        if (!$id_pregunta || !$respuesta || !$user) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Faltan datos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = getDatabase();
        $stmt = $db->prepare('INSERT INTO tecni_respuestas (id_pregunta, iden_pers, codi_prog, respuesta) VALUES (?, ?, ?, ?)');
        $stmt->execute([$id_pregunta, $user->iden_pers, $user->codi_prog, $respuesta]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Respuesta guardada correctamente']));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($authMiddleware);
};
