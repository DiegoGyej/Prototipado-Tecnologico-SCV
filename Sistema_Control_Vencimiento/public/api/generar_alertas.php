<?php
// public/api/generar_alertas.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../src/conexion.php';
require_once __DIR__ . '/../../src/correo.php';

try {
    $mode = isset($_GET['mode']) && $_GET['mode'] === 'exact' ? 'exact' : 'bucket';

    $umbrales = [];
    $stmtU = $pdo->query("SELECT diasAntes FROM Umbral_Alerta WHERE activo = 1 ORDER BY diasAntes DESC");
    $rowsU = $stmtU->fetchAll(PDO::FETCH_COLUMN);
    if ($rowsU && count($rowsU) > 0) {
        $umbrales = array_map('intval', $rowsU);
    } else {
        $umbrales = [30,15,7];
    }

    $hoy = new DateTime();
    $created = 0;
    $skipped = 0;
    $emailed = 0;
    $details = [];

    $stmtRecipients = $pdo->prepare("
        SELECT u.correo, u.nombre
        FROM Usuario u
        JOIN Rol r ON u.idRol = r.idRol
        WHERE u.activo = 1 AND r.nombre IN ('Administrador','Gerente')
    ");
    $stmtRecipients->execute();
    $recipients = $stmtRecipients->fetchAll(PDO::FETCH_ASSOC);

    // Seleccionar lotes vigentes y con fecha >= hoy
    $sql = "SELECT l.idLote, l.codigoLote, l.fechaVencimiento, l.cantidad, p.nombre AS producto
            FROM Lote l
            JOIN Producto p ON l.idProducto = p.idProducto
            WHERE (l.estado = 'activo' OR l.estado IS NULL) 
              AND l.fechaVencimiento >= CURDATE()
            ORDER BY l.fechaVencimiento ASC";
    $stmt = $pdo->query($sql);
    $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lotes as $l) {
        $fv = new DateTime($l['fechaVencimiento']);
        $diasFaltantes = (int)$hoy->diff($fv)->format('%r%a');
        if ($diasFaltantes < 0) continue;

        if ($mode === 'exact') {
            foreach ($umbrales as $u) {
                if ($diasFaltantes === (int)$u) {
                    // Evitamos duplicado
                    $chk = $pdo->prepare("SELECT 1 FROM Alerta WHERE idLote = :idl AND diasUmbral = :du AND estado != 'descartada' LIMIT 1");
                    $chk->execute([':idl' => $l['idLote'], ':du' => $u]);
                    if ($chk->fetch()) { $skipped++; continue; }

                    $msg = "Lote {$l['codigoLote']} ({$l['producto']}) vence en {$diasFaltantes} días (umbral {$u}).";
                    $ins = $pdo->prepare("INSERT INTO Alerta (idLote, diasUmbral, diasFaltantes, mensaje, estado, fechaGenerada, notificada) VALUES (:idl, :du, :df, :msg, 'nueva', NOW(), 0)");
                    $ins->execute([':idl' => $l['idLote'], ':du' => $u, ':df' => $diasFaltantes, ':msg' => $msg]);
                    $idAlerta = $pdo->lastInsertId();
                    $created++;

                    // Enviar correos (falta terminar de implementar)
                    $subject = "Alerta: lote {$l['codigoLote']} vence en {$diasFaltantes} días";
                    $body = "<p>{$msg}</p><p><strong>Cantidad:</strong> {$l['cantidad']}</p>";

                    $sentAny = false;
                    foreach ($recipients as $r) {
                        if (!empty($r['correo'])) {
                            if (enviarCorreo($r['correo'], $subject, $body)) {
                                $sentAny = true;
                            }
                        }
                    }

                    if ($sentAny) {
                        $pdo->prepare("UPDATE Alerta SET notificada = 1 WHERE idAlerta = :ida")->execute([':ida' => $idAlerta]);
                        $emailed++;
                    }

                    $details[] = ['idAlerta'=>$idAlerta,'idLote'=>$l['idLote'],'codigoLote'=>$l['codigoLote'],'umbral'=>$u,'dias'=>$diasFaltantes,'notified'=>$sentAny?1:0];
                }
            }
        } else { // bucket
            $umbralAplicable = null;
            if ($diasFaltantes <= 7) $umbralAplicable = 7;
            elseif ($diasFaltantes <= 15) $umbralAplicable = 15;
            elseif ($diasFaltantes <= 30) $umbralAplicable = 30;

            if ($umbralAplicable === null) continue;
            if (!in_array($umbralAplicable, $umbrales, true)) continue;

            $chk = $pdo->prepare("SELECT 1 FROM Alerta WHERE idLote = :idl AND diasUmbral = :du AND estado != 'descartada' LIMIT 1");
            $chk->execute([':idl' => $l['idLote'], ':du' => $umbralAplicable]);
            if ($chk->fetch()) { $skipped++; continue; }

            $msg = "Lote {$l['codigoLote']} ({$l['producto']}) vence en {$diasFaltantes} días (umbral {$umbralAplicable}).";
            $ins = $pdo->prepare("INSERT INTO Alerta (idLote, diasUmbral, diasFaltantes, mensaje, estado, fechaGenerada, notificada) VALUES (:idl, :du, :df, :msg, 'nueva', NOW(), 0)");
            $ins->execute([':idl' => $l['idLote'], ':du' => $umbralAplicable, ':df' => $diasFaltantes, ':msg' => $msg]);
            $idAlerta = $pdo->lastInsertId();
            $created++;

            // enviar correos (falta terminar de implementar)
            $subject = "Alerta: lote {$l['codigoLote']} vence en {$diasFaltantes} días";
            $body = "<p>{$msg}</p><p><strong>Cantidad:</strong> {$l['cantidad']}</p>";

            $sentAny = false;
            foreach ($recipients as $r) {
                if (!empty($r['correo'])) {
                    if (enviarCorreo($r['correo'], $subject, $body)) {
                        $sentAny = true;
                    }
                }
            }
            if ($sentAny) {
                $pdo->prepare("UPDATE Alerta SET notificada = 1 WHERE idAlerta = :ida")->execute([':ida' => $idAlerta]);
                $emailed++;
            }

            $details[] = ['idAlerta'=>$idAlerta,'idLote'=>$l['idLote'],'codigoLote'=>$l['codigoLote'],'umbral'=>$umbralAplicable,'dias'=>$diasFaltantes,'notified'=>$sentAny?1:0];
        }
    }

    echo json_encode([
        'ok'=>true,
        'mode'=>$mode,
        'umbrales'=>$umbrales,
        'created'=>$created,
        'skipped'=>$skipped,
        'emailed'=>$emailed,
        'details'=>$details
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error generando alertas','detalle'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
