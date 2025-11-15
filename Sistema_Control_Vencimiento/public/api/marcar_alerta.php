<?php
// public/api/marcar_alerta.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';

    // Requiere sesión
    require_login_api();

    // REQUERIR login y rol ADMIN (1 o 2)
    require_rol(1, true); // si no es admin devuelve 403 JSON y sale

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido. Use POST.']);
        exit;
    }

    // Acepta id_alerta (snake) o idAlerta (camel)
    $id_alerta = isset($_POST['id_alerta']) ? (int)$_POST['id_alerta'] : (isset($_POST['idAlerta']) ? (int)$_POST['idAlerta'] : 0);
    $nuevo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

    if ($id_alerta <= 0 || $nuevo_estado === '') {
        echo json_encode(['ok' => false, 'error' => 'Faltan parámetros: idAlerta y estado.']);
        exit;
    }

    // Validar estado aceptado
    $estados_validos = ['nueva','revisada','descartada'];
    if (!in_array($nuevo_estado, $estados_validos, true)) {
        echo json_encode(['ok' => false, 'error' => 'Estado no válido. Valores permitidos: ' . implode(', ', $estados_validos)]);
        exit;
    }

    // Actualizar sólo el estado
    $sql = "UPDATE Alerta
            SET estado = :estado
            WHERE idAlerta = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':estado' => $nuevo_estado,
        ':id' => $id_alerta
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok' => false, 'error' => 'Alerta no encontrada o el estado ya era el mismo.']);
        exit;
    }

    // Devolver la fila actualizada (sin columnas de revisión)
    $sel = $pdo->prepare("
        SELECT idAlerta, idLote, diasUmbral, diasFaltantes, mensaje, estado, fechaGenerada, notificada
        FROM Alerta
        WHERE idAlerta = :id LIMIT 1
    ");
    $sel->execute([':id' => $id_alerta]);
    $fila = $sel->fetch(PDO::FETCH_ASSOC);

    if (!$fila) {
        echo json_encode(['ok' => false, 'error' => 'Alerta actualizada pero no pudo recuperarse.']);
        exit;
    }

    $alerta = [
        'id_alerta'     => (int)$fila['idAlerta'],
        'id_lote'       => (int)$fila['idLote'],
        'dias_umbral'   => isset($fila['diasUmbral']) ? (int)$fila['diasUmbral'] : null,
        'dias_faltantes'=> isset($fila['diasFaltantes']) ? (int)$fila['diasFaltantes'] : null,
        'mensaje'       => $fila['mensaje'] ?? '',
        'estado'        => $fila['estado'] ?? '',
        'notificada'    => isset($fila['notificada']) ? (int)$fila['notificada'] : 0,
        'fecha_generada'=> $fila['fechaGenerada'] ?? null
    ];

    echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado.', 'alerta' => $alerta], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error DB', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error inesperado', 'detalle' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
