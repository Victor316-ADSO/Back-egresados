<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use Exception;

/**
 * Controlador de cuestionario
 */
class CuestionarioController extends BaseController
{
    public function __construct(ContainerInterface $c)
    {
        parent::__construct($c);
    }

    /**
     * Guarda una respuesta del cuestionario
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function responder(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $this->getJsonInput($request);
            $user = $request->getAttribute('user');

            if (!$data) {
                return $this->errorResponse($response, 'Datos JSON inválidos', 400);
            }

            $id_pregunta = $data['id_pregunta'] ?? null;
            $respuesta = $data['respuesta'] ?? null;

            if (!$id_pregunta || !$respuesta || !$user) {
                return $this->errorResponse($response, 'Faltan datos requeridos', 400);
            }

            $db = $this->getDatabase();
            $stmt = $db->prepare('INSERT INTO tecni_respuestas (id_pregunta, iden_pers, codi_prog, respuesta) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id_pregunta, $user->iden_pers, $user->codi_prog, $respuesta]);

            return $this->successResponse($response, 'Respuesta guardada correctamente', []);
            
        } catch (Exception $e) {
            error_log("Error en responder: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al guardar respuesta', 500);
        }
    }
}
