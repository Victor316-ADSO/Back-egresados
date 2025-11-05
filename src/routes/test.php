<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $authMiddleware = require __DIR__ . '/../../middleware/AuthMiddleware.php';

    $app->get('/test-token', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Token válido ✅',
            'user' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($authMiddleware);
};