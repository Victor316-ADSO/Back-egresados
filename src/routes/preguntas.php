<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {

    $app->get('/preguntas', function (Request $request, Response $response) {
        $db = getDatabase();
        $stmt = $db->query("SELECT * FROM tecni_preguntas");
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $payload = ['success' => true, 'preguntas' => $preguntas];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    });

};
