<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

return function (App $app) {
    // Endpoint para obtener datos del usuario autenticado
    $app->get('/usuario/perfil', function (Request $request, Response $response) {
        // Verificar autenticación
        $jwt = $request->getHeaderLine('Authorization');
        if (empty($jwt)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token de autorización requerido'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        $jwt = str_replace('Bearer ', '', $jwt);
        
        try {
            // Verificar y decodificar el token JWT
            $key = new Key($_ENV['JWT_SECRET'], 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data;
            
            // Obtener datos del usuario con JOIN a la tabla personas
            $db = getDatabase();
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
                    p.esta_pers,
                    p.foto_pers,
                    p.codi_nume,
                    p.proc_regi,
                    p.proc_ciud,
                    p.codi_eps,
                    p.tipo_sangre,
                    p.estrato,
                    e.fgra_egre as fecha_grad,
                    e.prom_egre,
                    e.ping_egre,
                    e.seme_curs,
                    e.item_opci,
                    e.desc_opci,
                    e.cali_opci,
                    e.plan,
                    e.moda_grad,
                    e.tarj_egre,
                    e.anio_tarj,
                    e.trabaja,
                    e.no_empleado,
                    e.detalles
                FROM egresados e
                INNER JOIN personas p ON e.iden_pers = p.iden_pers
                WHERE e.iden_pers = ? AND e.codi_prog = ?
            ");
            
            $stmt->execute([$userData->iden_pers, $userData->codi_prog]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Formatear la respuesta con los datos consolidados
            $datosUsuario = [
                // Identificación
                'iden_pers' => $usuario['iden_pers'],
                'codi_iden' => $usuario['codi_iden'],
                'codi_prog' => $usuario['codi_prog'],
                
                // Datos personales
                'nombre_completo' => trim($usuario['nomb_pers'] . ' ' . $usuario['ape1_pers'] . ' ' . $usuario['ape2_pers']),
                'nomb_pers' => $usuario['nomb_pers'],
                'ape1_pers' => $usuario['ape1_pers'],
                'ape2_pers' => $usuario['ape2_pers'],
                'sexo_pers' => $usuario['sexo_pers'],
                'fnac_pers' => $usuario['fnac_pers'],
                'fech_expe' => $usuario['fech_expe'],
                
                // Ubicación
                'lnac_pais' => $usuario['lnac_pais'],
                'lnac_regi' => $usuario['lnac_regi'],
                'lnac_ciud' => $usuario['lnac_ciud'],
                'lexp_pais' => $usuario['lexp_pais'],
                'lexp_regi' => $usuario['lexp_regi'],
                'lexp_ciud' => $usuario['lexp_ciud'],
                'proc_regi' => $usuario['proc_regi'],
                'proc_ciud' => $usuario['proc_ciud'],
                
                // Información adicional
                'esta_pers' => $usuario['esta_pers'],
                'foto_pers' => $usuario['foto_pers'],
                'codi_nume' => $usuario['codi_nume'],
                'codi_eps' => $usuario['codi_eps'],
                'tipo_sangre' => $usuario['tipo_sangre'],
                'estrato' => $usuario['estrato'],
                
                // Datos académicos
                'fecha_grad' => $usuario['fecha_grad'],
                'promocion' => $usuario['prom_egre'],
                'periodo_ingreso' => $usuario['ping_egre'],
                'semestres_cursados' => $usuario['seme_curs'],
                'opcion_grado' => $usuario['item_opci'],
                'descripcion_opcion' => $usuario['desc_opci'],
                'calificacion_opcion' => $usuario['cali_opci'],
                'plan' => $usuario['plan'],
                'modalidad_grado' => $usuario['moda_grad'],
                'tarjeta_profesional' => $usuario['tarj_egre'],
                'anio_tarjeta' => $usuario['anio_tarj'],
                
                // Información laboral
                'trabaja' => $usuario['trabaja'],
                'no_empleado' => $usuario['no_empleado'],
                'detalles' => $usuario['detalles']
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $datosUsuario
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error al obtener los datos del usuario: ' . $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    });

    // Obtener información de contacto desde tecni_datos_contacto
    $app->get('/usuario/contacto', function (Request $request, Response $response) {
        $jwt = $request->getHeaderLine('Authorization');
        if (empty($jwt)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Token de autorización requerido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $jwt = str_replace('Bearer ', '', $jwt);
        try {
            $key = new Key($_ENV['JWT_SECRET'], 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data;
            $db = getDatabase();
            $stmt = $db->prepare('SELECT iden_pers, celular, telefono_alternativo, email_institucional, email_alternativo, direccion_residencia, fecha_actualizacion FROM tecni_datos_contacto WHERE iden_pers = ? LIMIT 1');
            $stmt->execute([$userData->iden_pers]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $response->getBody()->write(json_encode(['success' => true, 'data' => $row]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Guardar información de contacto del egresado en tecni_datos_contacto
    $app->post('/usuario/contacto', function (Request $request, Response $response) {
        $jwt = $request->getHeaderLine('Authorization');
        if (empty($jwt)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Token de autorización requerido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $jwt = str_replace('Bearer ', '', $jwt);
        try {
            $key = new Key($_ENV['JWT_SECRET'], 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data;

            $data = (array) ($request->getParsedBody() ?? []);
            // Aceptar tanto nombres del front como de la tabla
            $celular = $data['celular'] ?? $data['telCelular'] ?? null;
            $telAlt = $data['telefono_alternativo'] ?? $data['telAlternativo'] ?? null;
            $emailInst = $data['email_institucional'] ?? $data['emailInstitucional'] ?? null;
            $emailAlt = $data['email_alternativo'] ?? $data['emailAlternativo'] ?? null;
            $dir = $data['direccion_residencia'] ?? $data['direccionResidencia'] ?? null;

            $db = getDatabase();
            // Upsert por iden_pers
            $stmtSel = $db->prepare('SELECT iden_pers FROM tecni_datos_contacto WHERE iden_pers = ?');
            $stmtSel->execute([$userData->iden_pers]);
            if ($stmtSel->fetch()) {
                $stmtUpd = $db->prepare('UPDATE tecni_datos_contacto SET celular = ?, telefono_alternativo = ?, email_institucional = ?, email_alternativo = ?, direccion_residencia = ?, fecha_actualizacion = NOW() WHERE iden_pers = ?');
                $stmtUpd->execute([$celular, $telAlt, $emailInst, $emailAlt, $dir, $userData->iden_pers]);
            } else {
                $stmtIns = $db->prepare('INSERT INTO tecni_datos_contacto (iden_pers, celular, telefono_alternativo, email_institucional, email_alternativo, direccion_residencia, fecha_actualizacion) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtIns->execute([$userData->iden_pers, $celular, $telAlt, $emailInst, $emailAlt, $dir]);
            }

            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Actualizar datos personales básicos del usuario en la tabla personas
    $app->post('/usuario/actualizar', function (Request $request, Response $response) {
        $jwt = $request->getHeaderLine('Authorization');
        if (empty($jwt)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Token de autorización requerido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $jwt = str_replace('Bearer ', '', $jwt);
        try {
            $key = new Key($_ENV['JWT_SECRET'], 'HS256');
            $decoded = JWT::decode($jwt, $key);
            $userData = $decoded->data; // contiene iden_pers y codi_prog

            $data = (array) ($request->getParsedBody() ?? []);
            // Campos permitidos a actualizar en personas
            $allowed = ['nomb_pers','ape1_pers','ape2_pers','fnac_pers','codi_iden','sexo_pers','esta_pers'];
            $setParts = [];
            $params = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $setParts[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($setParts)) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No hay campos para actualizar']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = getDatabase();
            $sql = 'UPDATE personas SET ' . implode(', ', $setParts) . ' WHERE iden_pers = ?';
            $params[] = $userData->iden_pers;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
};