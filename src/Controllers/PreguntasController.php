<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use Exception;

/**
 * Controlador de preguntas
 */
class PreguntasController extends BaseController
{
    public function __construct(ContainerInterface $c)
    {
        parent::__construct($c);
    }

    /**
     * Obtiene todas las preguntas
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getPreguntas(Request $request, Response $response, array $args): Response
    {
        try {
            $db = $this->getDatabase();
            $stmt = $db->query("SELECT * FROM tecni_preguntas");
            $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->successResponse($response, 'Preguntas obtenidas correctamente', [
                'preguntas' => $preguntas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getPreguntas: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al obtener preguntas', 500);
        }
    }
}
