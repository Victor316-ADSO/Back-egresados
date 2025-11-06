<?php
use Slim\Routing\RouteCollectorProxy;

$group->group('/usuario', function(RouteCollectorProxy $subgroup){

    // Obtener perfil del usuario
    $subgroup->get('/perfil', 'App\Controllers\UsuarioController:getPerfil');

    // Actualizar perfil del usuario
    $subgroup->put('/perfil', 'App\Controllers\UsuarioController:updatePerfil');
    
});
