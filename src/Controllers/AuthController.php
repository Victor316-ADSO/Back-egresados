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
            
            // Log para debugging
            error_log("Login attempt - Raw body: " . $request->getBody()->getContents());
            $request->getBody()->rewind(); // Rebobinar para que getJsonInput funcione
            $data = $this->getJsonInput($request);
            error_log("Login attempt - Parsed data: " . json_encode($data));
            
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

    /**
     * Obtiene el texto de autorización de tratamiento de datos
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getAutorizacion(Request $request, Response $response, array $args): Response
    {
        try {
            $apiUrl = 'https://axis.uninunez.edu.co/apiLDAP/api/authdb/get';
            $data = json_encode(['dbcod' => '21']);

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json'
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return $this->errorResponse($response, 'Error al conectar con el servicio de autorización: ' . $error, 500);
            }
            
            curl_close($ch);

            if ($httpCode !== 200) {
                return $this->errorResponse($response, 'Error al obtener autorización', $httpCode);
            }

            return $this->successResponse($response, 'Autorización obtenida correctamente', [
                'contenido' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getAutorizacion: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al obtener autorización: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Registra la aceptación de tratamiento de datos
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function setAutorizacion(Request $request, Response $response, array $args): Response
    {
        try {
            // Obtener datos del body JSON
            $data = $this->getJsonInput($request);
            
            if (!$data) {
                return $this->errorResponse($response, 'Datos JSON inválidos', 400);
            }
            
            $dni = $data['dni'] ?? $data['userdni'] ?? null;
            
            if (!$dni) {
                return $this->errorResponse($response, 'El DNI es requerido', 400);
            }

            // Obtener IP del cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            // Log para debugging
            error_log("Registrando autorización - DNI: $dni, IP: $ip");

            $apiUrl = 'https://axis.uninunez.edu.co/apiLDAP/api/authdb/set';
            $payload = json_encode([
                'dbcod' => '21',
                'app' => 'EGRESADOS-UPDATE',
                'userdni' => $dni,
                'ip' => $ip
            ]);

            error_log("Payload enviado al API: $payload");

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Para desarrollo

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log("Respuesta del API - HTTP Code: $httpCode, Response: $result");
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                error_log("Error CURL: $error");
                return $this->errorResponse($response, 'Error al conectar con el servicio de autorización: ' . $error, 500);
            }
            
            curl_close($ch);

            if ($httpCode !== 200) {
                return $this->errorResponse($response, 'Error al registrar autorización. Código HTTP: ' . $httpCode, $httpCode);
            }

            $resultData = json_decode($result, true);

            return $this->successResponse($response, 'Autorización registrada correctamente', [
                'resultado' => $resultData,
                'dni' => $dni,
                'ip' => $ip
            ]);
            
        } catch (Exception $e) {
            error_log("Error en setAutorizacion: " . $e->getMessage());
            return $this->errorResponse($response, 'Error al registrar autorización: ' . $e->getMessage(), 500);
        }
    }
}
