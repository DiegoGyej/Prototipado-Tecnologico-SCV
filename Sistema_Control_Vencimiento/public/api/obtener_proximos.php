<?php
// public/api/obtener_proximos.php
// Devuelve JSON con los lotes próximos a vencer.

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';
    require_login_api();

    // Parámetros
    $days = isset($_GET['days']) && is_numeric($_GET['days']) ? (int)$_GET['days'] : 30;
    // Si days == 0 interpretamos como 7 
    if ($days === 0) $days = 7;
    if ($days < 0) $days = 30;

    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 100;
    if ($limit <= 0) $limit = 100;
    if ($limit > 1000) $limit = 1000; 

    // Query: lotes con fechaVencimiento >= hoy y dias faltantes <= days
    $sql = "
        SELECT
            l.idLote,
            p.idProducto,
            p.codigoProducto,
            p.nombre AS producto,
            l.codigoLote,
            l.cantidad,
            l.fechaIngreso,
            l.fechaVencimiento,
            DATEDIFF(l.fechaVencimiento, CURDATE()) AS dias_faltantes
        FROM Lote l
        JOIN Producto p ON l.idProducto = p.idProducto
        WHERE l.fechaVencimiento >= CURDATE()
          AND DATEDIFF(l.fechaVencimiento, CURDATE()) <= :days
        ORDER BY l.fechaVencimiento ASC
        LIMIT :lim
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar y unificar nombres (camelCase)
    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'idLote' => (int)$r['idLote'],
            'idProducto' => (int)$r['idProducto'],
            'codigoProducto' => $r['codigoProducto'],
            'producto' => $r['producto'],
            'codigoLote' => $r['codigoLote'],
            'cantidad' => (int)$r['cantidad'],
            'fechaIngreso' => $r['fechaIngreso'],
            'fechaVencimiento' => $r['fechaVencimiento'],
            'diasFaltantes' => (int)$r['dias_faltantes']
        ];
    }

    echo json_encode([
        'ok' => true,
        'days' => $days,
        'limit' => $limit,
        'count' => count($result),
        'lotes' => $result
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error en la consulta', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error inesperado', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
