<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Firebase\JWT\JWT;
use PDO;
use Exception;

/**
 * Controlador de autenticación
 */
class AuthController extends BaseController
{
    public function __construct(ContainerInterface $c)
    {
        parent::__construct($c);
    }

    /**
     * Login de usuarios (egresados)
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function login(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $this->getJsonInput($request);
            
            if (!$data) {
                return $this->errorResponse($response, 'Datos JSON inválidos', 400);
            }

            $programa = $data['programa'] ?? null;
            $identificacion = $data['identificacion'] ?? null;

            if (!$programa || !$identificacion) {
                return $this->errorResponse($response, 'Programa e identificación son requeridos', 400);
            }

            $db = $this->getDatabase();
            $stmt = $db->prepare("SELECT * FROM egresados WHERE codi_prog = ? AND iden_pers = ?");
            $stmt->execute([$programa, $identificacion]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
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

                $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');

                return $this->successResponse($response, 'Login exitoso', [
                    'token' => $jwt,
                    'user' => $user
                ]);
            }

            return $this->errorResponse($response, 'Usuario no encontrado', 401);
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return $this->errorResponse($response, 'Error interno del servidor', 500);
        }
    }

    /**
     * Verifica si un token JWT es válido
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function verifyToken(Request $request, Response $response, array $args): Response
    {
        try {
            $token = $this->getBearerToken($request);
            
            if (!$token) {
                return $this->errorResponse($response, 'Token no proporcionado', 401);
            }
            
            $decoded = $this->verifyJwtToken($token);
            
            if (!$decoded) {
                return $this->errorResponse($response, 'Token inválido o expirado', 401);
            }
            
            return $this->successResponse($response, 'Token válido', [
                'user' => (array) $decoded
            ]);
            
        } catch (Exception $e) {
            error_log("Error en verifyToken: " . $e->getMessage());
            return $this->errorResponse($response, 'Token inválido o expirado', 401);
        }
    }

    /**
     * Refresca un token JWT válido
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function refreshToken(Request $request, Response $response, array $args): Response
    {
        try {
            $token = $this->getBearerToken($request);
            
            if (!$token) {
                return $this->errorResponse($response, 'Token no proporcionado', 401);
            }
            
            $decoded = $this->verifyJwtToken($token);
            
            if (!$decoded) {
                return $this->errorResponse($response, 'Token inválido', 401);
            }

            $userData = (array) $decoded;
            
            // Generar nuevo token
            $payload = [
                'iss' => 'http://localhost',
                'aud' => 'http://localhost',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 2), // 2 horas
                'data' => $userData['data']
            ];

            $newToken = JWT::encode($payload, $this->jwtSecret, 'HS256');
            
            return $this->successResponse($response, 'Token refrescado', [
                'token' => $newToken
            ]);
            
        } catch (Exception $e) {
            error_log("Error en refreshToken: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al refrescar token', 500);
        }
    }

    /**
     * Cierra la sesión del usuario
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function logout(Request $request, Response $response, array $args): Response
    {
        return $this->successResponse($response, 'Sesión cerrada correctamente', [
            'message' => 'Token eliminado del lado del cliente'
        ]);
    }
}
