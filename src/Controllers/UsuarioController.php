<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use Exception;

/**
 * Controlador de usuario
 */
class UsuarioController extends BaseController
{
    public function __construct(ContainerInterface $c)
    {
        parent::__construct($c);
    }

    /**
     * Obtiene el perfil del usuario autenticado
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getPerfil(Request $request, Response $response, array $args): Response
    {
        try {
            // Verificar autenticación
            $jwt = $request->getHeaderLine('Authorization');
            if (empty($jwt)) {
                return $this->errorResponse($response, 'Token de autorización requerido', 401);
            }
            
            $jwt = str_replace('Bearer ', '', $jwt);
            
            // Verificar y decodificar el token JWT
            $key = new Key($this->jwtSecret, 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data;
            
            // Obtener datos del usuario con JOIN a la tabla personas
            $db = $this->getDatabase();
            $stmt = $db->prepare("
                SELECT 
                    e.iden_pers, 
                    e.codi_prog,
                    p.codi_iden,
                    p.nomb_pers,
                    p.ape1_pers,
                    p.ape2_pers,
                    p.sexo_pers,
                    p.fnac_pers,
                    p.fech_expe,
                    p.lnac_pais,
                    p.lnac_regi,
                    p.lnac_ciud,
                    p.lexp_pais,
                    p.lexp_regi,
                    p.lexp_ciud,
                    p.dire_pers,
                    p.barr_pers,
                    p.telf_pers,
                    p.mail_pers,
                    p.timo_iden,
                    prog.nomb_prog
                FROM egresados e
                INNER JOIN personas p ON e.iden_pers = p.codi_iden
                LEFT JOIN programa prog ON e.codi_prog = prog.codi_prog
                WHERE e.iden_pers = ? AND e.codi_prog = ?
            ");
            $stmt->execute([$userData->iden_pers, $userData->codi_prog]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            return $this->successResponse($response, 'Perfil obtenido correctamente', [
                'usuario' => $usuario
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getPerfil: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al obtener perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualiza el perfil del usuario
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function updatePerfil(Request $request, Response $response, array $args): Response
    {
        try {
            // Verificar autenticación
            $jwt = $request->getHeaderLine('Authorization');
            if (empty($jwt)) {
                return $this->errorResponse($response, 'Token de autorización requerido', 401);
            }
            
            $jwt = str_replace('Bearer ', '', $jwt);
            
            // Verificar y decodificar el token JWT
            $key = new Key($this->jwtSecret, 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data;
            
            $data = $this->getJsonInput($request);
            
            if (!$data) {
                return $this->errorResponse($response, 'Datos JSON inválidos', 400);
            }

            $db = $this->getDatabase();
            
            // Actualizar datos de la persona
            $updateFields = [];
            $params = [];
            
            if (isset($data['mail_pers'])) {
                $updateFields[] = "mail_pers = ?";
                $params[] = $data['mail_pers'];
            }
            if (isset($data['telf_pers'])) {
                $updateFields[] = "telf_pers = ?";
                $params[] = $data['telf_pers'];
            }
            if (isset($data['dire_pers'])) {
                $updateFields[] = "dire_pers = ?";
                $params[] = $data['dire_pers'];
            }
            if (isset($data['barr_pers'])) {
                $updateFields[] = "barr_pers = ?";
                $params[] = $data['barr_pers'];
            }

            if (empty($updateFields)) {
                return $this->errorResponse($response, 'No hay campos para actualizar', 400);
            }

            $params[] = $userData->iden_pers;
            
            $sql = "UPDATE personas SET " . implode(", ", $updateFields) . " WHERE codi_iden = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $this->successResponse($response, 'Perfil actualizado correctamente', []);
            
        } catch (Exception $e) {
            error_log("Error en updatePerfil: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al actualizar perfil', 500);
        }
    }
}
