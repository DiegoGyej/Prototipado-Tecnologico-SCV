<?php
// public/api/get_alertas.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';

    // Requiere login para acceder
    require_login_api();

    // Filtros opcionales (segun la cantidad)
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $limit  = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 200;
    if ($limit <= 0) $limit = 200;
    if ($limit > 1000) $limit = 1000;

    // Base de consulta: Alerta -> Lote -> Producto (unidos)
    $baseSql = "
        SELECT
            a.idAlerta AS idAlerta,
            a.idLote   AS idLote,
            a.diasUmbral,
            a.diasFaltantes,
            a.mensaje,
            a.estado,
            a.notificada,
            a.fechaGenerada,
            l.codigoLote,
            l.fechaVencimiento,
            p.idProducto,
            p.codigoProducto,
            p.nombre AS producto
        FROM Alerta a
        JOIN Lote l     ON a.idLote = l.idLote
        JOIN Producto p ON l.idProducto = p.idProducto
    ";

    if ($estado !== '') {
        $sql = $baseSql . " WHERE a.estado = :estado ORDER BY a.fechaGenerada DESC LIMIT :lim";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = $baseSql . " ORDER BY a.fechaGenerada DESC LIMIT :lim";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapear salida
    $alertas = [];
    foreach ($rows as $r) {
        $alertas[] = [
            'id_alerta'       => isset($r['idAlerta']) ? (int)$r['idAlerta'] : null,
            'id_lote'         => isset($r['idLote']) ? (int)$r['idLote'] : null,
            'id_producto'     => isset($r['idProducto']) ? (int)$r['idProducto'] : null,
            'codigo_producto' => $r['codigoProducto'] ?? null,
            'producto'        => $r['producto'] ?? null,
            'codigo_lote'     => $r['codigoLote'] ?? null,
            'fecha_vencimiento'=> $r['fechaVencimiento'] ?? null,
            'dias_faltantes'  => isset($r['diasFaltantes']) ? (int)$r['diasFaltantes'] : null,
            'dias_umbral'     => isset($r['diasUmbral']) ? (int)$r['diasUmbral'] : null,
            'mensaje'         => $r['mensaje'] ?? '',
            'estado'          => $r['estado'] ?? '',
            'notificada'      => isset($r['notificada']) ? (int)$r['notificada'] : 0,
            'fecha_generada'  => $r['fechaGenerada'] ?? null
        ];
    }

    echo json_encode(['ok' => true, 'count' => count($alertas), 'alertas' => $alertas], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error inesperado', 'detalle' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}