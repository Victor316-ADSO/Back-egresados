<?php
use Slim\Routing\RouteCollectorProxy;

$group->group('/cuestionario', function(RouteCollectorProxy $subgroup){

    // Responder cuestionario
    $subgroup->post('/responder', 'App\Controllers\CuestionarioController:responder');
    
});
