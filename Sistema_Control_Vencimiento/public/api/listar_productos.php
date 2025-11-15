<?php
// public/api/listar_productos.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/conexion.php';
    require_once __DIR__ . '/../../src/autenticacion.php';
    require_login_api();

    // Parámetros opcionales
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
    if ($limit <= 0 || $limit > 1000) $limit = 200;

    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($q === '') {
        $stmt = $pdo->prepare("SELECT idProducto, codigoProducto, nombre FROM Producto ORDER BY idProducto DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare("SELECT idProducto, codigoProducto, nombre FROM Producto
                               WHERE codigoProducto LIKE :q OR nombre LIKE :q
                               ORDER BY idProducto DESC LIMIT :lim");
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'productos' => $rows], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error DB', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
