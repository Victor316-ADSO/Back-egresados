<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    // Ruta para guardar las respuestas del usuario
    $app->post('/api/guardar-respuestas', function (Request $request, Response $response) {
        $db = getDatabase();
        
        try {
            $db->beginTransaction();
            
            $data = (array) ($request->getParsedBody() ?? []);
            $iden_pers = $data['iden_pers'] ?? null;
            $respuestas = $data['respuestas'] ?? [];
            $fecha_encuesta = date('Y-m-d H:i:s');

            if (empty($iden_pers) || empty($respuestas)) {
                throw new Exception('Datos incompletos para guardar las respuestas');
            }

            // 1. Verificar si ya existe una encuesta realizada previa por este usuario
            $stmtLast = $db->prepare('SELECT id FROM tecni_encuesta_realizada WHERE id_persona = ? ORDER BY fecha DESC LIMIT 1');
            $stmtLast->execute([$iden_pers]);
            $last = $stmtLast->fetch(PDO::FETCH_ASSOC);

            if ($last && isset($last['id'])) {
                // Usar la última encuesta: actualizamos la fecha y reemplazamos sus respuestas
                $idEncuestaRealizada = $last['id'];
                $stmtUpdEnc = $db->prepare('UPDATE tecni_encuesta_realizada SET fecha = ? WHERE id = ?');
                $stmtUpdEnc->execute([$fecha_encuesta, $idEncuestaRealizada]);

                // Borrar respuestas previas para esa encuesta (las reemplazaremos)
                $stmtDel = $db->prepare('DELETE FROM tecni_respuestas_usuario WHERE id_encuesta_realizada = ?');
                $stmtDel->execute([$idEncuestaRealizada]);
            } else {
                // No existe encuesta previa: insertar una nueva
                $stmtEncuesta = $db->prepare(
                    "INSERT INTO tecni_encuesta_realizada 
                    (id_persona, fecha) 
                    VALUES (?, ?)"
                );
                $stmtEncuesta->execute([$iden_pers, $fecha_encuesta]);
                $idEncuestaRealizada = $db->lastInsertId();
            }

            // 2. Insertar cada respuesta en tecni_respuestas_usuario (nuevo set)
            $stmtRespuestas = $db->prepare(
                "INSERT INTO tecni_respuestas_usuario 
                (id_encuesta_realizada, id_pregunta, valor_respuesta) 
                VALUES (?, ?, ?)"
            );

            foreach ($respuestas as $r) {
                $respuesta = $r['respuesta'];
                // Si la respuesta es un array, la convertimos a JSON
                if (is_array($respuesta)) {
                    $respuesta = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
                }
                $stmtRespuestas->execute([
                    $idEncuestaRealizada,
                    $r['id_pregunta'],
                    $respuesta
                ]);
            }

            $db->commit();
            
            $payload = [
                'success' => true, 
                'message' => 'Respuestas guardadas correctamente',
                'fecha_encuesta' => $fecha_encuesta
            ];
            
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $payload = [
                'success' => false,
                'message' => 'Error al guardar las respuestas',
                'error' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    });
};
